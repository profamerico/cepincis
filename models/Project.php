<?php
require_once __DIR__ . '/../bootstrap.php';

class ProjectManager
{
    private PDO $db;
    private string $uploadsDirectory;

    private const THEMATIC_AREA_OPTIONS = [
        'EduCIS' => 'EduCIS',
        'EcoMat' => 'EcoMat',
        'IoT' => 'IoT',
        'CarbonZero' => 'CarbonZero',
        'UrbanSmart' => 'UrbanSmart',
    ];

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/pjpeg' => 'jpg',
        'image/png' => 'png', 'image/x-png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
    ];

    public function __construct($projectsFile = null, $uploadsDirectory = null)
    {
        $this->db = db();
        $this->uploadsDirectory = $uploadsDirectory ?: __DIR__ . '/../uploads/projects';
        if (!is_dir($this->uploadsDirectory)) @mkdir($this->uploadsDirectory, 0775, true);
    }

    public function createProject($userId, $title, $description, $category = null, $tags = [])
    {
        $result = $this->adminSaveProject(null, [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'category' => $category ?? $this->getDefaultThematicArea(),
            'tags' => $tags,
            'status' => 'active',
        ]);
        return $result['success'] ? $result['project'] : false;
    }

    public function getThematicAreaOptions(): array { return self::THEMATIC_AREA_OPTIONS; }
    public function getDefaultThematicArea(): string { return array_key_first(self::THEMATIC_AREA_OPTIONS) ?: 'EduCIS'; }

    public function getAllProjects(): array
    {
        $stmt = $this->db->query('SELECT * FROM projects ORDER BY updated_at DESC, created_at DESC');
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getUserProjects($userId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE owner_user_id = :user_id ORDER BY updated_at DESC');
        $stmt->execute(['user_id' => (int) $userId]);
        return $this->normalizeRows($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getProject($projectId)
    {
        $stmt = $this->db->prepare('SELECT * FROM projects WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (string) $projectId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->normalizeProject($row) : false;
    }

    public function getProjectTagList(array $project, bool $includeCategory = true): array
    {
        $tags = $this->normalizeTags($project['tags'] ?? []);
        if ($includeCategory) {
            $category = trim((string) ($project['category'] ?? ''));
            if ($category !== '') $tags = $this->normalizeTags(array_merge($tags, [$category]));
        }
        return $tags;
    }

    public function getProjectTags(?array $projects = null): array
    {
        $projects = $projects ?? $this->getAllProjects();
        $all = [];
        foreach ($projects as $project) $all = array_merge($all, $this->getProjectTagList($project));
        return $this->normalizeTags($all);
    }

    public function updateProject($projectId, $data)
    {
        $result = $this->adminSaveProject((string) $projectId, $data);
        return $result['success'] ? $result['project'] : false;
    }

    public function adminSaveProject(?string $projectId, array $data, ?array $uploadedImage = null): array
    {
        $isCreate = $projectId === null || trim($projectId) === '';
        $existing = $isCreate ? null : $this->getProject($projectId);
        if (!$isCreate && !is_array($existing)) return ['success' => false, 'errors' => ['Projeto não encontrado.']];

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $participation = trim((string) ($data['participation_info'] ?? ''));
        $categoryInput = trim((string) ($data['category'] ?? ''));
        $category = $this->normalizeCategory($categoryInput);
        $rawTags = $this->parseTags($data['tags'] ?? []);
        $tags = $this->normalizeTags($rawTags);
        $status = $this->normalizeStatus((string) ($data['status'] ?? 'active'));
        $userId = $this->normalizeUserId($data['user_id'] ?? $data['owner_user_id'] ?? null);
        $imagePath = trim((string) ($data['image_path'] ?? ''));
        $errors = [];

        if ($title === '') $errors[] = 'Título é obrigatório.';
        if ($description === '') $errors[] = 'Descrição do projeto é obrigatória.';
        if ($categoryInput === '' || !$this->isValidThematicArea($categoryInput)) $errors[] = 'Área temática inválida. Escolha uma das 5 siglas oficiais.';
        if ($this->findInvalidTags($rawTags)) $errors[] = 'As tags devem usar apenas as 5 siglas oficiais das Áreas Temáticas.';
        if ($userId === null) $errors[] = 'Responsável pelo projeto é obrigatório.';

        $hasUpload = $this->hasUploadedFile($uploadedImage);
        if ($imagePath !== '' && !$hasUpload) {
            $validation = $this->validateImagePath($imagePath);
            if (!$validation['success']) $errors[] = $validation['error'];
        }
        if ($hasUpload) {
            $validation = $this->validateImageUpload($uploadedImage);
            if (!$validation['success']) $errors[] = $validation['error'];
        }
        if ($errors) return ['success' => false, 'errors' => $errors];

        $previousImage = (string) ($existing['image_path'] ?? '');
        $nextImage = $imagePath !== '' ? $imagePath : $previousImage;
        if ($hasUpload) {
            $stored = $this->storeImageUpload($uploadedImage);
            if (!$stored['success']) return ['success' => false, 'errors' => [$stored['error']]];
            $nextImage = $stored['path'];
        }

        $id = $isCreate ? trim((string) ($data['id'] ?? '')) : (string) $projectId;
        if ($isCreate && $id === '') $id = 'prj_' . bin2hex(random_bytes(10));

        try {
            if ($isCreate) {
                $stmt = $this->db->prepare(
                    "INSERT INTO projects
                    (id, owner_user_id, title, description, category, status, publication_status, authentication_status, image_path, participation_info, tags_json, created_at, updated_at)
                    VALUES (:id,:owner_user_id,:title,:description,:category,:status,:publication_status,'missing',:image_path,:participation_info,:tags_json,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)"
                );
                $stmt->execute([
                    'id'=>$id,'owner_user_id'=>$userId,'title'=>$title,'description'=>$description,'category'=>$category,'status'=>$status,
                    'publication_status'=>($data['publication_status'] ?? 'published'),'image_path'=>$nextImage,'participation_info'=>$participation,
                    'tags_json'=>json_encode($tags, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
                ]);
            } else {
                $stmt = $this->db->prepare(
                    'UPDATE projects SET owner_user_id=:owner_user_id,title=:title,description=:description,category=:category,status=:status,publication_status=:publication_status,image_path=:image_path,participation_info=:participation_info,tags_json=:tags_json,updated_at=CURRENT_TIMESTAMP WHERE id=:id'
                );
                $stmt->execute([
                    'owner_user_id'=>$userId,'title'=>$title,'description'=>$description,'category'=>$category,'status'=>$status,
                    'publication_status'=>(string)($data['publication_status'] ?? ($existing['publication_status'] ?? 'published')),
                    'image_path'=>$nextImage,'participation_info'=>$participation,
                    'tags_json'=>json_encode($tags, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),'id'=>$id,
                ]);
            }
        } catch (Throwable $e) {
            if ($hasUpload && $nextImage !== $previousImage) $this->deleteManagedImage($nextImage);
            return ['success'=>false,'errors'=>['Não foi possível salvar o projeto no banco de dados.']];
        }

        if ($hasUpload && $previousImage !== '' && $previousImage !== $nextImage) $this->deleteManagedImage($previousImage);
        return ['success'=>true,'project'=>$this->getProject($id),'created'=>$isCreate];
    }

    public function deleteProject($projectId): bool
    {
        $project = $this->getProject($projectId);
        if (!$project) return false;
        try {
            $this->db->beginTransaction();
            foreach (['project_authentication_history','project_timeline_history','project_timeline_events','project_collaboration_invites','project_collaborators','project_documents','notifications'] as $table) {
                $stmt = $this->db->prepare("DELETE FROM {$table} WHERE project_id=:project_id");
                $stmt->execute(['project_id'=>(string)$projectId]);
            }
            $stmt = $this->db->prepare('DELETE FROM projects WHERE id=:id');
            $stmt->execute(['id'=>(string)$projectId]);
            $this->db->commit();
            $this->deleteManagedImage((string)($project['image_path']??''));
            return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return false;
        }
    }

    public function clearProjectsForUser(int $userId): int
    {
        $stmt=$this->db->prepare('UPDATE projects SET owner_user_id=NULL,updated_at=CURRENT_TIMESTAMP WHERE owner_user_id=:user_id');
        try{$stmt->execute(['user_id'=>$userId]);return $stmt->rowCount();}catch(Throwable $e){return 0;}
    }

    public function getUserStats($userId): array
    {
        $stats=['total'=>0,'active'=>0,'completed'=>0,'pending'=>0];
        $stmt=$this->db->prepare('SELECT status,COUNT(*) AS total FROM projects WHERE owner_user_id=:user_id GROUP BY status');$stmt->execute(['user_id'=>(int)$userId]);
        foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row){$n=(int)$row['total'];$stats['total']+=$n;$key=(string)$row['status'];if(isset($stats[$key]))$stats[$key]+=$n;}
        return $stats;
    }

    public function getProjectStats(): array
    {
        $stats=['total'=>0,'active'=>0,'completed'=>0,'pending'=>0,'without_owner'=>0,'authenticated'=>0,'authentication_pending'=>0,'authentication_rejected'=>0,'authentication_missing'=>0];
        $rows=$this->db->query('SELECT status,authentication_status,owner_user_id,COUNT(*) AS total FROM projects GROUP BY status,authentication_status,owner_user_id')->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $row){$n=(int)$row['total'];$stats['total']+=$n;$status=(string)$row['status'];if(isset($stats[$status]))$stats[$status]+=$n;if($row['owner_user_id']===null)$stats['without_owner']+=$n;$auth=(string)$row['authentication_status'];if($auth==='approved')$stats['authenticated']+=$n;elseif($auth==='pending')$stats['authentication_pending']+=$n;elseif($auth==='rejected')$stats['authentication_rejected']+=$n;else $stats['authentication_missing']+=$n;}
        return $stats;
    }

    public function setProjectAuthenticationStatus(string $projectId,string $status,?string $documentId=null,?string $authenticatedAt=null):bool
    {
        $status=$this->normalizeAuthenticationStatus($status);
        $stmt=$this->db->prepare('UPDATE projects SET authentication_status=:status,authenticated_document_id=:document_id,authenticated_at=:authenticated_at,updated_at=CURRENT_TIMESTAMP WHERE id=:id');
        $stmt->execute(['status'=>$status,'document_id'=>$status==='approved'?$documentId:null,'authenticated_at'=>$status==='approved'?($authenticatedAt?:date('Y-m-d H:i:s')):null,'id'=>$projectId]);
        return $stmt->rowCount()>=0;
    }

    private function normalizeRows(array $rows):array{$out=[];foreach($rows as $row)$out[]=$this->normalizeProject($row);return $out;}
    private function normalizeProject(array $p):array{
        $tags=[];$decoded=json_decode((string)($p['tags_json']??''),true);if(is_array($decoded))$tags=$decoded;
        return ['id'=>(string)($p['id']??''),'user_id'=>isset($p['owner_user_id'])&&$p['owner_user_id']!==null?(int)$p['owner_user_id']:null,'owner_user_id'=>$p['owner_user_id']??null,'title'=>trim((string)($p['title']??'')),'description'=>trim((string)($p['description']??'')),'participation_info'=>trim((string)($p['participation_info']??'')),'category'=>$this->normalizeCategory((string)($p['category']??'')),'tags'=>$this->normalizeTags($tags),'image_path'=>trim((string)($p['image_path']??'')),'status'=>$this->normalizeStatus((string)($p['status']??'active')),'publication_status'=>(string)($p['publication_status']??'published'),'authentication_status'=>$this->normalizeAuthenticationStatus((string)($p['authentication_status']??'missing')),'authenticated_document_id'=>$p['authenticated_document_id']??null,'authenticated_at'=>$p['authenticated_at']??null,'created_at'=>(string)($p['created_at']??''),'updated_at'=>(string)($p['updated_at']??'')];
    }
    private function parseTags($tags):array{if(is_string($tags))$tags=preg_split('/[,;\r\n]+/',$tags)?:[];return is_array($tags)?$tags:[];}
    private function normalizeTags($tags):array{$out=[];foreach($this->parseTags($tags) as $tag){$canonical=$this->canonicalizeThematicArea((string)$tag);if($canonical!==null&&!in_array($canonical,$out,true))$out[]=$canonical;}return $out;}
    private function findInvalidTags($tags):array{$invalid=[];foreach($this->parseTags($tags) as $tag){if(trim((string)$tag)!==''&&$this->canonicalizeThematicArea((string)$tag)===null)$invalid[]=(string)$tag;}return array_values(array_unique($invalid));}
    private function normalizeCategory(string $category):string{return $this->canonicalizeThematicArea($category)??$this->getDefaultThematicArea();}
    private function isValidThematicArea(string $value):bool{return $this->canonicalizeThematicArea($value)!==null;}
    private function canonicalizeThematicArea(string $value):?string{foreach(array_keys(self::THEMATIC_AREA_OPTIONS) as $key){if(strcasecmp(trim($value),$key)===0)return $key;}return null;}
    private function normalizeStatus(string $status):string{return in_array(strtolower(trim($status)),['active','completed','pending','draft'],true)?strtolower(trim($status)):'active';}
    private function normalizeAuthenticationStatus(string $status):string{return in_array(strtolower(trim($status)),['missing','pending','approved','rejected'],true)?strtolower(trim($status)):'missing';}
    private function normalizeUserId($id):?int{return $id===null||$id===''?null:(int)$id;}
    private function hasUploadedFile(?array $file):bool{return is_array($file)&&isset($file['error'])&&(int)$file['error']!==UPLOAD_ERR_NO_FILE;}
    private function validateImageUpload(?array $file):array{if(!$this->hasUploadedFile($file))return ['success'=>true];if((int)($file['error']??-1)!==UPLOAD_ERR_OK)return ['success'=>false,'error'=>'Não foi possível processar a imagem enviada.'];if((int)($file['size']??0)>6*1024*1024)return ['success'=>false,'error'=>'A imagem do projeto deve ter no máximo 6 MB.'];$mime=$this->detectMimeType((string)($file['tmp_name']??''));$ext=self::ALLOWED_IMAGE_TYPES[$mime]??'';if($ext===''){ $given=strtolower(pathinfo((string)($file['name']??''),PATHINFO_EXTENSION));if($given==='jpeg')$given='jpg';foreach(self::ALLOWED_IMAGE_TYPES as $m=>$e){if($e===$given){$ext=$e;$mime=$m;break;}}}return $ext!==''?['success'=>true,'mime_type'=>$mime,'extension'=>$ext]:['success'=>false,'error'=>'Formato de imagem não suportado. Envie JPG, PNG, WEBP ou GIF.'];}
    private function storeImageUpload(?array $file):array{$v=$this->validateImageUpload($file);if(!$v['success'])return $v;$ext=$v['extension'];$name='project_'.bin2hex(random_bytes(10)).'.'.$ext;$dest=$this->uploadsDirectory.DIRECTORY_SEPARATOR.$name;if(!move_uploaded_file((string)$file['tmp_name'],$dest))return ['success'=>false,'error'=>'Não foi possível salvar a imagem do projeto no servidor.'];return ['success'=>true,'path'=>'./uploads/projects/'.$name];}
    private function detectMimeType(string $path):string{if(function_exists('finfo_open')){$f=finfo_open(FILEINFO_MIME_TYPE);if($f){$m=(string)finfo_file($f,$path);finfo_close($f);return $m;}}return function_exists('mime_content_type')?(string)@mime_content_type($path):'';}
    private function validateImagePath(string $path):array{if(filter_var($path,FILTER_VALIDATE_URL)!==false&&in_array(strtolower((string)parse_url($path,PHP_URL_SCHEME)),['http','https'],true))return ['success'=>true];$candidate=str_starts_with($path,'./')?__DIR__.'/../'.substr($path,2):__DIR__.'/../'.ltrim($path,'/\\');return is_file($candidate)?['success'=>true]:['success'=>false,'error'=>'O caminho informado para a imagem do projeto não foi encontrado no projeto.'];}
    private function deleteManagedImage(string $path):void{if(!str_starts_with($path,'./uploads/projects/'))return;$file=$this->uploadsDirectory.DIRECTORY_SEPARATOR.basename($path);if(is_file($file))@unlink($file);}
}
