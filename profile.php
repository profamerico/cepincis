<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'login.php';
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();
$user = $auth->getCurrentUser();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'fullname'         => $_POST['fullname'] ?? '',
        'email'            => $_POST['email'] ?? '',
        'password'         => $_POST['password'] ?? '',
        'password_confirm' => $_POST['password_confirm'] ?? ''
    ];
    $result = $auth->updateProfile($data);
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

            <div class="login-container">
                <h1>Perfil</h1>

                <?php if ($result && !$result['success']): ?>
                    <?php foreach ($result['errors'] as $error): ?>
                        <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
                    <?php endforeach; ?>
                <?php elseif ($result && $result['success']): ?>
                    <div class="mensagem sucesso">Perfil atualizado com sucesso.</div>
                <?php endif; ?>

                <form action="profile.php" method="POST">
                    <label>Usuário (não editável)</label>
                    <input
                        type="text"
                        value="<?php echo htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8'); ?>"
                        disabled
                    />

                    <label for="fullname">Nome completo</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        value="<?php echo htmlspecialchars($user['fullname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    />

                    <label for="email">E-mail</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars($user['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                    />

                    <label for="password">Nova senha (opcional)</label>
                    <input type="password" id="password" name="password" />

                    <label for="password_confirm">Confirmar nova senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" />

                    <button type="submit">Salvar alterações</button>
                </form>

                <div class="login-links">
                    <a class="btn-secund