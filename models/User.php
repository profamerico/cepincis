<?php
class User {
    private $conn;
    private $table_name = "usuarios";

    public $id;
    public $username;
    public $password;
    public $data_criacao;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                 SET username=:username, password=:password";

        $stmt = $this->conn->prepare($query);

        $this->username = htmlspecialchars(strip_tags($this->username));
        $this->password = htmlspecialchars(strip_tags($this->password));

        $hashed_password = password_hash($this->password, PASSWORD_DEFAULT);

        $stmt->bindParam(":username", $this->username);
        $stmt->bindParam(":password", $hashed_password);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function usernameExists() {
        $query = "SELECT id, username, password 
                  FROM " . $this->table_name . " 
                  WHERE username = ? 
                  LIMIT 0,1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->username);
        $stmt->execute();

        if($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $row['id'];
            $this->username = $row['username'];
            $this->password = $row['password'];
            return true;
        }
        return false;
    }

    public function login($password) {
        if($this->usernameExists()) {
            if(password_verify($password, $this->password)) {
                return true;
            }
        }
        return false;
    }
}
?>