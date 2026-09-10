<?php
require_once __DIR__ . '/../bootstrap.php';

class ProjectManager
{
    private PDO $pdo;
    private string $uploadsDirectory;

    private const THEMATIC_AREA_OPTIONS = [
        'EduCIS' => 'EduCIS', 'EcoMat' => 'EcoMat', 'IoT' => 'IoT',
        'CarbonZero' => 'CarbonZero', 'UrbanSmart' => 'UrbanSmart',
    ];
    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg',
        'image/png' => 'png', 'image/x-png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
    ];

    public function __construct($projectsFile = null, $uploadsDirectory = null)
    {
        $this->pdo = db();
        $this->uploadsDirectory = $uploadsDirectory ?: __DIR__ . '/../uploads/projects';
        if (!is_dir($this->uploadsDirectory)) mkdir($this->uploadsDirectory, 0775, true);
        $this->ensureSchemaCompatibility();
    }

    private function ensureSchemaCompatibility(): void
    {
        $cols = $this->pdo->query("SHOW COLUMNS FROM projects")->fetchAll();
        $names = array_column($cols, 'Field');
        if (!in_array('tags_json', $names, true)) {
            $this->pdo->exec("ALTER TABLE projects ADD COLUMN tags_json TEXT NULL AFTER participation_info");
        }
    }

    public function createProject($userId, $title, $description, $category = null, $tags = [])
    {
        $r = $this->adminSaveProject(null, ['user_id'=>$userId,'title'=>$title,'description'=>$description,'category'=>$category ?? $this->getDefaultThematicArea(),'tags'=>$tags,'status'=>'active']);
        return $r['success'] ? $r['project'] : false;
    }
    public function getThematicAreaOptions(): array { return self::THEMATIC_AREA_OPTIONS; }
    public function getDefaultThematicArea(): string { return array_key_first(self::THEMATIC_AREA_OPTIONS) ?: 'EduCIS'; }

    public function getAllProjects(): array { return $this->fetchProjects(); }
    public function getUserProjects($userId): array {
        $stmt=$this->pdo->prepare("SELECT * FROM projects WHERE owner_user_id = ? ORDER BY updated_at DESC"); $stmt->execute([(int)$userId]); return $this->normalizeRows($stmt->fetchAll());
    }
    public function getProject($projectId) {
        $stmt=$this->pdo->prepare("SELECT * FROM projects WHERE id = ? LIMIT 1"); $stmt->execute([(string)$projectId]); $row=$stmt->fetch(); return $row ? $this->normalizeProject($row) : false;
    }
    public function getProjectTagList(array $project, bool $includeCategory=true): array {
        $tags=$this->normalizeTags($project['tags'] ?? []); if($includeCategory && trim((string)($project['category']??''))!=='') $tags=$this->normalizeTags(array_merge($tags,[$project['category']])); return $tags;
    }
    public function getProjectTags(?array $projects=null): array { $all=[]; foreach($projects ?? $this->fetchProjects() as $p) $all=array_merge($all,$this->getProjectTagList($p)); return $this->normalizeTags($all); }
    public function updateProject($projectId,$data){$r=$this->adminSaveProject((string)$projectId,$data);return $r['success']?$r['project']:false;}

    public function adminSaveProject(?string $projectId,array $data,?array $uploadedImage=null):array {
        $isCreate=$projectId===null||trim($projectId)==='';
        $existing=$isCreate?null:$this->getProject($projectId);
        if(!$isCreate&&!$existing) return ['success'=>false,'errors'=>['Projeto nao encontrado.']];
        $title=trim((string)($data['title']??'')); $description=trim((string)($data['description']??'')); $participation=trim((string)($data['participation_info']??''));
        $categoryInput=trim((string)($data['category']??'')); $category=$this->normalizeCategory($categoryInput); $tags=$this->normalizeTags($data['tags']??[]); $status=$this->normalizeStatus((string)($data['status']??'active'));
        $userId=$this->normalizeUserId($data['user_id']??$data['owner_user_id']??null); $imagePath=trim((string)($data['image_path']??'')); $errors=[];
        if($title==='')$errors[]='Titulo e obrigatorio.'; if($description==='')$errors[]='Descricao do projeto e obrigatoria.';
        if($categoryInput===''||!$this->isValidThematicArea($categoryInput))$errors[]='Area tematica invalida. Escolha uma das 5 siglas oficiais.';
        if(!empty($this->findInvalidTags($data['tags']??[])))$errors[]='As tags devem usar apenas as 5 siglas oficiais das Areas Tematicas.';
        if($userId===null)$errors[]='Responsavel pelo projeto e obrigatorio.';
        $hasUpload=$this->hasUploadedFile($uploadedImage); if($imagePath!==''&&!$hasUpload&&!$this->validateImagePath($imagePath)['success'])$errors[]='O caminho informado para a imagem nao foi encontrado.';
        if($hasUpload){$v=$this->validateImageUpload($uploadedImage);if(!$v['success'])$errors[]=$v['error'];}
        if($errors)return ['success'=>false,'errors'=>$errors];
        $previous=(string)($existing['image_path']??''); $next=$imagePath!==''?$imagePath:$previous;
        if($hasUpload){$u=$this->storeImageUpload($uploadedImage);if(!$u['success'])return ['success'=>false,'errors'=>[$u['error']]];$next=$u['path'];}
        if($isCreate){$id=trim((string)($data['id']??'')); if($id==='')$id=uniqid('prj_'); $stmt=$this->pdo->prepare("INSERT INTO projects (id,owner_user_id,title,description,category,status,publication_status,authentication_status,image_path,participation_info,tags_json,created_at,updated_at) VALUES (?,?,?,?,?,?, 'published','missing',?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)"); $stmt->execute([$id,$userId,$title,$description,$category,$status,$next,$participation,json_encode($tags,JSON_UNESCAPED_UNICODE)]); $created=true;
        } else {$id=(string)$projectId; $stmt=$this->pdo->prepare("UPDATE projects SET owner_user_id=?,title=?,description=?,category=?,status=?,image_path=?,participation_info=?,tags_json=?,updated_at=CURRENT_TIMESTAMP WHERE id=?");$stmt->execute([$userId,$title,$description,$category,$status,$next,$participation,json_encode($tags,JSON_UNESCAPED_UNICODE),$id]);$created=false;}
        if($hasUpload&&$previous!==''&&$previous!==$next)$this->deleteManagedImage($previous);
        $saved=$this->getProject($id); return ['success'=>true,'project'=>$saved,'created'=>$created];
    }

    public function deleteProject($projectId):bool {
        $p=$this->getProject($projectId); if(!$p)return false; $this->pdo->beginTransaction(); try { foreach(['project_documents','project_collaborators','project_collaboration_invites','project_authentication_history','project_timeline_history','project_timeline_events','notifications'] as $table){$stmt=$this->pdo->prepare("DELETE FROM {$table} WHERE project_id=?");$stmt->execute([(string)$projectId]);} $stmt=$this->pdo->prepare("DELETE FROM projects WHERE id=?");$stmt->execute([(string)$projectId]);$this->pdo->commit();$this->deleteManagedImage((string)($p['image_path']??''));return $stmt->rowCount()>0;}catch(Throwable $e){$this->pdo->rollBack();return false;}}
    public function clearProjectsForUser(int $userId):int {$stmt=$this->pdo->prepare("UPDATE projects SET owner_user_id=NULL, updated_at=CURRENT_TIMESTAMP WHERE owner_user_id=?"); try{$stmt->execute([$userId]);return $stmt->rowCount();}catch(Throwable $e){return 0;}}
    public function getUserStats($userId):array {$stmt=$this->pdo->prepare("SELECT status,COUNT(*) n FROM projects WHERE owner_user_id=? GROUP BY status");$stmt->execute([(int)$userId]);$s=['total'=>0,'active'=>0,'completed'=>0,'pending'=>0];foreach($stmt->fetchAll() as $r){$k=(string)$r['status'];$n=(int)$r['n'];$s['total']+=$n;if(isset($s[$k]))$s[$k]+=$n;}return $s;}
    public function getProjectStats():array {$s=['total'=>0,'active'=>0,'completed'=>0,'pending'=>0,'without_owner'=>0];foreach($this->pdo->query("SELECT status,owner_user_id,COUNT(*) n FROM projects GROUP BY status,owner_user_id")->fetchAll() as $r){$n=(int)$r['n'];$s['total']+=$n;$k=(string)$r['status'];if(isset($s[$k]))$s[$k]+=$n;if($r['owner_user_id']===null)$s['without_owner']+=$n;}return $s;}

    private function fetchProjects():array{return $this->normalizeRows($this->pdo->query("SELECT * FROM projects ORDER BY updated_at DESC")->fetchAll());}
    private function normalizeRows(array $rows):array{$out=[];foreach($rows as $r)$out[]=$this->normalizeProject($r);return $out;}
    private function normalizeProject(array $p):array{$tags=[];if(isset($p['tags_json'])){$d=json_decode((string)$p['tags_json'],true);if(is_array($d))$tags=$d;}return ['id'=>(string)$p['id'],'user_id'=>isset($p['owner_user_id'])?(int)$p['owner_user_id']:null,'owner_user_id'=>$p['owner_user_id']??null,'title'=>trim((string)($p['title']??'')),'description'=>trim((string)($p['description']??'')),'participation_info'=>trim((string)($p['participation_info']??'')),'category'=>$this->normalizeCategory((string)($p['category']??$this->getDefaultThematicArea())),'tags'=>$this->normalizeTags($tags),'image_path'=>trim((string)($p['image_path']??'')),'status'=>$this->normalizeStatus((string)($p['status']??'active')),'publication_status'=>(string)($p['publication_status']??'published'),'authentication_status'=>(string)($p['authentication_status']??'missing'),'authenticated_document_id'=>$p['authenticated_document_id']??null,'authenticated_at'=>$p['authenticated_at']??null,'created_at'=>(string)($p['created_at']??''),'updated_at'=>(string)($p['updated_at']??'')];}
    private function normalizeTags($tags):array{if(is_string($tags))$tags=preg_split('/[,;]+/',$tags)?:[];$out=[];foreach((array)$tags as $tag){$tag=trim((string)$tag);if($tag!==''&&!in_array($tag,$out,true))$out[]=$tag;}return $out;}
    private function findInvalidTags($tags):array{$invalid=[];foreach((array)$tags as $tag){$tag=trim((string)$tag);if($tag!==''&&!isset(self::THEMATIC_AREA_OPTIONS[$tag]))$invalid[]=$tag;}return $invalid;}
    private function normalizeCategory(string $c):string{return isset(self::THEMATIC_AREA_OPTIONS[$c])?$c:$this->getDefaultThematicArea();}
    private function isValidThematicArea(string $c):bool{return isset(self::THEMATIC_AREA_OPTIONS[$c]);}
    private function normalizeStatus(string $s):string{return in_array($s,['active','completed','pending','draft'],true)?$s:'active';}
    private function normalizeUserId($id):?int{return $id===null||$id===''?null:(int)$id;}
    private function hasUploadedFile(?array $f):bool{return is_array($f)&&isset($f['error'])&&(int)$f['error']!==UPLOAD_ERR_NO_FILE;}
    private function validateImageUpload(?array $f):array{if(!$this->hasUploadedFile($f))return ['success'=>true];if((int)($f['error']??-1)!==UPLOAD_ERR_OK)return ['success'=>false,'error'=>'Nao foi possivel processar a imagem.'];if((int)($f['size']??0)>5*1024*1024)return ['success'=>false,'error'=>'A imagem deve ter no maximo 5 MB.'];$mime=$this->detectMimeType((string)($f['tmp_name']??''));$ext=self::ALLOWED_IMAGE_TYPES[$mime]??'';return $ext!==''?['success'=>true,'extension'=>$ext]:['success'=>false,'error'=>'Formato de imagem nao suportado.'];}
    private function storeImageUpload(?array $f):array{$v=$this->validateImageUpload($f);if(!$v['success'])return $v;$ext=$v['extension'];$name=uniqid('project_',true).'.'.$ext;$dest=$this->uploadsDirectory.DIRECTORY_SEPARATOR.$name;if(!move_uploaded_file($f['tmp_name'],$dest))return ['success'=>false,'error'=>'Nao foi possivel salvar a imagem.'];return ['success'=>true,'path'=>'./uploads/projects/'.$name];}
    private function detectMimeType(string $p):string{if($p!==''&&function_exists('finfo_open')){$f=finfo_open(FILEINFO_MIME_TYPE);if($f){$m=(string)finfo_file($f,$p);finfo_close($f);return $m;}}return function_exists('mime_content_type')?(string)@mime_content_type($p):'';}
    private function validateImagePath(string $p):array{if(filter_var($p,FILTER_VALIDATE_URL)!==false)return ['success'=>true];$c=strpos($p,'./')===0?__DIR__.'/../'.substr($p,2):__DIR__.'/../'.ltrim($p,'/\\');return ['success'=>is_file($c),'error'=>'Imagem nao encontrada.'];}
    private function deleteManagedImage(string $p):void{if(strpos($p,'./uploads/projects/')!==0)return;$f=$this->uploadsDirectory.DIRECTORY_SEPARATOR.basename($p);if(is_file($f))@unlink($f);}
}
