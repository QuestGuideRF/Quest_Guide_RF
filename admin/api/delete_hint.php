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
$hint_id = $input['hint_id'] ?? null;
if (!$hint_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No hint ID provided']);
    exit;
}
try {
    $stmt = $pdo->prepare("SELECT map_image_path FROM hints WHERE id = ?");
    $stmt->execute([$hint_id]);
    $hint = $stmt->fetch();
    $stmt = $pdo->prepare("DELETE FROM hints WHERE id = ?");
    $stmt->execute([$hint_id]);
    if ($hint && $hint['map_image_path']) {
        $file_path = __DIR__ . '/../..' . $hint['map_image_path'];
        if (file_exists($file_path)) {
            unlink($file_path);
        }
    }
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}