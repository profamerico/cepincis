<?php
$pageTitle = 'Orientações | CEPIN-CIS';
$bodyClass = 'app-page orientations-page';

header('Content-Type: text/html; charset=UTF-8');
require_once 'controllers/AuthController.php';
require_once 'models/Orientation.php';
require_once 'models/Project.php';

function orientations_set_flash(string $type, string $message): void
{
        $_SESSION['orientations_flash'] = [
                'type' => $type,
                'message' => $message,
        ];
}

function orientations_redirect(string $suffix = ''): void
{
        $location = 'orientations.php';
        if ($suffix !== '') {
                $location .= $suffix;
        }

        header('Location: ' . $location);
        exit();
}

function orientations_status_label(string $status): string
{
        switch ($status) {
                case 'active':
                        return 'Ativa';
                case 'completed':
                        return 'Concluida';
                default:
                        return 'Planejáda';
        }
}

function orientations_format_datetime(?string $value): string
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

function orientations_excerpt(string $value, int $limit = 160): string
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
if (!$auth->canAccessResearchWorkspace($currentUser)) {
        header('Location: dashboard.php');
        exit();
}

$orientationManager = new OrientationManager();
$projectManager = new ProjectManager();
$allUsers = $auth->listUsers();
$allProjects = $projectManager->getAllProjects();
$isAdmin = $auth->isAdmin($currentUser);
$isAcademic = $auth->isAcademicResearcher($currentUser);
$canManageOrientations = $auth->canManageOrientations($currentUser);
$displayName = (string) ($currentUser['fullname'] ?? $currentUser['username']);
$roleLabel = $auth->getRoleLabel($currentUser);

$userMap = [];
foreach ($allUsers as $user) {
        $userMap[(int) ($user['id'] ?? 0)] = $user;
}

$projectMap = [];
foreach ($allProjects as $project) {
        $projectMap[(string) ($project['id'] ?? '')] = $project;
}

$academicUsers = array_values(array_filter($allUsers, static function (array $user) use ($auth): bool {
        return $auth->isAcademicResearcher($user);
}));
$supervisorUsers = array_values(array_filter($allUsers, static function (array $user) use ($auth): bool {
        return $auth->canManageOrientations($user) || $auth->isAdmin($user);
}));

