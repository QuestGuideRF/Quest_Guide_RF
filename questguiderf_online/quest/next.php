<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$progress_id = (int)($_GET['progress_id'] ?? 0);
if (!$progress_id) {
    header('Location: /dashboard.php');
    exit;
}
$progress = getDB()->fetch('SELECT * FROM user_progress WHERE id = ? AND user_id = ?', [$progress_id, $user['id']]);
if (!$progress) {
    header('Location: /dashboard.php');
    exit;
}
$point_id = $progress['current_point_id'];
$point_order = (int)$progress['current_point_order'];
$route_id = $progress['route_id'];
$points_completed = (int)$progress['points_completed'];
$total_points = getDB()->fetch('SELECT COUNT(*) as c FROM points WHERE route_id = ?', [$route_id])['c'];
$points_completed_new = $points_completed + 1;
$next_point = getDB()->fetch(
    'SELECT id, `order` FROM points WHERE route_id = ? AND `order` > ? ORDER BY `order` LIMIT 1',
    [$route_id, $point_order]
);
if ($next_point) {
    getDB()->query(
        'UPDATE user_progress SET current_point_id = ?, current_point_order = ?, points_completed = ?, updated_at = NOW() WHERE id = ?',
        [$next_point['id'], $next_point['order'], $points_completed_new, $progress_id]
    );
    header('Location: /quest/point.php?progress_id=' . $progress_id);
} else {
    getDB()->query(
        'UPDATE user_progress SET status = "COMPLETED", completed_at = NOW(), points_completed = ?, updated_at = NOW() WHERE id = ?',
        [$points_completed_new, $progress_id]
    );
    header('Location: /quest/complete.php?progress_id=' . $progress_id);
}
exit;