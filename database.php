<?php

class Database
{
    private static ?PDO $instance = null;

    public static function getSharedConnection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        $url = trim((string) getenv('DATABASE_URL'));
        $host = trim((string) getenv('MYSQL_HOST'));
        $port = trim((string) getenv('MYSQL_PORT'));
        $database = trim((string) getenv('MYSQL_DATABASE'));
        $user = trim((string) getenv('MYSQL_USER'));
        $password = (string) getenv('MYSQL_PASSWORD');

        if ($url !== '') {
            $parts = parse_url($url);
            if ($parts !== false && in_array(strtolower((string) ($parts['scheme'] ?? '')), ['mysql', 'mysql2', 'mariadb'], true)) {
                $host = (string) ($parts['host'] ?? $host);
                $port = (string) ($parts['port'] ?? ($port ?: '3306'));
                $database = ltrim((string) ($parts['path'] ?? $database), '/');
                $user = isset($parts['user']) ? urldecode((string) $parts['user']) : $user;
                $password = isset($parts['pass']) ? urldecode((string) $parts['pass']) : $password;
            }
        }

        if ($host === '' || $database === '' || $user === '') {
            throw new RuntimeException('Conexão MySQL não configurada. Verifique DATABASE_URL ou as variáveis MYSQL_*.');
        }

        self::$instance = new PDO(
            'mysql:host=' . $host . ';port=' . ((int) ($port ?: 3306)) . ';dbname=' . $database . ';charset=utf8mb4',
            $user,
            $password,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );

        return self::$instance;
    }

    public function getConnection(): PDO
    {
        return self::getSharedConnection();
    }
}

function db(): PDO
{
    return Database::getSharedConnection();
}
