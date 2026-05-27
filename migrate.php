<?php

require_once __DIR__ . '/database.php';

$db = new Database();

$pdo = $db->getConnection();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");

$migrationsPath = __DIR__ . '/migrations';

$files = scandir($migrationsPath);

foreach ($files as $file) {

    if (!str_ends_with($file, '.php')) {
        continue;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM migrations 
        WHERE migration = ?
    ");

    $stmt->execute([$file]);

    $alreadyExecuted = $stmt->fetchColumn();

    if ($alreadyExecuted) {
        echo "SKIPPED: {$file}\n";
        continue;
    }

    echo "RUNNING: {$file}\n";

    $migration = require $migrationsPath . '/' . $file;

    $migration['up']($pdo);

    $stmt = $pdo->prepare("
        INSERT INTO migrations (migration)
        VALUES (?)
    ");

    $stmt->execute([$file]);

    echo "DONE: {$file}\n";
}

echo "\nAll migrations completed.\n";
