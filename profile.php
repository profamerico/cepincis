<?php
$pageTitle = 'Perfil | CEPIN-CIS';
$bodyClass = 'app-page profile-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';

$auth = new AuthController();
$auth->requireAuth();

$projectManager = new ProjectManager();
$user = $auth->getCurrentUser();
$stats = $projectManager->getUserStats((int) $user['id']);
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = $auth->updateProfile([
        'fullname' => $_POST['fullname'] ?? '',
        'email' => $_POST['email'] ?? '',
        'password' => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? '',
    ]);

    if ($result['success']) {
        $user = $auth->getCurrentUser();
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Perfil</p>
            <h1><?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-copy">Atualize seus dados principais e mantenha a conta pronta para contato, acompanhamento e administracao do portal.</p>
        </div>

        <aside class="panel-hero-aside profile-summary-card">
            <span class="profile-initial"><?php echo htmlspecialchars(strtoupper(substr((string) $user['username'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
            <h2>@<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Membro desde <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) ($user['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></p>
        </aside>
    </section>

    <section class="metrics-grid">
        <article class="metric-card">
            <span class="metric-label">Projetos</span>
            <strong class="metric-value"><?php echo (int) $stats['total']; ?></strong>
            <p>Total atualmente ligado ao seu usuario.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Ativos</span>
            <strong class="metric-value"><?php echo (int) $stats['active']; ?></strong>
            <p>Itens em andamento.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Pendentes</span>
            <strong class="metric-value"><?php echo (int) $stats['pending']; ?></strong>
            <p>Demandas aguardando andamento.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Concluidos</span>
            <strong class="metric-value"><?php echo (int) $stats['completed']; ?></strong>
            <p>Projetos finalizados.</p>
        </article>
    </section>

    <section class="dashboard-layout">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Dados da conta</p>
                    <h2>Informacoes pessoais</h2>
                </div>
            </div>

            <?php if ($result && !$result['success']): ?>
                <?php foreach ($result['errors'] as $error): ?>
                    <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            <?php elseif ($result && $result['success']): ?>
                <div class="mensagem sucesso">Perfil atualizado com sucesso.</div>
            <?php endif; ?>

            <form method="POST" class="stack-form">
                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input
                        type="text"
                        id="username"
                        value="<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?>"
                        disabled
                    >
                </div>

                <div class="form-group">
                    <label for="fullname">Nome completo</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        value="<?php echo htmlspecialchars((string) ($user['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="password">Nova senha (opcional)</label>
                    <input type="password" id="password" name="password">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar nova senha</label>
                    <input type="password" id="password_confirm" name="password_confirm">
                </div>

                <button type="submit" class="dashboard-btn">Salvar alteracoes</button>
            </form>
        </article>

        <div class="stacked-panels">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Conta</p>
                        <h2>Resumo rapido</h2>
                    </div>
                </div>

                <ul class="dashboard-list">
                    <li>
                        <span>Nome exibido</span>
                        <strong><?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars((string) ($user['email'] ?: 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Usuario</span>
                        <strong>@<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                </ul>
            </article>

            <article class="panel-card accent-panel">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Atalho</p>
                        <h2>Voltar ao painel</h2>
                    </div>
                </div>

                <p class="panel-copy">Se voce terminou as alteracoes, pode voltar para o dashboard principal e seguir para configuracoes ou administracao.</p>
                <a class="dashboard-btn dashboard-btn--ghost" href="dashboard.php">Ir para o dashboard</a>
            </article>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
