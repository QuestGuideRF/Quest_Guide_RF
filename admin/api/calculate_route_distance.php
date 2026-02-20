<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json; charset=utf-8');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true) ?: [];
$route_id = isset($input['route_id']) ? (int)$input['route_id'] : 0;
$distance_km = isset($input['distance_km']) ? (float)$input['distance_km'] : null;
if (!$route_id || $distance_km === null || $distance_km < 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Не указан маршрут или расстояние']);
    exit;
}
$pdo = getDB()->getConnection();
$stmt = $pdo->prepare("SELECT id, creator_id FROM routes WHERE id = ?");
$stmt->execute([$route_id]);
$route = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$route) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Маршрут не найден']);
    exit;
}
if (function_exists('isModerator') && isModerator() && !empty($route['creator_id']) && (int)$route['creator_id'] !== (int)$_SESSION['admin_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Доступ запрещён']);
    exit;
}
$distance_km = round($distance_km, 2);
$stmt = $pdo->prepare("UPDATE routes SET distance = ? WHERE id = ?");
$stmt->execute([$distance_km, $route_id]);
echo json_encode(['success' => true, 'distance_km' => $distance_km]);