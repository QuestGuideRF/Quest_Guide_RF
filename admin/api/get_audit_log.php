<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit_log.php';
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$log_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
if (!$log_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Log ID required']);
    exit;
}
$pdo = getDB()->getConnection();
try {
    $stmt = $pdo->prepare("
        SELECT al.*, u.first_name, u.username
        FROM audit_log al
        LEFT JOIN users u ON al.user_id = u.id
        WHERE al.id = ?
    ");
    $stmt->execute([$log_id]);
    $log = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$log) {
        http_response_code(404);
        echo json_encode(['error' => 'Log not found']);
        exit;
    }
    echo json_encode(['success' => true, 'log' => $log]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}