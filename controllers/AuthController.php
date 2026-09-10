<?php
require_once __DIR__ . '/../bootstrap.php';

class AuthController
{
    private const ROLE_MEMBER='member', ROLE_ACADEMIC_RESEARCHER='academic_researcher', ROLE_ASSOCIATE_RESEARCHER='associate_researcher', ROLE_FULL_RESEARCHER='full_researcher', ROLE_ADMIN='admin';
    private const ROLE_DEFINITIONS=[
        self::ROLE_MEMBER=>['label'=>'Usuario','rank'=>10],self::ROLE_ACADEMIC_RESEARCHER=>['label'=>'Pesquisador Academico','rank'=>20],
        self::ROLE_ASSOCIATE_RESEARCHER=>['label'=>'Pesquisador Associado','rank'=>30],self::ROLE_FULL_RESEARCHER=>['label'=>'Pesquisador Pleno','rank'=>40],self::ROLE_ADMIN=>['label'=>'Administrador','rank'=>100],
    ];
    private PDO $pdo;
    public function __construct($usersFile=null){$this->pdo=db();}

    public function requireAuth():void{if($this->getCurrentUser()===null){header('Location: login.php');exit;}}
    public function requireAdmin():void{$this->requireAuth();if(!$this->isAdmin()){header('Location: dashboard.php');exit;}}
    public function requireAtLeastRole(string $requiredRole,string $fallback='dashboard.php'):void{$this->requireAuth();if(!$this->hasAtLeastRole($requiredRole,$this->getCurrentUser())){header('Location: '.$fallback);exit;}}
    public function redirectIfLoggedIn():void{if($this->getCurrentUser()!==null){header('Location: dashboard.php');exit;}}

