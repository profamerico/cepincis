<?php

return [

    'up' => function(PDO $pdo) {

        $sql = "

        CREATE TABLE IF NOT EXISTS project_documents (

            id INT AUTO_INCREMENT PRIMARY KEY,

            project_id VARCHAR(64) NOT NULL,

            uploaded_by_user_id INT NOT NULL,

            original_name VARCHAR(255) NOT NULL,

            stored_name VARCHAR(255) NOT NULL,

            storage_path VARCHAR(500) NOT NULL,

            mime_type VARCHAR(100) NOT NULL,

            extension VARCHAR(20) NOT NULL,

            size_bytes BIGINT NOT NULL,

            sha256_hash VARCHAR(64) NULL,

            status VARCHAR(50) DEFAULT 'pending',

            review_notes TEXT NULL,

            reviewed_by_user_id INT NULL,

            reviewed_at DATETIME NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

            INDEX idx_project_documents_project (project_id),

            INDEX idx_project_documents_status (status)

        );

        ";

        $pdo->exec($sql);
    },

    'down' => function(PDO $pdo) {

        $pdo->exec("
            DROP TABLE IF EXISTS project_documents
        ");
    }


];