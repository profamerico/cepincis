<?php
$pageTitle = 'Painel Admin | CEPIN-CIS';
$bodyClass = 'app-page admin-page';

require_once 'controllers/AuthController.php';
require_once 'models/ContentBlock.php';
require_once 'models/Project.php';

$auth = new AuthController();
$auth->requireAdmin();

$projectManager = new ProjectManager();
$contentManager = new ContentBlockManager();
$currentUser = $auth->getCurrentUser();
$roleOptions = $auth->getRoleDefinitions();
$contentPageOptions = $contentManager->getPageDefinitions();
$contentTypeOptions = $contentManager->getTypeDefinitions();
$contentWidthOptions = $contentManager->getWidthDefinitions();
$contentStatusOptions = $contentManager->getStatusDefinitions();

function admin_set_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function admin_redirect(string $anchor = ''): void
{
    $location = 'admin.php';
    if ($anchor !== '') {
        $location .= '#' . $anchor;
    }

    header('Location: ' . $location);
    exit();
}

function admin_status_label(string $status): string
{
    switch ($status) {
        case 'completed':
            return 'Concluido';
        case 'pending':
            return 'Pendente';
        default:
            return 'Ativo';
    }
}

function admin_format_datetime(?string $value): string
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

function admin_format_tags(array $tags): string
{
    if (empty($tags)) {
        return 'Sem tags';
    }

    return implode(', ', $tags);
}

function admin_content_excerpt(string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '') {
        return 'Sem texto complementar.';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($text, 'UTF-8') <= $limit) {
            return $text;
        }

        return rtrim(mb_substr($text, 0, $limit - 1, 'UTF-8')) . '...';
    }

    if (strlen($text) <= $limit) {
        return $text;
    }

    return rtrim(substr($text, 0, $limit - 1)) . '...';
}

