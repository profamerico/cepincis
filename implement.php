<?php
$pageTitle = 'Áreas Temáticas | CEPIN-CIS';
$bodyClass = 'public-page';
$regulationUrl = 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf';
$themes = [
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

$themeTags = ['EduCIS', 'EcoMat', 'IoT', 'CarbonZero', 'UrbanSmart'];
$themes = array_map(
    static function (array $theme, int $index) use ($themeTags): array {
        $theme['tag'] = $themeTags[$index] ?? '';
        return $theme;
    },
    $themes,
    array_keys($themes)
);

include_once 'includes/header.php';
?>

<div class="ball"></div>

<main class="page-shell public-shell">
    <section class="panel-card public-copy-card public-copy-card--featured">
        <h1>Áreas temáticas</h1>
        <p>As Áreas Temáticas nas quais serão alinhadas as linhas de pesquisas foram definidas para orientar as atividades do CEPIN-CIS e compreendem:</p>
    </section>

    <section id="areas" class="public-topic-grid public-topic-grid--wide">
        <?php foreach ($themes as $theme): ?>
            <article class="panel-card public-topic-card">
                <span class="public-topic-tag"><?php echo htmlspecialchars((string) ($theme['tag'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></span>
                <h3><?php echo htmlspecialchars($theme['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                <p><?php echo htmlspecialchars($theme['description'], ENT_QUOTES, 'UTF-8'); ?></p>
            </article>
        <?php endforeach; ?>
    </section>

    <section class="public-cta-grid">
        <article id="regulamento" class="panel-card public-simple-card">
            <h2>Regulamento</h2>

            <p>O regulamento do Centro de Pesquisa e Inovação em Cidades Inteligentes e Sustentáveis (CEPIN-CIS) foi aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco normativo consolida a missão do CEPIN-CIS como espaço de fomento à pesquisa aplicada, à inovação tecnológica e à reflexão crítica sobre os desafios contemporâneos das cidades.</p>

            <p>O regulamento estabelece as diretrizes para a participação de servidores e discentes vinculados a projetos de ensino, pesquisa ou extensão que dialoguem com as áreas temáticas do Centro, além de abrir espaço para a colaboração de pesquisadores externos.</p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="<?php echo htmlspecialchars($regulationUrl, ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener">Clique aqui para ver o regulamento</a>
            </div>
        </article>

        <article class="panel-card public-copy-card">
            <h2>Contato</h2>

            <p>Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.</p>

            <div class="public-contact-list">
                <div class="public-contact-item">
                    <strong>Email institucional</strong>
                    <a href="mailto:cepin.cis@ifspcaraguatatuba.edu.br">cepin.cis@ifspcaraguatatuba.edu.br</a>
                </div>
            </div>

            <div class="hero-actions">
                <a class="dashboard-btn" href="./contact.php">Enviar E-mail</a>
            </div>
        </article>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
