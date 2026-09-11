<?php
require_once __DIR__ . '/database.php';

header('Content-Type: text/plain; charset=UTF-8');

$expectedKey = trim((string) getenv('CEPIN_MIGRATION_KEY'));
$providedKey = trim((string) ($_GET['key'] ?? ''));
$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    if ($expectedKey === '' || $providedKey === '' || !hash_equals($expectedKey, $providedKey)) {
        http_response_code(404);
        exit("Migration indisponível.\n");
    }
}

try {
    $pdo = db();
    $pdo->exec("CREATE TABLE IF NOT EXISTS migrations (id INT AUTO_INCREMENT PRIMARY KEY, migration VARCHAR(255) NOT NULL UNIQUE, executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(120) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        fullname VARCHAR(255) NOT NULL,
        email VARCHAR(255) NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'member',
        provider VARCHAR(50) NOT NULL DEFAULT 'local',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS projects (
        id VARCHAR(64) PRIMARY KEY,
        owner_user_id INT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NULL,
        category VARCHAR(100) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'active',
        publication_status VARCHAR(50) NOT NULL DEFAULT 'published',
        authentication_status VARCHAR(50) NOT NULL DEFAULT 'missing',
        image_path VARCHAR(500) NULL,
        participation_info TEXT NULL,
        tags_json TEXT NULL,
        authenticated_document_id INT NULL,
        authenticated_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_projects_owner (owner_user_id),
        INDEX idx_projects_status (status),
        INDEX idx_projects_authentication (authentication_status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS partners (
        id VARCHAR(80) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        description TEXT NULL,
        image_path VARCHAR(500) NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_documents (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id VARCHAR(64) NOT NULL,
        uploaded_by_user_id INT NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        stored_name VARCHAR(255) NOT NULL,
        storage_path VARCHAR(500) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        extension VARCHAR(20) NOT NULL,
        size_bytes BIGINT NOT NULL,
        sha256_hash VARCHAR(64) NULL,
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        review_notes TEXT NULL,
        reviewed_by_user_id INT NULL,
        reviewed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_project_documents_project (project_id),
        INDEX idx_project_documents_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_collaborators (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id VARCHAR(64) NOT NULL,
        user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'collaborator',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_project_user (project_id,user_id),
        INDEX idx_project_collaborators_project (project_id),
        INDEX idx_project_collaborators_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_collaboration_invites (
        id VARCHAR(80) PRIMARY KEY,
        project_id VARCHAR(64) NOT NULL,
        invited_user_id INT NOT NULL,
        invited_by_user_id INT NOT NULL,
        role VARCHAR(50) NOT NULL DEFAULT 'collaborator',
        status VARCHAR(50) NOT NULL DEFAULT 'pending',
        responded_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_project_invites_project (project_id),
        INDEX idx_project_invites_user (invited_user_id),
        INDEX idx_project_invites_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_authentication_history (
        id INT AUTO_INCREMENT PRIMARY KEY,
        project_id VARCHAR(64) NOT NULL,
        document_id VARCHAR(80) NOT NULL,
        actor_user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_project_auth_history_project (project_id),
        INDEX idx_project_auth_history_document (document_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_timeline_events (
        id VARCHAR(80) PRIMARY KEY,
        project_id VARCHAR(64) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        event_date DATE NOT NULL,
        event_type VARCHAR(50) NOT NULL DEFAULT 'update',
        author_user_id INT NOT NULL,
        attachment_original_name VARCHAR(255) NULL,
        attachment_stored_name VARCHAR(255) NULL,
        attachment_path VARCHAR(500) NULL,
        attachment_mime_type VARCHAR(120) NULL,
        attachment_size_bytes BIGINT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at DATETIME NULL,
        INDEX idx_project_timeline_project (project_id),
        INDEX idx_project_timeline_date (event_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS project_timeline_history (
        id VARCHAR(80) PRIMARY KEY,
        event_id VARCHAR(80) NOT NULL,
        project_id VARCHAR(64) NOT NULL,
        actor_user_id INT NOT NULL,
        action VARCHAR(50) NOT NULL,
        before_payload JSON NULL,
        after_payload JSON NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_project_timeline_history_event (event_id),
        INDEX idx_project_timeline_history_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
        id VARCHAR(80) PRIMARY KEY,
        user_id INT NOT NULL,
        type VARCHAR(80) NOT NULL,
        title VARCHAR(255) NOT NULL,
        body TEXT NULL,
        project_id VARCHAR(64) NULL,
        target_url VARCHAR(500) NULL,
        actor_user_id INT NULL,
        read_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_notifications_user_read (user_id,read_at),
        INDEX idx_notifications_project (project_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $ensureColumns = static function (PDO $pdo, string $table, array $definitions): void {
        $columns = $pdo->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $existing = array_map(static fn(array $row): string => (string) $row['Field'], $columns);
        foreach ($definitions as $name => $definition) {
            if (!in_array($name, $existing, true)) {
                $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$name}` {$definition}");
            }
        }
    };

    $ensureColumns($pdo, 'users', [
        'password_hash' => 'VARCHAR(255) NULL',
        'fullname' => 'VARCHAR(255) NULL',
        'email' => 'VARCHAR(255) NULL',
        'role' => "VARCHAR(50) NOT NULL DEFAULT 'member'",
        'provider' => "VARCHAR(50) NOT NULL DEFAULT 'local'",
        'created_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP',
        'updated_at' => 'TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ]);
    $ensureColumns($pdo, 'projects', [
        'owner_user_id' => 'INT NULL',
        'publication_status' => "VARCHAR(50) NOT NULL DEFAULT 'published'",
        'authentication_status' => "VARCHAR(50) NOT NULL DEFAULT 'missing'",
        'participation_info' => 'TEXT NULL',
        'tags_json' => 'TEXT NULL',
        'authenticated_document_id' => 'INT NULL',
        'authenticated_at' => 'DATETIME NULL',
    ]);

    try { $pdo->exec('ALTER TABLE projects MODIFY owner_user_id INT NULL'); } catch (Throwable $e) {}

    $partners = [
        ['partner_copenhagen','Universidade de Copenhagen','Copenhagen, Dinamarca','./img/copenhagen.png'],
        ['partner_roma3','Universidade de Roma 3','Roma, Itália','./img/Roma 3.png'],
        ['partner_fuzhou','Universidade de Fuzhou','Instituição pública de ensino superior localizada em Fuzhou, capital da província de Fujian, na China.','./img/Fuhzou.png'],
        ['partner_getis','GETIS','Grupo de Pesquisa em Engenharia, Tecnologia, Inovação e Sustentabilidade (GETIS) - IFSP-CAR','./img/Getis.png'],
        ['partner_i2','i2','Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR','./img/i2v2.png'],
        ['partner_enasa','ENASA','Grupo de pesquisa em Energia, Água e Saneamento (ENASA) - IFSP-SP','./img/enasa.png'],
    ];
    $partnerStmt = $pdo->prepare('INSERT INTO partners (id,name,description,image_path,created_at,updated_at) VALUES (?,?,?,?,CURRENT_TIMESTAMP,CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE name=VALUES(name),description=VALUES(description),image_path=VALUES(image_path),updated_at=CURRENT_TIMESTAMP');
    foreach ($partners as $partner) $partnerStmt->execute($partner);

    $migrationNames = ['001_create_projects_table.php','002_create_project_documents.php','003_create_project_collaborators.php','004_create_project_ecosystem_tables.php','005_mysql_hardening_and_partners.php'];
    $record = $pdo->prepare('INSERT IGNORE INTO migrations (migration) VALUES (?)');
    foreach ($migrationNames as $name) $record->execute([$name]);

    echo "MIGRATION_OK\n";
    echo "Tabelas verificadas/criadas: users, projects, partners, project_documents, project_collaborators, project_collaboration_invites, project_authentication_history, project_timeline_events, project_timeline_history, notifications.\n";
    echo "Parceiros padrão verificados.\n";
    echo "Agora remova migrate.php e, se criada apenas para esta operação, CEPIN_MIGRATION_KEY das Variables do Railway.\n";
} catch (Throwable $e) {
    http_response_code(500);
    echo "MIGRATION_ERROR\n";
    echo $e->getMessage() . "\n";
}
