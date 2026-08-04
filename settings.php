<?php
$pageTitle = 'Configuracoes | CEPIN-CIS';
$bodyClass = 'app-page settings-page';

require_once 'controllers/AuthController.php';

$auth = new AuthController();
$auth->requireAuth();
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Configuracoes</p>
            <h1>Ajustes da sua experiência</h1>
            <p class="hero-copy">Uma área mais limpa para concentrar preferencias de conta, privacidade e notificações sem scripts inline espalhados pela página.</p>
        </div>
    </section>

    <section class="settings-layout">
        <aside class="settings-nav panel-card">
            <button type="button" class="tab-btn active" data-tab-target="account">Conta</button>
            <button type="button" class="tab-btn" data-tab-target="privacy">Privacidade</button>
            <button type="button" class="tab-btn" data-tab-target="notifications">Notificações</button>
        </aside>

        <div class="settings-content">
            <section class="panel-card tab-content active" data-tab-panel="account">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Conta</p>
                        <h2>Seguranca basica</h2>
                    </div>
                </div>

                <div class="form-group">
                    <label for="current-password">Senha atual</label>
                    <input type="password" id="current-password">
                </div>
                <div class="form-group">
                    <label for="new-password">Nova senha</label>
                    <input type="password" id="new-password">
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirmar nova senha</label>
                    <input type="password" id="confirm-password">
                </div>
                <button class="dashboard-btn">Alterar senha</button>
            </section>

            <section class="panel-card tab-content" data-tab-panel="privacy">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Privacidade</p>
                        <h2>Visibilidade do perfil</h2>
                    </div>
                </div>

                <div class="settings-row">
                    <span>Perfil público</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row">
                    <span>Mostrar email</span>
                    <label class="toggle-switch">
                        <input type="checkbox">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>

            <section class="panel-card tab-content" data-tab-panel="notifications">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Notificações</p>
                        <h2>Preferencias de aviso</h2>
                    </div>
                </div>

                <div class="settings-row">
                    <span>Notificações por email</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>

                <div class="settings-row">
                    <span>Atualizações de projetos</span>
                    <label class="toggle-switch">
                        <input type="checkbox" checked>
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </section>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>


