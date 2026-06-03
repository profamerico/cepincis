<?php
require_once __DIR__ . '/../bootstrap.php';

class UserProfileExtrasManager
{
    private string $profilesFile;
    private string $roleRequestsFile;
    private string $uploadDirectory;

    private const PHOTO_MAX_BYTES = 3145728;
    private const ALLOWED_PHOTO_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/webp' => 'webp',
    ];

    public function __construct(?string $dataDirectory = null, ?string $uploadDirectory = null)
    {
        $dataDirectory = $dataDirectory ?: __DIR__ . '/../data';
        $this->profilesFile = $dataDirectory . '/user_profiles.json';
        $this->roleRequestsFile = $dataDirectory . '/role_requests.json';
        $this->uploadDirectory = $uploadDirectory ?: __DIR__ . '/../uploads/user-profiles';

        $this->ensureStorage();
    }

    public function getProfile(int $userId): array
    {
        foreach ($this->loadJson($this->profilesFile) as $profile) {
            if ((int) ($profile['user_id'] ?? 0) === $userId) {
                return $this->normalizeProfile($profile);
            }
        }

        return $this->normalizeProfile([
            'user_id' => $userId,
        ]);
    }

    public function getProfilesByUserId(): array
    {
        $profiles = [];

        foreach ($this->loadJson($this->profilesFile) as $profile) {
            $normalized = $this->normalizeProfile($profile);
            $profiles[(int) $normalized['user_id']] = $normalized;
        }

        return $profiles;
    }

    public function saveProfile(int $userId, array $data, ?array $photoFile = null): array
    {
        $currentProfile = $this->getProfile($userId);
        $photoPath = (string) ($currentProfile['photo_path'] ?? '');

        if ($this->hasUploadedFile($photoFile)) {
            $photoResult = $this->storePhotoUpload($photoFile);
            if (!$photoResult['success']) {
                return [
                    'success' => false,
                    'errors' => [$photoResult['error']],
                ];
            }

            $photoPath = (string) ($photoResult['path'] ?? '');
            $this->deleteManagedPhoto((string) ($currentProfile['photo_path'] ?? ''));
        }

        $profile = $this->normalizeProfile([
            'user_id' => $userId,
            'photo_path' => $photoPath,
            'bio' => trim((string) ($data['bio'] ?? '')),
            'linkedin_url' => $this->normalizeUrl((string) ($data['linkedin_url'] ?? '')),
            'integra_ifsp_url' => $this->normalizeUrl((string) ($data['integra_ifsp_url'] ?? '')),
            'lattes_url' => $this->normalizeUrl((string) ($data['lattes_url'] ?? '')),
            'updated_at' => $this->now(),
        ]);

        $profiles = $this->loadJson($this->profilesFile);
        $updated = false;

        foreach ($profiles as &$storedProfile) {
            if ((int) ($storedProfile['user_id'] ?? 0) === $userId) {
                $storedProfile = $profile;
                $updated = true;
                break;
            }
        }
        unset($storedProfile);

        if (!$updated) {
            $profile['created_at'] = $this->now();
            $profiles[] = $profile;
        }

        $this->saveJson($this->profilesFile, $profiles);

        return [
            'success' => true,
            'profile' => $profile,
        ];
    }

    public function createRoleRequest(int $userId, string $requestedRole, string $message = ''): array
    {
        $requestedRole = $this->normalizeRequestedRole($requestedRole);
        if ($requestedRole === '') {
            return [
                'success' => false,
                'errors' => ['Selecione um nivel valido para solicitar.'],
            ];
        }

        foreach ($this->listRoleRequests('pending') as $request) {
            if ((int) ($request['user_id'] ?? 0) === $userId) {
                return [
                    'success' => false,
                    'errors' => ['Voce ja possui uma solicitacao de nivel pendente.'],
                ];
            }
        }

        $request = [
            'id' => uniqid('rr_', true),
            'user_id' => $userId,
            'requested_role' => $requestedRole,
            'message' => trim($message),
            'status' => 'pending',
            'reviewed_by_user_id' => null,
            'review_notes' => '',
            'reviewed_at' => null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $requests = $this->loadJson($this->roleRequestsFile);
        $requests[] = $request;
        $this->saveJson($this->roleRequestsFile, $requests);

        return [
            'success' => true,
            'request' => $request,
        ];
    }

    public function listRoleRequests(?string $status = null): array
    {
        $requests = array_values(array_filter($this->loadJson($this->roleRequestsFile), function (array $request) use ($status): bool {
            if ($status === null) {
                return true;
            }

            return (string) ($request['status'] ?? '') === $status;
        }));

        usort($requests, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''));
        });

        return $requests;
    }

    public function getLatestRoleRequestForUser(int $userId): ?array
    {
        foreach ($this->listRoleRequests() as $request) {
            if ((int) ($request['user_id'] ?? 0) === $userId) {
                return $request;
            }
        }

        return null;
    }

    public function reviewRoleRequest(string $requestId, int $reviewerId, string $decision, string $notes = ''): array
    {
        $decision = strtolower(trim($decision));
        if (!in_array($decision, ['approved', 'rejected'], true)) {
            return [
                'success' => false,
                'errors' => ['Decisao invalida para a solicitacao.'],
            ];
        }

        $requests = $this->loadJson($this->roleRequestsFile);
        $requestIndex = null;

        foreach ($requests as $index => $request) {
            if ((string) ($request['id'] ?? '') === $requestId) {
                $requestIndex = $index;
                break;
            }
        }

        if ($requestIndex === null) {
            return [
                'success' => false,
                'errors' => ['Solicitacao nao encontrada.'],
            ];
        }

        if ((string) ($requests[$requestIndex]['status'] ?? '') !== 'pending') {
            return [
                'success' => false,
                'errors' => ['Esta solicitacao ja foi revisada.'],
            ];
        }

        $requests[$requestIndex]['status'] = $decision;
        $requests[$requestIndex]['reviewed_by_user_id'] = $reviewerId;
        $requests[$requestIndex]['review_notes'] = trim($notes);
        $requests[$requestIndex]['reviewed_at'] = $this->now();
        $requests[$requestIndex]['updated_at'] = $this->now();

        $this->saveJson($this->roleRequestsFile, $requests);

        return [
            'success' => true,
            'request' => $requests[$requestIndex],
        ];
    }

    public function getRoleRequestLabel(string $role): string
    {
        switch ($this->normalizeRequestedRole($role)) {
            case 'academic_researcher':
                return 'Pesquisador Academico';
            case 'full_researcher':
                return 'Pesquisador Pleno';
            default:
                return 'Nivel solicitado';
        }
    }

    private function normalizeProfile(array $profile): array
    {
        return [
            'user_id' => (int) ($profile['user_id'] ?? 0),
            'photo_path' => trim((string) ($profile['photo_path'] ?? '')),
            'bio' => trim((string) ($profile['bio'] ?? '')),
            'linkedin_url' => trim((string) ($profile['linkedin_url'] ?? '')),
            'integra_ifsp_url' => trim((string) ($profile['integra_ifsp_url'] ?? '')),
            'lattes_url' => trim((string) ($profile['lattes_url'] ?? '')),
            'created_at' => (string) ($profile['created_at'] ?? ''),
            'updated_at' => (string) ($profile['updated_at'] ?? ''),
        ];
    }

    private function normalizeRequestedRole(string $role): string
    {
        $role = strtolower(trim($role));

        return in_array($role, ['academic_researcher', 'full_researcher'], true) ? $role : '';
    }

    private function normalizeUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') {
            return '';
        }

        if (!preg_match('/^https?:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        return filter_var($url, FILTER_VALIDATE_URL) ? $url : '';
    }

    private function storePhotoUpload(?array $photoFile): array
    {
        if (!$this->hasUploadedFile($photoFile)) {
            return ['success' => true, 'path' => ''];
        }

        if ((int) ($photoFile['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Nao foi possivel processar a foto enviada.'];
        }

        $tmpName = (string) ($photoFile['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'error' => 'A foto enviada nao foi reconhecida pelo servidor.'];
        }

        if ((int) ($photoFile['size'] ?? 0) > self::PHOTO_MAX_BYTES) {
            return ['success' => false, 'error' => 'A foto de perfil deve ter no maximo 3 MB.'];
        }

        $mimeType = $this->detectMimeType($tmpName);
        if (!isset(self::ALLOWED_PHOTO_TYPES[$mimeType])) {
            return ['success' => false, 'error' => 'Envie uma foto em JPG, PNG ou WEBP.'];
        }

        $imageInfo = @getimagesize($tmpName);
        if (!is_array($imageInfo)) {
            return ['success' => false, 'error' => 'A imagem enviada nao parece ser uma foto valida.'];
        }

        if (!is_dir($this->uploadDirectory)) {
            mkdir($this->uploadDirectory, 0775, true);
        }

        $extension = self::ALLOWED_PHOTO_TYPES[$mimeType];
        $filename = uniqid('profile_', true) . '.' . $extension;
        $destination = $this->uploadDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['success' => false, 'error' => 'Nao foi possivel salvar a foto no servidor.'];
        }

        return [
            'success' => true,
            'path' => './uploads/user-profiles/' . $filename,
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
            return (string) mime_content_type($tmpName);
        }

        return '';
    }

    private function deleteManagedPhoto(string $path): void
    {
        if ($path === '' || strpos($path, './uploads/user-profiles/') !== 0) {
            return;
        }

        $candidate = $this->uploadDirectory . DIRECTORY_SEPARATOR . basename($path);
        if (is_file($candidate)) {
            @unlink($candidate);
        }
    }

    private function hasUploadedFile(?array $file): bool
    {
        return is_array($file)
            && isset($file['error'])
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    }

    private function ensureStorage(): void
    {
        foreach ([dirname($this->profilesFile), $this->uploadDirectory] as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        foreach ([$this->profilesFile, $this->roleRequestsFile] as $file) {
            if (!file_exists($file)) {
                file_put_contents($file, json_encode([]), LOCK_EX);
            }
        }
    }

    private function loadJson(string $file): array
    {
        $decoded = json_decode((string) @file_get_contents($file), true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function saveJson(string $file, array $records): bool
    {
        return (bool) file_put_contents(
            $file,
            json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
