<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$route_id = (int)($_GET['route_id'] ?? 0);
if (!$route_id) {
    header('Location: /routes.php');
    exit;
}
$route = getDB()->fetch('SELECT * FROM routes WHERE id = ? AND is_active = 1', [$route_id]);
if (!$route) {
    header('Location: /routes.php');
    exit;
}
$balance = getBalance($user['id']);
$has_paid = getDB()->fetch(
    'SELECT 1 FROM payments WHERE user_id = ? AND route_id = ? AND status = "SUCCESS"',
    [$user['id'], $route_id]
);
if (!$has_paid && $balance < $route['price']) {
    header('Location: /routes/view.php?id=' . $route_id);
    exit;
}
$existing = getDB()->fetch(
    'SELECT id FROM user_progress WHERE user_id = ? AND route_id = ? AND status IN ("in_progress","IN_PROGRESS")',
    [$user['id'], $route_id]
);
if ($existing) {
    header('Location: /quest/point.php?progress_id=' . $existing['id']);
    exit;
}
if (!$has_paid) {
    $tb = getDB()->fetch('SELECT balance FROM token_balances WHERE user_id = ?', [$user['id']]);
    if (!$tb) {
        getDB()->query('INSERT INTO token_balances (user_id, balance) VALUES (?, 0)', [$user['id']]);
        $before = 0;
    } else {
        $before = (float)$tb['balance'];
    }
    $after = $before - (float)$route['price'];
    getDB()->query('UPDATE token_balances SET balance = balance - ? WHERE user_id = ?', [$route['price'], $user['id']]);
    getDB()->query(
        'INSERT INTO token_transactions (user_id, type, amount, balance_before, balance_after, description, related_route_id, status) VALUES (?, "purchase", ?, ?, ?, ?, ?, "completed")',
        [$user['id'], $route['price'], $before, $after, 'Покупка маршрута #' . $route_id, $route_id]
    );
    getDB()->query(
        'INSERT INTO payments (user_id, route_id, amount, currency, status) VALUES (?, ?, ?, "RUB", "SUCCESS")',
        [$user['id'], $route_id, (int)$route['price']]
    );
}
$first_point = getDB()->fetch(
    'SELECT id, `order` FROM points WHERE route_id = ? ORDER BY `order` LIMIT 1',
    [$route_id]
);
if (!$first_point) {
    header('Location: /routes.php');
    exit;
}
getDB()->query(
    'INSERT INTO user_progress (user_id, route_id, status, current_point_id, current_point_order, points_completed, started_at) VALUES (?, ?, "in_progress", ?, ?, 0, NOW())',
    [$user['id'], $route_id, $first_point['id'], $first_point['order']]
);
$progress_id = getDB()->lastInsertId();
header('Location: /quest/point.php?progress_id=' . $progress_id);
exit;