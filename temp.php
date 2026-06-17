<?php

require_once 'config/database.php';

$stmt = $pdo->query("SHOW TABLES");

echo "<h2>Tabelas:</h2>";

while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "<br>";
}