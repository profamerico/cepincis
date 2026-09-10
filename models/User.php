<?php
require_once __DIR__ . '/../bootstrap.php';
class User {
    private PDO $conn; private string $table_name='users';
    public $id,$username,$password,$data_criacao;
    public function __construct($db=null){$this->conn=$db instanceof PDO?$db:db();}
    public function create(){ $s=$this->conn->prepare("INSERT INTO users (username,password_hash,fullname,email,role,provider) VALUES (?,?,?,?, 'member','local')"); return $s->execute([$this->username,password_hash($this->password,PASSWORD_DEFAULT),$this->username,'']); }
    public function usernameExists(){ $s=$this->conn->prepare('SELECT id,username,password_hash FROM users WHERE username=? LIMIT 1');$s->execute([$this->username]);$r=$s->fetch();if($r){$this->id=$r['id'];$this->username=$r['username'];$this->password=$r['password_hash'];return true;}return false; }
    public function login($password){return $this->usernameExists()&&password_verify($password,$this->password);}
}
