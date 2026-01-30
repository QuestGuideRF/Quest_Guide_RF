<?php
header('Content-Type: image/png; charset=utf-8');
define('APP_INIT', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
session_start();
if (!isLoggedIn()) {
    http_response_code(401);
    die('Unauthorized');
}
$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    die('Unauthorized');
}
$progress_id = isset($_GET['progress_id']) ? (int)$_GET['progress_id'] : 0;
if (!$progress_id) {
    http_response_code(400);
    die('Progress ID required');
}
$pdo = getDB()->getConnection();
$pdo->exec("SET NAMES utf8mb4");
$stmt = $pdo->prepare("
    SELECT
        up.started_at,
        up.completed_at,
        TIMESTAMPDIFF(MINUTE, up.started_at, up.completed_at) as minutes,
        u.first_name,
        u.last_name,
        r.name as route_name,
        r.distance
    FROM user_progress up
    JOIN users u ON up.user_id = u.id
    JOIN routes r ON up.route_id = r.id
    WHERE up.id = ? AND up.user_id = ? AND up.status = 'COMPLETED'
");
$stmt->execute([$progress_id, $user['id']]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    http_response_code(404);
    die('Not found');
}
$certificate_path = __DIR__ . '/../assets/certificate_foto/certificate.png';
$font_path = __DIR__ . '/../assets/fonts/DejaVuSans.ttf';
if (!file_exists($certificate_path)) {
    die('Certificate template not found');
}
$image = imagecreatefrompng($certificate_path);
imagesavealpha($image, true);
$dark = imagecolorallocate($image, 60, 50, 40);
$gray = imagecolorallocate($image, 100, 90, 80);
$w = imagesx($image);
$h = imagesy($image);
$cx = $w / 2;
$user_name = trim($data['first_name'] . ' ' . $data['last_name']);
if (empty(trim($user_name))) {
    $user_name = "Участник";
}
$route_name = $data['route_name'] ?: "Квест";
$mins = (int)$data['minutes'];
$hrs = floor($mins / 60);
$m = $mins % 60;
$time_text = "Время прохождения: " . ($hrs > 0 ? $hrs . " ч. " : "") . $m . " мин.";
$dist = (float)($data['distance'] ?? 0);
$distance_text = "Расстояние: " . number_format($dist, 1, '.', '') . " км";
if (!file_exists($font_path)) {
    $alt_fonts = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/TTF/DejaVuSans.ttf',
    ];
    foreach ($alt_fonts as $f) {
        if (file_exists($f)) {
            $font_path = $f;
            break;
        }
    }
}
if (!file_exists($font_path)) {
    imagestring($image, 5, 50, 50, "Font not found!", $dark);
    imagepng($image);
    imagedestroy($image);
    exit;
}
function drawCenteredText($img, $text, $cx, $y, $color, $font, $size) {
    $box = imagettfbbox($size, 0, $font, $text);
    $tw = abs($box[4] - $box[0]);
    $x = $cx - ($tw / 2);
    imagettftext($img, $size, 0, (int)$x, (int)$y, $color, $font, $text);
}
$y1 = $h * 0.32;
$gap = $h * 0.08;
drawCenteredText($image, $user_name, $cx, $y1, $dark, $font_path, 48);
drawCenteredText($image, $route_name, $cx, $y1 + $gap, $gray, $font_path, 36);
drawCenteredText($image, $time_text, $cx, $y1 + $gap * 2, $gray, $font_path, 28);
drawCenteredText($image, $distance_text, $cx, $y1 + $gap * 2.7, $gray, $font_path, 28);
imagepng($image);
imagedestroy($image);