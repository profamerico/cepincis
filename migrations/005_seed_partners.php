<?php
return function (PDO $pdo): void {
    $partners = [
        ['partner_copenhagen', 'Universidade de Copenhagen', 'Copenhagen, Dinamarca', './img/copenhagen.png'],
        ['partner_roma3', 'Universidade de Roma 3', 'Roma, Itália', './img/Roma 3.png'],
        ['partner_fuzhou', 'Universidade de Fuzhou', 'Instituição pública de ensino superior localizada em Fuzhou, capital da província de Fujian, na China.', './img/Fuhzou.png'],
        ['partner_getis', 'GETIS', 'Grupo de Pesquisa em Engenharia, Tecnologia, Inovação e Sustentabilidade (GETIS) - IFSP-CAR', './img/Getis.png'],
        ['partner_i2', 'i2', 'Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR', './img/i2v2.png'],
        ['partner_enasa', 'ENASA', 'Grupo de pesquisa em Energia, Água e Saneamento (ENASA) - IFSP-SP', './img/enasa.png'],
    ];

    $stmt = $pdo->prepare("
        INSERT INTO partners (id, name, description, image_path)
        VALUES (:id, :name, :description, :image_path)
        ON DUPLICATE KEY UPDATE
            name = VALUES(name),
            description = VALUES(description),
            image_path = VALUES(image_path)
    ");

    foreach ($partners as $partner) {
        $stmt->execute([
            ':id' => $partner[0],
            ':name' => $partner[1],
            ':description' => $partner[2],
            ':image_path' => $partner[3],
        ]);
    }
};
