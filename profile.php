<?php
$pageTitle = 'Perfil | CEPIN-CIS';
$bodyClass = 'app-page profile-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';
require_once 'models/UserProfileExtras.php';

$auth = new AuthController();
$auth->requireAuth();

$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);
$profileExtrasManager = new UserProfileExtrasManager();
$user = $auth->getCurrentUser();
$stats = $projectManager->getUserStats((int) $user['id']);
$roleLabel = $auth->getRoleLabel($user);
$profileExtras = $profileExtrasManager->getProfile((int) $user['id']);
$latestRoleRequest = $profileExtrasManager->getLatestRoleRequestForUser((int) $user['id']);
$result = null;
$roleRequestResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? 'update_profile');

    if ($action === 'request_role') {
        $roleRequestResult = $profileExtrasManager->createRoleRequest(
            (int) $user['id'],
            (string) ($_POST['requested_role'] ?? ''),
            (string) ($_POST['request_message'] ?? '')
        );

        if ($roleRequestResult['success']) {
            $workspaceManager->notifyAdministrators(
                $auth->listUsers(),
                'role_request',
                'Solicitacao de aumento de nivel',
                (string) ($user['fullname'] ?? $user['username']) . ' solicitou nivel de ' . $profileExtrasManager->getRoleRequestLabel((string) ($_POST['requested_role'] ?? '')) . '.',
                null,
                'admin.php#role-requests',
                (int) $user['id']
            );

            $latestRoleRequest = $roleRequestResult['request'];
        }
    } else {
        $result = $auth->updateProfile([
            'fullname' => $_POST['fullname'] ?? '',
            'email' => $_POST['email'] ?? '',
            'password' => $_POST['password'] ?? '',
            'password_confirm' => $_POST['password_confirm'] ?? '',
        ]);

        if ($result['success']) {
            $extrasResult = $profileExtrasManager->saveProfile(
                (int) $user['id'],
                [
                    'bio' => $_POST['bio'] ?? '',
                    'linkedin_url' => $_POST['linkedin_url'] ?? '',
                    'integra_ifsp_url' => $_POST['integra_ifsp_url'] ?? '',
                    'lattes_url' => $_POST['lattes_url'] ?? '',
                ],
                isset($_FILES['photo_file']) && is_array($_FILES['photo_file']) ? $_FILES['photo_file'] : null
            );

            if (!$extrasResult['success']) {
                $result = $extrasResult;
            } else {
                $user = $auth->getCurrentUser();
                $profileExtras = $extrasResult['profile'];
            }
        }
    }
}

