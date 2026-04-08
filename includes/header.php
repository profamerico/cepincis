<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = $pageTitle ?? 'CEPIN-CIS';
$bodyClass = trim((string) ($bodyClass ?? ''));
$currentUser = $_SESSION['user'] ?? null;
$isLoggedIn = is_array($currentUser);
$displayName = $isLoggedIn
    ? trim((string) ($currentUser['fullname'] ?? $currentUser['username'] ?? 'Usuario'))
    : '';
$currentRole = strtolower(trim((string) ($currentUser['role'] ?? '')));
$isAdmin = $isLoggedIn && ($currentRole === 'admin' || (int) ($currentUser['id'] ?? 0) === 1 || strtolower((string) ($currentUser['username'] ?? '')) === 'admin');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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

        .header-user-name {
            color: white;
            margin-right: 12px;
            font-weight: 700;
        }
    </style>
</head>
<body<?php echo $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
    <header>
        <a href="./index.php" class="logo">CEPIN-CIS</a>
        <nav>
            <a href="./about.php#sobre">Sobre</a>
            <a href="./implement.php">Areas Tematicas</a>
            <a href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Regulamento</a>
            <a href="./contact.php">Contato</a>

            <?php if ($isLoggedIn): ?>
                <span class="header-user-name"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>
                <a href="./dashboard.php">Dashboard</a>
                <a href="./profile.php">Perfil</a>
                <?php if ($isAdmin): ?>
                    <a href="./admin.php">Admin</a>
                <?php endif; ?>
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
