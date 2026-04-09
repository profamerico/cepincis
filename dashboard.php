<?php
$pageTitle = 'Dashboard | CEPIN-CIS';
$bodyClass = 'app-page dashboard-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';

$auth = new AuthController();
$auth->requireAuth();

$projectManager = new ProjectManager();
$currentUser = $auth->getCurrentUser();
$isAdmin = $auth->isAdmin($currentUser);
$userStats = $projectManager->getUserStats((int) $currentUser['id']);
$projectStats = $isAdmin ? $projectManager->getProjectStats() : null;
$users = $isAdmin ? $auth->listUsers() : [];
$displayName = $currentUser['fullname'] ?? $currentUser['username'];
$roleLabel = $auth->getRoleLabel($currentUser);
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Painel interno</p>
            <h1><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-copy">Um ponto central para acompanhar sua conta, navegar pelos recursos do portal e administrar a plataforma quando voce tiver permissao elevada.</p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="profile.php">Editar perfil</a>
                <?php if ($isAdmin): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php">Abrir admin</a>
                <?php else: ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="settings.php">Ver configuracoes</a>
                <?php endif; ?>
            </div>
        </div>

        <aside class="panel-hero-aside">
            <span class="dashboard-badge"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <h2>Resumo da sessao</h2>
            <p>Conta conectada como <strong>@<?php echo htmlspecialchars((string) $currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            <ul class="hero-meta-list">
                <li>Email: <?php echo htmlspecialchars((string) ($currentUser['email'] ?: 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Projetos ativos: <?php echo (int) $userStats['active']; ?></li>
                <li>Projetos concluidos: <?php echo (int) $userStats['completed']; ?></li>
            </ul>
        </aside>
    </section>

    <section class="metrics-grid">
        <article class="metric-card">
            <span class="metric-label">Número de projetos</span>
            <strong class="metric-value"><?php echo (int) $userStats['total']; ?></strong>
            <p>Quantidade total associada ao seu usuario.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Ativos</span>
            <strong class="metric-value"><?php echo (int) $userStats['active']; ?></strong>
            <p>Itens em andamento no momento.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Pendentes</span>
            <strong class="metric-value"><?php echo (int) $userStats['pending']; ?></strong>
            <p>Demandas aguardando proximo passo.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label"><?php echo $isAdmin ? 'Usuarios' : 'Concluidos'; ?></span>
            <strong class="metric-value"><?php echo $isAdmin ? count($users) : (int) $userStats['completed']; ?></strong>
            <p><?php echo $isAdmin ? 'Contas registradas atualmente no portal.' : 'Projetos que ja foram finalizados.'; ?></p>
        </article>
    </section>

    <section class="dashboard-layout">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Atalhos</p>
                    <h2>O que voce quer fazer agora?</h2>
                </div>
            </div>

            <div class="action-grid">
                <a class="action-card" href="profile.php">
                    <i class="fa-solid fa-id-card"></i>
                    <div>
                        <strong>Atualizar perfil</strong>
                        <span>Revise nome, email e senha da conta.</span>
                    </div>
                </a>

                <a class="action-card" href="settings.php">
                    <i class="fa-solid fa-sliders"></i>
                    <div>
                        <strong>Preferencias</strong>
                        <span>Consulte ajustes de conta e notificacoes.</span>
                    </div>
                </a>

                <?php if ($isAdmin): ?>
                    <a class="action-card" href="admin.php">
                        <i class="fa-solid fa-shield-halved"></i>
                        <div>
                            <strong>Controle administrativo</strong>
                            <span>Usuarios, permissoes e projetos em um so lugar.</span>
                        </div>
                    </a>
                <?php endif; ?>

                <a class="action-card" href="logout.php">
                    <i class="fa-solid fa-right-from-bracket"></i>
                    <div>
                        <strong>Encerrar sessao</strong>
                        <span>Sair da conta com seguranca quando terminar.</span>
                    </div>
                </a>
            </div>
        </article>

        <div class="stacked-panels">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Status rapido</p>
                        <h2>Visao da sua conta</h2>
                    </div>
                </div>

                <ul class="dashboard-list">
                    <li>
                        <span>Perfil principal</span>
                        <strong><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Permissao atual</span>
                        <strong><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Usuario</span>
                        <strong>@<?php echo htmlspecialchars((string) $currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars((string) ($currentUser['email'] ?: 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                </ul>
            </article>

            <?php if ($isAdmin && $projectStats !== null): ?>
                <article class="panel-card accent-panel">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Panorama admin</p>
                            <h2>Estado da plataforma</h2>
                        </div>
                    </div>

                    <ul class="dashboard-list">
                        <li>
                            <span>Projetos totais</span>
                            <strong><?php echo (int) $projectStats['total']; ?></strong>
                        </li>
                        <li>
                            <span>Ativos</span>
                            <strong><?php echo (int) $projectStats['active']; ?></strong>
                        </li>
                        <li>
                            <span>Pendentes</span>
                            <strong><?php echo (int) $projectStats['pending']; ?></strong>
                        </li>
                        <li>
                            <span>Sem responsavel</span>
                            <strong><?php echo (int) $projectStats['without_owner']; ?></strong>
                        </li>
                    </ul>
                </article>
            <?php else: ?>
                <article class="panel-card accent-panel">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Conta</p>
                            <h2>Proximo passo sugerido</h2>
                        </div>
                    </div>

                    <p class="panel-copy">Mantenha seu perfil atualizado para facilitar o contato e a organizacao do portal. Se precisar de mais acessos, um administrador pode ajustar sua permissao no painel interno.</p>
                </article>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
