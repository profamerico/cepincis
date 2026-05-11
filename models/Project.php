<?php
class ProjectManager
{
    private $projectsFile;
    private $uploadsDirectory;

    private const THEMATIC_AREA_OPTIONS = [
        'EduCIS' => 'EduCIS',
        'EcoMat' => 'EcoMat',
        'IoT' => 'IoT',
        'CarbonZero' => 'CarbonZero',
        'UrbanSmart' => 'UrbanSmart',
    ];

    private const ALLOWED_IMAGE_TYPES = [
        'image/jpeg' => 'jpg',
        'image/jpg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/x-png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    public function __construct($projectsFile = null, $uploadsDirectory = null)
    {
        $this->projectsFile = $projectsFile ?: __DIR__ . '/../data/projects.json';
        $this->uploadsDirectory = $uploadsDirectory ?: __DIR__ . '/../uploads/projects';
        $this->ensureFileExists();
    }

    public function createProject($userId, $title, $description, $category = null, $tags = [])
    {
        $result = $this->adminSaveProject(null, [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'category' => $category ?? $this->getDefaultThematicArea(),
            'tags' => $tags,
            'status' => 'active',
        ]);

        return $result['success'] ? $result['project'] : false;
    }

    public function getThematicAreaOptions(): array
    {
        return self::THEMATIC_AREA_OPTIONS;
    }

    public function getDefaultThematicArea(): string
    {
        return array_key_first(self::THEMATIC_AREA_OPTIONS) ?: 'EduCIS';
    }

    public function getAllProjects(): array
    {
        return $this->loadProjects();
    }

    public function getUserProjects($userId): array
    {
        $projects = [];

        foreach ($this->loadProjects() as $project) {
            if ((int) ($project['user_id'] ?? 0) === (int) $userId) {
                $projects[] = $project;
            }
        }

        return $projects;
    }

    public function getProject($projectId)
    {
        foreach ($this->loadProjects() as $project) {
            if ((string) ($project['id'] ?? '') === (string) $projectId) {
                return $project;
            }
        }

        return false;
    }

    public function getProjectTagList(array $project, bool $includeCategory = true): array
    {
        $tags = $this->normalizeTags($project['tags'] ?? []);

        if ($includeCategory) {
            $category = trim((string) ($project['category'] ?? ''));
            if ($category !== '') {
                $tags = $this->normalizeTags(array_merge($tags, [$category]));
            }
        }

        return $tags;
    }

    public function getProjectTags(?array $projects = null): array
    {
        $projects = $projects ?? $this->loadProjects();
        $allTags = [];

        foreach ($projects as $project) {
            $allTags = array_merge($allTags, $this->getProjectTagList($project));
        }

        return $this->normalizeTags($allTags);
    }

    public function updateProject($projectId, $data)
    {
        $result = $this->adminSaveProject((string) $projectId, $data);

        return $result['success'] ? $result['project'] : false;
    }

    public function deleteProject($projectId)
    {
        $projects = $this->loadProjects();
        $filteredProjects = [];
        $removedProject = null;

        foreach ($projects as $project) {
            if ((string) ($project['id'] ?? '') === (string) $projectId) {
                $removedProject = $project;
                continue;
            }

            $filteredProjects[] = $project;
        }

        if ($removedProject === null) {
            return false;
        }

        if (!$this->saveProjects($filteredProjects)) {
            return false;
        }

        $this->deleteManagedImage((string) ($removedProject['image_path'] ?? ''));

        return true;
    }

    public function adminSaveProject(?string $projectId, array $data, ?array $uploadedImage = null): array
    {
        $projects = $this->loadProjects();
        $isCreate = $projectId === null || trim($projectId) === '';
        $projectIndex = $isCreate ? null : $this->findProjectIndex($projects, (string) $projectId);

        if (!$isCreate && $projectIndex === null) {
            return ['success' => false, 'errors' => ['Projeto nao encontrado.']];
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $participationInfo = trim((string) ($data['participation_info'] ?? ''));
        $categoryInput = trim((string) ($data['category'] ?? ''));
        $category = $this->normalizeCategory($categoryInput);
        $tagsInput = $data['tags'] ?? [];
        $tags = $this->normalizeTags($tagsInput);
        $status = $this->normalizeStatus((string) ($data['status'] ?? 'active'));
        $userId = $this->normalizeUserId($data['user_id'] ?? null);
        $imagePath = trim((string) ($data['image_path'] ?? ''));
        $errors = [];

        if ($title === '') {
            $errors[] = 'Titulo e obrigatorio.';
        }
        if ($description === '') {
            $errors[] = 'Descricao do projeto e obrigatoria.';
        }
        if ($categoryInput === '') {
            $errors[] = 'Area tematica e obrigatoria.';
        } elseif (!$this->isValidThematicArea($categoryInput)) {
            $errors[] = 'Area tematica invalida. Escolha uma das 5 siglas oficiais.';
        }

        $invalidTags = $this->findInvalidTags($tagsInput);
        if (!empty($invalidTags)) {
            $errors[] = 'As tags devem usar apenas as 5 siglas oficiais das Areas Tematicas.';
        }

        $hasUpload = $this->hasUploadedFile($uploadedImage);

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

        $existingProject = !$isCreate ? $projects[$projectIndex] : null;
        $previousImagePath = (string) ($existingProject['image_path'] ?? '');
        $nextImagePath = $imagePath !== '' ? $imagePath : $previousImagePath;

        if ($hasUpload) {
            $uploadResult = $this->storeImageUpload($uploadedImage);
            if (!$uploadResult['success']) {
                return ['success' => false, 'errors' => [$uploadResult['error']]];
            }

            $nextImagePath = (string) $uploadResult['path'];
        }

        if ($isCreate) {
            $savedProject = [
                'id' => uniqid('prj_'),
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'participation_info' => $participationInfo,
                'category' => $category,
                'tags' => $tags,
                'image_path' => $nextImagePath,
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
            $projects[] = $savedProject;
        } else {
            $projects[$projectIndex]['user_id'] = $userId;
            $projects[$projectIndex]['title'] = $title;
            $projects[$projectIndex]['description'] = $description;
            $projects[$projectIndex]['participation_info'] = $participationInfo;
            $projects[$projectIndex]['category'] = $category;
            $projects[$projectIndex]['tags'] = $tags;
            $projects[$projectIndex]['image_path'] = $nextImagePath;
            $projects[$projectIndex]['status'] = $status;
            $projects[$projectIndex]['updated_at'] = $this->now();
            $savedProject = $projects[$projectIndex];
        }

        if (!$this->saveProjects($projects)) {
            if ($hasUpload && $nextImagePath !== '' && $nextImagePath !== $previousImagePath) {
                $this->deleteManagedImage($nextImagePath);
            }

            return ['success' => false, 'errors' => ['Nao foi possivel salvar o projeto.']];
        }

        if ($hasUpload && $previousImagePath !== '' && $previousImagePath !== $nextImagePath) {
            $this->deleteManagedImage($previousImagePath);
        }

        return ['success' => true, 'project' => $savedProject, 'created' => $isCreate];
    }

    public function clearProjectsForUser(int $userId): int
    {
        $projects = $this->loadProjects();
        $updatedProjects = [];
        $affectedCount = 0;

        foreach ($projects as $project) {
            if ((int) ($project['user_id'] ?? 0) === $userId) {
                $project['user_id'] = null;
                $project['updated_at'] = $this->now();
                $affectedCount++;
            }

            $updatedProjects[] = $project;
        }

        if ($affectedCount > 0) {
            $this->saveProjects($updatedProjects);
        }

        return $affectedCount;
    }

    public function getUserStats($userId): array
    {
        $projects = $this->getUserProjects($userId);
        $stats = [
            'total' => count($projects),
            'active' => 0,
            'completed' => 0,
            'pending' => 0,
        ];

        foreach ($projects as $project) {
            $status = $project['status'] ?? 'active';

            if ($status === 'active') {
                $stats['active']++;
            }
            if ($status === 'completed') {
                $stats['completed']++;
            }
            if ($status === 'pending') {
                $stats['pending']++;
            }
        }

        return $stats;
    }

    public function getProjectStats(): array
    {
        $stats = [
            'total' => 0,
            'active' => 0,
            'completed' => 0,
            'pending' => 0,
            'without_owner' => 0,
        ];

        foreach ($this->loadProjects() as $project) {
            $stats['total']++;

            $status = $project['status'] ?? 'active';
            if (isset($stats[$status])) {
                $stats[$status]++;
            }

            if (($project['user_id'] ?? null) === null) {
                $stats['without_owner']++;
            }
        }

        return $stats;
    }

    private function ensureFileExists(): void
    {
        $directory = dirname($this->projectsFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0775, true);
        }

        if (!file_exists($this->projectsFile)) {
            file_put_contents($this->projectsFile, json_encode([]), LOCK_EX);
        }
    }

    private function loadProjects(): array
    {
        if (!file_exists($this->projectsFile)) {
            return [];
        }

        $contents = @file_get_contents($this->projectsFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded)) {
            return [];
        }

        $projects = [];
        foreach ($decoded as $project) {
            if (is_array($project)) {
                $projects[] = $this->normalizeProject($project);
            }
        }

        usort($projects, static function (array $left, array $right): int {
            return strtotime((string) ($right['updated_at'] ?? '')) <=> strtotime((string) ($left['updated_at'] ?? ''));
        });

        return $projects;
    }

    private function saveProjects(array $projects): bool
    {
        $normalizedProjects = [];

        foreach ($projects as $project) {
            if (is_array($project)) {
                $normalizedProjects[] = $this->normalizeProject($project);
            }
        }

        return (bool) file_put_contents(
            $this->projectsFile,
            json_encode($normalizedProjects, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizeProject(array $project): array
    {
        $createdAt = (string) ($project['created_at'] ?? $this->now());
        $updatedAt = (string) ($project['updated_at'] ?? $createdAt);

        return [
            'id' => (string) ($project['id'] ?? uniqid('prj_')),
            'user_id' => $this->normalizeUserId($project['user_id'] ?? null),
            'title' => trim((string) ($project['title'] ?? '')),
            'description' => trim((string) ($project['description'] ?? '')),
            'participation_info' => trim((string) ($project['participation_info'] ?? '')),
            'category' => $this->normalizeCategory((string) ($project['category'] ?? $this->getDefaultThematicArea())),
            'tags' => $this->normalizeTags($project['tags'] ?? []),
            'image_path' => trim((string) ($project['image_path'] ?? '')),
            'status' => $this->normalizeStatus((string) ($project['status'] ?? 'active')),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function normalizeUserId($userId): ?int
    {
        if ($userId === null || $userId === '') {
            return null;
        }

        return (int) $userId;
    }

    private function normalizeStatus(string $status): string
    {
        $allowed = ['active', 'completed', 'pending'];
        $status = strtolower(trim($status));

        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function normalizeCategory(string $category): string
    {
        $canonicalArea = $this->canonicalizeThematicArea($category);

        return $canonicalArea ?? $this->getDefaultThematicArea();
    }

    private function isValidThematicArea(string $value): bool
    {
        return $this->canonicalizeThematicArea($value) !== null;
    }

    private function canonicalizeThematicArea(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (array_keys(self::THEMATIC_AREA_OPTIONS) as $areaKey) {
            if (strcasecmp($areaKey, $value) === 0) {
                return $areaKey;
            }
        }

        return null;
    }

    private function parseTags($tags): array
    {
        if (is_string($tags)) {
            $tags = preg_split('/[,;\r\n]+/', $tags) ?: [];
        }

        return is_array($tags) ? $tags : [];
    }

    private function normalizeTags($tags): array
    {
        $tags = $this->parseTags($tags);
        $normalizedTags = [];
        $seenTags = [];

        foreach ($tags as $tag) {
            $cleanTag = trim((string) $tag);
            if ($cleanTag === '') {
                continue;
            }

            $canonicalTag = $this->canonicalizeThematicArea($cleanTag);
            if ($canonicalTag === null) {
                continue;
            }

            $normalizedKey = function_exists('mb_strtolower')
                ? mb_strtolower($canonicalTag, 'UTF-8')
                : strtolower($canonicalTag);
            if (isset($seenTags[$normalizedKey])) {
                continue;
            }

            $seenTags[$normalizedKey] = true;
            $normalizedTags[] = $canonicalTag;
        }

        return $normalizedTags;
    }

    private function findInvalidTags($tags): array
    {
        $invalidTags = [];

        foreach ($this->parseTags($tags) as $tag) {
            $cleanTag = trim((string) $tag);
            if ($cleanTag === '') {
                continue;
            }

            if ($this->canonicalizeThematicArea($cleanTag) !== null) {
                continue;
            }

            $invalidTags[] = $cleanTag;
        }

        return array_values(array_unique($invalidTags));
    }

    private function findProjectIndex(array $projects, string $projectId): ?int
    {
        foreach ($projects as $index => $project) {
            if ((string) ($project['id'] ?? '') === $projectId) {
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

        return isset($uploadedImage['error']) && (int) ($uploadedImage['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
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

        if ((int) ($uploadedImage['size'] ?? 0) > 6 * 1024 * 1024) {
            return ['success' => false, 'error' => 'A imagem do projeto deve ter no maximo 6 MB.'];
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
        $filename = uniqid('project_', true) . '.' . $extension;
        $destination = $this->uploadsDirectory . DIRECTORY_SEPARATOR . $filename;

        if (!is_dir($this->uploadsDirectory)) {
            mkdir($this->uploadsDirectory, 0775, true);
        }

        if (!move_uploaded_file($tmpName, $destination)) {
            return ['success' => false, 'error' => 'Nao foi possivel salvar a imagem do projeto no servidor.'];
        }

        return ['success' => true, 'path' => './uploads/projects/' . $filename];
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
            return ['success' => false, 'error' => 'A imagem do projeto nao pode ficar vazia.'];
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
            return ['success' => false, 'error' => 'O caminho informado para a imagem do projeto nao foi encontrado no projeto.'];
        }

        return ['success' => true];
    }

    private function deleteManagedImage(string $relativePath): void
    {
        $relativePath = trim($relativePath);
        if ($relativePath === '' || strpos($relativePath, './uploads/projects/') !== 0) {
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
