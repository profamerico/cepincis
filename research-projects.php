<?php
$pageTitle = 'Projetos de Pesquisa | CEPIN-CIS';
$bodyClass = 'app-page research-projects-page';

require_once 'controllers/AuthController.php';
require_once 'models/Orientation.php';
require_once 'models/Project.php';

function research_projects_set_flash(string $type, string $message): void
{
    $_SESSION['research_projects_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function research_projects_redirect(string $suffix = ''): void
{
    $location = 'research-projects.php';
    if ($suffix !== '') {
        $location .= $suffix;
    }

    header('Location: ' . $location);
    exit();
}

function research_projects_status_label(string $status): string
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

function research_projects_format_datetime(?string $value): string
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

function research_projects_format_tags(array $tags): string
{
    if (empty($tags)) {
        return 'Sem tags';
    }

    return implode(', ', $tags);
}

function research_projects_excerpt(string $value, int $limit = 170): string
{
    $value = trim(preg_replace('/\s+/', ' ', $value));
    if ($value === '') {
        return '';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        return rtrim(mb_substr($value, 0, $limit - 1, 'UTF-8')) . '...';
    }

    if (strlen($value) <= $limit) {
        return $value;
    }

    return rtrim(substr($value, 0, $limit - 1)) . '...';
}

$auth = new AuthController();
$auth->requireAuth();

$currentUser = $auth->getCurrentUser();
if (!$auth->canCreateProjects($currentUser)) {
    header('Location: dashboard.php');
    exit();
}

$projectManager = new ProjectManager();
$orientationManager = new OrientationManager();
$allUsers = $auth->listUsers();
$allProjects = $projectManager->getAllProjects();
$thematicAreaOptions = $projectManager->getThematicAreaOptions();
$isAdmin = $auth->isAdmin($currentUser);
$displayName = (string) ($currentUser['fullname'] ?? $currentUser['username']);
$roleLabel = $auth->getRoleLabel($currentUser);

$userMap = [];
foreach ($allUsers as $user) {
    $userMap[(int) ($user['id'] ?? 0)] = $user;
}

if (empty($_SESSION['research_projects_csrf'])) {
    $_SESSION['research_projects_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['research_projects_csrf'];
$flash = $_SESSION['research_projects_flash'] ?? null;
unset($_SESSION['research_projects_flash']);

$formErrors = [];
$formOverrides = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        $flash = ['type' => 'erro', 'message' => 'Sessao expirada. Recarregue a pagina e tente novamente.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');

        switch ($action) {
            case 'save_research_project':
                $projectId = trim((string) ($_POST['project_id'] ?? ''));
                $existingProject = $projectId !== '' ? $projectManager->getProject($projectId) : false;

                if ($projectId !== '' && !is_array($existingProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                if (!$isAdmin && is_array($existingProject) && (int) ($existingProject['user_id'] ?? 0) !== (int) $currentUser['id']) {
                    $flash = ['type' => 'erro', 'message' => 'Voce so pode editar projetos criados na sua propria area.'];
                    break;
                }

                $submittedProject = [
                    'id' => $projectId,
                    'user_id' => $isAdmin ? trim((string) ($_POST['user_id'] ?? '')) : (string) ((int) $currentUser['id']),
                    'title' => trim((string) ($_POST['title'] ?? '')),
                    'category' => trim((string) ($_POST['category'] ?? '')),
                    'tags' => array_values(array_filter(array_map('strval', (array) ($_POST['tags'] ?? [])))),
                    'status' => trim((string) ($_POST['status'] ?? 'active')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'participation_info' => trim((string) ($_POST['participation_info'] ?? '')),
                    'image_path' => trim((string) ($_POST['image_path'] ?? '')),
                ];
                $uploadedProjectImage = isset($_FILES['image_file']) && is_array($_FILES['image_file'])
                    ? $_FILES['image_file']
                    : null;

                if ($isAdmin && $submittedProject['user_id'] !== '' && !isset($userMap[(int) $submittedProject['user_id']])) {
                    $formErrors = ['Selecione um responsavel valido para o projeto.'];
                    $formOverrides = $submittedProject;
                    break;
                }

                $result = $projectManager->adminSaveProject($projectId !== '' ? $projectId : null, [
                    'user_id' => $submittedProject['user_id'],
                    'title' => $submittedProject['title'],
                    'category' => $submittedProject['category'],
                    'tags' => $submittedProject['tags'],
                    'status' => $submittedProject['status'],
                    'description' => $submittedProject['description'],
                    'participation_info' => $submittedProject['participation_info'],
                    'image_path' => $submittedProject['image_path'],
                ], $uploadedProjectImage);

                if ($result['success']) {
                    research_projects_set_flash(
                        'sucesso',
                        !empty($result['created']) ? 'Projeto criado com sucesso.' : 'Projeto atualizado com sucesso.'
                    );
                    research_projects_redirect('#manage');
                }

                $formErrors = $result['errors'] ?? ['Nao foi possivel salvar o projeto.'];
                $formOverrides = $submittedProject;
                break;

            case 'delete_research_project':
                $projectId = trim((string) ($_POST['project_id'] ?? ''));
                $existingProject = $projectId !== '' ? $projectManager->getProject($projectId) : false;

                if (!is_array($existingProject)) {
                    $flash = ['type' => 'erro', 'message' => 'Projeto nao encontrado.'];
                    break;
                }

                if (!$isAdmin && (int) ($existingProject['user_id'] ?? 0) !== (int) $currentUser['id']) {
                    $flash = ['type' => 'erro', 'message' => 'Voce so pode remover projetos que pertencem a sua conta.'];
                    break;
                }

                if ($projectManager->deleteProject($projectId)) {
                    $clearedOrientations = $orientationManager->clearProjectReferences($projectId);
                    $message = 'Projeto removido com sucesso.';
                    if ($clearedOrientations > 0) {
                        $message .= ' ' . $clearedOrientations . ' orientacao(oes) perderam o vinculo com este projeto.';
                    }

                    research_projects_set_flash('sucesso', $message);
                    research_projects_redirect('#list');
                }

                $flash = ['type' => 'erro', 'message' => 'Nao foi possivel remover o projeto.'];
                break;
        }
    }
}

$projects = $isAdmin ? $projectManager->getAllProjects() : $projectManager->getUserProjects((int) $currentUser['id']);
$projectStats = $isAdmin ? $projectManager->getProjectStats() : $projectManager->getUserStats((int) $currentUser['id']);
$linkedOrientationCount = count(array_filter($orientationManager->listOrientations(), static function (array $orientation) use ($projects): bool {
    $projectIds = [];
    foreach ($projects as $project) {
        $projectIds[(string) ($project['id'] ?? '')] = true;
    }

    $projectId = trim((string) ($orientation['project_id'] ?? ''));
    return $projectId !== '' && isset($projectIds[$projectId]);
}));

$editingProject = null;
if (!empty($_GET['edit'])) {
    $candidate = $projectManager->getProject((string) $_GET['edit']);
    if (is_array($candidate) && ($isAdmin || (int) ($candidate['user_id'] ?? 0) === (int) $currentUser['id'])) {
        $editingProject = $candidate;
    }
}

$projectForm = [
    'id' => '',
    'user_id' => $isAdmin ? '' : (string) ((int) $currentUser['id']),
    'title' => '',
    'category' => $projectManager->getDefaultThematicArea(),
    'tags' => [],
    'status' => 'active',
    'description' => '',
    'participation_info' => '',
    'image_path' => '',
];

if (is_array($editingProject)) {
    $projectForm = [
        'id' => (string) ($editingProject['id'] ?? ''),
        'user_id' => (string) ($editingProject['user_id'] ?? ''),
        'title' => (string) ($editingProject['title'] ?? ''),
        'category' => (string) ($editingProject['category'] ?? $projectManager->getDefaultThematicArea()),
        'tags' => $editingProject['tags'] ?? [],
        'status' => (string) ($editingProject['status'] ?? 'active'),
        'description' => (string) ($editingProject['description'] ?? ''),
        'participation_info' => (string) ($editingProject['participation_info'] ?? ''),
        'image_path' => (string) ($editingProject['image_path'] ?? ''),
    ];
}

if (is_array($formOverrides)) {
    $projectForm = array_merge($projectForm, [
        'id' => (string) ($formOverrides['id'] ?? $projectForm['id']),
        'user_id' => (string) ($formOverrides['user_id'] ?? $projectForm['user_id']),
        'title' => (string) ($formOverrides['title'] ?? $projectForm['title']),
        'category' => (string) ($formOverrides['category'] ?? $projectForm['category']),
        'tags' => is_array($formOverrides['tags'] ?? null) ? $formOverrides['tags'] : $projectForm['tags'],
        'status' => (string) ($formOverrides['status'] ?? $projectForm['status']),
        'description' => (string) ($formOverrides['description'] ?? $projectForm['description']),
        'participation_info' => (string) ($formOverrides['participation_info'] ?? $projectForm['participation_info']),
        'image_path' => (string) ($formOverrides['image_path'] ?? $projectForm['image_path']),
    ]);
}
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Workspace de pesquisa</p>
            <h1>Projetos de pesquisa</h1>
            <p class="hero-copy">
                <?php echo htmlspecialchars(
                    $isAdmin
                        ? 'Esta area oferece uma visao focada em projetos para complementar o painel mestre e o fluxo de orientacoes.'
                        : 'Como Pesquisador Pleno, voce pode cadastrar projetos diretamente daqui. Eles entram na base principal do portal e passam a abastecer a home e as paginas relacionadas.',
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="dashboard.php">Voltar ao dashboard</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#manage"><?php echo $projectForm['id'] !== '' ? 'Continuar edicao' : 'Novo projeto'; ?></a>
                <a class="dashboard-btn dashboard-btn--ghost" href="orientations.php">Abrir orientacoes</a>
            </div>
        </div>

        <aside class="panel-hero-aside">
            <span class="dashboard-badge"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <h2><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h2>
            <p><?php echo $isAdmin ? 'Voce pode revisar qualquer projeto por aqui, embora o painel admin continue sendo o ponto de governanca total.' : 'Tudo o que voce criar aqui ja nasce integrado ao restante do portal.'; ?></p>
            <ul class="hero-meta-list">
                <li>Projetos totais: <?php echo (int) ($projectStats['total'] ?? 0); ?></li>
                <li>Projetos ativos: <?php echo (int) ($projectStats['active'] ?? 0); ?></li>
                <li>Orientacoes vinculadas: <?php echo $linkedOrientationCount; ?></li>
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
            <span class="metric-label">Projetos</span>
            <strong class="metric-value"><?php echo (int) ($projectStats['total'] ?? 0); ?></strong>
            <p><?php echo $isAdmin ? 'Projetos totais presentes na plataforma.' : 'Projetos atualmente ligados a sua conta.'; ?></p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Ativos</span>
            <strong class="metric-value"><?php echo (int) ($projectStats['active'] ?? 0); ?></strong>
            <p>Projetos em andamento neste momento.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Pendentes</span>
            <strong class="metric-value"><?php echo (int) ($projectStats['pending'] ?? 0); ?></strong>
            <p>Projetos aguardando algum passo seguinte.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label"><?php echo $isAdmin ? 'Sem responsavel' : 'Concluidos'; ?></span>
            <strong class="metric-value"><?php echo $isAdmin ? (int) ($projectStats['without_owner'] ?? 0) : (int) ($projectStats['completed'] ?? 0); ?></strong>
            <p><?php echo $isAdmin ? 'Projetos hoje sem responsavel definido.' : 'Projetos finalizados dentro do seu historico.'; ?></p>
        </article>
    </section>

    <section class="dashboard-layout">
        <article id="manage" class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Publicacao</p>
                    <h2><?php echo $projectForm['id'] !== '' ? 'Editar projeto' : 'Novo projeto'; ?></h2>
                    <p class="admin-subtitle">Este formulario publica o projeto na mesma base usada pela home, pelo admin e pelos fluxos de orientacao.</p>
                </div>
            </div>

            <?php foreach ($formErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <form method="POST" enctype="multipart/form-data" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_research_project">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars((string) $projectForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <?php if ($isAdmin): ?>
                    <div class="form-group">
                        <label for="project_user_id">Responsavel</label>
                        <select id="project_user_id" name="user_id">
                            <option value="">Sem responsavel</option>
                            <?php foreach ($allUsers as $user): ?>
                                <?php $userId = (int) ($user['id'] ?? 0); ?>
                                <option value="<?php echo $userId; ?>" <?php echo (string) $projectForm['user_id'] === (string) $userId ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="project_owner_locked">Responsavel</label>
                        <input type="text" id="project_owner_locked" value="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="project_title">Titulo</label>
                    <input type="text" id="project_title" name="title" value="<?php echo htmlspecialchars((string) $projectForm['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="project_category">Categoria</label>
                    <select id="project_category" name="category" required>
                        <?php foreach ($thematicAreaOptions as $areaKey => $areaLabel): ?>
                            <option value="<?php echo htmlspecialchars((string) $areaKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo (string) $projectForm['category'] === (string) $areaKey ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars((string) $areaLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="form-help">A categoria principal do projeto fica restrita as 5 siglas oficiais das Areas Tematicas.</p>
                </div>

                <div class="form-group">
                    <label for="project_tags">Tags</label>
                    <div class="tag-options-grid" id="project_tags">
                        <?php foreach ($thematicAreaOptions as $areaKey => $areaLabel): ?>
                            <?php $isChecked = in_array((string) $areaKey, $projectForm['tags'], true); ?>
                            <label class="checkbox-row tag-option-card">
                                <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars((string) $areaKey, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $isChecked ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars((string) $areaLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="form-help">Escolha entre as mesmas 5 siglas oficiais para alimentar filtros e destaques do portal.</p>
                </div>

                <div class="form-group">
                    <label for="project_status">Status</label>
                    <select id="project_status" name="status">
                        <option value="active" <?php echo (string) $projectForm['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="pending" <?php echo (string) $projectForm['status'] === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="completed" <?php echo (string) $projectForm['status'] === 'completed' ? 'selected' : ''; ?>>Concluido</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="project_description">Descricao</label>
                    <textarea id="project_description" name="description" rows="8" placeholder="Apresente objetivo, escopo, impacto e contexto do projeto."><?php echo htmlspecialchars((string) $projectForm['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="project_participation_info">Como participar</label>
                    <textarea id="project_participation_info" name="participation_info" rows="6" placeholder="Explique quem pode entrar, disponibilidade esperada, perfil desejado e como a pessoa deve se apresentar ao escrever para a equipe."><?php echo htmlspecialchars((string) $projectForm['participation_info'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="form-help">Esse texto aparecera na pagina detalhada do projeto, junto do CTA para a pessoa escrever ao responsavel e ao CEPIN-CIS.</p>
                </div>

                <?php if ((string) $projectForm['image_path'] !== ''): ?>
                    <div class="admin-partner-preview admin-project-preview">
                        <img
                            src="<?php echo htmlspecialchars((string) $projectForm['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars((string) ($projectForm['title'] !== '' ? $projectForm['title'] : 'Preview do projeto'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="admin-partner-preview__image"
                        >
                        <div class="admin-partner-preview__copy">
                            <strong>Banner atual do projeto</strong>
                            <p><?php echo htmlspecialchars((string) ($projectForm['title'] !== '' ? $projectForm['title'] : 'Projeto em edicao'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span><?php echo htmlspecialchars((string) $projectForm['image_path'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="project_image_file">Imagem do banner</label>
                    <input type="file" id="project_image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <p class="form-help">Envie JPG, PNG, WEBP ou GIF com ate 6 MB. Essa arte sera usada como imagem de fundo do banner na pagina detalhada do projeto.</p>
                </div>

                <div class="form-group">
                    <label for="project_image_path">Ou caminho da imagem</label>
                    <input type="text" id="project_image_path" name="image_path" value="<?php echo htmlspecialchars((string) $projectForm['image_path'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="./img/projeto-banner.png ou ./uploads/projects/arquivo.png">
                    <p class="form-help">Se o asset ja existe no projeto, voce pode apontar o caminho local diretamente sem reenviar o arquivo.</p>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $projectForm['id'] !== '' ? 'Salvar projeto' : 'Criar projeto'; ?></button>
            </form>
        </article>

        <div class="stacked-panels">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Conexao com o portal</p>
                        <h2>O que muda quando voce publica</h2>
                    </div>
                </div>

                <ul class="dashboard-list">
                    <li>
                        <span>Base unica</span>
                        <strong>Home, admin e workspace</strong>
                    </li>
                    <li>
                        <span>Vinculo com orientacoes</span>
                        <strong><?php echo $linkedOrientationCount; ?> associado(s)</strong>
                    </li>
                    <li>
                        <span>Responsavel atual</span>
                        <strong><?php echo $isAdmin ? 'Definivel por projeto' : htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Nivel atual</span>
                        <strong><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                </ul>
            </article>

            <article class="panel-card accent-panel">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Boas praticas</p>
                        <h2>Como manter esse fluxo saudavel</h2>
                    </div>
                </div>

                <p class="panel-copy">Publique o projeto com titulo, descricao e tags claras. Depois, quando ele tiver orientandos envolvidos, conecte a orientacao no workspace correspondente para fechar o ciclo da hierarquia.</p>
            </article>
        </div>
    </section>

    <section id="list" class="admin-workspace">
        <article class="panel-card admin-workspace__full">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow"><?php echo $isAdmin ? 'Panorama' : 'Historico'; ?></p>
                    <h2><?php echo $isAdmin ? 'Projetos cadastrados' : 'Meus projetos'; ?></h2>
                    <p class="admin-subtitle"><?php echo $isAdmin ? 'O admin consegue revisar tudo por aqui, mas o ownership continua respeitado para pesquisadores plenos.' : 'Esta listagem mostra tudo o que ja foi publicado ou salvo na sua area de pesquisa.'; ?></p>
                </div>
            </div>

            <?php if (empty($projects)): ?>
                <p class="admin-empty"><?php echo $isAdmin ? 'Nenhum projeto cadastrado ainda.' : 'Voce ainda nao cadastrou nenhum projeto.'; ?></p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Categoria e tags</th>
                                <th>Status</th>
                                <th>Responsavel</th>
                                <th>Atualizado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <?php
                                $projectId = (string) ($project['id'] ?? '');
                                $owner = $userMap[(int) ($project['user_id'] ?? 0)] ?? null;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($project['title'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php if ((string) ($project['description'] ?? '') !== ''): ?>
                                            <div class="admin-meta"><?php echo htmlspecialchars(research_projects_excerpt((string) $project['description']), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($project['category'] ?? 'Geral'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars(research_projects_format_tags($project['tags'] ?? []), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><span class="admin-pill admin-pill--status"><?php echo htmlspecialchars(research_projects_status_label((string) ($project['status'] ?? 'active')), ENT_QUOTES, 'UTF-8'); ?></span></td>
                                    <td><?php echo htmlspecialchars((string) ($owner['fullname'] ?? $owner['username'] ?? 'Sem responsavel'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(research_projects_format_datetime($project['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project.php?id=<?php echo urlencode($projectId); ?>">Ver pagina</a>
                                            <a class="dashboard-btn admin-btn-small" href="research-projects.php?edit=<?php echo urlencode($projectId); ?>#manage">Editar</a>
                                            <form method="POST" onsubmit="return confirm('Excluir este projeto?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_research_project">
                                                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars($projectId, ENT_QUOTES, 'UTF-8'); ?>">
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
