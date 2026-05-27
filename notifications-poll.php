<?php
require_once 'controllers/AuthController.php';
require_once 'models/Project.php';
require_once 'models/ProjectWorkspace.php';

header('Content-Type: application/json; charset=UTF-8');

$auth = new AuthController();
$currentUser = $auth->getCurrentUser();

if (!$currentUser) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'unread_count' => 0,
    ]);
    exit();
}

$workspaceManager = new ProjectWorkspaceManager(new ProjectManager());
$notifications = $workspaceManager->getUserNotifications((int) ($currentUser['id'] ?? 0), 5);

echo json_encode([
    'success' => true,
    'unread_count' => $workspaceManager->getUnreadNotificationCount((int) ($currentUser['id'] ?? 0)),
    'latest' => array_map(static function (array $notification): array {
        return [
            'id' => (string) ($notification['id'] ?? ''),
            'title' => (string) ($notification['title'] ?? ''),
            'body' => (string) ($notification['body'] ?? ''),
            'target_url' => (string) ($notification['target_url'] ?? ''),
            'read_at' => $notification['read_at'] ?? null,
            'created_at' => (string) ($notification['created_at'] ?? ''),
        ];
    }, $notifications),
]);
