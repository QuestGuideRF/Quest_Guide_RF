<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/includes/auth.php';
logoutAdmin();
header('Location: /admin/login.php');
exit;