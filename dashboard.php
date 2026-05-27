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

$heroCopy = 'Um ponto central para acompanhar sua conta, navegar pelos recursos do portal e administrar a plataforma quando voce tiver permissao elevada.';
$heroMeta = [
    'Email: ' . (string) ($currentUser['email'] ?: 'Nao informado'),
    'Projetos ativos: ' . (int) $userStats['active'],
    'Projetos concluidos: ' . (int) $userStats['completed'],
];
$actionCards = [
    [
        'href' => 'notifications.php',
        'icon' => 'fa-bell',
        'title' => 'Notificacoes',
        'copy' => 'Veja convites, autenticacoes e atualizacoes de timeline.',
    ],
    [
        'href' => 'project-workspace.php',
        'icon' => 'fa-users-gear',
        'title' => 'Workspaces',
        'copy' => 'Acesse projetos em que voce cria, administra ou colabora.',
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
        'copy' => 'Consulte ajustes de conta e notificacoes.',
    ],
];
$statusPanelTitle = 'Visao da sua conta';
$statusPanelItems = [
    ['label' => 'Perfil principal', 'value' => $displayName],
    ['label' => 'Permissao atual', 'value' => $roleLabel],
    ['label' => 'Usuario', 'value' => '@' . (string) $currentUser['username']],
    ['label' => 'Email', 'value' => (string) ($currentUser['email'] ?: 'Nao informado')],
    ['label' => 'Notificacoes nao lidas', 'value' => (string) $unreadNotifications],
    ['label' => 'Workspaces acessiveis', 'value' => (string) count($workspaceProjects)],
    ['label' => 'Convites pendentes', 'value' => (string) count($pendingWorkspaceInvites)],
];
$accentEyebrow = 'Conta';
$accentTitle = 'Proximo passo sugerido';
$accentCopy = 'Mantenha seu perfil atualizado para facilitar o contato e a organizacao do portal. Se precisar de mais acessos, um administrador pode ajustar sua permissao no painel interno.';
$accentAction = ['href' => 'settings.php', 'label' => 'Ver configuracoes'];
$metrics = [];

