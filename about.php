<?php
$pageTitle = 'Sobre | CEPIN-CIS';
$bodyClass = 'public-page';

require_once 'models/ContentBlock.php';

$contentManager = new ContentBlockManager();
$aboutBlocks = array_values($contentManager->getPageBlocks('about'));
$aboutLayout = $contentManager->getPageLayout('about');

function about_render_body(string $text): string
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

function about_render_list(array $items): string
{
    if (empty($items)) {
        return '';
    }

    $lines = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $label = trim((string) ($item['label'] ?? ''));
        $value = trim((string) ($item['value'] ?? ''));

        if ($label === '' && $value === '') {
            continue;
        }

        $line = $label !== '' ? '<strong>' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . ':</strong> ' : '';
        $line .= htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        $lines[] = '<li>' . $line . '</li>';
    }

    if (empty($lines)) {
        return '';
    }

    return '<ul class="content-block-list">' . implode('', $lines) . '</ul>';
}

function about_render_contact_items(array $items): void
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

function about_is_infrastructure_block(array $block): bool
{
    $type = (string) ($block['type'] ?? '');
    $name = strtolower(trim((string) ($block['name'] ?? '')));
    $title = strtolower(trim((string) ($block['title'] ?? '')));

    if ($type !== 'about_list') {
        return false;
    }

    return strpos($name, 'infra') !== false || strpos($title, 'infra') !== false;
}

function about_next_heading_tag(array $block, bool &$pageHeadingUsed): ?string
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

function about_block_classes(array $block): string
{
    if (about_is_infrastructure_block($block)) {
        return 'about-block about-block--infrastructure infraestruturas';
    }

    $type = (string) ($block['type'] ?? 'about_text');
    $height = (string) ($block['height'] ?? 'regular');
    $classes = ['panel-card', 'content-block', 'about-block', 'content-block--height-' . $height];

    switch ($type) {
        case 'about_media':
            $classes[] = 'public-image-card';
            $classes[] = 'public-image-card--banner';
            $classes[] = 'about-block--media';
            break;
        case 'about_list':
            $classes[] = 'public-section-card';
            $classes[] = 'about-block--list';
            break;
        case 'about_cta':
            $classes[] = !empty($block['items']) ? 'public-copy-card' : 'public-simple-card';
            $classes[] = 'about-block--cta';
            break;
        case 'about_text':
        default:
            $classes[] = 'public-copy-card';
            $classes[] = 'about-block--text';
            break;
    }

    return implode(' ', $classes);
}

