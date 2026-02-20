<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
function loginUser($user) {
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['telegram_id'] = $user['telegram_id'];
    $_SESSION['logged_in'] = true;
}
function isLoggedIn() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}
function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return getDB()->fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
}
function logout() {
    session_destroy();
    header('Location: /');
    exit;
}
function requireAuth() {
    if (!isLoggedIn()) {
        header('Location: /');
        exit;
    }
}
function isUserBanned($user) {
    if (empty($user['ban_until'])) return false;
    return strtotime($user['ban_until']) > time();
}