<?php
declare(strict_types=1);
ob_start();
require_once __DIR__ . '/bootstrap.php';

$pageTitle = 'Painel Admin | CEPIN-CIS';
$bodyClass = 'app-page admin-page';

require_once 'controllers/AuthController.php';
require_once 'models/ContentBlock.php';
require_once 'models/Orientation.php';
require_once 'models/Partner.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';
require_once 'models/UserProfileExtras.php';

$auth = new AuthController();
$auth->requireAdmin();

$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);
$profileExtrasManager = new UserProfileExtrasManager();
$contentManager = new ContentBlockManager();
$orientationManager = new OrientationManager();
$partnerManager = new PartnerManager();
$thematicAreaOptions = $projectManager->getThematicAreaOptions();
$currentUser = $auth->getCurrentUser();
$users = $auth->listUsers();
$roleOptions = $auth->getRoleDefinitions();
$contentPageOptions = $contentManager->getPageDefinitions();
$contentTypeOptions = $contentManager->getTypeDefinitions();
$contentWidthOptions = $contentManager->getWidthDefinitions();
$contentHeightOptions = $contentManager->getHeightDefinitions();
$contentGridStyleOptions = $contentManager->getGridStyleDefinitions();
$contentStatusOptions = $contentManager->getStatusDefinitions();

function admin_set_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = [
        'type' => $type,
        'message' => $message,
    ];
}

function admin_redirect(string $anchor = ''): void
{
    $location = 'admin.php';
    if ($anchor !== '') {
        $location .= '#' . $anchor;
    }

    header('Location: ' . $location);
    exit();
}

function admin_status_label(string $status): string
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

function admin_format_datetime(?string $value): string
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

function admin_format_tags(array $tags): string
{
    if (empty($tags)) {
        return 'Sem tags';
    }

    return implode(', ', $tags);
}

function admin_provider_label(?string $provider): string
{
    $provider = strtolower(trim((string) $provider));

    switch ($provider) {
        case 'google':
            return 'Google';
        case 'github':
            return 'GitHub';
        case 'microsoft':
            return 'Microsoft';
        case 'social':
            return 'Rede social';
        case 'admin':
            return 'Painel admin';
        case 'local':
        default:
            return 'Local';
    }
}

function admin_content_excerpt(string $text, int $limit = 120): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));

    if ($text === '') {
        return 'Sem texto complementar.';
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

function admin_format_block_items(array $items): string
{
    if (empty($items)) {
        return 'Sem itens estruturados.';
    }

    $previewItems = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $label = trim((string) ($item['label'] ?? ''));
        $value = trim((string) ($item['value'] ?? ''));

        if ($label !== '' && $value !== '') {
            $previewItems[] = $label . ': ' . $value;
        } elseif ($value !== '') {
            $previewItems[] = $value;
        }

        if (count($previewItems) === 2) {
            break;
        }
    }

    return $previewItems ? implode(' | ', $previewItems) : 'Sem itens estruturados.';
}

function admin_preview_block_title(array $block): string
{
    $name = trim((string) ($block['name'] ?? ''));
    $title = trim((string) ($block['title'] ?? ''));

    if ($name !== '') {
        return $name;
    }

    if ($title !== '') {
        return $title;
    }

    return 'Bloco';
}

function admin_preview_block_copy(array $block): string
{
    $type = trim((string) ($block['type'] ?? ''));
    $itemsPreview = admin_format_block_items($block['items'] ?? []);

    if ($type === 'about_media') {
        $mediaAlt = trim((string) ($block['media_alt'] ?? ''));
        return $mediaAlt !== '' ? $mediaAlt : 'Bloco visual institucional.';
    }

    if ($itemsPreview !== 'Sem itens estruturados.') {
        return $itemsPreview;
    }

    return admin_content_excerpt((string) ($block['body'] ?? ''), 116);
}

function admin_visual_width_short_label(string $width): string
{
    switch ($width) {
        case 'span_1':
            return '1C';
        case 'span_2':
            return '2C';
        case 'span_3':
            return '3C';
        case 'span_4':
            return '4C';
        case 'full':
            return 'FULL';
        case 'half':
        default:
            return 'HALF';
    }
}

function admin_icon(string $name): string
{
    switch ($name) {
        case 'eye':
            return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M1.5 12s3.75-6.75 10.5-6.75S22.5 12 22.5 12s-3.75 6.75-10.5 6.75S1.5 12 1.5 12Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/><circle cx="12" cy="12" r="3.25" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
        case 'edit':
            return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4.5 19.5h3l9.75-9.75-3-3L4.5 16.5v3Z" fill="none" stroke="currentColor" stroke-linejoin="round" stroke-width="1.8"/><path d="m12.75 7.5 3 3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><path d="M13.5 4.5 16.5 1.5 22.5 7.5 19.5 10.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>';
        case 'up':
            return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5.25v13.5" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><path d="m6.75 10.5 5.25-5.25 5.25 5.25" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>';
        case 'down':
            return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 18.75V5.25" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"/><path d="m17.25 13.5-5.25 5.25-5.25-5.25" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>';
        default:
            return '';
    }
}

function admin_layout_builder_columns_max(string $pageKey): int
{
    return $pageKey === 'about' ? 4 : 2;
}

