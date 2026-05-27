<?php

return [

    'up' => function(PDO $pdo) {

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_authentication_history (
                id INT AUTO_INCREMENT PRIMARY KEY,
                project_id VARCHAR(64) NOT NULL,
                document_id VARCHAR(80) NOT NULL,
                actor_user_id INT NOT NULL,
                action VARCHAR(50) NOT NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_project_auth_history_project (project_id),
                INDEX idx_project_auth_history_document (document_id)
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_collaboration_invites (
                id VARCHAR(80) PRIMARY KEY,
                project_id VARCHAR(64) NOT NULL,
                invited_user_id INT NOT NULL,
                invited_by_user_id INT NOT NULL,
                role VARCHAR(50) NOT NULL DEFAULT 'collaborator',
                status VARCHAR(50) NOT NULL DEFAULT 'pending',
                responded_at DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY unique_pending_project_invite (project_id, invited_user_id, status),
                INDEX idx_project_invites_project (project_id),
                INDEX idx_project_invites_user (invited_user_id)
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_timeline_events (
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
                INDEX idx_project_timeline_date (event_date),
                INDEX idx_project_timeline_author (author_user_id)
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS project_timeline_history (
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
            )
        ");

        $pdo->exec("
            CREATE TABLE IF NOT EXISTS notifications (
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
                INDEX idx_notifications_user_read (user_id, read_at),
                INDEX idx_notifications_project (project_id)
            )
        ");
    },

    'down' => function(PDO $pdo) {

        $pdo->exec("DROP TABLE IF EXISTS notifications");
        $pdo->exec("DROP TABLE IF EXISTS project_timeline_history");
        $pdo->exec("DROP TABLE IF EXISTS project_timeline_events");
        $pdo->exec("DROP TABLE IF EXISTS project_collaboration_invites");
        $pdo->exec("DROP TABLE IF EXISTS project_authentication_history");
    }

];
