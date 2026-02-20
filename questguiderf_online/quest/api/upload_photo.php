<?php
require_once __DIR__ . '/../../includes/init.php';
requireAuth();
$user = getCurrentUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard.php');
    exit;
}
$progress_id = (int)($_POST['progress_id'] ?? 0);
$point_id = (int)($_POST['point_id'] ?? 0);
if (!$progress_id || !$point_id) {
    header('Location: /dashboard.php');
    exit;
}
$progress = getDB()->fetch('SELECT * FROM user_progress WHERE id = ? AND user_id = ?', [$progress_id, $user['id']]);
if (!$progress || $progress['current_point_id'] != $point_id) {
    header('Location: /dashboard.php');
    exit;
}
if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    $_SESSION['flash_error'] = 'Выберите фото для загрузки';
    header('Location: /quest/point.php?progress_id=' . $progress_id);
    exit;
}
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($_FILES['photo']['tmp_name']);
$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
if (!in_array($mime, $allowed)) {
    $_SESSION['flash_error'] = 'Допустимые форматы: JPG, PNG, GIF, WebP';
    header('Location: /quest/point.php?progress_id=' . $progress_id);
    exit;
}
$ext = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
][$mime] ?? 'jpg';
$photosDir = defined('PHOTOS_PATH') ? resolvePath(PHOTOS_PATH) : resolvePath(UPLOAD_PATH);
if (!is_dir($photosDir)) {
    if (!@mkdir($photosDir, 0755, true)) {
        $photosDir = rtrim(BASE_DIR, '/\\') . DIRECTORY_SEPARATOR . 'uploads';
        if (!is_dir($photosDir)) @mkdir($photosDir, 0755, true);
    }
}
$userDir = $photosDir . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $user['id'];
if (!is_dir($userDir)) @mkdir($userDir, 0755, true);
$filename = 'point_' . $point_id . '_' . time() . '.' . $ext;
$filepath = $userDir . DIRECTORY_SEPARATOR . $filename;
$moved = @move_uploaded_file($_FILES['photo']['tmp_name'], $filepath);
if (!$moved) {
    $fallbackDir = rtrim(BASE_DIR, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'users' . DIRECTORY_SEPARATOR . $user['id'];
    if (!is_dir($fallbackDir)) @mkdir($fallbackDir, 0755, true);
    $filepath = $fallbackDir . DIRECTORY_SEPARATOR . $filename;
    $moved = @move_uploaded_file($_FILES['photo']['tmp_name'], $filepath);
    if (!$moved) {
        $_SESSION['flash_error'] = 'Ошибка сохранения файла. Укажите PHOTOS_PATH в .env (например /www/questguiderf.ru/photos) и проверьте права.';
        header('Location: /quest/point.php?progress_id=' . $progress_id);
        exit;
    }
}
$relative_path = 'users/' . $user['id'] . '/' . $filename;
$file_hash = hash_file('sha256', $filepath);
getDB()->query(
    'INSERT INTO user_photos (user_id, point_id, file_id, file_path, file_hash, moderation_status) VALUES (?, ?, ?, ?, ?, "approved")',
    [$user['id'], $point_id, 'web_upload', $relative_path, $file_hash]
);
header('Location: /quest/next.php?progress_id=' . $progress_id);
exit;