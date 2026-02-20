<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    error_log("ADMIN LOGIN ATTEMPT: Token received: " . substr($token, 0, 20) . "...");
    if (loginAdminByToken($token)) {
        error_log("ADMIN LOGIN SUCCESS: Redirecting to dashboard");
        header('Location: /admin/dashboard.php');
        exit;
    } else {
        error_log("ADMIN LOGIN FAILED: Invalid token");
    }
}
<<<<<<< HEAD
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();
header('Location: /dashboard.php');
=======
if (isAdminLoggedIn()) {
    header('Location: /admin/dashboard.php');
    exit;
}
http_response_code(403);
require_once __DIR__ . '/../403.php';
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
exit;