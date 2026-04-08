<?php
$pageTitle = 'Registro | CEPIN-CIS';
$bodyClass = 'auth-page';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'controllers/AuthController.php';

$auth = new AuthController();

$result = null;
$username = '';
$fullname = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $fullname = trim($_POST['fullname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($password !== $password_confirm) {
        $result = ['success' => false, 'errors' => ['As senhas nao coincidem.']];
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

<main class="page-shell auth-shell">
    <section class="auth-grid">
        <aside class="auth-aside">
            <p class="eyebrow">Novo acesso</p>
            <h1>Crie uma conta para colaborar</h1>
            <p class="auth-copy">O cadastro libera o painel interno para acompanhar atividades, manter seus dados atualizados e acessar as ferramentas do portal.</p>

            <div class="auth-highlights">
                <div class="auth-highlight">
                    <strong>Onboarding simples</strong>
                    <span>Cadastro rapido com nome, email e credenciais locais.</span>
                </div>
                <div class="auth-highlight">
                    <strong>Area autenticada renovada</strong>
                    <span>Depois do login voce cai em um dashboard mais organizado.</span>
                </div>
                <div class="auth-highlight">
                    <strong>Permissoes controladas</strong>
                    <span>Administradores podem promover acessos no painel admin.</span>
                </div>
            </div>
        </aside>

        <section class="auth-card">
            <p class="eyebrow">Registro</p>
            <h2>Criar conta</h2>

            <?php if ($result && !$result['success']): ?>
                <?php foreach ($result['errors'] as $error): ?>
                    <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            <?php endif; ?>

            <form action="register.php" method="POST" class="stack-form">
                <div class="form-group">
                    <label for="fullname">Nome completo</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        value="<?php echo htmlspecialchars($fullname, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($email, ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input
                        type="text"
                        id="username"
                        name="username"
                        value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" required>
                </div>

                <button type="submit" class="dashboard-btn auth-submit">Registrar</button>
            </form>

            <p class="auth-link-copy">Ja tem conta? <a href="./login.php">Entrar</a></p>
        </section>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
