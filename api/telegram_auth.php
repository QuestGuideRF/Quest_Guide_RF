<?php
require_once __DIR__ . '/../includes/init.php';
$auth_data = $_GET;
if (!isset($auth_data['id']) || !isset($auth_data['hash'])) {
    die('Invalid auth data');
}
if (!verifyTelegramAuth($auth_data)) {
    die('Auth verification failed');
}
$user = getOrCreateUser($auth_data);
loginUser($user);
header('Location: /dashboard.php');