if (empty($_SESSION['orientations_csrf'])) {
        $_SESSION['orientations_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['orientations_csrf'];
$flash = $_SESSION['orientations_flash'] ?? null;
unset($_SESSION['orientations_flash']);

$formErrors = [];
$formOverrides = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $postedToken = (string) ($_POST['csrf_token'] ?? '');

        if (!hash_equals($csrfToken, $postedToken)) {
                $flash = ['type' => 'erro', 'message' => 'Sessão expirada. Recarregue a página e tente novamente.'];
        } else {
                $action = (string) ($_POST['action'] ?? '');

                switch ($action) {
                        case 'save_orientation':
                                if (!$canManageOrientations) {
                                        $flash = ['type' => 'erro', 'message' => 'Seu nível atual não pode editar orientações.'];
                                        break;
                                }

                                $orientationId = trim((string) ($_POST['orientation_id'] ?? ''));
                                $existingOrientation = $orientationId !== '' ? $orientationManager->getOrientation($orientationId) : false;

                                if ($orientationId !== '' && !is_array($existingOrientation)) {
                                        $flash = ['type' => 'erro', 'message' => 'Orientação não encontrada.'];
                                        break;
                                }

                                if (!$isAdmin && is_array($existingOrientation) && (int) ($existingOrientation['supervisor_id'] ?? 0) !== (int) $currentUser['id']) {
                                        $flash = ['type' => 'erro', 'message' => 'Você so pode editar orientações sob sua própria supervisão.'];
                                        break;
                                }

                                $submittedOrientation = [
                                        'id' => $orientationId,
                                        'title' => trim((string) ($_POST['title'] ?? '')),
                                        'researcher_id' => (int) ($_POST['researcher_id'] ?? 0),
                                        'supervisor_id' => $isAdmin ? (int) ($_POST['supervisor_id'] ?? 0) : (int) $currentUser['id'],
                                        'project_id' => trim((string) ($_POST['project_id'] ?? '')),
                                        'workload' => trim((string) ($_POST['workload'] ?? '')),
                                        'status' => trim((string) ($_POST['status'] ?? 'planned')),
                                        'notes' => trim((string) ($_POST['notes'] ?? '')),
                                ];

                                $validationErrors = [];
                                $researcher = $auth->getUserById($submittedOrientation['researcher_id']);
                                if (!is_array($researcher) || !$auth->isAcademicResearcher($researcher)) {
                                        $validationErrors[] = 'Selecione um Pesquisador Acadêmico válido.';
                                }

                                $supervisor = $auth->getUserById($submittedOrientation['supervisor_id']);
                                if (!is_array($supervisor) || !($auth->canManageOrientations($supervisor) || $auth->isAdmin($supervisor))) {
                                        $validationErrors[] = 'Supervisor inválido para esta orientação.';
                                }

                                if ($submittedOrientation['project_id'] !== '' && !isset($projectMap[$submittedOrientation['project_id']])) {
                                        $validationErrors[] = 'O projeto vinculado não foi encontrado.';
                                }

                                if ($validationErrors) {
                                        $formErrors = $validationErrors;
                                        $formOverrides = $submittedOrientation;
                                        break;
                                }

                                $result = $orientationManager->saveOrientation($orientationId !== '' ? $orientationId : null, [
                                        'title' => $submittedOrientation['title'],
                                        'researcher_id' => $submittedOrientation['researcher_id'],
                                        'supervisor_id' => $submittedOrientation['supervisor_id'],
                                        'project_id' => $submittedOrientation['project_id'],
                                        'workload' => $submittedOrientation['workload'],
                                        'status' => $submittedOrientation['status'],
                                        'notes' => $submittedOrientation['notes'],
                                ]);

                                if ($result['success']) {
                                        orientations_set_flash(
                                                'sucesso',
                                                !empty($result['created']) ? 'Orientação criada com sucesso.' : 'Orientação atualizada com sucesso.'
                                        );
                                        orientations_redirect('#manage');
                                }

                                $formErrors = $result['errors'] ?? ['Não foi possível salvar a orientação.'];
                                $formOverrides = $submittedOrientation;
                                break;

                        case 'delete_orientation':
                                if (!$canManageOrientations) {
                                        $flash = ['type' => 'erro', 'message' => 'Seu nível atual não pode remover orientações.'];
                                        break;
                                }

                                $orientationId = trim((string) ($_POST['orientation_id'] ?? ''));
                                $existingOrientation = $orientationId !== '' ? $orientationManager->getOrientation($orientationId) : false;

                                if (!is_array($existingOrientation)) {
                                        $flash = ['type' => 'erro', 'message' => 'Orientação não encontrada.'];
                                        break;
                                }

                                if (!$isAdmin && (int) ($existingOrientation['supervisor_id'] ?? 0) !== (int) $currentUser['id']) {
                                        $flash = ['type' => 'erro', 'message' => 'Você so pode remover orientações sob sua própria supervisão.'];
                                        break;
                                }

                                if ($orientationManager->deleteOrientation($orientationId)) {
                                        orientations_set_flash('sucesso', 'Orientação removida com sucesso.');
                                        orientations_redirect('#list');
                                }

                                $flash = ['type' => 'erro', 'message' => 'Não foi possível remover a orientação.'];
                                break;
                }
        }
}

if ($isAdmin) {
        $orientations = $orientationManager->listOrientations();
        $orientationStats = [
                'total' => count($orientations),
                'planned' => count(array_filter($orientations, static function (array $orientation): bool {
                        return (string) ($orientation['status'] ?? '') === 'planned';
                })),
                'active' => count(array_filter($orientations, static function (array $orientation): bool {
                        return (string) ($orientation['status'] ?? '') === 'active';
                })),
                'completed' => count(array_filter($orientations, static function (array $orientation): bool {
                        return (string) ($orientation['status'] ?? '') === 'completed';
                })),
        ];
} elseif ($canManageOrientations) {
        $orientations = $orientationManager->getOrientationsForSupervisor((int) $currentUser['id']);
        $orientationStats = $orientationManager->getStatsForSupervisor((int) $currentUser['id']);
} else {
        $orientations = $orientationManager->getOrientationsForResearcher((int) $currentUser['id']);
        $orientationStats = $orientationManager->getStatsForResearcher((int) $currentUser['id']);
}

$linkedProjectsCount = count(array_unique(array_filter(array_map(static function (array $orientation): string {
        return trim((string) ($orientation['project_id'] ?? ''));
}, $orientations))));
$supervisorCount = count(array_unique(array_filter(array_map(static function (array $orientation): int {
        return (int) ($orientation['supervisor_id'] ?? 0);
}, $orientations))));
$researcherCount = count(array_unique(array_filter(array_map(static function (array $orientation): int {
        return (int) ($orientation['researcher_id'] ?? 0);
}, $orientations))));

$editingOrientation = null;
if ($canManageOrientations && !empty($_GET['edit'])) {
        $candidate = $orientationManager->getOrientation((string) $_GET['edit']);
        if (is_array($candidate) && ($isAdmin || (int) ($candidate['supervisor_id'] ?? 0) === (int) $currentUser['id'])) {
                $editingOrientation = $candidate;
        }
}

$orientationForm = [
        'id' => '',
        'title' => '',
        'researcher_id' => '',
        'supervisor_id' => $isAdmin ? '' : (string) ((int) $currentUser['id']),
        'project_id' => '',
        'workload' => '',
        'status' => 'planned',
        'notes' => '',
];

if (is_array($editingOrientation)) {
        $orientationForm = [
                'id' => (string) ($editingOrientation['id'] ?? ''),
                'title' => (string) ($editingOrientation['title'] ?? ''),
                'researcher_id' => (string) ($editingOrientation['researcher_id'] ?? ''),
                'supervisor_id' => (string) ($editingOrientation['supervisor_id'] ?? ''),
                'project_id' => (string) ($editingOrientation['project_id'] ?? ''),
                'workload' => (string) ($editingOrientation['workload'] ?? ''),
                'status' => (string) ($editingOrientation['status'] ?? 'planned'),
                'notes' => (string) ($editingOrientation['notes'] ?? ''),
        ];
}

if (is_array($formOverrides)) {
        $orientationForm = array_merge($orientationForm, [
                'id' => (string) ($formOverrides['id'] ?? $orientationForm['id']),
                'title' => (string) ($formOverrides['title'] ?? $orientationForm['title']),
                'researcher_id' => (string) ($formOverrides['researcher_id'] ?? $orientationForm['researcher_id']),
                'supervisor_id' => (string) ($formOverrides['supervisor_id'] ?? $orientationForm['supervisor_id']),
                'project_id' => (string) ($formOverrides['project_id'] ?? $orientationForm['project_id']),
                'workload' => (string) ($formOverrides['workload'] ?? $orientationForm['workload']),
                'status' => (string) ($formOverrides['status'] ?? $orientationForm['status']),
                'notes' => (string) ($formOverrides['notes'] ?? $orientationForm['notes']),
        ]);
}

$heroCopy = $canManageOrientations
        ? 'Registre orientações acadêmicas, vincule projetos e acompanhe o andamento do trabalho supervisionado dentro do CEPIN-CIS.'
        : 'Acompanhe as orientações que foram registradas para você e veja os projetos e supervisores associados ao seu percurso acadêmico.';
$asideCopy = $canManageOrientations
        ? 'Seu painel de orientação está pronto para organizar pesquisadores acadêmicos, status e carga horária.'
        : 'As orientações aparecem aqui de forma somente leitura para você acompanhar o que está em andamento.';
$metrics = $canManageOrientations
        ? [
                ['label' => 'Orientações', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Quantidade atualmente sob sua visão de trabalho.'],
                ['label' => 'Ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientações em execução neste momento.'],
                ['label' => 'Projetos vinculados', 'value' => $linkedProjectsCount, 'copy' => 'Projetos que hoje sustentam essas orientações.'],
                ['label' => 'Pesquisadores', 'value' => $researcherCount, 'copy' => 'Pesquisadores acadêmicos ligados a suas orientações.'],
        ]
        : [
                ['label' => 'Orientações', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Quantidade atual ligada ao seu percurso.'],
                ['label' => 'Ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Frentes em andamento no momento.'],
                ['label' => 'Planejadas', 'value' => (int) ($orientationStats['planned'] ?? 0), 'copy' => 'Orientações registradas aguardando início.'],
                ['label' => 'Projetos vinculados', 'value' => $linkedProjectsCount, 'copy' => 'Projetos conectados as suas orientações.'],
        ];
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
        <section class="panel-hero">
                <div class="panel-hero-main">
                        <p class="eyebrow">Workspace de pesquisa</p>
                        <h1>Orientações</h1>
                        <p class="hero-copy"><?php echo htmlspecialchars($heroCopy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>

                        <div class="hero-actions">
                                <a class="dashboard-btn" href="dashboard.php">Voltar ao dashboard</a>
                                <?php if ($canManageOrientations): ?>
                                        <a class="dashboard-btn dashboard-btn--ghost" href="#manage"><?php echo $orientationForm['id'] !== '' ? 'Continuar edição' : 'Nova orientação'; ?></a>
                                <?php endif; ?>
                                <?php if ($auth->canCreateProjects($currentUser)): ?>
                                        <a class="dashboard-btn dashboard-btn--ghost" href="research-projects.php">Projetos de pesquisa</a>
                                <?php endif; ?>
                        </div>
                </div>

                <aside class="panel-hero-aside">
                        <span class="dashboard-badge"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                        <h2><?php echo htmlspecialchars($displayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
                        <p><?php echo htmlspecialchars($asideCopy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                        <ul class="hero-meta-list">
                                <li>Orientações ativas: <?php echo (int) ($orientationStats['active'] ?? 0); ?></li>
                                <li>Projetos vinculados: <?php echo $linkedProjectsCount; ?></li>
                                <li><?php echo $canManageOrientations ? 'Pesquisadores acompanhados' : 'Supervisores envolvidos'; ?>: <?php echo $canManageOrientations ? $researcherCount : $supervisorCount; ?></li>
                        </ul>
                </aside>
        </section>

        <?php if ($flash): ?>
                <div class="mensagem <?php echo htmlspecialchars((string) $flash['type'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </div>
        <?php endif; ?>

        <section class="metrics-grid">
                <?php foreach ($metrics as $metric): ?>
                        <article class="metric-card">
                                <span class="metric-label"><?php echo htmlspecialchars((string) $metric['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                                <strong class="metric-value"><?php echo htmlspecialchars((string) $metric['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                <p><?php echo htmlspecialchars((string) $metric['copy'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                        </article>
                <?php endforeach; ?>
        </section>

        <section class="dashboard-layout">
                <article id="manage" class="panel-card">
                        <div class="panel-card-header">
                                <div>
                                        <p class="eyebrow"><?php echo $canManageOrientations ? 'Supervisão' : 'Consulta'; ?></p>
                                        <h2><?php echo $canManageOrientations ? ($orientationForm['id'] !== '' ? 'Editar orientação' : 'Nova orientação') : 'Como este espaço funciona'; ?></h2>
                                        <p class="admin-subtitle">
                                                <?php if ($canManageOrientations): ?>
                                                        Pesquisadores Associados, Plenos e administradores podem cadastrar orientações para pesquisadores acadêmicos e relaciona-las a projetos existentes.
                                                <?php else: ?>
                                                        Seu perfil acompanha as orientações atribuidas por pesquisadores associados, plenos ou administradores. Quando novas orientações forem criadas, elas aparecerao na tabela abaixo.
                                                <?php endif; ?>
                                        </p>
                                </div>
                        </div>

                        <?php foreach ($formErrors as $error): ?>
                                <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                        <?php endforeach; ?>

                        <?php if ($canManageOrientations): ?>
                                <form method="POST" class="stack-form">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                        <input type="hidden" name="action" value="save_orientation">
                                        <input type="hidden" name="orientation_id" value="<?php echo htmlspecialchars((string) $orientationForm['id'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">

                                        <div class="form-group">
                                                <label for="orientation_title">Título da orientação</label>
                                                <input type="text" id="orientation_title" name="title" value="<?php echo htmlspecialchars((string) $orientationForm['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" required>
                                        </div>

                                        <div class="form-group">
                                                <label for="orientation_researcher">Pesquisador Acadêmico</label>
                                                <select id="orientation_researcher" name="researcher_id" required>
                                                        <option value="">Selecione</option>
                                                        <?php foreach ($academicUsers as $academicUser): ?>
                                                                <?php $academicId = (int) ($academicUser['id'] ?? 0); ?>
                                                                <option value="<?php echo $academicId; ?>" <?php echo (string) $orientationForm['researcher_id'] === (string) $academicId ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars((string) ($academicUser['fullname'] ?? $academicUser['username']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                                                </option>
                                                        <?php endforeach; ?>
                                                </select>
                                                <?php if (empty($academicUsers)): ?>
                                                        <p class="form-help">Ainda não existe nenhum usuário com o nível Pesquisador Acadêmico para ser vinculado.</p>
                                                <?php endif; ?>
                                        </div>

                                        <?php if ($isAdmin): ?>
                                                <div class="form-group">
                                                        <label for="orientation_supervisor">Supervisor</label>
                                                        <select id="orientation_supervisor" name="supervisor_id" required>
                                                                <option value="">Selecione</option>
                                                                <?php foreach ($supervisorUsers as $supervisorUser): ?>
                                                                        <?php $supervisorId = (int) ($supervisorUser['id'] ?? 0); ?>
                                                                        <option value="<?php echo $supervisorId; ?>" <?php echo (string) $orientationForm['supervisor_id'] === (string) $supervisorId ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars((string) ($supervisorUser['fullname'] ?? $supervisorUser['username']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                                                        </option>
                                                                <?php endforeach; ?>
                                                        </select>
                                                </div>
                                        <?php else: ?>
                                                <div class="form-group">
                                                        <label for="orientation_supervisor_locked">Supervisor</label>
                                                        <input type="text" id="orientation_supervisor_locked" value="<?php echo htmlspecialchars($displayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" disabled>
                                                </div>
                                        <?php endif; ?>

                                        <div class="form-group">
                                                <label for="orientation_project">Projeto vinculado</label>
                                                <select id="orientation_project" name="project_id">
                                                        <option value="">Sem projeto específico</option>
                                                        <?php foreach ($allProjects as $project): ?>
                                                                <?php $projectId = (string) ($project['id'] ?? ''); ?>
                                                                <option value="<?php echo htmlspecialchars($projectId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" <?php echo (string) $orientationForm['project_id'] === $projectId ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars((string) ($project['title'] ?? 'Projeto'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                                                                </option>
                                                        <?php endforeach; ?>
                                                </select>
                                                <?php if (empty($allProjects)): ?>
                                                        <p class="form-help">Ainda não ha projetos cadastrados. A orientação pode ser criada agora e vinculada depois.</p>
                                                <?php endif; ?>
                                        </div>

                                        <div class="form-group">
                                                <label for="orientation_workload">Carga horária ou vínculo</label>
                                                <input type="text" id="orientation_workload" name="workload" value="<?php echo htmlspecialchars((string) $orientationForm['workload'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>" placeholder="Ex.: 20h semanais | IC | voluntário">
                                        </div>

                                        <div class="form-group">
                                                <label for="orientation_status">Status</label>
                                                <select id="orientation_status" name="status">
                                                        <option value="planned" <?php echo (string) $orientationForm['status'] === 'planned' ? 'selected' : ''; ?>>Planejáda</option>
                                                        <option value="active" <?php echo (string) $orientationForm['status'] === 'active' ? 'selected' : ''; ?>>Ativa</option>
                                                        <option value="completed" <?php echo (string) $orientationForm['status'] === 'completed' ? 'selected' : ''; ?>>Concluida</option>
                                                </select>
                                        </div>

                                        <div class="form-group">
                                                <label for="orientation_notes">Observacôes</label>
                                                <textarea id="orientation_notes" name="notes" rows="6" placeholder="Diretrizes, objetivo da orientação, edital, observações de acompanhamento..."><?php echo htmlspecialchars((string) $orientationForm['notes'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></textarea>
                                        </div>

                                        <button type="submit" class="dashboard-btn"><?php echo $orientationForm['id'] !== '' ? 'Salvar orientação' : 'Criar orientação'; ?></button>
                                </form>
                        <?php else: ?>
                                <div class="panel-copy">
                                        Este workspace foi preparado para você acompanhar o que está sendo orientado por um Pesquisador Associado, Pesquisador Pleno ou administrador. Se algo estiver faltando, vale alinhar com o seu supervisor.
                                </div>
                        <?php endif; ?>
                </article>

                <div class="stacked-panels">
                        <article class="panel-card">
                                <div class="panel-card-header">
                                        <div>
                                                <p class="eyebrow">Resumo rapido</p>
                                                <h2><?php echo $canManageOrientations ? 'Seu papel de supervisão' : 'Seu papel no fluxo'; ?></h2>
                                        </div>
                                </div>

                                <ul class="dashboard-list">
                                        <li>
                                                <span>Nível atual</span>
                                                <strong><?php echo htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                        </li>
                                        <li>
                                                <span><?php echo $canManageOrientations ? 'Pesquisadores vinculados' : 'Supervisores vinculados'; ?></span>
                                                <strong><?php echo $canManageOrientations ? $researcherCount : $supervisorCount; ?></strong>
                                        </li>
                                        <li>
                                                <span>Projetos conectados</span>
                                                <strong><?php echo $linkedProjectsCount; ?></strong>
                                        </li>
                                        <li>
                                                <span>Orientações concluídas</span>
                                                <strong><?php echo (int) ($orientationStats['completed'] ?? 0); ?></strong>
                                        </li>
                                </ul>
                        </article>

                        <article class="panel-card accent-panel">
                                <div class="panel-card-header">
                                        <div>
                                                <p class="eyebrow"><?php echo $canManageOrientations ? 'Boas praticas' : 'Leitura'; ?></p>
                                                <h2><?php echo $canManageOrientations ? 'Como usar melhor este espaço' : 'O que você encontra aqui'; ?></h2>
                                        </div>
                                </div>

                                <p class="panel-copy">
                                        <?php if ($canManageOrientations): ?>
                                                Registre a orientação assim que o pesquisador entrar no fluxo, vincule um projeto quando existir e atualize o status conforme o trabalho avancar. Isso deixa a hierarquia viva e rastreavel.
                                        <?php else: ?>
                                                A tabela abaixo concentra título, supervisor, projeto relacionado, status e observações. Assim você não precisa depender de memória ou mensagens soltas para acompanhar seu caminho.
                                        <?php endif; ?>
                                </p>
                        </article>
                </div>
        </section>

        <section id="list" class="admin-workspace">
                <article class="panel-card admin-workspace__full">
                        <div class="panel-card-header">
                                <div>
                                        <p class="eyebrow"><?php echo $canManageOrientations ? 'Acompanhamento' : 'Histórico'; ?></p>
                                        <h2><?php echo $canManageOrientations ? 'Orientações cadastradas' : 'Minhas orientações'; ?></h2>
                                        <p class="admin-subtitle">
                                                <?php echo $canManageOrientations
                                                        ? 'A lista abaixo reflete as orientações sob sua gestao direta. Administradores enxergam o quadro completo.'
                                                        : 'Esta listagem mostra tudo o que já foi registrado para você no workspace de pesquisa.'; ?>
                                        </p>
                                </div>
                        </div>

                        <?php if (empty($orientations)): ?>
                                <p class="admin-empty"><?php echo $canManageOrientations ? 'Nenhuma orientação cadastrada ainda.' : 'Nenhuma orientação foi vinculada a você até agora.'; ?></p>
                        <?php else: ?>
                                <div class="admin-table-wrap">
                                        <table class="admin-table">
                                                <thead>
                                                        <tr>
                                                                <th>Orientação</th>
                                                                <th>Pesquisador Acadêmico</th>
                                                                <th>Supervisor</th>
                                                                <th>Projeto</th>
                                                                <th>Status</th>
                                                                <th>Atualizado em</th>
                                                                <?php if ($canManageOrientations): ?>
                                                                        <th>Açôes</th>
                                                                <?php endif; ?>
                                                        </tr>
                                                </thead>
                                                <tbody>
                                                        <?php foreach ($orientations as $orientation): ?>
                                                                <?php
                                                                $orientationId = (string) ($orientation['id'] ?? '');
                                                                $researcher = $userMap[(int) ($orientation['researcher_id'] ?? 0)] ?? null;
                                                                $supervisor = $userMap[(int) ($orientation['supervisor_id'] ?? 0)] ?? null;
                                                                $project = $projectMap[(string) ($orientation['project_id'] ?? '')] ?? null;
                                                                ?>
                                                                <tr>
                                                                        <td>
                                                                                <strong><?php echo htmlspecialchars((string) ($orientation['title'] ?? 'Orientação'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                                                                                <?php if ((string) ($orientation['workload'] ?? '') !== ''): ?>
                                                                                        <div class="admin-meta"><?php echo htmlspecialchars((string) $orientation['workload'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                                                                <?php endif; ?>
                                                                                <?php if ((string) ($orientation['notes'] ?? '') !== ''): ?>
                                                                                        <div class="admin-meta"><?php echo htmlspecialchars(orientations_excerpt((string) $orientation['notes']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></div>
                                                                                <?php endif; ?>
                                                                        </td>
                                                                        <td><?php echo htmlspecialchars((string) ($researcher['fullname'] ?? $researcher['username'] ?? 'Não encontrado'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                                                        <td><?php echo htmlspecialchars((string) ($supervisor['fullname'] ?? $supervisor['username'] ?? 'Não encontrado'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                                                        <td><?php echo htmlspecialchars((string) ($project['title'] ?? 'Sem projeto vinculado'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                                                        <td><span class="admin-pill admin-pill--status"><?php echo htmlspecialchars(orientations_status_label((string) ($orientation['status'] ?? 'planned')), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span></td>
                                                                        <td><?php echo htmlspecialchars(orientations_format_datetime($orientation['updated_at'] ?? null), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></td>
                                                                        <?php if ($canManageOrientations): ?>
                                                                                <td>
                                                                                        <div class="table-actions">
                                                                                                <a class="dashboard-btn admin-btn-small" href="orientations.php?edit=<?php echo urlencode($orientationId); ?>#manage">Editar</a>
                                                                                                <form method="POST" onsubmit="return confirm('Excluir esta orientação?');">
                                                                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                                                                                        <input type="hidden" name="action" value="delete_orientation">
                                                                                                        <input type="hidden" name="orientation_id" value="<?php echo htmlspecialchars($orientationId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                                                                                                        <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                                                                                </form>
                                                                                        </div>
                                                                                </td>
                                                                        <?php endif; ?>
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