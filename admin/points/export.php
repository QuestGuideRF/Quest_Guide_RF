<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
if (!isAdminLoggedIn()) {
    http_response_code(403);
    exit;
}
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$where = $route_id ? "WHERE p.route_id = $route_id" : "";
$stmt = $pdo->prepare("
    SELECT p.name, p.latitude, p.longitude, p.order,
           (SELECT t.task_text FROM tasks t WHERE t.point_id = p.id ORDER BY t.`order` ASC LIMIT 1) AS task_text,
           p.fact_text, p.audio_text, p.audio_enabled
    FROM points p
    $where
    ORDER BY p.route_id, p.order
");
$stmt->execute();
$points = $stmt->fetchAll(PDO::FETCH_ASSOC);
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="points_' . date('Y-m-d') . '.csv"');
$output = fopen('php://output', 'w');
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
fputcsv($output, ['Название', 'Широта', 'Долгота', 'Порядок', 'Задание', 'Факт', 'Аудио текст', 'Аудио включено']);
foreach ($points as $point) {
    fputcsv($output, [
        $point['name'],
        $point['latitude'],
        $point['longitude'],
        $point['order'],
        $point['task_text'] ?? '',
        $point['fact_text'] ?? '',
        $point['audio_text'] ?? '',
        $point['audio_enabled'] ? '1' : '0'
    ]);
}
fclose($output);
exit;