<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Project.php';

class ProjectWorkspaceManager
{
    private ProjectManager $projectManager;
    private string $dataDirectory;
    private string $documentStorageDirectory;
    private string $timelineStorageDirectory;

    private array $files;

    private const DOCUMENT_MAX_BYTES = 10485760;
    private const TIMELINE_ATTACHMENT_MAX_BYTES = 8388608;

    private const DOCUMENT_EXTENSIONS = ['pdf', 'docx'];
    private const TIMELINE_EXTENSIONS = ['pdf', 'docx', 'jpg', 'jpeg', 'png', 'webp'];

    private const TIMELINE_TYPES = [
        'update' => 'Atualizacao',
        'milestone' => 'Conquista',
        'fix' => 'Correcao',
        'documentation' => 'Documentacao',
        'release' => 'Release',
        'notice' => 'Aviso',
    ];

    public function __construct(
        ?ProjectManager $projectManager = null,
        ?string $dataDirectory = null,
        ?string $storageDirectory = null
    ) {
        $this->projectManager = $projectManager ?: new ProjectManager();
        $this->dataDirectory = $dataDirectory ?: __DIR__ . '/../data';
        $storageDirectory = $storageDirectory ?: __DIR__ . '/../storage';
        $this->documentStorageDirectory = $storageDirectory . DIRECTORY_SEPARATOR . 'project-documents';
        $this->timelineStorageDirectory = $storageDirectory . DIRECTORY_SEPARATOR . 'project-timeline';

        $this->files = [
            'documents' => $this->dataDirectory . '/project_documents.json',
            'authentication_history' => $this->dataDirectory . '/project_authentication_history.json',
            'collaborators' => $this->dataDirectory . '/project_collaborators.json',
            'invites' => $this->dataDirectory . '/project_collaboration_invites.json',
            'timeline_events' => $this->dataDirectory . '/project_timeline_events.json',
            'timeline_history' => $this->dataDirectory . '/project_timeline_history.json',
            'notifications' => $this->dataDirectory . '/notifications.json',
        ];

        $this->ensureStorage();
    }

    public function getTimelineTypeOptions(): array
    {
        return self::TIMELINE_TYPES;
    }

    public function getTimelineTypeLabel(string $type): string
    {
        $type = $this->normalizeTimelineType($type);

        return self::TIMELINE_TYPES[$type] ?? self::TIMELINE_TYPES['update'];
    }

    public function getAuthenticationLabel(string $status): string
    {
        switch ($this->normalizeAuthenticationStatus($status)) {
            case 'approved':
                return 'Aprovado';
            case 'pending':
                return 'Pendente';
            case 'rejected':
                return 'Rejeitado';
            default:
                return 'Sem documentacao';
        }
    }

    public function getAuthenticationStatus(array $project): array
    {
        $projectId = (string) ($project['id'] ?? '');
        $status = $this->normalizeAuthenticationStatus((string) ($project['authentication_status'] ?? 'missing'));
        $documentId = (string) ($project['authenticated_document_id'] ?? '');
        $documents = $this->getProjectDocuments($projectId);
        $latestDocument = $documents[0] ?? null;
        $approvedDocument = null;

        foreach ($documents as $document) {
            if ((string) ($document['id'] ?? '') === $documentId) {
                $approvedDocument = $document;
                break;
            }
        }

        if ($approvedDocument === null) {
            foreach ($documents as $document) {
                if ((string) ($document['status'] ?? '') === 'approved') {
                    $approvedDocument = $document;
                    break;
                }
            }
        }

        return [
            'status' => $status,
            'label' => $this->getAuthenticationLabel($status),
            'latest_document' => $latestDocument,
            'approved_document' => $approvedDocument,
            'document_count' => count($documents),
        ];
    }

