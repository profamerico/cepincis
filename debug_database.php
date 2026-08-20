<?php

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/database.php';

echo "=== AMBIENTE ===\n";

echo "PHP: " . PHP_VERSION . "\n";
echo "SAPI: " . PHP_SAPI . "\n";
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'SIM' : 'NAO') . "\n";

echo "\n=== PDO ===\n";
print_r(PDO::getAvailableDrivers());

echo "\n=== VARIÁVEIS ===\n";

echo "DATABASE_URL existe: ";
var_dump(
    isset($_ENV['DATABASE_URL']) ||
    getenv('DATABASE_URL') !== false
);

echo "MYSQL_HOST: ";
var_dump(getenv('MYSQL_HOST'));

echo "MYSQL_PORT: ";
var_dump(getenv('MYSQL_PORT'));

echo "MYSQL_DATABASE: ";
var_dump(getenv('MYSQL_DATABASE'));

echo "MYSQL_USER: ";
var_dump(getenv('MYSQL_USER'));

echo "\n=== CONEXÃO ===\n";

try {

    $db = new Database();
    $pdo = $db->getConnection();

    echo "CONEXAO OK\n";
    echo "DATABASE(): " . $pdo->query("SELECT DATABASE()")->fetchColumn() . "\n";

} catch (Throwable $e) {

    echo "ERRO\n";
    echo "Classe: " . get_class($e) . "\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
}