<?php

class ContentBlockManager
{
    private $blocksFile;
    private $layoutsFile;

    private const PAGE_DEFINITIONS = [
        'contact' => [
            'label' => 'Contato',
            'path' => 'contact.php',
            'allowed_types' => ['contact_info', 'map_embed', 'text_card'],
            'allowed_widths' => ['half', 'full'],
            'supports_layout_builder' => false,
        ],
        'thematic_areas' => [
            'label' => 'Areas tematicas',
            'path' => 'implement.php',
            'allowed_types' => ['thematic_intro', 'thematic_topic', 'thematic_cta'],
            'allowed_widths' => ['half', 'full'],
            'supports_layout_builder' => false,
        ],
        'about' => [
            'label' => 'Sobre',
            'path' => 'about.php',
            'allowed_types' => ['about_text', 'about_media', 'about_list', 'about_cta'],
            'allowed_widths' => ['span_1', 'span_2', 'span_3', 'span_4', 'full'],
            'supports_layout_builder' => true,
        ],
    ];

    private const TYPE_DEFINITIONS = [
        'contact_info' => [
            'label' => 'Contato principal',
            'description' => 'Card com titulo, texto, lista de contatos e CTA.',
        ],
        'map_embed' => [
            'label' => 'Mapa incorporado',
            'description' => 'Card com iframe externo, como Google Maps.',
        ],
        'text_card' => [
            'label' => 'Card de texto',
            'description' => 'Bloco generico para titulo, texto, lista e botao.',
        ],
        'thematic_intro' => [
            'label' => 'Intro de areas',
            'description' => 'Bloco de abertura da pagina de Areas Tematicas.',
        ],
        'thematic_topic' => [
            'label' => 'Area tematica',
            'description' => 'Card com tag, titulo e descricao de uma area tematica.',
        ],
        'thematic_cta' => [
            'label' => 'CTA tematico',
            'description' => 'Card complementar para regulamento, contato ou chamadas finais.',
        ],
        'about_text' => [
            'label' => 'Texto sobre',
            'description' => 'Card textual para missao, historia ou apresentacao institucional.',
        ],
        'about_media' => [
            'label' => 'Midia sobre',
            'description' => 'Card visual com imagem clara e opcionalmente imagem para o tema escuro.',
        ],
        'about_list' => [
            'label' => 'Lista sobre',
            'description' => 'Card para infraestrutura, recursos ou destaques em lista.',
        ],
        'about_cta' => [
            'label' => 'CTA sobre',
            'description' => 'Card complementar com texto, itens estruturados e chamada para acao.',
        ],
    ];

    private const WIDTH_DEFINITIONS = [
        'half' => 'Coluna simples',
        'full' => 'Largura total',
        'span_1' => '1 coluna',
        'span_2' => '2 colunas',
        'span_3' => '3 colunas',
        'span_4' => '4 colunas',
    ];

    private const HEIGHT_DEFINITIONS = [
        'compact' => 'Compacto',
        'regular' => 'Regular',
        'tall' => 'Alto',
    ];

    private const STATUS_DEFINITIONS = [
        'published' => 'Publicado',
        'hidden' => 'Oculto',
    ];

    private const GRID_STYLE_DEFINITIONS = [
        'standard' => 'Grid regular',
        'dense' => 'Grid denso',
    ];

    private const DEFAULT_LAYOUTS = [
        'about' => [
            'page_key' => 'about',
            'grid_style' => 'dense',
            'columns' => 4,
            'mobile_columns' => 1,
            'gap' => 24,
            'container_width' => 1220,
            'block_padding' => 32,
            'block_min_height' => 210,
        ],
    ];

    public function __construct($blocksFile = null, $layoutsFile = null)
    {
        $this->blocksFile = $blocksFile ?: __DIR__ . '/../data/content_blocks.json';
        $this->layoutsFile = $layoutsFile ?: __DIR__ . '/../data/content_layouts.json';

        $this->ensureFileExists();
        $this->ensureLayoutFileExists();
    }

    public function getPageDefinitions(): array
    {
        return self::PAGE_DEFINITIONS;
    }

    public function getTypeDefinitions(): array
    {
        return self::TYPE_DEFINITIONS;
    }

    public function getWidthDefinitions(): array
    {
        return self::WIDTH_DEFINITIONS;
    }

    public function getHeightDefinitions(): array
    {
        return self::HEIGHT_DEFINITIONS;
    }

    public function getGridStyleDefinitions(): array
    {
        return self::GRID_STYLE_DEFINITIONS;
    }

    public function getStatusDefinitions(): array
    {
        return self::STATUS_DEFINITIONS;
    }

    public function getAllowedTypesForPage(string $pageKey): array
    {
        $pageKey = $this->normalizePageKey($pageKey);
        $allowedTypes = self::PAGE_DEFINITIONS[$pageKey]['allowed_types'] ?? array_keys(self::TYPE_DEFINITIONS);

        return array_values(array_filter($allowedTypes, static function (string $type): bool {
            return isset(self::TYPE_DEFINITIONS[$type]);
        }));
    }

