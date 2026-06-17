<?php

require_once __DIR__ . '/config/database.php';

$database = new Database();
$pdo = $database->getConnection();

$migrations = [
    'migrations/001_create_projects_table.php',
    'migrations/002_create_project_documents.php',
    'migrations/003_create_project_collaborators.php',
    'migrations/004_create_project_ecosystem_tables.php',
];

foreach ($migrations as $file) {

    echo "Executando: {$file}<br>";

    $migration = require $file;

    $migration['up']($pdo);

    echo "OK<br>";
}

echo "Finalizado.";