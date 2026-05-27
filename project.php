<?php
$bodyClass = 'public-page project-detail-page';

require_once 'controllers/AuthController.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';

function project_detail_status_key(?string $status): string
{
    $status = strtolower(trim((string) $status));

    if (in_array($status, ['active', 'completed', 'pending'], true)) {
        return $status;
    }

    return 'active';
}

function project_detail_status_label(?string $status): string
{
    switch (project_detail_status_key($status)) {
        case 'completed':
            return 'Concluido';
        case 'pending':
            return 'Pendente';
        default:
            return 'Ativo';
    }
}

function project_detail_format_datetime(?string $value): string
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

function project_detail_render_body(string $text): string
{
    $text = trim($text);

    if ($text === '') {
        return '';
    }

    $paragraphs = preg_split('/\r\n\r\n|\n\n|\r\r/', $text) ?: [$text];
    $markup = [];

    foreach ($paragraphs as $paragraph) {
        $paragraph = trim((string) $paragraph);
        if ($paragraph === '') {
            continue;
        }

        $markup[] = '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
    }

    return implode(PHP_EOL, $markup);
}

function project_detail_excerpt(string $text, int $limit = 220): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '') {
        return 'Projeto cadastrado no portal do CEPIN-CIS.';
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

function project_detail_owner_name(?array $owner): string
{
    return trim((string) ($owner['fullname'] ?? $owner['username'] ?? 'Equipe CEPIN-CIS')) ?: 'Equipe CEPIN-CIS';
}

function project_detail_build_participation_text(array $project, ?array $owner): string
{
    $ownerName = project_detail_owner_name($owner);
    $projectTitle = trim((string) ($project['title'] ?? 'este projeto'));
    $category = trim((string) ($project['category'] ?? ''));

    $lines = [
        'Escreva para ' . $ownerName . ' e para o CEPIN-CIS apresentando seu interesse em participar de ' . $projectTitle . '.',
        'No email, vale informar sua vinculacao academica ou profissional, disponibilidade, experiencia anterior e de que forma voce imagina contribuir com o projeto.',
    ];

    if ($category !== '') {
        $lines[] = 'Como este projeto esta ligado a ' . $category . ', mencionar afinidade com essa area tematica ajuda a equipe a encaminhar melhor a conversa.';
    }

    return implode("\n\n", $lines);
}

function project_detail_build_mailto(array $project, ?array $owner, string $cepinEmail): string
{
    $ownerEmail = trim((string) ($owner['email'] ?? ''));
    $projectTitle = trim((string) ($project['title'] ?? 'Projeto CEPIN-CIS'));
    $category = trim((string) ($project['category'] ?? ''));

    $subject = 'Interesse em participar do projeto: ' . $projectTitle;
    $bodyLines = [
        'Ola,',
        '',
        'tenho interesse em participar do projeto "' . $projectTitle . '".',
        '',
        'Meu nome e:',
        'Minha vinculacao / area de atuacao:',
        'Minha disponibilidade estimada:',
        'Como acredito que posso contribuir:',
    ];

    if ($category !== '') {
        $bodyLines[] = 'Area tematica de maior afinidade: ' . $category;
    }

    $bodyLines[] = '';
    $bodyLines[] = 'Agradeco e fico a disposicao.';

    $query = [
        'subject' => $subject,
        'body' => implode("\n", $bodyLines),
    ];

    if ($ownerEmail !== '' && strcasecmp($ownerEmail, $cepinEmail) !== 0) {
        $query['cc'] = $cepinEmail;
        return 'mailto:' . $ownerEmail . '?' . http_build_query($query);
    }

    return 'mailto:' . $cepinEmail . '?' . http_build_query($query);
}

function project_detail_resolve_banner(array $project): array
{
    $configuredPath = trim((string) ($project['image_path'] ?? ''));
    if ($configuredPath !== '') {
        return [
            'path' => $configuredPath,
            'is_fallback' => false,
        ];
    }

    $fallbackPaths = [
        './img/AreasTematicasBG.jpg',
        './img/RegulamentoBG.jpg',
        './img/ProjetosBG.jpg',
    ];

    $seed = implode('|', [
        (string) ($project['id'] ?? ''),
        (string) ($project['category'] ?? ''),
        (string) ($project['title'] ?? ''),
    ]);
    $hash = sprintf('%u', crc32($seed !== '' ? $seed : 'cepin-cis-project'));
    $index = (int) $hash % count($fallbackPaths);

    return [
        'path' => $fallbackPaths[$index],
        'is_fallback' => true,
    ];
}

$cepinEmail = 'cepin.cis@ifspcaraguatatuba.edu.br';
$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);
$auth = new AuthController();
$projectId = trim((string) ($_GET['id'] ?? ''));
$project = $projectId !== '' ? $projectManager->getProject($projectId) : false;

