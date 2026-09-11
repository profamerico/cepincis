<?php
require_once __DIR__ . '/../bootstrap.php';
require_once __DIR__ . '/Project.php';

class ProjectWorkspaceManager
{
    private PDO $db;
    private ProjectManager $projectManager;
    private string $documentStorageDirectory;
    private string $timelineStorageDirectory;

    private const DOCUMENT_MAX_BYTES = 10 * 1024 * 1024;
    private const DOCUMENT_EXTENSIONS = ['pdf', 'docx'];
    private const TIMELINE_MAX_BYTES = 10 * 1024 * 1024;

    public function __construct(?ProjectManager $projectManager = null)
    {
        $this->db = db();
        $this->projectManager = $projectManager ?: new ProjectManager();
        $this->documentStorageDirectory = __DIR__ . '/../uploads/projects/documents';
        $this->timelineStorageDirectory = __DIR__ . '/../uploads/projects/timeline';
        $this->ensureStorage();
    }

    public function getTimelineTypeOptions(): array
    {
        return [
            'update' => 'Atualização',
            'milestone' => 'Marco',
            'meeting' => 'Reunião',
            'result' => 'Resultado',
            'publication' => 'Publicação',
        ];
    }

    public function getTimelineTypeLabel(string $type): string
    {
        $type = $this->normalizeTimelineType($type);
        return $this->getTimelineTypeOptions()[$type] ?? 'Atualização';
    }

    public function getAuthenticationLabel(string $status): string
    {
        return [
            'missing' => 'Sem documentação',
            'pending' => 'Em análise',
            'approved' => 'Autenticado',
            'rejected' => 'Rejeitado',
        ][$this->normalizeAuthenticationStatus($status)] ?? 'Sem documentação';
    }

    public function getAuthenticationStatus(array $project): array
    {
        $status = $this->normalizeAuthenticationStatus((string) ($project['authentication_status'] ?? 'missing'));
        return ['status' => $status, 'label' => $this->getAuthenticationLabel($status)];
    }

