<?php

require_once 'database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();

    echo "<h2>Banco de Dados</h2>";

    $stmt = $conn->query("
        SELECT
            DEFAULT_CHARACTER_SET_NAME,
            DEFAULT_COLLATION_NAME
        FROM information_schema.SCHEMATA
        WHERE SCHEMA_NAME = DATABASE();
    ");

    echo "<pre>";
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
    echo "</pre>";

    echo "<hr><h2>Conexão Atual</h2>";

    $stmt = $conn->query("
        SELECT
            @@character_set_client AS client,
            @@character_set_connection AS connection,
            @@character_set_database AS database_charset,
            @@character_set_results AS results,
            @@character_set_server AS server_charset,
            @@collation_connection AS collation_connection,
            @@collation_server AS collation_server;
    ");

    echo "<pre>";
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
    echo "</pre>";

    echo "<hr><h2>Tabelas</h2>";

    $stmt = $conn->query("
        SELECT
            TABLE_NAME,
            TABLE_COLLATION
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        ORDER BY TABLE_NAME;
    ");

    echo "<table border='1' cellpadding='6' cellspacing='0'>";
    echo "<tr><th>Tabela</th><th>Collation</th></tr>";

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['TABLE_NAME']}</td>";
        echo "<td>{$row['TABLE_COLLATION']}</td>";
        echo "</tr>";
    }

    echo "</table>";

} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}