$pageTitle = is_array($project) && trim((string) ($project['title'] ?? '')) !== ''
    ? trim((string) $project['title']) . ' | CEPIN-CIS'
    : 'Projeto | CEPIN-CIS';

include_once 'includes/header.php';
?>

<div class="ball"></div>

<?php if (!is_array($project)): ?>
    <main class="page-shell public-shell project-detail-shell">
        <section class="panel-card public-copy-card public-copy-card--featured project-detail-empty">
            <p class="eyebrow">Projeto</p>
            <h1>Projeto nao encontrado</h1>
            <p>Esse link pode ter expirado ou o projeto pode ter sido removido da base publica.</p>
            <div class="hero-actions">
                <a class="dashboard-btn" href="./index.php#projetos">Voltar aos projetos</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="./contact.php">Entrar em contato</a>
            </div>
        </section>
    </main>
    <?php include_once 'includes/footer.php'; ?>
    <?php return; ?>
<?php endif; ?>

<?php
$owner = isset($project['user_id']) && $project['user_id'] !== null
    ? $auth->getUserById((int) $project['user_id'])
    : null;
$currentUser = $auth->getCurrentUser();
$ownerName = project_detail_owner_name($owner);
$ownerEmail = trim((string) ($owner['email'] ?? ''));
$ownerRole = $owner !== null ? $auth->getRoleLabel($owner) : 'Equipe CEPIN-CIS';
$statusKey = project_detail_status_key((string) ($project['status'] ?? 'active'));
$statusLabel = project_detail_status_label((string) ($project['status'] ?? 'active'));
$projectTags = $projectManager->getProjectTagList($project, false);
$projectDescription = trim((string) ($project['description'] ?? ''));
$participationInfo = trim((string) ($project['participation_info'] ?? ''));
$banner = project_detail_resolve_banner($project);
$bannerPath = (string) ($banner['path'] ?? '');
$isFallbackBanner = !empty($banner['is_fallback']);
$authentication = $workspaceManager->getAuthenticationStatus($project);
$timelineEvents = $workspaceManager->getProjectTimeline((string) ($project['id'] ?? ''));
$collaborators = $workspaceManager->getProjectCollaborators((string) ($project['id'] ?? ''));
$canOpenWorkspace = $workspaceManager->canViewWorkspace($project, $currentUser);
$participationMailto = project_detail_build_mailto($project, $owner, $cepinEmail);
$displayParticipationText = $participationInfo !== ''
    ? $participationInfo
    : project_detail_build_participation_text($project, $owner);
?>