    public function getProjectDocuments(string $projectId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_documents WHERE project_id = :project_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPendingProjectDocuments(): array
    {
        $stmt = $this->db->query("SELECT * FROM project_documents WHERE status = 'pending' ORDER BY created_at ASC, id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDocument(string $documentId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_documents WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => (int) $documentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getAuthenticationHistory(string $projectId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_authentication_history WHERE project_id = :project_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        $userId = (int) ($user['id'] ?? 0);
        if ($projectId === '' || $userId <= 0) {
            $this->deleteStoredFile((string) ($stored['storage_path'] ?? ''), $this->documentStorageDirectory);
            return ['success' => false, 'errors' => ['Projeto ou usuário inválido.']];
        }

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO project_documents
                (project_id, uploaded_by_user_id, original_name, stored_name, storage_path, mime_type, extension, size_bytes, sha256_hash, status)
                VALUES (:project_id, :user_id, :original_name, :stored_name, :storage_path, :mime_type, :extension, :size_bytes, :sha256_hash, \'pending\')'
            );
            $stmt->execute([
                'project_id' => $projectId,
                'user_id' => $userId,
                'original_name' => $stored['original_name'],
                'stored_name' => $stored['stored_name'],
                'storage_path' => $stored['storage_path'],
                'mime_type' => $validation['mime_type'],
                'extension' => $validation['extension'],
                'size_bytes' => (int) ($file['size'] ?? 0),
                'sha256_hash' => hash_file('sha256', (string) $stored['absolute_path']) ?: null,
            ]);
            $documentId = (int) $this->db->lastInsertId();
            $this->appendAuthenticationHistory($projectId, (string) $documentId, $userId, 'submitted', 'Documento enviado para análise.');
            $this->projectManager->setProjectAuthenticationStatus($projectId, 'pending');
            return ['success' => true, 'document' => $this->getDocument((string) $documentId)];
        } catch (Throwable $e) {
            $this->deleteStoredFile((string) ($stored['storage_path'] ?? ''), $this->documentStorageDirectory);
            return ['success' => false, 'errors' => ['Não foi possível registrar o documento no banco de dados.']];
        }
    }

    public function reviewProjectDocument(string $documentId, array $reviewer, string $decision, string $notes = ''): array
    {
        $decision = $this->normalizeDocumentDecision($decision);
        if ($decision === '') {
            return ['success' => false, 'errors' => ['Decisão inválida para o documento.']];
        }

        $document = $this->getDocument($documentId);
        if (!$document) {
            return ['success' => false, 'errors' => ['Documento não encontrado.']];
        }

        $reviewerId = (int) ($reviewer['id'] ?? 0);
        $projectId = (string) ($document['project_id'] ?? '');

        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare(
                'UPDATE project_documents
                 SET status = :status, review_notes = :notes, reviewed_by_user_id = :reviewer_id, reviewed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'status' => $decision,
                'notes' => trim($notes),
                'reviewer_id' => $reviewerId,
                'id' => (int) $documentId,
            ]);

            $this->appendAuthenticationHistory($projectId, $documentId, $reviewerId, $decision, trim($notes));

            if ($decision === 'approved') {
                $this->projectManager->setProjectAuthenticationStatus($projectId, 'approved', $documentId, date('Y-m-d H:i:s'));
            } else {
                $hasApproved = $this->hasApprovedDocument($projectId);
                $this->projectManager->setProjectAuthenticationStatus($projectId, $hasApproved ? 'approved' : 'rejected');
            }

            $this->db->commit();
            return ['success' => true, 'document' => $this->getDocument($documentId)];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'errors' => ['Não foi possível registrar a avaliação do documento.']];
        }
    }

    public function getProjectCollaborators(string $projectId, bool $includeInactive = false): array
    {
        $sql = 'SELECT * FROM project_collaborators WHERE project_id = :project_id';
        $sql .= ' ORDER BY created_at ASC, id ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getActiveCollaborator(string $projectId, int $userId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_collaborators WHERE project_id = :project_id AND user_id = :user_id LIMIT 1');
        $stmt->execute(['project_id' => $projectId, 'user_id' => $userId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getProjectInvites(string $projectId): array
    {
        $stmt = $this->db->prepare("SELECT * FROM project_collaboration_invites WHERE project_id = :project_id AND status = 'pending' ORDER BY created_at DESC");
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUserInvites(int $userId, string $status = 'pending'): array
    {
        $sql = 'SELECT * FROM project_collaboration_invites WHERE invited_user_id = :user_id';
        $params = ['user_id' => $userId];
        if ($status !== '') {
            $sql .= ' AND status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY created_at DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getInvite(string $inviteId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_collaboration_invites WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $inviteId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function inviteCollaborator(array $project, array $actor, int $invitedUserId, string $role = 'collaborator'): array
    {
        if (!$this->canManageProject($project, $actor)) {
            return ['success' => false, 'errors' => ['Você não tem permissão para convidar colaboradores neste projeto.']];
        }

        $projectId = (string) ($project['id'] ?? '');
        $actorId = (int) ($actor['id'] ?? 0);
        $ownerId = (int) ($project['user_id'] ?? 0);
        $role = $this->normalizeProjectRole($role);

        if ($invitedUserId <= 0) {
            return ['success' => false, 'errors' => ['Selecione um usuário válido.']];
        }
        if ($invitedUserId === $ownerId) {
            return ['success' => false, 'errors' => ['O responsável já possui controle total do projeto.']];
        }
        if ($this->getActiveCollaborator($projectId, $invitedUserId) !== null) {
            return ['success' => false, 'errors' => ['Este usuário já é colaborador do projeto.']];
        }
        if ($this->getUserInvitesForProject($projectId, $invitedUserId) !== null) {
            return ['success' => false, 'errors' => ['Já existe um convite pendente para este usuário.']];
        }

        $inviteId = $this->nextId('inv_');
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO project_collaboration_invites (id, project_id, invited_user_id, invited_by_user_id, role, status)
                 VALUES (:id, :project_id, :invited_user_id, :invited_by_user_id, :role, 'pending')"
            );
            $stmt->execute([
                'id' => $inviteId,
                'project_id' => $projectId,
                'invited_user_id' => $invitedUserId,
                'invited_by_user_id' => $actorId,
                'role' => $role,
            ]);

            $this->createNotification(
                $invitedUserId,
                'collaboration_invite',
                'Convite de colaboração',
                'Você recebeu um convite para colaborar em "' . $this->projectTitle($project) . '".',
                $projectId,
                'project-workspace.php?id=' . rawurlencode($projectId),
                $actorId
            );

            return ['success' => true, 'invite' => $this->getInvite($inviteId)];
        } catch (Throwable $e) {
            return ['success' => false, 'errors' => ['Não foi possível criar o convite. Verifique se já existe um convite pendente.']];
        }
    }

    public function respondToInvitation(string $inviteId, array $user, string $response): array
    {
        $response = strtolower(trim($response));
        if (!in_array($response, ['accepted', 'rejected'], true)) {
            return ['success' => false, 'errors' => ['Resposta inválida para o convite.']];
        }

        $invite = $this->getInvite($inviteId);
        $userId = (int) ($user['id'] ?? 0);
        if (!$invite) {
            return ['success' => false, 'errors' => ['Convite não encontrado.']];
        }
        if ((int) $invite['invited_user_id'] !== $userId) {
            return ['success' => false, 'errors' => ['Este convite pertence a outro usuário.']];
        }
        if ((string) $invite['status'] !== 'pending') {
            return ['success' => false, 'errors' => ['Este convite já foi respondido.']];
        }

        $project = $this->projectManager->getProject((string) $invite['project_id']);
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->prepare("UPDATE project_collaboration_invites SET status = :status, responded_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND status = 'pending'");
            $stmt->execute(['status' => $response, 'id' => $inviteId]);

            if ($response === 'accepted' && is_array($project)) {
                $this->addCollaboratorRecord($project, $userId, (string) $invite['role']);
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'errors' => ['Não foi possível registrar a resposta ao convite.']];
        }

        if (is_array($project)) {
            if ($response === 'accepted') {
                $this->notifyProjectManagers($project, 'collaboration_accepted', 'Convite aceito', $this->userName($user) . ' aceitou colaborar em "' . $this->projectTitle($project) . '".', $userId);
                $this->notifyProjectParticipants($project, 'collaborator_added', 'Novo colaborador', $this->userName($user) . ' entrou no projeto "' . $this->projectTitle($project) . '".', $userId, $userId);
            } else {
                $this->notifyProjectManagers($project, 'collaboration_rejected', 'Convite recusado', $this->userName($user) . ' recusou o convite para "' . $this->projectTitle($project) . '".', $userId);
            }
        }

        return ['success' => true, 'invite' => $this->getInvite($inviteId)];
    }

    public function removeCollaborator(array $project, array $actor, int $userId): array
    {
        if (!$this->canManageProject($project, $actor)) {
            return ['success' => false, 'errors' => ['Você não tem permissão para remover colaboradores neste projeto.']];
        }
        if ($userId <= 0 || $userId === (int) ($project['user_id'] ?? 0)) {
            return ['success' => false, 'errors' => ['O responsável não pode ser removido do projeto.']];
        }

        $stmt = $this->db->prepare('DELETE FROM project_collaborators WHERE project_id = :project_id AND user_id = :user_id');
        $stmt->execute(['project_id' => (string) $project['id'], 'user_id' => $userId]);
        if ($stmt->rowCount() < 1) {
            return ['success' => false, 'errors' => ['Colaborador não encontrado.']];
        }

        $this->createNotification(
            $userId,
            'collaborator_removed',
            'Você saiu do projeto',
            'Seu acesso ao workspace de "' . $this->projectTitle($project) . '" foi removido.',
            (string) $project['id'],
            'project-workspace.php?id=' . rawurlencode((string) $project['id']),
            (int) ($actor['id'] ?? 0)
        );
        return ['success' => true];
    }

    public function canManageProject(array $project, ?array $user): bool
    {
        if (!$user) return false;
        if ($this->isAdmin($user)) return true;
        $uid = (int) ($user['id'] ?? 0);
        if ($uid === (int) ($project['user_id'] ?? $project['owner_user_id'] ?? 0)) return true;
        $collaborator = $this->getActiveCollaborator((string) ($project['id'] ?? ''), $uid);
        return is_array($collaborator) && (string) ($collaborator['role'] ?? '') === 'project_admin';
    }

    public function canEditTimeline(array $project, ?array $user): bool
    {
        return $this->canViewWorkspace($project, $user);
    }

    public function canViewWorkspace(array $project, ?array $user): bool
    {
        if (!$user) return false;
        if ($this->isAdmin($user)) return true;
        $uid = (int) ($user['id'] ?? 0);
        if ($uid === (int) ($project['user_id'] ?? $project['owner_user_id'] ?? 0)) return true;
        return $this->getActiveCollaborator((string) ($project['id'] ?? ''), $uid) !== null;
    }

    public function canEditTimelineEvent(array $project, array $event, ?array $user): bool
    {
        if (!$this->canEditTimeline($project, $user)) return false;
        if ($this->isAdmin($user)) return true;
        $uid = (int) ($user['id'] ?? 0);
        return $uid === (int) ($event['author_user_id'] ?? 0)
            || $uid === (int) ($project['user_id'] ?? 0)
            || ($this->getActiveCollaborator((string) $project['id'], $uid)['role'] ?? '') === 'project_admin';
    }

    public function getProjectTimeline(string $projectId, bool $includeDeleted = false): array
    {
        $sql = 'SELECT * FROM project_timeline_events WHERE project_id = :project_id';
        if (!$includeDeleted) $sql .= ' AND deleted_at IS NULL';
        $sql .= ' ORDER BY event_date DESC, created_at DESC, id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['project_id' => $projectId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTimelineEvent(string $eventId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_timeline_events WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $eventId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function addTimelineEvent(array $project, array $user, array $data, ?array $file = null): array
    {
        if (!$this->canEditTimeline($project, $user)) {
            return ['success' => false, 'errors' => ['Você não pode adicionar eventos neste projeto.']];
        }
        $normalized = $this->normalizeTimelinePayload($data);
        if (!empty($normalized['errors'])) return ['success' => false, 'errors' => $normalized['errors']];

        $attachment = $this->storeTimelineAttachmentIfPresent($file);
        if (!$attachment['success']) return ['success' => false, 'errors' => [$attachment['error']]];

        $eventId = $this->nextId('tl_');
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO project_timeline_events
                (id, project_id, title, description, event_date, event_type, author_user_id, attachment_original_name, attachment_stored_name, attachment_path, attachment_mime_type, attachment_size_bytes)
                VALUES (:id, :project_id, :title, :description, :event_date, :event_type, :author_user_id, :original_name, :stored_name, :path, :mime, :size)'
            );
            $stmt->execute([
                'id' => $eventId,
                'project_id' => (string) $project['id'],
                'title' => $normalized['title'],
                'description' => $normalized['description'],
                'event_date' => $normalized['event_date'],
                'event_type' => $normalized['event_type'],
                'author_user_id' => (int) $user['id'],
                'original_name' => $attachment['original_name'] ?? null,
                'stored_name' => $attachment['stored_name'] ?? null,
                'path' => $attachment['storage_path'] ?? null,
                'mime' => $attachment['mime_type'] ?? null,
                'size' => $attachment['size_bytes'] ?? null,
            ]);
            $event = $this->getTimelineEvent($eventId);
            $this->appendTimelineHistory($eventId, (string) $project['id'], (int) $user['id'], 'created', null, $event);
            $this->notifyProjectParticipants($project, 'timeline_updated', 'Timeline atualizada', $this->userName($user) . ' adicionou um evento em "' . $this->projectTitle($project) . '".', (int) $user['id'], (int) $user['id']);
            return ['success' => true, 'event' => $event];
        } catch (Throwable $e) {
            if (!empty($attachment['storage_path'])) $this->deleteStoredFile((string) $attachment['storage_path'], $this->timelineStorageDirectory);
            return ['success' => false, 'errors' => ['Não foi possível salvar o evento no banco de dados.']];
        }
    }

    public function updateTimelineEvent(array $project, string $eventId, array $user, array $data, ?array $file = null): array
    {
        $event = $this->getTimelineEvent($eventId);
        if (!$event) return ['success' => false, 'errors' => ['Evento não encontrado.']];
        if (!$this->canEditTimelineEvent($project, $event, $user)) return ['success' => false, 'errors' => ['Você não pode editar este evento.']];

        $normalized = $this->normalizeTimelinePayload($data);
        if (!empty($normalized['errors'])) return ['success' => false, 'errors' => $normalized['errors']];
        $attachment = $this->storeTimelineAttachmentIfPresent($file);
        if (!$attachment['success']) return ['success' => false, 'errors' => [$attachment['error']]];

        $oldPath = (string) ($event['attachment_path'] ?? '');
        try {
            $sql = 'UPDATE project_timeline_events SET title=:title,description=:description,event_date=:event_date,event_type=:event_type,updated_at=CURRENT_TIMESTAMP';
            $params = [
                'title' => $normalized['title'], 'description' => $normalized['description'], 'event_date' => $normalized['event_date'], 'event_type' => $normalized['event_type'], 'id' => $eventId,
            ];
            if (!empty($attachment['has_file'])) {
                $sql .= ',attachment_original_name=:original_name,attachment_stored_name=:stored_name,attachment_path=:path,attachment_mime_type=:mime,attachment_size_bytes=:size';
                $params += ['original_name'=>$attachment['original_name'],'stored_name'=>$attachment['stored_name'],'path'=>$attachment['storage_path'],'mime'=>$attachment['mime_type'],'size'=>$attachment['size_bytes']];
            }
            $sql .= ' WHERE id=:id';
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $updated = $this->getTimelineEvent($eventId);
            $this->appendTimelineHistory($eventId, (string) $project['id'], (int) $user['id'], 'updated', $event, $updated);
            if (!empty($attachment['has_file']) && $oldPath !== '') $this->deleteStoredFile($oldPath, $this->timelineStorageDirectory);
            $this->notifyProjectParticipants($project, 'timeline_updated', 'Timeline atualizada', $this->userName($user) . ' atualizou um evento em "' . $this->projectTitle($project) . '".', (int) $user['id'], (int) $user['id']);
            return ['success' => true, 'event' => $updated];
        } catch (Throwable $e) {
            if (!empty($attachment['has_file'])) $this->deleteStoredFile((string) ($attachment['storage_path'] ?? ''), $this->timelineStorageDirectory);
            return ['success' => false, 'errors' => ['Não foi possível atualizar o evento.']];
        }
    }

    public function deleteTimelineEvent(array $project, string $eventId, array $user): array
    {
        $event = $this->getTimelineEvent($eventId);
        if (!$event) return ['success' => false, 'errors' => ['Evento não encontrado.']];
        if (!$this->canEditTimelineEvent($project, $event, $user)) return ['success' => false, 'errors' => ['Você não pode remover este evento.']];
        try {
            $stmt = $this->db->prepare('UPDATE project_timeline_events SET deleted_at=CURRENT_TIMESTAMP,updated_at=CURRENT_TIMESTAMP WHERE id=:id AND deleted_at IS NULL');
            $stmt->execute(['id' => $eventId]);
            $this->appendTimelineHistory($eventId, (string) $project['id'], (int) $user['id'], 'deleted', $event, null);
            $this->notifyProjectParticipants($project, 'timeline_updated', 'Timeline atualizada', $this->userName($user) . ' removeu um evento de "' . $this->projectTitle($project) . '".', (int) $user['id'], (int) $user['id']);
            return $stmt->rowCount() > 0 ? ['success' => true] : ['success' => false, 'errors' => ['O evento já foi removido.']];
        } catch (Throwable $e) {
            return ['success' => false, 'errors' => ['Não foi possível remover o evento.']];
        }
    }

    public function getTimelineHistory(string $eventId): array
    {
        $stmt = $this->db->prepare('SELECT * FROM project_timeline_history WHERE event_id=:event_id ORDER BY created_at DESC, id DESC');
        $stmt->execute(['event_id' => $eventId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessibleProjectsForUser(array $projects, array $user): array
    {
        $accessible = [];
        foreach ($projects as $project) {
            if ($this->canViewWorkspace($project, $user)) $accessible[] = $project;
        }
        return $accessible;
    }

    public function createNotification(int $userId, string $type, string $title, string $body, ?string $projectId = null, ?string $targetUrl = null, ?int $actorUserId = null): ?array
    {
        if ($userId <= 0) return null;
        try {
            $id = $this->nextId('ntf_');
            $stmt = $this->db->prepare(
                'INSERT INTO notifications (id,user_id,type,title,body,project_id,target_url,actor_user_id,read_at)
                 VALUES (:id,:user_id,:type,:title,:body,:project_id,:target_url,:actor_user_id,NULL)'
            );
            $stmt->execute([
                'id'=>$id,'user_id'=>$userId,'type'=>trim($type) ?: 'general','title'=>trim($title) ?: 'Notificação','body'=>trim($body),'project_id'=>$projectId,'target_url'=>$targetUrl,'actor_user_id'=>$actorUserId,
            ]);
            return $this->getNotification($id);
        } catch (Throwable $e) {
            return null;
        }
    }

    public function notifyAdministrators(array $users, string $type, string $title, string $body, ?string $projectId = null, ?string $targetUrl = null, ?int $actorUserId = null): int
    {
        $count = 0;
        foreach ($users as $user) {
            if (!$this->isAdmin($user)) continue;
            $targetUserId = (int) ($user['id'] ?? 0);
            if ($actorUserId !== null && $targetUserId === $actorUserId) continue;
            if ($this->createNotification($targetUserId, $type, $title, $body, $projectId, $targetUrl, $actorUserId)) $count++;
        }
        return $count;
    }

    public function notifyProjectParticipants(array $project, string $type, string $title, string $body, ?int $actorUserId = null, ?int $excludeUserId = null): int
    {
        $count = 0;
        foreach ($this->getProjectParticipantIds($project) as $userId) {
            if ($excludeUserId !== null && $userId === $excludeUserId) continue;
            if ($this->createNotification($userId, $type, $title, $body, (string) $project['id'], 'project-workspace.php?id=' . rawurlencode((string) $project['id']), $actorUserId)) $count++;
        }
        return $count;
    }

    public function notifyProjectManagers(array $project, string $type, string $title, string $body, ?int $actorUserId = null): int
    {
        $ids = [];
        $ownerId = (int) ($project['user_id'] ?? 0);
        if ($ownerId > 0) $ids[$ownerId] = true;
        foreach ($this->getProjectCollaborators((string) $project['id']) as $collaborator) {
            if ((string) ($collaborator['role'] ?? '') === 'project_admin') $ids[(int) $collaborator['user_id']] = true;
        }
        $count = 0;
        foreach (array_keys($ids) as $userId) {
            if ($this->createNotification((int) $userId, $type, $title, $body, (string) $project['id'], 'project-workspace.php?id=' . rawurlencode((string) $project['id']), $actorUserId)) $count++;
        }
        return $count;
    }

    public function getUserNotifications(int $userId, int $limit = 80): array
    {
        $limit = max(1, min(100, $limit));
        $stmt = $this->db->prepare("SELECT * FROM notifications WHERE user_id=:user_id ORDER BY created_at DESC, id DESC LIMIT {$limit}");
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUnreadNotificationCount(int $userId): int
    {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM notifications WHERE user_id=:user_id AND read_at IS NULL');
        $stmt->execute(['user_id' => $userId]);
        return (int) $stmt->fetchColumn();
    }

    public function markNotificationRead(string $notificationId, int $userId): bool
    {
        $stmt = $this->db->prepare('UPDATE notifications SET read_at=COALESCE(read_at,CURRENT_TIMESTAMP) WHERE id=:id AND user_id=:user_id');
        $stmt->execute(['id'=>$notificationId,'user_id'=>$userId]);
        return $stmt->rowCount() > 0;
    }

    public function markAllNotificationsRead(int $userId): int
    {
        $stmt = $this->db->prepare('UPDATE notifications SET read_at=CURRENT_TIMESTAMP WHERE user_id=:user_id AND read_at IS NULL');
        $stmt->execute(['user_id'=>$userId]);
        return $stmt->rowCount();
    }

    public function canViewProjectDocument(array $document, array $project, ?array $user): bool
    {
        return $this->canViewWorkspace($project, $user);
    }

    public function getDocumentFileInfo(string $documentId): ?array
    {
        $document = $this->getDocument($documentId);
        if (!$document) return null;
        return $this->resolveFileInfo($document['storage_path'] ?? '', $document['original_name'] ?? 'documento', $this->documentStorageDirectory);
    }

    public function getTimelineAttachmentFileInfo(string $eventId): ?array
    {
        $event = $this->getTimelineEvent($eventId);
        if (!$event || empty($event['attachment_path'])) return null;
        return $this->resolveFileInfo($event['attachment_path'], $event['attachment_original_name'] ?? 'anexo', $this->timelineStorageDirectory);
    }

    public function deleteProjectData(string $projectId): void
    {
        foreach ($this->getProjectDocuments($projectId) as $document) {
            $this->deleteStoredFile((string) ($document['storage_path'] ?? ''), $this->documentStorageDirectory);
        }
        foreach ($this->getProjectTimeline($projectId, true) as $event) {
            $this->deleteStoredFile((string) ($event['attachment_path'] ?? ''), $this->timelineStorageDirectory);
        }
        foreach (['project_authentication_history','project_timeline_history','project_timeline_events','project_collaboration_invites','project_collaborators','project_documents','notifications'] as $table) {
            try {
                $stmt = $this->db->prepare("DELETE FROM {$table} WHERE project_id=:project_id");
                $stmt->execute(['project_id'=>$projectId]);
            } catch (Throwable $e) {
                // A project deletion should not fail only because a secondary table is absent.
            }
        }
    }

    private function addCollaboratorRecord(array $project, int $userId, string $role): void
    {
        $stmt = $this->db->prepare("INSERT INTO project_collaborators (project_id,user_id,role) VALUES (:project_id,:user_id,:role) ON DUPLICATE KEY UPDATE role=VALUES(role),updated_at=CURRENT_TIMESTAMP");
        $stmt->execute(['project_id'=>(string)$project['id'],'user_id'=>$userId,'role'=>$this->normalizeProjectRole($role)]);
    }

    private function getUserInvitesForProject(string $projectId, int $userId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM project_collaboration_invites WHERE project_id=:project_id AND invited_user_id=:user_id AND status='pending' LIMIT 1");
        $stmt->execute(['project_id'=>$projectId,'user_id'=>$userId]);
        $row=$stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function hasApprovedDocument(string $projectId): bool
    {
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM project_documents WHERE project_id=:project_id AND status='approved'");
        $stmt->execute(['project_id'=>$projectId]);
        return (int)$stmt->fetchColumn()>0;
    }

    private function appendAuthenticationHistory(string $projectId,string $documentId,int $actorUserId,string $action,string $notes=''):void
    {
        $stmt=$this->db->prepare('INSERT INTO project_authentication_history (project_id,document_id,actor_user_id,action,notes) VALUES (:project_id,:document_id,:actor_user_id,:action,:notes)');
        $stmt->execute(['project_id'=>$projectId,'document_id'=>$documentId,'actor_user_id'=>$actorUserId,'action'=>$action,'notes'=>$notes]);
    }

    private function appendTimelineHistory(string $eventId,string $projectId,int $actorUserId,string $action,?array $before,?array $after):void
    {
        $stmt=$this->db->prepare('INSERT INTO project_timeline_history (id,event_id,project_id,actor_user_id,action,before_payload,after_payload) VALUES (:id,:event_id,:project_id,:actor_user_id,:action,:before_payload,:after_payload)');
        $stmt->execute([
            'id'=>$this->nextId('tlh_'),'event_id'=>$eventId,'project_id'=>$projectId,'actor_user_id'=>$actorUserId,'action'=>$action,
            'before_payload'=>$before?json_encode($before,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
            'after_payload'=>$after?json_encode($after,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES):null,
        ]);
    }

    private function normalizeTimelinePayload(array $data): array
    {
        $title=trim((string)($data['title']??''));$description=trim((string)($data['description']??''));$date=trim((string)($data['event_date']??date('Y-m-d')));$type=$this->normalizeTimelineType((string)($data['event_type']??'update'));$errors=[];
        if($title==='')$errors[]='Título é obrigatório.';if($description==='')$errors[]='Descrição é obrigatória.';if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date))$errors[]='Data inválida.';
        return ['title'=>$title,'description'=>$description,'event_date'=>$date,'event_type'=>$type,'errors'=>$errors];
    }

    private function storeTimelineAttachmentIfPresent(?array $file): array
    {
        if (!$this->hasUploadedFile($file)) return ['success'=>true,'has_file'=>false];
        if ((int)($file['error']??-1)!==UPLOAD_ERR_OK) return ['success'=>false,'error'=>'Não foi possível processar o anexo.'];
        if ((int)($file['size']??0)>self::TIMELINE_MAX_BYTES) return ['success'=>false,'error'=>'O anexo deve ter no máximo 10 MB.'];
        $original=$this->sanitizeFilename((string)($file['name']??'anexo'));$ext=strtolower(pathinfo($original,PATHINFO_EXTENSION));
        $allowed=['pdf','doc','docx','png','jpg','jpeg','webp','txt'];
        if(!in_array($ext,$allowed,true))return ['success'=>false,'error'=>'Formato de anexo não suportado.'];
        return $this->storeUploadedFile($file,$this->timelineStorageDirectory,'timeline_', $ext);
    }

    private function validateFileUpload(?array $file,array $allowedExtensions,int $maxBytes,bool $strictDocument):array
    {
        if(!$this->hasUploadedFile($file))return ['success'=>false,'error'=>'Selecione um arquivo.'];
        if((int)($file['error']??-1)!==UPLOAD_ERR_OK)return ['success'=>false,'error'=>'Não foi possível processar o arquivo enviado.'];
        if((int)($file['size']??0)>$maxBytes)return ['success'=>false,'error'=>'O arquivo deve ter no máximo '.($maxBytes/(1024*1024)).' MB.'];
        $tmp=(string)($file['tmp_name']??'');if($tmp===''||!is_uploaded_file($tmp))return ['success'=>false,'error'=>'O upload não foi reconhecido pelo servidor.'];
        $name=$this->sanitizeFilename((string)($file['name']??''));$ext=strtolower(pathinfo($name,PATHINFO_EXTENSION));
        if(!in_array($ext,$allowedExtensions,true))return ['success'=>false,'error'=>'Formato não suportado. Envie PDF ou DOCX.'];
        $mime=$this->detectMimeType($tmp);
        if($strictDocument&&!$this->validateFileSignature($tmp,$ext,$mime))return ['success'=>false,'error'=>'O arquivo não corresponde a um PDF ou DOCX válido.'];
        return ['success'=>true,'extension'=>$ext,'mime_type'=>$this->documentMime($ext,$mime)];
    }

    private function validateFileSignature(string $tmpName,string $extension,string $mimeType):bool
    {
        if($extension==='pdf'){
            $h=@fopen($tmpName,'rb');$prefix=$h?fread($h,5):'';if($h)fclose($h);return $prefix==='%PDF-';
        }
        if($extension==='docx'){
            if(!class_exists('ZipArchive'))return str_contains($mimeType,'wordprocessingml.document')||$mimeType==='application/zip';
            $zip=new ZipArchive();if($zip->open($tmpName)!==true)return false; $valid=$zip->locateName('[Content_Types].xml')!==false&&$zip->locateName('word/document.xml')!==false;$zip->close();return $valid;
        }
        return false;
    }

    private function documentMime(string $extension,string $detected):string
    {
        if($extension==='pdf')return 'application/pdf';if($extension==='docx')return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';return $detected?:'application/octet-stream';
    }

    private function storeUploadedFile(array $file,string $directory,string $prefix,string $extension):array
    {
        $tmp=(string)($file['tmp_name']??'');$safeExtension=preg_replace('/[^a-z0-9]+/i','',$extension)?:'bin';$stored=$prefix.bin2hex(random_bytes(10)).'.'.$safeExtension;$absolute=$directory.DIRECTORY_SEPARATOR.$stored;
        if(!move_uploaded_file($tmp,$absolute))return ['success'=>false,'error'=>'Não foi possível salvar o arquivo.'];
        $relative='./uploads/projects/'.($directory===$this->documentStorageDirectory?'documents':'timeline').'/'.$stored;
        return ['success'=>true,'original_name'=>$this->sanitizeFilename((string)($file['name']??$stored)),'stored_name'=>$stored,'storage_path'=>$relative,'absolute_path'=>$absolute,'mime_type'=>$this->detectMimeType($absolute),'extension'=>$safeExtension,'size_bytes'=>(int)($file['size']??0),'has_file'=>true];
    }

    private function getProjectParticipantIds(array $project):array
    {
        $ids=[];$owner=(int)($project['user_id']??$project['owner_user_id']??0);if($owner>0)$ids[$owner]=true;
        foreach($this->getProjectCollaborators((string)$project['id']) as $c){$id=(int)($c['user_id']??0);if($id>0)$ids[$id]=true;}
        return array_map('intval',array_keys($ids));
    }

    private function getNotification(string $id):?array
    {
        $stmt=$this->db->prepare('SELECT * FROM notifications WHERE id=:id LIMIT 1');$stmt->execute(['id'=>$id]);$row=$stmt->fetch(PDO::FETCH_ASSOC);return $row?:null;
    }

    private function normalizeDocumentDecision(string $decision):string{return in_array($decision,['approved','rejected'],true)?$decision:'';}
    private function normalizeProjectRole(string $role):string{return in_array($role,['collaborator','project_admin'],true)?$role:'collaborator';}
    private function normalizeTimelineType(string $type):string{return isset($this->getTimelineTypeOptions()[$type])?$type:'update';}
    private function normalizeAuthenticationStatus(string $status):string{return in_array($status,['missing','pending','approved','rejected'],true)?$status:'missing';}
    private function hasUploadedFile(?array $file):bool{return is_array($file)&&isset($file['error'])&&(int)$file['error']!==UPLOAD_ERR_NO_FILE;}
    private function detectMimeType(string $path):string{if($path!==''&&function_exists('finfo_open')){$f=finfo_open(FILEINFO_MIME_TYPE);if($f){$mime=(string)finfo_file($f,$path);finfo_close($f);return $mime;}}return function_exists('mime_content_type')?(string)@mime_content_type($path):'';}
    private function sanitizeFilename(string $filename):string{$filename=basename($filename);$filename=preg_replace('/[^A-Za-z0-9._ -]+/u','',$filename);return trim($filename)!==''?trim($filename):'arquivo';}
    private function resolveFileInfo(string $storagePath,string $originalName,string $baseDirectory):?array{$safe=$this->resolveStoragePath($storagePath,$baseDirectory);if(!$safe||!is_file($safe))return null;return ['absolute_path'=>$safe,'original_name'=>$this->sanitizeFilename($originalName),'mime_type'=>$this->detectMimeType($safe)?:'application/octet-stream'];}
    private function resolveStoragePath(string $storagePath,string $baseDirectory):?string{if(!str_starts_with($storagePath,'./uploads/projects/'))return null;$relative=substr($storagePath,strlen('./uploads/projects/'));$base=realpath($baseDirectory);$candidate=realpath(__DIR__.'/../uploads/projects/'.$relative);if(!$base||!$candidate)return null;$prefix=rtrim($base,DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;return str_starts_with($candidate,$prefix)?$candidate:null;}
    private function deleteStoredFile(string $storagePath,string $baseDirectory):void{$safe=$this->resolveStoragePath($storagePath,$baseDirectory);if($safe&&is_file($safe))@unlink($safe);}
    private function ensureStorage():void{foreach([$this->documentStorageDirectory,$this->timelineStorageDirectory] as $dir){if(!is_dir($dir))@mkdir($dir,0775,true);}}
    private function projectTitle(array $project):string{return trim((string)($project['title']??'Projeto'))?:'Projeto';}
    private function userName(array $user):string{return trim((string)($user['fullname']??$user['username']??'Usuário'))?:'Usuário';}
    private function isAdmin(?array $user):bool{if(!$user)return false;$role=strtolower(trim((string)($user['role']??'')));return $role==='admin'||(int)($user['role_rank']??0)>=100||(int)($user['id']??0)===1;}
    private function nextId(string $prefix):string{return $prefix.bin2hex(random_bytes(10));}
}
