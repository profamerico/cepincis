<?php

require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/../database.php';

class PartnerManager
{
    private $db;
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

    public function __construct($uploadsDirectory = null)
    {
        $database = new Database();
        $this->db = $database->getConnection();

        $this->uploadsDirectory = $uploadsDirectory ?: __DIR__ . '/../uploads/partners';

        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0775, true);
        }
    }

    public function listPartners(): array
    {
        $stmt = $this->db->query("
            SELECT
                id,
                name,
                description,
                image_path,
                created_at,
                updated_at
            FROM partners
            ORDER BY created_at ASC
        ");

        $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($partners as &$partner) {
            $partner = $this->normalizePartner($partner);
        }

        return $partners;
    }

    public function getPartner(string $partnerId)
    {
        $stmt = $this->db->prepare("
            SELECT
                id,
                name,
                description,
                image_path,
                created_at,
                updated_at
            FROM partners
            WHERE id = :id
            LIMIT 1
        ");

        $stmt->execute([
            ':id' => $partnerId,
        ]);

        $partner = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$partner) {
            return false;
        }

        return $this->normalizePartner($partner);
    }

    public function countPartners(): int
    {
        $stmt = $this->db->query("SELECT COUNT(*) FROM partners");

        return (int) $stmt->fetchColumn();
    }

    public function adminSavePartner(?string $partnerId, array $data, ?array $uploadedImage = null): array
    {
        $isCreate = $partnerId === null || trim($partnerId) === '';
        $partnerId = $partnerId !== null ? trim($partnerId) : null;

        $name = trim((string) ($data['name'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $imagePath = trim((string) ($data['image_path'] ?? ''));
        $errors = [];

        if ($name === '') {
            $errors[] = 'Nome do parceiro é obrigatório.';
        }

        if ($description === '') {
            $errors[] = 'Descrição do parceiro é obrigatória.';
        }

        $hasUpload = $this->hasUploadedFile($uploadedImage);

        if ($isCreate && !$hasUpload && $imagePath === '') {
            $errors[] = 'Envie uma imagem ou informe um caminho válido para o card do parceiro.';
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
            return [
                'success' => false,
                'errors' => $errors,
            ];
        }

        $existingPartner = null;

        if (!$isCreate) {
            $existingPartner = $this->getPartner($partnerId);

            if ($existingPartner === false) {
                return [
                    'success' => false,
                    'errors' => ['Parceiro não encontrado.'],
                ];
            }
        }

        $previousImagePath = (string) ($existingPartner['image_path'] ?? '');
        $nextImagePath = $imagePath !== ''
            ? $imagePath
            : $previousImagePath;

        if ($hasUpload) {
            $uploadResult = $this->storeImageUpload($uploadedImage);

            if (!$uploadResult['success']) {
                return [
                    'success' => false,
                    'errors' => [$uploadResult['error']],
                ];
            }

            $nextImagePath = (string) $uploadResult['path'];
        }

        if ($nextImagePath === '') {
            return [
                'success' => false,
                'errors' => ['A imagem do parceiro não pode ficar vazia.'],
            ];
        }

        try {
            if ($isCreate) {
                $newId = uniqid('partner_');

                $stmt = $this->db->prepare("
                    INSERT INTO partners (
                        id,
                        name,
                        description,
                        image_path
                    ) VALUES (
                        :id,
                        :name,
                        :description,
                        :image_path
                    )
                ");

                $stmt->execute([
                    ':id' => $newId,
                    ':name' => $name,
                    ':description' => $description,
                    ':image_path' => $nextImagePath,
                ]);

                $savedPartner = $this->getPartner($newId);
            } else {
                $stmt = $this->db->prepare("
                    UPDATE partners
                    SET
                        name = :name,
                        description = :description,
                        image_path = :image_path
                    WHERE id = :id
                ");

                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':image_path' => $nextImagePath,
                    ':id' => $partnerId,
                ]);

                $savedPartner = $this->getPartner($partnerId);
            }
        } catch (PDOException $e) {
            if (
                $hasUpload &&
                $nextImagePath !== '' &&
                $nextImagePath !== $previousImagePath
            ) {
                $this->deleteManagedImage($nextImagePath);
            }

            return [
                'success' => false,
                'errors' => ['Não foi possível salvar o parceiro no banco de dados.'],
            ];
        }

        if (
            $hasUpload &&
            $previousImagePath !== '' &&
            $previousImagePath !== $nextImagePath
        ) {
            $this->deleteManagedImage($previousImagePath);
        }

        return [
            'success' => true,
            'partner' => $savedPartner,
            'created' => $isCreate,
        ];
    }

    public function deletePartner(string $partnerId): bool
    {
        $partner = $this->getPartner($partnerId);

        if ($partner === false) {
            return false;
        }

        try {
            $stmt = $this->db->prepare("
                DELETE FROM partners
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $partnerId,
            ]);
        } catch (PDOException $e) {
            return false;
        }

        if ($stmt->rowCount() < 1) {
            return false;
        }

        $this->deleteManagedImage(
            (string) ($partner['image_path'] ?? '')
        );

        return true;
    }

    private function normalizePartner(array $partner): array
    {
        $createdAt = (string) ($partner['created_at'] ?? '');
        $updatedAt = (string) ($partner['updated_at'] ?? $createdAt);

        return [
            'id' => (string) ($partner['id'] ?? ''),
            'name' => trim((string) ($partner['name'] ?? '')),
            'description' => trim((string) ($partner['description'] ?? '')),
            'image_path' => trim((string) ($partner['image_path'] ?? '')),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function hasUploadedFile(?array $uploadedImage): bool
    {
        if (!is_array($uploadedImage)) {
            return false;
        }

        return isset($uploadedImage['error'])
            && (int) $uploadedImage['error'] !== UPLOAD_ERR_NO_FILE;
    }

    private function validateImageUpload(?array $uploadedImage): array
    {
        if (!$this->hasUploadedFile($uploadedImage)) {
            return [
                'success' => true,
            ];
        }

        if ((int) ($uploadedImage['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return [
                'success' => false,
                'error' => 'Não foi possível processar a imagem enviada.',
            ];
        }

        $tmpName = (string) ($uploadedImage['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return [
                'success' => false,
                'error' => 'O upload da imagem não foi reconhecido pelo servidor.',
            ];
        }

        $imageFormat = $this->resolveUploadedImageFormat(
            $uploadedImage,
            $tmpName
        );

        if (!$imageFormat['success']) {
            return [
                'success' => false,
                'error' => 'Formato de imagem não suportado. Envie JPG, PNG, WEBP ou GIF.',
            ];
        }

        if ((int) ($uploadedImage['size'] ?? 0) > 5 * 1024 * 1024) {
            return [
                'success' => false,
                'error' => 'A imagem do parceiro deve ter no máximo 5 MB.',
            ];
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
            return [
                'success' => false,
                'error' => 'Não foi possível salvar a imagem do parceiro no servidor.',
            ];
        }

        return [
            'success' => true,
            'path' => './uploads/partners/' . $filename,
        ];
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

    private function resolveUploadedImageFormat(
        ?array $uploadedImage,
        string $tmpName
    ): array {
        $mimeCandidates = [
            $this->detectMimeType($tmpName),
            (string) ($uploadedImage['type'] ?? ''),
        ];

        foreach ($mimeCandidates as $mimeCandidate) {
            $normalizedMimeType = $this->normalizeImageMimeType(
                $mimeCandidate
            );

            if ($normalizedMimeType !== '') {
                return [
                    'success' => true,
                    'mime_type' => $normalizedMimeType,
                    'extension' => self::ALLOWED_IMAGE_TYPES[$normalizedMimeType],
                ];
            }
        }

        $extension = strtolower(
            (string) pathinfo(
                (string) ($uploadedImage['name'] ?? ''),
                PATHINFO_EXTENSION
            )
        );

        if ($extension !== '') {
            if ($extension === 'jpeg') {
                $extension = 'jpg';
            }

            $mimeType = array_search(
                $extension,
                self::ALLOWED_IMAGE_TYPES,
                true
            );

            if (is_string($mimeType)) {
                return [
                    'success' => true,
                    'mime_type' => $mimeType,
                    'extension' => $extension,
                ];
            }
        }

        return [
            'success' => false,
        ];
    }

    private function normalizeImageMimeType(string $mimeType): string
    {
        $mimeType = strtolower(trim($mimeType));

        if ($mimeType === '') {
            return '';
        }

        return isset(self::ALLOWED_IMAGE_TYPES[$mimeType])
            ? $mimeType
            : '';
    }

    private function validateImagePath(string $imagePath): array
    {
        $imagePath = trim($imagePath);

        if ($imagePath === '') {
            return [
                'success' => false,
                'error' => 'A imagem do parceiro não pode ficar vazia.',
            ];
        }

        $url = filter_var(
            $imagePath,
            FILTER_VALIDATE_URL
        );

        if ($url !== false) {
            $scheme = strtolower(
                (string) parse_url(
                    $imagePath,
                    PHP_URL_SCHEME
                )
            );

            if (in_array($scheme, ['http', 'https'], true)) {
                return [
                    'success' => true,
                ];
            }
        }

        $candidatePath = $imagePath;

        if (strpos($candidatePath, './') === 0) {
            $candidatePath = __DIR__ . '/../' . substr($candidatePath, 2);
        } elseif (
            !preg_match('/^[a-zA-Z]:[\\\\\\/]/', $candidatePath)
            && strpos($candidatePath, '/') !== 0
        ) {
            $candidatePath = __DIR__ . '/../' . ltrim(
                $candidatePath,
                '\\/'
            );
        }

        if (!file_exists($candidatePath)) {
            return [
                'success' => false,
                'error' => 'O caminho informado para a imagem do parceiro não foi encontrado no projeto.',
            ];
        }

        return [
            'success' => true,
        ];
    }

    private function deleteManagedImage(string $relativePath): void
    {
        $relativePath = trim($relativePath);

        if (
            $relativePath === ''
            || strpos($relativePath, './uploads/partners/') !== 0
        ) {
            return;
        }

        $filename = basename($relativePath);

        if ($filename === '') {
            return;
        }

        $absolutePath = $this->uploadsDirectory
            . DIRECTORY_SEPARATOR
            . $filename;

        if (is_file($absolutePath)) {
            @unlink($absolutePath);
        }
    }
}
