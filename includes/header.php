<?php
// Iniciar sessão se não estiver iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>CEPIN-CIS</title>
    <link rel="stylesheet" href="style.css" />
    <link href="https://fonts.googleapis.com/css2?family=Aldrich&family=Chivo:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        .mensagem {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
            text-align: center;
        }
        .mensagem.erro {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        .mensagem.sucesso {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }
    </style>
</head>
<body>
    <!-- ===== HEADER ===== -->
    <header>
        <a href="./index.php" class="logo">CEPIN-CIS</a>
        <nav>
            <a href="./about.php#sobre">Sobre</a>
            <a href="./implement.php">Áreas Temáticas</a>
            <a href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Regulamento</a>
            <a href="./contact.php">Contato</a>
            
            <?php if(isset($_SESSION['usuario_nome'])): ?>
                <span style="color: white; margin-right: 15px;"><?php echo $_SESSION['usuario_nome']; ?></span>
                <a href="./dashboard.php">Dashboard</a>
                <a href="./logout.php">Sair</a>
            <?php else: ?>
                <a href="./index.php">
                    <img class="imagensdoheader" src="./img/download (1).png" alt="Home">
                </a>
                <a href="./login.php">
                    <img class="imagensdoheader" src="./img/login.png" alt="Login">
                </a>
            <?php endif; ?>
        </nav>
    </header>