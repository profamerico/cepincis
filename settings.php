<?php
require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();
?>

<?php include_once 'includes/header.php'; ?>


            <div class="ball"></div>
            
            <div class="settings-container">
                <div class="dashboard-header">
                    <h1>Configurações</h1>
                    <p>Personalize sua experiência no CEPIN-CIS</p>
                </div>
                
                <div class="settings-tabs">
                    <button class="tab-btn active" onclick="openTab('tab-account')">Conta</button>
                    <button class="tab-btn" onclick="openTab('tab-privacy')">Privacidade</button>
                    <button class="tab-btn" onclick="openTab('tab-notifications')">Notificações</button>
                </div>
                
                <div id="tab-account" class="tab-content active">
                    <div class="settings-card">
                        <h3>Informações da Conta</h3>
                        <div class="form-group">
                            <label for="current-password">Senha Atual</label>
                            <input type="password" id="current-password">
                        </div>
                        <div class="form-group">
                            <label for="new-password">Nova Senha</label>
                            <input type="password" id="new-password">
                        </div>
                        <div class="form-group">
                            <label for="confirm-password">Confirmar Nova Senha</label>
                            <input type="password" id="confirm-password">
                        </div>
                        <button class="dashboard-btn" style="border: none; cursor: pointer;">Alterar Senha</button>
                    </div>
                </div>
                
                <div id="tab-privacy" class="tab-content">
                    <div class="settings-card">
                        <h3>Configurações de Privacidade</h3>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span>Perfil Público</span>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span>Mostrar Email</span>
                            <label class="toggle-switch">
                                <input type="checkbox">
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div id="tab-notifications" class="tab-content">
                    <div class="settings-card">
                        <h3>Preferências de Notificação</h3>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span>Notificações por Email</span>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <span>Atualizações de Projetos</span>
                            <label class="toggle-switch">
                                <input type="checkbox" checked>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(tabName) {
    // Esconde todas as abas
    var tabs = document.getElementsByClassName('tab-content');
    for (var i = 0; i < tabs.length; i++) {
        tabs[i].classList.remove('active');
    }
    
    // Remove active de todos os botões
    var buttons = document.getElementsByClassName('tab-btn');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].classList.remove('active');
    }
    
    // Mostra a aba clicada
    document.getElementById(tabName).classList.add('active');
    event.currentTarget.classList.add('active');
}
</script>

<?php include_once 'includes/footer.php'; ?>