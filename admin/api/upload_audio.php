<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
$point_id = isset($_POST['point_id']) ? (int)$_POST['point_id'] : 0;
$language = isset($_POST['language']) ? $_POST['language'] : 'ru';
if (!$point_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Point ID is required']);
    exit;
}
if (!in_array($language, ['ru', 'en'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid language']);
    exit;
}
$file_key = 'audio_file';
if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File upload error']);
    exit;
}
$file = $_FILES[$file_key];
$max_size = 20 * 1024 * 1024;
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File size exceeds 20 MB limit']);
    exit;
}
$allowed_extensions = ['mp3', 'm4a', 'wav', 'ogg'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_extensions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid file format. Allowed: ' . implode(', ', $allowed_extensions)]);
    exit;
}
try {
    $pdo = getDB()->getConnection();
    $stmt = $pdo->prepare("SELECT id FROM points WHERE id = ?");
    $stmt->execute([$point_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Point not found']);
        exit;
    }
    $upload_dir = __DIR__ . '/../../uploads/audio/points/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = 'point_' . $point_id . '_' . $language . '_' . time() . '.' . $ext;
    $upload_path = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save file']);
        exit;
    }
    $audio_path = '/uploads/audio/points/' . $filename;
    $field_name = $language === 'ru' ? 'audio_file_path_ru' : 'audio_file_path_en';
    $stmt = $pdo->prepare("SELECT $field_name FROM points WHERE id = ?");
    $stmt->execute([$point_id]);
    $old_file = $stmt->fetchColumn();
    if ($old_file && file_exists(__DIR__ . '/../..' . $old_file)) {
        @unlink(__DIR__ . '/../..' . $old_file);
    }
    $stmt = $pdo->prepare("
        UPDATE points
        SET $field_name = ?,
            audio_enabled = 1
        WHERE id = ?
    ");
    $stmt->execute([$audio_path, $point_id]);
    echo json_encode([
        'success' => true,
        'message' => 'Audio file uploaded successfully',
        'file_path' => $audio_path
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}