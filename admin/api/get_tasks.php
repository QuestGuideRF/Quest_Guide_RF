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
$point_id = isset($_GET['point_id']) ? (int)$_GET['point_id'] : 0;
if (!$point_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Point ID is required']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT * FROM tasks WHERE point_id = ? ORDER BY `order` ASC");
    $stmt->execute([$point_id]);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'tasks' => $tasks]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}