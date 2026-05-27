<?php

class Database {

    private $conn;

    public function getConnection() {

        $this->conn = null;
        $this->loadEnvironment();

        try {

            $databaseUrl = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL') ?: '';

            if ($databaseUrl !== '') {
                $url = parse_url($databaseUrl);

                $host = $url["host"] ?? 'localhost';
                $port = $url["port"] ?? 3306;
                $user = $url["user"] ?? '';
                $pass = $url["pass"] ?? '';
                $db = ltrim((string) ($url["path"] ?? ''), '/');
            } else {
                $host = $_ENV['MYSQL_HOST'] ?? $_ENV['DB_HOST'] ?? getenv('MYSQL_HOST') ?: getenv('DB_HOST') ?: 'localhost';
                $port = $_ENV['MYSQL_PORT'] ?? $_ENV['DB_PORT'] ?? getenv('MYSQL_PORT') ?: getenv('DB_PORT') ?: 3306;
                $user = $_ENV['MYSQL_USER'] ?? $_ENV['DB_USER'] ?? getenv('MYSQL_USER') ?: getenv('DB_USER') ?: 'root';
                $pass = $_ENV['MYSQL_PASSWORD'] ?? $_ENV['DB_PASS'] ?? getenv('MYSQL_PASSWORD') ?: getenv('DB_PASS') ?: '';
                $db = $_ENV['MYSQL_DATABASE'] ?? $_ENV['DB_NAME'] ?? getenv('MYSQL_DATABASE') ?: getenv('DB_NAME') ?: '';
            }

            if ($db === '') {
                throw new PDOException('Nome do banco de dados nao configurado.');
            }

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

    private function loadEnvironment(): void
    {
        $envPath = __DIR__ . '/.env';

        if (!is_file($envPath)) {
            return;
        }

        $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim((string) $line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = array_map('trim', explode('=', $line, 2));
            $value = trim($value, "\"'");

            if ($key !== '' && !isset($_ENV[$key])) {
                $_ENV[$key] = $value;
                putenv($key . '=' . $value);
            }
        }
    }
}
