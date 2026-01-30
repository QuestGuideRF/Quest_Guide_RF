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
$point_id = $input['point_id'] ?? null;
if (!$point_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No point ID provided']);
    exit;
}
try {
    $stmt = $pdo->prepare("DELETE FROM points WHERE id = ?");
    $stmt->execute([$point_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}