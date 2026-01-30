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
$hint_ids = $input['hint_ids'] ?? [];
$hint_id = $input['hint_id'] ?? null;
$target_point_id = $input['target_point_id'] ?? null;
if (!$target_point_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Target point ID required']);
    exit;
}
if ($hint_id) {
    $hint_ids = [$hint_id];
}
if (empty($hint_ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'No hints to copy']);
    exit;
}
try {
    $pdo->beginTransaction();
    foreach ($hint_ids as $hint_id) {
        $stmt = $pdo->prepare("SELECT * FROM hints WHERE id = ?");
        $stmt->execute([$hint_id]);
        $hint = $stmt->fetch();
        if (!$hint) continue;
        $stmt = $pdo->prepare("
            INSERT INTO hints (point_id, text, level, order_index, has_map, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $target_point_id,
            $hint['text'],
            $hint['level'],
            $hint['order_index'],
            $hint['has_map']
        ]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'copied' => count($hint_ids)]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}