    public function getAllowedWidthsForPage(string $pageKey): array
    {
        $pageKey = $this->normalizePageKey($pageKey);
        $allowedWidths = self::PAGE_DEFINITIONS[$pageKey]['allowed_widths'] ?? array_keys(self::WIDTH_DEFINITIONS);

        return array_values(array_filter($allowedWidths, static function (string $width): bool {
            return isset(self::WIDTH_DEFINITIONS[$width]);
        }));
    }

    public function getAllowedWidthDefinitionsForPage(string $pageKey): array
    {
        $definitions = [];

        foreach ($this->getAllowedWidthsForPage($pageKey) as $widthKey) {
            $definitions[$widthKey] = self::WIDTH_DEFINITIONS[$widthKey];
        }

        return $definitions;
    }

    public function getDefaultTypeForPage(string $pageKey): string
    {
        $allowedTypes = $this->getAllowedTypesForPage($pageKey);

        return $allowedTypes[0] ?? 'text_card';
    }

    public function getDefaultWidthForPage(string $pageKey): string
    {
        if ($this->normalizePageKey($pageKey) === 'about') {
            return 'span_2';
        }

        $allowedWidths = $this->getAllowedWidthsForPage($pageKey);

        return $allowedWidths[0] ?? 'half';
    }

    public function getDefaultHeightForPage(string $pageKey): string
    {
        unset($pageKey);

        return 'regular';
    }

    public function isTypeAllowedForPage(string $pageKey, string $type): bool
    {
        $normalizedType = $this->normalizeType($type);

        return in_array($normalizedType, $this->getAllowedTypesForPage($pageKey), true);
    }

    public function isWidthAllowedForPage(string $pageKey, string $width): bool
    {
        $normalizedWidth = $this->normalizeWidth($width);

        return in_array($normalizedWidth, $this->getAllowedWidthsForPage($pageKey), true);
    }

    public function pageSupportsLayoutBuilder(string $pageKey): bool
    {
        $pageKey = $this->normalizePageKey($pageKey);

        return !empty(self::PAGE_DEFINITIONS[$pageKey]['supports_layout_builder']);
    }

    public function getPageLabel(string $pageKey): string
    {
        $pageKey = $this->normalizePageKey($pageKey);

        return self::PAGE_DEFINITIONS[$pageKey]['label'] ?? 'Pagina';
    }

    public function getTypeLabel(string $type): string
    {
        $type = $this->normalizeType($type);

        return self::TYPE_DEFINITIONS[$type]['label'] ?? 'Bloco';
    }

    public function getWidthLabel(string $width): string
    {
        $width = $this->normalizeWidth($width);

        return self::WIDTH_DEFINITIONS[$width] ?? self::WIDTH_DEFINITIONS['half'];
    }

    public function getHeightLabel(string $height): string
    {
        $height = $this->normalizeHeight($height);

        return self::HEIGHT_DEFINITIONS[$height] ?? self::HEIGHT_DEFINITIONS['regular'];
    }

    public function getStatusLabel(string $status): string
    {
        $status = $this->normalizeStatus($status);

        return self::STATUS_DEFINITIONS[$status] ?? self::STATUS_DEFINITIONS['published'];
    }

    public function getGridStyleLabel(string $gridStyle): string
    {
        $gridStyle = $this->normalizeGridStyle($gridStyle);

        return self::GRID_STYLE_DEFINITIONS[$gridStyle] ?? self::GRID_STYLE_DEFINITIONS['standard'];
    }

    public function getWidthSpan(string $width, int $columns = 4): int
    {
        $columns = max(1, $columns);
        $width = $this->normalizeWidth($width);

        switch ($width) {
            case 'full':
                return $columns;
            case 'span_4':
                return min(4, $columns);
            case 'span_3':
                return min(3, $columns);
            case 'span_2':
                return min(2, $columns);
            case 'span_1':
            case 'half':
            default:
                return 1;
        }
    }

    public function getHeightFactor(string $height): float
    {
        $height = $this->normalizeHeight($height);

        switch ($height) {
            case 'compact':
                return 0.82;
            case 'tall':
                return 1.34;
            case 'regular':
            default:
                return 1.0;
        }
    }

    public function listBlocks(?string $pageKey = null, bool $publishedOnly = false): array
    {
        $blocks = $this->loadBlocks();

        if ($pageKey !== null && trim($pageKey) !== '') {
            $normalizedPageKey = $this->normalizePageKey($pageKey);
            $blocks = array_values(array_filter($blocks, static function (array $block) use ($normalizedPageKey): bool {
                return $block['page_key'] === $normalizedPageKey;
            }));
        }

        if ($publishedOnly) {
            $blocks = array_values(array_filter($blocks, static function (array $block): bool {
                return ($block['status'] ?? 'published') === 'published';
            }));
        }

        return $blocks;
    }

