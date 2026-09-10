<?php
require_once __DIR__ . '/../bootstrap.php';
class UserManager
{
    private PDO $pdo;
    public function __construct(){ $this->pdo=db(); }
    public function getAllUsers():array{return $this->pdo->query('SELECT id,username,fullname,email,role,provider,created_at,updated_at FROM users ORDER BY id')->fetchAll();}
    public function getUserById(int $id):?array{$s=$this->pdo->prepare('SELECT id,username,fullname,email,role,provider,created_at,updated_at FROM users WHERE id=?');$s->execute([$id]);return $s->fetch()?:null;}
    public function usernameExists(string $username,?int $exceptId=null):bool{$sql='SELECT 1 FROM users WHERE LOWER(username)=LOWER(?)';$a=[$username];if($exceptId!==null){$sql.=' AND id<>?';$a[]=$exceptId;}$s=$this->pdo->prepare($sql.' LIMIT 1');$s->execute($a);return (bool)$s->fetchColumn();}
}