if ($isAdmin) {
    $heroCopy = 'Voce controla a plataforma inteira: usuarios, projetos, conteudo global, parceiros e tambem os fluxos de pesquisa e orientacao.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Nao informado'),
        'Usuarios cadastrados: ' . count($users),
        'Projetos ativos na plataforma: ' . (int) ($projectStats['active'] ?? 0),
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Orientacoes',
        'copy' => 'Acompanhe todas as orientacoes cadastradas no portal.',
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
        'copy' => 'Usuarios, permissoes, projetos e conteudo em um so lugar.',
    ];
    $metrics = [
        ['label' => 'Usuarios', 'value' => count($users), 'copy' => 'Contas registradas atualmente no portal.'],
        ['label' => 'Projetos', 'value' => (int) ($projectStats['total'] ?? 0), 'copy' => 'Projetos totais na plataforma.'],
        ['label' => 'Orientacoes', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Orientacoes ativas ou historicas do workspace.'],
        ['label' => 'Docs pendentes', 'value' => (int) ($projectStats['authentication_pending'] ?? 0), 'copy' => 'Projetos aguardando aprovacao documental.'],
    ];
    $statusPanelTitle = 'Radar administrativo';
    $statusPanelItems[] = ['label' => 'Orientacoes ativas', 'value' => (string) (int) ($orientationStats['active'] ?? 0)];
    $accentEyebrow = 'Panorama admin';
    $accentTitle = 'Estado da plataforma';
    $accentCopy = 'Os fluxos principais ja estao conectados: conteudo, parceiros, projetos e orientacoes. O painel mestre segue sendo o ponto de governanca mais alto.';
    $accentAction = ['href' => 'admin.php', 'label' => 'Abrir admin'];
} elseif ($isFull) {
    $heroCopy = 'Como Pesquisador Pleno, voce pode orientar pesquisadores academicos e cadastrar projetos diretamente no site do CEPIN-CIS.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Nao informado'),
        'Projetos criados por voce: ' . (int) $userStats['total'],
        'Orientacoes ativas: ' . (int) ($orientationStats['active'] ?? 0),
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Gerenciar orientacoes',
        'copy' => 'Organize orientandos, carga horaria e status das orientacoes.',
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
        ['label' => 'Orientacoes ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientacoes em execucao com pesquisadores academicos.'],
        ['label' => 'Orientandos', 'value' => $uniqueResearchersCount, 'copy' => 'Pesquisadores academicos atualmente acompanhados por voce.'],
    ];
    $statusPanelItems[] = ['label' => 'Projetos vinculados a orientacoes', 'value' => (string) $linkedProjectsCount];
    $accentEyebrow = 'Pesquisa aplicada';
    $accentTitle = 'Seu espaco de trabalho';
    $accentCopy = 'Use o workspace para manter orientacoes em dia e publicar novos projetos. Tudo o que voce cadastrar aqui ja conversa com o portal principal.';
    $accentAction = ['href' => 'research-projects.php', 'label' => 'Abrir projetos'];
} elseif ($isAssociate) {
    $heroCopy = 'Como Pesquisador Associado, voce pode orientar pesquisadores academicos e acompanhar os fluxos vinculados a projetos aprovados no ambito do CEPIN-CIS.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Nao informado'),
        'Orientacoes totais: ' . (int) ($orientationStats['total'] ?? 0),
        'Orientandos ativos: ' . $uniqueResearchersCount,
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Gerenciar orientacoes',
        'copy' => 'Crie, acompanhe e finalize orientacoes academicas.',
    ];
    $metrics = [
        ['label' => 'Orientacoes', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Orientacoes cadastradas sob sua supervisao.'],
        ['label' => 'Ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientacoes em andamento agora.'],
        ['label' => 'Planejadas', 'value' => (int) ($orientationStats['planned'] ?? 0), 'copy' => 'Orientacoes aguardando inicio formal.'],
        ['label' => 'Orientandos', 'value' => $uniqueResearchersCount, 'copy' => 'Pesquisadores academicos atualmente ligados a voce.'],
    ];
    $statusPanelItems[] = ['label' => 'Projetos vinculados a orientacoes', 'value' => (string) $linkedProjectsCount];
    $accentEyebrow = 'Orientacao';
    $accentTitle = 'Proximo passo sugerido';
    $accentCopy = 'Seu papel agora e estruturar orientacoes. Sempre que um pesquisador academico entrar em um projeto, voce pode registrar a supervisao por la.';
    $accentAction = ['href' => 'orientations.php', 'label' => 'Abrir orientacoes'];
} elseif ($isAcademic) {
    $heroCopy = 'Como Pesquisador Academico, este painel passa a acompanhar suas orientacoes e a evolucao do seu percurso dentro dos projetos ligados ao CEPIN-CIS.';
    $heroMeta = [
        'Email: ' . (string) ($currentUser['email'] ?: 'Nao informado'),
        'Orientacoes em andamento: ' . (int) ($orientationStats['active'] ?? 0),
        'Projetos vinculados: ' . $linkedProjectsCount,
    ];
    $actionCards[] = [
        'href' => 'orientations.php',
        'icon' => 'fa-user-graduate',
        'title' => 'Minhas orientacoes',
        'copy' => 'Veja suas orientacoes, supervisores e projetos relacionados.',
    ];
    $metrics = [
        ['label' => 'Orientacoes', 'value' => (int) ($orientationStats['total'] ?? 0), 'copy' => 'Orientacoes atualmente associadas ao seu percurso.'],
        ['label' => 'Ativas', 'value' => (int) ($orientationStats['active'] ?? 0), 'copy' => 'Orientacoes em andamento neste momento.'],
        ['label' => 'Planejadas', 'value' => (int) ($orientationStats['planned'] ?? 0), 'copy' => 'Frentes cadastradas aguardando inicio.'],
        ['label' => 'Projetos vinculados', 'value' => $linkedProjectsCount, 'copy' => 'Projetos associados as suas orientacoes.'],
    ];
    $statusPanelItems[] = ['label' => 'Orientacoes concluidas', 'value' => (string) (int) ($orientationStats['completed'] ?? 0)];
    $accentEyebrow = 'Acompanhamento';
    $accentTitle = 'Proximo passo sugerido';
    $accentCopy = 'Sempre que um pesquisador associado ou pleno registrar uma orientacao para voce, ela aparecera aqui. Use esse espaco para acompanhar o andamento e os vinculos com projetos.';
    $accentAction = ['href' => 'orientations.php', 'label' => 'Ver minhas orientacoes'];
} else {
    $metrics = [
        ['label' => 'Numero de projetos', 'value' => (int) $userStats['total'], 'copy' => 'Quantidade total associada ao seu usuario.'],
        ['label' => 'Ativos', 'value' => (int) $userStats['active'], 'copy' => 'Itens em andamento no momento.'],
        ['label' => 'Pendentes', 'value' => (int) $userStats['pending'], 'copy' => 'Demandas aguardando proximo passo.'],
        ['label' => 'Concluidos', 'value' => (int) $userStats['completed'], 'copy' => 'Projetos que ja foram finalizados.'],
    ];
}

