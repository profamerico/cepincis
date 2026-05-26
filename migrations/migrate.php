<?php

require_once 'database.php'; // PDO

$pdo = getPDO();

$migrationsPath = __DIR__ . '/migrations';

$files = scandir($migrationsPath);

foreach ($files as $file) {
    if (!str_ends_with($file, '.php')) continue;

    // verifica se já rodou
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM migrations WHERE migration = ?");
    $stmt->execute([$file]);

    if ($stmt->fetchColumn() > 0) {
        continue;
    }

    echo "Running migration: $file\n";

    $migration = require $migrationsPath . '/' . $file;

    $migration['up']($pdo);

    $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
    $stmt->execute([$file]);
}

echo "Migrations completed.\n";