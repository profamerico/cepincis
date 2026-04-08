<?php
class ProjectManager
{
    private $projectsFile;

    public function __construct($projectsFile = null)
    {
        $this->projectsFile = $projectsFile ?: __DIR__ . '/../data/projects.json';
        $this->ensureFileExists();
    }

    public function createProject($userId, $title, $description, $category = 'Geral')
    {
        $result = $this->adminSaveProject(null, [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'category' => $category,
            'status' => 'active',
        ]);

        return $result['success'] ? $result['project'] : false;
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
            if ((string) $project['id'] === (string) $projectId) {
                return $project;
            }
        }

        return false;
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
        $wasRemoved = false;

        foreach ($projects as $project) {
            if ((string) $project['id'] === (string) $projectId) {
                $wasRemoved = true;
                continue;
            }

            $filteredProjects[] = $project;
        }

        if (!$wasRemoved) {
            return false;
        }

        return $this->saveProjects($filteredProjects);
    }

    public function adminSaveProject(?string $projectId, array $data): array
    {
        $projects = $this->loadProjects();
        $isCreate = $projectId === null || trim($projectId) === '';
        $projectIndex = $isCreate ? null : $this->findProjectIndex($projects, $projectId);

        if (!$isCreate && $projectIndex === null) {
            return ['success' => false, 'errors' => ['Projeto nao encontrado.']];
        }

        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $category = trim((string) ($data['category'] ?? 'Geral'));
        $status = $this->normalizeStatus((string) ($data['status'] ?? 'active'));
        $userId = $this->normalizeUserId($data['user_id'] ?? null);

        $errors = [];
        if ($title === '') {
            $errors[] = 'Titulo e obrigatorio.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($isCreate) {
            $savedProject = [
                'id' => uniqid('prj_'),
                'user_id' => $userId,
                'title' => $title,
                'description' => $description,
                'category' => $category !== '' ? $category : 'Geral',
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
            $projects[] = $savedProject;
        } else {
            $projects[$projectIndex]['user_id'] = $userId;
            $projects[$projectIndex]['title'] = $title;
            $projects[$projectIndex]['description'] = $description;
            $projects[$projectIndex]['category'] = $category !== '' ? $category : 'Geral';
            $projects[$projectIndex]['status'] = $status;
            $projects[$projectIndex]['updated_at'] = $this->now();
            $savedProject = $projects[$projectIndex];
        }

        $this->saveProjects($projects);

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
            return strtotime((string) $right['updated_at']) <=> strtotime((string) $left['updated_at']);
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
            'category' => trim((string) ($project['category'] ?? 'Geral')) ?: 'Geral',
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

    private function findProjectIndex(array $projects, string $projectId): ?int
    {
        foreach ($projects as $index => $project) {
            if ((string) ($project['id'] ?? '') === $projectId) {
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
