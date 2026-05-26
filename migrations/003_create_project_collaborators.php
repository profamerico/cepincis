<?php

return [

    'up' => function(PDO $pdo) {

        $sql = "

        CREATE TABLE IF NOT EXISTS project_collaborators (

            id INT AUTO_INCREMENT PRIMARY KEY,

            project_id VARCHAR(64) NOT NULL,

            user_id INT NOT NULL,

            role VARCHAR(50) NOT NULL DEFAULT 'collaborator',

            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ON UPDATE CURRENT_TIMESTAMP,

            UNIQUE KEY unique_project_user (project_id, user_id),

            INDEX idx_project_collaborators_project (project_id),

            INDEX idx_project_collaborators_user (user_id)

        );

        ";

        $pdo->exec($sql);
    },

    'down' => function(PDO $pdo) {

        $pdo->exec("
            DROP TABLE IF EXISTS project_collaborators
        ");
    }

];