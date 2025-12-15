<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'controllers/AuthController.php'; // reutiliza a classe AuthController já definida

$auth = new AuthController();

$result = null;
$username = '';
$fullname = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($password !== $password_confirm) {
        $result = ['success' => false, 'errors' => ['As senhas não coincidem.']];
    } else {
        $result = $auth->register($username, $password, $fullname, $email);
    }

    if ($result['success']) {
        $_SESSION['temp_username'] = $username;
        header("Location: login.php");
        exit();
    }
}
?>

<?php include_once 'includes/header.php'; ?>

<div class="js-cont">
    <div class="js-scroll">
        <div class="full-screen">
            <div class="ball"></div>

            <div class="login-container">
                <h1>Registro</h1>

                <?php if ($result && !$result['success']): ?>
                    <?php foreach ($result['errors'] as $error): ?>
                        <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <form action="register.php" method="POST">
                    <label for="fullname">Nome completo</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    />

                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    />

                    <label for="username">Usuário</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    />

                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required />

                    <label for="password_confirm">Confirmar senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" required />

                    <button type="submit">Registrar</button>
                </form>

                <p>Já tem conta? <a href="./login.php">Entrar</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>