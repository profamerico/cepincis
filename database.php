<?php

class Database {

    private $conn;

    public function getConnection() {

        $this->conn = null;

        try {

            $url = parse_url($_ENV['DATABASE_URL']);

            $host = $url["host"];
            $port = $url["port"];
            $user = $url["user"];
            $pass = $url["pass"];
            $db = ltrim($url["path"], '/');

            $this->conn = new PDO(
                "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
                $user,
                $pass
            );

            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch(PDOException $e) {

            die("Erro de conexão: " . $e->getMessage());

        }

        return $this->conn;
    }
}