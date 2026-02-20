<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json; charset=utf-8');
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'No file']);
    exit;
}
$user = getCurrentUser();
$file = $_FILES['avatar'];
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'error' => 'Max 5 MB']);
    exit;
}
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $file['tmp_name']);
if (!in_array($mime, $allowed)) {
    echo json_encode(['success' => false, 'error' => 'Invalid format']);
    exit;
}
$ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'][$mime] ?? 'jpg';
$uploadDir = resolvePath(UPLOAD_PATH) . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user['id'];
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
}
if (!is_dir($uploadDir)) {
    $uploadDir = rtrim(BASE_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user['id'];
    @mkdir($uploadDir, 0755, true);
}
$filename = 'avatar_' . time() . '.' . $ext;
$filepath = $uploadDir . DIRECTORY_SEPARATOR . $filename;
$moved = @move_uploaded_file($file['tmp_name'], $filepath);
if (!$moved) {
    echo json_encode(['success' => false, 'error' => 'Upload failed. Check directory permissions.']);
    exit;
}
$relativeUrl = rtrim(UPLOAD_URL, '/') . '/avatars/' . $user['id'] . '/' . $filename;
$photoUrl = (strpos($relativeUrl, 'http') === 0) ? $relativeUrl : rtrim(SITE_URL, '/') . $relativeUrl;
getDB()->query('UPDATE users SET photo_url = ? WHERE id = ?', [$photoUrl, $user['id']]);
if (!empty($user['photo_url']) && strpos($user['photo_url'], '/avatars/') !== false) {
    $oldPath = resolvePath(UPLOAD_PATH) . DIRECTORY_SEPARATOR . 'avatars' . DIRECTORY_SEPARATOR . $user['id'] . DIRECTORY_SEPARATOR . basename(parse_url($user['photo_url'], PHP_URL_PATH));
    if (file_exists($oldPath)) @unlink($oldPath);
}
echo json_encode(['success' => true, 'photo_url' => $photoUrl]);
exit;