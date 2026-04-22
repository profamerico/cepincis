<?php
$pageTitle = 'Contato | CEPIN-CIS';
$bodyClass = 'public-page';
$projectInterest = trim((string) ($_GET['project'] ?? ''));
$categoryInterest = trim((string) ($_GET['category'] ?? ''));
$hasContext = $projectInterest !== '' || $categoryInterest !== '';

require_once 'models/ContentBlock.php';

$contentManager = new ContentBlockManager();
$contactBlocks = array_values($contentManager->getPageBlocks('contact'));
$initialVisibleContactBlocks = array_slice($contactBlocks, 0, 7);
$overflowContactBlocks = array_slice($contactBlocks, 7);
$hasOverflowContactBlocks = !empty($overflowContactBlocks);

function contact_next_heading_tag(bool &$pageHeadingUsed): string
{
    if (!$pageHeadingUsed) {
        $pageHeadingUsed = true;
        return 'h1';
    }

    return 'h2';
}

function contact_render_body(string $text): string
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

function contact_block_classes(array $block): string
{
    $classes = ['panel-card', 'content-block', 'content-block--' . (string) ($block['width'] ?? 'half')];
    $type = (string) ($block['type'] ?? 'text_card');

    if ($type === 'contact_info') {
        $classes[] = 'public-copy-card';
        $classes[] = 'public-copy-card--featured';
        $classes[] = 'content-block--contact-info';
    } elseif ($type === 'map_embed') {
        $classes[] = 'public-map-card';
        $classes[] = 'content-block--map';
    } else {
        $classes[] = 'public-section-card';
        $classes[] = 'content-block--text';
    }

    return implode(' ', $classes);
}

function contact_render_block(array $block, string $headingTag, bool $hasContext, string $projectInterest, string $categoryInterest): void
{
    $blockType = (string) ($block['type'] ?? 'text_card');
    ?>
    <article class="<?php echo htmlspecialchars(contact_block_classes($block), ENT_QUOTES, 'UTF-8'); ?>">
        <?php if (($block['eyebrow'] ?? '') !== ''): ?>
            <p class="eyebrow content-block-eyebrow"><?php echo htmlspecialchars((string) $block['eyebrow'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <<?php echo $headingTag; ?>>
            <?php echo htmlspecialchars((string) ($block['title'] ?? 'Bloco'), ENT_QUOTES, 'UTF-8'); ?>
        </<?php echo $headingTag; ?>>

        <?php echo contact_render_body((string) ($block['body'] ?? '')); ?>

        <?php if (!empty($block['show_context_note']) && $hasContext): ?>
            <div class="public-context-note">
                <?php if ($projectInterest !== ''): ?>
                    <p><strong>Projeto:</strong> <?php echo htmlspecialchars($projectInterest, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>

                <?php if ($categoryInterest !== ''): ?>
                    <p><strong>Categoria:</strong> <?php echo htmlspecialchars($categoryInterest, ENT_QUOTES, 'UTF-8'); ?></p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($block['items'])): ?>
            <div class="public-contact-list">
                <?php foreach ($block['items'] as $item): ?>
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

        <?php if ($blockType === 'map_embed' && (string) ($block['embed_url'] ?? '') !== ''): ?>
            <iframe
                class="public-map-frame"
                src="<?php echo htmlspecialchars((string) $block['embed_url'], ENT_QUOTES, 'UTF-8'); ?>"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                allowfullscreen=""
                title="<?php echo htmlspecialchars((string) ($block['title'] ?? 'Mapa do CEPIN-CIS'), ENT_QUOTES, 'UTF-8'); ?>"
            ></iframe>
        <?php endif; ?>

        <?php if ($blockType !== 'map_embed' && (string) ($block['cta_label'] ?? '') !== '' && (string) ($block['cta_url'] ?? '') !== ''): ?>
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
    <div class="content-block-reveal-root" data-content-block-reveal-root>
        <section id="contato" class="public-contact-grid public-contact-grid--page public-contact-grid--blocks">
            <?php if (empty($contactBlocks)): ?>
                <article class="panel-card public-copy-card public-copy-card--featured content-block content-block--full">
                    <h1>Contato em atualizacao</h1>
                    <p>Os blocos desta pagina ainda nao foram publicados no painel mestre.</p>
                </article>
            <?php else: ?>
                <?php $pageHeadingUsed = false; ?>
                <?php foreach ($initialVisibleContactBlocks as $block): ?>
                    <?php contact_render_block($block, contact_next_heading_tag($pageHeadingUsed), $hasContext, $projectInterest, $categoryInterest); ?>
                <?php endforeach; ?>

                <?php if ($hasOverflowContactBlocks): ?>
                    <button
                        type="button"
                        class="panel-card content-block content-block--half content-block--toggle"
                        data-content-block-toggle
                        aria-expanded="false"
                        aria-controls="contactOverflowBlocks"
                    >
                        <span class="content-block-toggle__icon" aria-hidden="true">+</span>
                        <span class="content-block-toggle__label">Ver mais blocos</span>
                        <span class="content-block-toggle__count"><?php echo count($overflowContactBlocks); ?> bloco(s) extra(s)</span>
                    </button>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <?php if ($hasOverflowContactBlocks): ?>
            <div class="content-block-reveal" id="contactOverflowBlocks" data-content-block-reveal>
                <div class="content-block-reveal__inner" data-content-block-reveal-inner>
                    <div class="public-contact-grid public-contact-grid--page public-contact-grid--blocks public-contact-grid--overflow">
                        <?php foreach ($overflowContactBlocks as $block): ?>
                            <?php contact_render_block($block, contact_next_heading_tag($pageHeadingUsed), $hasContext, $projectInterest, $categoryInterest); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>

<?php include_once 'includes/footer.php'; ?>
