<?php
define('APP_INIT', true);
require_once __DIR__ . '/../includes/init.php';
$token = $_GET['token'] ?? $_POST['token'] ?? '';
if (empty($token)) {
    header('Location: /?error=no_token');
    exit;
}
$session = getDB()->fetch(
    'SELECT telegram_id, expires_at FROM user_sessions WHERE token = ? AND is_used = FALSE',
    [$token]
);
if (!$session) {
    header('Location: /?error=invalid_token');
    exit;
}
$exp = new DateTime($session['expires_at'], new DateTimeZone('UTC'));
if ($exp < new DateTime('now', new DateTimeZone('UTC'))) {
    header('Location: /?error=expired_token');
    exit;
}
getDB()->query('UPDATE user_sessions SET is_used = TRUE, used_at = NOW() WHERE token = ?', [$token]);
$user = getDB()->fetch('SELECT * FROM users WHERE telegram_id = ?', [$session['telegram_id']]);
if (!$user) {
    header('Location: /?error=user_not_found');
    exit;
}
loginUser($user);
header('Location: /dashboard.php');
exit;