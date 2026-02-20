<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}
try {
    $stmt = $pdo->query("
        DELETE FROM user_sessions
        WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ");
    $deleted = $stmt->rowCount();
    echo json_encode(['success' => true, 'deleted' => $deleted]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}