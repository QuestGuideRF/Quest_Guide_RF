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
$task_orders = $input['tasks'] ?? [];
if (empty($task_orders)) {
    http_response_code(400);
    echo json_encode(['error' => 'No tasks provided']);
    exit;
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE tasks SET `order` = ? WHERE id = ?");
    foreach ($task_orders as $item) {
        $stmt->execute([$item['order'], $item['id']]);
    }
    $pdo->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}