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
$tag_id = $input['tag_id'] ?? null;
if (!$tag_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No tag ID provided']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM route_tags WHERE tag_id = ?");
    $stmt->execute([$tag_id]);
    $count = $stmt->fetch()['cnt'];
    if ($count > 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Тег используется в ' . $count . ' маршрутах']);
        exit;
    }
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$tag_id]);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}