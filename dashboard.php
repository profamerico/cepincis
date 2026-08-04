<?php
$pageTitle = 'Dashboard | CEPIN-CIS';
$bodyClass = 'app-page dashboard-page';

require_once 'controllers/AuthController.php';
require_once 'models/Orientation.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';

function dashboard_count_distinct(array $items, string $key): int
{
    $seen = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $value = trim((string) ($item[$key] ?? ''));
        if ($value === '') {
            continue;
        }

        $seen[$value] = true;
    }

    return count($seen);
}

$auth = new AuthController();
$auth->requireAuth();

$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);
$orientationManager = new OrientationManager();
$currentUser = $auth->getCurrentUser();
$displayName = (string) ($currentUser['fullname'] ?? $currentUser['username']);
$roleLabel = $auth->getRoleLabel($currentUser);
$isAdmin = $auth->isAdmin($currentUser);
$isAcademic = $auth->isAcademicResearcher($currentUser);
$isAssociate = $auth->isAssociateResearcher($currentUser);
$isFull = $auth->isFullResearcher($currentUser);
$canAccessResearchWorkspace = $auth->canAccessResearchWorkspace($currentUser);
$canManageOrientations = $auth->canManageOrientations($currentUser);
$canCreateProjects = $auth->canCreateProjects($currentUser);
$userStats = $projectManager->getUserStats((int) $currentUser['id']);
$projectStats = $isAdmin ? $projectManager->getProjectStats() : null;
$workspaceProjects = $workspaceManager->getAccessibleProjectsForUser($projectManager->getAllProjects(), $currentUser);
$pendingWorkspaceInvites = $workspaceManager->getUserInvites((int) $currentUser['id']);
$unreadNotifications = $workspaceManager->getUnreadNotificationCount((int) $currentUser['id']);
$users = $isAdmin ? $auth->listUsers() : [];
$workspaceOrientations = [];
$orientationStats = null;
$linkedProjectsCount = 0;
$uniqueResearchersCount = 0;

if ($canAccessResearchWorkspace) {
    if ($canManageOrientations) {
        $workspaceOrientations = $isAdmin
            ? $orientationManager->listOrientations()
            : $orientationManager->getOrientationsForSupervisor((int) $currentUser['id']);
        $orientationStats = $isAdmin
            ? [
                'total' => count($workspaceOrientations),
                'planned' => count(array_filter($workspaceOrientations, static function (array $orientation): bool {
                    return (string) ($orientation['status'] ?? '') === 'planned';
                })),
                'active' => count(array_filter($workspaceOrientations, static function (array $orientation): bool {
                    return (string) ($orientation['status'] ?? '') === 'active';
                })),
                'completed' => count(array_filter($workspaceOrientations, static function (array $orientation): bool {
                    return (string) ($orientation['status'] ?? '') === 'completed';
                })),
            ]
            : $orientationManager->getStatsForSupervisor((int) $currentUser['id']);
    } else {
        $workspaceOrientations = $orientationManager->getOrientationsForResearcher((int) $currentUser['id']);
        $orientationStats = $orientationManager->getStatsForResearcher((int) $currentUser['id']);
    }

    $linkedProjectsCount = dashboard_count_distinct($workspaceOrientations, 'project_id');
    $uniqueResearchersCount = count(array_unique(array_map(static function (array $orientation): int {
        return (int) ($orientation['researcher_id'] ?? 0);
    }, $workspaceOrientations)));
}

