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
$review_id = $input['review_id'] ?? null;
$action = $input['action'] ?? '';
if (!$review_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No review ID provided']);
    exit;
}
try {
    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE reviews SET is_approved = 1, is_hidden = 0 WHERE id = ?");
            $stmt->execute([$review_id]);
            echo json_encode(['success' => true]);
            break;
        case 'hide':
            $stmt = $pdo->prepare("UPDATE reviews SET is_hidden = 1, is_approved = 0 WHERE id = ?");
            $stmt->execute([$review_id]);
            echo json_encode(['success' => true]);
            break;
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$review_id]);
            echo json_encode(['success' => true]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}