<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit;
}
function loadEnv($path) {
    if (!file_exists($path)) {
        die('.env file not found. Copy .env.example to .env');
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        $parts = explode('=', $line, 2);
        if (count($parts) < 2) continue;
        $name = trim($parts[0]);
        $value = trim($parts[1]);
        if (!array_key_exists($name, $_ENV)) {
            $_ENV[$name] = $value;
        }
    }
}
$baseDir = dirname(__DIR__);
loadEnv($baseDir . '/.env');
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? '');
define('DB_USER', $_ENV['DB_USER'] ?? '');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');
define('BOT_TOKEN', $_ENV['BOT_TOKEN'] ?? '');
define('BOT_USERNAME', $_ENV['BOT_USERNAME'] ?? '');
define('SITE_URL', rtrim($_ENV['SITE_URL'] ?? 'https://questguiderf.online', '/'));
define('SITE_NAME', 'QuestGuideRF');
define('BASE_DIR', $baseDir);
define('UPLOAD_PATH', $_ENV['UPLOAD_PATH'] ?? '../uploads');
define('UPLOAD_URL', $_ENV['UPLOAD_URL'] ?? '/uploads');
define('PHOTOS_PATH', $_ENV['PHOTOS_PATH'] ?? $_ENV['UPLOAD_PATH'] ?? '../uploads');
define('PHOTOS_URL', $_ENV['PHOTOS_URL'] ?? $_ENV['UPLOAD_URL'] ?? '/uploads');
define('SESSION_LIFETIME', 60 * 60 * 24 * 90);
date_default_timezone_set('Europe/Moscow');
$debug = ($_ENV['DEBUG'] ?? 'false') === 'true';
if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}