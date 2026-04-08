<?php
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
?>

<?php include_once 'includes/header.php'; ?>

<div class="js-cont">
    <div class="js-scroll">
        <div class="full-screen">
            <div class="ball"></div>

            <div class="dashboard-container">
                <div class="dashboard-header">
                    <h1>Bem-vindo, <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>Seu painel central para acompanhar conta, projetos e administracao do portal.</p>
                </div>

                <div class="profile-stats">
                    <div class="stat-card">
                        <span class="stat-number"><?php echo (int) $userStats['total']; ?></span>
                        <span class="stat-label">Projetos vinculados</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo (int) $userStats['active']; ?></span>
                        <span class="stat-label">Projetos ativos</span>
                    </div>
                    <div class="stat-card">
                        <span class="stat-number"><?php echo (int) $userStats['completed']; ?></span>
                        <span class="stat-label">Projetos concluidos</span>
                    </div>
                    <?php if ($isAdmin): ?>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo count($users); ?></span>
                            <span class="stat-label">Usuarios na plataforma</span>
                        </div>
                    <?php else: ?>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo (int) $userStats['pending']; ?></span>
                            <span class="stat-label">Projetos pendentes</span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($isAdmin && $projectStats !== null): ?>
                    <div class="profile-form" style="margin-bottom: 30px;">
                        <h3>Resumo administrativo</h3>
                        <div class="profile-stats">
                            <div class="stat-card">
                                <span class="stat-number"><?php echo (int) $projectStats['total']; ?></span>
                                <span class="stat-label">Projetos totais</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo (int) $projectStats['active']; ?></span>
                                <span class="stat-label">Ativos</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo (int) $projectStats['pending']; ?></span>
                                <span class="stat-label">Pendentes</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-number"><?php echo (int) $projectStats['without_owner']; ?></span>
                                <span class="stat-label">Sem responsavel</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="dashboard-menu">
                    <div class="dashboard-card">
                        <i class="fa-solid fa-id-card"></i>
                        <h3>Perfil</h3>
                        <p>Atualize nome, email e senha da conta logada.</p>
                        <a class="dashboard-btn" href="profile.php">Abrir perfil</a>
                    </div>

                    <div class="dashboard-card">
                        <i class="fa-solid fa-gear"></i>
                        <h3>Configuracoes</h3>
                        <p>Consulte a area de preferencias e recursos da sua conta.</p>
                        <a class="dashboard-btn" href="settings.php">Abrir configuracoes</a>
                    </div>

                    <?php if ($isAdmin): ?>
                        <div class="dashboard-card">
                            <i class="fa-solid fa-shield-halved"></i>
                            <h3>Painel Admin</h3>
                            <p>Gerencie usuarios, permissoes e todos os projetos cadastrados.</p>
                            <a class="dashboard-btn" href="admin.php">Abrir painel admin</a>
                        </div>
                    <?php endif; ?>

                    <div class="dashboard-card">
                        <i class="fa-solid fa-right-from-bracket"></i>
                        <h3>Sair</h3>
                        <p>Encerre a sessao atual quando terminar o gerenciamento.</p>
                        <a class="dashboard-btn" href="logout.php">Encerrar sessao</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