$profilePhotoPath = trim((string) ($profileExtras['photo_path'] ?? ''));
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Perfil</p>
            <h1><?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="hero-copy">Atualize seus dados principais e mantenha a conta pronta para contato, acompanhamento e administracao do portal.</p>
        </div>

        <aside class="panel-hero-aside profile-summary-card">
            <?php if ($profilePhotoPath !== ''): ?>
                <img class="profile-photo" src="<?php echo htmlspecialchars($profilePhotoPath, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?>">
            <?php else: ?>
                <span class="profile-initial"><?php echo htmlspecialchars(strtoupper(substr((string) $user['username'], 0, 1)), ENT_QUOTES, 'UTF-8'); ?></span>
            <?php endif; ?>
            <h2>@<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?></h2>
            <p>Membro desde <?php echo htmlspecialchars(date('d/m/Y', strtotime((string) ($user['created_at'] ?? 'now'))), ENT_QUOTES, 'UTF-8'); ?></p>
        </aside>
    </section>

    <section class="profile-cta-panel panel-card">
        <div>
            <p class="eyebrow">Acesso rapido</p>
            <h2>Projetos, workspaces e evolucao de nivel</h2>
            <p class="panel-copy">Entre direto nas areas de pesquisa ou solicite uma permissao maior para participar do fluxo academico.</p>
        </div>

        <div class="profile-cta-actions">
            <a class="dashboard-btn profile-cta-primary" href="research-projects.php">Projetos</a>
            <a class="dashboard-btn profile-cta-primary" href="project-workspace.php">Workspaces</a>
            <form method="POST" class="profile-role-request-form">
                <input type="hidden" name="action" value="request_role">
                <input type="hidden" name="requested_role" value="academic_researcher">
                <button class="dashboard-btn dashboard-btn--ghost" type="submit">Solicitar Academico</button>
            </form>
            <form method="POST" class="profile-role-request-form">
                <input type="hidden" name="action" value="request_role">
                <input type="hidden" name="requested_role" value="full_researcher">
                <button class="dashboard-btn dashboard-btn--ghost" type="submit">Solicitar Pleno</button>
            </form>
        </div>

        <?php if ($latestRoleRequest && (string) ($latestRoleRequest['status'] ?? '') === 'pending'): ?>
            <p class="form-help">Solicitacao pendente: <?php echo htmlspecialchars($profileExtrasManager->getRoleRequestLabel((string) ($latestRoleRequest['requested_role'] ?? '')), ENT_QUOTES, 'UTF-8'); ?>.</p>
        <?php endif; ?>

        <?php if ($roleRequestResult && !$roleRequestResult['success']): ?>
            <?php foreach ($roleRequestResult['errors'] as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>
        <?php elseif ($roleRequestResult && $roleRequestResult['success']): ?>
            <div class="mensagem sucesso">Solicitacao enviada para os administradores.</div>
        <?php endif; ?>
    </section>

    <section class="metrics-grid">
        <article class="metric-card">
            <span class="metric-label">Projetos</span>
            <strong class="metric-value"><?php echo (int) $stats['total']; ?></strong>
            <p>Total atualmente ligado ao seu usuario.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Ativos</span>
            <strong class="metric-value"><?php echo (int) $stats['active']; ?></strong>
            <p>Itens em andamento.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Pendentes</span>
            <strong class="metric-value"><?php echo (int) $stats['pending']; ?></strong>
            <p>Demandas aguardando andamento.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Concluidos</span>
            <strong class="metric-value"><?php echo (int) $stats['completed']; ?></strong>
            <p>Projetos finalizados.</p>
        </article>
    </section>

    <section class="dashboard-layout">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Dados da conta</p>
                    <h2>Informacoes pessoais</h2>
                </div>
            </div>

            <?php if ($result && !$result['success']): ?>
                <?php foreach ($result['errors'] as $error): ?>
                    <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
                <?php endforeach; ?>
            <?php elseif ($result && $result['success']): ?>
                <div class="mensagem sucesso">Perfil atualizado com sucesso.</div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="stack-form">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input
                        type="text"
                        id="username"
                        value="<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?>"
                        disabled
                    >
                </div>

                <div class="form-group">
                    <label for="fullname">Nome completo</label>
                    <input
                        type="text"
                        id="fullname"
                        name="fullname"
                        value="<?php echo htmlspecialchars((string) ($user['fullname'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="photo_file">Foto de perfil</label>
                    <input type="file" id="photo_file" name="photo_file" accept="image/png,image/jpeg,image/webp">
                    <p class="form-help">Envie JPG, PNG ou WEBP com ate 3 MB.</p>
                </div>

                <div class="form-group">
                    <label for="bio">Biografia</label>
                    <textarea id="bio" name="bio" rows="6" placeholder="Conte um pouco sobre sua trajetoria, area de pesquisa e interesses."><?php echo htmlspecialchars((string) ($profileExtras['bio'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="linkedin_url">LinkedIn</label>
                        <input type="url" id="linkedin_url" name="linkedin_url" value="<?php echo htmlspecialchars((string) ($profileExtras['linkedin_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://linkedin.com/in/...">
                    </div>

                    <div class="form-group">
                        <label for="integra_ifsp_url">Integra IFSP</label>
                        <input type="url" id="integra_ifsp_url" name="integra_ifsp_url" value="<?php echo htmlspecialchars((string) ($profileExtras['integra_ifsp_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="https://integra.ifsp.edu.br/...">
                    </div>
                </div>

                <div class="form-group">
                    <label for="lattes_url">Lattes CNPq</label>
                    <input type="url" id="lattes_url" name="lattes_url" value="<?php echo htmlspecialchars((string) ($profileExtras['lattes_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" placeholder="http://lattes.cnpq.br/...">
                </div>

                <div class="form-group">
                    <label for="password">Nova senha (opcional)</label>
                    <input type="password" id="password" name="password">
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar nova senha</label>
                    <input type="password" id="password_confirm" name="password_confirm">
                </div>

                <button type="submit" class="dashboard-btn">Salvar alteracoes</button>
            </form>
        </article>

        <div class="stacked-panels">
            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Conta</p>
                        <h2>Resumo rapido</h2>
                    </div>
                </div>

                <ul class="dashboard-list">
                    <li>
                        <span>Nome exibido</span>
                        <strong><?php echo htmlspecialchars((string) ($user['fullname'] ?? $user['username']), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Email</span>
                        <strong><?php echo htmlspecialchars((string) ($user['email'] ?: 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Usuario</span>
                        <strong>@<?php echo htmlspecialchars((string) $user['username'], ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                    <li>
                        <span>Nivel atual</span>
                        <strong><?php echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                    </li>
                </ul>
            </article>

            <article class="panel-card">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Links publicos</p>
                        <h2>Perfis academicos</h2>
                    </div>
                </div>

                <div class="profile-public-links">
                    <?php if ((string) ($profileExtras['linkedin_url'] ?? '') !== ''): ?>
                        <a href="<?php echo htmlspecialchars((string) $profileExtras['linkedin_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fa-brands fa-linkedin-in"></i><span>LinkedIn</span></a>
                    <?php endif; ?>
                    <?php if ((string) ($profileExtras['integra_ifsp_url'] ?? '') !== ''): ?>
                        <a href="<?php echo htmlspecialchars((string) $profileExtras['integra_ifsp_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-building-columns"></i><span>Integra IFSP</span></a>
                    <?php endif; ?>
                    <?php if ((string) ($profileExtras['lattes_url'] ?? '') !== ''): ?>
                        <a href="<?php echo htmlspecialchars((string) $profileExtras['lattes_url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener"><i class="fa-solid fa-graduation-cap"></i><span>Lattes CNPq</span></a>
                    <?php endif; ?>
                    <?php if (
                        (string) ($profileExtras['linkedin_url'] ?? '') === ''
                        && (string) ($profileExtras['integra_ifsp_url'] ?? '') === ''
                        && (string) ($profileExtras['lattes_url'] ?? '') === ''
                    ): ?>
                        <p class="admin-empty">Nenhum link publico cadastrado ainda.</p>
                    <?php endif; ?>
                </div>
            </article>

            <article class="panel-card accent-panel">
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Atalho</p>
                        <h2>Voltar ao painel</h2>
                    </div>
                </div>

                <p class="panel-copy">Se voce terminou as alteracoes, pode voltar para o dashboard principal e seguir para configuracoes ou administracao.</p>
                <a class="dashboard-btn dashboard-btn--ghost" href="dashboard.php">Ir para o dashboard</a>
            </article>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