$heroCopy = 'Um ponto central para acompanhar sua conta, navegar pelos recursos do portal e administrar a plataforma quando você tiver permissão elevada.';
$heroMeta = [
    'Email: ' . (string) ($currentUser['email'] ?: 'Não informado'),
    'Projetos ativos: ' . (int) $userStats['active'],
    'Projetos concluidos: ' . (int) $userStats['completed'],
];
$actionCards = [
    [
        'href' => 'notifications.php',
        'icon' => 'fa-bell',
        'title' => 'Notificações',
        'copy' => 'Veja convites, autenticações e atualizações de timeline.',
    ],
    [
        'href' => 'project-workspace.php',
        'icon' => 'fa-users-gear',
        'title' => 'Workspaces',
        'copy' => 'Acesse projetos em que você cria, administra ou colabora.',
    ],
    [
        'href' => 'profile.php',
        'icon' => 'fa-id-card',
        'title' => 'Atualizar perfil',
        'copy' => 'Revise nome, email e senha da conta.',
    ],
    [
        'href' => 'settings.php',
        'icon' => 'fa-sliders',
        'title' => 'Preferencias',
        'copy' => 'Consulte ajustes de conta e notificações.',
    ],
];
$statusPanelTitle = 'Visão da sua conta';
$statusPanelItems = [
    ['label' => 'Perfil principal', 'value' => $displayName],
    ['label' => 'Permissão atual', 'value' => $roleLabel],
    ['label' => 'Usuário', 'value' => '@' . (string) $currentUser['username']],
    ['label' => 'Email', 'value' => (string) ($currentUser['email'] ?: 'Não informado')],
    ['label' => 'Notificações não lidas', 'value' => (string) $unreadNotifications],
    ['label' => 'Workspaces acessiveis', 'value' => (string) count($workspaceProjects)],
    ['label' => 'Convites pendentes', 'value' => (string) count($pendingWorkspaceInvites)],
];
$accentEyebrow = 'Conta';
$accentTitle = 'Proximo passo sugerido';
$accentCopy = 'Mantenha seu perfil atualizado para facilitar o contato e a organização do portal. Se precisar de mais acessos, um administrador pode ajustar sua permissão no painel interno.';
$accentAction = ['href' => 'settings.php', 'label' => 'Ver configuracoes'];
$metrics = [];

