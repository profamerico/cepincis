<?php

// A URL base para onde as redes sociais vão devolver o usuário
$callback_base = "https://cepincis.com.br/callback.php";

// Montando o link do Google
$google_auth_url = "https://accounts.google.com/o/oauth2/v2/auth?" . http_build_query([
    'client_id'     => '_ID_GOOGLE', // 
    'redirect_uri'  => $callback_base . '?provider=google',
    'response_type' => 'code',
    'scope'         => 'email profile'
]);

// Montando o link do Facebook
$facebook_auth_url = "https://www.facebook.com/v12.0/dialog/oauth?" . http_build_query([
    'client_id'    => '_ID_FACEBOOK', // 
    'redirect_uri' => $callback_base . '?provider=facebook',
    'scope'        => 'email'
]);

// Montando o link do GitHub
$github_auth_url = "https://github.com/login/oauth/authorize?" . http_build_query([
    'client_id'    => 'Ov23limHfW3vDDuSJJvk', // 
    'redirect_uri' => $callback_base . '?provider=github',
    'scope'        => 'user:email'
]);

// Montando o link do LinkedIn
$linkedin_auth_url = "https://www.linkedin.com/oauth/v2/authorization?" . http_build_query([
    'response_type' => 'code',
    'client_id'     => '_ID_LINKEDIN', // 
    'redirect_uri'  => $callback_base . '?provider=linkedin',
    'scope'         => 'r_liteprofile r_emailaddress'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->redirectIfLoggedIn();

$result = null;
$username_preenchido = '';

// pega username vindo do registro (se houver)
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
    } else {
        $username_preenchido = $username;
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<div class="js-cont">
    <div class="js-scroll">
        <div class="full-screen">
            <div class="ball"></div>

            <div class="login-container">
                <h1>Login</h1>
                <img src="./img/loginarrow.png" alt="Ícone de login">

                <?php if ($result && !$result['success']): ?>
                    <?php foreach ($result['errors'] as $error): ?>
                        <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <form action="login.php" method="POST">
                    <label for="username">Usuário</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($username_preenchido, ENT_QUOTES, 'UTF-8'); ?>"
                        placeholder="Usuário..."
                        required />

                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" placeholder="Senha..." required />

                    <button type="submit">Entrar</button>
                </form>

                <p>Não tem uma conta? <a href="./register.php">Registre-se</a></p>

                <div class="social-login">
                    <p>Ou conecte-se com:</p>
                    <div class="social-icons">
                        <a href="<?php echo htmlspecialchars($google_auth_url); ?>" title="Google">
                            <i class="fa-brands fa-google-plus-g"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars($github_auth_url); ?>" title="GitHub">
                            <i class="fa-brands fa-github"></i>
                        </a>
                        <a href="<?php echo htmlspecialchars($linkedin_auth_url); ?>" title="LinkedIn">
                            <i class="fa-brands fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>