function admin_format_block_items(array $items): string
{
    if (empty($items)) {
        return 'Sem itens estruturados.';
    }

    $previewItems = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $label = trim((string) ($item['label'] ?? ''));
        $value = trim((string) ($item['value'] ?? ''));

        if ($label !== '' && $value !== '') {
            $previewItems[] = $label . ': ' . $value;
        } elseif ($value !== '') {
            $previewItems[] = $value;
        }

        if (count($previewItems) === 2) {
            break;
        }
    }

    return $previewItems ? implode(' | ', $previewItems) : 'Sem itens estruturados.';
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['admin_csrf'];
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$userFormErrors = [];
$projectFormErrors = [];
$contentFormErrors = [];
$userFormOverrides = null;
$projectFormOverrides = null;
$contentFormOverrides = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        $flash = ['type' => 'erro', 'message' => 'Sessao expirada. Recarregue a pagina e tente novamente.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');

        switch ($action) {
            case 'save_user':
                $userId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : null;
                $submittedUser = [
                    'id' => $userId ?? '',
                    'username' => trim((string) ($_POST['username'] ?? '')),
                    'fullname' => trim((string) ($_POST['fullname'] ?? '')),
                    'email' => trim((string) ($_POST['email'] ?? '')),
                    'role' => trim((string) ($_POST['role'] ?? 'member')),
                ];

                $result = $auth->adminSaveUser($userId, [
                    'username' => $submittedUser['username'],
                    'fullname' => $submittedUser['fullname'],
                    'email' => $submittedUser['email'],
                    'role' => $submittedUser['role'],
                    'password' => (string) ($_POST['password'] ?? ''),
                ]);

                if ($result['success']) {
                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Usuario criado com sucesso.' : 'Usuario atualizado com sucesso.'
                    );
                    admin_redirect('users');
                }

                $userFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o usuario.'];
                $userFormOverrides = $submittedUser;
                break;

            case 'delete_user':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $result = $auth->deleteUser($userId);

                if ($result['success']) {
                    $orphanedProjects = $projectManager->clearProjectsForUser($userId);
                    $message = 'Usuario removido com sucesso.';
                    if ($orphanedProjects > 0) {
                        $message .= ' ' . $orphanedProjects . ' projeto(s) ficaram sem responsavel.';
                    }

                    admin_set_flash('sucesso', $message);
                    admin_redirect('users');
                }

                $flash = [
                    'type' => 'erro',
                    'message' => $result['errors'][0] ?? 'Nao foi possivel remover o usuario.',
                ];
                break;

            case 'save_project':
                $projectId = trim((string) ($_POST['project_id'] ?? ''));
                $submittedProject = [
                    'id' => $projectId,
                    'user_id' => trim((string) ($_POST['user_id'] ?? '')),
                    'title' => trim((string) ($_POST['title'] ?? '')),
                    'category' => trim((string) ($_POST['category'] ?? '')),
                    'tags' => trim((string) ($_POST['tags'] ?? '')),
                    'status' => trim((string) ($_POST['status'] ?? 'active')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                ];

                $result = $projectManager->adminSaveProject($projectId !== '' ? $projectId : null, [
                    'user_id' => $submittedProject['user_id'],
                    'title' => $submittedProject['title'],
                    'category' => $submittedProject['category'],
                    'tags' => $submittedProject['tags'],
                    'status' => $submittedProject['status'],
                    'description' => $submittedProject['description'],
                ]);

                if ($result['success']) {
                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Projeto criado com sucesso.' : 'Projeto atualizado com sucesso.'
                    );
                    admin_redirect('projects');
                }

                $projectFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o projeto.'];
                $projectFormOverrides = $submittedProject;
                break;

            case 'delete_project':
                $projectId = trim((string) ($_POST['project_id'] ?? ''));

                if ($projectManager->deleteProject($projectId)) {
                    admin_set_flash('sucesso', 'Projeto removido com sucesso.');
                    admin_redirect('projects');
                }

                $flash = ['type' => 'erro', 'message' => 'Nao foi possivel remover o projeto.'];
                break;

            case 'save_content_block':
                $blockId = trim((string) ($_POST['block_id'] ?? ''));
                $submittedBlock = [
                    'id' => $blockId,
                    'page_key' => trim((string) ($_POST['page_key'] ?? 'contact')),
                    'type' => trim((string) ($_POST['type'] ?? '')),
                    'name' => trim((string) ($_POST['name'] ?? '')),
                    'eyebrow' => trim((string) ($_POST['eyebrow'] ?? '')),
                    'title' => trim((string) ($_POST['title'] ?? '')),
                    'body' => trim((string) ($_POST['body'] ?? '')),
                    'items_text' => trim((string) ($_POST['items_text'] ?? '')),
                    'cta_label' => trim((string) ($_POST['cta_label'] ?? '')),
                    'cta_url' => trim((string) ($_POST['cta_url'] ?? '')),
                    'embed_url' => trim((string) ($_POST['embed_url'] ?? '')),
                    'width' => trim((string) ($_POST['width'] ?? 'half')),
                    'position' => trim((string) ($_POST['position'] ?? '')),
                    'status' => trim((string) ($_POST['status'] ?? 'published')),
                    'show_context_note' => isset($_POST['show_context_note']) ? '1' : '0',
                ];

                $result = $contentManager->adminSaveBlock($blockId !== '' ? $blockId : null, $submittedBlock);

                if ($result['success']) {
                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Bloco de conteudo criado com sucesso.' : 'Bloco de conteudo atualizado com sucesso.'
                    );
                    admin_redirect('content');
                }

                $contentFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o bloco de conteudo.'];
                $contentFormOverrides = $submittedBlock;
                break;

            case 'delete_content_block':
                $blockId = trim((string) ($_POST['block_id'] ?? ''));

                if ($contentManager->deleteBlock($blockId)) {
                    admin_set_flash('sucesso', 'Bloco de conteudo removido com sucesso.');
                    admin_redirect('content');
                }

                $flash = ['type' => 'erro', 'message' => 'Nao foi possivel remover o bloco de conteudo.'];
                break;
        }
    }
}