    public function getPageBlocks(string $pageKey, bool $publishedOnly = true): array
    {
        return $this->listBlocks($pageKey, $publishedOnly);
    }

    public function getBlock(string $blockId)
    {
        foreach ($this->loadBlocks() as $block) {
            if ((string) ($block['id'] ?? '') === (string) $blockId) {
                return $block;
            }
        }

        return false;
    }

    public function getNextPosition(string $pageKey): int
    {
        $position = 0;

        foreach ($this->getPageBlocks($pageKey, false) as $block) {
            $position = max($position, (int) ($block['position'] ?? 0));
        }

        return $position + 10;
    }

    public function formatItemsForTextarea(array $items): string
    {
        $lines = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string) ($item['label'] ?? ''));
            $value = trim((string) ($item['value'] ?? ''));
            $url = trim((string) ($item['url'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            if ($label === '') {
                $lines[] = $value;
                continue;
            }

            $line = $label . ' | ' . $value;
            if ($url !== '') {
                $line .= ' | ' . $url;
            }

            $lines[] = $line;
        }

        return implode(PHP_EOL, $lines);
    }

    public function listPageLayouts(): array
    {
        return $this->loadLayouts();
    }

    public function getPageLayout(string $pageKey): array
    {
        $pageKey = $this->normalizePageKey($pageKey);
        $layouts = $this->loadLayouts();

        if (isset($layouts[$pageKey]) && is_array($layouts[$pageKey])) {
            return $layouts[$pageKey];
        }

        return $this->normalizeLayout(['page_key' => $pageKey]);
    }

    public function adminSaveLayout(string $pageKey, array $data): array
    {
        $pageKey = $this->normalizePageKey($pageKey);

        if (!$this->pageSupportsLayoutBuilder($pageKey)) {
            return ['success' => false, 'errors' => ['Essa pagina ainda nao possui um builder estrutural.']];
        }

        $layoutData = [
            'page_key' => $pageKey,
            'grid_style' => $data['grid_style'] ?? 'standard',
            'columns' => $data['columns'] ?? 4,
            'mobile_columns' => $data['mobile_columns'] ?? 1,
            'gap' => $data['gap'] ?? 24,
            'container_width' => $data['container_width'] ?? 1220,
            'block_padding' => $data['block_padding'] ?? 32,
            'block_min_height' => $data['block_min_height'] ?? 210,
        ];

        $normalizedLayout = $this->normalizeLayout($layoutData);
        $errors = [];

        if ($normalizedLayout['columns'] < $normalizedLayout['mobile_columns']) {
            $errors[] = 'A grade mobile nao pode ter mais colunas que a grade desktop.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $layouts = $this->loadLayouts();
        $layouts[$pageKey] = $normalizedLayout;
        $this->saveLayouts($layouts);

        return ['success' => true, 'layout' => $normalizedLayout];
    }

    public function adminSaveBlock(?string $blockId, array $data): array
    {
        $blocks = $this->loadBlocks();
        $isCreate = $blockId === null || trim($blockId) === '';
        $blockIndex = $isCreate ? null : $this->findBlockIndex($blocks, $blockId);

        if (!$isCreate && $blockIndex === null) {
            return ['success' => false, 'errors' => ['Bloco nao encontrado.']];
        }

        $pageKey = $this->normalizePageKey((string) ($data['page_key'] ?? 'contact'));
        $typeInput = trim((string) ($data['type'] ?? ''));
        $type = $typeInput !== '' ? $this->normalizeType($typeInput) : $this->getDefaultTypeForPage($pageKey);
        $widthInput = trim((string) ($data['width'] ?? ''));
        $width = $widthInput !== '' ? $this->normalizeWidth($widthInput) : $this->getDefaultWidthForPage($pageKey);
        $height = $this->normalizeHeight((string) ($data['height'] ?? $this->getDefaultHeightForPage($pageKey)));
        $status = $this->normalizeStatus((string) ($data['status'] ?? 'published'));
        $name = trim((string) ($data['name'] ?? ''));
        $eyebrow = trim((string) ($data['eyebrow'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $ctaLabel = trim((string) ($data['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($data['cta_url'] ?? ''));
        $embedUrl = trim((string) ($data['embed_url'] ?? ''));
        $mediaUrl = trim((string) ($data['media_url'] ?? ''));
        $mediaDarkUrl = trim((string) ($data['media_dark_url'] ?? ''));
        $mediaAlt = trim((string) ($data['media_alt'] ?? ''));
        $items = $this->normalizeItems($data['items_text'] ?? ($data['items'] ?? []));
        $showContextNote = $this->normalizeBool($data['show_context_note'] ?? false);
        $positionInput = trim((string) ($data['position'] ?? ''));
        $position = $positionInput !== '' ? (int) $positionInput : $this->getNextPosition($pageKey);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Nome interno do bloco e obrigatorio.';
        }

        if ($title === '' && $type !== 'about_media') {
            $errors[] = 'Titulo do bloco e obrigatorio.';
        }

        if (!$this->isTypeAllowedForPage($pageKey, $type)) {
            $errors[] = 'Esse tipo de bloco nao esta disponivel para a pagina selecionada.';
        }

        if (!$this->isWidthAllowedForPage($pageKey, $width)) {
            $errors[] = 'Esse tamanho horizontal nao esta disponivel para a pagina selecionada.';
        }

        if ($type === 'map_embed' && $embedUrl === '') {
            $errors[] = 'URL de embed e obrigatoria para blocos de mapa.';
        }

        if ($type === 'about_media' && $mediaUrl === '') {
            $errors[] = 'Imagem principal e obrigatoria para blocos de midia da pagina Sobre.';
        }

        if ($ctaLabel !== '' && $ctaUrl === '') {
            $errors[] = 'Informe a URL do botao quando houver um rotulo de CTA.';
        }

        if ($mediaDarkUrl !== '' && $mediaAlt === '') {
            $errors[] = 'Informe o texto alternativo quando houver imagem configurada para a pagina Sobre.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($isCreate) {
            $savedBlock = [
                'id' => uniqid('blk_'),
                'page_key' => $pageKey,
                'type' => $type,
                'name' => $name,
                'eyebrow' => $eyebrow,
                'title' => $title,
                'body' => $body,
                'items' => $items,
                'cta_label' => $ctaLabel,
                'cta_url' => $ctaUrl,
                'embed_url' => $embedUrl,
                'media_url' => $mediaUrl,
                'media_dark_url' => $mediaDarkUrl,
                'media_alt' => $mediaAlt,
                'width' => $width,
                'height' => $height,
                'position' => $position,
                'status' => $status,
                'show_context_note' => $showContextNote,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];

            $blocks[] = $savedBlock;
        } else {
            $blocks[$blockIndex]['page_key'] = $pageKey;
            $blocks[$blockIndex]['type'] = $type;
            $blocks[$blockIndex]['name'] = $name;
            $blocks[$blockIndex]['eyebrow'] = $eyebrow;
            $blocks[$blockIndex]['title'] = $title;
            $blocks[$blockIndex]['body'] = $body;
            $blocks[$blockIndex]['items'] = $items;
            $blocks[$blockIndex]['cta_label'] = $ctaLabel;
            $blocks[$blockIndex]['cta_url'] = $ctaUrl;
            $blocks[$blockIndex]['embed_url'] = $embedUrl;
            $blocks[$blockIndex]['media_url'] = $mediaUrl;
            $blocks[$blockIndex]['media_dark_url'] = $mediaDarkUrl;
            $blocks[$blockIndex]['media_alt'] = $mediaAlt;
            $blocks[$blockIndex]['width'] = $width;
            $blocks[$blockIndex]['height'] = $height;
            $blocks[$blockIndex]['position'] = $position;
            $blocks[$blockIndex]['status'] = $status;
            $blocks[$blockIndex]['show_context_note'] = $showContextNote;
            $blocks[$blockIndex]['updated_at'] = $this->now();

            $savedBlock = $blocks[$blockIndex];
        }

        $this->saveBlocks($blocks);

        return ['success' => true, 'block' => $savedBlock, 'created' => $isCreate];
    }

    public function deleteBlock(string $blockId): bool
    {
        $blocks = $this->loadBlocks();
        $updatedBlocks = [];
        $removed = false;

        foreach ($blocks as $block) {
            if ((string) ($block['id'] ?? '') === (string) $blockId) {
                $removed = true;
                continue;
            }

            $updatedBlocks[] = $block;
        }

        if (!$removed) {
            return false;
        }

        return $this->saveBlocks($updatedBlocks);
    }

    private function ensureFileExists(): void
    {
        $directory = dirname($this->blocksFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!file_exists($this->blocksFile)) {
            file_put_contents(
                $this->blocksFile,
                json_encode($this->defaultBlocks(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        }
    }

    private function ensureLayoutFileExists(): void
    {
        $directory = dirname($this->layoutsFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!file_exists($this->layoutsFile)) {
            file_put_contents(
                $this->layoutsFile,
                json_encode($this->defaultLayouts(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        }
    }

    private function defaultBlocks(): array
    {
        $timestamp = $this->now();

        return [
            [
                'id' => 'blk_contact_primary',
                'page_key' => 'contact',
                'type' => 'contact_info',
                'name' => 'Contato principal',
                'eyebrow' => 'Canal oficial',
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
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 10,
                'status' => 'published',
                'show_context_note' => true,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_contact_map',
                'page_key' => 'contact',
                'type' => 'map_embed',
                'name' => 'Mapa do campus',
                'eyebrow' => '',
                'title' => 'Mapa',
                'body' => '',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => 'https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d228.4439040435433!2d-45.4258447087537!3d-23.636501255140573!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1spt-BR!2sbr!4v1763413745838!5m2!1spt-BR!2sbr',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 20,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_intro',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_intro',
                'name' => 'Intro Areas Tematicas',
                'eyebrow' => 'CEPIN-CIS',
                'title' => 'Areas tematicas',
                'body' => 'As Areas Tematicas nas quais serao alinhadas as linhas de pesquisa foram definidas para orientar as atividades do CEPIN-CIS e compreendem:',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'full',
                'height' => 'regular',
                'position' => 10,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_educis',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_topic',
                'name' => 'Area Tematica EduCIS',
                'eyebrow' => 'EduCIS',
                'title' => 'Formacao de recursos humanos para cidades inteligentes e sustentaveis',
                'body' => 'Desenvolvimento de recursos humanos e metodos ageis de capacitacao para impulsionar a inovacao em Cidades Inteligentes e Sustentaveis.',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 20,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_ecomat',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_topic',
                'name' => 'Area Tematica EcoMat',
                'eyebrow' => 'EcoMat',
                'title' => 'Novos materiais e economia circular',
                'body' => 'Investigar materiais sustentaveis e promover economia circular, reduzindo o impacto ambiental.',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 30,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_iot',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_topic',
                'name' => 'Area Tematica IoT',
                'eyebrow' => 'IoT',
                'title' => 'Desenvolvimento tecnologico e conectividade para cidades inteligentes e sustentaveis',
                'body' => 'Desenvolver tecnologias avancadas e solucoes de conectividade para criar ambientes urbanos mais inteligentes, eficientes e sustentaveis.',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 40,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_carbonzero',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_topic',
                'name' => 'Area Tematica CarbonZero',
                'eyebrow' => 'CarbonZero',
                'title' => 'Descarbonizacao do ambiente construido',
                'body' => 'Promover a reducao das emissoes de carbono em edificios, infraestrutura, mobilidade urbana, fontes de energia e saneamento ambiental.',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 50,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_urbansmart',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_topic',
                'name' => 'Area Tematica UrbanSmart',
                'eyebrow' => 'UrbanSmart',
                'title' => 'Monitoramento e operacoes urbanas inteligentes',
                'body' => 'Desenvolver solucoes para monitorar e gerenciar a infraestrutura urbana com gemeos digitais, plataformas digitais, sistemas autonomos e drones, ampliando a resiliencia climatica.',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 60,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_regulation',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_cta',
                'name' => 'Regulamento CEPIN-CIS',
                'eyebrow' => '',
                'title' => 'Regulamento',
                'body' => 'O regulamento do Centro de Pesquisa e Inovacao em Cidades Inteligentes e Sustentaveis (CEPIN-CIS) foi aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco normativo consolida a missao do CEPIN-CIS como espaco de fomento a pesquisa aplicada, a inovacao tecnologica e a reflexao critica sobre os desafios contemporaneos das cidades.' . PHP_EOL . PHP_EOL . 'O regulamento estabelece as diretrizes para a participacao de servidores e discentes vinculados a projetos de ensino, pesquisa ou extensao que dialoguem com as areas tematicas do Centro, alem de abrir espaco para a colaboracao de pesquisadores externos.',
                'items' => [],
                'cta_label' => 'Clique aqui para ver o regulamento',
                'cta_url' => 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 70,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_thematic_contact',
                'page_key' => 'thematic_areas',
                'type' => 'thematic_cta',
                'name' => 'Contato Areas Tematicas',
                'eyebrow' => '',
                'title' => 'Contato',
                'body' => 'Quer saber mais ou colaborar com o CEPIN-CIS? Entre em contato com nossa equipe de pesquisa.',
                'items' => [
                    [
                        'label' => 'Email institucional',
                        'value' => 'cepin.cis@ifspcaraguatatuba.edu.br',
                        'url' => 'mailto:cepin.cis@ifspcaraguatatuba.edu.br',
                    ],
                ],
                'cta_label' => 'Enviar E-mail',
                'cta_url' => './contact.php',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'half',
                'height' => 'regular',
                'position' => 80,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_about_intro',
                'page_key' => 'about',
                'type' => 'about_text',
                'name' => 'Sobre CEPIN-CIS',
                'eyebrow' => 'CEPIN-CIS',
                'title' => 'Sobre nos',
                'body' => 'O Centro de Pesquisa e Inovacao em Cidades Inteligentes e Sustentaveis (CEPIN-CIS), implementado no IFSP campus Caraguatatuba, tem como missao fomentar o desenvolvimento de cidades inteligentes e sustentaveis. Para isso, atua como um repositorio de tecnologias, um espaco dedicado a experimentacao pratica e um agente de interlocucao capaz de estabelecer conexoes produtivas entre os setores publico e privado.' . PHP_EOL . PHP_EOL . 'Sua atuacao se estrutura de maneira a promover, de forma inclusiva, colaborativa e equitativa, o debate e a construcao de solucoes que contemplem as dimensoes ambientais, economicas, sociais e culturais da sustentabilidade.' . PHP_EOL . PHP_EOL . 'Nesse sentido, o CEPIN-CIS direciona seus esforcos para desenvolver investigacao fundamental ou aplicada voltada ao campo das cidades inteligentes e sustentaveis, aproximando pesquisa, transferencia de tecnologia e aplicacao pratica.',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'span_2',
                'height' => 'tall',
                'position' => 10,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_about_banner',
                'page_key' => 'about',
                'type' => 'about_media',
                'name' => 'Banner institucional',
                'eyebrow' => '',
                'title' => '',
                'body' => '',
                'items' => [],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => './img/banner.png',
                'media_dark_url' => './img/bannerescuro.png',
                'media_alt' => 'Logo CEPIN-CIS',
                'width' => 'span_2',
                'height' => 'tall',
                'position' => 20,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_about_infrastructure',
                'page_key' => 'about',
                'type' => 'about_list',
                'name' => 'Infraestruturas',
                'eyebrow' => 'Estrutura',
                'title' => 'Infraestruturas',
                'body' => 'O CEPIN-CIS esta localizado na sala 107B do IFSP Campus Caraguatatuba, e sua infraestrutura conta com os seguintes recursos:',
                'items' => [
                    ['label' => '', 'value' => '03 Desktop HP 280 G5 SFF com processador Intel Core i7 de 10a geracao, SSD 512 GB e 16 GB de RAM.', 'url' => ''],
                    ['label' => '', 'value' => '03 monitores LG 23.8 Full HD 75Hz 5ms HDMI.', 'url' => ''],
                    ['label' => '', 'value' => '01 notebook VAIO FE15 AMD Ryzen 7 com Windows 11, 16 GB e SSD 512 GB.', 'url' => ''],
                    ['label' => '', 'value' => '01 switch TP-Link 8 portas TL-SG108E.', 'url' => ''],
                    ['label' => '', 'value' => '07 kits Arduino com sensores, display, motores e placa Mega 2560 R3.', 'url' => ''],
                    ['label' => '', 'value' => '01 kit de sensores diversos compativeis com Arduino.', 'url' => ''],
                ],
                'cta_label' => '',
                'cta_url' => '',
                'embed_url' => '',
                'media_url' => './img/Monitor.png',
                'media_dark_url' => '',
                'media_alt' => 'Monitor ilustrando a infraestrutura do CEPIN-CIS',
                'width' => 'full',
                'height' => 'regular',
                'position' => 30,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_about_regulation',
                'page_key' => 'about',
                'type' => 'about_cta',
                'name' => 'Regulamento sobre',
                'eyebrow' => '',
                'title' => 'Regulamento',
                'body' => 'O regulamento do Centro de Pesquisa e Inovacao em Cidades Inteligentes e Sustentaveis (CEPIN-CIS) foi aprovado em 2024 pelo Conselho de Campus (CONCAM) do IFSP Caraguatatuba. Este marco normativo consolida a missao do CEPIN-CIS como espaco de fomento a pesquisa aplicada, a inovacao tecnologica e a reflexao critica sobre os desafios contemporaneos das cidades.' . PHP_EOL . PHP_EOL . 'O regulamento estabelece as diretrizes para a participacao de servidores e discentes vinculados a projetos de ensino, pesquisa ou extensao que dialoguem com as areas tematicas do Centro, alem de abrir espaco para a colaboracao de pesquisadores externos.',
                'items' => [],
                'cta_label' => 'Clique aqui para ver o regulamento',
                'cta_url' => 'https://www.ifspcaraguatatuba.edu.br/images/CEPIN/Portaria_Normativa_n%C2%BA_14-2024_Aprova_regulamento_CEPIN-CIS.pdf',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'span_2',
                'height' => 'regular',
                'position' => 40,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'id' => 'blk_about_contact',
                'page_key' => 'about',
                'type' => 'about_cta',
                'name' => 'Contato sobre',
                'eyebrow' => '',
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
                'cta_label' => 'Enviar E-mail',
                'cta_url' => 'mailto:cepin.cis@ifspcaraguatatuba.edu.br',
                'embed_url' => '',
                'media_url' => '',
                'media_dark_url' => '',
                'media_alt' => '',
                'width' => 'span_2',
                'height' => 'regular',
                'position' => 50,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];
    }

    private function defaultLayouts(): array
    {
        $layouts = [];

        foreach (self::DEFAULT_LAYOUTS as $pageKey => $layout) {
            $layouts[$pageKey] = $this->normalizeLayout(array_merge($layout, ['page_key' => $pageKey]));
        }

        return $layouts;
    }

    private function loadBlocks(): array
    {
        if (!file_exists($this->blocksFile)) {
            return $this->defaultBlocks();
        }

        $contents = @file_get_contents($this->blocksFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded)) {
            return $this->defaultBlocks();
        }

        $blocks = [];

        foreach ($decoded as $block) {
            if (is_array($block)) {
                $blocks[] = $this->normalizeBlock($block);
            }
        }

        usort($blocks, static function (array $left, array $right): int {
            $pageComparison = strcmp((string) $left['page_key'], (string) $right['page_key']);
            if ($pageComparison !== 0) {
                return $pageComparison;
            }

            $positionComparison = (int) ($left['position'] ?? 0) <=> (int) ($right['position'] ?? 0);
            if ($positionComparison !== 0) {
                return $positionComparison;
            }

            return strtotime((string) $right['updated_at']) <=> strtotime((string) $left['updated_at']);
        });

        return $blocks;
    }

    private function loadLayouts(): array
    {
        if (!file_exists($this->layoutsFile)) {
            return $this->defaultLayouts();
        }

        $contents = @file_get_contents($this->layoutsFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded)) {
            return $this->defaultLayouts();
        }

        $layouts = [];

        foreach ($decoded as $pageKey => $layout) {
            if (!is_array($layout)) {
                continue;
            }

            $layout['page_key'] = is_string($pageKey) ? $pageKey : ($layout['page_key'] ?? 'about');
            $normalizedLayout = $this->normalizeLayout($layout);

            if ($this->pageSupportsLayoutBuilder($normalizedLayout['page_key'])) {
                $layouts[$normalizedLayout['page_key']] = $normalizedLayout;
            }
        }

        foreach ($this->defaultLayouts() as $pageKey => $layout) {
            if (!isset($layouts[$pageKey])) {
                $layouts[$pageKey] = $layout;
            }
        }

        ksort($layouts);

        return $layouts;
    }

    private function saveBlocks(array $blocks): bool
    {
        $normalizedBlocks = [];

        foreach ($blocks as $block) {
            if (is_array($block)) {
                $normalizedBlocks[] = $this->normalizeBlock($block);
            }
        }

        usort($normalizedBlocks, static function (array $left, array $right): int {
            $pageComparison = strcmp((string) $left['page_key'], (string) $right['page_key']);
            if ($pageComparison !== 0) {
                return $pageComparison;
            }

            return (int) ($left['position'] ?? 0) <=> (int) ($right['position'] ?? 0);
        });

        return (bool) file_put_contents(
            $this->blocksFile,
            json_encode($normalizedBlocks, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function saveLayouts(array $layouts): bool
    {
        $normalizedLayouts = [];

        foreach ($layouts as $pageKey => $layout) {
            if (!is_array($layout)) {
                continue;
            }

            $layout['page_key'] = is_string($pageKey) ? $pageKey : ($layout['page_key'] ?? 'about');
            $normalizedLayout = $this->normalizeLayout($layout);

            if ($this->pageSupportsLayoutBuilder($normalizedLayout['page_key'])) {
                $normalizedLayouts[$normalizedLayout['page_key']] = $normalizedLayout;
            }
        }

        ksort($normalizedLayouts);

        return (bool) file_put_contents(
            $this->layoutsFile,
            json_encode($normalizedLayouts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizeBlock(array $block): array
    {
        $createdAt = (string) ($block['created_at'] ?? $this->now());
        $updatedAt = (string) ($block['updated_at'] ?? $createdAt);
        $pageKey = $this->normalizePageKey((string) ($block['page_key'] ?? 'contact'));
        $type = $this->normalizeType((string) ($block['type'] ?? $this->getDefaultTypeForPage($pageKey)));
        $width = $this->normalizeWidth((string) ($block['width'] ?? $this->getDefaultWidthForPage($pageKey)));

        if (!$this->isTypeAllowedForPage($pageKey, $type)) {
            $type = $this->getDefaultTypeForPage($pageKey);
        }

        if (!$this->isWidthAllowedForPage($pageKey, $width)) {
            $width = $this->getDefaultWidthForPage($pageKey);
        }

        return [
            'id' => (string) ($block['id'] ?? uniqid('blk_')),
            'page_key' => $pageKey,
            'type' => $type,
            'name' => trim((string) ($block['name'] ?? '')),
            'eyebrow' => trim((string) ($block['eyebrow'] ?? '')),
            'title' => trim((string) ($block['title'] ?? '')),
            'body' => trim((string) ($block['body'] ?? '')),
            'items' => $this->normalizeItems($block['items'] ?? []),
            'cta_label' => trim((string) ($block['cta_label'] ?? '')),
            'cta_url' => trim((string) ($block['cta_url'] ?? '')),
            'embed_url' => trim((string) ($block['embed_url'] ?? '')),
            'media_url' => trim((string) ($block['media_url'] ?? '')),
            'media_dark_url' => trim((string) ($block['media_dark_url'] ?? '')),
            'media_alt' => trim((string) ($block['media_alt'] ?? '')),
            'width' => $width,
            'height' => $this->normalizeHeight((string) ($block['height'] ?? $this->getDefaultHeightForPage($pageKey))),
            'position' => (int) ($block['position'] ?? 10),
            'status' => $this->normalizeStatus((string) ($block['status'] ?? 'published')),
            'show_context_note' => $this->normalizeBool($block['show_context_note'] ?? false),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function normalizeLayout(array $layout): array
    {
        $pageKey = $this->normalizePageKey((string) ($layout['page_key'] ?? 'about'));
        $defaults = self::DEFAULT_LAYOUTS[$pageKey] ?? self::DEFAULT_LAYOUTS['about'];

        return [
            'page_key' => $pageKey,
            'grid_style' => $this->normalizeGridStyle((string) ($layout['grid_style'] ?? $defaults['grid_style'])),
            'columns' => $this->normalizeIntRange($layout['columns'] ?? $defaults['columns'], 1, 4, $defaults['columns']),
            'mobile_columns' => $this->normalizeIntRange($layout['mobile_columns'] ?? $defaults['mobile_columns'], 1, 2, $defaults['mobile_columns']),
            'gap' => $this->normalizeIntRange($layout['gap'] ?? $defaults['gap'], 12, 56, $defaults['gap']),
            'container_width' => $this->normalizeIntRange($layout['container_width'] ?? $defaults['container_width'], 880, 1480, $defaults['container_width']),
            'block_padding' => $this->normalizeIntRange($layout['block_padding'] ?? $defaults['block_padding'], 18, 56, $defaults['block_padding']),
            'block_min_height' => $this->normalizeIntRange($layout['block_min_height'] ?? $defaults['block_min_height'], 140, 420, $defaults['block_min_height']),
        ];
    }

    private function normalizeItems($items): array
    {
        if (is_string($items)) {
            $rows = preg_split('/\r\n|\r|\n/', $items) ?: [];
            $normalizedItems = [];

            foreach ($rows as $row) {
                $row = trim((string) $row);
                if ($row === '') {
                    continue;
                }

                $parts = array_map('trim', explode('|', $row));
                $item = [
                    'label' => $parts[0] ?? '',
                    'value' => $parts[1] ?? ($parts[0] ?? ''),
                    'url' => $parts[2] ?? '',
                ];

                $normalizedItem = $this->normalizeItem($item);
                if ($normalizedItem !== null) {
                    $normalizedItems[] = $normalizedItem;
                }
            }

            return $normalizedItems;
        }

        if (!is_array($items)) {
            return [];
        }

        $normalizedItems = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $item = ['label' => '', 'value' => $item, 'url' => ''];
            }

            if (!is_array($item)) {
                continue;
            }

            $normalizedItem = $this->normalizeItem($item);
            if ($normalizedItem !== null) {
                $normalizedItems[] = $normalizedItem;
            }
        }

        return $normalizedItems;
    }

    private function normalizeItem(array $item): ?array
    {
        $label = trim((string) ($item['label'] ?? ''));
        $value = trim((string) ($item['value'] ?? ''));
        $url = trim((string) ($item['url'] ?? ''));

        if ($label === '' && $value === '') {
            return null;
        }

        if ($value === '') {
            $value = $label;
        }

        return [
            'label' => $label,
            'value' => $value,
            'url' => $url,
        ];
    }

    private function normalizePageKey(string $pageKey): string
    {
        $pageKey = strtolower(trim($pageKey));

        return isset(self::PAGE_DEFINITIONS[$pageKey]) ? $pageKey : 'contact';
    }

    private function normalizeType(string $type): string
    {
        $type = strtolower(trim($type));

        return isset(self::TYPE_DEFINITIONS[$type]) ? $type : 'contact_info';
    }

    private function normalizeWidth(string $width): string
    {
        $width = strtolower(trim($width));

        return isset(self::WIDTH_DEFINITIONS[$width]) ? $width : 'half';
    }

    private function normalizeHeight(string $height): string
    {
        $height = strtolower(trim($height));

        return isset(self::HEIGHT_DEFINITIONS[$height]) ? $height : 'regular';
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return isset(self::STATUS_DEFINITIONS[$status]) ? $status : 'published';
    }

    private function normalizeGridStyle(string $gridStyle): string
    {
        $gridStyle = strtolower(trim($gridStyle));

        return isset(self::GRID_STYLE_DEFINITIONS[$gridStyle]) ? $gridStyle : 'standard';
    }

    private function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
    }

    private function normalizeIntRange($value, int $min, int $max, int $fallback): int
    {
        if (!is_numeric($value)) {
            return $fallback;
        }

        $value = (int) $value;

        if ($value < $min) {
            return $min;
        }

        if ($value > $max) {
            return $max;
        }

        return $value;
    }

    private function findBlockIndex(array $blocks, string $blockId): ?int
    {
        foreach ($blocks as $index => $block) {
            if ((string) ($block['id'] ?? '') === (string) $blockId) {
                return $index;
            }
        }

        return null;
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
