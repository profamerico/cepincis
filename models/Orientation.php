<?php
require_once __DIR__ . '/../bootstrap.php';

class OrientationManager
{
    private $orientationsFile;

    public function __construct($orientationsFile = null)
    {
        $this->orientationsFile = $orientationsFile ?: __DIR__ . '/../data/orientations.json';
        $this->ensureFileExists();
    }

    public function listOrientations(): array
    {
        return $this->loadOrientations();
    }

    public function getOrientation(string $orientationId)
    {
        foreach ($this->loadOrientations() as $orientation) {
            if ((string) ($orientation['id'] ?? '') === $orientationId) {
                return $orientation;
            }
        }

        return false;
    }

    public function getOrientationsForResearcher(int $researcherId): array
    {
        return array_values(array_filter($this->loadOrientations(), static function (array $orientation) use ($researcherId): bool {
            return (int) ($orientation['researcher_id'] ?? 0) === $researcherId;
        }));
    }

    public function getOrientationsForSupervisor(int $supervisorId): array
    {
        return array_values(array_filter($this->loadOrientations(), static function (array $orientation) use ($supervisorId): bool {
            return (int) ($orientation['supervisor_id'] ?? 0) === $supervisorId;
        }));
    }

    public function saveOrientation(?string $orientationId, array $data): array
    {
        $orientations = $this->loadOrientations();
        $isCreate = $orientationId === null || trim($orientationId) === '';
        $orientationIndex = $isCreate ? null : $this->findOrientationIndex($orientations, (string) $orientationId);

        if (!$isCreate && $orientationIndex === null) {
            return ['success' => false, 'errors' => ['Orientacao nao encontrada.']];
        }

        $title = trim((string) ($data['title'] ?? ''));
        $projectId = trim((string) ($data['project_id'] ?? ''));
        $supervisorId = (int) ($data['supervisor_id'] ?? 0);
        $researcherId = (int) ($data['researcher_id'] ?? 0);
        $workload = trim((string) ($data['workload'] ?? ''));
        $notes = trim((string) ($data['notes'] ?? ''));
        $status = $this->normalizeStatus((string) ($data['status'] ?? 'planned'));

        $errors = [];
        if ($title === '') {
            $errors[] = 'Titulo da orientacao e obrigatorio.';
        }
        if ($supervisorId <= 0) {
            $errors[] = 'Supervisor invalido.';
        }
        if ($researcherId <= 0) {
            $errors[] = 'Pesquisador academico invalido.';
        }

        if ($errors) {
            return ['success' => false, 'errors' => $errors];
        }

        if ($isCreate) {
            $savedOrientation = [
                'id' => uniqid('ori_'),
                'title' => $title,
                'project_id' => $projectId,
                'supervisor_id' => $supervisorId,
                'researcher_id' => $researcherId,
                'workload' => $workload,
                'notes' => $notes,
                'status' => $status,
                'created_at' => $this->now(),
                'updated_at' => $this->now(),
            ];
            $orientations[] = $savedOrientation;
        } else {
            $orientations[$orientationIndex]['title'] = $title;
            $orientations[$orientationIndex]['project_id'] = $projectId;
            $orientations[$orientationIndex]['supervisor_id'] = $supervisorId;
            $orientations[$orientationIndex]['researcher_id'] = $researcherId;
            $orientations[$orientationIndex]['workload'] = $workload;
            $orientations[$orientationIndex]['notes'] = $notes;
            $orientations[$orientationIndex]['status'] = $status;
            $orientations[$orientationIndex]['updated_at'] = $this->now();
            $savedOrientation = $orientations[$orientationIndex];
        }

        if (!$this->saveOrientations($orientations)) {
            return ['success' => false, 'errors' => ['Nao foi possivel salvar a orientacao.']];
        }

        return ['success' => true, 'orientation' => $savedOrientation, 'created' => $isCreate];
    }

    public function deleteOrientation(string $orientationId): bool
    {
        $orientations = $this->loadOrientations();
        $filteredOrientations = [];
        $wasRemoved = false;

        foreach ($orientations as $orientation) {
            if ((string) ($orientation['id'] ?? '') === $orientationId) {
                $wasRemoved = true;
                continue;
            }

            $filteredOrientations[] = $orientation;
        }

        if (!$wasRemoved) {
            return false;
        }

        return $this->saveOrientations($filteredOrientations);
    }

