<?php

require_once __DIR__ . '/config/database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();

    // 1. Criar a tabela
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS partners (
            id VARCHAR(80) PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            description TEXT NULL,
            image_path VARCHAR(500) NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
                ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    echo "Tabela 'partners' criada/verificada com sucesso.<br><br>";

    // 2. Dados dos parceiros
    $partners = [
        [
            'id' => 'partner_copenhagen',
            'name' => 'Universidade de Copenhagen',
            'description' => 'Copenhagen, Dinamarca',
            'image_path' => './img/copenhagen.png',
            'created_at' => '2026-05-06 00:00:00',
            'updated_at' => '2026-05-06 00:00:00'
        ],
        [
            'id' => 'partner_roma3',
            'name' => 'Universidade de Roma 3',
            'description' => 'Roma, Itália',
            'image_path' => './img/Roma 3.png',
            'created_at' => '2026-05-06 00:00:00',
            'updated_at' => '2026-05-06 00:00:00'
        ],
        [
            'id' => 'partner_fuzhou',
            'name' => 'Universidade de Fuzhou',
            'description' => 'Instituição pública de ensino superior localizada em Fuzhou, capital da província de Fujian, na China.',
            'image_path' => './img/Fuhzou.png',
            'created_at' => '2026-05-06 00:00:00',
            'updated_at' => '2026-05-06 00:00:00'
        ],
        [
            'id' => 'partner_getis',
            'name' => 'GETIS',
            'description' => 'Grupo de Pesquisa em Engenharia, Tecnologia, Inovação e Sustentabilidade (GETIS) - IFSP-CAR',
            'image_path' => './img/Getis.png',
            'created_at' => '2026-05-06 00:00:00',
            'updated_at' => '2026-05-06 00:00:00'
        ],
        [
            'id' => 'partner_i2',
            'name' => 'i2',
            'description' => 'Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR',
            'image_path' => './img/i2v2.png',
            'created_at' => '2026-05-06 00:00:00',
            'updated_at' => '2026-05-06 00:00:00'
        ],
        [
            'id' => 'partner_enasa',
            'name' => 'ENASA',
            'description' => 'Grupo de pesquisa em Energia, Água e Saneamento (ENASA) - IFSP-SP',
            'image_path' => './img/enasa.png',
            'created_at' => '2026-05-06 00:00:00',
            'updated_at' => '2026-05-06 00:00:00'
        ]
    ];

    // 3. Inserir/atualizar os parceiros
    $stmt = $pdo->prepare("
        INSERT INTO partners (
            id,
            name,
            description,
            image_path,
            created_at,
            updated_at
        ) VALUES (
            :id,
            :name,
            :description,
            :image_path,
            :created_at,
            :updated_at
        )
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            image_path = VALUES(image_path),
            updated_at = VALUES(updated_at)
    ");

    foreach ($partners as $partner) {
        $stmt->execute($partner);
        echo "OK: " . htmlspecialchars($partner['name']) . "<br>";
    }

    echo "<br><strong>Finalizado. 6 parceiros processados.</strong>";

} catch (Throwable $e) {

    http_response_code(500);

    echo "<strong>ERRO:</strong><br>";
    echo htmlspecialchars($e->getMessage());
}