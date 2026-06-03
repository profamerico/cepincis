<?php
$pageTitle = 'Recuperar senha | CEPIN-CIS';
$bodyClass = 'auth-page';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'controllers/AuthController.php';
require_once 'models/PasswordReset.php';

$auth = new AuthController();
$auth->redirectIfLoggedIn();
$resetManager = new PasswordResetManager();

$result = null;
$resetUrl = '';
$identifier = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim((string) ($_POST['identifier'] ?? ''));
    $user = $auth->findUserByUsernameOrEmail($identifier);

    $result = [
        'success' => true,
        'message' => 'Se a conta existir, um link de redefinicao sera gerado para ela.',
    ];

    if ($user) {
        $token = $resetManager->createToken((int) ($user['id'] ?? 0));
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $resetUrl = $scheme . '://' . $host . ($basePath !== '' ? $basePath : '') . '/reset-password.php?token=' . urlencode((string) $token['token']);
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell auth-shell">
    <section class="auth-grid">
        <aside class="auth-aside">
            <p class="eyebrow">Acesso</p>
            <h1>Recupere sua senha</h1>
            <p class="auth-copy">Informe seu usuario ou email cadastrado para gerar um link temporario de redefinicao.</p>
        </aside>

        <section class="auth-card">
            <p class="eyebrow">Senha</p>
            <h2>Esqueci minha senha</h2>

            <?php if ($result): ?>
                <div class="mensagem sucesso"><?php echo htmlspecialchars((string) $result['message'], ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>

            <?php if ($resetUrl !== ''): ?>
                <div class="password-reset-link-box">
                    <span>Link temporario</span>
                    <a href="<?php echo htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            <?php endif; ?>

            <form action="forgot-password.php" method="POST" class="stack-form">
                <div class="form-group">
                    <label for="identifier">Usuario ou email</label>
                    <input
                        type="text"
                        id="identifier"
                        name="identifier"
                        value="<?php echo htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <button type="submit" class="dashboard-btn auth-submit">Gerar link</button>
            </form>

            <p class="auth-link-copy"><a href="./login.php">Voltar ao login</a></p>
        </section>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