<main class="page-shell public-shell project-detail-shell">
    <section class="panel-card project-detail-hero<?php echo $bannerPath !== '' ? ' project-detail-hero--with-media' : ''; ?><?php echo $isFallbackBanner ? ' project-detail-hero--fallback' : ''; ?>">
        <?php if ($bannerPath !== ''): ?>
            <img
                class="project-detail-hero__background"
                src="<?php echo htmlspecialchars($bannerPath, ENT_QUOTES, 'UTF-8'); ?>"
                alt=""
                aria-hidden="true"
            >
        <?php endif; ?>

        <div class="project-detail-hero__veil" aria-hidden="true"></div>

        <div class="project-detail-hero__grid">
            <div class="project-detail-hero__content">
                <p class="eyebrow">Projeto CEPIN-CIS</p>

                <div class="project-detail-badge-row">
                    <span class="project-detail-badge"><?php echo htmlspecialchars((string) ($project['category'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></span>
                    <span class="project-detail-badge project-detail-badge--status project-detail-badge--<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <span class="project-detail-badge project-detail-badge--auth project-detail-badge--<?php echo htmlspecialchars((string) ($authentication['status'] ?? 'missing'), ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string) ($authentication['label'] ?? 'Sem documentacao'), ENT_QUOTES, 'UTF-8'); ?>
                    </span>
                    <?php foreach ($projectTags as $tag): ?>
                        <span class="project-detail-badge project-detail-badge--tag"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                    <?php endforeach; ?>
                </div>

                <h1><?php echo htmlspecialchars((string) ($project['title'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></h1>
                <p class="project-detail-hero__lede"><?php echo htmlspecialchars(project_detail_excerpt($projectDescription), ENT_QUOTES, 'UTF-8'); ?></p>

                <?php if ($isFallbackBanner): ?>
                    <p class="project-detail-hero__note">Este projeto ainda nao recebeu um banner proprio, entao o portal esta usando um dos fundos editoriais do CEPIN-CIS como capa temporaria.</p>
                <?php endif; ?>

                <div class="hero-actions">
                    <a class="dashboard-btn" href="<?php echo htmlspecialchars($participationMailto, ENT_QUOTES, 'UTF-8'); ?>">Quero participar</a>
                    <?php if ($canOpenWorkspace): ?>
                        <a class="dashboard-btn dashboard-btn--ghost" href="project-workspace.php?id=<?php echo urlencode((string) ($project['id'] ?? '')); ?>">Abrir workspace</a>
                    <?php endif; ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="./index.php#projetos">Voltar aos projetos</a>
                </div>
            </div>

            <aside class="project-detail-hero__aside">
                <p class="project-detail-kicker">Contato de entrada</p>
                <h2>Como esse convite funciona</h2>
                <p>Ao clicar no CTA, seu cliente de email sera aberto com mensagem pronta para falar com o responsavel pelo projeto e com o CEPIN-CIS.</p>

                <div class="project-detail-contact-stack">
                    <div class="project-detail-contact-item">
                        <span>Responsavel</span>
                        <strong><?php echo htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                        <small><?php echo htmlspecialchars($ownerRole, ENT_QUOTES, 'UTF-8'); ?></small>
                    </div>

                    <div class="project-detail-contact-item">
                        <span>Email do responsavel</span>
                        <?php if ($ownerEmail !== ''): ?>
                            <a href="mailto:<?php echo htmlspecialchars($ownerEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($ownerEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                        <?php else: ?>
                            <strong>Contato centralizado pelo CEPIN-CIS</strong>
                        <?php endif; ?>
                    </div>

                    <div class="project-detail-contact-item">
                        <span>Email do CEPIN-CIS</span>
                        <a href="mailto:<?php echo htmlspecialchars($cepinEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cepinEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <section class="project-detail-grid">
        <article class="panel-card public-copy-card project-detail-section-card">
            <p class="eyebrow">Visao geral</p>
            <h2>Sobre o projeto</h2>
            <?php
            echo project_detail_render_body(
                $projectDescription !== ''
                    ? $projectDescription
                    : 'Os detalhes completos deste projeto ainda estao sendo publicados pela equipe responsavel.'
            );
            ?>
        </article>

        <aside class="panel-card project-detail-sidebar">
            <p class="eyebrow">Ficha rapida</p>
            <h2>Resumo do projeto</h2>

            <dl class="project-detail-facts">
                <div>
                    <dt>Area tematica</dt>
                    <dd><?php echo htmlspecialchars((string) ($project['category'] ?? 'Projeto'), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Status</dt>
                    <dd><?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Autenticacao</dt>
                    <dd><?php echo htmlspecialchars((string) ($authentication['label'] ?? 'Sem documentacao'), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Responsavel</dt>
                    <dd><?php echo htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Criado em</dt>
                    <dd><?php echo htmlspecialchars(project_detail_format_datetime($project['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
                <div>
                    <dt>Ultima atualizacao</dt>
                    <dd><?php echo htmlspecialchars(project_detail_format_datetime($project['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></dd>
                </div>
            </dl>

            <div class="project-detail-sidebar__actions">
                <a class="dashboard-btn" href="<?php echo htmlspecialchars($participationMailto, ENT_QUOTES, 'UTF-8'); ?>">Enviar manifestacao de interesse</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="mailto:<?php echo htmlspecialchars($cepinEmail, ENT_QUOTES, 'UTF-8'); ?>">Falar com o CEPIN-CIS</a>
            </div>
        </aside>
    </section>

    <section class="project-detail-grid project-detail-grid--secondary">
        <article class="panel-card public-section-card project-detail-section-card">
            <p class="eyebrow">Timeline</p>
            <h2>Evolucao do projeto</h2>
            <?php if (empty($timelineEvents)): ?>
                <p>A equipe ainda nao publicou eventos na timeline deste projeto.</p>
            <?php else: ?>
                <div class="project-timeline project-timeline--public">
                    <?php foreach ($timelineEvents as $event): ?>
                        <article class="project-timeline-item project-timeline-item--<?php echo htmlspecialchars((string) ($event['event_type'] ?? 'update'), ENT_QUOTES, 'UTF-8'); ?>">
                            <span class="project-timeline-date"><?php echo htmlspecialchars(project_detail_format_datetime($event['event_date'] ?? null), ENT_QUOTES, 'UTF-8'); ?></span>
                            <div class="project-timeline-card">
                                <div class="project-timeline-card__header">
                                    <span><?php echo htmlspecialchars($workspaceManager->getTimelineTypeLabel((string) ($event['event_type'] ?? 'update')), ENT_QUOTES, 'UTF-8'); ?></span>
                                </div>
                                <h3><?php echo htmlspecialchars((string) ($event['title'] ?? 'Atualizacao'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php echo project_detail_render_body((string) ($event['description'] ?? '')); ?>
                                <?php if (!empty($event['attachment_path'])): ?>
                                    <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project-file.php?kind=timeline&id=<?php echo urlencode((string) ($event['id'] ?? '')); ?>">Baixar anexo</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="panel-card public-section-card project-detail-section-card">
            <p class="eyebrow">Equipe</p>
            <h2>Colaboradores</h2>
            <div class="project-detail-contact-stack project-detail-contact-stack--compact">
                <div class="project-detail-contact-item">
                    <span>Responsavel</span>
                    <strong><?php echo htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>
                <?php foreach ($collaborators as $collaborator): ?>
                    <?php $collaboratorUser = $auth->getUserById((int) ($collaborator['user_id'] ?? 0)); ?>
                    <div class="project-detail-contact-item">
                        <span><?php echo (string) ($collaborator['role'] ?? '') === 'project_admin' ? 'Admin do projeto' : 'Colaborador'; ?></span>
                        <strong><?php echo htmlspecialchars(project_detail_owner_name($collaboratorUser), ENT_QUOTES, 'UTF-8'); ?></strong>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($collaborators)): ?>
                    <div class="project-detail-contact-item">
                        <span>Colaboradores</span>
                        <strong>Equipe em formacao</strong>
                    </div>
                <?php endif; ?>
            </div>
        </article>
    </section>

    <section class="project-detail-grid project-detail-grid--secondary">
        <article class="panel-card public-section-card project-detail-section-card">
            <p class="eyebrow">Participacao</p>
            <h2>Como participar</h2>
            <?php echo project_detail_render_body($displayParticipationText); ?>
        </article>

        <article class="panel-card public-section-card project-detail-section-card">
            <p class="eyebrow">Passo a passo</p>
            <h2>O que enviar no primeiro contato</h2>

            <ol class="project-detail-steps">
                <li>Apresente seu nome, vinculacao academica ou profissional e como conheceu o projeto.</li>
                <li>Explique sua disponibilidade, sua afinidade com a area tematica e de que forma imagina contribuir.</li>
                <li>Envie a mensagem para o responsavel pelo projeto e mantenha o CEPIN-CIS em copia para facilitar o acompanhamento institucional.</li>
            </ol>

            <div class="project-detail-contact-stack project-detail-contact-stack--compact">
                <div class="project-detail-contact-item">
                    <span>Responsavel do projeto</span>
                    <strong><?php echo htmlspecialchars($ownerName, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="project-detail-contact-item">
                    <span>Canal institucional</span>
                    <a href="mailto:<?php echo htmlspecialchars($cepinEmail, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cepinEmail, ENT_QUOTES, 'UTF-8'); ?></a>
                </div>
            </div>
        </article>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
