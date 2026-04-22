<?php
$pageTitle = 'Areas tematicas | CEPIN-CIS';
$bodyClass = 'public-page';

require_once 'models/ContentBlock.php';

$contentManager = new ContentBlockManager();
$thematicBlocks = array_values($contentManager->getPageBlocks('thematic_areas'));
$introBlocks = [];
$topicBlocks = [];
$ctaBlocks = [];

foreach ($thematicBlocks as $block) {
    $blockType = (string) ($block['type'] ?? 'thematic_topic');

    if ($blockType === 'thematic_intro') {
        $introBlocks[] = $block;
        continue;
    }

    if ($blockType === 'thematic_cta') {
        $ctaBlocks[] = $block;
        continue;
    }

    if ($blockType === 'thematic_topic') {
        $topicBlocks[] = $block;
    }
}

function thematic_render_body(string $text): string
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

function thematic_next_heading_tag(bool &$pageHeadingUsed): string
{
    if (!$pageHeadingUsed) {
        $pageHeadingUsed = true;
        return 'h1';
    }

    return 'h2';
}

function thematic_render_contact_items(array $items): void
{
    if (empty($items)) {
        return;
    }
    ?>
    <div class="public-contact-list">
        <?php foreach ($items as $item): ?>
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
    <?php
}

function thematic_render_intro_block(array $block, string $headingTag): void
{
    ?>
    <section class="panel-card public-copy-card public-copy-card--featured thematic-block thematic-block--intro">
        <?php if (($block['eyebrow'] ?? '') !== ''): ?>
            <p class="eyebrow content-block-eyebrow"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <<?php echo $headingTag; ?>>
            <?php echo htmlspecialchars((string) ($block['title'] ?? 'Areas tematicas'), ENT_QUOTES, 'UTF-8'); ?>
        </<?php echo $headingTag; ?>>

        <?php echo thematic_render_body((string) ($block['body'] ?? '')); ?>

        <?php if ((string) ($block['cta_label'] ?? '') !== '' && (string) ($block['cta_url'] ?? '') !== ''): ?>
            <div class="hero-actions">
                <a class="dashboard-btn" href="<?php echo htmlspecialchars((string) $block['cta_url'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $block['cta_label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        <?php endif; ?>
    </section>
    <?php
}

function thematic_render_topic_block(array $block): void
{
    $width = (string) ($block['width'] ?? 'half');
    ?>
    <article class="panel-card public-topic-card thematic-block thematic-block--topic content-block content-block--<?php echo htmlspecialchars($width, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (($block['eyebrow'] ?? '') !== ''): ?>
            <span class="public-topic-tag"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></span>
        <?php endif; ?>

        <h3><?php echo htmlspecialchars((string) ($block['title'] ?? 'Area tematica'), ENT_QUOTES, 'UTF-8'); ?></h3>
        <?php echo thematic_render_body((string) ($block['body'] ?? '')); ?>
    </article>
    <?php
}

function thematic_render_cta_block(array $block): void
{
    $width = (string) ($block['width'] ?? 'half');
    $baseClass = !empty($block['items']) ? 'public-copy-card' : 'public-simple-card';
    ?>
    <article class="panel-card <?php echo htmlspecialchars($baseClass, ENT_QUOTES, 'UTF-8'); ?> thematic-block thematic-block--cta content-block content-block--<?php echo htmlspecialchars($width, ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (($block['eyebrow'] ?? '') !== ''): ?>
            <p class="eyebrow content-block-eyebrow"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <h2><?php echo htmlspecialchars((string) ($block['title'] ?? 'Bloco complementar'), ENT_QUOTES, 'UTF-8'); ?></h2>

        <?php echo thematic_render_body((string) ($block['body'] ?? '')); ?>
        <?php thematic_render_contact_items($block['items'] ?? []); ?>

        <?php if ((string) ($block['cta_label'] ?? '') !== '' && (string) ($block['cta_url'] ?? '') !== ''): ?>
            <div class="hero-actions">
                <a class="dashboard-btn" href="<?php echo htmlspecialchars((string) $block['cta_url'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars((string) $block['cta_label'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </div>
        <?php endif; ?>
    </article>
    <?php
}

include_once 'includes/header.php';
?>

<div class="ball"></div>

<main class="page-shell public-shell">
    <?php if (empty($introBlocks)): ?>
        <section class="panel-card public-copy-card public-copy-card--featured thematic-block thematic-block--intro">
            <h1>Areas tematicas</h1>
            <p>Os blocos de abertura desta pagina ainda nao foram publicados no painel mestre.</p>
        </section>
    <?php else: ?>
        <?php $pageHeadingUsed = false; ?>
        <?php foreach ($introBlocks as $block): ?>
            <?php thematic_render_intro_block($block, thematic_next_heading_tag($pageHeadingUsed)); ?>
        <?php endforeach; ?>
    <?php endif; ?>

    <section id="areas" class="public-topic-grid public-topic-grid--wide public-topic-grid--blocks">
        <?php if (empty($topicBlocks)): ?>
            <article class="panel-card public-topic-card thematic-block thematic-block--topic content-block content-block--full">
                <span class="public-topic-tag">Em atualizacao</span>
                <h3>Nenhuma area tematica publicada</h3>
                <p>Use o painel mestre para criar ou reorganizar os cards desta secao.</p>
            </article>
        <?php else: ?>
            <?php foreach ($topicBlocks as $block): ?>
                <?php thematic_render_topic_block($block); ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>

    <?php if (!empty($ctaBlocks)): ?>
        <section class="public-cta-grid public-cta-grid--blocks">
            <?php foreach ($ctaBlocks as $block): ?>
                <?php thematic_render_cta_block($block); ?>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>

<?php include_once 'includes/footer.php'; ?>
