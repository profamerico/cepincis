<?php
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->redirectIfLoggedIn();

$result = null;

// Auto-preenchimento se veio do registro
$username_preenchido = '';
if (isset($_SESSION['temp_username'])) {
    $username_preenchido = $_SESSION['temp_username'];
    unset($_SESSION['temp_username']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $result = $auth->login($username, $password);
    
    if ($result['success']) {
        header("Location: dashboard.php");
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
                <h1>Login</h1>
                <img src="./img/loginarrow.png" alt="Ícone de login">
                
                <?php if ($result && !$result['success']): ?>
                    <?php foreach ($result['errors'] as $error): ?>
                        <div class="mensagem erro"><?php echo $error; ?></div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <form action="login.php" method="POST">
                    <label for="username">Usuário</label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo $username_preenchido; ?>" 
                           placeholder="Digite seu usuário" required />

                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" placeholder="Digite sua senha" required />

                    <button type="submit">Entrar</button>
                </form>
                <p>Não tem uma conta? <a href="./register.php">Registre-se</a></p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>