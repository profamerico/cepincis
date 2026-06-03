<?php
$pageTitle = 'Redefinir senha | CEPIN-CIS';
$bodyClass = 'auth-page';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'controllers/AuthController.php';
require_once 'models/PasswordReset.php';

$auth = new AuthController();
$auth->redirectIfLoggedIn();
$resetManager = new PasswordResetManager();

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$resetRecord = $resetManager->getValidReset($token);
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$resetRecord) {
        $result = [
            'success' => false,
            'errors' => ['Link de redefinicao invalido ou expirado.'],
        ];
    } else {
        $result = $auth->resetPasswordForUserId(
            (int) ($resetRecord['user_id'] ?? 0),
            (string) ($_POST['password'] ?? ''),
            (string) ($_POST['password_confirm'] ?? '')
        );

        if ($result['success']) {
            $resetManager->markUsed($token);
        }
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell auth-shell">
    <section class="auth-grid">
        <aside class="auth-aside">
            <p class="eyebrow">Seguranca</p>
            <h1>Defina uma nova senha</h1>
            <p class="auth-copy">Use uma senha com pelo menos seis caracteres. Depois da troca, o link deixa de funcionar.</p>
        </aside>

        <section class="auth-card">
            <p class="eyebrow">Senha</p>
            <h2>Redefinir senha</h2>

            <?php if ($result && !$result['success']): ?>
                <?php foreach ($result['errors'] as $error): ?>
                    <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            <?php elseif ($result && $result['success']): ?>
                <div class="mensagem sucesso">Senha atualizada com sucesso. Voce ja pode entrar novamente.</div>
                <p class="auth-link-copy"><a href="./login.php">Ir para o login</a></p>
            <?php elseif (!$resetRecord): ?>
                <div class="mensagem erro">Link de redefinicao invalido ou expirado.</div>
                <p class="auth-link-copy"><a href="./forgot-password.php">Gerar novo link</a></p>
            <?php endif; ?>

            <?php if ($resetRecord && (!$result || !$result['success'])): ?>
                <form action="reset-password.php" method="POST" class="stack-form">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token, ENT_QUOTES, 'UTF-8'); ?>">

                    <div class="form-group">
                        <label for="password">Nova senha</label>
                        <input type="password" id="password" name="password" required>
                    </div>

                    <div class="form-group">
                        <label for="password_confirm">Confirmar nova senha</label>
                        <input type="password" id="password_confirm" name="password_confirm" required>
                    </div>

                    <button type="submit" class="dashboard-btn auth-submit">Atualizar senha</button>
                </form>
            <?php endif; ?>
        </section>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