    public function getProjectDocuments(string $projectId): array
    {
        $documents = array_values(array_filter($this->loadRecords('documents'), static function (array $document) use ($projectId): bool {
            return (string) ($document['project_id'] ?? '') === $projectId;
        }));

        usort($documents, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''));
        });

        return $documents;
    }

    public function getPendingProjectDocuments(): array
    {
        $documents = array_values(array_filter($this->loadRecords('documents'), static function (array $document): bool {
            return (string) ($document['status'] ?? '') === 'pending';
        }));

        usort($documents, static function (array $left, array $right): int {
            return strtotime((string) ($left['created_at'] ?? '')) <=> strtotime((string) ($right['created_at'] ?? ''));
        });

        return $documents;
    }

    public function getDocument(string $documentId): ?array
    {
        foreach ($this->loadRecords('documents') as $document) {
            if ((string) ($document['id'] ?? '') === $documentId) {
                return $document;
            }
        }

        return null;
    }

    public function getAuthenticationHistory(string $projectId): array
    {
        $history = array_values(array_filter($this->loadRecords('authentication_history'), static function (array $entry) use ($projectId): bool {
            return (string) ($entry['project_id'] ?? '') === $projectId;
        }));

        usort($history, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''));
        });

        return $history;
    }

    public function validateProjectDocumentUpload(?array $file): array
    {
        return $this->validateFileUpload($file, self::DOCUMENT_EXTENSIONS, self::DOCUMENT_MAX_BYTES, true);
    }

    public function uploadProjectDocument(array $project, array $user, ?array $file): array
    {
        $validation = $this->validateProjectDocumentUpload($file);
        if (!$validation['success']) {
            return $validation;
        }

        $stored = $this->storeUploadedFile($file, $this->documentStorageDirectory, 'document_', (string) $validation['extension']);
        if (!$stored['success']) {
            return $stored;
        }

        $projectId = (string) ($project['id'] ?? '');
        $document = [
            'id' => $this->nextId('doc_'),
            'project_id' => $projectId,
            'uploaded_by_user_id' => (int) ($user['id'] ?? 0),
            'original_name' => $this->sanitizeFilename((string) ($file['name'] ?? 'documento')),
            'stored_name' => (string) $stored['stored_name'],
            'storage_path' => (string) $stored['storage_path'],
            'mime_type' => (string) $validation['mime_type'],
            'extension' => (string) $validation['extension'],
            'size_bytes' => (int) ($file['size'] ?? 0),
            'sha256_hash' => hash_file('sha256', (string) $stored['absolute_path']) ?: '',
            'status' => 'pending',
            'review_notes' => '',
            'reviewed_by_user_id' => null,
            'reviewed_at' => null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $documents = $this->loadRecords('documents');
        $documents[] = $document;
        $this->saveRecords('documents', $documents);

        $this->appendAuthenticationHistory($projectId, $document['id'], (int) ($user['id'] ?? 0), 'submitted', 'Documento enviado para analise.');
        $this->projectManager->setProjectAuthenticationStatus($projectId, 'pending');

        return [
            'success' => true,
            'document' => $document,
        ];
    }

    public function reviewProjectDocument(string $documentId, array $reviewer, string $decision, string $notes = ''): array
    {
        $decision = $this->normalizeDocumentDecision($decision);
        if ($decision === '') {
            return ['success' => false, 'errors' => ['Decisao invalida para o documento.']];
        }

        $documents = $this->loadRecords('documents');
        $documentIndex = null;

        foreach ($documents as $index => $document) {
            if ((string) ($document['id'] ?? '') === $documentId) {
                $documentIndex = $index;
                break;
            }
        }

        if ($documentIndex === null) {
            return ['success' => false, 'errors' => ['Documento nao encontrado.']];
        }

        $documents[$documentIndex]['status'] = $decision;
        $documents[$documentIndex]['review_notes'] = trim($notes);
        $documents[$documentIndex]['reviewed_by_user_id'] = (int) ($reviewer['id'] ?? 0);
        $documents[$documentIndex]['reviewed_at'] = $this->now();
        $documents[$documentIndex]['updated_at'] = $this->now();

        $this->saveRecords('documents', $documents);

        $document = $documents[$documentIndex];
        $projectId = (string) ($document['project_id'] ?? '');
        $action = $decision === 'approved' ? 'approved' : 'rejected';
        $this->appendAuthenticationHistory($projectId, $documentId, (int) ($reviewer['id'] ?? 0), $action, trim($notes));

        if ($decision === 'approved') {
            $this->projectManager->setProjectAuthenticationStatus($projectId, 'approved', $documentId, $this->now());
        } elseif (!$this->projectHasApprovedDocument($projectId)) {
            $this->projectManager->setProjectAuthenticationStatus($projectId, 'rejected');
        }

        return [
            'success' => true,
            'document' => $document,
        ];
    }

    public function getProjectCollaborators(string $projectId, bool $includeInactive = false): array
    {
        $collaborators = array_values(array_filter($this->loadRecords('collaborators'), static function (array $collaborator) use ($projectId, $includeInactive): bool {
            if ((string) ($collaborator['project_id'] ?? '') !== $projectId) {
                return false;
            }

            return $includeInactive || (string) ($collaborator['status'] ?? 'active') === 'active';
        }));

        usort($collaborators, static function (array $left, array $right): int {
            return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
        });

        return $collaborators;
    }

    public function getActiveCollaborator(string $projectId, int $userId): ?array
    {
        foreach ($this->getProjectCollaborators($projectId) as $collaborator) {
            if ((int) ($collaborator['user_id'] ?? 0) === $userId) {
                return $collaborator;
            }
        }

        return null;
    }

    public function getProjectInvites(string $projectId): array
    {
        $invites = array_values(array_filter($this->loadRecords('invites'), static function (array $invite) use ($projectId): bool {
            return (string) ($invite['project_id'] ?? '') === $projectId
                && (string) ($invite['status'] ?? '') === 'pending';
        }));

        usort($invites, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''));
        });

        return $invites;
    }

    public function getUserInvites(int $userId, string $status = 'pending'): array
    {
        $invites = array_values(array_filter($this->loadRecords('invites'), static function (array $invite) use ($userId, $status): bool {
            return (int) ($invite['invited_user_id'] ?? 0) === $userId
                && (string) ($invite['status'] ?? '') === $status;
        }));

        usort($invites, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''));
        });

        return $invites;
    }

    public function getInvite(string $inviteId): ?array
    {
        foreach ($this->loadRecords('invites') as $invite) {
            if ((string) ($invite['id'] ?? '') === $inviteId) {
                return $invite;
            }
        }

        return null;
    }

    public function inviteCollaborator(array $project, array $actor, int $invitedUserId, string $role = 'collaborator'): array
    {
        if (!$this->canManageProject($project, $actor)) {
            return ['success' => false, 'errors' => ['Voce nao tem permissao para convidar colaboradores neste projeto.']];
        }

        $projectId = (string) ($project['id'] ?? '');
        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($project['user_id'] ?? 0);
        $role = $this->normalizeProjectRole($role);

        if ($invitedUserId <= 0) {
            return ['success' => false, 'errors' => ['Selecione um usuario valido.']];
        }

        if ($invitedUserId === $ownerId) {
            return ['success' => false, 'errors' => ['O responsavel ja possui controle total do projeto.']];
        }

        if ($this->getActiveCollaborator($projectId, $invitedUserId) !== null) {
            return ['success' => false, 'errors' => ['Este usuario ja e colaborador do projeto.']];
        }

        foreach ($this->getProjectInvites($projectId) as $invite) {
            if ((int) ($invite['invited_user_id'] ?? 0) === $invitedUserId) {
                return ['success' => false, 'errors' => ['Ja existe um convite pendente para este usuario.']];
            }
        }

        $invite = [
            'id' => $this->nextId('inv_'),
            'project_id' => $projectId,
            'invited_user_id' => $invitedUserId,
            'invited_by_user_id' => $actorId,
            'role' => $role,
            'status' => 'pending',
            'responded_at' => null,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];

        $invites = $this->loadRecords('invites');
        $invites[] = $invite;
        $this->saveRecords('invites', $invites);

        $this->createNotification(
            $invitedUserId,
            'collaboration_invite',
            'Convite de colaboracao',
            'Voce recebeu um convite para colaborar em "' . $this->projectTitle($project) . '".',
            $projectId,
            'notifications.php',
            $actorId
        );

        return [
            'success' => true,
            'invite' => $invite,
        ];
    }

    public function respondToInvitation(string $inviteId, array $user, string $response): array
    {
        $response = strtolower(trim($response));
        if (!in_array($response, ['accepted', 'rejected'], true)) {
            return ['success' => false, 'errors' => ['Resposta invalida para o convite.']];
        }

        $invites = $this->loadRecords('invites');
        $inviteIndex = null;

        foreach ($invites as $index => $invite) {
            if ((string) ($invite['id'] ?? '') === $inviteId) {
                $inviteIndex = $index;
                break;
            }
        }

        if ($inviteIndex === null) {
            return ['success' => false, 'errors' => ['Convite nao encontrado.']];
        }

        $invite = $invites[$inviteIndex];
        $userId = (int) ($user['id'] ?? 0);

        if ((int) ($invite['invited_user_id'] ?? 0) !== $userId) {
            return ['success' => false, 'errors' => ['Este convite pertence a outro usuario.']];
        }

        if ((string) ($invite['status'] ?? '') !== 'pending') {
            return ['success' => false, 'errors' => ['Este convite ja foi respondido.']];
        }

        $invites[$inviteIndex]['status'] = $response;
        $invites[$inviteIndex]['responded_at'] = $this->now();
        $invites[$inviteIndex]['updated_at'] = $this->now();
        $this->saveRecords('invites', $invites);

        $project = $this->projectManager->getProject((string) ($invite['project_id'] ?? ''));
        if (!is_array($project)) {
            return ['success' => true, 'invite' => $invites[$inviteIndex]];
        }

        if ($response === 'accepted') {
            $this->addCollaboratorRecord($project, $userId, (string) ($invite['role'] ?? 'collaborator'), (int) ($invite['invited_by_user_id'] ?? 0));
            $this->notifyProjectManagers(
                $project,
                'collaboration_accepted',
                'Convite aceito',
                $this->userName($user) . ' aceitou colaborar em "' . $this->projectTitle($project) . '".',
                $userId
            );
            $this->notifyProjectParticipants(
                $project,
                'collaborator_added',
                'Novo colaborador',
                $this->userName($user) . ' entrou no projeto "' . $this->projectTitle($project) . '".',
                $userId,
                $userId
            );
        } else {
            $this->notifyProjectManagers(
                $project,
                'collaboration_rejected',
                'Convite recusado',
                $this->userName($user) . ' recusou o convite para "' . $this->projectTitle($project) . '".',
                $userId
            );
        }

        return [
            'success' => true,
            'invite' => $invites[$inviteIndex],
        ];
    }

    public function removeCollaborator(array $project, array $actor, int $userId): array
    {
        if (!$this->canManageProject($project, $actor)) {
            return ['success' => false, 'errors' => ['Voce nao tem permissao para remover colaboradores.']];
        }

        $projectId = (string) ($project['id'] ?? '');
        $collaborators = $this->loadRecords('collaborators');
        $changed = false;

        foreach ($collaborators as &$collaborator) {
            if (
                (string) ($collaborator['project_id'] ?? '') === $projectId
                && (int) ($collaborator['user_id'] ?? 0) === $userId
                && (string) ($collaborator['status'] ?? 'active') === 'active'
            ) {
                $collaborator['status'] = 'removed';
                $collaborator['updated_at'] = $this->now();
                $changed = true;
                break;
            }
        }
        unset($collaborator);

        if (!$changed) {
            return ['success' => false, 'errors' => ['Colaborador nao encontrado.']];
        }

        $this->saveRecords('collaborators', $collaborators);
        $this->createNotification(
            $userId,
            'collaborator_removed',
            'Colaboracao encerrada',
            'Seu acesso de colaborador em "' . $this->projectTitle($project) . '" foi removido.',
            $projectId,
            'project.php?id=' . rawurlencode($projectId),
            (int) ($actor['id'] ?? 0)
        );

        return ['success' => true];
    }

    public function canManageProject(array $project, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        $userId = (int) ($user['id'] ?? 0);
        $role = strtolower(trim((string) ($user['role'] ?? '')));

        if ($role === 'admin' || $userId === 1 || strtolower((string) ($user['username'] ?? '')) === 'admin') {
            return true;
        }

        if ($userId > 0 && (int) ($project['user_id'] ?? 0) === $userId) {
            return true;
        }

        $collaborator = $this->getActiveCollaborator((string) ($project['id'] ?? ''), $userId);

        return is_array($collaborator) && (string) ($collaborator['role'] ?? '') === 'project_admin';
    }

    public function canEditTimeline(array $project, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canManageProject($project, $user)) {
            return true;
        }

        return $this->getActiveCollaborator((string) ($project['id'] ?? ''), (int) ($user['id'] ?? 0)) !== null;
    }

    public function canViewWorkspace(array $project, ?array $user): bool
    {
        return $this->canEditTimeline($project, $user);
    }

    public function canEditTimelineEvent(array $project, array $event, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canManageProject($project, $user)) {
            return true;
        }

        return (int) ($event['author_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
    }

    public function getProjectTimeline(string $projectId, bool $includeDeleted = false): array
    {
        $events = array_values(array_filter($this->loadRecords('timeline_events'), static function (array $event) use ($projectId, $includeDeleted): bool {
            if ((string) ($event['project_id'] ?? '') !== $projectId) {
                return false;
            }

            return $includeDeleted || empty($event['deleted_at']);
        }));

        usort($events, static function (array $left, array $right): int {
            $dateCompare = strcmp((string) ($left['event_date'] ?? ''), (string) ($right['event_date'] ?? ''));
            if ($dateCompare !== 0) {
                return $dateCompare;
            }

            return strcmp((string) ($left['created_at'] ?? ''), (string) ($right['created_at'] ?? ''));
        });

        return $events;
    }

    public function getTimelineEvent(string $eventId): ?array
    {
        foreach ($this->loadRecords('timeline_events') as $event) {
            if ((string) ($event['id'] ?? '') === $eventId) {
                return $event;
            }
        }

        return null;
    }

    public function addTimelineEvent(array $project, array $user, array $data, ?array $file = null): array
    {
        if (!$this->canEditTimeline($project, $user)) {
            return ['success' => false, 'errors' => ['Voce nao tem permissao para atualizar a timeline deste projeto.']];
        }

        $normalized = $this->normalizeTimelinePayload($data);
        if (!empty($normalized['errors'])) {
            return ['success' => false, 'errors' => $normalized['errors']];
        }

        $attachment = $this->storeTimelineAttachmentIfPresent($file);
        if (!$attachment['success']) {
            return ['success' => false, 'errors' => [$attachment['error']]];
        }

        $projectId = (string) ($project['id'] ?? '');
        $event = [
            'id' => $this->nextId('tl_'),
            'project_id' => $projectId,
            'title' => $normalized['title'],
            'description' => $normalized['description'],
            'event_date' => $normalized['event_date'],
            'event_type' => $normalized['event_type'],
            'author_user_id' => (int) ($user['id'] ?? 0),
            'attachment_original_name' => $attachment['original_name'] ?? '',
            'attachment_stored_name' => $attachment['stored_name'] ?? '',
            'attachment_path' => $attachment['storage_path'] ?? '',
            'attachment_mime_type' => $attachment['mime_type'] ?? '',
            'attachment_size_bytes' => $attachment['size_bytes'] ?? 0,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
            'deleted_at' => null,
        ];

        $events = $this->loadRecords('timeline_events');
        $events[] = $event;
        $this->saveRecords('timeline_events', $events);
        $this->appendTimelineHistory($event['id'], $projectId, (int) ($user['id'] ?? 0), 'created', null, $event);

        $this->notifyProjectParticipants(
            $project,
            'timeline_added',
            'Nova atualizacao na timeline',
            '"' . $event['title'] . '" foi adicionada em "' . $this->projectTitle($project) . '".',
            (int) ($user['id'] ?? 0),
            (int) ($user['id'] ?? 0)
        );

        return [
            'success' => true,
            'event' => $event,
        ];
    }

    public function updateTimelineEvent(array $project, string $eventId, array $user, array $data, ?array $file = null): array
    {
        $events = $this->loadRecords('timeline_events');
        $eventIndex = null;

        foreach ($events as $index => $event) {
            if ((string) ($event['id'] ?? '') === $eventId && empty($event['deleted_at'])) {
                $eventIndex = $index;
                break;
            }
        }

        if ($eventIndex === null) {
            return ['success' => false, 'errors' => ['Evento nao encontrado.']];
        }

        if (!$this->canEditTimelineEvent($project, $events[$eventIndex], $user)) {
            return ['success' => false, 'errors' => ['Voce nao pode editar este evento.']];
        }

        $normalized = $this->normalizeTimelinePayload($data);
        if (!empty($normalized['errors'])) {
            return ['success' => false, 'errors' => $normalized['errors']];
        }

        $before = $events[$eventIndex];
        $attachment = $this->storeTimelineAttachmentIfPresent($file);
        if (!$attachment['success']) {
            return ['success' => false, 'errors' => [$attachment['error']]];
        }

        $events[$eventIndex]['title'] = $normalized['title'];
        $events[$eventIndex]['description'] = $normalized['description'];
        $events[$eventIndex]['event_date'] = $normalized['event_date'];
        $events[$eventIndex]['event_type'] = $normalized['event_type'];
        $events[$eventIndex]['updated_at'] = $this->now();

        if (!empty($attachment['has_file'])) {
            $this->deleteStoredFile((string) ($events[$eventIndex]['attachment_path'] ?? ''), $this->timelineStorageDirectory);
            $events[$eventIndex]['attachment_original_name'] = $attachment['original_name'];
            $events[$eventIndex]['attachment_stored_name'] = $attachment['stored_name'];
            $events[$eventIndex]['attachment_path'] = $attachment['storage_path'];
            $events[$eventIndex]['attachment_mime_type'] = $attachment['mime_type'];
            $events[$eventIndex]['attachment_size_bytes'] = $attachment['size_bytes'];
        }

        $this->saveRecords('timeline_events', $events);
        $this->appendTimelineHistory($eventId, (string) ($project['id'] ?? ''), (int) ($user['id'] ?? 0), 'updated', $before, $events[$eventIndex]);

        return [
            'success' => true,
            'event' => $events[$eventIndex],
        ];
    }

    public function deleteTimelineEvent(array $project, string $eventId, array $user): array
    {
        $events = $this->loadRecords('timeline_events');
        $eventIndex = null;

        foreach ($events as $index => $event) {
            if ((string) ($event['id'] ?? '') === $eventId && empty($event['deleted_at'])) {
                $eventIndex = $index;
                break;
            }
        }

        if ($eventIndex === null) {
            return ['success' => false, 'errors' => ['Evento nao encontrado.']];
        }

        if (!$this->canEditTimelineEvent($project, $events[$eventIndex], $user)) {
            return ['success' => false, 'errors' => ['Voce nao pode remover este evento.']];
        }

        $before = $events[$eventIndex];
        $events[$eventIndex]['deleted_at'] = $this->now();
        $events[$eventIndex]['updated_at'] = $this->now();
        $this->saveRecords('timeline_events', $events);
        $this->appendTimelineHistory($eventId, (string) ($project['id'] ?? ''), (int) ($user['id'] ?? 0), 'deleted', $before, $events[$eventIndex]);

        return ['success' => true];
    }

    public function getTimelineHistory(string $eventId): array
    {
        return array_values(array_filter($this->loadRecords('timeline_history'), static function (array $entry) use ($eventId): bool {
            return (string) ($entry['event_id'] ?? '') === $eventId;
        }));
    }

    public function getAccessibleProjectsForUser(array $projects, array $user): array
    {
        $userId = (int) ($user['id'] ?? 0);
        $role = strtolower(trim((string) ($user['role'] ?? '')));

        if ($role === 'admin' || $userId === 1) {
            return $projects;
        }

        $accessible = [];
        foreach ($projects as $project) {
            if ($this->canViewWorkspace($project, $user)) {
                $accessible[] = $project;
            }
        }

        return $accessible;
    }

    public function createNotification(
        int $userId,
        string $type,
        string $title,
        string $body,
        ?string $projectId = null,
        ?string $targetUrl = null,
        ?int $actorUserId = null
    ): ?array {
        if ($userId <= 0) {
            return null;
        }

        $notification = [
            'id' => $this->nextId('ntf_'),
            'user_id' => $userId,
            'type' => trim($type) !== '' ? trim($type) : 'general',
            'title' => trim($title) !== '' ? trim($title) : 'Notificacao',
            'body' => trim($body),
            'project_id' => $projectId,
            'target_url' => $targetUrl,
            'actor_user_id' => $actorUserId,
            'read_at' => null,
            'created_at' => $this->now(),
        ];

        $notifications = $this->loadRecords('notifications');
        $notifications[] = $notification;
        $this->saveRecords('notifications', $notifications);

        return $notification;
    }

    public function notifyAdministrators(array $users, string $type, string $title, string $body, ?string $projectId = null, ?string $targetUrl = null, ?int $actorUserId = null): int
    {
        $count = 0;
        foreach ($users as $user) {
            $role = strtolower(trim((string) ($user['role'] ?? '')));
            if ($role !== 'admin' && (int) ($user['id'] ?? 0) !== 1) {
                continue;
            }

            if ($this->createNotification((int) ($user['id'] ?? 0), $type, $title, $body, $projectId, $targetUrl, $actorUserId)) {
                $count++;
            }
        }

        return $count;
    }

    public function notifyProjectParticipants(array $project, string $type, string $title, string $body, ?int $actorUserId = null, ?int $excludeUserId = null): int
    {
        $projectId = (string) ($project['id'] ?? '');
        $userIds = $this->getProjectParticipantIds($project);
        $count = 0;

        foreach ($userIds as $userId) {
            if ($excludeUserId !== null && $userId === $excludeUserId) {
                continue;
            }

            if ($this->createNotification($userId, $type, $title, $body, $projectId, 'project-workspace.php?id=' . rawurlencode($projectId), $actorUserId)) {
                $count++;
            }
        }

        return $count;
    }

    public function notifyProjectManagers(array $project, string $type, string $title, string $body, ?int $actorUserId = null): int
    {
        $projectId = (string) ($project['id'] ?? '');
        $userIds = [];
        $ownerId = (int) ($project['user_id'] ?? 0);
        if ($ownerId > 0) {
            $userIds[$ownerId] = true;
        }

        foreach ($this->getProjectCollaborators($projectId) as $collaborator) {
            if ((string) ($collaborator['role'] ?? '') === 'project_admin') {
                $userIds[(int) ($collaborator['user_id'] ?? 0)] = true;
            }
        }

        $count = 0;
        foreach (array_keys($userIds) as $userId) {
            if ($this->createNotification((int) $userId, $type, $title, $body, $projectId, 'project-workspace.php?id=' . rawurlencode($projectId), $actorUserId)) {
                $count++;
            }
        }

        return $count;
    }

    public function getUserNotifications(int $userId, int $limit = 80): array
    {
        $notifications = array_values(array_filter($this->loadRecords('notifications'), static function (array $notification) use ($userId): bool {
            return (int) ($notification['user_id'] ?? 0) === $userId;
        }));

        usort($notifications, static function (array $left, array $right): int {
            return strtotime((string) ($right['created_at'] ?? '')) <=> strtotime((string) ($left['created_at'] ?? ''));
        });

        return array_slice($notifications, 0, max(1, $limit));
    }

    public function getUnreadNotificationCount(int $userId): int
    {
        return count(array_filter($this->loadRecords('notifications'), static function (array $notification) use ($userId): bool {
            return (int) ($notification['user_id'] ?? 0) === $userId && empty($notification['read_at']);
        }));
    }

    public function markNotificationRead(string $notificationId, int $userId): bool
    {
        $notifications = $this->loadRecords('notifications');
        $changed = false;

        foreach ($notifications as &$notification) {
            if (
                (string) ($notification['id'] ?? '') === $notificationId
                && (int) ($notification['user_id'] ?? 0) === $userId
            ) {
                $notification['read_at'] = $notification['read_at'] ?: $this->now();
                $changed = true;
                break;
            }
        }
        unset($notification);

        if ($changed) {
            $this->saveRecords('notifications', $notifications);
        }

        return $changed;
    }

    public function markAllNotificationsRead(int $userId): int
    {
        $notifications = $this->loadRecords('notifications');
        $count = 0;

        foreach ($notifications as &$notification) {
            if ((int) ($notification['user_id'] ?? 0) === $userId && empty($notification['read_at'])) {
                $notification['read_at'] = $this->now();
                $count++;
            }
        }
        unset($notification);

        if ($count > 0) {
            $this->saveRecords('notifications', $notifications);
        }

        return $count;
    }

    public function canViewProjectDocument(array $document, array $project, ?array $user): bool
    {
        if (!$user) {
            return false;
        }

        if ($this->canViewWorkspace($project, $user)) {
            return true;
        }

        return (int) ($document['uploaded_by_user_id'] ?? 0) === (int) ($user['id'] ?? 0);
    }

    public function getDocumentFileInfo(string $documentId): ?array
    {
        $document = $this->getDocument($documentId);
        if (!$document) {
            return null;
        }

        $path = $this->resolveStoragePath((string) ($document['storage_path'] ?? ''), $this->documentStorageDirectory);
        if ($path === null) {
            return null;
        }

        return [
            'record' => $document,
            'absolute_path' => $path,
            'download_name' => (string) ($document['original_name'] ?? 'documento.' . ($document['extension'] ?? 'pdf')),
            'mime_type' => (string) ($document['mime_type'] ?? 'application/octet-stream'),
        ];
    }

    public function getTimelineAttachmentFileInfo(string $eventId): ?array
    {
        $event = $this->getTimelineEvent($eventId);
        if (!$event || empty($event['attachment_path']) || !empty($event['deleted_at'])) {
            return null;
        }

        $path = $this->resolveStoragePath((string) ($event['attachment_path'] ?? ''), $this->timelineStorageDirectory);
        if ($path === null) {
            return null;
        }

        return [
            'record' => $event,
            'absolute_path' => $path,
            'download_name' => (string) ($event['attachment_original_name'] ?? 'anexo'),
            'mime_type' => (string) ($event['attachment_mime_type'] ?? 'application/octet-stream'),
        ];
    }

    public function deleteProjectData(string $projectId): void
    {
        foreach ($this->getProjectDocuments($projectId) as $document) {
            $this->deleteStoredFile((string) ($document['storage_path'] ?? ''), $this->documentStorageDirectory);
        }

        foreach ($this->getProjectTimeline($projectId, true) as $event) {
            $this->deleteStoredFile((string) ($event['attachment_path'] ?? ''), $this->timelineStorageDirectory);
        }

        foreach (['documents', 'authentication_history', 'collaborators', 'invites', 'timeline_events', 'timeline_history', 'notifications'] as $key) {
            $records = array_values(array_filter($this->loadRecords($key), static function (array $record) use ($projectId, $key): bool {
                if ($key === 'timeline_history') {
                    return (string) ($record['project_id'] ?? '') !== $projectId;
                }

                return (string) ($record['project_id'] ?? '') !== $projectId;
            }));
            $this->saveRecords($key, $records);
        }
    }

    private function addCollaboratorRecord(array $project, int $userId, string $role, int $invitedByUserId): void
    {
        $projectId = (string) ($project['id'] ?? '');
        if ($this->getActiveCollaborator($projectId, $userId) !== null) {
            return;
        }

        $collaborators = $this->loadRecords('collaborators');
        $collaborators[] = [
            'id' => $this->nextId('col_'),
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $this->normalizeProjectRole($role),
            'status' => 'active',
            'invited_by_user_id' => $invitedByUserId,
            'created_at' => $this->now(),
            'updated_at' => $this->now(),
        ];
        $this->saveRecords('collaborators', $collaborators);
    }

    private function projectHasApprovedDocument(string $projectId): bool
    {
        foreach ($this->getProjectDocuments($projectId) as $document) {
            if ((string) ($document['status'] ?? '') === 'approved') {
                return true;
            }
        }

        return false;
    }

    private function appendAuthenticationHistory(string $projectId, string $documentId, int $actorUserId, string $action, string $notes = ''): void
    {
        $history = $this->loadRecords('authentication_history');
        $history[] = [
            'id' => $this->nextId('auh_'),
            'project_id' => $projectId,
            'document_id' => $documentId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'notes' => trim($notes),
            'created_at' => $this->now(),
        ];
        $this->saveRecords('authentication_history', $history);
    }

    private function appendTimelineHistory(string $eventId, string $projectId, int $actorUserId, string $action, ?array $before, ?array $after): void
    {
        $history = $this->loadRecords('timeline_history');
        $history[] = [
            'id' => $this->nextId('tlh_'),
            'event_id' => $eventId,
            'project_id' => $projectId,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'before' => $before,
            'after' => $after,
            'created_at' => $this->now(),
        ];
        $this->saveRecords('timeline_history', $history);
    }

    private function normalizeTimelinePayload(array $data): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $eventDate = trim((string) ($data['event_date'] ?? ''));
        $eventType = $this->normalizeTimelineType((string) ($data['event_type'] ?? 'update'));
        $errors = [];

        if ($title === '') {
            $errors[] = 'Informe um titulo para o evento.';
        }

        if ($description === '') {
            $errors[] = 'Informe uma descricao para o evento.';
        }

        if ($eventDate === '') {
            $eventDate = date('Y-m-d');
        }

        $date = DateTime::createFromFormat('Y-m-d', $eventDate);
        if (!$date || $date->format('Y-m-d') !== $eventDate) {
            $errors[] = 'Informe uma data valida para o evento.';
        }

        return [
            'title' => $title,
            'description' => $description,
            'event_date' => $eventDate,
            'event_type' => $eventType,
            'errors' => $errors,
        ];
    }

    private function storeTimelineAttachmentIfPresent(?array $file): array
    {
        if (!$this->hasUploadedFile($file)) {
            return ['success' => true, 'has_file' => false];
        }

        $validation = $this->validateFileUpload($file, self::TIMELINE_EXTENSIONS, self::TIMELINE_ATTACHMENT_MAX_BYTES, false);
        if (!$validation['success']) {
            return ['success' => false, 'error' => $validation['error']];
        }

        $stored = $this->storeUploadedFile($file, $this->timelineStorageDirectory, 'timeline_', (string) $validation['extension']);
        if (!$stored['success']) {
            return ['success' => false, 'error' => $stored['error']];
        }

        return [
            'success' => true,
            'has_file' => true,
            'original_name' => $this->sanitizeFilename((string) ($file['name'] ?? 'anexo')),
            'stored_name' => (string) $stored['stored_name'],
            'storage_path' => (string) $stored['storage_path'],
            'mime_type' => (string) $validation['mime_type'],
            'size_bytes' => (int) ($file['size'] ?? 0),
        ];
    }

    private function validateFileUpload(?array $file, array $allowedExtensions, int $maxBytes, bool $strictDocument): array
    {
        if (!$this->hasUploadedFile($file)) {
            return ['success' => false, 'error' => 'Envie um arquivo valido.'];
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['success' => false, 'error' => 'Nao foi possivel processar o upload.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return ['success' => false, 'error' => 'O arquivo enviado nao foi reconhecido pelo servidor.'];
        }

        $size = (int) ($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return ['success' => false, 'error' => 'O arquivo excede o tamanho permitido.'];
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        if (!in_array($extension, $allowedExtensions, true)) {
            return ['success' => false, 'error' => 'Extensao nao permitida para este upload.'];
        }

        $mimeType = $this->detectMimeType($tmpName);
        $signatureResult = $this->validateFileSignature($tmpName, $extension, $mimeType, $strictDocument);

        if (!$signatureResult['success']) {
            return ['success' => false, 'error' => $signatureResult['error']];
        }

        return [
            'success' => true,
            'extension' => $extension,
            'mime_type' => $signatureResult['mime_type'] ?: $mimeType,
        ];
    }

    private function validateFileSignature(string $tmpName, string $extension, string $mimeType, bool $strictDocument): array
    {
        $mimeType = strtolower(trim($mimeType));
        $head = (string) @file_get_contents($tmpName, false, null, 0, 8);

        if ($extension === 'pdf') {
            if (strncmp($head, '%PDF-', 5) !== 0) {
                return ['success' => false, 'error' => 'O PDF enviado nao possui assinatura valida.'];
            }

            return ['success' => true, 'mime_type' => 'application/pdf'];
        }

        if ($extension === 'docx') {
            if (strncmp($head, "PK", 2) !== 0) {
                return ['success' => false, 'error' => 'O DOCX enviado nao possui estrutura valida.'];
            }

            if (class_exists('ZipArchive')) {
                $zip = new ZipArchive();
                if ($zip->open($tmpName) !== true) {
                    return ['success' => false, 'error' => 'Nao foi possivel validar a estrutura DOCX.'];
                }

                $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;
                $hasDocument = $zip->locateName('word/document.xml') !== false;
                $zip->close();

                if (!$hasContentTypes || !$hasDocument) {
                    return ['success' => false, 'error' => 'O arquivo DOCX nao contem os metadados esperados.'];
                }
            }

            return [
                'success' => true,
                'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ];
        }

        if ($strictDocument) {
            return ['success' => false, 'error' => 'A documentacao deve ser PDF ou DOCX.'];
        }

        $imageMimes = [
            'jpg' => ['image/jpeg', 'image/pjpeg'],
            'png' => ['image/png', 'image/x-png'],
            'webp' => ['image/webp'],
        ];

        if (isset($imageMimes[$extension]) && in_array($mimeType, $imageMimes[$extension], true)) {
            return ['success' => true, 'mime_type' => $mimeType];
        }

        return ['success' => false, 'error' => 'Arquivo de anexo nao suportado.'];
    }

    private function storeUploadedFile(array $file, string $directory, string $prefix, string $extension): array
    {
        if (!is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $storedName = uniqid($prefix, true) . '.' . $extension;
        $absolutePath = $directory . DIRECTORY_SEPARATOR . $storedName;

        if (!move_uploaded_file((string) ($file['tmp_name'] ?? ''), $absolutePath)) {
            return ['success' => false, 'error' => 'Nao foi possivel salvar o arquivo no servidor.'];
        }

        return [
            'success' => true,
            'stored_name' => $storedName,
            'storage_path' => $storedName,
            'absolute_path' => $absolutePath,
        ];
    }

    private function getProjectParticipantIds(array $project): array
    {
        $projectId = (string) ($project['id'] ?? '');
        $ids = [];
        $ownerId = (int) ($project['user_id'] ?? 0);

        if ($ownerId > 0) {
            $ids[$ownerId] = true;
        }

        foreach ($this->getProjectCollaborators($projectId) as $collaborator) {
            $userId = (int) ($collaborator['user_id'] ?? 0);
            if ($userId > 0) {
                $ids[$userId] = true;
            }
        }

        return array_map('intval', array_keys($ids));
    }

    private function normalizeDocumentDecision(string $decision): string
    {
        $decision = strtolower(trim($decision));
        if ($decision === 'approve') {
            return 'approved';
        }
        if ($decision === 'reject') {
            return 'rejected';
        }

        return in_array($decision, ['approved', 'rejected'], true) ? $decision : '';
    }

    private function normalizeProjectRole(string $role): string
    {
        $role = strtolower(trim($role));

        return in_array($role, ['collaborator', 'project_admin'], true) ? $role : 'collaborator';
    }

    private function normalizeTimelineType(string $type): string
    {
        $type = strtolower(trim($type));

        return isset(self::TIMELINE_TYPES[$type]) ? $type : 'update';
    }

    private function normalizeAuthenticationStatus(string $status): string
    {
        $status = strtolower(trim($status));

        return in_array($status, ['missing', 'pending', 'approved', 'rejected'], true) ? $status : 'missing';
    }

    private function hasUploadedFile(?array $file): bool
    {
        return is_array($file)
            && isset($file['error'])
            && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
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

    private function sanitizeFilename(string $filename): string
    {
        $filename = basename($filename);
        $filename = preg_replace('/[^A-Za-z0-9._ -]/', '_', $filename);
        $filename = trim((string) $filename);

        return $filename !== '' ? $filename : 'arquivo';
    }

    private function resolveStoragePath(string $storagePath, string $baseDirectory): ?string
    {
        $filename = basename($storagePath);
        if ($filename === '') {
            return null;
        }

        $candidate = $baseDirectory . DIRECTORY_SEPARATOR . $filename;
        $realBase = realpath($baseDirectory);
        $realCandidate = realpath($candidate);

        if ($realBase === false || $realCandidate === false || strpos($realCandidate, $realBase) !== 0 || !is_file($realCandidate)) {
            return null;
        }

        return $realCandidate;
    }

    private function deleteStoredFile(string $storagePath, string $baseDirectory): void
    {
        $path = $this->resolveStoragePath($storagePath, $baseDirectory);
        if ($path !== null) {
            @unlink($path);
        }
    }

    private function loadRecords(string $key): array
    {
        $file = $this->files[$key] ?? null;
        if ($file === null || !file_exists($file)) {
            return [];
        }

        $contents = @file_get_contents($file);
        $decoded = json_decode((string) $contents, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    private function saveRecords(string $key, array $records): bool
    {
        $file = $this->files[$key] ?? null;
        if ($file === null) {
            return false;
        }

        return (bool) file_put_contents(
            $file,
            json_encode(array_values($records), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            LOCK_EX
        );
    }

    private function ensureStorage(): void
    {
        if (!is_dir($this->dataDirectory)) {
            mkdir($this->dataDirectory, 0775, true);
        }

        foreach ([$this->documentStorageDirectory, $this->timelineStorageDirectory] as $directory) {
            if (!is_dir($directory)) {
                mkdir($directory, 0775, true);
            }
        }

        foreach ($this->files as $file) {
            if (!file_exists($file)) {
                file_put_contents($file, json_encode([]), LOCK_EX);
            }
        }
    }

    private function projectTitle(array $project): string
    {
        return trim((string) ($project['title'] ?? 'Projeto')) ?: 'Projeto';
    }

    private function userName(array $user): string
    {
        return trim((string) ($user['fullname'] ?? $user['username'] ?? 'Usuario')) ?: 'Usuario';
    }

    private function nextId(string $prefix): string
    {
        return uniqid($prefix, true);
    }

    private function now(): string
    {
        return date('Y-m-d H:i:s');
    }
}