if ($isAdmin) {
    $heroCopy = 'Você controla a plataforma inteira: usuários, projetos, conteúdo global, parceiros e também os fluxos de pesquisa e orientação.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Não informado'),
        'Usuários cadastrados: ' . count($users),
        'Projetos ativos na plataforma: ' . (int) ($projectStats['active'] ?? 0),
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Orientações',
        'copy' => 'Acompanhe todas as orientações cadastradas no portal.',
    ];
    $actionCards[] = [
        'href' => 'research-projects.php',
        'icon' => 'fa-diagram-project',
        'title' => 'Projetos de pesquisa',
        'copy' => 'Visualize e ajuste os projetos em um fluxo focado no workspace.',
    ];
    $actionCards[] = [
        'href' => 'admin.php',
        'icon' => 'fa-shield-halved',
        'title' => 'Controle administrativo',
        'copy' => 'Usuários, permissões, projetos e conteúdo em um só lugar.',
    ];
    $metrics = [
        ['label' => 'Usuários', 'value' => count($users), 'copy' => 'Contas registradas atualmente no portal.'],
        ['label' => 'Projetos', 'value' => (int) ($projectStats['total'] ?? 0), 'copy' => 'Projetos totais na plataforma.'],
        ['label' => 'Orientações', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Orientações ativas ou históricas do workspace.'],
        ['label' => 'Docs pendentes', 'value' => (int) ($projectStats['authentication_pending'] ?? 0), 'copy' => 'Projetos aguardando aprovação documental.'],
    ];
    $statusPanelTitle = 'Radar administrativo';
    $statusPanelItems[] = ['label' => 'Orientações ativas', 'value' => (string) (int) ($orientationStats['active'] ?? 0)];
    $accentEyebrow = 'Panorama admin';
    $accentTitle = 'Estado da plataforma';
    $accentCopy = 'Os fluxos principais já estão conectados: conteúdo, parceiros, projetos e orientações. O painel mestre segue sendo o ponto de governança mais alto.';
    $accentAction = ['href' => 'admin.php', 'label' => 'Abrir admin'];
} elseif ($isFull) {
    $heroCopy = 'Como Pesquisador Pleno, você pode orientar pesquisadores acadêmicos e cadastrar projetos diretamente no site do CEPIN-CIS.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Não informado'),
        'Projetos criados por você: ' . (int) $userStats['total'],
        'Orientações ativas: ' . (int) ($orientationStats['active'] ?? 0),
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Gerenciar orientações',
        'copy' => 'Organize orientandos, carga horária e status das orientações.',
    ];
    $actionCards[] = [
        'href' => 'research-projects.php',
        'icon' => 'fa-diagram-project',
        'title' => 'Publicar projetos',
        'copy' => 'Crie e atualize projetos que entram diretamente no portal.',
    ];
    $metrics = [
        ['label' => 'Projetos', 'value' => (int) $userStats['total'], 'copy' => 'Projetos atualmente ligados a sua conta.'],
        ['label' => 'Projetos ativos', 'value' => (int) $userStats['active'], 'copy' => 'Projetos em andamento publicados ou em andamento.'],
        ['label' => 'Orientações ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientações em execução com pesquisadores acadêmicos.'],
        ['label' => 'Orientandos', 'value' => $uniqueResearchersCount, 'copy' => 'Pesquisadores acadêmicos atualmente acompanhados por você.'],
    ];
    $statusPanelItems[] = ['label' => 'Projetos vinculados a orientações', 'value' => (string) $linkedProjectsCount];
    $accentEyebrow = 'Pesquisa aplicada';
    $accentTitle = 'Seu espaço de trabalho';
    $accentCopy = 'Use o workspace para manter orientações em dia e publicar novos projetos. Tudo o que você cadastrar aqui já conversa com o portal principal.';
    $accentAction = ['href' => 'research-projects.php', 'label' => 'Abrir projetos'];
} elseif ($isAssociate) {
    $heroCopy = 'Como Pesquisador Associado, você pode orientar pesquisadores acadêmicos e acompanhar os fluxos vinculados a projetos aprovados no ambito do CEPIN-CIS.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Não informado'),
        'Orientações totais: ' . (int) ($orientationStats['total'] ?? 0),
        'Orientandos ativos: ' . $uniqueResearchersCount,
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Gerenciar orientações',
        'copy' => 'Crie, acompanhe e finalize orientações acadêmicas.',
    ];
    $metrics = [
        ['label' => 'Orientações', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Orientações cadastradas sob sua supervisão.'],
        ['label' => 'Ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientações em andamento agora.'],
        ['label' => 'Planejadas', 'value' => (int) ($orientationStats['planned'] ?? 0), 'copy' => 'Orientações aguardando início formal.'],
        ['label' => 'Orientandos', 'value' => $uniqueResearchersCount, 'copy' => 'Pesquisadores acadêmicos atualmente ligados a você.'],
    ];
    $statusPanelItems[] = ['label' => 'Projetos vinculados a orientações', 'value' => (string) $linkedProjectsCount];
    $accentEyebrow = 'Orientação';
    $accentTitle = 'Proximo passo sugerido';
    $accentCopy = 'Seu papel agora e estruturar orientações. Sempre que um pesquisador acadêmico entrar em um projeto, você pode registrar a supervisão por lá.';
    $accentAction = ['href' => 'orientations.php', 'label' => 'Abrir orientações'];
} elseif ($isAcademic) {
    $heroCopy = 'Como Pesquisador Acadêmico, este painel passa a acompanhar suas orientações e a evolução do seu percurso dentro dos projetos ligados ao CEPIN-CIS.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Não informado'),
        'Orientações em andamento: ' . (int) ($orientationStats['active'] ?? 0),
        'Projetos vinculados: ' . $linkedProjectsCount,
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Minhas orientações',
        'copy' => 'Veja suas orientações, supervisores e projetos relacionados.',
    ];
    $metrics = [
        ['label' => 'Orientações', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Orientações atualmente associadas ao seu percurso.'],
        ['label' => 'Ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientações em andamento neste momento.'],
        ['label' => 'Planejadas', 'value' => (int) ($orientationStats['planned'] ?? 0), 'copy' => 'Frentes cadastradas aguardando início.'],
        ['label' => 'Projetos vinculados', 'value' => $linkedProjectsCount, 'copy' => 'Projetos associados as suas orientações.'],
    ];
    $statusPanelItems[] = ['label' => 'Orientações concluídas', 'value' => (string) (int) ($orientationStats['completed'] ?? 0)];
    $accentEyebrow = 'Acompanhamento';
    $accentTitle = 'Proximo passo sugerido';
    $accentCopy = 'Sempre que um pesquisador associado ou pleno registrar uma orientação para você, ela aparecerá aqui. Use esse espaço para acompanhar o andamento e os vínculos com projetos.';
    $accentAction = ['href' => 'orientations.php', 'label' => 'Ver minhas orientações'];
} else {
    $metrics = [
        ['label' => 'Número de projetos', 'value' => (int) $userStats['total'], 'copy' => 'Quantidade total associada ao seu usuário.'],
        ['label' => 'Ativos', 'value' => (int) $userStats['active'], 'copy' => 'Itens em andamento no momento.'],
        ['label' => 'Pendentes', 'value' => (int) $userStats['pending'], 'copy' => 'Demandas aguardando proximo passo.'],
        ['label' => 'Concluidos', 'value' => (int) $userStats['completed'], 'copy' => 'Projetos que já foram finalizados.'],
    ];
}

