<?php

require_once __DIR__ . '/database.php';

$db = new Database();

$pdo = $db->getConnection();

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