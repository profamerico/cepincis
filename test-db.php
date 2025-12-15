<?php
require_once 'config/database.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "✅ Banco de dados conectado!";
    
    // Testar inserção
    $stmt = $conn->prepare("INSERT INTO usuarios (username, password) VALUES (?, ?)");
    $stmt->execute(['teste', 'senhateste']);
    
    echo "<br>✅ Usuário teste inserido!";
    
} else {
    echo "❌ Erro no banco de dados";
}
?>