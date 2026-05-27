<?php
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';

$projectManager = new ProjectManager();
$workspaceManager = new ProjectWorkspaceManager($projectManager);

$kind = strtolower(trim((string) ($_GET['kind'] ?? '')));
$id = trim((string) ($_GET['id'] ?? ''));

if ($id === '' || !in_array($kind, ['document', 'timeline'], true)) {
    http_response_code(404);
    echo 'Arquivo nao encontrado.';
    exit();
}

if ($kind === 'document') {
    require_once 'controllers/AuthController.php';

    $auth = new AuthController();
    $auth->requireAuth();
    $currentUser = $auth->getCurrentUser();
    $fileInfo = $workspaceManager->getDocumentFileInfo($id);

    if (!$fileInfo) {
        http_response_code(404);
        echo 'Documento nao encontrado.';
        exit();
    }

    $document = $fileInfo['record'];
    $project = $projectManager->getProject((string) ($document['project_id'] ?? ''));

    if (!is_array($project) || !$workspaceManager->canViewProjectDocument($document, $project, $currentUser)) {
        http_response_code(403);
        echo 'Acesso negado.';
        exit();
    }
} else {
    $fileInfo = $workspaceManager->getTimelineAttachmentFileInfo($id);

    if (!$fileInfo) {
        http_response_code(404);
        echo 'Anexo nao encontrado.';
        exit();
    }
}

$path = (string) ($fileInfo['absolute_path'] ?? '');
if ($path === '' || !is_file($path)) {
    http_response_code(404);
    echo 'Arquivo nao encontrado.';
    exit();
}

$downloadName = basename((string) ($fileInfo['download_name'] ?? 'arquivo'));
$mimeType = (string) ($fileInfo['mime_type'] ?? 'application/octet-stream');

header('Content-Type: ' . $mimeType);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . str_replace('"', '', $downloadName) . '"');
header('X-Content-Type-Options: nosniff');
readfile($path);
exit();
