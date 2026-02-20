<?php
error_reporting(0);
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');
ob_start();
try {
    require_once __DIR__ . '/../includes/init.php';
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Ошибка инициализации: ' . $e->getMessage()]);
    exit;
}
$output = ob_get_clean();
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Необходима авторизация']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Метод не поддерживается']);
    exit;
}
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Файл не загружен']);
    exit;
}
$user = getCurrentUser();
$file = $_FILES['avatar'];
$maxSize = 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Размер файла не должен превышать 10 МБ']);
    exit;
}
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mimeType, $allowedTypes)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Недопустимый формат файла. Разрешены: JPG, PNG, GIF, WEBP']);
    exit;
}
switch ($mimeType) {
    case 'image/jpeg':
        $extension = 'jpg';
        break;
    case 'image/png':
        $extension = 'png';
        break;
    case 'image/gif':
        $extension = 'gif';
        break;
    case 'image/webp':
        $extension = 'webp';
        break;
    default:
        $extension = 'jpg';
}
$uploadDir = __DIR__ . '/../uploads/avatars/' . $user['id'];
if (!file_exists($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Не удалось создать директорию']);
        exit;
    }
}
$filename = 'avatar_' . time() . '.' . $extension;
$filepath = $uploadDir . '/' . $filename;
try {
    $image = null;
    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
    }
    if (!$image) {
        throw new Exception('Не удалось обработать изображение');
    }
    $width = imagesx($image);
    $height = imagesy($image);
    $newSize = 300;
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    $newImage = imagecreatetruecolor($newSize, $newSize);
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($newImage, false);
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newSize, $newSize, $transparent);
    }
    imagecopyresampled($newImage, $image, 0, 0, $x, $y, $newSize, $newSize, $size, $size);
    $saved = false;
    switch ($extension) {
        case 'jpg':
            $saved = imagejpeg($newImage, $filepath, 90);
            break;
        case 'png':
            $saved = imagepng($newImage, $filepath, 9);
            break;
        case 'gif':
            $saved = imagegif($newImage, $filepath);
            break;
        case 'webp':
            $saved = imagewebp($newImage, $filepath, 90);
            break;
    }
    imagedestroy($image);
    imagedestroy($newImage);
    if (!$saved) {
        throw new Exception('Не удалось сохранить файл');
    }
    if ($user['photo_url'] && strpos($user['photo_url'], '/uploads/avatars/') === 0) {
        $oldFile = __DIR__ . '/..' . $user['photo_url'];
        if (file_exists($oldFile)) {
            @unlink($oldFile);
        }
    }
    $photoUrl = '/uploads/avatars/' . $user['id'] . '/' . $filename;
    try {
        $db = getDB();
        $result = $db->execute(
            'UPDATE users SET photo_url = ? WHERE id = ?',
            [$photoUrl, $user['id']]
        );
        if (!$result) {
            throw new Exception('Не удалось обновить БД');
        }
    } catch (Exception $e) {
        throw new Exception('Ошибка БД: ' . $e->getMessage());
    }
    if (isset($_SESSION['user'])) {
        $_SESSION['user']['photo_url'] = $photoUrl;
    }
    while (ob_get_level()) {
        ob_end_clean();
    }
    echo json_encode([
        'success' => true,
        'photo_url' => $photoUrl,
        'message' => 'Аватар успешно обновлён'
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    while (ob_get_level()) {
        ob_end_clean();
    }
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}