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
if (isAdminLoggedIn()) {
    header('Location: /admin/dashboard.php');
    exit;
}
http_response_code(403);
require_once __DIR__ . '/../403.php';
exit;