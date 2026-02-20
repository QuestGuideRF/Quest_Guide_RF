<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
function e($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}
function formatDuration($minutes) {
    $min = (int)$minutes;
    if ($min < 60) return $min . ' мин';
    $h = floor($min / 60);
    $m = $min % 60;
    return $m ? "{$h} ч {$m} мин" : "{$h} ч";
}
function formatDistance($km) {
    if ($km < 1) return round($km * 1000) . ' м';
    return number_format($km, 1) . ' км';
}
function formatPrice($amount, $lang = 'ru') {
    $suffix = $lang === 'en' ? ' tokens' : ' грошей';
    return number_format($amount, 0, ',', ' ') . $suffix;
}
function getUserStats($user_id) {
    $db = getDB();
    $completed = $db->fetch(
        'SELECT COUNT(*) as c FROM user_progress WHERE user_id = ? AND status IN ("completed","COMPLETED")',
        [$user_id]
    )['c'] ?? 0;
    $total_points = $db->fetch(
        'SELECT COALESCE(SUM(points_completed),0) as t FROM user_progress WHERE user_id = ?',
        [$user_id]
    )['t'] ?? 0;
    $total_time = $db->fetch(
        'SELECT COALESCE(SUM(TIMESTAMPDIFF(MINUTE, started_at, completed_at)),0) as t
         FROM user_progress WHERE user_id = ? AND status IN ("completed","COMPLETED") AND completed_at IS NOT NULL',
        [$user_id]
    )['t'] ?? 0;
    $achievements = $db->fetch(
        'SELECT COUNT(*) as c FROM user_achievements WHERE user_id = ?',
        [$user_id]
    )['c'] ?? 0;
    return [
        'completed_routes' => $completed,
        'total_points' => $total_points,
        'total_time' => $total_time,
        'achievements_count' => $achievements,
    ];
}
function getLocalizedField($row, $field, $lang) {
    $en = $field . '_en';
    if ($lang === 'en' && !empty($row[$en])) return $row[$en];
    return $row[$field] ?? '';
}
function getBalance($user_id) {
    $db = getDB();
    $row = $db->fetch('SELECT balance FROM token_balances WHERE user_id = ?', [$user_id]);
    if (!$row) return 0;
    return (float)$row['balance'];
}
function getDefaultAvatar($name) {
    $initial = mb_substr($name ?: 'U', 0, 1);
    return 'https://ui-avatars.com/api/?name=' . urlencode($initial) . '&background=random';
}
function getUserPhotos($user_id, $route_id = null, $limit = 100) {
    $db = getDB();
    $sql = 'SELECT p.*, pt.name as point_name, pt.name_en as point_name_en, r.name as route_name, r.name_en as route_name_en
            FROM user_photos p
            JOIN points pt ON p.point_id = pt.id
            JOIN routes r ON pt.route_id = r.id
            WHERE p.user_id = ? AND p.moderation_status IN ("approved","pending")';
    $params = [$user_id];
    if ($route_id) {
        $sql .= ' AND r.id = ?';
        $params[] = $route_id;
    }
    $sql .= ' ORDER BY p.created_at DESC LIMIT ' . (int)$limit;
    return $db->fetchAll($sql, $params);
}
function resolvePath($path) {
    $path = trim($path);
    if (strpos($path, '/') === 0 || preg_match('#^[A-Za-z]:[\\\\/]#', $path)) {
        return rtrim($path, '/\\');
    }
    $full = rtrim(BASE_DIR, '/\\') . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), '/\\');
    $resolved = @realpath($full);
    return $resolved ?: $full;
}