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
$route_ids = $input['route_ids'] ?? [];
$tag_ids = $input['tag_ids'] ?? [];
$action = $input['action'] ?? 'assign';
if (empty($route_ids) || empty($tag_ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'Route IDs and Tag IDs required']);
    exit;
}
try {
    $pdo->beginTransaction();
    $affected = 0;
    foreach ($route_ids as $route_id) {
        foreach ($tag_ids as $tag_id) {
            if ($action === 'assign') {
                $stmt = $pdo->prepare("SELECT id FROM route_tags WHERE route_id = ? AND tag_id = ?");
                $stmt->execute([$route_id, $tag_id]);
                if (!$stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT INTO route_tags (route_id, tag_id) VALUES (?, ?)");
                    $stmt->execute([$route_id, $tag_id]);
                    $affected++;
                }
            } else {
                $stmt = $pdo->prepare("DELETE FROM route_tags WHERE route_id = ? AND tag_id = ?");
                $stmt->execute([$route_id, $tag_id]);
                $affected += $stmt->rowCount();
            }
        }
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'affected' => $affected]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}