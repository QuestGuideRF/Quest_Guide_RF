<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    header('Location: /403.php?reason=direct_access');
    exit;
}
function e($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}
function formatDate($date, $format = 'd.m.Y') {
    if (empty($date)) return '-';
    return date($format, strtotime($date));
}
function formatDateTime($datetime, $format = 'd.m.Y H:i') {
    if (empty($datetime)) return '-';
    return date($format, strtotime($datetime));
}
function formatDuration($minutes) {
    if ($minutes < 60) {
        return (int)$minutes . ' мин';
    }
    $minutes = (int)$minutes;
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    if ($mins == 0) {
        return $hours . ' ч';
    }
    return $hours . ' ч ' . $mins . ' мин';
}
function formatDistance($km) {
    if ($km < 1) {
        return round($km * 1000) . ' м';
    }
    return number_format($km, 1) . ' км';
}
function formatPrice($amount) {
    return number_format($amount, 0, ',', ' ') . ' ₽';
}
function getUserStats($user_id) {
    $db = getDB();
    $total_routes = $db->fetch(
        'SELECT COUNT(*) as count FROM user_progress WHERE user_id = ?',
        [$user_id]
    )['count'] ?? 0;
    $completed_routes = $db->fetch(
        'SELECT COUNT(*) as count FROM user_progress
         WHERE user_id = ? AND status = "completed"',
        [$user_id]
    )['count'] ?? 0;
    $total_points = $db->fetch(
        'SELECT SUM(points_completed) as total FROM user_progress WHERE user_id = ?',
        [$user_id]
    )['total'] ?? 0;
    $total_time = $db->fetch(
        'SELECT SUM(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as total
         FROM user_progress
         WHERE user_id = ? AND status = "completed"',
        [$user_id]
    )['total'] ?? 0;
    $total_paid = $db->fetch(
        'SELECT SUM(amount) as total FROM payments
         WHERE user_id = ? AND status = "success"',
        [$user_id]
    )['total'] ?? 0;
    $achievements_count = $db->fetch(
        'SELECT COUNT(*) as count FROM user_achievements WHERE user_id = ?',
        [$user_id]
    )['count'] ?? 0;
    return [
        'total_routes' => $total_routes,
        'completed_routes' => $completed_routes,
        'in_progress' => $total_routes - $completed_routes,
        'total_points' => $total_points,
        'total_time' => $total_time,
        'total_paid' => $total_paid,
        'achievements_count' => $achievements_count,
    ];
}
function getRouteProgress($progress_id) {
    $db = getDB();
    $progress = $db->fetch(
        'SELECT up.*, r.name as route_name
         FROM user_progress up
         JOIN routes r ON up.route_id = r.id
         WHERE up.id = ?',
        [$progress_id]
    );
    if (!$progress) return null;
    $total_points = $db->fetch(
        'SELECT COUNT(*) as count FROM points WHERE route_id = ?',
        [$progress['route_id']]
    )['count'];
    $progress['total_points'] = $total_points;
    $progress['progress_percent'] = $total_points > 0
        ? round(($progress['points_completed'] / $total_points) * 100)
        : 0;
    return $progress;
}
function getUserLevel($total_points) {
    $level = 1;
    $points_for_level = 10;
    while ($total_points >= $points_for_level) {
        $level++;
        $total_points -= $points_for_level;
        $points_for_level += 5;
    }
    return [
        'level' => $level,
        'current_points' => $total_points,
        'points_to_next' => $points_for_level - $total_points,
    ];
}
function getUserPhotos($user_id, $route_id = null, $limit = 100) {
    $db = getDB();
    $sql = 'SELECT p.*, pt.name as point_name, r.name as route_name
            FROM user_photos p
            JOIN points pt ON p.point_id = pt.id
            JOIN routes r ON pt.route_id = r.id
            WHERE p.user_id = ?';
    $params = [$user_id];
    if ($route_id) {
        $sql .= ' AND r.id = ?';
        $params[] = $route_id;
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT ?';
    $params[] = $limit;
    return $db->fetchAll($sql, $params);
}
function getDefaultAvatar($name) {
    $initial = mb_substr($name, 0, 1);
    return 'https://ui-avatars.com/api/?name=' . urlencode($initial) . '&background=random';
}
function getStatusBadge($status) {
    $status = strtolower($status);
    $badges = [
        'completed' => '<span class="badge badge-success">Завершен</span>',
        'in_progress' => '<span class="badge badge-primary">В процессе</span>',
        'abandoned' => '<span class="badge badge-secondary">Прерван</span>',
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">Неизвестно</span>';
}
function getPaymentStatusBadge($status) {
    $badges = [
        'success' => '<span class="badge badge-success">Оплачено</span>',
        'pending' => '<span class="badge badge-warning">Ожидание</span>',
        'failed' => '<span class="badge badge-danger">Ошибка</span>',
        'refunded' => '<span class="badge badge-info">Возврат</span>',
    ];
    return $badges[$status] ?? '<span class="badge badge-secondary">Неизвестно</span>';
}