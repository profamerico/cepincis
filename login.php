<?php
header('Content-Type: text/html; charset=UTF-8');
$pageTitle = 'Login | CEPIN-CIS';
$bodyClass = 'auth-page';

$callback_base = "https://cepincis.com.br/callback.php";

$google_auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id' => '_ID_GOOGLE',
    'redirect_uri' => $callback_base . '?provider=google',
    'response_type' => 'code',
    'scope' => 'email profile'
]);

$github_auth_url = "https://github.com/login/oauth/authorize?" . http_build_query([
    'client_id' => 'Ov23limHfW3vDDuSJJvk',
    'redirect_uri' => $callback_base . '?provider=github',
    'scope' => 'user:email'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->redirectIfLoggedIn();

$result = null;
$username_preenchido = '';

if (isset($_SESSION['temp_username'])) {
    $username_preenchido = $_SESSION['temp_username'];
    unset($_SESSION['temp_username']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = $auth->login($username, $password);

    if ($result['success']) {
        header("Location: dashboard.php");
        exit();
    }

    $username_preenchido = $username;
}
?>

<?php
 include_once 'includes/header.php';
;
?>

<main class="page-shell auth-shell">
    <section class="auth-grid">
        <aside class="auth-aside">
            <p class="eyebrow">Portal interno</p>
            <h1>Acesse sua área de trabalho</h1>
            <p class="auth-copy">Entre para acompanhar projetos, editar seu perfil e, se você for administrador, controlar usuários e publicações da plataforma.</p>

            <div class="auth-highlights">
                <div class="auth-highlight">
                    <strong>Gestão centralizada</strong>
                    <span>Conta, perfil e administração no mesmo fluxo.</span>
                </div>
                <div class="auth-highlight">
                    <strong>Experiência mais limpa</strong>
                    <span>Sem o scroll especial da home atrapalhando formulários.</span>
                </div>
                <div class="auth-highlight">
                    <strong>Acesso social</strong>
                    <span>Google e GitHub seguem funcionando no mesmo painel interno.</span>
                </div>
            </div>
        </aside>

        <section class="auth-card">
            <p class="eyebrow">Login</p>
            <h2>Entrar no CEPIN-CIS</h2>

            <?php if ($result && !$result['success']): ?>
                <?php foreach ($result['errors'] as $error): ?>
                    <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form action="login.php" method="POST" class="stack-form">
                <div class="form-group">
                    <label for="username">Usuário</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($username_preenchido, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
                        placeholder="Seu usuário"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" placeholder="Sua senha" required>
                </div>

                <button type="submit" class="dashboard-btn auth-submit">Entrar</button>
            </form>

            <div class="auth-divider"><span>ou continue com</span></div>

            <div class="social-icons auth-socials">
                <a href="<?php echo htmlspecialchars($google_auth_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" title="Google">
                    <i class="fa-brands fa-google-plus-g"></i>
                </a>
                <a href="<?php echo htmlspecialchars($github_auth_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" title="GitHub">
                    <i class="fa-brands fa-github"></i>
                </a>
            </div>

            <p class="auth-link-copy"><a href="./forgot-password.php">Esqueci minha senha</a></p>
            <p class="auth-link-copy">Não tem uma conta? <a href="./register.php">Criar cadastro</a></p>
        </section>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
