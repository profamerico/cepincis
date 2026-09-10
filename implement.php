<?php
$pageTitle = 'Areas tematicas | CEPIN-CIS';
$bodyClass = 'public-page';

require_once 'models/ContentBlock.php';

$contentManager = new ContentBlockManager();
$thematicBlocks = array_values($contentManager->getPageBlocks('thematic_areas'));
$thematicLayout = $contentManager->getPageLayout('thematic_areas');

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

function thematic_next_heading_tag(array $block, bool &$pageHeadingUsed): ?string
{
    if (trim((string) ($block['title'] ?? '')) === '') {
        return null;
    }

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

function thematic_block_classes(array $block): string
{
    $type = (string) ($block['type'] ?? 'thematic_topic');
    $height = (string) ($block['height'] ?? 'regular');
    $classes = ['panel-card', 'content-block', 'thematic-block', 'content-block--height-' . $height];

    switch ($type) {
        case 'thematic_intro':
            $classes[] = 'public-copy-card';
            $classes[] = 'public-copy-card--featured';
            $classes[] = 'thematic-block--intro';
            break;
        case 'thematic_cta':
            $classes[] = !empty($block['items']) ? 'public-copy-card' : 'public-simple-card';
            $classes[] = 'thematic-block--cta';
            break;
        case 'thematic_topic':
        default:
            $classes[] = 'public-topic-card';
            $classes[] = 'thematic-block--topic';
            break;
    }

    return implode(' ', $classes);
}

function thematic_block_style(array $block, ContentBlockManager $contentManager, array $layout): string
{
    $columns = max(1, (int) ($layout['columns'] ?? 2));
    $span = $contentManager->getWidthSpan((string) ($block['width'] ?? 'half'), $columns);

    return 'grid-column: span ' . $span . ' / span ' . $span . ';';
}

function thematic_render_block(array $block, ?string $headingTag, ContentBlockManager $contentManager, array $layout): void
{
    $type = (string) ($block['type'] ?? 'thematic_topic');
    ?>
    <article
        class="<?php echo htmlspecialchars(thematic_block_classes($block), ENT_QUOTES, 'UTF-8'); ?>"
        style="<?php echo htmlspecialchars(thematic_block_style($block, $contentManager, $layout), ENT_QUOTES, 'UTF-8'); ?>"
    >
        <?php if (($block['eyebrow'] ?? '') !== ''): ?>
            <?php if ($type === 'thematic_topic'): ?>
                <span class="public-topic-tag"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></span>
            <?php else: ?>
                <p class="eyebrow content-block-eyebrow"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($headingTag !== null): ?>
            <<?php echo $headingTag; ?>>
                <?php echo htmlspecialchars((string) ($block['title'] ?? 'Bloco tematico'), ENT_QUOTES, 'UTF-8'); ?>
            </<?php echo $headingTag; ?>>
        <?php endif; ?>

        <?php echo thematic_render_body((string) ($block['body'] ?? '')); ?>

        <?php if ($type === 'thematic_cta' && !empty($block['items'])): ?>
            <?php thematic_render_contact_items($block['items']); ?>
        <?php endif; ?>

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

$gridStyleClass = (string) ($thematicLayout['grid_style'] ?? 'standard') === 'dense'
    ? 'content-layout-grid content-layout-grid--dense'
    : 'content-layout-grid';
$layoutStyle = sprintf(
    '--content-layout-columns:%d; --content-layout-mobile-columns:%d; --content-layout-gap:%dpx; --content-layout-width:%dpx; --content-layout-padding:%dpx; --content-layout-min-height:%dpx;',
    (int) ($thematicLayout['columns'] ?? 2),
    (int) ($thematicLayout['mobile_columns'] ?? 1),
    (int) ($thematicLayout['gap'] ?? 22),
    (int) ($thematicLayout['container_width'] ?? 1180),
    (int) ($thematicLayout['block_padding'] ?? 28),
    (int) ($thematicLayout['block_min_height'] ?? 200)
);

include_once 'includes/header.php';
?>

<div class="ball"></div>

<main class="page-shell public-shell">
    <section id="areas" class="content-layout-shell" style="<?php echo htmlspecialchars($layoutStyle, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="<?php echo htmlspecialchars($gridStyleClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (empty($thematicBlocks)): ?>
                <article class="panel-card public-copy-card public-copy-card--featured thematic-block content-block content-block--height-regular" style="grid-column: 1 / -1;">
                    <h1>Areas tematicas em atualizacao</h1>
                    <p>Os blocos desta pagina ainda nao foram publicados no painel mestre.</p>
                </article>
            <?php else: ?>
                <?php $pageHeadingUsed = false; ?>
                <?php foreach ($thematicBlocks as $block): ?>
                    <?php thematic_render_block($block, thematic_next_heading_tag($block, $pageHeadingUsed), $contentManager, $thematicLayout); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
