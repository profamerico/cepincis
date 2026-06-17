<?php

require_once 'database.php';

$db = new Database();
$pdo = $db->getConnection();

$stmt = $pdo->query("SHOW TABLES");

echo "<pre>";

while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
    echo $row[0] . "\n";
}

echo "</pre>";