$users = $auth->listUsers();
$contentBlocks = $contentManager->listBlocks(null, false);
$contactContentBlocks = $contentManager->getPageBlocks('contact', false);
$thematicContentBlocks = $contentManager->getPageBlocks('thematic_areas', false);
$projects = $projectManager->getAllProjects();
$projectStats = $projectManager->getProjectStats();
$contentStats = [
    'total' => count($contentBlocks),
    'published' => count(array_filter($contentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
    'contact_total' => count($contactContentBlocks),
    'contact_published' => count(array_filter($contactContentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
    'thematic_total' => count($thematicContentBlocks),
    'thematic_published' => count(array_filter($thematicContentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
];

$userMap = [];
$projectCountByUser = [];
$adminCount = 0;

foreach ($users as $user) {
    $userMap[(int) $user['id']] = $user;
    if ($auth->isAdmin($user)) {
        $adminCount++;
    }
}

foreach ($projects as $project) {
    if (($project['user_id'] ?? null) !== null) {
        $ownerId = (int) $project['user_id'];
        $projectCountByUser[$ownerId] = ($projectCountByUser[$ownerId] ?? 0) + 1;
    }
}

$editingUser = null;
if (isset($_GET['edit_user']) && $_GET['edit_user'] !== '') {
    $editingUser = $auth->getUserById((int) $_GET['edit_user']);
}

$editingProject = null;
if (!empty($_GET['edit_project'])) {
    $candidateProject = $projectManager->getProject((string) $_GET['edit_project']);
    if (is_array($candidateProject)) {
        $editingProject = $candidateProject;
    }
}

$editingContentBlock = null;
if (!empty($_GET['edit_block'])) {
    $candidateBlock = $contentManager->getBlock((string) $_GET['edit_block']);
    if (is_array($candidateBlock)) {
        $editingContentBlock = $candidateBlock;
    }
}

$userForm = [
    'id' => $editingUser['id'] ?? '',
    'username' => $editingUser['username'] ?? '',
    'fullname' => $editingUser['fullname'] ?? '',
    'email' => $editingUser['email'] ?? '',
    'role' => $editingUser['role'] ?? 'member',
];

if ($userFormOverrides !== null) {
    $userForm = array_merge($userForm, $userFormOverrides);
}

$projectForm = [
    'id' => $editingProject['id'] ?? '',
    'user_id' => isset($editingProject['user_id']) && $editingProject['user_id'] !== null ? (string) $editingProject['user_id'] : '',
    'title' => $editingProject['title'] ?? '',
    'category' => $editingProject['category'] ?? 'Geral',
    'tags' => implode(', ', $editingProject['tags'] ?? []),
    'status' => $editingProject['status'] ?? 'active',
    'description' => $editingProject['description'] ?? '',
];

if ($projectFormOverrides !== null) {
    $projectForm = array_merge($projectForm, $projectFormOverrides);
}

$defaultContentPageKey = $editingContentBlock['page_key'] ?? 'contact';
$contentForm = [
    'id' => $editingContentBlock['id'] ?? '',
    'page_key' => $defaultContentPageKey,
    'type' => $editingContentBlock['type'] ?? $contentManager->getDefaultTypeForPage($defaultContentPageKey),
    'name' => $editingContentBlock['name'] ?? '',
    'eyebrow' => $editingContentBlock['eyebrow'] ?? '',
    'title' => $editingContentBlock['title'] ?? '',
    'body' => $editingContentBlock['body'] ?? '',
    'items_text' => isset($editingContentBlock['items']) ? $contentManager->formatItemsForTextarea($editingContentBlock['items']) : '',
    'cta_label' => $editingContentBlock['cta_label'] ?? '',
    'cta_url' => $editingContentBlock['cta_url'] ?? '',
    'embed_url' => $editingContentBlock['embed_url'] ?? '',
    'width' => $editingContentBlock['width'] ?? 'half',
    'position' => isset($editingContentBlock['position']) ? (string) $editingContentBlock['position'] : (string) $contentManager->getNextPosition($defaultContentPageKey),
    'status' => $editingContentBlock['status'] ?? 'published',
    'show_context_note' => !empty($editingContentBlock['show_context_note']),
];

if ($contentFormOverrides !== null) {
    $contentForm = array_merge($contentForm, $contentFormOverrides);
    $contentForm['show_context_note'] = !empty($contentFormOverrides['show_context_note']);
}

if (!$contentManager->isTypeAllowedForPage((string) $contentForm['page_key'], (string) $contentForm['type'])) {
    $contentForm['type'] = $contentManager->getDefaultTypeForPage((string) $contentForm['page_key']);
}

$currentRoleLabel = $auth->getRoleLabel($currentUser);
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell admin-app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Administracao</p>
            <h1>Painel mestre do portal</h1>
            <p class="hero-copy">Controle usuarios, niveis de acesso, projetos e o conteudo global em blocos. Agora o builder ja abastece Contato e Areas Tematicas a partir do mesmo painel mestre.</p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="#users">Usuarios</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#projects">Projetos</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#content">Conteudo global</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="dashboard.php">Voltar ao dashboard</a>
            </div>
        </div>

        <aside class="panel-hero-aside">
            <span class="dashboard-badge">Admin ativo</span>
            <h2>Conta atual</h2>
            <p><?php echo htmlspecialchars((string) ($currentUser['fullname'] ?? $currentUser['username']), ENT_QUOTES, 'UTF-8'); ?></p>
            <ul class="hero-meta-list">
                <li>Usuario: @<?php echo htmlspecialchars((string) $currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Email: <?php echo htmlspecialchars((string) ($currentUser['email'] ?: 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Nivel: <?php echo htmlspecialchars($currentRoleLabel, ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>
        </aside>
    </section>

    <?php if ($flash): ?>
        <div class="mensagem <?php echo htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif; ?>

    <section class="metrics-grid">
        <article class="metric-card">
            <span class="metric-label">Usuarios</span>
            <strong class="metric-value"><?php echo count($users); ?></strong>
            <p>Contas registradas na plataforma.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Admins</span>
            <strong class="metric-value"><?php echo $adminCount; ?></strong>
            <p>Perfis com permissao total.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Projetos</span>
            <strong class="metric-value"><?php echo (int) $projectStats['total']; ?></strong>
            <p>Total de itens salvos no portal.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Sem responsavel</span>
            <strong class="metric-value"><?php echo (int) $projectStats['without_owner']; ?></strong>
            <p>Projetos aguardando reatribuicao.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Blocos globais</span>
            <strong class="metric-value"><?php echo (int) $contentStats['total']; ?></strong>
            <p><?php echo (int) $contentStats['published']; ?> publicados no total entre as paginas administraveis.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Contato</span>
            <strong class="metric-value"><?php echo (int) $contentStats['contact_total']; ?></strong>
            <p><?php echo (int) $contentStats['contact_published']; ?> publicados na pagina de contato.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Areas tematicas</span>
            <strong class="metric-value"><?php echo (int) $contentStats['thematic_total']; ?></strong>
            <p><?php echo (int) $contentStats['thematic_published']; ?> publicados na pagina de Areas Tematicas.</p>
        </article>
    </section>

    <section id="users" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Usuarios</p>
                    <h2><?php echo $userForm['id'] !== '' ? 'Editar usuario' : 'Novo usuario'; ?></h2>
                    <p class="admin-hierarchy-note">Hierarquia atual: Usuario, Pesquisador Academico, Pesquisador Associado, Pesquisador Pleno e Admin.</p>
                </div>

                <?php if ($userForm['id'] !== ''): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php#users">Limpar formulario</a>
                <?php endif; ?>
            </div>

            <?php foreach ($userFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <form method="POST" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string) $userForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars((string) $userForm['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="fullname">Nome completo</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars((string) $userForm['fullname'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars((string) $userForm['email'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="role">Nivel hierarquico</label>
                    <select id="role" name="role">
                        <?php foreach ($roleOptions as $roleKey => $roleMeta): ?>
                            <option value="<?php echo htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $auth->getRoleKey($userForm['role']) === $roleKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $roleMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo $userForm['id'] !== '' ? 'Nova senha (opcional)' : 'Senha'; ?></label>
                    <input type="password" id="password" name="password" <?php echo $userForm['id'] === '' ? 'required' : ''; ?>>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $userForm['id'] !== '' ? 'Salvar usuario' : 'Criar usuario'; ?></button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Base de usuarios</p>
                    <h2>Usuarios cadastrados</h2>
                </div>
            </div>

            <?php if (empty($users)): ?>
                <p class="admin-empty">Nenhum usuario cadastrado ainda.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nivel</th>
                                <th>Email</th>
                                <th>Projetos</th>
                                <th>Criado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $listedUser): ?>
                                <?php
                                $listedUserId = (int) $listedUser['id'];
                                $listedIsAdmin = $auth->isAdmin($listedUser);
                                $listedRoleKey = $auth->getRoleKey($listedUser);
                                $listedRoleLabel = $auth->getRoleLabel($listedUser);
                                $listedRoleClass = 'admin-pill--' . str_replace('_', '-', $listedRoleKey);
                                $isSelf = $listedUserId === (int) $currentUser['id'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) $listedUser['fullname'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta">@<?php echo htmlspecialchars((string) $listedUser['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="admin-pill <?php echo htmlspecialchars($listedRoleClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($listedRoleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($listedUser['email'] ?: '-'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo (int) ($projectCountByUser[$listedUserId] ?? 0); ?></td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($listedUser['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small" href="admin.php?edit_user=<?php echo $listedUserId; ?>#users">Editar</a>

                                            <?php if (!$isSelf): ?>
                                                <form method="POST" onsubmit="return confirm('Remover este usuario?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $listedUserId; ?>">
                                                    <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="admin-self-tag">Conta atual</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section id="projects" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Projetos</p>
                    <h2><?php echo $projectForm['id'] !== '' ? 'Editar projeto' : 'Novo projeto'; ?></h2>
                </div>

                <?php if ($projectForm['id'] !== ''): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php#projects">Limpar formulario</a>
                <?php endif; ?>
            </div>

            <?php foreach ($projectFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <form method="POST" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_project">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars((string) $projectForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="user_id">Responsavel</label>
                    <select id="user_id" name="user_id">
                        <option value="">Sem responsavel</option>
                        <?php foreach ($users as $listedUser): ?>
                            <?php $listedUserId = (int) $listedUser['id']; ?>
                            <option value="<?php echo $listedUserId; ?>" <?php echo (string) $projectForm['user_id'] === (string) $listedUserId ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $listedUser['fullname'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Titulo</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars((string) $projectForm['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Categoria</label>
                    <input type="text" id="category" name="category" value="<?php echo htmlspecialchars((string) $projectForm['category'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group">
                    <label for="tags">Tags</label>
                    <input type="text" id="tags" name="tags" value="<?php echo htmlspecialchars((string) $projectForm['tags'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="IoT, UrbanSmart, CarbonZero">
                    <p class="form-help">Separe as tags por virgula para alimentar filtros e cards da home.</p>
                </div>

                <div class="form-group">
                    <label for="project_status">Status</label>
                    <select id="project_status" name="status">
                        <option value="active" <?php echo $projectForm['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="pending" <?php echo $projectForm['status'] === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="completed" <?php echo $projectForm['status'] === 'completed' ? 'selected' : ''; ?>>Concluido</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Descricao</label>
                    <textarea id="description" name="description" rows="6"><?php echo htmlspecialchars((string) $projectForm['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $projectForm['id'] !== '' ? 'Salvar projeto' : 'Criar projeto'; ?></button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Acervo</p>
                    <h2>Projetos cadastrados</h2>
                </div>
            </div>

            <?php if (empty($projects)): ?>
                <p class="admin-empty">Nenhum projeto cadastrado ainda.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Responsavel</th>
                                <th>Status</th>
                                <th>Categoria</th>
                                <th>Atualizado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <?php
                                $ownerId = $project['user_id'] ?? null;
                                $owner = $ownerId !== null && isset($userMap[(int) $ownerId]) ? $userMap[(int) $ownerId] : null;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) $project['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) $project['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta">Tags: <?php echo htmlspecialchars(admin_format_tags($projectManager->getProjectTagList($project, false)), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($owner['fullname'] ?? 'Sem responsavel'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="admin-pill admin-pill--status">
                                            <?php echo htmlspecialchars(admin_status_label((string) $project['status']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $project['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($project['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small" href="admin.php?edit_project=<?php echo urlencode((string) $project['id']); ?>#projects">Editar</a>

                                            <form method="POST" onsubmit="return confirm('Excluir este projeto?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_project">
                                                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars((string) $project['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section id="content" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Conteudo global</p>
                    <h2><?php echo $contentForm['id'] !== '' ? 'Editar bloco' : 'Novo bloco'; ?></h2>
                    <p class="admin-subtitle">CMS interno por blocos. Hoje ele abastece Contato e Areas Tematicas com pagina, tipo, ordem e visibilidade controlados pelo admin.</p>
                </div>

                <?php if ($contentForm['id'] !== ''): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php#content">Limpar formulario</a>
                <?php endif; ?>
            </div>

            <?php foreach ($contentFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <form method="POST" class="stack-form" data-block-form-root>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_content_block">
                <input type="hidden" name="block_id" value="<?php echo htmlspecialchars((string) $contentForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="content_page_key">Pagina</label>
                    <select id="content_page_key" name="page_key" data-block-page-select>
                        <?php foreach ($contentPageOptions as $pageKey => $pageMeta): ?>
                            <option value="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $contentForm['page_key'] === (string) $pageKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $pageMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content_type">Tipo de bloco</label>
                    <select id="content_type" name="type" data-block-type-select>
                        <?php foreach ($contentTypeOptions as $typeKey => $typeMeta): ?>
                            <?php
                            $allowedPages = [];

                            foreach ($contentPageOptions as $pageKey => $pageMeta) {
                                $pageAllowedTypes = $pageMeta['allowed_types'] ?? array_keys($contentTypeOptions);
                                if (in_array($typeKey, $pageAllowedTypes, true)) {
                                    $allowedPages[] = $pageKey;
                                }
                            }
                            ?>
                            <option
                                value="<?php echo htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8'); ?>"
                                data-allowed-pages="<?php echo htmlspecialchars(implode(',', $allowedPages), ENT_QUOTES, 'UTF-8'); ?>"
                                data-type-description="<?php echo htmlspecialchars((string) ($typeMeta['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                <?php echo (string) $contentForm['type'] === (string) $typeKey ? 'selected' : ''; ?>
                            >
                                <?php echo htmlspecialchars((string) $typeMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help" data-block-type-help data-default-help="Cada pagina libera apenas os tipos de bloco que fazem sentido para o layout dela.">
                        <?php echo htmlspecialchars((string) ($contentTypeOptions[$contentForm['type']]['description'] ?? 'Cada pagina libera apenas os tipos de bloco que fazem sentido para o layout dela.'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <div class="form-group">
                    <label for="content_name">Nome interno</label>
                    <input type="text" id="content_name" name="name" value="<?php echo htmlspecialchars((string) $contentForm['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                    <p class="form-help">Esse nome aparece no painel para identificar o bloco com rapidez.</p>
                </div>

                <div class="form-group">
                    <label for="content_eyebrow">Eyebrow / tag</label>
                    <input type="text" id="content_eyebrow" name="eyebrow" value="<?php echo htmlspecialchars((string) $contentForm['eyebrow'], ENT_QUOTES, 'UTF-8'); ?>">
                    <p class="form-help">Use esse campo como selo curto do bloco, como "Canal oficial", "CEPIN-CIS" ou "EduCIS".</p>
                </div>

                <div class="form-group">
                    <label for="content_title">Titulo</label>
                    <input type="text" id="content_title" name="title" value="<?php echo htmlspecialchars((string) $contentForm['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="content_body">Texto principal</label>
                    <textarea id="content_body" name="body" rows="6"><?php echo htmlspecialchars((string) $contentForm['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card,thematic_cta">
                    <label for="content_items_text">Itens estruturados</label>
                    <textarea id="content_items_text" name="items_text" rows="6"><?php echo htmlspecialchars((string) $contentForm['items_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="form-help">Uma linha por item no formato: Rotulo | valor | link opcional. Ex.: Email | cepin@dominio.com | mailto:cepin@dominio.com</p>
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card,thematic_intro,thematic_cta">
                    <label for="content_cta_label">Rotulo do botao</label>
                    <input type="text" id="content_cta_label" name="cta_label" value="<?php echo htmlspecialchars((string) $contentForm['cta_label'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card,thematic_intro,thematic_cta">
                    <label for="content_cta_url">URL do botao</label>
                    <input type="text" id="content_cta_url" name="cta_url" value="<?php echo htmlspecialchars((string) $contentForm['cta_url'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="mailto:, https:// ou /rota-interna">
                </div>

                <div class="form-group" data-block-field-group="map_embed">
                    <label for="content_embed_url">URL do embed</label>
                    <textarea id="content_embed_url" name="embed_url" rows="4"><?php echo htmlspecialchars((string) $contentForm['embed_url'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="form-help">Cole apenas o valor de `src` do iframe incorporado.</p>
                </div>

                <div class="form-group">
                    <label for="content_width">Largura do bloco</label>
                    <select id="content_width" name="width">
                        <?php foreach ($contentWidthOptions as $widthKey => $widthLabel): ?>
                            <option value="<?php echo htmlspecialchars($widthKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $contentForm['width'] === (string) $widthKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($widthLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content_position">Posicao</label>
                    <input type="number" id="content_position" name="position" value="<?php echo htmlspecialchars((string) $contentForm['position'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="1">
                    <p class="form-help">Quanto menor o numero, mais acima o bloco aparece na pagina.</p>
                </div>

                <div class="form-group">
                    <label for="content_status">Visibilidade</label>
                    <select id="content_status" name="status">
                        <?php foreach ($contentStatusOptions as $statusKey => $statusLabel): ?>
                            <option value="<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $contentForm['status'] === (string) $statusKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card">
                    <label class="checkbox-row" for="content_context_note">
                        <input type="checkbox" id="content_context_note" name="show_context_note" value="1" <?php echo !empty($contentForm['show_context_note']) ? 'checked' : ''; ?>>
                        Exibir contexto de projeto/categoria quando o usuario vier da home
                    </label>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $contentForm['id'] !== '' ? 'Salvar bloco' : 'Criar bloco'; ?></button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Builder</p>
                    <h2>Blocos cadastrados</h2>
                    <p class="admin-subtitle">Os blocos sao renderizados por pagina, ordenados por posicao e podem ser publicados ou ocultados sem apagar o historico.</p>
                </div>
            </div>

            <?php if (empty($contentBlocks)): ?>
                <p class="admin-empty">Nenhum bloco de conteudo cadastrado ainda.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Bloco</th>
                                <th>Pagina</th>
                                <th>Tipo</th>
                                <th>Layout</th>
                                <th>Atualizado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($contentBlocks as $block): ?>
                                <?php
                                $blockId = (string) ($block['id'] ?? '');
                                $blockStatus = (string) ($block['status'] ?? 'published');
                                $blockStatusClass = $blockStatus === 'published' ? 'admin-pill--published' : 'admin-pill--hidden';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($block['name'] ?? 'Bloco'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) ($block['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta"><?php echo htmlspecialchars(admin_content_excerpt((string) ($block['body'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta">Itens: <?php echo htmlspecialchars(admin_format_block_items($block['items'] ?? []), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="admin-pill admin-pill--page">
                                            <?php echo htmlspecialchars($contentManager->getPageLabel((string) ($block['page_key'] ?? 'contact')), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-meta"><?php echo htmlspecialchars($contentManager->getTypeLabel((string) ($block['type'] ?? 'text_card')), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php if ((string) ($block['type'] ?? '') === 'map_embed'): ?>
                                            <div class="admin-meta">Embed configurado</div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="admin-pill <?php echo htmlspecialchars($blockStatusClass, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($contentManager->getStatusLabel($blockStatus), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <div class="admin-meta">Posicao: <?php echo (int) ($block['position'] ?? 0); ?></div>
                                        <div class="admin-meta">Largura: <?php echo htmlspecialchars($contentManager->getWidthLabel((string) ($block['width'] ?? 'half')), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($block['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small" href="admin.php?edit_block=<?php echo urlencode($blockId); ?>#content">Editar</a>

                                            <form method="POST" onsubmit="return confirm('Excluir este bloco de conteudo?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_content_block">
                                                <input type="hidden" name="block_id" value="<?php echo htmlspecialchars($blockId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
