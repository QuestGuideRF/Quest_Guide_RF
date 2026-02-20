<?php
require_once dirname(__DIR__) . '/includes/init.php';
requireAuth();
$user = getCurrentUser();
$path = isset($_GET['path']) ? trim($_GET['path']) : '';
if ($path === '' || strpos($path, '..') !== false || $path[0] === '/') {
    http_response_code(400);
    exit;
}
if (!preg_match('#^users/(\d+)/[a-zA-Z0-9_.-]+\.(jpe?g|png|gif|webp)$#', $path, $m) || (int)$m[1] !== (int)$user['id']) {
    http_response_code(403);
    exit;
}
$relPath = str_replace('/', DIRECTORY_SEPARATOR, $path);
$photosBase = resolvePath(defined('PHOTOS_PATH') ? PHOTOS_PATH : UPLOAD_PATH);
$bases = [$photosBase];
if (defined('UPLOAD_PATH')) {
    $uploadBase = resolvePath(UPLOAD_PATH);
    if ($uploadBase !== $photosBase) $bases[] = $uploadBase;
}
$full = null;
foreach ($bases as $base) {
    $candidate = $base . DIRECTORY_SEPARATOR . $relPath;
    if (file_exists($candidate) && is_file($candidate)) {
        $full = $candidate;
        break;
    }
}
if (!$full) {
    http_response_code(404);
    exit;
}
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mimes = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
$mime = $mimes[$ext] ?? 'application/octet-stream';
header('Content-Type: ' . $mime);
header('Cache-Control: public, max-age=86400');
readfile($full);