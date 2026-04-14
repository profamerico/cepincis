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
$currentScript = basename((string) ($_SERVER['SCRIPT_NAME'] ?? ''));
$mobileAccountLinks = $isLoggedIn
    ? [
        ['href' => './index.php', 'label' => 'Home', 'icon' => 'fa-house'],
        ['href' => './dashboard.php', 'label' => 'Dashboard', 'icon' => 'fa-table-columns'],
        ['href' => './profile.php', 'label' => 'Perfil', 'icon' => 'fa-user'],
        ['href' => './logout.php', 'label' => 'Sair', 'icon' => 'fa-right-from-bracket'],
    ]
    : [
        ['href' => './index.php', 'label' => 'Home', 'icon' => 'fa-house'],
        ['href' => './login.php', 'label' => 'Entrar', 'icon' => 'fa-right-to-bracket'],
    ];

if ($isAdmin) {
    array_splice($mobileAccountLinks, 3, 0, [[
        'href' => './admin.php',
        'label' => 'Admin',
        'icon' => 'fa-shield-halved',
    ]]);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="icon" type="image/png" href="<?php echo htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="apple-touch-icon" href="<?php echo htmlspecialchars($faviconPath, ENT_QUOTES, 'UTF-8'); ?>">
    <script>document.documentElement.classList.add('js-enabled');</script>
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
    <div class="page-loader" data-page-loader>
        <div class="page-loader__inner" role="status" aria-live="polite" aria-label="Carregando pagina">
            <strong class="page-loader__brand">CEPIN-CIS</strong>
        </div>
    </div>

    <script>
        (function () {
            function releaseLoader() {
                if (document.body) {
                    document.body.classList.add('page-loader-ready');
                }
            }

            window.setTimeout(releaseLoader, 5000);
        }());
    </script>

    <header class="site-header">
        <a href="./index.php" class="logo">CEPIN-CIS</a>
        <button
            type="button"
            class="mobile-nav-toggle"
            data-mobile-nav-toggle
            aria-expanded="false"
            aria-controls="mobileNavDrawer"
            aria-label="Abrir menu"
        >
            <span></span>
            <span></span>
            <span></span>
        </button>

        <nav class="site-nav">
            <div class="site-nav-links">
                <a href="./about.php#sobre">Sobre</a>
                <a href="./implement.php">Areas Temáticas</a>
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

    <div class="mobile-nav-overlay" data-mobile-nav-overlay hidden></div>

    <aside class="mobile-nav-drawer" id="mobileNavDrawer" data-mobile-nav-drawer aria-hidden="true">
        <div class="mobile-nav-drawer-inner">
            <div class="mobile-nav-drawer-top">
                <div>
                    <p class="mobile-nav-kicker">Menu</p>
                    <strong class="mobile-nav-title">Acesso rapido</strong>
                </div>

                <button type="button" class="mobile-nav-close" data-mobile-nav-close aria-label="Fechar menu">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <?php if ($isLoggedIn): ?>
                <div class="mobile-user-badge">
                    <span>Conectado como</span>
                    <strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
            <?php endif; ?>

            <nav class="mobile-account-nav" aria-label="Acesso da conta">
                <?php foreach ($mobileAccountLinks as $mobileLink): ?>
                    <?php
                    $mobileLinkHref = (string) $mobileLink['href'];
                    $mobileLinkLabel = (string) $mobileLink['label'];
                    $mobileLinkIcon = (string) $mobileLink['icon'];
                    $mobileLinkScript = basename(parse_url($mobileLinkHref, PHP_URL_PATH) ?: '');
                    $isMobileLinkActive = $mobileLinkScript !== '' && $mobileLinkScript === $currentScript;
                    ?>
                    <a
                        href="<?php echo htmlspecialchars($mobileLinkHref, ENT_QUOTES, 'UTF-8'); ?>"
                        class="mobile-account-link<?php echo $isMobileLinkActive ? ' is-active' : ''; ?>"
                        <?php echo $isMobileLinkActive ? ' aria-current="page"' : ''; ?>
                    >
                        <i class="fa-solid <?php echo htmlspecialchars($mobileLinkIcon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                        <span><?php echo htmlspecialchars($mobileLinkLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                <?php endforeach; ?>
            </nav>

            <p class="mobile-nav-note">A navegacao institucional fica na barra inferior para voce trocar de area sem perder espaco no topo.</p>
        </div>
    </aside>
