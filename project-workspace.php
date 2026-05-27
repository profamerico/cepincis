<?php
$pageTitle = 'Workspace de Projetos | CEPIN-CIS';
$bodyClass = 'app-page project-workspace-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';

function workspace_set_flash(string $type, string $message): void
{
    $_SESSION['project_workspace_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function workspace_redirect(?string $projectId = null, string $anchor = ''): void
{
    $location = 'project-workspace.php';
    if ($projectId !== null && $projectId !== '') {
        $location .= '?id=' . rawurlencode($projectId);
    }
    if ($anchor !== '') {
        $location .= '#' . $anchor;
    }

    header('Location: ' . $location);
    exit();
}

function workspace_format_datetime(?string $value): string
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

function workspace_format_date(?string $value): string
{
    if (!$value) {
        return '-';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '-';
    }

    return date('d/m/Y', $timestamp);
}

function workspace_render_body(string $text): string
{
    $text = trim($text);
    if ($text === '') {
        return '';
    }

    $paragraphs = preg_split('/\r\n\r\n|\n\n|\r\r/', $text) ?: [$text];
    $html = [];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph !== '') {
            $html[] = '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
        }
    }

    return implode(PHP_EOL, $html);
}

function workspace_user_name(array $userMap, int $userId): string
{
    $user = $userMap[$userId] ?? null;
    if (!is_array($user)) {
        return 'Usuario #' . $userId;
    }

    return trim((string) ($user['fullname'] ?? $user['username'] ?? ('Usuario #' . $userId))) ?: 'Usuario #' . $userId;
}

function workspace_project_role_label(string $role): string
{
    return $role === 'project_admin' ? 'Administrador do projeto' : 'Colaborador';
}

$auth = new AuthController();
$auth->requireAuth();

$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);
$currentUser = $auth->getCurrentUser();
$allUsers = $auth->listUsers();
$allProjects = $projectManager->getAllProjects();
$userMap = [];

foreach ($allUsers as $user) {
    $userMap[(int) ($user['id'] ?? 0)] = $user;
}

