<?php
if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
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
$faviconPath = './img/Captura_de_tela_2026-03-23_165121-removebg-preview.png';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>">
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
    </style>
</head>
<body<?php echo $bodyClass !== '' ? ' class="' . htmlspecialchars($bodyClass, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>
    <header>
        <a href="./index.php" class="logo">CEPIN-CIS</a>
        <nav class="site-nav">
            <div class="site-nav-links">
                <a href="./about.php#sobre">Sobre</a>
                <a href="./implement.php">Areas Tematicas</a>
                <a href="https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf">Regulamento</a>
                <a href="./contact.php">Contato</a>
            </div>

            <div class="site-nav-actions">
                <?php if ($isLoggedIn): ?>
                    <span class="header-user-badge"><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></span>

                    <a href="./dashboard.php" class="header-icon-link" aria-label="Dashboard" title="Dashboard">
                        <i class="fa-solid fa-table-columns"></i>
                    </a>

                    <a href="./profile.php" class="header-icon-link" aria-label="Perfil" title="Perfil">
                        <i class="fa-solid fa-user"></i>
                    </a>

                    <?php if ($isAdmin): ?>
                        <a href="./admin.php" class="header-icon-link" aria-label="Admin" title="Admin">
                            <i class="fa-solid fa-shield-halved"></i>
                        </a>
                    <?php endif; ?>

                    <a href="./logout.php" class="header-icon-link" aria-label="Sair" title="Sair">
                        <i class="fa-solid fa-right-from-bracket"></i>
                    </a>
                <?php else: ?>
                    <a href="./index.php" class="header-icon-link header-icon-link--image" aria-label="Home" title="Home">
                        <img class="imagensdoheader" src="./img/download (1).png" alt="Home">
                    </a>
                    <a href="./login.php" class="header-icon-link header-icon-link--image" aria-label="Login" title="Login">
                        <img class="imagensdoheader" src="./img/login.png" alt="Login">
                    </a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