function admin_render_layout_builder(
    string $pageKey,
    array $pageDefinition,
    array $layoutForm,
    array $blocks,
    array $widthOptions,
    array $heightOptions,
    array $gridStyleOptions,
    array $errors,
    bool $isVisible,
    string $csrfToken,
    ContentBlockManager $contentManager
): void {
    $pageLabel = (string) ($pageDefinition['label'] ?? $contentManager->getPageLabel($pageKey));
    $columnsMax = admin_layout_builder_columns_max($pageKey);
    $gridStyle = (string) ($layoutForm['grid_style'] ?? 'standard');
    $layoutStyle = sprintf(
        '--admin-layout-columns:%d; --admin-layout-gap:%dpx; --admin-layout-width:%dpx; --admin-layout-padding:%dpx; --admin-layout-min-height:%dpx; --admin-layout-flow:%s;',
        (int) ($layoutForm['columns'] ?? ($pageKey === 'about' ? 4 : 2)),
        (int) ($layoutForm['gap'] ?? 24),
        (int) ($layoutForm['container_width'] ?? 1220),
        (int) ($layoutForm['block_padding'] ?? 32),
        (int) ($layoutForm['block_min_height'] ?? 210),
        $gridStyle === 'dense' ? 'row dense' : 'row'
    );
?>
    <article
        class="panel-card admin-layout-builder"
        data-layout-builder
        data-layout-builder-page="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>"
        data-layout-inline-controls="1"
 <?php echo $isVisible ? '' : 'hidden'; ?>>
        <div class="panel-card-header">
            <div>
                <p class="eyebrow">Esqueleto da pagina</p>
                <h2>Layout visual de <?php echo htmlspecialchars($pageLabel, ENT_QUOTES, 'UTF-8'); ?></h2>
                <p class="admin-subtitle">Ajuste o grid desta pagina e refine cada bloco diretamente no canvas com olho, lapis e controles contextuais.</p>
            </div>
        </div>

 <?php foreach ($errors as $error): ?>
            <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
 <?php endforeach; ?>

        <form method="POST" class="stack-form admin-layout-builder__form" data-layout-builder-form>
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="action" value="save_content_layout">
            <input type="hidden" name="layout_page_key" value="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>">
            <input type="hidden" name="layout_blocks_json" value="" data-layout-blocks-input>

            <div class="admin-layout-builder__controls">
                <div class="admin-layout-control admin-layout-control--select">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_grid_style">Formato do grid</label>
                        <span class="admin-layout-control__value" data-layout-value-output="grid_style"></span>
                    </div>
                    <select id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_grid_style" name="grid_style" data-layout-input="grid_style" class="admin-layout-control__select">
   <?php foreach ($gridStyleOptions as $gridStyleKey => $gridStyleLabel): ?>
                            <option value="<?php echo htmlspecialchars($gridStyleKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $gridStyle === (string) $gridStyleKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars($gridStyleLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                    <p class="admin-layout-control__note">Defina se a grade deve respirar mais ou encaixar os cards de forma mais densa.</p>
                </div>

                <div class="admin-layout-control">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_columns">Colunas no desktop</label>
                        <span class="admin-layout-control__value" data-layout-value-output="columns"></span>
                    </div>
                    <input type="range" id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_columns" name="columns" value="<?php echo htmlspecialchars((string) ($layoutForm['columns'] ?? '2'), ENT_QUOTES, 'UTF-8'); ?>" min="1" max="<?php echo $columnsMax; ?>" step="1" data-layout-input="columns" class="admin-layout-control__range">
                    <div class="admin-layout-control__scale">
                        <span>Mais focado</span>
                        <span>Mais aberto</span>
                    </div>
                </div>


                <div class="admin-layout-control">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_mobile_columns">Colunas no mobile</label>
                        <span class="admin-layout-control__value" data-layout-value-output="mobile_columns"></span>
                    </div>
                    <input type="range" id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_mobile_columns" name="mobile_columns" value="<?php echo htmlspecialchars((string) ($layoutForm['mobile_columns'] ?? '1'), ENT_QUOTES, 'UTF-8'); ?>" min="1" max="2" step="1" data-layout-input="mobile_columns" class="admin-layout-control__range">
                    <div class="admin-layout-control__scale">
                        <span>Empilhado</span>
                        <span>Duplo</span>
                    </div>
                </div>

                <div class="admin-layout-control">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_gap">Distancia entre blocos</label>
                        <span class="admin-layout-control__value" data-layout-value-output="gap"></span>
                    </div>
                    <input type="range" id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_gap" name="gap" value="<?php echo htmlspecialchars((string) ($layoutForm['gap'] ?? '24'), ENT_QUOTES, 'UTF-8'); ?>" min="12" max="56" step="1" data-layout-input="gap" class="admin-layout-control__range">
                    <div class="admin-layout-control__scale">
                        <span>Mais justo</span>
                        <span>Mais arejado</span>
                    </div>
                </div>

                <div class="admin-layout-control">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_container_width">Largura da composicao</label>
                        <span class="admin-layout-control__value" data-layout-value-output="container_width"></span>
                    </div>
                    <input type="range" id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_container_width" name="container_width" value="<?php echo htmlspecialchars((string) ($layoutForm['container_width'] ?? '1220'), ENT_QUOTES, 'UTF-8'); ?>" min="880" max="1480" step="10" data-layout-input="container_width" class="admin-layout-control__range">
                    <div class="admin-layout-control__scale">
                        <span>Contida</span>
                        <span>Cenografica</span>
                    </div>
                </div>

                <div class="admin-layout-control">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_block_padding">Respiro interno</label>
                        <span class="admin-layout-control__value" data-layout-value-output="block_padding"></span>
                    </div>
                    <input type="range" id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_block_padding" name="block_padding" value="<?php echo htmlspecialchars((string) ($layoutForm['block_padding'] ?? '32'), ENT_QUOTES, 'UTF-8'); ?>" min="18" max="56" step="1" data-layout-input="block_padding" class="admin-layout-control__range">
                    <div class="admin-layout-control__scale">
                        <span>Enxuto</span>
                        <span>Respirado</span>
                    </div>
                </div>

                <div class="admin-layout-control">
                    <div class="admin-layout-control__top">
                        <label for="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_block_min_height">Presenca vertical</label>
                        <span class="admin-layout-control__value" data-layout-value-output="block_min_height"></span>
                    </div>
                    <input type="range" id="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>_block_min_height" name="block_min_height" value="<?php echo htmlspecialchars((string) ($layoutForm['block_min_height'] ?? '210'), ENT_QUOTES, 'UTF-8'); ?>" min="140" max="420" step="5" data-layout-input="block_min_height" class="admin-layout-control__range">
                    <div class="admin-layout-control__scale">
                        <span>Baixa</span>
                        <span>Monumental</span>
                    </div>
                </div>
            </div>

            <div class="admin-layout-builder__footer">
                <p class="admin-layout-builder__hint">Ajuste o ambiente geral acima e refine cada bloco diretamente no canvas abaixo com os icones de olho e lapis.</p>
                <button type="submit" class="dashboard-btn">Salvar composicao de<?php echo htmlspecialchars($pageLabel, ENT_QUOTES, 'UTF-8'); ?></button>
            </div>
        </form>

        <div class="admin-layout-preview-shell">
            <div class="admin-layout-preview-copy">
                <strong>Canvas interativo</strong>
                <span>Clique em qualquer card para abrir a edicao. O olho volta para visualizacao, o lapis entra no modo de ajuste e as mudancas de largura, altura, ordem e visibilidade ficam prontas para salvar em lote.</span>
            </div>

            <div class="admin-layout-canvas" data-layout-preview style="<?php echo htmlspecialchars($layoutStyle, ENT_QUOTES, 'UTF-8'); ?>">
   <?php foreach ($blocks as $block): ?>
   <?php
                    $previewSpan = $contentManager->getWidthSpan((string) ($block['width'] ?? 'half'), (int) ($layoutForm['columns'] ?? 2));
                    $previewHeightFactor = $contentManager->getHeightFactor((string) ($block['height'] ?? 'regular'));
                    $previewType = (string) ($block['type'] ?? 'text_card');
                    $previewWidth = (string) ($block['width'] ?? 'half');
                    $previewHeight = (string) ($block['height'] ?? 'regular');
                    $previewStatus = (string) ($block['status'] ?? 'published');
                    $previewTitle = admin_preview_block_title($block);
                    $previewCopy = admin_preview_block_copy($block);
                    $previewTypeLabel = $contentManager->getTypeLabel($previewType);
                    $previewWidthLabel = $contentManager->getWidthLabel($previewWidth);
                    $previewHeightLabel = $contentManager->getHeightLabel($previewHeight);
                    $previewStatusLabel = $contentManager->getStatusLabel($previewStatus);
                    $previewBlockSlug = preg_replace('/[^a-z0-9_-]+/', '-', strtolower($previewType));
                    ?>
                    <article
                        class="admin-layout-preview-block admin-layout-preview-block--<?php echo htmlspecialchars((string) $previewBlockSlug, ENT_QUOTES, 'UTF-8'); ?><?php echo $previewStatus === 'hidden' ? 'admin-layout-preview-block--hidden' : ''; ?>"
                        data-layout-block-id="<?php echo htmlspecialchars((string) ($block['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                        data-layout-block-title="<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>"
                        data-layout-selectable="1"
                        tabindex="0"
                        data-preview-width="<?php echo htmlspecialchars($previewWidth, ENT_QUOTES, 'UTF-8'); ?>"
                        data-preview-height="<?php echo htmlspecialchars($previewHeight, ENT_QUOTES, 'UTF-8'); ?>"
                        data-preview-status="<?php echo htmlspecialchars($previewStatus, ENT_QUOTES, 'UTF-8'); ?>"
                        style="grid-column: span<?php echo $previewSpan; ?> / span<?php echo $previewSpan; ?>; --admin-block-height-factor:<?php echo htmlspecialchars((string) $previewHeightFactor, ENT_QUOTES, 'UTF-8'); ?>;">
                        <div class="admin-layout-preview-block__rail">
                            <button type="button" class="admin-layout-preview-icon is-active" data-layout-mode="preview" aria-label="Visualizar<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>" title="Visualizar bloco">
   <?php echo admin_icon('eye'); ?>
                            </button>
                            <button type="button" class="admin-layout-preview-icon" data-layout-mode="edit" aria-label="Editar layout de<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>" title="Editar layout do bloco">
   <?php echo admin_icon('edit'); ?>
                            </button>
                        </div>

                        <div class="admin-layout-preview-block__shell">
                            <span class="admin-layout-preview-block__type"><?php echo htmlspecialchars($previewTypeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            <strong class="admin-layout-preview-block__title"><?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                            <span class="admin-layout-preview-block__meta" data-layout-block-meta>
                                Ordem visual<?php echo (int) ($block['position'] ?? 0); ?> |<?php echo htmlspecialchars($previewWidthLabel, ENT_QUOTES, 'UTF-8'); ?> |<?php echo htmlspecialchars($previewHeightLabel, ENT_QUOTES, 'UTF-8'); ?> |<?php echo htmlspecialchars($previewStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <p class="admin-layout-preview-block__body"><?php echo htmlspecialchars($previewCopy, ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="admin-layout-preview-block__mockup" aria-hidden="true">
                                <span class="admin-layout-preview-block__line admin-layout-preview-block__line--strong"></span>
                                <span class="admin-layout-preview-block__line"></span>
                                <span class="admin-layout-preview-block__line admin-layout-preview-block__line--short"></span>
                            </div>
                        </div>

                        <div class="admin-layout-preview-panel" data-layout-block-panel hidden>
                            <div class="admin-layout-preview-panel__section">
                                <span class="admin-layout-preview-panel__label">Largura</span>
                                <div class="admin-layout-preview-choices" role="group" aria-label="Largura de<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>">
   <?php foreach ($widthOptions as $widthKey => $widthLabel): ?>
                                        <button
                                            type="button"
                                            class="admin-layout-choice<?php echo $previewWidth === (string) $widthKey ? 'is-selected' : ''; ?>"
                                            data-layout-choice="width"
                                            data-choice-value="<?php echo htmlspecialchars((string) $widthKey, ENT_QUOTES, 'UTF-8'); ?>"
                                            title="<?php echo htmlspecialchars((string) $widthLabel, ENT_QUOTES, 'UTF-8'); ?>">
           <?php echo htmlspecialchars(admin_visual_width_short_label((string) $widthKey), ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
   <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="admin-layout-preview-panel__section">
                                <span class="admin-layout-preview-panel__label">Altura</span>
                                <div class="admin-layout-preview-choices" role="group" aria-label="Altura de<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>">
   <?php foreach ($heightOptions as $heightKey => $heightLabel): ?>
                                        <button
                                            type="button"
                                            class="admin-layout-choice admin-layout-choice--text<?php echo $previewHeight === (string) $heightKey ? 'is-selected' : ''; ?>"
                                            data-layout-choice="height"
                                            data-choice-value="<?php echo htmlspecialchars((string) $heightKey, ENT_QUOTES, 'UTF-8'); ?>">
           <?php echo htmlspecialchars((string) $heightLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </button>
   <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="admin-layout-preview-panel__actions">
                                <button type="button" class="admin-layout-preview-action admin-layout-preview-action--ghost" data-layout-visibility-toggle>
                                    <span data-layout-visibility-label><?php echo $previewStatus === 'hidden' ? 'Publicar bloco' : 'Ocultar bloco'; ?></span>
                                </button>

                                <button type="button" class="admin-layout-preview-action" data-layout-move="-1">
   <?php echo admin_icon('up'); ?>
                                    <span>Subir</span>
                                </button>

                                <button type="button" class="admin-layout-preview-action" data-layout-move="1">
   <?php echo admin_icon('down'); ?>
                                    <span>Descer</span>
                                </button>

                                <button type="button" class="admin-layout-preview-link" data-layout-edit-content>Editar conteudo</button>
                            </div>
                        </div>
                    </article>
   <?php endforeach; ?>
            </div>
        </div>
    </article>
<?php
}

if (empty($_SESSION['admin_csrf'])) {
    $_SESSION['admin_csrf'] = bin2hex(random_bytes(16));
}

$csrfToken = $_SESSION['admin_csrf'];
$flash = $_SESSION['admin_flash'] ?? null;
unset($_SESSION['admin_flash']);

$userFormErrors = [];
$projectFormErrors = [];
$documentFormErrors = [];
$roleRequestErrors = [];
$contentFormErrors = [];
$partnerFormErrors = [];
$contentLayoutErrors = [];
$userFormOverrides = null;
$projectFormOverrides = null;
$documentFormOverrides = null;
$contentFormOverrides = null;
$partnerFormOverrides = null;
$contentLayoutOverrides = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = (string) ($_POST['csrf_token'] ?? '');

    if (!hash_equals($csrfToken, $postedToken)) {
        $flash = ['type' => 'erro', 'message' => 'Sessao expirada. Recarregue a pagina e tente novamente.'];
    } else {
        $action = (string) ($_POST['action'] ?? '');

        switch ($action) {
            case 'save_user':
                $userId = isset($_POST['user_id']) && $_POST['user_id'] !== '' ? (int) $_POST['user_id'] : null;
                $submittedUser = [
                    'id' => $userId ?? '',
                    'username' => trim((string) ($_POST['username'] ?? '')),
                    'fullname' => trim((string) ($_POST['fullname'] ?? '')),
                    'email' => trim((string) ($_POST['email'] ?? '')),
                    'role' => trim((string) ($_POST['role'] ?? 'member')),
                ];

                $result = $auth->adminSaveUser($userId, [
                    'username' => $submittedUser['username'],
                    'fullname' => $submittedUser['fullname'],
                    'email' => $submittedUser['email'],
                    'role' => $submittedUser['role'],
                    'password' => (string) ($_POST['password'] ?? ''),
                ]);

                if ($result['success']) {
                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Usuario criado com sucesso.' : 'Usuario atualizado com sucesso.'
                    );
                    admin_redirect('users');
                }

                $userFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o usuario.'];
                $userFormOverrides = $submittedUser;
                break;

            case 'delete_user':
                $userId = (int) ($_POST['user_id'] ?? 0);
                $result = $auth->deleteUser($userId);

                if ($result['success']) {
                    $orphanedProjects = $projectManager->clearProjectsForUser($userId);
                    $removedOrientations = $orientationManager->deleteOrientationsForUser($userId);
                    $message = 'Usuario removido com sucesso.';
                    if ($orphanedProjects > 0) {
                        $message .= ' ' . $orphanedProjects . ' projeto(s) ficaram sem responsavel.';
                    }
                    if ($removedOrientations > 0) {
                        $message .= ' ' . $removedOrientations . ' orientacao(oes) vinculada(s) a essa conta foram removidas.';
                    }

                    admin_set_flash('sucesso', $message);
                    admin_redirect('users');
                }

                $flash = [
                    'type' => 'erro',
                    'message' => $result['errors'][0] ?? 'Nao foi possivel remover o usuario.',
                ];
                break;

            case 'save_project':
                $projectId = trim((string) ($_POST['project_id'] ?? ''));
                $submittedProject = [
                    'id' => $projectId,
                    'user_id' => trim((string) ($_POST['user_id'] ?? '')),
                    'title' => trim((string) ($_POST['title'] ?? '')),
                    'category' => trim((string) ($_POST['category'] ?? '')),
                    'tags' => array_values(array_filter(array_map('strval', (array) ($_POST['tags'] ?? [])))),
                    'status' => trim((string) ($_POST['status'] ?? 'active')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'participation_info' => trim((string) ($_POST['participation_info'] ?? '')),
                    'image_path' => trim((string) ($_POST['image_path'] ?? '')),
                ];
                $uploadedProjectImage = isset($_FILES['image_file']) && is_array($_FILES['image_file'])
                    ? $_FILES['image_file']
                    : null;
                $uploadedProjectDocument = isset($_FILES['document_file']) && is_array($_FILES['document_file'])
                    ? $_FILES['document_file']
                    : null;
                $hasProjectDocumentUpload = is_array($uploadedProjectDocument)
                    && isset($uploadedProjectDocument['error'])
                    && (int) ($uploadedProjectDocument['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

                if ($projectId === '' && !$hasProjectDocumentUpload) {
                    $projectFormErrors = ['Envie a documentacao obrigatoria em PDF ou DOCX para criar o projeto.'];
                    $projectFormOverrides = $submittedProject;
                    break;
                }

                if ($hasProjectDocumentUpload) {
                    $documentValidation = $workspaceManager->validateProjectDocumentUpload($uploadedProjectDocument);
                    if (!$documentValidation['success']) {
                        $projectFormErrors = [$documentValidation['error'] ?? 'Documento invalido.'];
                        $projectFormOverrides = $submittedProject;
                        break;
                    }
                }

                $result = $projectManager->adminSaveProject($projectId !== '' ? $projectId : null, [
                    'user_id' => $submittedProject['user_id'],
                    'title' => $submittedProject['title'],
                    'category' => $submittedProject['category'],
                    'tags' => $submittedProject['tags'],
                    'status' => $submittedProject['status'],
                    'description' => $submittedProject['description'],
                    'participation_info' => $submittedProject['participation_info'],
                    'image_path' => $submittedProject['image_path'],
                ], $uploadedProjectImage);

                if ($result['success']) {
                    $savedProject = $result['project'];
                    if ($hasProjectDocumentUpload) {
                        $documentResult = $workspaceManager->uploadProjectDocument($savedProject, $currentUser, $uploadedProjectDocument);

                        if (!$documentResult['success']) {
                            if (!empty($result['created'])) {
                                $projectManager->deleteProject((string) ($savedProject['id'] ?? ''));
                            }

                            $projectFormErrors = [$documentResult['error'] ?? ($documentResult['errors'][0] ?? 'Nao foi possivel anexar a documentacao.')];
                            $projectFormOverrides = $submittedProject;
                            break;
                        }

                        $workspaceManager->notifyAdministrators(
                            $users,
                            'document_pending',
                            'Documento aguardando aprovacao',
                            'O projeto "' . (string) ($savedProject['title'] ?? 'Projeto') . '" recebeu uma nova documentacao.',
                            (string) ($savedProject['id'] ?? ''),
                            'admin.php#document-authentication',
                            (int) ($currentUser['id'] ?? 0)
                        );
                    }

                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Projeto criado e documentacao enviada para avaliacao.' : 'Projeto atualizado com sucesso.'
                    );
                    admin_redirect('projects');
                }

                $projectFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o projeto.'];
                $projectFormOverrides = $submittedProject;
                break;

            case 'delete_project':
                $projectId = trim((string) ($_POST['project_id'] ?? ''));

                if ($projectManager->deleteProject($projectId)) {
                    $workspaceManager->deleteProjectData($projectId);
                    $clearedOrientations = $orientationManager->clearProjectReferences($projectId);
                    $message = 'Projeto removido com sucesso.';
                    if ($clearedOrientations > 0) {
                        $message .= ' ' . $clearedOrientations . ' orientacao(oes) perderam o vinculo com este projeto.';
                    }

                    admin_set_flash('sucesso', $message);
                    admin_redirect('projects');
                }

                $flash = ['type' => 'erro', 'message' => 'Nao foi possivel remover o projeto.'];
                break;

            case 'review_project_document':
                $documentId = trim((string) ($_POST['document_id'] ?? ''));
                $decision = trim((string) ($_POST['decision'] ?? ''));
                $reviewNotes = trim((string) ($_POST['review_notes'] ?? ''));
                $result = $workspaceManager->reviewProjectDocument($documentId, $currentUser, $decision, $reviewNotes);

                if ($result['success']) {
                    $document = $result['document'];
                    $project = $projectManager->getProject((string) ($document['project_id'] ?? ''));
                    $projectTitle = is_array($project) ? (string) ($project['title'] ?? 'Projeto') : 'Projeto';
                    $isApproved = (string) ($document['status'] ?? '') === 'approved';
                    $messageTitle = $isApproved ? 'Projeto autenticado' : 'Documento rejeitado';
                    $messageBody = $isApproved
                        ? 'O projeto "' . $projectTitle . '" teve a documentacao aprovada.'
                        : 'A documentacao do projeto "' . $projectTitle . '" foi rejeitada.';

                    $workspaceManager->createNotification(
                        (int) ($document['uploaded_by_user_id'] ?? 0),
                        $isApproved ? 'document_approved' : 'document_rejected',
                        $messageTitle,
                        $messageBody,
                        (string) ($document['project_id'] ?? ''),
                        'project-workspace.php?id=' . rawurlencode((string) ($document['project_id'] ?? '')),
                        (int) ($currentUser['id'] ?? 0)
                    );

                    if (is_array($project) && (int) ($project['user_id'] ?? 0) !== (int) ($document['uploaded_by_user_id'] ?? 0)) {
                        $workspaceManager->createNotification(
                            (int) ($project['user_id'] ?? 0),
                            $isApproved ? 'project_authenticated' : 'document_rejected',
                            $messageTitle,
                            $messageBody,
                            (string) ($project['id'] ?? ''),
                            'project-workspace.php?id=' . rawurlencode((string) ($project['id'] ?? '')),
                            (int) ($currentUser['id'] ?? 0)
                        );
                    }

                    admin_set_flash('sucesso', $isApproved ? 'Documento aprovado e projeto autenticado.' : 'Documento rejeitado.');
                    admin_redirect('document-authentication');
                }

                $documentFormErrors = $result['errors'] ?? ['Nao foi possivel revisar o documento.'];
                break;

            case 'review_role_request':
                $requestId = trim((string) ($_POST['request_id'] ?? ''));
                $decision = trim((string) ($_POST['decision'] ?? ''));
                $reviewNotes = trim((string) ($_POST['review_notes'] ?? ''));
                $reviewResult = $profileExtrasManager->reviewRoleRequest($requestId, (int) ($currentUser['id'] ?? 0), $decision, $reviewNotes);

                if (!$reviewResult['success']) {
                    $roleRequestErrors = $reviewResult['errors'] ?? ['Nao foi possivel revisar a solicitacao.'];
                    break;
                }

                $roleRequest = $reviewResult['request'];
                $requestUser = $auth->getUserById((int) ($roleRequest['user_id'] ?? 0));

                if ($decision === 'approved' && is_array($requestUser)) {
                    $saveResult = $auth->adminSaveUser((int) ($requestUser['id'] ?? 0), [
                        'username' => (string) ($requestUser['username'] ?? ''),
                        'fullname' => (string) ($requestUser['fullname'] ?? ''),
                        'email' => (string) ($requestUser['email'] ?? ''),
                        'role' => (string) ($roleRequest['requested_role'] ?? ''),
                        'password' => '',
                    ]);

                    if (!$saveResult['success']) {
                        $roleRequestErrors = $saveResult['errors'] ?? ['Nao foi possivel promover o usuario.'];
                        break;
                    }
                }

                if (is_array($requestUser)) {
                    $workspaceManager->createNotification(
                        (int) ($requestUser['id'] ?? 0),
                        $decision === 'approved' ? 'role_request_approved' : 'role_request_rejected',
                        $decision === 'approved' ? 'Nivel aprovado' : 'Solicitacao de nivel recusada',
                        $decision === 'approved'
                            ? 'Sua solicitacao para ' . $profileExtrasManager->getRoleRequestLabel((string) ($roleRequest['requested_role'] ?? '')) . ' foi aprovada.'
                            : 'Sua solicitacao de aumento de nivel foi recusada.',
                        null,
                        'profile.php',
                        (int) ($currentUser['id'] ?? 0)
                    );
                }

                admin_set_flash('sucesso', $decision === 'approved' ? 'Solicitacao aprovada e usuario promovido.' : 'Solicitacao rejeitada.');
                admin_redirect('role-requests');
                break;

            case 'save_partner':
                $partnerId = trim((string) ($_POST['partner_id'] ?? ''));
                $submittedPartner = [
                    'id' => $partnerId,
                    'name' => trim((string) ($_POST['name'] ?? '')),
                    'description' => trim((string) ($_POST['description'] ?? '')),
                    'image_path' => trim((string) ($_POST['image_path'] ?? '')),
                ];
                $uploadedPartnerImage = isset($_FILES['image_file']) && is_array($_FILES['image_file'])
                    ? $_FILES['image_file']
                    : null;

                $result = $partnerManager->adminSavePartner($partnerId !== '' ? $partnerId : null, $submittedPartner, $uploadedPartnerImage);

                if ($result['success']) {
                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Parceiro criado com sucesso.' : 'Parceiro atualizado com sucesso.'
                    );
                    admin_redirect('partners');
                }

                $partnerFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o parceiro.'];
                $partnerFormOverrides = $submittedPartner;
                break;

            case 'delete_partner':
                $partnerId = trim((string) ($_POST['partner_id'] ?? ''));

                if ($partnerManager->deletePartner($partnerId)) {
                    admin_set_flash('sucesso', 'Parceiro removido com sucesso.');
                    admin_redirect('partners');
                }

                $flash = ['type' => 'erro', 'message' => 'Nao foi possivel remover o parceiro.'];
                break;

            case 'save_content_block':
                $blockId = trim((string) ($_POST['block_id'] ?? ''));
                $submittedBlock = [
                    'id' => $blockId,
                    'page_key' => trim((string) ($_POST['page_key'] ?? 'contact')),
                    'type' => trim((string) ($_POST['type'] ?? '')),
                    'name' => trim((string) ($_POST['name'] ?? '')),
                    'eyebrow' => trim((string) ($_POST['eyebrow'] ?? '')),
                    'title' => trim((string) ($_POST['title'] ?? '')),
                    'body' => trim((string) ($_POST['body'] ?? '')),
                    'items_text' => trim((string) ($_POST['items_text'] ?? '')),
                    'cta_label' => trim((string) ($_POST['cta_label'] ?? '')),
                    'cta_url' => trim((string) ($_POST['cta_url'] ?? '')),
                    'embed_url' => trim((string) ($_POST['embed_url'] ?? '')),
                    'media_url' => trim((string) ($_POST['media_url'] ?? '')),
                    'media_dark_url' => trim((string) ($_POST['media_dark_url'] ?? '')),
                    'media_alt' => trim((string) ($_POST['media_alt'] ?? '')),
                    'width' => trim((string) ($_POST['width'] ?? 'half')),
                    'height' => trim((string) ($_POST['height'] ?? 'regular')),
                    'position' => trim((string) ($_POST['position'] ?? '')),
                    'status' => trim((string) ($_POST['status'] ?? 'published')),
                    'show_context_note' => isset($_POST['show_context_note']) ? '1' : '0',
                ];

                $result = $contentManager->adminSaveBlock($blockId !== '' ? $blockId : null, $submittedBlock);

                if ($result['success']) {
                    admin_set_flash(
                        'sucesso',
                        $result['created'] ? 'Bloco de conteudo criado com sucesso.' : 'Bloco de conteudo atualizado com sucesso.'
                    );
                    admin_redirect('content');
                }

                $contentFormErrors = $result['errors'] ?? ['Nao foi possivel salvar o bloco de conteudo.'];
                $contentFormOverrides = $submittedBlock;
                break;

            case 'delete_content_block':
                $blockId = trim((string) ($_POST['block_id'] ?? ''));

                if ($contentManager->deleteBlock($blockId)) {
                    admin_set_flash('sucesso', 'Bloco de conteudo removido com sucesso.');
                    admin_redirect('content');
                }

                $flash = ['type' => 'erro', 'message' => 'Nao foi possivel remover o bloco de conteudo.'];
                break;

            case 'save_content_layout':
                $layoutPageKey = trim((string) ($_POST['layout_page_key'] ?? 'about'));
                $submittedLayout = [
                    'page_key' => $layoutPageKey,
                    'grid_style' => trim((string) ($_POST['grid_style'] ?? 'dense')),
                    'columns' => trim((string) ($_POST['columns'] ?? '4')),
                    'mobile_columns' => trim((string) ($_POST['mobile_columns'] ?? '1')),
                    'gap' => trim((string) ($_POST['gap'] ?? '24')),
                    'container_width' => trim((string) ($_POST['container_width'] ?? '1220')),
                    'block_padding' => trim((string) ($_POST['block_padding'] ?? '32')),
                    'block_min_height' => trim((string) ($_POST['block_min_height'] ?? '210')),
                ];
                $submittedBlockStates = [];
                $layoutBlocksJson = trim((string) ($_POST['layout_blocks_json'] ?? ''));

                if ($layoutBlocksJson !== '') {
                    $decodedLayoutBlocks = json_decode($layoutBlocksJson, true);

                    if (!is_array($decodedLayoutBlocks)) {
                        $pageLabel = $contentManager->getPageLabel($layoutPageKey);
                        $contentLayoutErrors = ['O editor visual da pagina ' . $pageLabel . ' enviou um estado invalido. Recarregue a pagina e tente novamente.'];
                        $contentLayoutOverrides = $submittedLayout;
                        break;
                    }

                    $submittedBlockStates = $decodedLayoutBlocks;
                }

                $result = $contentManager->adminSaveLayoutBuilder($layoutPageKey, $submittedLayout, $submittedBlockStates);

                if ($result['success']) {
                    admin_set_flash('sucesso', 'Composicao visual da pagina ' . $contentManager->getPageLabel($layoutPageKey) . ' salva com sucesso.');
                    admin_redirect('content');
                }

                $contentLayoutErrors = $result['errors'] ?? ['Nao foi possivel salvar a estrutura da pagina.'];
                $contentLayoutOverrides = $submittedLayout;
                break;
        }
    }
}

$users = $auth->listUsers();
$partners = $partnerManager->listPartners();
$contentBlocks = $contentManager->listBlocks(null, false);
$layoutBuilderPageKeys = [];

foreach ($contentPageOptions as $pageKey => $pageDefinition) {
    if (!empty($pageDefinition['supports_layout_builder'])) {
        $layoutBuilderPageKeys[] = (string) $pageKey;
    }
}

$preferredLayoutBuilderOrder = ['contact', 'about', 'thematic_areas'];
usort($layoutBuilderPageKeys, static function (string $left, string $right) use ($preferredLayoutBuilderOrder): int {
    $leftIndex = array_search($left, $preferredLayoutBuilderOrder, true);
    $rightIndex = array_search($right, $preferredLayoutBuilderOrder, true);

    if ($leftIndex === false && $rightIndex === false) {
        return strcmp($left, $right);
    }

    if ($leftIndex === false) {
        return 1;
    }

    if ($rightIndex === false) {
        return -1;
    }

    return $leftIndex <=> $rightIndex;
});

$pageBlocksByKey = [];
$pageLayoutsByKey = [];

foreach ($layoutBuilderPageKeys as $pageKey) {
    $pageBlocksByKey[$pageKey] = $contentManager->getPageBlocks($pageKey, false);
    $pageLayoutsByKey[$pageKey] = $contentManager->getPageLayout($pageKey);
}

$contactContentBlocks = $pageBlocksByKey['contact'] ?? [];
$thematicContentBlocks = $pageBlocksByKey['thematic_areas'] ?? [];
$aboutContentBlocks = $pageBlocksByKey['about'] ?? [];
$projects = $projectManager->getAllProjects();
$projectStats = $projectManager->getProjectStats();
$pendingProjectDocuments = $workspaceManager->getPendingProjectDocuments();
$pendingDocumentCount = count($pendingProjectDocuments);
$pendingRoleRequests = $profileExtrasManager->listRoleRequests('pending');
$pendingRoleRequestCount = count($pendingRoleRequests);
$partnerCount = count($partners);
$contentStats = [
    'total' => count($contentBlocks),
    'published' => count(array_filter($contentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
    'contact_total' => count($contactContentBlocks),
    'contact_published' => count(array_filter($contactContentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
    'thematic_total' => count($thematicContentBlocks),
    'thematic_published' => count(array_filter($thematicContentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
    'about_total' => count($aboutContentBlocks),
    'about_published' => count(array_filter($aboutContentBlocks, static function (array $block): bool {
        return ($block['status'] ?? 'published') === 'published';
    })),
];

$userMap = [];
$projectCountByUser = [];
$orientationSupervisionCountByUser = [];
$orientationAssignedCountByUser = [];
$adminCount = 0;
$allOrientations = $orientationManager->listOrientations();

foreach ($users as $user) {
    $userMap[(int) $user['id']] = $user;
    if ($auth->isAdmin($user)) {
        $adminCount++;
    }
}

foreach ($projects as $project) {
    if (($project['user_id'] ?? null) !== null) {
        $ownerId = (int) $project['user_id'];
        $projectCountByUser[$ownerId] = ($projectCountByUser[$ownerId] ?? 0) + 1;
    }
}

foreach ($allOrientations as $orientation) {
    $supervisorId = (int) ($orientation['supervisor_id'] ?? 0);
    $researcherId = (int) ($orientation['researcher_id'] ?? 0);

    if ($supervisorId > 0) {
        $orientationSupervisionCountByUser[$supervisorId] = ($orientationSupervisionCountByUser[$supervisorId] ?? 0) + 1;
    }

    if ($researcherId > 0) {
        $orientationAssignedCountByUser[$researcherId] = ($orientationAssignedCountByUser[$researcherId] ?? 0) + 1;
    }
}

$editingUser = null;
if (isset($_GET['edit_user']) && $_GET['edit_user'] !== '') {
    $editingUser = $auth->getUserById((int) $_GET['edit_user']);
}

$editingProject = null;
if (!empty($_GET['edit_project'])) {
    $candidateProject = $projectManager->getProject((string) $_GET['edit_project']);
    if (is_array($candidateProject)) {
        $editingProject = $candidateProject;
    }
}

$editingContentBlock = null;
if (!empty($_GET['edit_block'])) {
    $candidateBlock = $contentManager->getBlock((string) $_GET['edit_block']);
    if (is_array($candidateBlock)) {
        $editingContentBlock = $candidateBlock;
    }
}

$editingPartner = null;
if (!empty($_GET['edit_partner'])) {
    $candidatePartner = $partnerManager->getPartner((string) $_GET['edit_partner']);
    if (is_array($candidatePartner)) {
        $editingPartner = $candidatePartner;
    }
}

$userForm = [
    'id' => $editingUser['id'] ?? '',
    'username' => $editingUser['username'] ?? '',
    'fullname' => $editingUser['fullname'] ?? '',
    'email' => $editingUser['email'] ?? '',
    'role' => $editingUser['role'] ?? 'member',
];

if ($userFormOverrides !== null) {
    $userForm = array_merge($userForm, $userFormOverrides);
}

$editingUserId = isset($editingUser['id']) ? (int) $editingUser['id'] : 0;
$editingUserRoleKey = $editingUserId > 0 ? $auth->getRoleKey($editingUser) : $auth->getRoleKey($userForm['role']);
$editingUserRoleLabel = $editingUserId > 0 ? $auth->getRoleLabel($editingUser) : $auth->getRoleLabel($userForm['role']);
$editingUserRoleClass = 'admin-pill--' . str_replace('_', '-', $editingUserRoleKey);
$editingUserProjectCount = $editingUserId > 0 ? (int) ($projectCountByUser[$editingUserId] ?? 0) : 0;
$editingUserSupervisionCount = $editingUserId > 0 ? (int) ($orientationSupervisionCountByUser[$editingUserId] ?? 0) : 0;
$editingUserAssignedCount = $editingUserId > 0 ? (int) ($orientationAssignedCountByUser[$editingUserId] ?? 0) : 0;
$editingUserProviderLabel = $editingUserId > 0 ? admin_provider_label((string) ($editingUser['provider'] ?? 'local')) : 'Local';
$editingUserCreatedAt = $editingUserId > 0 ? admin_format_datetime($editingUser['created_at'] ?? null) : '-';
$editingUserUpdatedAt = $editingUserId > 0 ? admin_format_datetime($editingUser['updated_at'] ?? null) : '-';
$editingUserIsSelf = $editingUserId > 0 && $editingUserId === (int) $currentUser['id'];

$projectForm = [
    'id' => $editingProject['id'] ?? '',
    'user_id' => isset($editingProject['user_id']) && $editingProject['user_id'] !== null ? (string) $editingProject['user_id'] : '',
    'title' => $editingProject['title'] ?? '',
    'category' => $editingProject['category'] ?? $projectManager->getDefaultThematicArea(),
    'tags' => $editingProject['tags'] ?? [],
    'status' => $editingProject['status'] ?? 'active',
    'description' => $editingProject['description'] ?? '',
    'participation_info' => $editingProject['participation_info'] ?? '',
    'image_path' => $editingProject['image_path'] ?? '',
];

if ($projectFormOverrides !== null) {
    $projectForm = array_merge($projectForm, $projectFormOverrides);
}

$defaultContentPageKey = $editingContentBlock['page_key'] ?? 'contact';
$contentForm = [
    'id' => $editingContentBlock['id'] ?? '',
    'page_key' => $defaultContentPageKey,
    'type' => $editingContentBlock['type'] ?? $contentManager->getDefaultTypeForPage($defaultContentPageKey),
    'name' => $editingContentBlock['name'] ?? '',
    'eyebrow' => $editingContentBlock['eyebrow'] ?? '',
    'title' => $editingContentBlock['title'] ?? '',
    'body' => $editingContentBlock['body'] ?? '',
    'items_text' => isset($editingContentBlock['items']) ? $contentManager->formatItemsForTextarea($editingContentBlock['items']) : '',
    'cta_label' => $editingContentBlock['cta_label'] ?? '',
    'cta_url' => $editingContentBlock['cta_url'] ?? '',
    'embed_url' => $editingContentBlock['embed_url'] ?? '',
    'media_url' => $editingContentBlock['media_url'] ?? '',
    'media_dark_url' => $editingContentBlock['media_dark_url'] ?? '',
    'media_alt' => $editingContentBlock['media_alt'] ?? '',
    'width' => $editingContentBlock['width'] ?? $contentManager->getDefaultWidthForPage($defaultContentPageKey),
    'height' => $editingContentBlock['height'] ?? $contentManager->getDefaultHeightForPage($defaultContentPageKey),
    'position' => isset($editingContentBlock['position']) ? (string) $editingContentBlock['position'] : (string) $contentManager->getNextPosition($defaultContentPageKey),
    'status' => $editingContentBlock['status'] ?? 'published',
    'show_context_note' => !empty($editingContentBlock['show_context_note']),
];

if ($contentFormOverrides !== null) {
    $contentForm = array_merge($contentForm, $contentFormOverrides);
    $contentForm['show_context_note'] = !empty($contentFormOverrides['show_context_note']);
}

$partnerForm = [
    'id' => $editingPartner['id'] ?? '',
    'name' => $editingPartner['name'] ?? '',
    'description' => $editingPartner['description'] ?? '',
    'image_path' => $editingPartner['image_path'] ?? '',
];

if ($partnerFormOverrides !== null) {
    $partnerForm = array_merge($partnerForm, $partnerFormOverrides);
}

if (!$contentManager->isTypeAllowedForPage((string) $contentForm['page_key'], (string) $contentForm['type'])) {
    $contentForm['type'] = $contentManager->getDefaultTypeForPage((string) $contentForm['page_key']);
}

if (!$contentManager->isWidthAllowedForPage((string) $contentForm['page_key'], (string) $contentForm['width'])) {
    $contentForm['width'] = $contentManager->getDefaultWidthForPage((string) $contentForm['page_key']);
}

$layoutFormsByPage = [];
$layoutWidthOptionsByPage = [];

foreach ($layoutBuilderPageKeys as $pageKey) {
    $pageLayout = $pageLayoutsByKey[$pageKey] ?? [];
    $layoutFormsByPage[$pageKey] = [
        'page_key' => $pageKey,
        'grid_style' => $pageLayout['grid_style'] ?? 'standard',
        'columns' => isset($pageLayout['columns']) ? (string) $pageLayout['columns'] : (string) admin_layout_builder_columns_max($pageKey),
        'mobile_columns' => isset($pageLayout['mobile_columns']) ? (string) $pageLayout['mobile_columns'] : '1',
        'gap' => isset($pageLayout['gap']) ? (string) $pageLayout['gap'] : '24',
        'container_width' => isset($pageLayout['container_width']) ? (string) $pageLayout['container_width'] : '1220',
        'block_padding' => isset($pageLayout['block_padding']) ? (string) $pageLayout['block_padding'] : '32',
        'block_min_height' => isset($pageLayout['block_min_height']) ? (string) $pageLayout['block_min_height'] : '210',
    ];
    $layoutWidthOptionsByPage[$pageKey] = $contentManager->getAllowedWidthDefinitionsForPage($pageKey);
}

$layoutOverridePageKey = (string) ($contentLayoutOverrides['page_key'] ?? '');
if ($contentLayoutOverrides !== null && $contentManager->pageSupportsLayoutBuilder($layoutOverridePageKey)) {
    $contentForm['page_key'] = $layoutOverridePageKey;
    if (!$contentManager->isTypeAllowedForPage((string) $contentForm['page_key'], (string) $contentForm['type'])) {
        $contentForm['type'] = $contentManager->getDefaultTypeForPage((string) $contentForm['page_key']);
    }
    if (!$contentManager->isWidthAllowedForPage((string) $contentForm['page_key'], (string) $contentForm['width'])) {
        $contentForm['width'] = $contentManager->getDefaultWidthForPage((string) $contentForm['page_key']);
    }
    $layoutFormsByPage[$layoutOverridePageKey] = array_merge($layoutFormsByPage[$layoutOverridePageKey] ?? [], $contentLayoutOverrides);
}

$currentRoleLabel = $auth->getRoleLabel($currentUser);
$layoutHeightOptions = $contentManager->getHeightDefinitions();
?>

<?php include_once 'includes/header.php'; ?>

<main class="page-shell app-shell admin-app-shell">
    <section class="panel-hero">
        <div class="panel-hero-main">
            <p class="eyebrow">Administracao</p>
            <h1>Painel mestre do portal</h1>
            <p class="hero-copy">Controle usuarios, niveis de acesso, projetos, parceiros e o conteudo global em blocos. Agora o builder ja abastece Contato, Areas Tematicas e a pagina Sobre com estrutura editavel pelo admin, enquanto a home passa a refletir esses conteudos e o carrossel institucional.</p>

            <div class="hero-actions">
                <a class="dashboard-btn" href="#users">Usuarios</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#role-requests">Niveis</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#document-authentication">Documentos</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#projects">Projetos</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#partners">Parceiros</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="#content">Conteudo global</a>
                <a class="dashboard-btn dashboard-btn--ghost" href="dashboard.php">Voltar ao dashboard</a>
            </div>
        </div>

        <aside class="panel-hero-aside">
            <span class="dashboard-badge">Admin ativo</span>
            <h2>Conta atual</h2>
            <p><?php echo htmlspecialchars((string) ($currentUser['fullname'] ?? $currentUser['username']), ENT_QUOTES, 'UTF-8'); ?></p>
            <ul class="hero-meta-list">
                <li>Usuario: @<?php echo htmlspecialchars((string) $currentUser['username'], ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Email:<?php echo htmlspecialchars((string) ($currentUser['email'] ?: 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></li>
                <li>Nivel:<?php echo htmlspecialchars($currentRoleLabel, ENT_QUOTES, 'UTF-8'); ?></li>
            </ul>
        </aside>
    </section>

<?php if ($flash): ?>
        <div class="mensagem<?php echo htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8'); ?>">
  <?php echo htmlspecialchars((string) $flash['message'], ENT_QUOTES, 'UTF-8'); ?>
        </div>
<?php endif; ?>

    <section class="metrics-grid">
        <article class="metric-card">
            <span class="metric-label">Usuarios</span>
            <strong class="metric-value"><?php echo count($users); ?></strong>
            <p>Contas registradas na plataforma.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Admins</span>
            <strong class="metric-value"><?php echo $adminCount; ?></strong>
            <p>Perfis com permissao total.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Projetos</span>
            <strong class="metric-value"><?php echo (int) $projectStats['total']; ?></strong>
            <p>Total de itens salvos no portal.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Parceiros</span>
            <strong class="metric-value"><?php echo (int) $partnerCount; ?></strong>
            <p>Cards alimentando o carrossel institucional da home.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Docs pendentes</span>
            <strong class="metric-value"><?php echo (int) $pendingDocumentCount; ?></strong>
            <p>Documentos aguardando aprovacao administrativa.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Niveis</span>
            <strong class="metric-value"><?php echo (int) $pendingRoleRequestCount; ?></strong>
            <p>Solicitacoes de aumento de nivel pendentes.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Blocos globais</span>
            <strong class="metric-value"><?php echo (int) $contentStats['total']; ?></strong>
            <p><?php echo (int) $contentStats['published']; ?> publicados no total entre as paginas administraveis.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Contato</span>
            <strong class="metric-value"><?php echo (int) $contentStats['contact_total']; ?></strong>
            <p><?php echo (int) $contentStats['contact_published']; ?> publicados na pagina de contato.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Areas tematicas</span>
            <strong class="metric-value"><?php echo (int) $contentStats['thematic_total']; ?></strong>
            <p><?php echo (int) $contentStats['thematic_published']; ?> publicados na pagina de Areas Tematicas.</p>
        </article>
        <article class="metric-card">
            <span class="metric-label">Sobre</span>
            <strong class="metric-value"><?php echo (int) $contentStats['about_total']; ?></strong>
            <p><?php echo (int) $contentStats['about_published']; ?> publicados na pagina Sobre.</p>
        </article>
    </section>

    <section id="role-requests" class="admin-workspace">
        <article class="panel-card admin-workspace__full">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Permissoes</p>
                    <h2>Solicitacoes de aumento de nivel</h2>
                    <p class="admin-subtitle">Revise pedidos para Pesquisador Academico ou Pesquisador Pleno e promova a conta quando fizer sentido.</p>
                </div>
            </div>

            <?php foreach ($roleRequestErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <?php if (empty($pendingRoleRequests)): ?>
                <p class="admin-empty">Nenhuma solicitacao de nivel pendente.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nivel solicitado</th>
                                <th>Data</th>
                                <th>Revisao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingRoleRequests as $roleRequest): ?>
                                <?php $requestUser = $userMap[(int) ($roleRequest['user_id'] ?? 0)] ?? null; ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($requestUser['fullname'] ?? $requestUser['username'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) ($requestUser['email'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars($profileExtrasManager->getRoleRequestLabel((string) ($roleRequest['requested_role'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($roleRequest['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" class="admin-review-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="review_role_request">
                                            <input type="hidden" name="request_id" value="<?php echo htmlspecialchars((string) ($roleRequest['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <textarea name="review_notes" rows="2" placeholder="Observacao opcional"></textarea>
                                            <div class="table-actions">
                                                <button type="submit" name="decision" value="approved" class="dashboard-btn admin-btn-small">Aprovar</button>
                                                <button type="submit" name="decision" value="rejected" class="dashboard-btn admin-btn-danger">Rejeitar</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section id="users" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Usuarios</p>
                    <h2><?php echo $userForm['id'] !== '' ? 'Editar perfil do usuario' : 'Novo usuario'; ?></h2>
                    <p class="admin-hierarchy-note">Hierarquia atual: Usuario, Pesquisador Academico, Pesquisador Associado, Pesquisador Pleno e Admin. O editor abaixo ajusta identidade, contato, permissao e senha da conta selecionada.</p>
                </div>

   <?php if ($userForm['id'] !== ''): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php#users">Limpar formulario</a>
   <?php endif; ?>
            </div>

  <?php if ($editingUserId > 0): ?>
                <section class="admin-user-profile-summary">
                    <div class="admin-user-profile-summary__head">
                        <div>
                            <p class="eyebrow">Perfil em edicao</p>
                            <h3><?php echo htmlspecialchars((string) ($editingUser['fullname'] ?? $editingUser['username']), ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="admin-meta">@<?php echo htmlspecialchars((string) ($editingUser['username'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></p>
                        </div>

                        <div class="admin-user-profile-summary__badges">
                            <span class="admin-pill<?php echo htmlspecialchars($editingUserRoleClass, ENT_QUOTES, 'UTF-8'); ?>">
   <?php echo htmlspecialchars($editingUserRoleLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </span>
   <?php if ($editingUserIsSelf): ?>
                                <span class="admin-self-tag">Conta atual</span>
   <?php endif; ?>
                        </div>
                    </div>

                    <div class="admin-user-profile-stats">
                        <article class="admin-user-profile-stat">
                            <span>Projetos</span>
                            <strong><?php echo $editingUserProjectCount; ?></strong>
                        </article>
                        <article class="admin-user-profile-stat">
                            <span>Supervisoes</span>
                            <strong><?php echo $editingUserSupervisionCount; ?></strong>
                        </article>
                        <article class="admin-user-profile-stat">
                            <span>Orientacoes recebidas</span>
                            <strong><?php echo $editingUserAssignedCount; ?></strong>
                        </article>
                    </div>

                    <ul class="dashboard-list admin-user-profile-list">
                        <li>
                            <span>Email atual</span>
                            <strong><?php echo htmlspecialchars((string) (($editingUser['email'] ?? '') !== '' ? $editingUser['email'] : 'Nao informado'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        </li>
                        <li>
                            <span>Origem da conta</span>
                            <strong><?php echo htmlspecialchars($editingUserProviderLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </li>
                        <li>
                            <span>Criado em</span>
                            <strong><?php echo htmlspecialchars($editingUserCreatedAt, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </li>
                        <li>
                            <span>Ultima atualizacao</span>
                            <strong><?php echo htmlspecialchars($editingUserUpdatedAt, ENT_QUOTES, 'UTF-8'); ?></strong>
                        </li>
                    </ul>
                </section>
  <?php endif; ?>

  <?php foreach ($userFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endforeach; ?>

            <form method="POST" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_user">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string) $userForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="username">Usuario</label>
                    <input type="text" id="username" name="username" value="<?php echo htmlspecialchars((string) $userForm['username'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="fullname">Nome completo</label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo htmlspecialchars((string) $userForm['fullname'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars((string) $userForm['email'], ENT_QUOTES, 'UTF-8'); ?>">
                </div>

   <?php if ($editingUserId > 0): ?>
                    <div class="form-group">
                        <label for="user_provider_preview">Origem da conta</label>
                        <input type="text" id="user_provider_preview" value="<?php echo htmlspecialchars($editingUserProviderLabel, ENT_QUOTES, 'UTF-8'); ?>" disabled>
                    </div>
   <?php endif; ?>

                <div class="form-group">
                    <label for="role">Nivel hierarquico</label>
                    <select id="role" name="role">
   <?php foreach ($roleOptions as $roleKey => $roleMeta): ?>
                            <option value="<?php echo htmlspecialchars($roleKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $auth->getRoleKey($userForm['role']) === $roleKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars((string) $roleMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="password"><?php echo $userForm['id'] !== '' ? 'Nova senha (opcional)' : 'Senha'; ?></label>
                    <input type="password" id="password" name="password"<?php echo $userForm['id'] === '' ? 'required' : ''; ?>>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $userForm['id'] !== '' ? 'Salvar usuario' : 'Criar usuario'; ?></button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Base de usuarios</p>
                    <h2>Usuarios cadastrados</h2>
                </div>
            </div>

  <?php if (empty($users)): ?>
                <p class="admin-empty">Nenhum usuario cadastrado ainda.</p>
  <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Usuario</th>
                                <th>Nivel</th>
                                <th>Perfil</th>
                                <th>Atuacao</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
   <?php foreach ($users as $listedUser): ?>
   <?php
                                $listedUserId = (int) $listedUser['id'];
                                $listedIsAdmin = $auth->isAdmin($listedUser);
                                $listedRoleKey = $auth->getRoleKey($listedUser);
                                $listedRoleLabel = $auth->getRoleLabel($listedUser);
                                $listedRoleClass = 'admin-pill--' . str_replace('_', '-', $listedRoleKey);
                                $isSelf = $listedUserId === (int) $currentUser['id'];
                                $isEditing = $listedUserId === $editingUserId;
                                $listedProviderLabel = admin_provider_label((string) ($listedUser['provider'] ?? 'local'));
                                $listedProjectCount = (int) ($projectCountByUser[$listedUserId] ?? 0);
                                $listedSupervisionCount = (int) ($orientationSupervisionCountByUser[$listedUserId] ?? 0);
                                $listedAssignedCount = (int) ($orientationAssignedCountByUser[$listedUserId] ?? 0);
                                ?>
                                <tr class="<?php echo $isEditing ? 'admin-table-row-active' : ''; ?>">
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) $listedUser['fullname'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta">@<?php echo htmlspecialchars((string) $listedUser['username'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) ($listedUser['email'] ?: 'Email nao informado'), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="admin-pill<?php echo htmlspecialchars($listedRoleClass, ENT_QUOTES, 'UTF-8'); ?>">
           <?php echo htmlspecialchars($listedRoleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($listedProviderLabel, ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta">Criado em<?php echo htmlspecialchars(admin_format_datetime($listedUser['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta">Atualizado em<?php echo htmlspecialchars(admin_format_datetime($listedUser['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <strong><?php echo $listedProjectCount; ?> projeto(s)</strong>
                                        <div class="admin-meta">Supervisiona<?php echo $listedSupervisionCount; ?> orientacao(oes)</div>
                                        <div class="admin-meta">Recebe<?php echo $listedAssignedCount; ?> orientacao(oes)</div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <form method="GET" action="admin.php#users" class="table-actions__inline-form">
                                                <input type="hidden" name="edit_user" value="<?php echo $listedUserId; ?>">
                                                <button type="submit" class="dashboard-btn admin-btn-small">Editar perfil</button>
                                            </form>

           <?php if (!$isSelf): ?>
                                                <form method="POST" onsubmit="return confirm('Remover este usuario?');">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $listedUserId; ?>">
                                                    <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                                </form>
           <?php else: ?>
                                                <span class="admin-self-tag">Conta atual</span>
           <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
   <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
  <?php endif; ?>
        </article>
    </section>

    <section id="document-authentication" class="admin-workspace">
        <article class="panel-card admin-workspace__full">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Autenticador documental</p>
                    <h2>Documentos pendentes</h2>
                    <p class="admin-subtitle">Aprove ou rejeite PDF/DOCX enviados pelos responsaveis dos projetos. A aprovacao autentica o projeto na pagina publica.</p>
                </div>
            </div>

            <?php foreach ($documentFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars((string) $error, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endforeach; ?>

            <?php if (empty($pendingProjectDocuments)): ?>
                <p class="admin-empty">Nenhum documento pendente de avaliacao.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Documento</th>
                                <th>Projeto</th>
                                <th>Enviado por</th>
                                <th>Data</th>
                                <th>Avaliacao</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pendingProjectDocuments as $document): ?>
                                <?php
                                $documentProject = $projectManager->getProject((string) ($document['project_id'] ?? ''));
                                $documentProjectTitle = is_array($documentProject) ? (string) ($documentProject['title'] ?? 'Projeto') : 'Projeto removido';
                                $uploader = $userMap[(int) ($document['uploaded_by_user_id'] ?? 0)] ?? null;
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($document['original_name'] ?? 'Documento'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo number_format(((int) ($document['size_bytes'] ?? 0)) / 1024, 1, ',', '.'); ?> KB</div>
                                        <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project-file.php?kind=document&id=<?php echo urlencode((string) ($document['id'] ?? '')); ?>">Baixar</a>
                                    </td>
                                    <td><?php echo htmlspecialchars($documentProjectTitle, ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars((string) ($uploader['fullname'] ?? $uploader['username'] ?? 'Usuario'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($document['created_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <form method="POST" class="admin-review-form">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                            <input type="hidden" name="action" value="review_project_document">
                                            <input type="hidden" name="document_id" value="<?php echo htmlspecialchars((string) ($document['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
                                            <textarea name="review_notes" rows="2" placeholder="Observacao opcional"></textarea>
                                            <div class="table-actions">
                                                <button type="submit" name="decision" value="approved" class="dashboard-btn admin-btn-small">Aprovar</button>
                                                <button type="submit" name="decision" value="rejected" class="dashboard-btn admin-btn-danger">Rejeitar</button>
                                            </div>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </article>
    </section>

    <section id="projects" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Projetos</p>
                    <h2><?php echo $projectForm['id'] !== '' ? 'Editar projeto' : 'Novo projeto'; ?></h2>
                </div>

   <?php if ($projectForm['id'] !== ''): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php#projects">Limpar formulario</a>
   <?php endif; ?>
            </div>

  <?php foreach ($projectFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endforeach; ?>

            <form method="POST" enctype="multipart/form-data" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_project">
                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars((string) $projectForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="user_id">Responsavel</label>
                    <select id="user_id" name="user_id">
                        <option value="">Sem responsavel</option>
   <?php foreach ($users as $listedUser): ?>
   <?php $listedUserId = (int) $listedUser['id']; ?>
                            <option value="<?php echo $listedUserId; ?>"<?php echo (string) $projectForm['user_id'] === (string) $listedUserId ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars((string) $listedUser['fullname'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Titulo</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars((string) $projectForm['title'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="category">Categoria</label>
                    <select id="category" name="category" required>
   <?php foreach ($thematicAreaOptions as $areaKey => $areaLabel): ?>
                            <option value="<?php echo htmlspecialchars((string) $areaKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $projectForm['category'] === (string) $areaKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars((string) $areaLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                    <p class="form-help">A categoria principal do projeto agora fica restrita as 5 siglas oficiais das Areas Tematicas.</p>
                </div>

                <div class="form-group">
                    <label for="tags">Tags</label>
                    <div class="tag-options-grid" id="tags">
   <?php foreach ($thematicAreaOptions as $areaKey => $areaLabel): ?>
   <?php $isChecked = in_array((string) $areaKey, $projectForm['tags'], true); ?>
                            <label class="checkbox-row tag-option-card">
                                <input type="checkbox" name="tags[]" value="<?php echo htmlspecialchars((string) $areaKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $isChecked ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars((string) $areaLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                            </label>
   <?php endforeach; ?>
                    </div>
                    <p class="form-help">As tags tambem ficam limitadas as 5 siglas das Areas Tematicas e alimentam os filtros e cards da home.</p>
                </div>

                <div class="form-group">
                    <label for="project_status">Status</label>
                    <select id="project_status" name="status">
                        <option value="active"<?php echo $projectForm['status'] === 'active' ? 'selected' : ''; ?>>Ativo</option>
                        <option value="pending"<?php echo $projectForm['status'] === 'pending' ? 'selected' : ''; ?>>Pendente</option>
                        <option value="completed"<?php echo $projectForm['status'] === 'completed' ? 'selected' : ''; ?>>Concluido</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="description">Descricao</label>
                    <textarea id="description" name="description" rows="6"><?php echo htmlspecialchars((string) $projectForm['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="participation_info">Como participar</label>
                    <textarea id="participation_info" name="participation_info" rows="5" placeholder="Explique o perfil desejado, disponibilidade esperada, tipo de colaboracao e qualquer orientacao para entrada no projeto."><?php echo htmlspecialchars((string) $projectForm['participation_info'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="form-help">Esse texto aparecera na pagina detalhada do projeto para orientar quem quiser participar.</p>
                </div>

                <div class="form-group">
                    <label for="project_document_file">Documentacao obrigatoria</label>
                    <input type="file" id="project_document_file" name="document_file" accept=".pdf,.docx,application/pdf,application/vnd.openxmlformats-officedocument.wordprocessingml.document" <?php echo $projectForm['id'] === '' ? 'required' : ''; ?>>
                    <p class="form-help">Novos projetos precisam de PDF ou DOCX para entrar no fluxo de autenticacao. Em edicoes, use este campo apenas para enviar uma nova versao documental.</p>
                </div>

   <?php if ((string) $projectForm['image_path'] !== ''): ?>
                    <div class="admin-partner-preview admin-project-preview">
                        <img
                            src="<?php echo htmlspecialchars((string) $projectForm['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                            alt="<?php echo htmlspecialchars((string) ($projectForm['title'] !== '' ? $projectForm['title'] : 'Preview do projeto'), ENT_QUOTES, 'UTF-8'); ?>"
                            class="admin-partner-preview__image">
                        <div class="admin-partner-preview__copy">
                            <strong>Banner atual do projeto</strong>
                            <p><?php echo htmlspecialchars((string) ($projectForm['title'] !== '' ? $projectForm['title'] : 'Projeto em edicao'), ENT_QUOTES, 'UTF-8'); ?></p>
                            <span><?php echo htmlspecialchars((string) $projectForm['image_path'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                    </div>
   <?php endif; ?>

                <div class="form-group">
                    <label for="project_image_file">Imagem do banner</label>
                    <input type="file" id="project_image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <p class="form-help">Envie JPG, PNG, WEBP ou GIF com ate 6 MB. Esse campo e opcional: sem imagem, a pagina do projeto usa automaticamente uma capa editorial escura do CEPIN-CIS.</p>
                </div>

                <div class="form-group">
                    <label for="project_image_path">Ou caminho da imagem</label>
                    <input type="text" id="project_image_path" name="image_path" value="<?php echo htmlspecialchars((string) $projectForm['image_path'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="./img/projeto-banner.png ou ./uploads/projects/arquivo.png">
                    <p class="form-help">Se voce ja possui o asset no projeto, pode apontar o caminho diretamente sem reenviar o arquivo. Se deixar vazio, o fallback visual entra automaticamente.</p>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $projectForm['id'] !== '' ? 'Salvar projeto' : 'Criar projeto'; ?></button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Acervo</p>
                    <h2>Projetos cadastrados</h2>
                </div>
            </div>

  <?php if (empty($projects)): ?>
                <p class="admin-empty">Nenhum projeto cadastrado ainda.</p>
  <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Projeto</th>
                                <th>Responsavel</th>
                                <th>Status</th>
                                <th>Autenticacao</th>
                                <th>Categoria</th>
                                <th>Atualizado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
   <?php foreach ($projects as $project): ?>
   <?php
                                $ownerId = $project['user_id'] ?? null;
                                $owner = $ownerId !== null && isset($userMap[(int) $ownerId]) ? $userMap[(int) $ownerId] : null;
                                $authentication = $workspaceManager->getAuthenticationStatus($project);
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) $project['title'], ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) $project['description'], ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta">Tags:<?php echo htmlspecialchars(admin_format_tags($projectManager->getProjectTagList($project, false)), ENT_QUOTES, 'UTF-8'); ?></div>
       <?php if ((string) ($project['image_path'] ?? '') !== ''): ?>
                                            <div class="admin-meta">Banner:<?php echo htmlspecialchars((string) $project['image_path'], ENT_QUOTES, 'UTF-8'); ?></div>
       <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) ($owner['fullname'] ?? 'Sem responsavel'), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <span class="admin-pill admin-pill--status">
           <?php echo htmlspecialchars(admin_status_label((string) $project['status']), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="admin-pill admin-pill--<?php echo htmlspecialchars((string) ($authentication['status'] ?? 'missing'), ENT_QUOTES, 'UTF-8'); ?>">
           <?php echo htmlspecialchars((string) ($authentication['label'] ?? 'Sem documentacao'), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars((string) $project['category'], ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($project['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project.php?id=<?php echo urlencode((string) $project['id']); ?>">Ver pagina</a>
                                            <a class="dashboard-btn admin-btn-small dashboard-btn--ghost" href="project-workspace.php?id=<?php echo urlencode((string) $project['id']); ?>">Workspace</a>
                                            <a class="dashboard-btn admin-btn-small" href="admin.php?edit_project=<?php echo urlencode((string) $project['id']); ?>#projects">Editar</a>

                                            <form method="POST" onsubmit="return confirm('Excluir este projeto?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_project">
                                                <input type="hidden" name="project_id" value="<?php echo htmlspecialchars((string) $project['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
   <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
  <?php endif; ?>
        </article>
    </section>

    <section id="partners" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Carrossel institucional</p>
                    <h2><?php echo $partnerForm['id'] !== '' ? 'Editar parceiro' : 'Novo parceiro'; ?></h2>
                    <p class="admin-subtitle">Gerencie os cards da secao Parceiros na home com imagem, nome e descricao. O upload substitui o caminho atual quando voce quiser renovar uma marca ou identidade visual.</p>
                </div>

   <?php if ($partnerForm['id'] !== ''): ?>
                    <a class="dashboard-btn dashboard-btn--ghost" href="admin.php#partners">Limpar formulario</a>
   <?php endif; ?>
            </div>

  <?php foreach ($partnerFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endforeach; ?>

  <?php if ((string) $partnerForm['image_path'] !== ''): ?>
                <div class="admin-partner-preview">
                    <img
                        src="<?php echo htmlspecialchars((string) $partnerForm['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                        alt="<?php echo htmlspecialchars((string) ($partnerForm['name'] !== '' ? $partnerForm['name'] : 'Preview do parceiro'), ENT_QUOTES, 'UTF-8'); ?>"
                        class="admin-partner-preview__image">
                    <div class="admin-partner-preview__copy">
                        <strong><?php echo htmlspecialchars((string) ($partnerForm['name'] !== '' ? $partnerForm['name'] : 'Parceiro em edicao'), ENT_QUOTES, 'UTF-8'); ?></strong>
                        <p><?php echo htmlspecialchars(admin_content_excerpt((string) $partnerForm['description'], 180), ENT_QUOTES, 'UTF-8'); ?></p>
                        <span><?php echo htmlspecialchars((string) $partnerForm['image_path'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
  <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="stack-form">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_partner">
                <input type="hidden" name="partner_id" value="<?php echo htmlspecialchars((string) $partnerForm['id'], ENT_QUOTES, 'UTF-8'); ?>">

                <div class="form-group">
                    <label for="partner_name">Nome</label>
                    <input type="text" id="partner_name" name="name" value="<?php echo htmlspecialchars((string) $partnerForm['name'], ENT_QUOTES, 'UTF-8'); ?>" required>
                </div>

                <div class="form-group">
                    <label for="partner_description">Descricao</label>
                    <textarea id="partner_description" name="description" rows="5" required><?php echo htmlspecialchars((string) $partnerForm['description'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group">
                    <label for="partner_image_file">Imagem do card</label>
                    <input type="file" id="partner_image_file" name="image_file" accept="image/png,image/jpeg,image/webp,image/gif">
                    <p class="form-help">Envie JPG, PNG, WEBP ou GIF com ate 5 MB. Se voce subir um novo arquivo, ele passa a ser usado na home automaticamente.</p>
                </div>

                <div class="form-group">
                    <label for="partner_image_path">Ou caminho da imagem</label>
                    <input type="text" id="partner_image_path" name="image_path" value="<?php echo htmlspecialchars((string) $partnerForm['image_path'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="./img/parceiro.png ou ./uploads/partners/arquivo.png">
                    <p class="form-help">Esse campo e util quando a marca ja existe no projeto e voce quer apontar para um asset local sem reenviar o arquivo.</p>
                </div>

                <button type="submit" class="dashboard-btn"><?php echo $partnerForm['id'] !== '' ? 'Salvar parceiro' : 'Criar parceiro'; ?></button>
            </form>
        </article>

        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Home page</p>
                    <h2>Parceiros cadastrados</h2>
                    <p class="admin-subtitle">A ordem abaixo alimenta diretamente o carrossel da home, incluindo nome, descricao e arte do card.</p>
                </div>
            </div>

  <?php if (empty($partners)): ?>
                <p class="admin-empty">Nenhum parceiro cadastrado ainda.</p>
  <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Parceiro</th>
                                <th>Imagem</th>
                                <th>Atualizado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
   <?php foreach ($partners as $partner): ?>
   <?php $partnerId = (string) ($partner['id'] ?? ''); ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($partner['name'] ?? 'Parceiro'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars(admin_content_excerpt((string) ($partner['description'] ?? ''), 180), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) ($partner['image_path'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
       <?php if ((string) ($partner['image_path'] ?? '') !== ''): ?>
                                            <img
                                                src="<?php echo htmlspecialchars((string) $partner['image_path'], ENT_QUOTES, 'UTF-8'); ?>"
                                                alt="<?php echo htmlspecialchars((string) ($partner['name'] ?? 'Parceiro'), ENT_QUOTES, 'UTF-8'); ?>"
                                                class="admin-partner-thumb">
       <?php else: ?>
                                            <span class="admin-empty">Sem imagem</span>
       <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($partner['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small" href="admin.php?edit_partner=<?php echo urlencode($partnerId); ?>#partners">Editar</a>

                                            <form method="POST" onsubmit="return confirm('Excluir este parceiro do carrossel?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_partner">
                                                <input type="hidden" name="partner_id" value="<?php echo htmlspecialchars($partnerId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
   <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
  <?php endif; ?>
        </article>
    </section>

    <section id="content" class="admin-workspace">
        <article class="panel-card">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Conteudo global</p>
                    <h2 data-content-form-heading><?php echo $contentForm['id'] !== '' ? 'Editar bloco' : 'Novo bloco'; ?></h2>
                    <p class="admin-subtitle">CMS interno por blocos. Hoje ele abastece Contato, Areas Tematicas e a pagina Sobre, incluindo estrutura de grid, espacamentos e tamanho dos cards.</p>
                </div>
                <a
                    class="dashboard-btn dashboard-btn--ghost"
                    href="admin.php#content"
                    data-content-form-reset
   <?php echo $contentForm['id'] !== '' ? '' : 'hidden'; ?>>
                    Limpar formulario
                </a>
            </div>

  <?php foreach ($contentFormErrors as $error): ?>
                <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
  <?php endforeach; ?>

            <form method="POST" class="stack-form" data-block-form-root data-content-form>
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                <input type="hidden" name="action" value="save_content_block">
                <input type="hidden" name="block_id" value="<?php echo htmlspecialchars((string) $contentForm['id'], ENT_QUOTES, 'UTF-8'); ?>" data-content-field="id">

                <div class="form-group">
                    <label for="content_page_key">Pagina</label>
                    <select id="content_page_key" name="page_key" data-block-page-select data-content-field="page_key">
   <?php foreach ($contentPageOptions as $pageKey => $pageMeta): ?>
                            <option
                                value="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>"
                                data-supports-layout-builder="<?php echo !empty($pageMeta['supports_layout_builder']) ? '1' : '0'; ?>"
   <?php echo (string) $contentForm['page_key'] === (string) $pageKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars((string) $pageMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="content_type">Tipo de bloco</label>
                    <select id="content_type" name="type" data-block-type-select data-content-field="type">
   <?php foreach ($contentTypeOptions as $typeKey => $typeMeta): ?>
   <?php
                            $allowedPages = [];

                            foreach ($contentPageOptions as $pageKey => $pageMeta) {
                                $pageAllowedTypes = $pageMeta['allowed_types'] ?? array_keys($contentTypeOptions);
                                if (in_array($typeKey, $pageAllowedTypes, true)) {
                                    $allowedPages[] = $pageKey;
                                }
                            }
                            ?>
                            <option
                                value="<?php echo htmlspecialchars($typeKey, ENT_QUOTES, 'UTF-8'); ?>"
                                data-allowed-pages="<?php echo htmlspecialchars(implode(',', $allowedPages), ENT_QUOTES, 'UTF-8'); ?>"
                                data-type-description="<?php echo htmlspecialchars((string) ($typeMeta['description'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
   <?php echo (string) $contentForm['type'] === (string) $typeKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars((string) $typeMeta['label'], ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                    <p class="form-help" data-block-type-help data-default-help="Cada pagina libera apenas os tipos de bloco que fazem sentido para o layout dela.">
   <?php echo htmlspecialchars((string) ($contentTypeOptions[$contentForm['type']]['description'] ?? 'Cada pagina libera apenas os tipos de bloco que fazem sentido para o layout dela.'), ENT_QUOTES, 'UTF-8'); ?>
                    </p>
                </div>

                <div class="form-group">
                    <label for="content_name">Nome interno</label>
                    <input type="text" id="content_name" name="name" value="<?php echo htmlspecialchars((string) $contentForm['name'], ENT_QUOTES, 'UTF-8'); ?>" required data-content-field="name">
                    <p class="form-help">Esse nome aparece no painel para identificar o bloco com rapidez.</p>
                </div>

                <div class="form-group">
                    <label for="content_eyebrow">Eyebrow / tag</label>
                    <input type="text" id="content_eyebrow" name="eyebrow" value="<?php echo htmlspecialchars((string) $contentForm['eyebrow'], ENT_QUOTES, 'UTF-8'); ?>" data-content-field="eyebrow">
                    <p class="form-help">Use esse campo como selo curto do bloco, como "Canal oficial", "CEPIN-CIS" ou "EduCIS".</p>
                </div>

                <div class="form-group">
                    <label for="content_title">Titulo</label>
                    <input type="text" id="content_title" name="title" value="<?php echo htmlspecialchars((string) $contentForm['title'], ENT_QUOTES, 'UTF-8'); ?>" required data-content-field="title">
                </div>

                <div class="form-group">
                    <label for="content_body">Texto principal</label>
                    <textarea id="content_body" name="body" rows="6" data-content-field="body"><?php echo htmlspecialchars((string) $contentForm['body'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card,thematic_cta,about_list,about_cta">
                    <label for="content_items_text">Itens estruturados</label>
                    <textarea id="content_items_text" name="items_text" rows="6" data-content-field="items_text"><?php echo htmlspecialchars((string) $contentForm['items_text'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="form-help">Uma linha por item no formato: Rotulo | valor | link opcional. Ex.: Email | cepin@dominio.com | mailto:cepin@dominio.com</p>
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card,thematic_intro,thematic_cta,about_text,about_cta">
                    <label for="content_cta_label">Rotulo do botao</label>
                    <input type="text" id="content_cta_label" name="cta_label" value="<?php echo htmlspecialchars((string) $contentForm['cta_label'], ENT_QUOTES, 'UTF-8'); ?>" data-content-field="cta_label">
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card,thematic_intro,thematic_cta,about_text,about_cta">
                    <label for="content_cta_url">URL do botao</label>
                    <input type="text" id="content_cta_url" name="cta_url" value="<?php echo htmlspecialchars((string) $contentForm['cta_url'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="mailto:, https:// ou /rota-interna" data-content-field="cta_url">
                </div>

                <div class="form-group" data-block-field-group="map_embed">
                    <label for="content_embed_url">URL do embed</label>
                    <textarea id="content_embed_url" name="embed_url" rows="4" data-content-field="embed_url"><?php echo htmlspecialchars((string) $contentForm['embed_url'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                    <p class="form-help">Cole apenas o valor de `src` do iframe incorporado.</p>
                </div>

                <div class="form-group" data-block-field-group="about_media,about_list">
                    <label for="content_media_url">Imagem principal</label>
                    <input type="text" id="content_media_url" name="media_url" value="<?php echo htmlspecialchars((string) $contentForm['media_url'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="./img/banner.png ou https://..." data-content-field="media_url">
                </div>

                <div class="form-group" data-block-field-group="about_media">
                    <label for="content_media_dark_url">Imagem tema escuro</label>
                    <input type="text" id="content_media_dark_url" name="media_dark_url" value="<?php echo htmlspecialchars((string) $contentForm['media_dark_url'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="./img/bannerescuro.png" data-content-field="media_dark_url">
                </div>

                <div class="form-group" data-block-field-group="about_media,about_list">
                    <label for="content_media_alt">Texto alternativo da imagem</label>
                    <input type="text" id="content_media_alt" name="media_alt" value="<?php echo htmlspecialchars((string) $contentForm['media_alt'], ENT_QUOTES, 'UTF-8'); ?>" placeholder="Descricao objetiva da imagem" data-content-field="media_alt">
                </div>

                <div class="form-group">
                    <label for="content_width">Largura do bloco</label>
                    <select id="content_width" name="width" data-block-width-select data-content-field="width">
   <?php foreach ($contentWidthOptions as $widthKey => $widthLabel): ?>
   <?php
                            $allowedWidthPages = [];

                            foreach ($contentPageOptions as $pageKey => $pageMeta) {
                                $pageAllowedWidths = $pageMeta['allowed_widths'] ?? array_keys($contentWidthOptions);
                                if (in_array($widthKey, $pageAllowedWidths, true)) {
                                    $allowedWidthPages[] = $pageKey;
                                }
                            }
                            ?>
                            <option
                                value="<?php echo htmlspecialchars($widthKey, ENT_QUOTES, 'UTF-8'); ?>"
                                data-allowed-pages="<?php echo htmlspecialchars(implode(',', $allowedWidthPages), ENT_QUOTES, 'UTF-8'); ?>"
   <?php echo (string) $contentForm['width'] === (string) $widthKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars($widthLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                    <p class="form-help">Na pagina Sobre, isso define quantas colunas o card ocupa dentro do grid configurado pelo admin.</p>
                </div>

                <div class="form-group" data-block-field-group="about_text,about_media,about_list,about_cta">
                    <label for="content_height">Altura visual do bloco</label>
                    <select id="content_height" name="height" data-block-height-select data-content-field="height">
   <?php foreach ($contentHeightOptions as $heightKey => $heightLabel): ?>
                            <option value="<?php echo htmlspecialchars($heightKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $contentForm['height'] === (string) $heightKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars($heightLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                    <p class="form-help">Use isso para deixar um card mais compacto, equilibrado ou dominante dentro da composicao.</p>
                </div>

                <div class="form-group">
                    <label for="content_position">Posicao</label>
                    <input type="number" id="content_position" name="position" value="<?php echo htmlspecialchars((string) $contentForm['position'], ENT_QUOTES, 'UTF-8'); ?>" min="1" step="1" data-content-field="position">
                    <p class="form-help">Quanto menor o numero, mais acima o bloco aparece na pagina.</p>
                </div>

                <div class="form-group">
                    <label for="content_status">Visibilidade</label>
                    <select id="content_status" name="status" data-content-field="status">
   <?php foreach ($contentStatusOptions as $statusKey => $statusLabel): ?>
                            <option value="<?php echo htmlspecialchars($statusKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $contentForm['status'] === (string) $statusKey ? 'selected' : ''; ?>>
   <?php echo htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
   <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group" data-block-field-group="contact_info,text_card">
                    <label class="checkbox-row" for="content_context_note">
                        <input type="checkbox" id="content_context_note" name="show_context_note" value="1"<?php echo !empty($contentForm['show_context_note']) ? 'checked' : ''; ?> data-content-field="show_context_note">
                        Exibir contexto de projeto/categoria quando o usuario vier da home
                    </label>
                </div>

                <button type="submit" class="dashboard-btn" data-content-form-submit><?php echo $contentForm['id'] !== '' ? 'Salvar bloco' : 'Criar bloco'; ?></button>
            </form>
        </article>

        <div class="admin-layout-builder-stack">
  <?php foreach ($layoutBuilderPageKeys as $layoutPageKey): ?>
   <?php
                $layoutErrorsForPage = $layoutOverridePageKey === $layoutPageKey ? $contentLayoutErrors : [];
                admin_render_layout_builder(
                    $layoutPageKey,
                    $contentPageOptions[$layoutPageKey] ?? [],
                    $layoutFormsByPage[$layoutPageKey] ?? ['page_key' => $layoutPageKey],
                    $pageBlocksByKey[$layoutPageKey] ?? [],
                    $layoutWidthOptionsByPage[$layoutPageKey] ?? [],
                    $layoutHeightOptions,
                    $contentGridStyleOptions,
                    $layoutErrorsForPage,
                    (string) $contentForm['page_key'] === $layoutPageKey,
                    $csrfToken,
                    $contentManager
                );
                ?>
  <?php endforeach; ?>
        </div>

 <?php if (false): ?>
            <article
                class="panel-card admin-layout-builder"
                data-layout-builder
                data-layout-builder-page="about"
                data-layout-inline-controls="1"
   <?php echo (string) $contentForm['page_key'] === 'about' ? '' : 'hidden'; ?>>
                <div class="panel-card-header">
                    <div>
                        <p class="eyebrow">Esqueleto da pagina</p>
                        <h2>Layout da aba Sobre</h2>
                        <p class="admin-subtitle">Ajuste a composicao da Sobre como se estivesse montando uma vitrine: primeiro defina o ritmo geral e depois clique em qualquer card para editar o bloco direto no canvas.</p>
                    </div>
                </div>

   <?php foreach ($contentLayoutErrors as $error): ?>
                    <div class="mensagem erro"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></div>
   <?php endforeach; ?>

                <form method="POST" class="stack-form admin-layout-builder__form" data-layout-builder-form>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="action" value="save_content_layout">
                    <input type="hidden" name="layout_page_key" value="about">
                    <input type="hidden" name="layout_blocks_json" value="" data-layout-blocks-input>

                    <div class="admin-layout-builder__controls">
                        <div class="admin-layout-control admin-layout-control--select">
                            <div class="admin-layout-control__top">
                                <label for="about_grid_style">Formato do grid</label>
                                <span class="admin-layout-control__value" data-layout-value-output="grid_style"></span>
                            </div>
                            <select id="about_grid_style" name="grid_style" data-layout-input="grid_style" class="admin-layout-control__select">
   <?php foreach ($contentGridStyleOptions as $gridStyleKey => $gridStyleLabel): ?>
                                    <option value="<?php echo htmlspecialchars($gridStyleKey, ENT_QUOTES, 'UTF-8'); ?>"<?php echo (string) $aboutLayoutForm['grid_style'] === (string) $gridStyleKey ? 'selected' : ''; ?>>
       <?php echo htmlspecialchars($gridStyleLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </option>
   <?php endforeach; ?>
                            </select>
                            <p class="admin-layout-control__note">Defina se a grade deve respirar mais ou encaixar os cards de forma mais densa.</p>
                        </div>

                        <div class="admin-layout-control">
                            <div class="admin-layout-control__top">
                                <label for="about_columns">Colunas no desktop</label>
                                <span class="admin-layout-control__value" data-layout-value-output="columns"></span>
                            </div>
                            <input type="range" id="about_columns" name="columns" value="<?php echo htmlspecialchars((string) $aboutLayoutForm['columns'], ENT_QUOTES, 'UTF-8'); ?>" min="1" max="4" step="1" data-layout-input="columns" class="admin-layout-control__range">
                            <div class="admin-layout-control__scale">
                                <span>Mais focado</span>
                                <span>Mais aberto</span>
                            </div>
                        </div>

                        <div class="admin-layout-control">
                            <div class="admin-layout-control__top">
                                <label for="about_mobile_columns">Colunas no mobile</label>
                                <span class="admin-layout-control__value" data-layout-value-output="mobile_columns"></span>
                            </div>
                            <input type="range" id="about_mobile_columns" name="mobile_columns" value="<?php echo htmlspecialchars((string) $aboutLayoutForm['mobile_columns'], ENT_QUOTES, 'UTF-8'); ?>" min="1" max="2" step="1" data-layout-input="mobile_columns" class="admin-layout-control__range">
                            <div class="admin-layout-control__scale">
                                <span>Empilhado</span>
                                <span>Duplo</span>
                            </div>
                        </div>

                        <div class="admin-layout-control">
                            <div class="admin-layout-control__top">
                                <label for="about_gap">Distancia entre blocos</label>
                                <span class="admin-layout-control__value" data-layout-value-output="gap"></span>
                            </div>
                            <input type="range" id="about_gap" name="gap" value="<?php echo htmlspecialchars((string) $aboutLayoutForm['gap'], ENT_QUOTES, 'UTF-8'); ?>" min="12" max="56" step="1" data-layout-input="gap" class="admin-layout-control__range">
                            <div class="admin-layout-control__scale">
                                <span>Mais justo</span>
                                <span>Mais arejado</span>
                            </div>
                        </div>

                        <div class="admin-layout-control">
                            <div class="admin-layout-control__top">
                                <label for="about_container_width">Largura da composicao</label>
                                <span class="admin-layout-control__value" data-layout-value-output="container_width"></span>
                            </div>
                            <input type="range" id="about_container_width" name="container_width" value="<?php echo htmlspecialchars((string) $aboutLayoutForm['container_width'], ENT_QUOTES, 'UTF-8'); ?>" min="880" max="1480" step="10" data-layout-input="container_width" class="admin-layout-control__range">
                            <div class="admin-layout-control__scale">
                                <span>Contida</span>
                                <span>Cenografica</span>
                            </div>
                        </div>

                        <div class="admin-layout-control">
                            <div class="admin-layout-control__top">
                                <label for="about_block_padding">Respiro interno</label>
                                <span class="admin-layout-control__value" data-layout-value-output="block_padding"></span>
                            </div>
                            <input type="range" id="about_block_padding" name="block_padding" value="<?php echo htmlspecialchars((string) $aboutLayoutForm['block_padding'], ENT_QUOTES, 'UTF-8'); ?>" min="18" max="56" step="1" data-layout-input="block_padding" class="admin-layout-control__range">
                            <div class="admin-layout-control__scale">
                                <span>Enxuto</span>
                                <span>Respirado</span>
                            </div>
                        </div>

                        <div class="admin-layout-control">
                            <div class="admin-layout-control__top">
                                <label for="about_block_min_height">Presenca vertical</label>
                                <span class="admin-layout-control__value" data-layout-value-output="block_min_height"></span>
                            </div>
                            <input type="range" id="about_block_min_height" name="block_min_height" value="<?php echo htmlspecialchars((string) $aboutLayoutForm['block_min_height'], ENT_QUOTES, 'UTF-8'); ?>" min="140" max="420" step="5" data-layout-input="block_min_height" class="admin-layout-control__range">
                            <div class="admin-layout-control__scale">
                                <span>Baixa</span>
                                <span>Monumental</span>
                            </div>
                        </div>
                    </div>

                    <div class="admin-layout-builder__footer">
                        <p class="admin-layout-builder__hint">Ajuste o ambiente geral acima e refine cada bloco diretamente no canvas abaixo com os icones de olho e lapis.</p>
                        <button type="submit" class="dashboard-btn">Salvar composicao da Sobre</button>
                    </div>
                </form>

                <div class="admin-layout-preview-shell">
                    <div class="admin-layout-preview-copy">
                        <strong>Canvas interativo</strong>
                        <span>Clique em qualquer card para abrir a edicao. O olho volta para visualizacao, o lapis entra no modo de ajuste e as mudancas de largura, altura, ordem e visibilidade ficam prontas para salvar em lote.</span>
                    </div>

                    <div
                        class="admin-layout-canvas"
                        data-layout-preview
                        style="--admin-layout-columns:<?php echo (int) $aboutLayoutForm['columns']; ?>; --admin-layout-gap:<?php echo (int) $aboutLayoutForm['gap']; ?>px; --admin-layout-width:<?php echo (int) $aboutLayoutForm['container_width']; ?>px; --admin-layout-min-height:<?php echo (int) $aboutLayoutForm['block_min_height']; ?>px; --admin-layout-flow:<?php echo (string) $aboutLayoutForm['grid_style'] === 'dense' ? 'row dense' : 'row'; ?>;">
   <?php foreach ($aboutContentBlocks as $block): ?>
   <?php
                            $previewSpan = $contentManager->getWidthSpan((string) ($block['width'] ?? 'span_1'), (int) $aboutLayoutForm['columns']);
                            $previewHeightFactor = $contentManager->getHeightFactor((string) ($block['height'] ?? 'regular'));
                            $previewType = (string) ($block['type'] ?? 'about_text');
                            $previewWidth = (string) ($block['width'] ?? 'span_1');
                            $previewHeight = (string) ($block['height'] ?? 'regular');
                            $previewStatus = (string) ($block['status'] ?? 'published');
                            $previewTitle = admin_preview_block_title($block);
                            $previewCopy = admin_preview_block_copy($block);
                            $previewTypeLabel = $contentManager->getTypeLabel($previewType);
                            $previewWidthLabel = $contentManager->getWidthLabel($previewWidth);
                            $previewHeightLabel = $contentManager->getHeightLabel($previewHeight);
                            $previewStatusLabel = $contentManager->getStatusLabel($previewStatus);
                            $previewBlockSlug = preg_replace('/[^a-z0-9_-]+/', '-', strtolower($previewType));
                            ?>
                            <article
                                class="admin-layout-preview-block admin-layout-preview-block--<?php echo htmlspecialchars((string) $previewBlockSlug, ENT_QUOTES, 'UTF-8'); ?><?php echo $previewStatus === 'hidden' ? 'admin-layout-preview-block--hidden' : ''; ?>"
                                data-layout-block-id="<?php echo htmlspecialchars((string) ($block['id'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                data-layout-block-title="<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>"
                                data-layout-selectable="1"
                                tabindex="0"
                                data-preview-width="<?php echo htmlspecialchars($previewWidth, ENT_QUOTES, 'UTF-8'); ?>"
                                data-preview-height="<?php echo htmlspecialchars($previewHeight, ENT_QUOTES, 'UTF-8'); ?>"
                                data-preview-status="<?php echo htmlspecialchars($previewStatus, ENT_QUOTES, 'UTF-8'); ?>"
                                style="grid-column: span<?php echo $previewSpan; ?> / span<?php echo $previewSpan; ?>; --admin-block-height-factor:<?php echo htmlspecialchars((string) $previewHeightFactor, ENT_QUOTES, 'UTF-8'); ?>;">
                                <div class="admin-layout-preview-block__rail">
                                    <button type="button" class="admin-layout-preview-icon is-active" data-layout-mode="preview" aria-label="Visualizar<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>" title="Visualizar bloco">
       <?php echo admin_icon('eye'); ?>
                                    </button>
                                    <button type="button" class="admin-layout-preview-icon" data-layout-mode="edit" aria-label="Editar layout de<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>" title="Editar layout do bloco">
       <?php echo admin_icon('edit'); ?>
                                    </button>
                                </div>

                                <div class="admin-layout-preview-block__shell">
                                    <span class="admin-layout-preview-block__type"><?php echo htmlspecialchars($previewTypeLabel, ENT_QUOTES, 'UTF-8'); ?></span>
                                    <strong class="admin-layout-preview-block__title"><?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                                    <span class="admin-layout-preview-block__meta" data-layout-block-meta>
                                        Ordem visual<?php echo (int) ($block['position'] ?? 0); ?> |<?php echo htmlspecialchars($previewWidthLabel, ENT_QUOTES, 'UTF-8'); ?> |<?php echo htmlspecialchars($previewHeightLabel, ENT_QUOTES, 'UTF-8'); ?> |<?php echo htmlspecialchars($previewStatusLabel, ENT_QUOTES, 'UTF-8'); ?>
                                    </span>
                                    <p class="admin-layout-preview-block__body"><?php echo htmlspecialchars($previewCopy, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="admin-layout-preview-block__mockup" aria-hidden="true">
                                        <span class="admin-layout-preview-block__line admin-layout-preview-block__line--strong"></span>
                                        <span class="admin-layout-preview-block__line"></span>
                                        <span class="admin-layout-preview-block__line admin-layout-preview-block__line--short"></span>
                                    </div>
                                </div>

                                <div class="admin-layout-preview-panel" data-layout-block-panel hidden>
                                    <div class="admin-layout-preview-panel__section">
                                        <span class="admin-layout-preview-panel__label">Largura</span>
                                        <div class="admin-layout-preview-choices" role="group" aria-label="Largura de<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>">
           <?php foreach ($aboutVisualWidthOptions as $widthKey => $widthLabel): ?>
                                                <button
                                                    type="button"
                                                    class="admin-layout-choice<?php echo $previewWidth === (string) $widthKey ? 'is-selected' : ''; ?>"
                                                    data-layout-choice="width"
                                                    data-choice-value="<?php echo htmlspecialchars((string) $widthKey, ENT_QUOTES, 'UTF-8'); ?>"
                                                    title="<?php echo htmlspecialchars((string) $widthLabel, ENT_QUOTES, 'UTF-8'); ?>">
                   <?php echo htmlspecialchars(admin_visual_width_short_label((string) $widthKey), ENT_QUOTES, 'UTF-8'); ?>
                                                </button>
           <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="admin-layout-preview-panel__section">
                                        <span class="admin-layout-preview-panel__label">Altura</span>
                                        <div class="admin-layout-preview-choices" role="group" aria-label="Altura de<?php echo htmlspecialchars($previewTitle, ENT_QUOTES, 'UTF-8'); ?>">
           <?php foreach ($aboutVisualHeightOptions as $heightKey => $heightLabel): ?>
                                                <button
                                                    type="button"
                                                    class="admin-layout-choice admin-layout-choice--text<?php echo $previewHeight === (string) $heightKey ? 'is-selected' : ''; ?>"
                                                    data-layout-choice="height"
                                                    data-choice-value="<?php echo htmlspecialchars((string) $heightKey, ENT_QUOTES, 'UTF-8'); ?>">
                   <?php echo htmlspecialchars((string) $heightLabel, ENT_QUOTES, 'UTF-8'); ?>
                                                </button>
           <?php endforeach; ?>
                                        </div>
                                    </div>

                                    <div class="admin-layout-preview-panel__actions">
                                        <button type="button" class="admin-layout-preview-action admin-layout-preview-action--ghost" data-layout-visibility-toggle>
                                            <span data-layout-visibility-label><?php echo $previewStatus === 'hidden' ? 'Publicar bloco' : 'Ocultar bloco'; ?></span>
                                        </button>

                                        <button type="button" class="admin-layout-preview-action" data-layout-move="-1">
           <?php echo admin_icon('up'); ?>
                                            <span>Subir</span>
                                        </button>

                                        <button type="button" class="admin-layout-preview-action" data-layout-move="1">
           <?php echo admin_icon('down'); ?>
                                            <span>Descer</span>
                                        </button>

                                        <button type="button" class="admin-layout-preview-link" data-layout-edit-content>Editar conteudo</button>
                                    </div>
                                </div>
                            </article>
   <?php endforeach; ?>

                        <article
                            class="admin-layout-preview-block admin-layout-preview-block--draft"
                            data-layout-preview-draft
                            data-preview-width="<?php echo htmlspecialchars((string) $contentForm['width'], ENT_QUOTES, 'UTF-8'); ?>"
                            data-preview-height="<?php echo htmlspecialchars((string) $contentForm['height'], ENT_QUOTES, 'UTF-8'); ?>">
                            <div class="admin-layout-preview-block__shell">
                                <span class="admin-layout-preview-block__type">Rascunho atual</span>
                                <strong class="admin-layout-preview-block__title" data-layout-draft-title><?php echo htmlspecialchars((string) ($contentForm['name'] !== '' ? $contentForm['name'] : 'Bloco em edicao'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                <span class="admin-layout-preview-block__meta" data-layout-draft-meta>
   <?php echo htmlspecialchars($contentManager->getWidthLabel((string) $contentForm['width']), ENT_QUOTES, 'UTF-8'); ?> Â·<?php echo htmlspecialchars($contentManager->getHeightLabel((string) $contentForm['height']), ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                                <p class="admin-layout-preview-block__body">Este card responde ao formulario acima e mostra como o bloco atual vai entrar na composicao quando for salvo.</p>
                                <div class="admin-layout-preview-block__mockup" aria-hidden="true">
                                    <span class="admin-layout-preview-block__line admin-layout-preview-block__line--strong"></span>
                                    <span class="admin-layout-preview-block__line"></span>
                                    <span class="admin-layout-preview-block__line admin-layout-preview-block__line--short"></span>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </article>
 <?php endif; ?>

        <article class="panel-card admin-workspace__full">
            <div class="panel-card-header">
                <div>
                    <p class="eyebrow">Builder</p>
                    <h2>Blocos cadastrados</h2>
                    <p class="admin-subtitle">Os blocos sao renderizados por pagina, ordenados por posicao e podem ser publicados ou ocultados sem apagar o historico.</p>
                </div>
            </div>

  <?php if (empty($contentBlocks)): ?>
                <p class="admin-empty">Nenhum bloco de conteudo cadastrado ainda.</p>
  <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Bloco</th>
                                <th>Pagina</th>
                                <th>Tipo</th>
                                <th>Layout</th>
                                <th>Atualizado em</th>
                                <th>Acoes</th>
                            </tr>
                        </thead>
                        <tbody>
   <?php foreach ($contentBlocks as $block): ?>
   <?php
                                $blockId = (string) ($block['id'] ?? '');
                                $blockStatus = (string) ($block['status'] ?? 'published');
                                $blockStatusClass = $blockStatus === 'published' ? 'admin-pill--published' : 'admin-pill--hidden';
                                ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars((string) ($block['name'] ?? 'Bloco'), ENT_QUOTES, 'UTF-8'); ?></strong>
                                        <div class="admin-meta"><?php echo htmlspecialchars((string) ($block['title'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta"><?php echo htmlspecialchars(admin_content_excerpt((string) ($block['body'] ?? '')), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta">Itens:<?php echo htmlspecialchars(admin_format_block_items($block['items'] ?? []), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td>
                                        <span class="admin-pill admin-pill--page">
           <?php echo htmlspecialchars($contentManager->getPageLabel((string) ($block['page_key'] ?? 'contact')), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="admin-meta"><?php echo htmlspecialchars($contentManager->getTypeLabel((string) ($block['type'] ?? 'text_card')), ENT_QUOTES, 'UTF-8'); ?></div>
       <?php if ((string) ($block['type'] ?? '') === 'map_embed'): ?>
                                            <div class="admin-meta">Embed configurado</div>
       <?php endif; ?>
       <?php if ((string) ($block['type'] ?? '') === 'about_media'): ?>
                                            <div class="admin-meta">Midia configurada</div>
       <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="admin-pill<?php echo htmlspecialchars($blockStatusClass, ENT_QUOTES, 'UTF-8'); ?>">
           <?php echo htmlspecialchars($contentManager->getStatusLabel($blockStatus), ENT_QUOTES, 'UTF-8'); ?>
                                        </span>
                                        <div class="admin-meta">Posicao:<?php echo (int) ($block['position'] ?? 0); ?></div>
                                        <div class="admin-meta">Largura:<?php echo htmlspecialchars($contentManager->getWidthLabel((string) ($block['width'] ?? 'half')), ENT_QUOTES, 'UTF-8'); ?></div>
                                        <div class="admin-meta">Altura:<?php echo htmlspecialchars($contentManager->getHeightLabel((string) ($block['height'] ?? 'regular')), ENT_QUOTES, 'UTF-8'); ?></div>
                                    </td>
                                    <td><?php echo htmlspecialchars(admin_format_datetime($block['updated_at'] ?? null), ENT_QUOTES, 'UTF-8'); ?></td>
                                    <td>
                                        <div class="table-actions">
                                            <a class="dashboard-btn admin-btn-small" href="admin.php?edit_block=<?php echo urlencode($blockId); ?>#content">Editar</a>

                                            <form method="POST" onsubmit="return confirm('Excluir este bloco de conteudo?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8'); ?>">
                                                <input type="hidden" name="action" value="delete_content_block">
                                                <input type="hidden" name="block_id" value="<?php echo htmlspecialchars($blockId, ENT_QUOTES, 'UTF-8'); ?>">
                                                <button type="submit" class="dashboard-btn admin-btn-danger">Excluir</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
   <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
  <?php endif; ?>
        </article>
    </section>
</main>

<?php
$contentBlockPayloads = [];
foreach ($contentBlocks as $block) {
    $blockId = (string) ($block['id'] ?? '');
    if ($blockId === '') {
        continue;
    }

    $contentBlockPayloads[$blockId] = [
        'id' => $blockId,
        'page_key' => (string) ($block['page_key'] ?? 'contact'),
        'type' => (string) ($block['type'] ?? ''),
        'name' => (string) ($block['name'] ?? ''),
        'eyebrow' => (string) ($block['eyebrow'] ?? ''),
        'title' => (string) ($block['title'] ?? ''),
        'body' => (string) ($block['body'] ?? ''),
        'items_text' => $contentManager->formatItemsForTextarea($block['items'] ?? []),
        'cta_label' => (string) ($block['cta_label'] ?? ''),
        'cta_url' => (string) ($block['cta_url'] ?? ''),
        'embed_url' => (string) ($block['embed_url'] ?? ''),
        'media_url' => (string) ($block['media_url'] ?? ''),
        'media_dark_url' => (string) ($block['media_dark_url'] ?? ''),
        'media_alt' => (string) ($block['media_alt'] ?? ''),
        'width' => (string) ($block['width'] ?? ''),
        'height' => (string) ($block['height'] ?? ''),
        'position' => (string) ($block['position'] ?? ''),
        'status' => (string) ($block['status'] ?? 'published'),
        'show_context_note' => !empty($block['show_context_note']),
    ];
}
?>
<script id="content-block-payloads" type="application/json">
<?php echo json_encode($contentBlockPayloads, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>
<script>
    (function() {
        const widthLabels = {
            half: 'Coluna simples',
            full: 'Largura total',
            span_1: '1 coluna',
            span_2: '2 colunas',
            span_3: '3 colunas',
            span_4: '4 colunas',
        };
        const heightLabels = {
            compact: 'Compacto',
            regular: 'Regular',
            tall: 'Alto',
        };
        const statusLabels = {
            published: 'Publicado',
            hidden: 'Oculto',
        };

        function clampNumber(value, min, max, fallback) {
            const parsed = Number.parseInt(value, 10);

            if (Number.isNaN(parsed)) {
                return fallback;
            }

            return Math.min(max, Math.max(min, parsed));
        }

        function resolveSpan(width, columns) {
            switch (width) {
                case 'full':
                    return columns;
                case 'span_4':
                    return Math.min(4, columns);
                case 'span_3':
                    return Math.min(3, columns);
                case 'span_2':
                    return Math.min(2, columns);
                case 'span_1':
                case 'half':
                default:
                    return 1;
            }
        }

        function resolveHeightFactor(height) {
            switch (height) {
                case 'compact':
                    return 0.82;
                case 'tall':
                    return 1.34;
                case 'regular':
                default:
                    return 1;
            }
        }

        function describeLayoutValue(key, value) {
            const numericValue = Number.parseInt(value, 10);

            switch (key) {
                case 'grid_style':
                    return value === 'dense' ? 'Mosaico denso' : 'Fluxo regular';
                case 'columns':
                    return numericValue === 1 ? '1 coluna' : String(numericValue) + ' colunas';
                case 'mobile_columns':
                    return numericValue === 1 ? 'Empilhado' : '2 colunas';
                case 'gap':
                    if (numericValue <= 18) {
                        return 'Compacto';
                    }
                    if (numericValue <= 30) {
                        return 'Equilibrado';
                    }
                    if (numericValue <= 42) {
                        return 'Respirado';
                    }
                    return 'Aereo';
                case 'container_width':
                    if (numericValue <= 980) {
                        return 'Contida';
                    }
                    if (numericValue <= 1180) {
                        return 'Equilibrada';
                    }
                    if (numericValue <= 1340) {
                        return 'Ampla';
                    }
                    return 'Cenografica';
                case 'block_padding':
                    if (numericValue <= 24) {
                        return 'Enxuto';
                    }
                    if (numericValue <= 36) {
                        return 'Confortavel';
                    }
                    if (numericValue <= 46) {
                        return 'Respirado';
                    }
                    return 'Luxuoso';
                case 'block_min_height':
                    if (numericValue <= 180) {
                        return 'Baixa';
                    }
                    if (numericValue <= 250) {
                        return 'Media';
                    }
                    if (numericValue <= 320) {
                        return 'Alta';
                    }
                    return 'Monumental';
                default:
                    return String(value || '');
            }
        }

        function getEventElementTarget(event) {
            if (event.target instanceof Element) {
                return event.target;
            }

            if (event.target && event.target.parentElement instanceof Element) {
                return event.target.parentElement;
            }

            return null;
        }

        function initAdminInlineBuilders() {
            const builders = Array.from(document.querySelectorAll('[data-layout-builder][data-layout-inline-controls="1"]'));
            const contentForm = document.querySelector('[data-content-form]');
            const pageField = document.querySelector('[data-block-page-select]');
            const contentHeading = document.querySelector('[data-content-form-heading]');
            const contentSubmit = document.querySelector('[data-content-form-submit]');
            const contentReset = document.querySelector('[data-content-form-reset]');
            const payloadNode = document.getElementById('content-block-payloads');

            if (!builders.length || !contentForm) {
                return;
            }

            const fieldMap = {};
            Array.from(contentForm.querySelectorAll('[data-content-field]')).forEach((field) => {
                fieldMap[field.dataset.contentField || ''] = field;
            });

            let payloads = {};
            if (payloadNode && payloadNode.textContent.trim() !== '') {
                try {
                    const parsed = JSON.parse(payloadNode.textContent);
                    if (parsed && typeof parsed === 'object') {
                        payloads = parsed;
                    }
                } catch (error) {
                    payloads = {};
                }
            }

            function getCurrentContentId() {
                const idField = fieldMap.id;
                return idField instanceof HTMLInputElement ? idField.value.trim() : '';
            }

            function setContentFormMode(blockId) {
                const isEditingExistingBlock = String(blockId || '').trim() !== '';

                if (contentHeading) {
                    contentHeading.textContent = isEditingExistingBlock ? 'Editar bloco' : 'Novo bloco';
                }

                if (contentSubmit instanceof HTMLButtonElement) {
                    contentSubmit.textContent = isEditingExistingBlock ? 'Salvar bloco' : 'Criar bloco';
                }

                if (contentReset instanceof HTMLElement) {
                    contentReset.hidden = !isEditingExistingBlock;
                }
            }

            function setFieldValue(fieldName, value) {
                const field = fieldMap[fieldName];

                if (!(field instanceof HTMLElement)) {
                    return;
                }

                if (field instanceof HTMLInputElement && field.type === 'checkbox') {
                    field.checked = Boolean(value);
                    return;
                }

                if (field instanceof HTMLInputElement || field instanceof HTMLTextAreaElement || field instanceof HTMLSelectElement) {
                    field.value = value == null ? '' : String(value);
                }
            }

            function focusContentForm() {
                contentForm.scrollIntoView({
                    block: 'start',
                    behavior: 'smooth',
                });

                const nameField = fieldMap.name;
                if (nameField instanceof HTMLElement && typeof nameField.focus === 'function') {
                    window.setTimeout(() => {
                        nameField.focus();
                    }, 120);
                }
            }

            function emitFieldSignals(fieldName) {
                const field = fieldMap[fieldName];

                if (!(field instanceof HTMLElement)) {
                    return;
                }

                field.dispatchEvent(new Event('input', {
                    bubbles: true
                }));
                field.dispatchEvent(new Event('change', {
                    bubbles: true
                }));
            }

            const builderStates = window.__adminInlineBuilderStates || new Map();
            window.__adminInlineBuilderStates = builderStates;

            function syncVisibleBuilders() {
                if (!(pageField instanceof HTMLSelectElement)) {
                    return;
                }

                builders.forEach((builder) => {
                    builder.hidden = builder.dataset.layoutBuilderPage !== pageField.value;
                });
            }

            function syncAllBuilders() {
                builderStates.forEach((builderState) => {
                    if (builderState && typeof builderState.sync === 'function') {
                        builderState.sync();
                    }
                });
            }

            function openContentBlock(blockId) {
                const payload = payloads[String(blockId || '').trim()];

                if (!payload || typeof payload !== 'object') {
                    return;
                }

                [
                    'id',
                    'page_key',
                    'type',
                    'name',
                    'eyebrow',
                    'title',
                    'body',
                    'items_text',
                    'cta_label',
                    'cta_url',
                    'embed_url',
                    'media_url',
                    'media_dark_url',
                    'media_alt',
                    'width',
                    'height',
                    'position',
                    'status',
                    'show_context_note'
                ].forEach((fieldName) => {
                    setFieldValue(fieldName, payload[fieldName]);
                });

                ['page_key', 'type', 'name', 'width', 'height', 'status', 'show_context_note'].forEach(emitFieldSignals);
                setContentFormMode(payload.id || '');
                syncVisibleBuilders();
                syncAllBuilders();

                const targetPageKey = String(payload.page_key || '');
                const targetState = builderStates.get(targetPageKey);
                if (targetState && typeof targetState.openBlock === 'function') {
                    targetState.openBlock(String(payload.id || ''));
                }

                focusContentForm();
            }

            function initBuilder(builder) {
                const preview = builder.querySelector('[data-layout-preview]');
                const layoutForm = builder.querySelector('[data-layout-builder-form]');
                const blocksInput = layoutForm?.querySelector('[data-layout-blocks-input]') || null;
                const layoutInputs = layoutForm ? Array.from(layoutForm.querySelectorAll('[data-layout-input]')) : [];
                const layoutValueOutputs = builder ? Array.from(builder.querySelectorAll('[data-layout-value-output]')) : [];
                const pageKey = builder.dataset.layoutBuilderPage || '';

                if (!(preview instanceof HTMLElement) || !(layoutForm instanceof HTMLFormElement) || !(blocksInput instanceof HTMLInputElement)) {
                    return;
                }

                if (builder.dataset.inlineControlsBound === '1') {
                    return;
                }

                builder.dataset.inlineControlsBound = '1';

                function getBlocks() {
                    return Array.from(preview.querySelectorAll('[data-layout-block-id]'));
                }

                function bindControl(control, handler) {
                    if (!(control instanceof HTMLElement) || control.dataset.layoutInlineControlBound === '1') {
                        return;
                    }

                    control.dataset.layoutInlineControlBound = '1';
                    control.dataset.layoutInlineControl = '1';
                    control.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();

                        if (typeof event.stopImmediatePropagation === 'function') {
                            event.stopImmediatePropagation();
                        }

                        handler();
                    });
                }

                function getCurrentColumns() {
                    const columnsInput = layoutForm.querySelector('[data-layout-input="columns"]');
                    const columnsMax = Number.parseInt(columnsInput?.max || '4', 10);
                    return clampNumber(columnsInput?.value || '4', 1, Number.isNaN(columnsMax) ? 4 : columnsMax, 4);
                }

                function serializeBlocks() {
                    blocksInput.value = JSON.stringify(
                        getBlocks().map((block, index) => ({
                            id: block.dataset.layoutBlockId || '',
                            width: block.dataset.previewWidth || 'half',
                            height: block.dataset.previewHeight || 'regular',
                            status: block.dataset.previewStatus || 'published',
                            position: (index + 1) * 10,
                        }))
                    );
                }

                function applyLayoutVariables() {
                    const columnsInput = layoutForm.querySelector('[data-layout-input="columns"]');
                    const columnsMax = Number.parseInt(columnsInput?.max || '4', 10);
                    const columns = clampNumber(columnsInput?.value || '4', 1, Number.isNaN(columnsMax) ? 4 : columnsMax, 4);
                    const mobileColumns = clampNumber(layoutForm.querySelector('[data-layout-input="mobile_columns"]')?.value || '1', 1, 2, 1);
                    const gap = clampNumber(layoutForm.querySelector('[data-layout-input="gap"]')?.value || '24', 12, 56, 24);
                    const containerWidth = clampNumber(layoutForm.querySelector('[data-layout-input="container_width"]')?.value || '1220', 880, 1480, 1220);
                    const blockPadding = clampNumber(layoutForm.querySelector('[data-layout-input="block_padding"]')?.value || '32', 18, 56, 32);
                    const blockMinHeight = clampNumber(layoutForm.querySelector('[data-layout-input="block_min_height"]')?.value || '210', 140, 420, 210);
                    const gridStyle = layoutForm.querySelector('[data-layout-input="grid_style"]')?.value === 'dense' ? 'row dense' : 'row';

                    preview.style.setProperty('--admin-layout-columns', String(columns));
                    preview.style.setProperty('--admin-layout-mobile-columns', String(mobileColumns));
                    preview.style.setProperty('--admin-layout-gap', String(gap) + 'px');
                    preview.style.setProperty('--admin-layout-width', String(containerWidth) + 'px');
                    preview.style.setProperty('--admin-layout-padding', String(blockPadding) + 'px');
                    preview.style.setProperty('--admin-layout-min-height', String(blockMinHeight) + 'px');
                    preview.style.setProperty('--admin-layout-flow', gridStyle);

                    layoutValueOutputs.forEach((output) => {
                        const key = output.dataset.layoutValueOutput || '';
                        const input = layoutForm.querySelector('[data-layout-input="' + key + '"]');

                        if (input instanceof HTMLInputElement || input instanceof HTMLSelectElement) {
                            output.textContent = describeLayoutValue(key, input.value);
                        }
                    });
                }

                function applyBlockFrame(block) {
                    if (!(block instanceof HTMLElement)) {
                        return;
                    }

                    const previewWidth = block.dataset.previewWidth || 'half';
                    const previewHeight = block.dataset.previewHeight || 'regular';
                    const span = resolveSpan(previewWidth, getCurrentColumns());

                    block.style.gridColumn = 'span ' + String(span) + ' / span ' + String(span);
                    block.style.setProperty('--admin-block-height-factor', String(resolveHeightFactor(previewHeight)));
                }

                function revealBlockPanel(block) {
                    const panel = block?.querySelector('[data-layout-block-panel]');
                    if (!(panel instanceof HTMLElement)) {
                        return;
                    }

                    window.requestAnimationFrame(() => {
                        panel.scrollIntoView({
                            block: 'nearest',
                            behavior: 'smooth',
                        });
                    });
                }

                function setBlockMode(targetBlock, mode, revealPanel) {
                    getBlocks().forEach((block) => {
                        const isEditing = block === targetBlock && mode === 'edit';
                        const panel = block.querySelector('[data-layout-block-panel]');
                        const previewButton = block.querySelector('[data-layout-mode="preview"]');
                        const editButton = block.querySelector('[data-layout-mode="edit"]');

                        block.classList.toggle('is-editing', isEditing);
                        previewButton?.classList.toggle('is-active', !isEditing);
                        editButton?.classList.toggle('is-active', isEditing);

                        if (panel instanceof HTMLElement) {
                            panel.hidden = !isEditing;
                        }
                    });

                    if (mode === 'edit' && revealPanel) {
                        revealBlockPanel(targetBlock);
                    }
                }

                function moveBlock(block, direction) {
                    const blocks = getBlocks();
                    const currentIndex = blocks.indexOf(block);
                    const targetIndex = currentIndex + direction;

                    if (currentIndex === -1 || targetIndex < 0 || targetIndex >= blocks.length) {
                        return;
                    }

                    const targetBlock = blocks[targetIndex];

                    if (direction < 0) {
                        preview.insertBefore(block, targetBlock);
                    } else {
                        preview.insertBefore(targetBlock, block);
                    }
                }

                function updateBlock(block, index, totalBlocks) {
                    const previewWidth = block.dataset.previewWidth || 'half';
                    const previewHeight = block.dataset.previewHeight || 'regular';
                    const previewStatus = block.dataset.previewStatus || 'published';
                    const meta = block.querySelector('[data-layout-block-meta]');
                    const visibilityLabel = block.querySelector('[data-layout-visibility-label]');
                    const moveUpButton = block.querySelector('[data-layout-move="-1"]');
                    const moveDownButton = block.querySelector('[data-layout-move="1"]');
                    const editContentButton = block.querySelector('[data-layout-edit-content]');
                    const isContentTarget = getCurrentContentId() !== '' && block.dataset.layoutBlockId === getCurrentContentId();

                    block.classList.toggle('admin-layout-preview-block--hidden', previewStatus === 'hidden');
                    block.classList.toggle('is-content-target', isContentTarget);

                    if (meta) {
                        meta.textContent = 'Ordem visual ' + String(index + 1) + ' | ' + (widthLabels[previewWidth] || 'Largura') + ' | ' + (heightLabels[previewHeight] || 'Regular') + ' | ' + (statusLabels[previewStatus] || 'Publicado');
                    }

                    if (visibilityLabel) {
                        visibilityLabel.textContent = previewStatus === 'hidden' ? 'Publicar bloco' : 'Ocultar bloco';
                    }

                    block.querySelectorAll('[data-layout-choice="width"]').forEach((button) => {
                        button.classList.toggle('is-selected', button.dataset.choiceValue === previewWidth);
                    });

                    block.querySelectorAll('[data-layout-choice="height"]').forEach((button) => {
                        button.classList.toggle('is-selected', button.dataset.choiceValue === previewHeight);
                    });

                    if (moveUpButton instanceof HTMLButtonElement) {
                        moveUpButton.disabled = index === 0;
                    }

                    if (moveDownButton instanceof HTMLButtonElement) {
                        moveDownButton.disabled = index === totalBlocks - 1;
                    }

                    if (editContentButton instanceof HTMLElement) {
                        editContentButton.classList.toggle('is-active-target', isContentTarget);
                        editContentButton.textContent = isContentTarget ? 'Conteudo em edicao' : 'Editar conteudo';
                    }
                }

                function bindBlockControls(block) {
                    if (!(block instanceof HTMLElement) || block.dataset.layoutInlineBlockBound === '1') {
                        return;
                    }

                    block.dataset.layoutInlineBlockBound = '1';

                    bindControl(block.querySelector('[data-layout-mode="preview"]'), function() {
                        setBlockMode(block, 'preview', false);
                        syncBuilderState();
                    });

                    bindControl(block.querySelector('[data-layout-mode="edit"]'), function() {
                        setBlockMode(block, 'edit', true);
                        syncBuilderState();
                    });

                    block.querySelectorAll('[data-layout-choice="width"]').forEach(function(button) {
                        bindControl(button, function() {
                            block.dataset.previewWidth = button.dataset.choiceValue || 'half';
                            setBlockMode(block, 'edit', false);
                            syncBuilderState();
                        });
                    });

                    block.querySelectorAll('[data-layout-choice="height"]').forEach(function(button) {
                        bindControl(button, function() {
                            block.dataset.previewHeight = button.dataset.choiceValue || 'regular';
                            setBlockMode(block, 'edit', false);
                            syncBuilderState();
                        });
                    });

                    bindControl(block.querySelector('[data-layout-visibility-toggle]'), function() {
                        block.dataset.previewStatus = block.dataset.previewStatus === 'hidden' ? 'published' : 'hidden';
                        setBlockMode(block, 'edit', false);
                        syncBuilderState();
                    });

                    bindControl(block.querySelector('[data-layout-move="-1"]'), function() {
                        moveBlock(block, -1);
                        setBlockMode(block, 'edit', true);
                        syncBuilderState();
                    });

                    bindControl(block.querySelector('[data-layout-move="1"]'), function() {
                        moveBlock(block, 1);
                        setBlockMode(block, 'edit', true);
                        syncBuilderState();
                    });

                    bindControl(block.querySelector('[data-layout-edit-content]'), function() {
                        openContentBlock(block.dataset.layoutBlockId || '');
                    });

                    block.addEventListener('click', function(event) {
                        const eventElement = getEventElementTarget(event);

                        if (eventElement?.closest('[data-layout-inline-control="1"]')) {
                            return;
                        }

                        setBlockMode(block, 'edit', true);
                        syncBuilderState();
                    });

                    block.addEventListener('keydown', function(event) {
                        const eventElement = getEventElementTarget(event);

                        if (eventElement?.closest('[data-layout-inline-control="1"]')) {
                            return;
                        }

                        if (event.key !== 'Enter' && event.key !== ' ') {
                            return;
                        }

                        event.preventDefault();
                        setBlockMode(block, 'edit', true);
                        syncBuilderState();
                    });
                }

                function ensureEditingBlock() {
                    const currentContentId = getCurrentContentId();
                    const preferredBlock = currentContentId !== '' ?
                        getBlocks().find((block) => block.dataset.layoutBlockId === currentContentId) || null :
                        null;
                    const activeBlock = getBlocks().find((block) => block.classList.contains('is-editing')) || null;
                    const firstBlock = preferredBlock || activeBlock || getBlocks()[0] || null;

                    if (firstBlock) {
                        setBlockMode(firstBlock, 'edit', false);
                    }
                }

                function syncBuilderState() {
                    applyLayoutVariables();

                    const blocks = getBlocks();
                    blocks.forEach(function(block, index) {
                        bindBlockControls(block);
                        applyBlockFrame(block);
                        updateBlock(block, index, blocks.length);
                    });

                    serializeBlocks();

                    if (!builder.hidden) {
                        ensureEditingBlock();
                    }
                }

                function openBlockInBuilder(blockId) {
                    const targetBlock = getBlocks().find((block) => block.dataset.layoutBlockId === String(blockId || '')) || null;
                    if (!targetBlock) {
                        return;
                    }

                    setBlockMode(targetBlock, 'edit', true);
                    syncBuilderState();
                }

                layoutInputs.forEach((input) => {
                    input.addEventListener('input', syncBuilderState);
                    input.addEventListener('change', syncBuilderState);
                });

                layoutForm.addEventListener('submit', serializeBlocks);

                builderStates.set(pageKey, {
                    sync: syncBuilderState,
                    openBlock: openBlockInBuilder,
                });

                syncBuilderState();
            }

            builders.forEach(initBuilder);
            setContentFormMode(getCurrentContentId());

            if (document.body.dataset.adminInlineBuildersGlobalBound !== '1') {
                if (pageField instanceof HTMLSelectElement) {
                    pageField.addEventListener('change', function() {
                        syncVisibleBuilders();
                        syncAllBuilders();
                    });
                }

                document.body.dataset.adminInlineBuildersGlobalBound = '1';
            }

            syncVisibleBuilders();
            syncAllBuilders();
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.setTimeout(initAdminInlineBuilders, 0);
        });

        window.addEventListener('load', function() {
            window.setTimeout(initAdminInlineBuilders, 0);
        }, {
            once: true
        });
    }());
</script>
<?php include_once 'includes/footer.php'; ?>
