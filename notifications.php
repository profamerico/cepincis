<?php
$pageTitle = 'Notificacoes | CEPIN-CIS';
$bodyClass = 'app-page notifications-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';

function notifications_set_flash(string $type, string $message): void
{
    $_SESSION['notifications_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function notifications_redirect(): void
{
    header('Location: notifications.php');
    exit();
}

function notifications_format_datetime(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y H:i', $timestamp);
}

$auth = new AuthController();
$auth->requireAuth();

$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);
$currentUser = $auth->getCurrentUser();
$currentUserId = (int) ($currentUser['id'] ?? 0);

if (empty($_SESSION['notifications_csrf'])) {
    $_SESSION['notifications_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['notifications_csrf'];
$flash = $_SESSION['notifications_flash'] ?? null;
unset($_SESSION['notifications_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        $flash = ['type' => 'erro', 'message' => 'Sessao expirada. Recarregue a pagina e tente novamente.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');

        if ($action === 'mark_read') {
            $workspaceManager->markNotificationRead((string) ($_POST['notification_id'] ?? ''), $currentUserId);
            notifications_set_flash('sucesso', 'Notificacao marcada como lida.');
            notifications_redirect();
        }

        if ($action === 'mark_all_read') {
            $count = $workspaceManager->markAllNotificationsRead($currentUserId);
            notifications_set_flash('sucesso', $count . ' notificacao(oes) marcada(s) como lida(s).');
            notifications_redirect();
        }

        if ($action === 'respond_invite') {
            $response = (string) ($_POST['response'] ?? '');
            $result = $workspaceManager->respondToInvitation((string) ($_POST['invite_id'] ?? ''), $currentUser, $response);

            if ($result['success']) {
                notifications_set_flash('sucesso', $response === 'accepted' ? 'Convite aceito com sucesso.' : 'Convite recusado.');
                notifications_redirect();
            }

            $flash = ['type' => 'erro', 'message' => $result['errors'][0] ?? 'Nao foi possivel responder ao convite.'];
        }
    }
}

$notifications = $workspaceManager->getUserNotifications($currentUserId, 120);
$unreadCount = $workspaceManager->getUnreadNotificationCount($currentUserId);
$pendingInvites = $workspaceManager->getUserInvites($currentUserId);
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell notifications-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Central</p>
            <h1>Notificacoes</h1>
            <p class="hero-copy">Acompanhe autenticacoes, convites de colaboracao, respostas e novas atualizacoes nas timelines dos projetos.</p>
        </div>
        <aside class="panel-hero-aside">
            <span class="dashboard-badge" data-notification-count><?php echo $unreadCount; ?></span>
            <h2>Itens nao lidos</h2>
            <p>O contador tambem aparece no topo e e atualizado por polling enquanto voce navega autenticado.</p>
        </aside>
    </section>

    <?php if ($flash): ?>
        <div class="mensagem <?php echo htmlspecialchars((string) ($flash['type'] ?? 'sucesso'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="dashboard-layout">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Historico</p>
                    <h2>Atividades recentes</h2>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button class="dashboard-btn dashboard-btn--ghost" type="submit">Marcar todas como lidas</button>
                    </form>
                <?php endif; ?>
            </div>

            <?php if (empty($notifications)): ?>
                <p class="admin-empty">Nenhuma notificacao por enquanto.</p>
            <?php else: ?>
                <div class="notification-stack">
                    <?php foreach ($notifications as $notification): ?>
                        <?php
                        $isUnread = empty($notification['read_at']);
                        $targetUrl = trim((string) ($notification['target_url'] ?? ''));
                        ?>
                        <article class="notification-item<?php echo $isUnread ? ' notification-item--unread' : ''; ?>">
                            <div class="notification-item__content">
                                <span><?php echo htmlspecialchars(notifications_format_datetime($notification['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                                <strong><?php echo htmlspecialchars((string) ($notification['title'] ?? 'Notificacao'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <p><?php echo htmlspecialchars((string) ($notification['body'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>
                            <div class="notification-item__actions">
                                <?php if ($targetUrl !== ''): ?>
                                    <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="<?php echo htmlspecialchars($targetUrl, ENT_QUOTES, 'UTF-8'); ?>">Abrir</a>
                                <?php endif; ?>
                                <?php if ($isUnread): ?>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="notification_id" value="<?php echo htmlspecialchars((string) ($notification['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <button class="dashboard-btn admin-btn-small" type="submit">Lida</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <aside class="stacked-panels">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Convites</p>
                        <h2>Colaboracoes pendentes</h2>
                    </div>
                </div>

                <?php if (empty($pendingInvites)): ?>
                    <p class="admin-empty">Nenhum convite pendente.</p>
                <?php else: ?>
                    <div class="notification-stack">
                        <?php foreach ($pendingInvites as $invite): ?>
                            <?php $project = $projectManager->getProject((string) ($invite['project_id'] ?? '')); ?>
                            <article class="notification-item notification-item--invite">
                                <div class="notification-item__content">
                                    <span><?php echo htmlspecialchars(notifications_format_datetime($invite['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong><?php echo htmlspecialchars(is_array($project) ? (string) ($project['title'] ?? 'Projeto') : 'Projeto', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <p><?php echo ((string) ($invite['role'] ?? '') === 'project_admin') ? 'Convite para administrar o projeto.' : 'Convite para colaborar na timeline do projeto.'; ?></p>
                                </div>
                                <div class="table-actions">
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="respond_invite">
                                        <input type="hidden" name="invite_id" value="<?php echo htmlspecialchars((string) ($invite['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="response" value="accepted">
                                        <button class="dashboard-btn admin-btn-small" type="submit">Aceitar</button>
                                    </form>
                                    <form method="POST">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="respond_invite">
                                        <input type="hidden" name="invite_id" value="<?php echo htmlspecialchars((string) ($invite['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                        <input type="hidden" name="response" value="rejected">
                                        <button class="dashboard-btn admin-btn-danger" type="submit">Recusar</button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <article class="panel-card accent-panel">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Workspace</p>
                        <h2>Projetos colaborativos</h2>
                    </div>
                </div>
                <p class="panel-copy">Use o workspace para publicar eventos na timeline, acompanhar documentos e gerenciar convites quando seu papel permitir.</p>
                <a class="dashboard-btn dashboard-btn--ghost" href="project-workspace.php">Abrir workspaces</a>
            </article>
        </aside>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
