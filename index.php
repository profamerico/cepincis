<?php
$pageTitle = 'CEPIN-CIS';
$bodyClass = 'smooth-scroll-page home-page public-page';
$regulationUrl = 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf';

require_once 'models/ContentBlock.php';
require_once 'models/Partner.php';
require_once 'models/Project.php';

$contentManager = new ContentBlockManager();
$partnerManager = new PartnerManager();
$projectManager = new ProjectManager();

$homepageProjects = $projectManager->getAllProjects();
$homepageProjectTags = $projectManager->getProjectTags($homepageProjects);
$aboutHomeBlocks = $contentManager->getPageBlocks('about');
$contactHomeBlocks = $contentManager->getPageBlocks('contact');
$homepagePartners = $partnerManager->listPartners();

$implementationHighlights = [
    [
        'title' => 'Formacao de recursos humanos para cidades inteligentes e sustentaveis',
        'description' => 'Desenvolvimento de recursos humanos e metodos ageis de capacitacao para impulsionar a inovacao em Cidades Inteligentes e Sustentaveis.',
    ],
    [
        'title' => 'Novos materiais e economia circular',
        'description' => 'Investigar materiais sustentaveis e promover economia circular, reduzindo o impacto ambiental.',
    ],
    [
        'title' => 'Desenvolvimento tecnologico e conectividade para cidades inteligentes e sustentaveis',
        'description' => 'Desenvolver tecnologias avancadas e solucoes de conectividade para criar ambientes urbanos mais inteligentes, eficientes e sustentaveis.',
    ],
    [
        'title' => 'Descarbonizacao do ambiente construido',
        'description' => 'Promover a reducao das emissoes de carbono em edificios, infraestrutura, mobilidade urbana, fontes de energia e saneamento ambiental.',
    ],
    [
        'title' => 'Monitoramento e operacoes urbanas inteligentes',
        'description' => 'Desenvolver solucoes para monitorar e gerenciar a infraestrutura urbana com plataformas digitais, sistemas autonomos e drones.',
    ],
];

$implementationHighlightTags = ['EduCIS', 'EcoMat', 'IoT', 'CarbonZero', 'UrbanSmart'];
$implementationHighlights = array_map(
    static function (array $highlight, int $index) use ($implementationHighlightTags): array {
        $highlight['tag'] = $implementationHighlightTags[$index] ?? '';
        return $highlight;
    },
    $implementationHighlights,
    array_keys($implementationHighlights)
);

function home_project_status_label(string $status): string
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

function home_project_status_key(?string $status): string
{
    $status = strtolower(trim((string) $status));

    if (in_array($status, ['active', 'completed', 'pending'], true)) {
        return $status;
    }

    return 'active';
}

