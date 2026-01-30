<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$task_id = $input['task_id'] ?? null;
if (!$task_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Task ID required']);
    exit;
}
$admin = getCurrentAdmin();
try {
    $stmt = $pdo->prepare("
        UPDATE moderation_tasks
        SET assigned_to = ?, status = 'in_progress', updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$admin['id'], $task_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}