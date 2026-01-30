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
$route_id = $input['route_id'] ?? null;
$new_name = $input['new_name'] ?? null;
if (!$route_id || !$new_name) {
    http_response_code(400);
    echo json_encode(['error' => 'Route ID and new name required']);
    exit;
}
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("
        INSERT INTO routes (name, description, city_id, price, route_type, is_active,
                          difficulty_level, estimated_duration, max_hints_per_route,
                          difficulty, duration_minutes, season, created_at)
        SELECT ?, description, city_id, price, route_type, 0,
               difficulty_level, estimated_duration, max_hints_per_route,
               difficulty, duration_minutes, season, NOW()
        FROM routes WHERE id = ?
    ");
    $stmt->execute([$new_name, $route_id]);
    $new_route_id = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        INSERT INTO route_tags (route_id, tag_id)
        SELECT ?, tag_id FROM route_tags WHERE route_id = ?
    ");
    $stmt->execute([$new_route_id, $route_id]);
    $stmt = $pdo->prepare("
        SELECT * FROM points WHERE route_id = ?
    ");
    $stmt->execute([$route_id]);
    $points = $stmt->fetchAll();
    $point_map = [];
    foreach ($points as $point) {
        $stmt = $pdo->prepare("
            INSERT INTO points (route_id, name, latitude, longitude, `order`, fact_text,
                              audio_text, audio_enabled, is_bonus, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $new_route_id,
            $point['name'],
            $point['latitude'],
            $point['longitude'],
            $point['order'],
            $point['fact_text'],
            $point['audio_text'],
            $point['audio_enabled'],
            $point['is_bonus']
        ]);
        $new_point_id = $pdo->lastInsertId();
        $point_map[$point['id']] = $new_point_id;
    }
    foreach ($point_map as $old_point_id => $new_point_id) {
        $stmt = $pdo->prepare("
            INSERT INTO tasks (point_id, `order`, task_text, task_text_en, task_type, text_answer, text_answer_hint, accept_partial_match, max_attempts, created_at)
            SELECT ?, `order`, task_text, task_text_en, task_type, text_answer, text_answer_hint, accept_partial_match, max_attempts, NOW()
            FROM tasks WHERE point_id = ?
        ");
        $stmt->execute([$new_point_id, $old_point_id]);
    }
    foreach ($point_map as $old_point_id => $new_point_id) {
        $stmt = $pdo->prepare("
            INSERT INTO hints (point_id, text, order_index, created_at)
            SELECT ?, text, order_index, NOW()
            FROM hints WHERE point_id = ?
        ");
        $stmt->execute([$new_point_id, $old_point_id]);
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'new_route_id' => $new_route_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}