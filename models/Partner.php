<?php
require_once __DIR__ . '/../bootstrap.php';
class PartnerManager
{
    private PDO $pdo; private string $uploadsDirectory;
    private const ALLOWED_IMAGE_TYPES=['image/jpeg'=>'jpg','image/jpg'=>'jpg','image/pjpeg'=>'jpg','image/png'=>'png','image/x-png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
    public function __construct($partnersFile=null,$uploadsDirectory=null){$this->pdo=db();$this->uploadsDirectory=$uploadsDirectory?:__DIR__.'/../uploads/partners';if(!is_dir($this->uploadsDirectory))mkdir($this->uploadsDirectory,0775,true);}
    public function listPartners():array{return $this->pdo->query('SELECT id,name,description,image_path,created_at,updated_at FROM partners ORDER BY created_at ASC,name ASC')->fetchAll();}
    public function getPartner(string $id){$s=$this->pdo->prepare('SELECT * FROM partners WHERE id=? LIMIT 1');$s->execute([$id]);return $s->fetch()?:false;}
    public function countPartners():int{return (int)$this->pdo->query('SELECT COUNT(*) FROM partners')->fetchColumn();}
    public function adminSavePartner(?string $id,array $data,?array $file=null):array{
        $create=$id===null||trim($id)==='';$existing=$create?null:$this->getPartner($id);if(!$create&&!$existing)return ['success'=>false,'errors'=>['Parceiro nao encontrado.']];
        $name=trim((string)($data['name']??''));$desc=trim((string)($data['description']??''));$path=trim((string)($data['image_path']??''));$e=[];if($name==='')$e[]='Nome do parceiro e obrigatorio.';if($desc==='')$e[]='Descricao do parceiro e obrigatoria.';
        $has=$this->hasUpload($file);if($create&&!$has&&$path==='')$e[]='Envie uma imagem ou informe um caminho valido para o card do parceiro.';if($path!==''&&!$has&&!$this->validPath($path))$e[]='O caminho informado para a imagem nao foi encontrado.';if($has){$v=$this->validateUpload($file);if(!$v['success'])$e[]=$v['error'];}if($e)return ['success'=>false,'errors'=>$e];
        $old=(string)($existing['image_path']??'');$next=$path!==''?$path:$old;if($has){$u=$this->store($file);if(!$u['success'])return ['success'=>false,'errors'=>[$u['error']]];$next=$u['path'];}
        if($create){$id=$id?:uniqid('partner_');$s=$this->pdo->prepare('INSERT INTO partners (id,name,description,image_path,created_at,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)');$s->execute([$id,$name,$desc,$next]);$created=true;}else{$s=$this->pdo->prepare('UPDATE partners SET name=?,description=?,image_path=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$name,$desc,$next,$id]);$created=false;}
        if($has&&$old!==''&&$old!==$next)$this->deleteManaged($old);return ['success'=>true,'partner'=>$this->getPartner((string)$id),'created'=>$created];
    }
    public function deletePartner(string $id):bool{$p=$this->getPartner($id);if(!$p)return false;$s=$this->pdo->prepare('DELETE FROM partners WHERE id=?');$s->execute([$id]);if($s->rowCount()){$this->deleteManaged((string)$p['image_path']);return true;}return false;}
    private function hasUpload(?array $f):bool{return is_array($f)&&isset($f['error'])&&(int)$f['error']!==UPLOAD_ERR_NO_FILE;}
    private function validateUpload(?array $f):array{if(!$this->hasUpload($f))return ['success'=>true];if((int)($f['error']??-1)!==UPLOAD_ERR_OK)return ['success'=>false,'error'=>'Nao foi possivel processar a imagem.'];if((int)($f['size']??0)>5*1024*1024)return ['success'=>false,'error'=>'A imagem deve ter no maximo 5 MB.'];$m=$this->mime((string)($f['tmp_name']??''));return isset(self::ALLOWED_IMAGE_TYPES[$m])?['success'=>true,'extension'=>self::ALLOWED_IMAGE_TYPES[$m]]:['success'=>false,'error'=>'Formato de imagem nao suportado. Envie JPG, PNG, WEBP ou GIF.'];}
    private function store(array $f):array{$v=$this->validateUpload($f);if(!$v['success'])return $v;$n=uniqid('partner_',true).'.'.$v['extension'];if(!move_uploaded_file($f['tmp_name'],$this->uploadsDirectory.'/'.$n))return ['success'=>false,'error'=>'Nao foi possivel salvar a imagem.'];return ['success'=>true,'path'=>'./uploads/partners/'.$n];}
    private function mime(string $p):string{if(function_exists('finfo_open')){$f=finfo_open(FILEINFO_MIME_TYPE);if($f){$m=(string)finfo_file($f,$p);finfo_close($f);return $m;}}return '';}
    private function validPath(string $p):bool{if(filter_var($p,FILTER_VALIDATE_URL)!==false)return true;$c=strpos($p,'./')===0?__DIR__.'/../'.substr($p,2):__DIR__.'/../'.ltrim($p,'/\\');return is_file($c);}
    private function deleteManaged(string $p):void{if(strpos($p,'./uploads/partners/')!==0)return;$f=$this->uploadsDirectory.'/'.basename($p);if(is_file($f))@unlink($f);}
}
