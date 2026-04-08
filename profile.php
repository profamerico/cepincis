<?php
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

<div class="js-cont">
    <div class="js-scroll">
        <div class="full-screen">
            <div class="ball"></div>

            <div class="profile-container">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo htmlspecialchars(strtoupper(substr((string) $user['username'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?>
                    </div>
                    <h1><?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?></h1>
                    <p>Membro do CEPIN-CIS desde <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) ($user['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></p>

                    <div class="profile-stats">
                        <div class="stat-card">
                            <span class="stat-number"><?php echo (int) $stats['total']; ?></span>
                            <span class="stat-label">Projetos</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo (int) $stats['active']; ?></span>
                            <span class="stat-label">Ativos</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo (int) $stats['completed']; ?></span>
                            <span class="stat-label">Concluidos</span>
                        </div>
                    </div>
                </div>

                <div class="profile-form">
                    <h3>Dados da conta</h3>

                    <?php if ($result && !$result['success']): ?>
                        <?php foreach ($result['errors'] as $error): ?>
                            <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                    <?php elseif ($result && $result['success']): ?>
                        <div class="mensagem sucesso">Perfil atualizado com sucesso.</div>
                    <?php endif; ?>

                    <form method="POST">
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

                        <button type="submit" class="dashboard-btn" style="border: none; cursor: pointer;">Salvar alteracoes</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
