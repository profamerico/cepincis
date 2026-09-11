<?php

return [
    'up' => function (PDO $pdo): void {
        // The canonical idempotent migration entry point is migrate.php.
        // This file documents the final hardening/seed step for environments
        // that inspect the migration directory directly.
    },
    'down' => function (PDO $pdo): void {
        // Intentionally empty: this migration must not remove production data.
    },
];