$actionCards[] = [
    'href' => 'logout.php',
    'icon' => 'fa-right-from-bracket',
    'title' => 'Encerrar sessao',
    'copy' => 'Sair da conta com seguranca quando terminar.',
];
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Painel interno</p>
            <h1><?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-copy"><?php echo htmlspecialchars($heroCopy, ENT_QUOTES, 'UTF-8'); ?></p>

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
            <span class="dashboard-badge"><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></span>
            <h2>Resumo da sessao</h2>
            <p>Conta conectada como <strong>@<?php echo htmlspecialchars((string) $currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></strong>.</p>
            <ul class="hero-meta-list">
                <?php foreach ($heroMeta as $metaLine): ?>
                    <li><?php echo htmlspecialchars($metaLine, ENT_QUOTES, 'UTF-8'); ?></li>
                <?php endforeach; ?>
            </ul>
        </aside>
    </section>

    <section class="metrics-grid">
        <?php foreach ($metrics as $metric): ?>
            <article class="metric-card">
                <span class="metric-label"><?php echo htmlspecialchars((string) $metric['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                <strong class="metric-value"><?php echo htmlspecialchars((string) $metric['value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                <p><?php echo htmlspecialchars((string) $metric['copy'], ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="dashboard-layout">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Atalhos</p>
                    <h2>O que voce quer fazer agora?</h2>
                </div>
            </div>

            <div class="action-grid">
                <?php foreach ($actionCards as $actionCard): ?>
                    <a class="action-card" href="<?php echo htmlspecialchars((string) $actionCard['href'], ENT_QUOTES, 'UTF-8'); ?>">
                        <i class="fa-solid <?php echo htmlspecialchars((string) $actionCard['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i>
                        <div>
                            <strong><?php echo htmlspecialchars((string) $actionCard['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span><?php echo htmlspecialchars((string) $actionCard['copy'], ENT_QUOTES, 'UTF-8'); ?></span>
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
                        <h2><?php echo htmlspecialchars($statusPanelTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>

                <ul class="dashboard-list">
                    <?php foreach ($statusPanelItems as $item): ?>
                        <li>
                            <span><?php echo htmlspecialchars((string) $item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong><?php echo htmlspecialchars((string) $item['value'], ENT_QUOTES, 'UTF-8'); ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </article>

            <article class="panel-card accent-panel">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow"><?php echo htmlspecialchars($accentEyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
                        <h2><?php echo htmlspecialchars($accentTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                    </div>
                </div>

                <p class="panel-copy"><?php echo htmlspecialchars($accentCopy, ENT_QUOTES, 'UTF-8'); ?></p>
                <a class="dashboard-btn dashboard-btn--ghost" href="<?php echo htmlspecialchars((string) $accentAction['href'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $accentAction['label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </article>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
