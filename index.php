<?php
require_once __DIR__ . '/includes/init.php';
if (!isLoggedIn()) {
    require_once __DIR__ . '/pages/login.php';
    exit;
}
header('Location: /dashboard.php');