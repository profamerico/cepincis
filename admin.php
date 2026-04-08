<?php
$pageTitle = 'Painel Admin | CEPIN-CIS';
$bodyClass = 'app-page admin-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';

$auth = new AuthController();
$auth->requireAdmin();

$projectManager = new ProjectManager();
$currentUser = $auth->getCurrentUser();

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

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['admin_csrf'];
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$userFormErrors = [];
$projectFormErrors = [];
$userFormOverrides = null;
$projectFormOverrides = null;

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
                    'status' => trim((string) ($_POST['status'] ?? 'active')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                ];

                $result = $projectManager->adminSaveProject($projectId !== '' ? $projectId : null, [
                    'user_id' => $submittedProject['user_id'],
                    'title' => $submittedProject['title'],
                    'category' => $submittedProject['category'],
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
        }
    }
}

$users = $auth->listUsers();
$projects = $projectManager->getAllProjects();
$projectStats = $projectManager->getProjectStats();

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
    'status' => $editingProject['status'] ?? 'active',
    'description' => $editingProject['description'] ?? '',
];

if ($projectFormOverrides !== null) {
    $projectForm = array_merge($projectForm, $projectFormOverrides);
}
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell admin-app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Administracao</p>
            <h1>Painel mestre do portal</h1>
            <p class="hero-copy">Controle usuarios, niveis de acesso e todos os projetos publicados em uma area mais clara, com formularios e tabelas separados por contexto.</p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="#users">Usuarios</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#projects">Projetos</a>
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
                <li>Nivel: administrador</li>
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
    </section>

    <section id="users" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Usuarios</p>
                    <h2><?php echo $userForm['id'] !== '' ? 'Editar usuario' : 'Novo usuario'; ?></h2>
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
                    <label for="role">Permissao</label>
                    <select id="role" name="role">
                        <option value="member" <?php echo $userForm['role'] === 'member' ? 'selected' : ''; ?>>Membro</option>
                        <option value="admin" <?php echo $userForm['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
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
                                <th>Permissao</th>
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
                                $isSelf = $listedUserId === (int) $currentUser['id'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) $listedUser['fullname'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta">@<?php echo htmlspecialchars((string) $listedUser['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="admin-pill <?php echo $listedIsAdmin ? 'admin-pill--admin' : 'admin-pill--member'; ?>">
                                            <?php echo $listedIsAdmin ? 'Admin' : 'Membro'; ?>
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
                    <label for="status">Status</label>
                    <select id="status" name="status">
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
</main>

<?php include_once 'includes/footer.php'; ?>