function home_project_excerpt(string $text, int $limit = 150): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '') {
        return 'Projeto cadastrado no portal do CEPIN-CIS. Os detalhes completos podem ser publicados conforme a equipe atualizar a base.';
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

function home_find_first_block(array $blocks, array $preferredTypes): ?array
{
    foreach ($preferredTypes as $type) {
        foreach ($blocks as $block) {
            if ((string) ($block['type'] ?? '') === $type) {
                return $block;
            }
        }
    }

    return $blocks[0] ?? null;
}

function home_render_body(string $text): string
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

$defaultAboutTextBlock = [
    'title' => 'Sobre',
    'body' => "Os principais objetivos do CEPIN-CIS sao desenvolver investigacao fundamental ou aplicada focada em cidades inteligentes e sustentaveis, contribuir ativamente para a inovacao por meio da transferencia de tecnologia e oferecer atividades de extensao.\n\nO Centro de Pesquisa e Inovacao em Cidades Inteligentes e Sustentaveis (CEPIN-CIS), implementado no IFSP campus Caraguatatuba, tem como missao fomentar o desenvolvimento de cidades inteligentes e sustentaveis, funcionando como um repositorio de tecnologias, laboratorio de aplicacao e agente de interlocucao entre os setores publico e privado.",
    'cta_label' => 'Saiba mais',
    'cta_url' => './about.php#sobre',
];
$defaultAboutMediaBlock = [
    'media_url' => './img/banner.png',
    'media_dark_url' => './img/bannerescuro.png',
    'media_alt' => 'Logo CEPIN-CIS',
];
$defaultContactInfoBlock = [
    'title' => 'Contato',
    'body' => 'Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.',
    'items' => [
        [
            'label' => 'Email institucional',
            'value' => 'cepin.cis@ifspcaraguatatuba.edu.br',
            'url' => 'mailto:cepin.cis@ifspcaraguatatuba.edu.br',
        ],
        [
            'label' => 'Endereco',
            'value' => 'IFSP Campus Caraguatatuba, sala 107B.',
            'url' => '',
        ],
    ],
    'cta_label' => 'Enviar e-mail',
    'cta_url' => 'mailto:cepin.cis@ifspcaraguatatuba.edu.br',
];
$defaultContactMapBlock = [
    'title' => 'Mapa',
    'embed_url' => 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d228.4439040435433!2d-45.4258447087537!3d-23.636501255140573!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1spt-BR!2sbr!4v1763413745838!5m2!1spt-BR!2sbr',
];

$homeAboutTextBlock = array_replace($defaultAboutTextBlock, home_find_first_block($aboutHomeBlocks, ['about_text']) ?? []);
$homeAboutMediaBlock = array_replace($defaultAboutMediaBlock, home_find_first_block($aboutHomeBlocks, ['about_media']) ?? []);
$homeContactInfoBlock = array_replace($defaultContactInfoBlock, home_find_first_block($contactHomeBlocks, ['contact_info', 'text_card']) ?? []);
$homeContactMapBlock = array_replace($defaultContactMapBlock, home_find_first_block($contactHomeBlocks, ['map_embed']) ?? []);

$homeAboutButtonLabel = trim((string) ($homeAboutTextBlock['cta_label'] ?? '')) !== ''
    ? (string) $homeAboutTextBlock['cta_label']
    : 'Saiba mais';
$homeAboutButtonUrl = trim((string) ($homeAboutTextBlock['cta_url'] ?? '')) !== ''
    ? (string) $homeAboutTextBlock['cta_url']
    : './about.php#sobre';
$homeContactSecondaryLabel = trim((string) ($homeContactInfoBlock['cta_label'] ?? '')) !== ''
    ? (string) $homeContactInfoBlock['cta_label']
    : 'Enviar e-mail';
$homeContactSecondaryUrl = trim((string) ($homeContactInfoBlock['cta_url'] ?? '')) !== ''
    ? (string) $homeContactInfoBlock['cta_url']
    : 'mailto:cepin.cis@ifspcaraguatatuba.edu.br';

include_once 'includes/header.php';
?>

<div class="js-cont">
    <div class="js-scroll">
        <div class="full-screen">
            <div class="ball"></div>

            <section class="hero">
                <h1>CEPIN-CIS</h1>
                <h2>Centro de Pesquisa e Inovacao em Cidades Inteligentes</h2>
                <div class="hero-buttons">
                    <a href="./about.php#sobre" class="btn big">Saiba Mais</a>
                    <a href="./contact.php" class="btn big">Entre em Contato</a>
                </div>
            </section>

            <section class="secao-publicacoes-recentes">
                <div class="cabecalho-publicacoes cabecalho-publicacoes--home">
                    <div>
                        <h3 class="titulo-categoria-publicacoes">do CEPIN-CIS:</h3>
                        <h2 class="titulo-principal-publicacoes">Projetos</h2>
                    </div>
                    <p class="subtitulo-publicacoes-home">Um recorte dos projetos reais cadastrados no portal, com busca por termo e filtros por tags para facilitar a exploracao.</p>
                </div>

                <div class="barra-pesquisa">
                    <input
                        type="text"
                        id="campoPesquisa"
                        aria-label="Pesquisar projetos"
                        placeholder="<?php echo empty($homepageProjects) ? 'Nenhum projeto publicado no momento.' : 'Pesquisar projetos por nome, categoria ou tag'; ?>"
                        <?php echo empty($homepageProjects) ? 'disabled' : ''; ?>
                    >
                </div>

                <div class="filtros-tags">
                    <button type="button" data-tag="todos" class="active">Todas</button>
                    <?php foreach ($homepageProjectTags as $tag): ?>
                        <button type="button" data-tag="<?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>">
                            <?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                    <?php endforeach; ?>
                </div>

                <p class="mensagem-projetos-home" data-carousel-empty-message hidden>Nenhum projeto encontrado para esse filtro.</p>

                <div class="wrapper-carrossel-publicacoes">
                    <div class="container-carrossel-publicacoes" id="carrosselPublicacoes">
                        <?php if (!empty($homepageProjects)): ?>
                            <?php foreach ($homepageProjects as $project): ?>
                                <?php
                                $statusKey = home_project_status_key($project['status'] ?? 'active');
                                $statusLabel = home_project_status_label($statusKey);
                                $projectTags = $projectManager->getProjectTagList($project);
                                $displayTags = $projectManager->getProjectTagList($project, false);
                                $searchTags = implode(' ', $projectTags);
                                $contactQuery = http_build_query([
                                    'project' => $project['title'] ?? '',
                                    'category' => $project['category'] ?? '',
                                ]);
                                ?>
                                <article
                                    class="card-publicacao-carrossel"
                                    data-project-card="true"
                                    data-project-id="<?php echo htmlspecialchars((string) ($project['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-title="<?php echo htmlspecialchars((string) ($project['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-category="<?php echo htmlspecialchars((string) ($project['category'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-status="<?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>"
                                    data-tag-list="<?php echo htmlspecialchars(implode('||', $projectTags), ENT_QUOTES, 'UTF-8'); ?>"
                                    data-tags="<?php echo htmlspecialchars($searchTags, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <div class="topo-card-publicacao">
                                        <h3 class="categoria-card-publicacao"><?php echo htmlspecialchars((string) ($project['category'] ?? 'Geral'), ENT_QUOTES, 'UTF-8'); ?></h3>
                                        <span class="status-card-publicacao status-card-publicacao--<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>">
                                            <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </div>

                                    <div class="conteudo-card-publicacao">
                                        <h2 class="titulo-card-publicacao"><?php echo htmlspecialchars((string) ($project['title'] ?? 'Projeto sem titulo'), ENT_QUOTES, 'UTF-8'); ?></h2>
                                        <p class="descricao-card-publicacao"><?php echo htmlspecialchars(home_project_excerpt((string) ($project['description'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></p>
                                    </div>

                                    <div class="rodape-card-publicacao">
                                        <div class="lista-tags-projeto-home">
                                            <?php foreach ($displayTags ?: [(string) ($project['category'] ?? 'Geral')] as $tag): ?>
                                                <span class="tag-projeto-home"><?php echo htmlspecialchars($tag, ENT_QUOTES, 'UTF-8'); ?></span>
                                            <?php endforeach; ?>
                                        </div>

                                        <a class="botao-explorar-publicacao" href="./contact.php?<?php echo htmlspecialchars($contactQuery, ENT_QUOTES, 'UTF-8'); ?>">
                                            <span>Saiba mais</span>
                                        </a>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <article class="card-publicacao-carrossel card-publicacao-carrossel--empty" data-project-card="true" data-empty-state="true">
                                <div class="topo-card-publicacao">
                                    <h3 class="categoria-card-publicacao">Projetos</h3>
                                    <span class="status-card-publicacao status-card-publicacao--pending">Aguardando publicacao</span>
                                </div>

                                <div class="conteudo-card-publicacao">
                                    <h2 class="titulo-card-publicacao">Nenhum projeto cadastrado ainda</h2>
                                    <p class="descricao-card-publicacao">Assim que os projetos forem adicionados no painel administrativo, este carrossel sera preenchido automaticamente com os dados reais do portal.</p>
                                </div>

                                <div class="rodape-card-publicacao">
                                    <div class="lista-tags-projeto-home">
                                        <span class="tag-projeto-home tag-projeto-home--muted">Sem tags por enquanto</span>
                                    </div>

                                    <a class="botao-explorar-publicacao" href="./login.php">
                                        <span>Acessar portal</span>
                                    </a>
                                </div>
                            </article>
                        <?php endif; ?>
                    </div>

                    <div class="navegacao-carrossel-publicacoes">
                        <button
                            type="button"
                            class="botao-seta-carrossel"
                            data-carousel-prev
                            aria-label="Projeto anterior"
                            <?php echo count($homepageProjects) <= 1 ? 'disabled' : ''; ?>
                        >
                            &#8592;
                        </button>
                        <button
                            type="button"
                            class="botao-seta-carrossel"
                            data-carousel-next
                            aria-label="Proximo projeto"
                            <?php echo count($homepageProjects) <= 1 ? 'disabled' : ''; ?>
                        >
                            &#8594;
                        </button>
                    </div>

                    <div class="indicadores-paginacao-carrossel" data-carousel-indicators></div>
                </div>
            </section>

            <main class="page-shell public-home-sections">
                <section id="sobre" class="public-story-grid">
                    <article class="panel-card public-copy-card">
                        <h2><?php echo htmlspecialchars((string) ($homeAboutTextBlock['title'] ?? 'Sobre'), ENT_QUOTES, 'UTF-8'); ?></h2>

                        <div class="mobile-collapse-card" data-mobile-collapse>
                            <button type="button" class="mobile-collapse-toggle" data-mobile-collapse-toggle aria-expanded="false">
                                <span>Ler resumo</span>
                                <i class="fa-solid fa-chevron-down"></i>
                            </button>

                            <div class="mobile-collapse-content" data-mobile-collapse-content>
                                <div class="mobile-collapse-inner" data-mobile-collapse-inner>
                                    <?php echo home_render_body((string) ($homeAboutTextBlock['body'] ?? '')); ?>
                                </div>
                            </div>
                        </div>

                        <div class="hero-actions">
                            <a class="dashboard-btn" href="<?php echo htmlspecialchars($homeAboutButtonUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($homeAboutButtonLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                    </article>

                    <article class="panel-card public-image-card public-image-card--banner">
                        <img
                            class="public-image-card__asset public-image-card__asset--light"
                            src="<?php echo htmlspecialchars((string) ($homeAboutMediaBlock['media_url'] ?? './img/banner.png'), ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars((string) ($homeAboutMediaBlock['media_alt'] ?? 'Logo CEPIN-CIS'), ENT_QUOTES, 'UTF-8'); ?>"
                        >
                        <?php if ((string) ($homeAboutMediaBlock['media_dark_url'] ?? '') !== ''): ?>
                            <img
                                class="public-image-card__asset public-image-card__asset--dark"
                                src="<?php echo htmlspecialchars((string) $homeAboutMediaBlock['media_dark_url'], ENT_QUOTES, 'UTF-8'); ?>"
                                alt=""
                                aria-hidden="true"
                            >
                        <?php endif; ?>
                    </article>
                </section>

                <section id="implementacao" class="panel-card public-section-card">
                    <div class="panel-card-header">
                        <div>
                            <h2>Areas Tematicas</h2>
                        </div>

                        <a class="dashboard-btn dashboard-btn--ghost" href="./implement.php">Ver todas as areas</a>
                    </div>

                    <p class="panel-copy">As Areas Tematicas, nas quais serao alinhados os projetos, foram definidas para orientar as atividades do CEPIN-CIS e compreendem:</p>

                    <div class="public-topic-grid public-topic-grid--home">
                        <?php foreach ($implementationHighlights as $highlight): ?>
                            <article class="public-topic-card">
                                <span class="public-topic-tag"><?php echo htmlspecialchars((string) ($highlight['tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                                <h3><?php echo htmlspecialchars($highlight['title'], ENT_QUOTES, 'UTF-8'); ?></h3>

                                <div class="mobile-collapse-card mobile-collapse-card--compact" data-mobile-collapse>
                                    <button type="button" class="mobile-collapse-toggle mobile-collapse-toggle--compact" data-mobile-collapse-toggle aria-expanded="false">
                                        <span>Ver resumo</span>
                                        <i class="fa-solid fa-chevron-down"></i>
                                    </button>

                                    <div class="mobile-collapse-content mobile-collapse-content--compact" data-mobile-collapse-content>
                                        <div class="mobile-collapse-inner" data-mobile-collapse-inner>
                                            <p><?php echo htmlspecialchars($highlight['description'], ENT_QUOTES, 'UTF-8'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

                <section id="parceiros" class="panel-card public-partners-panel">
                    <div class="panel-card-header">
                        <div>
                            <h2>Parceiros</h2>
                        </div>
                    </div>

                    <?php if (!empty($homepagePartners)): ?>
                        <div class="public-partners-stage" data-partners-carousel>
                            <div class="carousel-container">
                                <button class="nav-arrow left" type="button" aria-label="Parceiro anterior" data-partner-prev <?php echo count($homepagePartners) <= 1 ? 'disabled' : ''; ?>>&#8249;</button>
                                <div class="carousel-track">
                                    <?php foreach ($homepagePartners as $index => $partner): ?>
                                        <div
                                            class="card"
                                            data-index="<?php echo (int) $index; ?>"
                                            data-partner-card
                                            data-partner-name="<?php echo htmlspecialchars((string) ($partner['name'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                            data-partner-description="<?php echo htmlspecialchars((string) ($partner['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                        >
                                            <img
                                                src="<?php echo htmlspecialchars((string) ($partner['image_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                                alt="<?php echo htmlspecialchars((string) ($partner['name'] ?? 'Parceiro'), ENT_QUOTES, 'UTF-8'); ?>"
                                            >
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <button class="nav-arrow right" type="button" aria-label="Proximo parceiro" data-partner-next <?php echo count($homepagePartners) <= 1 ? 'disabled' : ''; ?>>&#8250;</button>
                            </div>

                            <div class="member-info">
                                <h2 class="member-name" data-partner-name><?php echo htmlspecialchars((string) ($homepagePartners[0]['name'] ?? 'Parceiro'), ENT_QUOTES, 'UTF-8'); ?></h2>
                                <p class="member-role" data-partner-description><?php echo htmlspecialchars((string) ($homepagePartners[0]['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                            </div>

                            <div class="dots">
                                <?php foreach ($homepagePartners as $index => $partner): ?>
                                    <div class="dot<?php echo $index === 0 ? ' active' : ''; ?>" data-index="<?php echo (int) $index; ?>" data-partner-dot></div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="public-partners-empty">
                            <p>Nenhum parceiro foi publicado no carrossel ainda. Assim que a equipe cadastrar novas instituicoes no painel mestre, elas aparecerao aqui automaticamente.</p>
                        </div>
                    <?php endif; ?>
                </section>

                <section id="regulamento" class="panel-card public-simple-card">
                    <h2>Regulamento</h2>

                    <div class="mobile-collapse-card" data-mobile-collapse>
                        <button type="button" class="mobile-collapse-toggle" data-mobile-collapse-toggle aria-expanded="false">
                            <span>Ler texto</span>
                            <i class="fa-solid fa-chevron-down"></i>
                        </button>

                        <div class="mobile-collapse-content" data-mobile-collapse-content>
                            <div class="mobile-collapse-inner" data-mobile-collapse-inner>
                                <p>O regulamento do Centro de Pesquisa e Inovacao em Cidades Inteligentes e Sustentaveis (CEPIN-CIS) foi aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco normativo consolida a missao do CEPIN-CIS como espaco de fomento a pesquisa aplicada, a inovacao tecnologica e a reflexao critica sobre os desafios contemporaneos das cidades.</p>

                                <p>O regulamento estabelece as diretrizes para a participacao de servidores e discentes vinculados a projetos de ensino, pesquisa ou extensao que dialoguem com as areas tematicas do Centro, alem de abrir espaco para a colaboracao de pesquisadores externos.</p>
                            </div>
                        </div>
                    </div>

                    <div class="hero-actions">
                        <a class="dashboard-btn" href="<?php echo htmlspecialchars($regulationUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Clique aqui para ver o regulamento</a>
                    </div>
                </section>

                <section id="contato" class="public-contact-grid">
                    <article class="panel-card public-copy-card">
                        <h2><?php echo htmlspecialchars((string) ($homeContactInfoBlock['title'] ?? 'Contato'), ENT_QUOTES, 'UTF-8'); ?></h2>

                        <?php echo home_render_body((string) ($homeContactInfoBlock['body'] ?? '')); ?>

                        <?php if (!empty($homeContactInfoBlock['items'])): ?>
                            <div class="public-contact-list">
                                <?php foreach ($homeContactInfoBlock['items'] as $item): ?>
                                    <?php
                                    $itemLabel = trim((string) ($item['label'] ?? ''));
                                    $itemValue = trim((string) ($item['value'] ?? ''));
                                    $itemUrl = trim((string) ($item['url'] ?? ''));
                                    if ($itemLabel === '' && $itemValue === '') {
                                        continue;
                                    }
                                    ?>
                                    <div class="public-contact-item">
                                        <?php if ($itemLabel !== ''): ?>
                                            <strong><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <?php endif; ?>

                                        <?php if ($itemUrl !== ''): ?>
                                            <a href="<?php echo htmlspecialchars($itemUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($itemValue, ENT_QUOTES, 'UTF-8'); ?>
                                            </a>
                                        <?php else: ?>
                                            <span><?php echo htmlspecialchars($itemValue, ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <div class="hero-actions">
                            <a class="dashboard-btn" href="./contact.php">Abrir contato</a>
                            <a class="dashboard-btn dashboard-btn--ghost" href="<?php echo htmlspecialchars($homeContactSecondaryUrl, ENT_QUOTES, 'UTF-8'); ?>">
                                <?php echo htmlspecialchars($homeContactSecondaryLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </a>
                        </div>
                    </article>

                    <article class="panel-card public-map-card">
                        <h2><?php echo htmlspecialchars((string) ($homeContactMapBlock['title'] ?? 'Mapa'), ENT_QUOTES, 'UTF-8'); ?></h2>

                        <iframe
                            class="public-map-frame"
                            src="<?php echo htmlspecialchars((string) ($homeContactMapBlock['embed_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen=""
                            title="<?php echo htmlspecialchars((string) ($homeContactMapBlock['title'] ?? 'Mapa do IFSP Campus Caraguatatuba'), ENT_QUOTES, 'UTF-8'); ?>"
                        ></iframe>
                    </article>
                </section>
            </main>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
