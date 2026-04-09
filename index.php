<?php
$pageTitle = 'CEPIN-CIS';
$bodyClass = 'smooth-scroll-page home-page public-page';
$regulationUrl = 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf';

require_once 'models/Project.php';

$projectManager = new ProjectManager();
$homepageProjects = $projectManager->getAllProjects();
$homepageProjectTags = $projectManager->getProjectTags($homepageProjects);
$implementationHighlights = [
    [
        'title' => 'Formação de recursos humanos para cidades inteligentes e sustentáveis',
        'description' => 'Desenvolvimento de recursos humanos e métodos ágeis de capacitação para impulsionar a inovação em Cidades Inteligentes e Sustentáveis.',
    ],
    [
        'title' => 'Novos materiais e economia circular',
        'description' => 'Investigar materiais sustentáveis e promoção de economia circular, reduzindo o impacto ambiental.',
    ],
    [
        'title' => 'Desenvolvimento tecnológico e conectividade para cidades inteligentes e sustentáveis',
        'description' => 'Desenvolver tecnologias avançadas e soluções de conectividade para criar ambientes urbanos mais inteligentes, eficientes e sustentáveis.',
    ],
    [
        'title' => 'Descarbonização do ambiente construído',
        'description' => 'Promover a redução das emissões de carbono em edifícios, infraestrutura, mobilidade urbana, fontes de energia e saneamento ambiental.',
    ],
    [
        'title' => 'Monitoramento e operações urbanas inteligentes',
        'description' => 'Desenvolver soluções para monitorar e gerenciar a infraestrutura urbana, utilizando tecnologias como gêmeos digitais, plataformas digitais, sistemas autônomos e drones, para otimizar o funcionamento de serviços essenciais e melhorar a resiliência climática.',
    ],
];