$actionCards[] = [
    'href' => 'logout.php',
    'icon' => 'fa-right-from-bracket',
    'title' => 'Encerrar sessão',
    'copy' => 'Sair da conta com seguranca quando terminar.',
];
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Painel interno</p>
            <h1><?php echo htmlspecialchars($displayName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>
            <p class="hero-copy"><?php echo htmlspecialchars($heroCopy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="profile.php">Editar perfil</a>
                <?php if ($isAdmin): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php">Abrir admin</a>
                <?php elseif ($canCreateProjects): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="research-projects.php">Projetos de pesquisa</a>
                <?php elseif ($canAccessResearchWorkspace): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="orientations.php">Abrir workspace</a>
                <?php else: ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="settings.php">Ver configuracoes</a>
                <?php endif; ?>
            </div>
        </div>

        <aside class="panel-hero-aside">
            <span class="dashboard-badge"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
            <h2>Resumo da sessão</h2>
            <p>Conta conectada como <strong>@<?php echo htmlspecialchars((string) $currentUser['username'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>.</p>
            <ul class="hero-meta-list">
                <?php foreach ($heroMeta as $metaLine): ?>
                    <li><?php echo htmlspecialchars($metaLine, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </section>

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
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Atalhos</p>
                    <h2>O que você quer fazer agora?</h2>
                </div>
            </div>

            <div class="action-grid">
                <?php foreach ($actionCards as $actionCard): ?>
                    <a class="action-card" href="<?php echo htmlspecialchars((string) $actionCard['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                        <i class="fa-solid <?php echo htmlspecialchars((string) $actionCard['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"></i>
                        <div>
                            <strong><?php echo htmlspecialchars((string) $actionCard['title'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars((string) $actionCard['copy'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </article>

        <div class="stacked-panels">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Status rapido</p>
                        <h2><?php echo htmlspecialchars($statusPanelTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
                    </div>
                </div>

                <ul class="dashboard-list">
                    <?php foreach ($statusPanelItems as $item): ?>
                        <li>
                            <span><?php echo htmlspecialchars((string) $item['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
                            <strong><?php echo htmlspecialchars((string) $item['value'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>

            <article class="panel-card accent-panel">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow"><?php echo htmlspecialchars($accentEyebrow, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                        <h2><?php echo htmlspecialchars($accentTitle, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
                    </div>
                </div>

                <p class="panel-copy"><?php echo htmlspecialchars($accentCopy, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>
                <a class="dashboard-btn dashboard-btn--ghost" href="<?php echo htmlspecialchars((string) $accentAction['href'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $accentAction['label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>
                </a>
            </article>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
