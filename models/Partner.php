<?php

class PartnerManager
{
    private $partnersFile;
    private $uploadsDirectory;

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct($partnersFile = null, $uploadsDirectory = null)
    {
        $this->partnersFile = $partnersFile ?: __DIR__ . '/../data/partners.json';
        $this->uploadsDirectory = $uploadsDirectory ?: __DIR__ . '/../uploads/partners';

        $this->ensureFileExists();
    }

    public function listPartners(): array
    {
        return $this->loadPartners();
    }

    public function getPartner(string $partnerId)
    {
        foreach ($this->loadPartners() as $partner) {
            if ((string) ($partner['id'] ?? '') === $partnerId) {
                return $partner;
            }
        }

        return false;
    }

    public function countPartners(): int
    {
        return count($this->loadPartners());
    }

    public function adminSavePartner(?string $partnerId, array $data, ?array $uploadedImage = null): array
    {
        $partners = $this->loadPartners();
        $isCreate = $partnerId === null || trim($partnerId) === '';
        $partnerIndex = $isCreate ? null : $this->findPartnerIndex($partners, (string) $partnerId);

        if (!$isCreate && $partnerIndex === null) {
            return ['success' => false, 'errors' => ['Parceiro nao encontrado.']];
        }

        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $imagePath = trim((string) ($data['image_path'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Nome do parceiro e obrigatorio.';
        }

        if ($description === '') {
            $errors[] = 'Descricao do parceiro e obrigatoria.';
        }

        $hasUpload = $this->hasUploadedFile($uploadedImage);
        if ($isCreate && !$hasUpload && $imagePath === '') {
            $errors[] = 'Envie uma imagem ou informe um caminho valido para o card do parceiro.';
        }

        if ($imagePath !== '' && !$hasUpload) {
            $imagePathValidation = $this->validateImagePath($imagePath);
            if (!$imagePathValidation['success']) {
                $errors[] = $imagePathValidation['error'];
            }
        }

        if ($hasUpload) {
            $uploadValidation = $this->validateImageUpload($uploadedImage);
            if (!$uploadValidation['success']) {
                $errors[] = $uploadValidation['error'];
            }
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        $existingPartner = !$isCreate ? $partners[$partnerIndex] : null;
        $previousImagePath = (string) ($existingPartner['image_path'] ?? '');
        $nextImagePath = $imagePath !== '' ? $imagePath : $previousImagePath;

        if ($hasUpload) {
            $uploadResult = $this->storeImageUpload($uploadedImage);
            if (!$uploadResult['success']) {
                return ['success' => false, 'errors' => [$uploadResult['error']]];
            }

            $nextImagePath = (string) $uploadResult['path'];
        }

        if ($nextImagePath === '') {
            return ['success' => false, 'errors' => ['A imagem do parceiro nao pode ficar vazia.']];
        }

        if ($isCreate) {
            $savedPartner = [
                'id' => uniqid('partner_'),
                'name' => $name,
                'description' => $description,
                'image_path' => $nextImagePath,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
            $partners[] = $savedPartner;
        } else {
            $partners[$partnerIndex]['name'] = $name;
            $partners[$partnerIndex]['description'] = $description;
            $partners[$partnerIndex]['image_path'] = $nextImagePath;
            $partners[$partnerIndex]['updated_at'] = $this->now();
            $savedPartner = $partners[$partnerIndex];
        }

        if (!$this->savePartners($partners)) {
            if ($hasUpload && $nextImagePath !== '' && $nextImagePath !== $previousImagePath) {
                $this->deleteManagedImage($nextImagePath);
            }

            return ['success' => false, 'errors' => ['Nao foi possivel salvar o parceiro.']];
        }

        if ($hasUpload && $previousImagePath !== '' && $previousImagePath !== $nextImagePath) {
            $this->deleteManagedImage($previousImagePath);
        }

        return ['success' => true, 'partner' => $savedPartner, 'created' => $isCreate];
    }

    public function deletePartner(string $partnerId): bool
    {
        $partners = $this->loadPartners();
        $filteredPartners = [];
        $removedPartner = null;

        foreach ($partners as $partner) {
            if ((string) ($partner['id'] ?? '') === $partnerId) {
                $removedPartner = $partner;
                continue;
            }

            $filteredPartners[] = $partner;
        }

        if ($removedPartner === null) {
            return false;
        }

        if (!$this->savePartners($filteredPartners)) {
            return false;
        }

        $this->deleteManagedImage((string) ($removedPartner['image_path'] ?? ''));

        return true;
    }

    private function ensureFileExists(): void
    {
        $directory = dirname($this->partnersFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0775, true);
        }

        if (!file_exists($this->partnersFile)) {
            file_put_contents(
                $this->partnersFile,
                json_encode($this->defaultPartners(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                LOCK_EX
            );
        }
    }

    private function defaultPartners(): array
    {
        $seedCreatedAt = '2026-05-06 00:00:00';

        return [
            [
                'id' => 'partner_copenhagen',
                'name' => 'Universidade de Copenhagen',
                'description' => 'Copenhagen, Dinamarca',
                'image_path' => './img/copenhagen.png',
                'created_at' => $seedCreatedAt,
                'updated_at' => $seedCreatedAt,
            ],
            [
                'id' => 'partner_roma3',
                'name' => 'Universidade de Roma 3',
                'description' => 'Roma, Italia',
                'image_path' => './img/Roma 3.png',
                'created_at' => $seedCreatedAt,
                'updated_at' => $seedCreatedAt,
            ],
            [
                'id' => 'partner_fuzhou',
                'name' => 'Universidade de Fuzhou',
                'description' => 'Instituicao publica de ensino superior localizada em Fuzhou, capital da provincia de Fujian, na China.',
                'image_path' => './img/Fuhzou.png',
                'created_at' => $seedCreatedAt,
                'updated_at' => $seedCreatedAt,
            ],
            [
                'id' => 'partner_getis',
                'name' => 'GETIS',
                'description' => 'Grupo de Pesquisa em Engenharia, Tecnologia, Inovacao e Sustentabilidade (GETIS) - IFSP-CAR',
                'image_path' => './img/Getis.png',
                'created_at' => $seedCreatedAt,
                'updated_at' => $seedCreatedAt,
            ],
            [
                'id' => 'partner_i2',
                'name' => 'i2',
                'description' => 'Grupo de Pesquisas em Tecnologias Inovadoras - IFSP CAR',
                'image_path' => './img/i2v2.png',
                'created_at' => $seedCreatedAt,
                'updated_at' => $seedCreatedAt,
            ],
            [
                'id' => 'partner_enasa',
                'name' => 'ENASA',
                'description' => 'Grupo de pesquisa em Energia, Agua e Saneamento (ENASA) - IFSP-SP',
                'image_path' => './img/enasa.png',
                'created_at' => $seedCreatedAt,
                'updated_at' => $seedCreatedAt,
            ],
        ];
    }

    private function loadPartners(): array
    {
        if (!file_exists($this->partnersFile)) {
            return $this->defaultPartners();
        }

        $contents = @file_get_contents($this->partnersFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded)) {
            return $this->defaultPartners();
        }

        $partners = [];
        foreach ($decoded as $partner) {
            if (is_array($partner)) {
                $partners[] = $this->normalizePartner($partner);
            }
        }

        usort($partners, static function (array $left, array $right): int {
            return strtotime((string) ($left['created_at'] ?? '')) <=> strtotime((string) ($right['created_at'] ?? ''));
        });

        return $partners;
    }

    private function savePartners(array $partners): bool
    {
        $normalizedPartners = [];

        foreach ($partners as $partner) {
            if (is_array($partner)) {
                $normalizedPartners[] = $this->normalizePartner($partner);
            }
        }

        return (bool) file_put_contents(
            $this->partnersFile,
            json_encode($normalizedPartners, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizePartner(array $partner): array
    {
        $createdAt = (string) ($partner['created_at'] ?? $this->now());
        $updatedAt = (string) ($partner['updated_at'] ?? $createdAt);

        return [
            'id' => (string) ($partner['id'] ?? uniqid('partner_')),
            'name' => trim((string) ($partner['name'] ?? '')),
            'description' => trim((string) ($partner['description'] ?? '')),
            'image_path' => trim((string) ($partner['image_path'] ?? '')),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function findPartnerIndex(array $partners, string $partnerId): ?int
    {
        foreach ($partners as $index => $partner) {
            if ((string) ($partner['id'] ?? '') === $partnerId) {
                return $index;
            }
        }

        return null;
    }

    private function hasUploadedFile(?array $uploadedImage): bool
    {
        if (!is_array($uploadedImage)) {
            return false;
        }

        return isset($uploadedImage['error']) && (int) $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE;
    }

    private function validateImageUpload(?array $uploadedImage): array
    {
        if (!$this->hasUploadedFile($uploadedImage)) {
            return ['success' => true];
        }

        if ((int) ($uploadedImage['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Nao foi possivel processar a imagem enviada.'];
        }

        $tmpName = (string) ($uploadedImage['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'error' => 'O upload da imagem nao foi reconhecido pelo servidor.'];
        }

        $imageFormat = $this->resolveUploadedImageFormat($uploadedImage, $tmpName);
        if (!$imageFormat['success']) {
            return ['success' => false, 'error' => 'Formato de imagem nao suportado. Envie JPG, PNG, WEBP ou GIF.'];
        }

        if ((int) ($uploadedImage['size'] ?? 0) > 5 * 1024 * 1024) {
            return ['success' => false, 'error' => 'A imagem do parceiro deve ter no maximo 5 MB.'];
        }

        return [
            'success' => true,
            'mime_type' => (string) ($imageFormat['mime_type'] ?? ''),
            'extension' => (string) ($imageFormat['extension'] ?? 'png'),
        ];
    }

    private function storeImageUpload(?array $uploadedImage): array
    {
        $validation = $this->validateImageUpload($uploadedImage);
        if (!$validation['success']) {
            return $validation;
        }

        $tmpName = (string) ($uploadedImage['tmp_name'] ?? '');
        $extension = (string) ($validation['extension'] ?? 'png');
        $filename = uniqid('partner_', true) . '.' . $extension;
        $destination = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0775, true);
        }

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['success' => false, 'error' => 'Nao foi possivel salvar a imagem do parceiro no servidor.'];
        }

        return ['success' => true, 'path' => './uploads/partners/' . $filename];
    }

    private function detectMimeType(string $tmpName): string
    {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $mimeType = (string) finfo_file($finfo, $tmpName);
                finfo_close($finfo);

                return $mimeType;
            }
        }

        if (function_exists('mime_content_type')) {
            $mimeType = (string) mime_content_type($tmpName);
            if ($mimeType !== '') {
                return $mimeType;
            }
        }

        if (function_exists('getimagesize')) {
            $imageInfo = @getimagesize($tmpName);
            if (is_array($imageInfo) && !empty($imageInfo['mime'])) {
                return (string) $imageInfo['mime'];
            }
        }

        return '';
    }

    private function resolveUploadedImageFormat(?array $uploadedImage, string $tmpName): array
    {
        $mimeCandidates = [
            $this->detectMimeType($tmpName),
            (string) ($uploadedImage['type'] ?? ''),
        ];

        foreach ($mimeCandidates as $mimeCandidate) {
            $normalizedMimeType = $this->normalizeImageMimeType($mimeCandidate);
            if ($normalizedMimeType !== '') {
                return [
                    'success' => true,
                    'mime_type' => $normalizedMimeType,
                    'extension' => self::ALLOWED_IMAGE_TYPES[$normalizedMimeType],
                ];
            }
        }

        $extension = strtolower((string) pathinfo((string) ($uploadedImage['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== '') {
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $mimeType = array_search($extension, self::ALLOWED_IMAGE_TYPES, true);
            if (is_string($mimeType)) {
                return [
                    'success' => true,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                ];
            }
        }

        return ['success' => false];
    }

    private function normalizeImageMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));
        if ($mimeType === '') {
            return '';
        }

        return isset(self::ALLOWED_IMAGE_TYPES[$mimeType]) ? $mimeType : '';
    }

    private function validateImagePath(string $imagePath): array
    {
        $imagePath = trim($imagePath);
        if ($imagePath === '') {
            return ['success' => false, 'error' => 'A imagem do parceiro nao pode ficar vazia.'];
        }

        $url = filter_var($imagePath, FILTER_VALIDATE_URL);
        if ($url !== false) {
            $scheme = strtolower((string) parse_url($imagePath, PHP_URL_SCHEME));
            if (in_array($scheme, ['http', 'https'], true)) {
                return ['success' => true];
            }
        }

        $candidatePath = $imagePath;
        if (strpos($candidatePath, './') === 0) {
            $candidatePath = __DIR__ . '/../' . substr($candidatePath, 2);
        } elseif (!preg_match('/^[a-zA-Z]:[\\\\\\/]/', $candidatePath) && strpos($candidatePath, '/') !== 0) {
            $candidatePath = __DIR__ . '/../' . ltrim($candidatePath, '\\/');
        }

        if (!file_exists($candidatePath)) {
            return ['success' => false, 'error' => 'O caminho informado para a imagem do parceiro nao foi encontrado no projeto.'];
        }

        return ['success' => true];
    }

    private function deleteManagedImage(string $relativePath): void
    {
        $relativePath = trim($relativePath);
        if ($relativePath === '' || strpos($relativePath, './uploads/partners/') !== 0) {
            return;
        }

        $filename = basename($relativePath);
        if ($filename === '') {
            return;
        }

        $absolutePath = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $filename;
        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