    public function login(string $username,string $password):array{
        $username=trim($username);if($username==='')return ['success'=>false,'errors'=>['Usuario e obrigatorio.']];if($password==='')return ['success'=>false,'errors'=>['Senha e obrigatoria.']];
        $s=$this->pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');$s->execute([$username]);$u=$s->fetch();
        if(!$u||!password_verify($password,(string)($u['password_hash']??'')))return ['success'=>false,'errors'=>['Usuario ou senha invalidos.']];
        $u=$this->normalizeUser($u);$this->syncSessionUser($u);return ['success'=>true,'user'=>$u];
    }
    public function loginSocialUser(string $displayName,string $email='',string $provider='social'):array{
        $displayName=trim($displayName);$email=trim($email);$provider=trim($provider)?:'social';if($displayName==='')return ['success'=>false,'errors'=>['Nao foi possivel identificar o usuario da rede social.']];
        $u=null;if($email!==''){$s=$this->pdo->prepare('SELECT * FROM users WHERE email=? LIMIT 1');$s->execute([$email]);$u=$s->fetch()?:null;}
        if(!$u){$username=$this->generateUniqueUsername($displayName,$email,$provider);$hash=password_hash(bin2hex(random_bytes(16)),PASSWORD_DEFAULT);$s=$this->pdo->prepare("INSERT INTO users (username,password_hash,fullname,email,role,provider,created_at,updated_at) VALUES (?,?,?,?,'member',?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");$s->execute([$username,$hash,$displayName,$email,$provider]);$id=(int)$this->pdo->lastInsertId();$u=$this->getUserById($id);}else{$s=$this->pdo->prepare('UPDATE users SET fullname=?,email=?,provider=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$displayName,$email,$provider,(int)$u['id']]);$u=$this->getUserById((int)$u['id']);}
        $this->syncSessionUser($u);return ['success'=>true,'user'=>$u];
    }
    public function logout():void{$_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),' ',time()-42000,$p['path'],$p['domain'],$p['secure'],$p['httponly']);}session_destroy();header('Location: login.php');exit;}
    public function register(string $username,string $password,string $fullname,string $email):array{
        $username=trim($username);$fullname=trim($fullname);$email=trim($email);$e=[];
        if($username==='')$e[]='Usuario e obrigatorio.';if(strlen($password)<6)$e[]='Senha deve ter ao menos 6 caracteres.';if($fullname==='')$e[]='Nome completo e obrigatorio.';
        if($email===''||!filter_var($email,FILTER_VALIDATE_EMAIL))$e[]='Email institucional e obrigatorio para o cadastro local.';elseif(!$this->isAllowedSelfRegistrationEmail($email))$e[]='Use um email institucional do IF ou um dominio gov.br para criar conta local.';
        if($this->findUserByUsername($username))$e[]='Usuario ja existe.';if($e)return ['success'=>false,'errors'=>$e];
        $s=$this->pdo->prepare("INSERT INTO users (username,password_hash,fullname,email,role,provider,created_at,updated_at) VALUES (?,?,?,?,'member','local',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");$s->execute([$username,password_hash($password,PASSWORD_DEFAULT),$fullname,$email]);return ['success'=>true];
    }
    public function getCurrentUser():?array{return !empty($_SESSION['user'])&&is_array($_SESSION['user'])?$_SESSION['user']:null;}
    public function getRoleDefinitions():array{return self::ROLE_DEFINITIONS;}
    public function getRoleKey($r=null):string{return $this->normalizeRole($r);}
    public function getRoleLabel($r=null):string{$k=$this->normalizeRole($r);return self::ROLE_DEFINITIONS[$k]['label'];}
    public function getRoleRank($r=null):int{$k=$this->normalizeRole($r);return (int)self::ROLE_DEFINITIONS[$k]['rank'];}
    public function hasAtLeastRole(string $requiredRole,?array $user=null):bool{return $this->getRoleRank($user)>= $this->getRoleRank($requiredRole);}
    public function isAcademicResearcher(?array $u=null):bool{return $this->normalizeRole($u?:$this->getCurrentUser())===self::ROLE_ACADEMIC_RESEARCHER;}
    public function isAssociateResearcher(?array $u=null):bool{return $this->normalizeRole($u?:$this->getCurrentUser())===self::ROLE_ASSOCIATE_RESEARCHER;}
    public function isFullResearcher(?array $u=null):bool{return $this->normalizeRole($u?:$this->getCurrentUser())===self::ROLE_FULL_RESEARCHER;}
    public function isAdmin(?array $u=null):bool{$u=$u?:$this->getCurrentUser();return $u? $this->normalizeRole($u)===self::ROLE_ADMIN:false;}
    public function canAccessResearchWorkspace(?array $u=null):bool{return $this->hasAtLeastRole(self::ROLE_ACADEMIC_RESEARCHER,$u?:$this->getCurrentUser());}
    public function canManageOrientations(?array $u=null):bool{return $this->hasAtLeastRole(self::ROLE_ASSOCIATE_RESEARCHER,$u?:$this->getCurrentUser());}
    public function canCreateProjects(?array $u=null):bool{return $this->hasAtLeastRole(self::ROLE_FULL_RESEARCHER,$u?:$this->getCurrentUser());}

    public function updateProfile(array $data):array{
        $this->requireAuth();$u=$this->getCurrentUser();$fullname=trim((string)($data['fullname']??$data['full_name']??''));$email=trim((string)($data['email']??''));$password=(string)($data['password']??'');$confirm=(string)($data['password_confirm']??$data['confirm_password']??'');$e=[];
        if($fullname==='')$e[]='Nome completo e obrigatorio.';if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))$e[]='Email invalido.';if($password!==''&&strlen($password)<6)$e[]='A nova senha deve ter ao menos 6 caracteres.';if($password!==''&&$password!==$confirm)$e[]='As senhas nao coincidem.';
        if(strtolower((string)$u['provider'])==='local'&&$email!==''&&!$this->isAllowedSelfRegistrationEmail($email))$e[]='Contas locais so podem usar email institucional do IF ou dominio gov.br.';if($e)return ['success'=>false,'errors'=>$e];
        if($password!==''){$s=$this->pdo->prepare('UPDATE users SET fullname=?,email=?,password_hash=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$fullname,$email,password_hash($password,PASSWORD_DEFAULT),(int)$u['id']]);}else{$s=$this->pdo->prepare('UPDATE users SET fullname=?,email=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$fullname,$email,(int)$u['id']]);}$this->syncSessionUser($this->getUserById((int)$u['id']));return ['success'=>true];
    }
    public function updateUserProfile($arg1,$arg2=null,$arg3=null){if(is_array($arg1))return $this->updateProfile($arg1);$data=is_array($arg2)?$arg2:[];$data['fullname']=$data['fullname']??$data['full_name']??''; $u=$this->getUserById((int)$arg1);if(!$u)return false;$s=$this->pdo->prepare('UPDATE users SET fullname=?,email=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([$data['fullname'],$data['email']??$u['email'],(int)$arg1]);if((int)($this->getCurrentUser()['id']??0)===(int)$arg1)$this->syncSessionUser($this->getUserById((int)$arg1));return true;}
    public function changePassword(string $currentPassword,string $newPassword,string $confirmPassword):array{$this->requireAuth();$u=$this->getUserById((int)$this->getCurrentUser()['id']);$e=[];if($currentPassword===''||!password_verify($currentPassword,(string)$u['password_hash']))$e[]='Senha atual incorreta.';if(strlen($newPassword)<6)$e[]='Nova senha deve ter ao menos 6 caracteres.';if($newPassword!==$confirmPassword)$e[]='As senhas nao coincidem.';if($e)return ['success'=>false,'errors'=>$e];$s=$this->pdo->prepare('UPDATE users SET password_hash=?,updated_at=CURRENT_TIMESTAMP WHERE id=?');$s->execute([password_hash($newPassword,PASSWORD_DEFAULT),(int)$u['id']]);return ['success'=>true];}
    public function listUsers():array{return array_map(fn($u)=>$this->normalizeUser($u),$this->pdo->query('SELECT * FROM users ORDER BY id')->fetchAll());}
    public function getUserById(int $id):?array{$s=$this->pdo->prepare('SELECT * FROM users WHERE id=? LIMIT 1');$s->execute([$id]);$u=$s->fetch();return $u?$this->normalizeUser($u):null;}
    public function adminSaveUser(?int $id,array $data):array{$this->requireAdmin();$isCreate=empty($id);$username=trim((string)($data['username']??''));$fullname=trim((string)($data['fullname']??''));$email=trim((string)($data['email']??''));$password=(string)($data['password']??'');$role=$this->normalizeRole((string)($data['role']??self::ROLE_MEMBER));$e=[];if($username==='')$e[]='Usuario e obrigatorio.';if($fullname==='')$e[]='Nome completo e obrigatorio.';if($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))$e[]='Email invalido.';if($isCreate&&strlen($password)<6)$e[]='Defina uma senha com pelo menos 6 caracteres.';if(!$isCreate&&$password!==''&&strlen($password)<6)$e[]='A nova senha deve ter ao menos 6 caracteres.';$other=$this->findUserByUsername($username,$isCreate?null:$id);if($other)$e[]='Ja existe outro usuario com esse login.';if(!$isCreate&&$role!==self::ROLE_ADMIN&&$this->isAdmin($this->getUserById($id))&&$this->countAdmins($id)===0)$e[]='Nao e possivel remover a permissao do ultimo admin.';if($e)return ['success'=>false,'errors'=>$e];
        if($isCreate){$s=$this->pdo->prepare("INSERT INTO users (username,password_hash,fullname,email,role,provider,created_at,updated_at) VALUES (?,?,?,?,?,'admin',CURRENT_TIMESTAMP,CURRENT_TIMESTAMP)");$s->execute([$username,password_hash($password,PASSWORD_DEFAULT),$fullname,$email,$role]);$id=(int)$this->pdo->lastInsertId();$created=true;}else{$sql='UPDATE users SET username=?,fullname=?,email=?,role=?,updated_at=CURRENT_TIMESTAMP';$args=[$username,$fullname,$email,$role];if($password!==''){$sql.=',password_hash=?';$args[]=password_hash($password,PASSWORD_DEFAULT);}$sql.=' WHERE id=?';$args[]=$id;$s=$this->pdo->prepare($sql);$s->execute($args);$created=false;}$saved=$this->getUserById((int)$id);if((int)($this->getCurrentUser()['id']??0)===(int)$id)$this->syncSessionUser($saved);return ['success'=>true,'user'=>$saved,'created'=>$created];
    }
    public function deleteUser(int $id):array{$this->requireAdmin();if((int)($this->getCurrentUser()['id']??0)===$id)return ['success'=>false,'errors'=>['Voce nao pode remover a propria conta pelo painel admin.']];$u=$this->getUserById($id);if(!$u)return ['success'=>false,'errors'=>['Usuario nao encontrado.']];if($this->isAdmin($u)&&$this->countAdmins($id)===0)return ['success'=>false,'errors'=>['Nao e possivel remover o ultimo admin.']];$s=$this->pdo->prepare('DELETE FROM users WHERE id=?');$s->execute([$id]);return ['success'=>true,'user'=>$u];}

    private function findUserByUsername(string $username,?int $except=null):?array{$s=$this->pdo->prepare('SELECT * FROM users WHERE LOWER(username)=LOWER(?)'.($except!==null?' AND id<>?':'').' LIMIT 1');$args=[$username];if($except!==null)$args[]=$except;$s->execute($args);$u=$s->fetch();return $u?:null;}
    private function countAdmins(?int $except=null):int{$sql="SELECT COUNT(*) FROM users WHERE role='admin'";$args=[];if($except!==null){$sql.=' AND id<>?';$args[]=$except;}$s=$this->pdo->prepare($sql);$s->execute($args);return (int)$s->fetchColumn();}
    private function normalizeUser(array $u):array{return ['id'=>(int)($u['id']??0),'username'=>trim((string)($u['username']??'')),'password_hash'=>(string)($u['password_hash']??''),'fullname'=>trim((string)($u['fullname']??$u['username']??'')),'email'=>trim((string)($u['email']??'')),'role'=>$this->normalizeRole($u),'provider'=>trim((string)($u['provider']??'local'))?:'local','created_at'=>(string)($u['created_at']??''),'updated_at'=>(string)($u['updated_at']??'')];}
    private function syncSessionUser(array $u):void{$u=$this->normalizeUser($u);$_SESSION['user']=['id'=>$u['id'],'username'=>$u['username'],'fullname'=>$u['fullname'],'email'=>$u['email'],'role'=>$u['role'],'role_label'=>$this->getRoleLabel($u),'role_rank'=>$this->getRoleRank($u),'provider'=>$u['provider'],'created_at'=>$u['created_at'],'logged_at'=>time()];$_SESSION['usuario_id']=$u['id'];$_SESSION['usuario_nome']=$u['fullname'];$_SESSION['usuario_email']=$u['email'];$_SESSION['usuario_role']=$u['role'];$_SESSION['user_name']=$u['fullname'];$_SESSION['user_email']=$u['email'];}
    private function normalizeRole($r=null):string{$role='';$id=0;$name='';if(is_array($r)){$role=strtolower(trim((string)($r['role']??'')));$id=(int)($r['id']??0);$name=strtolower(trim((string)($r['username']??'')));}else{$role=strtolower(trim((string)($r??'')));}if($role==='user')$role='member';if(isset(self::ROLE_DEFINITIONS[$role]))return $role;return ($id===1||$name==='admin')?self::ROLE_ADMIN:self::ROLE_MEMBER;}
    private function generateUniqueUsername(string $display,string $email,string $provider):string{$base=$this->slugifyUsername($email!==''?((string)strstr($email,'@',true)):$display)?:$this->slugifyUsername($provider)?:'user';$c=$base;$i=1;while($this->findUserByUsername($c))$c=$base.(++$i);return $c;}
    private function slugifyUsername(string $v):string{return substr((string)preg_replace('/[^a-z0-9]+/','',strtolower(trim($v))),0,24);}
    private function isAllowedSelfRegistrationEmail(string $email):bool{$d=strtolower((string)substr(strrchr(trim($email),'@'),1));return $d!==''&&(preg_match('/^(?:[a-z0-9-]+\.)*gov\.br$/',$d)||preg_match('/^(?:[a-z0-9-]+\.)*if[a-z0-9-]+\.edu\.br$/',$d));}
}
