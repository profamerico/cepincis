<?php

header('Content-Type: text/plain; charset=utf-8');

require_once __DIR__ . '/config/database.php';

echo "PHP: " . PHP_VERSION . PHP_EOL;
echo "pdo_mysql: " . (extension_loaded('pdo_mysql') ? 'SIM' : 'NAO') . PHP_EOL;
echo "Drivers: ";
print_r(PDO::getAvailableDrivers());

echo PHP_EOL . "Tentando Database..." . PHP_EOL;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "CONEXAO OK!" . PHP_EOL;
    echo "Banco: " . $pdo->query("SELECT DATABASE()")->fetchColumn() . PHP_EOL;

} catch (Throwable $e) {

    echo "ERRO: " . get_class($e) . PHP_EOL;
    echo "Mensagem: " . $e->getMessage() . PHP_EOL;
    echo "Arquivo: " . $e->getFile() . PHP_EOL;
    echo "Linha: " . $e->getLine() . PHP_EOL;
}