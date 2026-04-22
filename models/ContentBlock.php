<?php

class ContentBlockManager
{
    private $blocksFile;

    private const PAGE_DEFINITIONS = [
        'contact' => [
            'label' => 'Contato',
            'path' => 'contact.php',
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
    ];

    private const WIDTH_DEFINITIONS = [
        'half' => 'Coluna simples',
        'full' => 'Largura total',
    ];

    private const STATUS_DEFINITIONS = [
        'published' => 'Publicado',
        'hidden' => 'Oculto',
    ];

    public function __construct($blocksFile = null)
    {
        $this->blocksFile = $blocksFile ?: __DIR__ . '/../data/content_blocks.json';
        $this->ensureFileExists();
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

    public function getStatusDefinitions(): array
    {
        return self::STATUS_DEFINITIONS;
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

    public function getStatusLabel(string $status): string
    {
        $status = $this->normalizeStatus($status);

        return self::STATUS_DEFINITIONS[$status] ?? self::STATUS_DEFINITIONS['published'];
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

    public function adminSaveBlock(?string $blockId, array $data): array
    {
        $blocks = $this->loadBlocks();
        $isCreate = $blockId === null || trim($blockId) === '';
        $blockIndex = $isCreate ? null : $this->findBlockIndex($blocks, $blockId);

        if (!$isCreate && $blockIndex === null) {
            return ['success' => false, 'errors' => ['Bloco nao encontrado.']];
        }

        $pageKey = $this->normalizePageKey((string) ($data['page_key'] ?? 'contact'));
        $type = $this->normalizeType((string) ($data['type'] ?? 'contact_info'));
        $width = $this->normalizeWidth((string) ($data['width'] ?? 'half'));
        $status = $this->normalizeStatus((string) ($data['status'] ?? 'published'));
        $name = trim((string) ($data['name'] ?? ''));
        $eyebrow = trim((string) ($data['eyebrow'] ?? ''));
        $title = trim((string) ($data['title'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));
        $ctaLabel = trim((string) ($data['cta_label'] ?? ''));
        $ctaUrl = trim((string) ($data['cta_url'] ?? ''));
        $embedUrl = trim((string) ($data['embed_url'] ?? ''));
        $items = $this->normalizeItems($data['items_text'] ?? ($data['items'] ?? []));
        $showContextNote = $this->normalizeBool($data['show_context_note'] ?? false);
        $positionInput = trim((string) ($data['position'] ?? ''));
        $position = $positionInput !== '' ? (int) $positionInput : $this->getNextPosition($pageKey);

        $errors = [];

        if ($name === '') {
            $errors[] = 'Nome interno do bloco e obrigatorio.';
        }

        if ($title === '') {
            $errors[] = 'Titulo do bloco e obrigatorio.';
        }

        if ($type === 'map_embed' && $embedUrl === '') {
            $errors[] = 'URL de embed e obrigatoria para blocos de mapa.';
        }

        if ($ctaLabel !== '' && $ctaUrl === '') {
            $errors[] = 'Informe a URL do botao quando houver um rotulo de CTA.';
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
                'width' => $width,
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
            $blocks[$blockIndex]['width'] = $width;
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
                'width' => 'half',
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
                'width' => 'half',
                'position' => 20,
                'status' => 'published',
                'show_context_note' => false,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];
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

    private function normalizeBlock(array $block): array
    {
        $createdAt = (string) ($block['created_at'] ?? $this->now());
        $updatedAt = (string) ($block['updated_at'] ?? $createdAt);

        return [
            'id' => (string) ($block['id'] ?? uniqid('blk_')),
            'page_key' => $this->normalizePageKey((string) ($block['page_key'] ?? 'contact')),
            'type' => $this->normalizeType((string) ($block['type'] ?? 'contact_info')),
            'name' => trim((string) ($block['name'] ?? '')),
            'eyebrow' => trim((string) ($block['eyebrow'] ?? '')),
            'title' => trim((string) ($block['title'] ?? '')),
            'body' => trim((string) ($block['body'] ?? '')),
            'items' => $this->normalizeItems($block['items'] ?? []),
            'cta_label' => trim((string) ($block['cta_label'] ?? '')),
            'cta_url' => trim((string) ($block['cta_url'] ?? '')),
            'embed_url' => trim((string) ($block['embed_url'] ?? '')),
            'width' => $this->normalizeWidth((string) ($block['width'] ?? 'half')),
            'position' => (int) ($block['position'] ?? 10),
            'status' => $this->normalizeStatus((string) ($block['status'] ?? 'published')),
            'show_context_note' => $this->normalizeBool($block['show_context_note'] ?? false),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
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

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return isset(self::STATUS_DEFINITIONS[$status]) ? $status : 'published';
    }

    private function normalizeBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $value = strtolower(trim((string) $value));

        return in_array($value, ['1', 'true', 'on', 'yes'], true);
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
