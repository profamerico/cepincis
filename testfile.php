<?php

require_once 'config/database.php';

$db = new Database();
$pdo = $db->getConnection();

$tables = [
    'users',
    'projects',
    'project_documents',
    'project_collaborators',
    'project_collaboration_invites',
    'project_authentication_history',
    'project_timeline_events',
    'project_timeline_history',
    'notifications'
];

echo "<style>
body{font-family:Arial,sans-serif;padding:20px;}
table{border-collapse:collapse;margin-bottom:40px;}
th,td{border:1px solid #ccc;padding:6px 10px;font-size:14px;}
th{background:#f0f0f0;}
h2{margin-top:40px;}
.ok{color:green;font-weight:bold;}
.empty{color:red;font-weight:bold;}
.error{color:darkred;font-weight:bold;}
</style>";

foreach ($tables as $table) {

    echo "<h2>Tabela: {$table}</h2>";

    try {

        $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();

        if ($count == 0) {
            echo "<p class='empty'>Registros: 0 (TABELA VAZIA)</p>";
            continue;
        }

        echo "<p class='ok'>Registros: {$count}</p>";

        $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 5");

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$rows) {
            echo "<p>Nenhum registro encontrado.</p>";
            continue;
        }

        echo "<table>";

        echo "<tr>";
        foreach (array_keys($rows[0]) as $column) {
            echo "<th>{$column}</th>";
        }
        echo "</tr>";

        foreach ($rows as $row) {

            echo "<tr>";

            foreach ($row as $value) {

                if (is_null($value)) {
                    $value = "<i>NULL</i>";
                }

                $value = htmlspecialchars((string)$value);

                echo "<td>{$value}</td>";
            }

            echo "</tr>";
        }

        echo "</table>";

    } catch (Exception $e) {

        echo "<p class='error'>Erro: " . htmlspecialchars($e->getMessage()) . "</p>";

    }

}