function home_project_status_label(string $status): string
{
    switch ($status) {
        case 'completed':
            return 'Concluído';
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

include_once 'includes/header.php';
?>

<div class="js-cont">
    <div class="js-scroll">
        <div class="full-screen">
            <div class="ball"></div>

            <section class="hero">
                <h1>CEPIN-CIS</h1>
                <h2>Centro de Pesquisa e Inovação em Cidades Inteligentes</h2>
                <div class="hero-buttons">
                    <a href="./about.php#sobre" class="btn big">Saiba Mais</a>
                    <a href="./contact.php" class="btn big">Entre em Contato</a>
                </div>
            </section>

            <section class="secao-publicacoes-recentes">
                <div class="cabecalho-publicacoes cabecalho-publicacoes--home">
                    <div>
                        <h3 class="titulo-categoria-publicacoes">do CEPIN-CIS:</h3>
                        <h2 class="titulo-principal-publicacoes">PROJETOS</h2>
                    </div>
                    <p class="subtitulo-publicacoes-home">Um recorte dos projetos reais cadastrados no portal, com busca por termo e filtros por tags para facilitar a exploração.</p>
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
                                        <h2 class="titulo-card-publicacao"><?php echo htmlspecialchars((string) ($project['title'] ?? 'Projeto sem título'), ENT_QUOTES, 'UTF-8'); ?></h2>
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
                                    <span class="status-card-publicacao status-card-publicacao--pending">Aguardando publicação</span>
                                </div>

                                <div class="conteudo-card-publicacao">
                                    <h2 class="titulo-card-publicacao">Nenhum projeto cadastrado ainda</h2>
                                    <p class="descricao-card-publicacao">Assim que os projetos forem adicionados no painel administrativo, este carrossel será preenchido automaticamente com os dados reais do portal.</p>
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
                            aria-label="Próximo projeto"
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
                        <h2>Sobre</h2>

                        <p>Os principais objetivos do CEPIN-CIS são desenvolver investigação fundamental ou aplicada focada em cidades inteligentes e sustentáveis, contribuir ativamente para a inovação por meio da transferência de tecnologia e oferecer atividades de extensão.</p>

                        <p>O Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis (CEPIN-CIS), implementado no IFSP campus Caraguatatuba, tem como missão fomentar o desenvolvimento de cidades inteligentes e sustentáveis, funcionando como um repositório de tecnologias, laboratório de aplicação e agente de interlocução entre os setores público e privado.</p>

                        <div class="hero-actions">
                            <a class="dashboard-btn" href="./about.php#sobre">Saiba mais</a>
                        </div>
                    </article>

                    <article class="panel-card public-image-card">
                        <img src="./img/banner.png" alt="Logo CEPIN-CIS">
                    </article>
                </section>

                <section id="implementacao" class="panel-card public-section-card">
                    <div class="panel-card-header">
                        <div>
                            <h2>Implementação</h2>
                        </div>

                        <a class="dashboard-btn dashboard-btn--ghost" href="./implement.php">Ver todas as áreas</a>
                    </div>

                    <p class="panel-copy">A implementação do CEPIN-CIS ocorreu durante os anos de 2022 e 2023, com recursos obtidos através do edital nº PRP/IFSP 329/2021.</p>

                    <div class="public-topic-grid public-topic-grid--home">
                        <?php foreach ($implementationHighlights as $highlight): ?>
                            <article class="public-topic-card">
                                <h3><?php echo htmlspecialchars($highlight['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <p><?php echo htmlspecialchars($highlight['description'], ENT_QUOTES, 'UTF-8'); ?></p>
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

                    <div class="public-partners-stage">
                        <div class="carousel-container">
                            <button class="nav-arrow left" type="button" aria-label="Parceiro anterior">&#8249;</button>
                            <div class="carousel-track">
                                <div class="card" data-index="0">
                                    <img src="./img/Kobenhavns.png" alt="Universidade de Copenhagen">
                                </div>
                                <div class="card" data-index="1">
                                    <img src="./img/Roma 3.png" alt="Universidade de Roma 3">
                                </div>
                                <div class="card" data-index="2">
                                    <img src="./img/FUHZOU UNIVERSITY SLIDER.png" alt="Universidade de Fuzhou">
                                </div>
                                <div class="card" data-index="3">
                                    <img src="./img/getis.png" alt="GETIS">
                                </div>
                                <div class="card" data-index="4">
                                    <img src="./img/i2v2.png" alt="i2">
                                </div>
                                <div class="card" data-index="5">
                                    <img src="./img/Enasa.png" alt="ENASA">
                                </div>
                            </div>
                            <button class="nav-arrow right" type="button" aria-label="Próximo parceiro">&#8250;</button>
                        </div>
                    </div>

                    <div class="member-info">
                        <h2 class="member-name">Universidade de Copenhagen</h2>
                        <p class="member-role">Copenhagen, Dinamarca</p>
                    </div>

                    <div class="dots">
                        <div class="dot active" data-index="0"></div>
                        <div class="dot" data-index="1"></div>
                        <div class="dot" data-index="2"></div>
                        <div class="dot" data-index="3"></div>
                        <div class="dot" data-index="4"></div>
                        <div class="dot" data-index="5"></div>
                    </div>
                </section>

                <section id="regulamento" class="panel-card public-simple-card">
                    <h2>Regulamento</h2>

                    <p>O regulamento do Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis (CEPIN-CIS) foi aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco normativo consolida a missão do CEPIN-CIS como espaço de fomento à pesquisa aplicada, à inovação tecnológica e à reflexão crítica sobre os desafios contemporâneos das cidades.</p>

                    <p>O regulamento estabelece as diretrizes para a participação de servidores e discentes vinculados a projetos de ensino, pesquisa ou extensão que dialoguem com as áreas temáticas do Centro, além de abrir espaço para a colaboração de pesquisadores externos.</p>

                    <div class="hero-actions">
                        <a class="dashboard-btn" href="<?php echo htmlspecialchars($regulationUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Clique aqui para ver o regulamento</a>
                    </div>
                </section>

                <section id="contato" class="public-contact-grid">
                    <article class="panel-card public-copy-card">
                        <h2>Contato</h2>

                        <p>Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.</p>

                        <div class="public-contact-list">
                            <div class="public-contact-item">
                                <strong>Email institucional</strong>
                                <a href="mailto:cepin.cis@ifspcaraguatatuba.edu.br">cepin.cis@ifspcaraguatatuba.edu.br</a>
                            </div>

                            <div class="public-contact-item">
                                <strong>Endereço</strong>
                                <span>IFSP Campus Caraguatatuba, sala 107B.</span>
                            </div>
                        </div>

                        <div class="hero-actions">
                            <a class="dashboard-btn" href="./contact.php">Abrir contato</a>
                            <a class="dashboard-btn dashboard-btn--ghost" href="mailto:cepin.cis@ifspcaraguatatuba.edu.br">Enviar e-mail</a>
                        </div>
                    </article>

                    <article class="panel-card public-map-card">
                        <h2>Mapa</h2>

                        <iframe
                            class="public-map-frame"
                            src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d228.4439040435433!2d-45.4258447087537!3d-23.636501255140573!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1spt-BR!2sbr!4v1763413745838!5m2!1spt-BR!2sbr"
                            loading="lazy"
                            referrerpolicy="no-referrer-when-downgrade"
                            allowfullscreen=""
                            title="Mapa do IFSP Campus Caraguatatuba"
                        ></iframe>
                    </article>
                </section>
            </main>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>