function about_render_block(array $block, ?string $headingTag, ContentBlockManager $contentManager, array $layout): void
{
    $columns = max(1, (int) ($layout['columns'] ?? 4));
    $span = $contentManager->getWidthSpan((string) ($block['width'] ?? 'span_1'), $columns);
    $type = (string) ($block['type'] ?? 'about_text');
    $isInfrastructureBlock = about_is_infrastructure_block($block);

    if ($isInfrastructureBlock) {
        $headingTag = $headingTag ?? 'h2';
        $monitorImage = trim((string) ($block['media_url'] ?? ''));
        if ($monitorImage === '') {
            $monitorImage = './img/Monitor.png';
        }
        $monitorAlt = trim((string) ($block['media_alt'] ?? ''));
        if ($monitorAlt === '') {
            $monitorAlt = 'Monitor ilustrando a infraestrutura do CEPIN-CIS';
        }
        ?>
        <section
            class="<?php echo htmlspecialchars(about_block_classes($block), ENT_QUOTES, 'UTF-8'); ?>"
            style="grid-column: span <?php echo $span; ?> / span <?php echo $span; ?>;"
        >
            <div class="infraestruturas-inner">
                <<?php echo $headingTag; ?> class="titulo-infraestruturas-sobre">
                    <?php echo htmlspecialchars((string) ($block['title'] ?? 'Infraestruturas'), ENT_QUOTES, 'UTF-8'); ?>
                </<?php echo $headingTag; ?>>

                <div class="infraestruturas-layout">
                    <img
                        src="<?php echo htmlspecialchars($monitorImage, ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars($monitorAlt, ENT_QUOTES, 'UTF-8'); ?>"
                        class="monitor"
                    >

                    <div class="infraestruturas-copy">
                        <?php if ((string) ($block['body'] ?? '') !== ''): ?>
                            <p class="descricao-infraestruturas"><?php echo nl2br(htmlspecialchars((string) $block['body'], ENT_QUOTES, 'UTF-8')); ?></p>
                        <?php endif; ?>

                        <?php if (!empty($block['items'])): ?>
                            <div class="descricao-infraestruturas infraestruturas-list">
                                <?php foreach ($block['items'] as $item): ?>
                                    <?php
                                    if (!is_array($item)) {
                                        continue;
                                    }

                                    $itemLabel = trim((string) ($item['label'] ?? ''));
                                    $itemValue = trim((string) ($item['value'] ?? ''));
                                    if ($itemLabel === '' && $itemValue === '') {
                                        continue;
                                    }
                                    ?>
                                    <p>
                                        <?php if ($itemLabel !== ''): ?>
                                            <strong><?php echo htmlspecialchars($itemLabel, ENT_QUOTES, 'UTF-8'); ?>:</strong>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($itemValue, ENT_QUOTES, 'UTF-8'); ?>
                                    </p>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </section>
        <?php
        return;
    }

    ?>
    <article
        class="<?php echo htmlspecialchars(about_block_classes($block), ENT_QUOTES, 'UTF-8'); ?>"
        style="grid-column: span <?php echo $span; ?> / span <?php echo $span; ?>;"
    >
        <?php if ($type === 'about_media'): ?>
            <img
                class="public-image-card__asset public-image-card__asset--light"
                src="<?php echo htmlspecialchars((string) ($block['media_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                alt="<?php echo htmlspecialchars((string) ($block['media_alt'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
            >

            <?php if ((string) ($block['media_dark_url'] ?? '') !== ''): ?>
                <img
                    class="public-image-card__asset public-image-card__asset--dark"
                    src="<?php echo htmlspecialchars((string) ($block['media_dark_url'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                    alt=""
                    aria-hidden="true"
                >
            <?php endif; ?>
        <?php else: ?>
            <?php if ((string) ($block['eyebrow'] ?? '') !== ''): ?>
                <p class="eyebrow content-block-eyebrow"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <?php if ($headingTag !== null): ?>
                <<?php echo $headingTag; ?>>
                    <?php echo htmlspecialchars((string) ($block['title'] ?? 'Bloco'), ENT_QUOTES, 'UTF-8'); ?>
                </<?php echo $headingTag; ?>>
            <?php endif; ?>

            <?php echo about_render_body((string) ($block['body'] ?? '')); ?>

            <?php if ($type === 'about_list'): ?>
                <?php echo about_render_list($block['items'] ?? []); ?>
            <?php elseif (!empty($block['items'])): ?>
                <?php about_render_contact_items($block['items']); ?>
            <?php endif; ?>

            <?php if ((string) ($block['cta_label'] ?? '') !== '' && (string) ($block['cta_url'] ?? '') !== ''): ?>
                <div class="hero-actions">
                    <a class="dashboard-btn" href="<?php echo htmlspecialchars((string) $block['cta_url'], ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars((string) $block['cta_label'], ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </article>
    <?php
}

$gridStyleClass = (string) ($aboutLayout['grid_style'] ?? 'standard') === 'dense'
    ? 'content-layout-grid content-layout-grid--dense'
    : 'content-layout-grid';
$layoutStyle = sprintf(
    '--content-layout-columns:%d; --content-layout-mobile-columns:%d; --content-layout-gap:%dpx; --content-layout-width:%dpx; --content-layout-padding:%dpx; --content-layout-min-height:%dpx;',
    (int) ($aboutLayout['columns'] ?? 4),
    (int) ($aboutLayout['mobile_columns'] ?? 1),
    (int) ($aboutLayout['gap'] ?? 24),
    (int) ($aboutLayout['container_width'] ?? 1220),
    (int) ($aboutLayout['block_padding'] ?? 32),
    (int) ($aboutLayout['block_min_height'] ?? 210)
);

include_once 'includes/header.php';
?>

<div class="ball"></div>

<main class="page-shell public-shell">
    <section id="sobre" class="about-layout-shell" style="<?php echo htmlspecialchars($layoutStyle, ENT_QUOTES, 'UTF-8'); ?>">
        <div class="<?php echo htmlspecialchars($gridStyleClass, ENT_QUOTES, 'UTF-8'); ?>">
            <?php if (empty($aboutBlocks)): ?>
                <article class="panel-card public-copy-card about-block content-block content-block--height-regular" style="grid-column: 1 / -1;">
                    <h1>Sobre em atualizacao</h1>
                    <p>Os blocos da pagina Sobre ainda nao foram publicados no painel mestre.</p>
                </article>
            <?php else: ?>
                <?php $pageHeadingUsed = false; ?>
                <?php foreach ($aboutBlocks as $block): ?>
                    <?php about_render_block($block, about_next_heading_tag($block, $pageHeadingUsed), $contentManager, $aboutLayout); ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</main>

<?php include_once 'includes/footer.php'; ?>
