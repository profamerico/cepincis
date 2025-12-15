<?php
class Database {
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            // SQLite - funciona sem MySQL
            $database_file = __DIR__ . '/../database.sqlite';
            $this->conn = new PDO("sqlite:" . $database_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Criar tabela automaticamente
            $this->createTable();
            
        } catch(PDOException $exception) {
            echo "Erro de conexão: " . $exception->getMessage();
        }

        return $this->conn;
    }

    private function createTable() {
        $query = "CREATE TABLE IF NOT EXISTS usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            password TEXT NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP
        )";
        
        $this->conn->exec($query);
    }
}
?>