if (empty($_SESSION['project_workspace_csrf'])) {
    $_SESSION['project_workspace_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['project_workspace_csrf'];
$flash = $_SESSION['project_workspace_flash'] ?? null;
unset($_SESSION['project_workspace_flash']);

$projectId = trim((string) ($_GET['id'] ?? ''));
$currentProject = $projectId !== '' ? $projectManager->getProject($projectId) : null;
$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        $flash = ['type' => 'erro', 'message' => 'Sessao expirada. Recarregue a pagina e tente novamente.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');
        $postedProjectId = trim((string) ($_POST['project_id'] ?? $projectId));
        $postedProject = $postedProjectId !== '' ? $projectManager->getProject($postedProjectId) : null;

        switch ($action) {
            case 'respond_invite':
                $inviteId = trim((string) ($_POST['invite_id'] ?? ''));
                $response = trim((string) ($_POST['response'] ?? ''));
                $result = $workspaceManager->respondToInvitation($inviteId, $currentUser, $response);

                if ($result['success']) {
                    workspace_set_flash('sucesso', $response === 'accepted' ? 'Convite aceito com sucesso.' : 'Convite recusado.');
                    $invite = $result['invite'] ?? [];
                    workspace_redirect((string) ($invite['project_id'] ?? ''));
                }

                $flash = ['type' => 'erro', 'message' => $result['errors'][0] ?? 'Nao foi possivel responder ao convite.'];
                break;

            case 'upload_document':
                if (!is_array($postedProject) || !$workspaceManager->canManageProject($postedProject, $currentUser)) {
                    $flash = ['type' => 'erro', 'message' => 'Voce nao pode enviar documentacao para este projeto.'];
                    break;
                }

                $result = $workspaceManager->uploadProjectDocument(
                    $postedProject,
                    $currentUser,
                    isset($_FILES['document_file']) && is_array($_FILES['document_file']) ? $_FILES['document_file'] : null
                );

                if ($result['success']) {
                    $workspaceManager->notifyAdministrators(
                        $allUsers,
                        'document_pending',
                        'Documento aguardando aprovacao',
                        'O projeto "' . (string) ($postedProject['title'] ?? 'Projeto') . '" recebeu uma nova documentacao.',
                        $postedProjectId,
                        'admin.php#document-authentication',
                        (int) ($currentUser['id'] ?? 0)
                    );
                    workspace_set_flash('sucesso', 'Documento enviado para avaliacao administrativa.');
                    workspace_redirect($postedProjectId, 'documents');
                }

                $formErrors = [$result['error'] ?? ($result['errors'][0] ?? 'Nao foi possivel enviar o documento.')];
                break;

            case 'invite_collaborator':
                if (!is_array($postedProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                $result = $workspaceManager->inviteCollaborator(
                    $postedProject,
                    $currentUser,
                    (int) ($_POST['invited_user_id'] ?? 0),
                    (string) ($_POST['collaboration_role'] ?? 'collaborator')
                );

                if ($result['success']) {
                    workspace_set_flash('sucesso', 'Convite enviado ao usuario selecionado.');
                    workspace_redirect($postedProjectId, 'collaborators');
                }

                $formErrors = $result['errors'] ?? ['Nao foi possivel enviar o convite.'];
                break;

            case 'remove_collaborator':
                if (!is_array($postedProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                $result = $workspaceManager->removeCollaborator($postedProject, $currentUser, (int) ($_POST['collaborator_user_id'] ?? 0));

                if ($result['success']) {
                    workspace_set_flash('sucesso', 'Colaborador removido do projeto.');
                    workspace_redirect($postedProjectId, 'collaborators');
                }

                $formErrors = $result['errors'] ?? ['Nao foi possivel remover o colaborador.'];
                break;

            case 'add_timeline_event':
                if (!is_array($postedProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                $result = $workspaceManager->addTimelineEvent(
                    $postedProject,
                    $currentUser,
                    [
                        'title' => $_POST['timeline_title'] ?? '',
                        'description' => $_POST['timeline_description'] ?? '',
                        'event_date' => $_POST['timeline_event_date'] ?? '',
                        'event_type' => $_POST['timeline_event_type'] ?? 'update',
                    ],
                    isset($_FILES['timeline_attachment']) && is_array($_FILES['timeline_attachment']) ? $_FILES['timeline_attachment'] : null
                );

                if ($result['success']) {
                    workspace_set_flash('sucesso', 'Evento adicionado a timeline.');
                    workspace_redirect($postedProjectId, 'timeline');
                }

                $formErrors = $result['errors'] ?? ['Nao foi possivel adicionar o evento.'];
                break;

            case 'update_timeline_event':
                if (!is_array($postedProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                $result = $workspaceManager->updateTimelineEvent(
                    $postedProject,
                    trim((string) ($_POST['timeline_event_id'] ?? '')),
                    $currentUser,
                    [
                        'title' => $_POST['timeline_title'] ?? '',
                        'description' => $_POST['timeline_description'] ?? '',
                        'event_date' => $_POST['timeline_event_date'] ?? '',
                        'event_type' => $_POST['timeline_event_type'] ?? 'update',
                    ],
                    isset($_FILES['timeline_attachment']) && is_array($_FILES['timeline_attachment']) ? $_FILES['timeline_attachment'] : null
                );

                if ($result['success']) {
                    workspace_set_flash('sucesso', 'Evento atualizado.');
                    workspace_redirect($postedProjectId, 'timeline');
                }

                $formErrors = $result['errors'] ?? ['Nao foi possivel atualizar o evento.'];
                break;

            case 'delete_timeline_event':
                if (!is_array($postedProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                $result = $workspaceManager->deleteTimelineEvent(
                    $postedProject,
                    trim((string) ($_POST['timeline_event_id'] ?? '')),
                    $currentUser
                );

                if ($result['success']) {
                    workspace_set_flash('sucesso', 'Evento removido da timeline.');
                    workspace_redirect($postedProjectId, 'timeline');
                }

                $formErrors = $result['errors'] ?? ['Nao foi possivel remover o evento.'];
                break;
        }
    }
}

if ($projectId !== '' && !is_array($currentProject)) {
    $pageTitle = 'Projeto nao encontrado | CEPIN-CIS';
}
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell project-workspace-shell">
    <?php if ($flash): ?>
        <div class="mensagem <?php echo htmlspecialchars((string) ($flash['type'] ?? 'sucesso'), ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <?php foreach ($formErrors as $error): ?>
        <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endforeach; ?>

    <?php if ($projectId === ''): ?>
        <?php
        $accessibleProjects = $workspaceManager->getAccessibleProjectsForUser($allProjects, $currentUser);
        $pendingInvites = $workspaceManager->getUserInvites((int) ($currentUser['id'] ?? 0));
        ?>

        <section class="panel-hero">
            <div class="panel-hero-main">
                <p class="eyebrow">Colaboracao</p>
                <h1>Workspace dos projetos</h1>
                <p class="hero-copy">Acompanhe convites, documentacao, colaboradores e timeline dos projetos em que voce participa.</p>
            </div>
            <aside class="panel-hero-aside">
                <span class="dashboard-badge"><?php echo count($pendingInvites); ?> convite(s)</span>
                <h2>Seu acesso</h2>
                <p>Projetos aparecem aqui quando voce e responsavel, colaborador ativo ou administrador da plataforma.</p>
            </aside>
        </section>

        <section class="dashboard-layout">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Projetos acessiveis</p>
                        <h2>Escolha um workspace</h2>
                    </div>
                </div>

                <?php if (empty($accessibleProjects)): ?>
                    <p class="admin-empty">Voce ainda nao possui projetos com workspace liberado.</p>
                <?php else: ?>
                    <div class="project-workspace-list">
                        <?php foreach ($accessibleProjects as $project): ?>
                            <?php $authentication = $workspaceManager->getAuthenticationStatus($project); ?>
                            <a class="project-workspace-card" href="project-workspace.php?id=<?php echo urlencode((string) ($project['id'] ?? '')); ?>">
                                <span><?php echo htmlspecialchars((string) ($project['category'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></span>
                                <strong><?php echo htmlspecialchars((string) ($project['title'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <small><?php echo htmlspecialchars((string) $authentication['label'], ENT_QUOTES, 'UTF-8'); ?></small>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </article>

            <aside class="stacked-panels">
                <article class="panel-card">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Convites</p>
                            <h2>Pendentes</h2>
                        </div>
                    </div>

                    <?php if (empty($pendingInvites)): ?>
                        <p class="admin-empty">Nenhum convite pendente no momento.</p>
                    <?php else: ?>
                        <div class="notification-stack">
                            <?php foreach ($pendingInvites as $invite): ?>
                                <?php $inviteProject = $projectManager->getProject((string) ($invite['project_id'] ?? '')); ?>
                                <article class="notification-item">
                                    <strong><?php echo htmlspecialchars(is_array($inviteProject) ? (string) ($inviteProject['title'] ?? 'Projeto') : 'Projeto', ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?php echo htmlspecialchars(workspace_project_role_label((string) ($invite['role'] ?? 'collaborator')), ENT_QUOTES, 'UTF-8'); ?></span>
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
            </aside>
        </section>
    <?php elseif (!is_array($currentProject)): ?>
        <section class="panel-card project-detail-empty">
            <p class="eyebrow">Workspace</p>
            <h1>Projeto nao encontrado</h1>
            <p>O workspace solicitado nao existe ou foi removido.</p>
            <a class="dashboard-btn" href="project-workspace.php">Voltar aos workspaces</a>
        </section>
    <?php elseif (!$workspaceManager->canViewWorkspace($currentProject, $currentUser)): ?>
        <section class="panel-card project-detail-empty">
            <p class="eyebrow">Acesso restrito</p>
            <h1>Voce nao participa deste projeto</h1>
            <p>Somente responsaveis, colaboradores e administradores podem abrir este workspace.</p>
            <a class="dashboard-btn" href="dashboard.php">Voltar ao dashboard</a>
        </section>
    <?php else: ?>
        <?php
        $authentication = $workspaceManager->getAuthenticationStatus($currentProject);
        $documents = $workspaceManager->getProjectDocuments($projectId);
        $authenticationHistory = $workspaceManager->getAuthenticationHistory($projectId);
        $collaborators = $workspaceManager->getProjectCollaborators($projectId);
        $pendingProjectInvites = $workspaceManager->getProjectInvites($projectId);
        $timelineEvents = $workspaceManager->getProjectTimeline($projectId);
        $canManageProject = $workspaceManager->canManageProject($currentProject, $currentUser);
        $canEditTimeline = $workspaceManager->canEditTimeline($currentProject, $currentUser);
        $ownerId = (int) ($currentProject['user_id'] ?? 0);
        $blockedInviteUserIds = [$ownerId => true, (int) ($currentUser['id'] ?? 0) => true];
        foreach ($collaborators as $collaborator) {
            $blockedInviteUserIds[(int) ($collaborator['user_id'] ?? 0)] = true;
        }
        foreach ($pendingProjectInvites as $invite) {
            $blockedInviteUserIds[(int) ($invite['invited_user_id'] ?? 0)] = true;
        }
        ?>

        <section class="panel-hero">
            <div class="panel-hero-main">
                <p class="eyebrow">Workspace do projeto</p>
                <h1><?php echo htmlspecialchars((string) ($currentProject['title'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="hero-copy"><?php echo htmlspecialchars((string) ($currentProject['description'] ?? 'Mantenha a documentacao, colaboradores e timeline atualizados.'), ENT_QUOTES, 'UTF-8'); ?></p>
                <div class="hero-actions">
                    <a class="dashboard-btn" href="#timeline">Atualizar timeline</a>
                    <a class="dashboard-btn dashboard-btn--ghost" href="project.php?id=<?php echo urlencode($projectId); ?>">Ver pagina publica</a>
                </div>
            </div>
            <aside class="panel-hero-aside">
                <span class="dashboard-badge dashboard-badge--<?php echo htmlspecialchars((string) $authentication['status'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $authentication['label'], ENT_QUOTES, 'UTF-8'); ?>
                </span>
                <h2>Status documental</h2>
                <p>Um projeto so e considerado autenticado quando algum documento PDF ou DOCX for aprovado por administrador.</p>
            </aside>
        </section>

        <section class="metrics-grid">
            <article class="metric-card">
                <span class="metric-label">Documentos</span>
                <strong class="metric-value"><?php echo count($documents); ?></strong>
                <p>Arquivos enviados para validacao.</p>
            </article>
            <article class="metric-card">
                <span class="metric-label">Colaboradores</span>
                <strong class="metric-value"><?php echo count($collaborators); ?></strong>
                <p>Pessoas ativas no projeto.</p>
            </article>
            <article class="metric-card">
                <span class="metric-label">Timeline</span>
                <strong class="metric-value"><?php echo count($timelineEvents); ?></strong>
                <p>Eventos publicados na evolucao.</p>
            </article>
            <article class="metric-card">
                <span class="metric-label">Convites</span>
                <strong class="metric-value"><?php echo count($pendingProjectInvites); ?></strong>
                <p>Solicitacoes aguardando resposta.</p>
            </article>
        </section>

        <section class="dashboard-layout" id="documents">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Autenticador</p>
                        <h2>Documentacao do projeto</h2>
                        <p class="admin-subtitle">Envie PDF ou DOCX de ate 10 MB. O arquivo fica pendente ate avaliacao administrativa.</p>
                    </div>
                </div>

                <?php if ($canManageProject): ?>
                    <form method="POST" enctype="multipart/form-data" class="stack-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="upload_document">
                        <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-group">
                            <label for="document_file">Arquivo de documentacao</label>
                            <input type="file" id="document_file" name="document_file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" required>
                            <p class="form-help">O envio valida extensao, tamanho, MIME e assinatura basica do arquivo.</p>
                        </div>
                        <button type="submit" class="dashboard-btn">Enviar documento</button>
                    </form>
                <?php else: ?>
                    <p class="admin-empty">Somente o responsavel, admin do projeto ou administrador global pode enviar documentacao.</p>
                <?php endif; ?>
            </article>

            <aside class="stacked-panels">
                <article class="panel-card">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Historico</p>
                            <h2>Arquivos enviados</h2>
                        </div>
                    </div>

                    <?php if (empty($documents)): ?>
                        <p class="admin-empty">Nenhum documento enviado ainda.</p>
                    <?php else: ?>
                        <div class="document-list">
                            <?php foreach ($documents as $document): ?>
                                <article class="document-row">
                                    <div>
                                        <strong><?php echo htmlspecialchars((string) ($document['original_name'] ?? 'Documento'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <span><?php echo htmlspecialchars(workspace_format_datetime($document['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?> por <?php echo htmlspecialchars(workspace_user_name($userMap, (int) ($document['uploaded_by_user_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="document-row__actions">
                                        <span class="admin-pill admin-pill--<?php echo htmlspecialchars((string) ($document['status'] ?? 'pending'), ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($workspaceManager->getAuthenticationLabel((string) ($document['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project-file.php?kind=document&id=<?php echo urlencode((string) ($document['id'] ?? '')); ?>">Baixar</a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </article>

                <article class="panel-card">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Auditoria</p>
                            <h2>Historico de autenticacao</h2>
                        </div>
                    </div>

                    <?php if (empty($authenticationHistory)): ?>
                        <p class="admin-empty">Sem movimentacoes de autenticacao ate agora.</p>
                    <?php else: ?>
                        <ul class="workspace-audit-list">
                            <?php foreach ($authenticationHistory as $entry): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars((string) ($entry['action'] ?? 'evento'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?php echo htmlspecialchars(workspace_format_datetime($entry['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                                    <?php if (trim((string) ($entry['notes'] ?? '')) !== ''): ?>
                                        <small><?php echo htmlspecialchars((string) $entry['notes'], ENT_QUOTES, 'UTF-8'); ?></small>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            </aside>
        </section>

        <section class="dashboard-layout" id="collaborators">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Equipe</p>
                        <h2>Colaboradores do projeto</h2>
                    </div>
                </div>

                <div class="collaborator-list">
                    <article class="collaborator-row">
                        <div>
                            <strong><?php echo htmlspecialchars(workspace_user_name($userMap, $ownerId), ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span>Criador / responsavel</span>
                        </div>
                    </article>
                    <?php foreach ($collaborators as $collaborator): ?>
                        <?php $collaboratorUserId = (int) ($collaborator['user_id'] ?? 0); ?>
                        <article class="collaborator-row">
                            <div>
                                <strong><?php echo htmlspecialchars(workspace_user_name($userMap, $collaboratorUserId), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span><?php echo htmlspecialchars(workspace_project_role_label((string) ($collaborator['role'] ?? 'collaborator')), ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <?php if ($canManageProject): ?>
                                <form method="POST" onsubmit="return confirm('Remover este colaborador?');">
                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="action" value="remove_collaborator">
                                    <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
                                    <input type="hidden" name="collaborator_user_id" value="<?php echo $collaboratorUserId; ?>">
                                    <button type="submit" class="dashboard-btn admin-btn-danger">Remover</button>
                                </form>
                            <?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>

            <aside class="stacked-panels">
                <article class="panel-card">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Convite</p>
                            <h2>Adicionar colaborador</h2>
                        </div>
                    </div>

                    <?php if ($canManageProject): ?>
                        <form method="POST" class="stack-form">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="action" value="invite_collaborator">
                            <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="form-group">
                                <label for="invited_user_id">Usuario</label>
                                <select id="invited_user_id" name="invited_user_id" required>
                                    <option value="">Selecione</option>
                                    <?php foreach ($allUsers as $listedUser): ?>
                                        <?php $listedUserId = (int) ($listedUser['id'] ?? 0); ?>
                                        <?php if (isset($blockedInviteUserIds[$listedUserId])) {
                                            continue;
                                        } ?>
                                        <option value="<?php echo $listedUserId; ?>"><?php echo htmlspecialchars((string) ($listedUser['fullname'] ?? $listedUser['username']), ENT_QUOTES, 'UTF-8'); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="collaboration_role">Papel</label>
                                <select id="collaboration_role" name="collaboration_role">
                                    <option value="collaborator">Colaborador</option>
                                    <option value="project_admin">Administrador do projeto</option>
                                </select>
                            </div>
                            <button class="dashboard-btn" type="submit">Enviar convite</button>
                        </form>
                    <?php else: ?>
                        <p class="admin-empty">Somente gestores do projeto podem convidar colaboradores.</p>
                    <?php endif; ?>
                </article>

                <article class="panel-card">
                    <div class="panel-card-header">
                        <div>
                            <p class="eyebrow">Pendentes</p>
                            <h2>Convites enviados</h2>
                        </div>
                    </div>
                    <?php if (empty($pendingProjectInvites)): ?>
                        <p class="admin-empty">Nenhum convite pendente.</p>
                    <?php else: ?>
                        <ul class="workspace-audit-list">
                            <?php foreach ($pendingProjectInvites as $invite): ?>
                                <li>
                                    <strong><?php echo htmlspecialchars(workspace_user_name($userMap, (int) ($invite['invited_user_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span><?php echo htmlspecialchars(workspace_project_role_label((string) ($invite['role'] ?? 'collaborator')), ENT_QUOTES, 'UTF-8'); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            </aside>
        </section>

        <section class="dashboard-layout" id="timeline">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Timeline</p>
                        <h2>Adicionar evento</h2>
                    </div>
                </div>

                <?php if ($canEditTimeline): ?>
                    <form method="POST" enctype="multipart/form-data" class="stack-form">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="action" value="add_timeline_event">
                        <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
                        <div class="form-grid-2">
                            <div class="form-group">
                                <label for="timeline_title">Titulo</label>
                                <input type="text" id="timeline_title" name="timeline_title" required>
                            </div>
                            <div class="form-group">
                                <label for="timeline_event_date">Data</label>
                                <input type="date" id="timeline_event_date" name="timeline_event_date" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="timeline_event_type">Tipo</label>
                            <select id="timeline_event_type" name="timeline_event_type">
                                <?php foreach ($workspaceManager->getTimelineTypeOptions() as $typeKey => $typeLabel): ?>
                                    <option value="<?php echo htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="timeline_description">Descricao</label>
                            <textarea id="timeline_description" name="timeline_description" rows="5" required></textarea>
                        </div>
                        <div class="form-group">
                            <label for="timeline_attachment">Anexo opcional</label>
                            <input type="file" id="timeline_attachment" name="timeline_attachment" accept=".pdf,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp">
                            <p class="form-help">Anexos aceitos: PDF, DOCX, JPG, PNG ou WEBP ate 8 MB.</p>
                        </div>
                        <button class="dashboard-btn" type="submit">Publicar evento</button>
                    </form>
                <?php else: ?>
                    <p class="admin-empty">Seu papel atual permite acompanhar, mas nao editar a timeline.</p>
                <?php endif; ?>
            </article>

            <aside class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Linha do tempo</p>
                        <h2>Evolucao publica</h2>
                    </div>
                </div>

                <?php if (empty($timelineEvents)): ?>
                    <p class="admin-empty">Nenhum evento publicado ainda.</p>
                <?php else: ?>
                    <div class="project-timeline">
                        <?php foreach ($timelineEvents as $event): ?>
                            <?php $canEditEvent = $workspaceManager->canEditTimelineEvent($currentProject, $event, $currentUser); ?>
                            <article class="project-timeline-item project-timeline-item--<?php echo htmlspecialchars((string) ($event['event_type'] ?? 'update'), ENT_QUOTES, 'UTF-8'); ?>">
                                <span class="project-timeline-date"><?php echo htmlspecialchars(workspace_format_date($event['event_date'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                                <div class="project-timeline-card">
                                    <div class="project-timeline-card__header">
                                        <span><?php echo htmlspecialchars($workspaceManager->getTimelineTypeLabel((string) ($event['event_type'] ?? 'update')), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <small><?php echo htmlspecialchars(workspace_user_name($userMap, (int) ($event['author_user_id'] ?? 0)), ENT_QUOTES, 'UTF-8'); ?></small>
                                    </div>
                                    <h3><?php echo htmlspecialchars((string) ($event['title'] ?? 'Evento'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <?php echo workspace_render_body((string) ($event['description'] ?? '')); ?>
                                    <?php if (!empty($event['attachment_path'])): ?>
                                        <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project-file.php?kind=timeline&id=<?php echo urlencode((string) ($event['id'] ?? '')); ?>">Baixar anexo</a>
                                    <?php endif; ?>

                                    <?php if ($canEditEvent): ?>
                                        <details class="timeline-edit-details">
                                            <summary>Editar evento</summary>
                                            <form method="POST" enctype="multipart/form-data" class="stack-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="update_timeline_event">
                                                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="timeline_event_id" value="<?php echo htmlspecialchars((string) ($event['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                <div class="form-group">
                                                    <label>Titulo</label>
                                                    <input type="text" name="timeline_title" value="<?php echo htmlspecialchars((string) ($event['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" required>
                                                </div>
                                                <div class="form-grid-2">
                                                    <div class="form-group">
                                                        <label>Data</label>
                                                        <input type="date" name="timeline_event_date" value="<?php echo htmlspecialchars((string) ($event['event_date'] ?? date('Y-m-d')), ENT_QUOTES, 'UTF-8'); ?>" required>
                                                    </div>
                                                    <div class="form-group">
                                                        <label>Tipo</label>
                                                        <select name="timeline_event_type">
                                                            <?php foreach ($workspaceManager->getTimelineTypeOptions() as $typeKey => $typeLabel): ?>
                                                                <option value="<?php echo htmlspecialchars((string) $typeKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) ($event['event_type'] ?? '') === (string) $typeKey ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars((string) $typeLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label>Descricao</label>
                                                    <textarea name="timeline_description" rows="4" required><?php echo htmlspecialchars((string) ($event['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                                                </div>
                                                <div class="form-group">
                                                    <label>Substituir anexo</label>
                                                    <input type="file" name="timeline_attachment" accept=".pdf,.docx,.jpg,.jpeg,.png,.webp,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document,image/jpeg,image/png,image/webp">
                                                </div>
                                                <div class="table-actions">
                                                    <button class="dashboard-btn admin-btn-small" type="submit">Salvar</button>
                                                </div>
                                            </form>
                                            <form method="POST" onsubmit="return confirm('Remover este evento da timeline?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_timeline_event">
                                                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="timeline_event_id" value="<?php echo htmlspecialchars((string) ($event['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                <button class="dashboard-btn admin-btn-danger" type="submit">Remover evento</button>
                                            </form>
                                        </details>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </aside>
        </section>
    <?php endif; ?>
</main>

<?php include_once 'includes/footer.php'; ?>
