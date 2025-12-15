<?php
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();

$user = $auth->getCurrentUser();
$message = '';

// Processar atualização do perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'email' => $_POST['email'],
        'bio' => $_POST['bio'],
        'full_name' => $_POST['full_name']
    ];
    
    // delega a atualização de perfil para o AuthController
    if ($auth->updateUserProfile($_SESSION['usuario_id'], $data)) {
        $message = '<div class="mensagem sucesso">Perfil atualizado com sucesso!</div>';
        $user = $auth->getCurrentUser(); // Recarregar dados
    } else {
        $message = '<div class="mensagem erro">Erro ao atualizar perfil.</div>';
    }
}

// Processar mudança de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $message = '<div class="mensagem erro">As senhas não coincidem.</div>';
    } elseif (strlen($new_password) < 6) {
        $message = '<div class="mensagem erro">A senha deve ter pelo menos 6 caracteres.</div>';
    } else {
        if ($auth->changePassword($_SESSION['usuario_id'], $current_password, $new_password)) {
            $message = '<div class="mensagem sucesso">Senha alterada com sucesso!</div>';
        } else {
            $message = '<div class="mensagem erro">Senha atual incorreta.</div>';
        }
    }
}
?>

<?php include_once 'includes/header.php'; ?>


            <div class="ball"></div>
            
            <div class="profile-container">
                <?php echo $message; ?>
                
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
                    </div>
                    <h1><?php echo htmlspecialchars($user['username']); ?></h1>
                    <p>Membro do CEPIN-CIS desde <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
                    
                    <?php
                    require_once 'models/Project.php';
                    $projectManager = new ProjectManager();
                    $stats = $projectManager->getUserStats($_SESSION['usuario_id']);
                    ?>
                    
                    <div class="profile-stats">
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $stats['total']; ?></span>
                            <span class="stat-label">Projetos</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $stats['active']; ?></span>
                            <span class="stat-label">Ativos</span>
                        </div>
                        <div class="stat-card">
                            <span class="stat-number"><?php echo $stats['completed']; ?></span>
                            <span class="stat-label">Concluídos</span>
                        </div>
                    </div>
                </div>
                
                <div class="profile-form">
                    <h3>Informações Pessoais</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="username">Nome de Usuário</label>
                            <input type="text" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Nome Completo</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" placeholder="Seu nome completo">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" placeholder="seu@email.com">
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Biografia</label>
                            <textarea id="bio" name="bio" rows="4" placeholder="Conte um pouco sobre você..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="dashboard-btn" style="border: none; cursor: pointer;">Salvar Alterações</button>
                    </form>
                </div>
                
                <div class="profile-form" style="margin-top: 30px;">
                    <h3>Alterar Senha</h3>
                    
                    <form method="POST">
                        <div class="form-group">
                            <label for="current_password">Senha Atual</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">Nova Senha</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirmar Nova Senha</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="dashboard-btn" style="border: none; cursor: pointer;">Alterar Senha</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>