    public function deleteOrientationsForUser(int $userId): int
    {
        $orientations = $this->loadOrientations();
        $filteredOrientations = [];
        $removedCount = 0;

        foreach ($orientations as $orientation) {
            $isSupervisor = (int) ($orientation['supervisor_id'] ?? 0) === $userId;
            $isResearcher = (int) ($orientation['researcher_id'] ?? 0) === $userId;

            if ($isSupervisor || $isResearcher) {
                $removedCount++;
                continue;
            }

            $filteredOrientations[] = $orientation;
        }

        if ($removedCount > 0) {
            $this->saveOrientations($filteredOrientations);
        }

        return $removedCount;
    }

    public function clearProjectReferences(string $projectId): int
    {
        $projectId = trim($projectId);
        if ($projectId === '') {
            return 0;
        }

        $orientations = $this->loadOrientations();
        $updatedCount = 0;

        foreach ($orientations as $index => $orientation) {
            if ((string) ($orientation['project_id'] ?? '') !== $projectId) {
                continue;
            }

            $orientations[$index]['project_id'] = '';
            $orientations[$index]['updated_at'] = $this->now();
            $updatedCount++;
        }

        if ($updatedCount > 0) {
            $this->saveOrientations($orientations);
        }

        return $updatedCount;
    }

    public function getStatsForResearcher(int $researcherId): array
    {
        return $this->buildStats($this->getOrientationsForResearcher($researcherId));
    }

    public function getStatsForSupervisor(int $supervisorId): array
    {
        return $this->buildStats($this->getOrientationsForSupervisor($supervisorId));
    }

    private function buildStats(array $orientations): array
    {
        $stats = [
            'total' => count($orientations),
            'planned' => 0,
            'active' => 0,
            'completed' => 0,
        ];

        foreach ($orientations as $orientation) {
            $status = (string) ($orientation['status'] ?? 'planned');
            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        return $stats;
    }

    private function ensureFileExists(): void
    {
        $directory = dirname($this->orientationsFile);

        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        if (!file_exists($this->orientationsFile)) {
            file_put_contents($this->orientationsFile, json_encode([]), LOCK_EX);
        }
    }

    private function loadOrientations(): array
    {
        if (!file_exists($this->orientationsFile)) {
            return [];
        }

        $contents = @file_get_contents($this->orientationsFile);
        $decoded = json_decode((string) $contents, true);

        if (!is_array($decoded)) {
            return [];
        }

        $orientations = [];
        foreach ($decoded as $orientation) {
            if (is_array($orientation)) {
                $orientations[] = $this->normalizeOrientation($orientation);
            }
        }

        usort($orientations, static function (array $left, array $right): int {
            return strtotime((string) ($right['updated_at'] ?? '')) <=> strtotime((string) ($left['updated_at'] ?? ''));
        });

        return $orientations;
    }

    private function saveOrientations(array $orientations): bool
    {
        $normalizedOrientations = [];

        foreach ($orientations as $orientation) {
            if (is_array($orientation)) {
                $normalizedOrientations[] = $this->normalizeOrientation($orientation);
            }
        }

        return (bool) file_put_contents(
            $this->orientationsFile,
            json_encode($normalizedOrientations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function normalizeOrientation(array $orientation): array
    {
        $createdAt = (string) ($orientation['created_at'] ?? $this->now());
        $updatedAt = (string) ($orientation['updated_at'] ?? $createdAt);

        return [
            'id' => (string) ($orientation['id'] ?? uniqid('ori_')),
            'title' => trim((string) ($orientation['title'] ?? '')),
            'project_id' => trim((string) ($orientation['project_id'] ?? '')),
            'supervisor_id' => (int) ($orientation['supervisor_id'] ?? 0),
            'researcher_id' => (int) ($orientation['researcher_id'] ?? 0),
            'workload' => trim((string) ($orientation['workload'] ?? '')),
            'notes' => trim((string) ($orientation['notes'] ?? '')),
            'status' => $this->normalizeStatus((string) ($orientation['status'] ?? 'planned')),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $allowed = ['planned', 'active', 'completed'];
        $status = strtolower(trim($status));

        return in_array($status, $allowed, true) ? $status : 'planned';
    }

    private function findOrientationIndex(array $orientations, string $orientationId): ?int
    {
        foreach ($orientations as $index => $orientation) {
            if ((string) ($orientation['id'] ?? '') === $orientationId) {
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
