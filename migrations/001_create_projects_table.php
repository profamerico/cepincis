<?php

return [

    'up' => function(PDO $pdo) {

        $sql = "

        CREATE TABLE IF NOT EXISTS projects (

            id VARCHAR(64) PRIMARY KEY,

            owner_user_id INT NOT NULL,

            title VARCHAR(255) NOT NULL,

            description TEXT NULL,

            category VARCHAR(100) NULL,

            status VARCHAR(50) DEFAULT 'draft',

            publication_status VARCHAR(50) DEFAULT 'draft',

            authentication_status VARCHAR(50) DEFAULT 'missing',

            image_path VARCHAR(255) NULL,

            participation_info TEXT NULL,

            authenticated_document_id INT NULL,

            authenticated_at DATETIME NULL,

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP

        );

        ";

        $pdo->exec($sql);
    },

    'down' => function(PDO $pdo) {

        $pdo->exec("
            DROP TABLE IF EXISTS projects
        ");
    }

];