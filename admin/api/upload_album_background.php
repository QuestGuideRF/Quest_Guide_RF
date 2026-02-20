<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/init.php';
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
$route_id = isset($_POST['route_id']) ? (int)$_POST['route_id'] : 0;
if (!$route_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'route_id required']);
    exit;
}
$file_key = 'background_file';
if (!isset($_FILES[$file_key]) || $_FILES[$file_key]['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'File upload error']);
    exit;
}
$file = $_FILES[$file_key];
$max_size = 5 * 1024 * 1024;
if ($file['size'] > $max_size) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Max size 5 MB']);
    exit;
}
$allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowed_extensions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Allowed: JPG, PNG, GIF, WebP']);
    exit;
}
try {
    $pdo = getDB()->getConnection();
    $stmt = $pdo->prepare("SELECT id FROM routes WHERE id = ?");
    $stmt->execute([$route_id]);
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Route not found']);
        exit;
    }
    $upload_dir = __DIR__ . '/../../uploads/album_backgrounds/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    $filename = 'route_' . $route_id . '_' . time() . '.' . $ext;
    $upload_path = $upload_dir . $filename;
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Failed to save']);
        exit;
    }
    $web_path = '/uploads/album_backgrounds/' . $filename;
    $stmt = $pdo->prepare("SELECT template_json FROM album_templates WHERE route_id = ?");
    $stmt->execute([$route_id]);
    $row = $stmt->fetch();
    $default_template = [
        'page_orientation' => 'landscape',
        'photo_position' => 'right',
        'show_point_name' => true,
        'show_fact_text' => true,
        'show_photo_date' => true,
        'show_address' => false,
        'custom_caption' => '',
        'font_size' => 14,
        'show_page_numbers' => true,
        'cover_title' => '',
        'cover_subtitle' => '',
    ];
    $tpl = $row && $row['template_json'] ? array_merge($default_template, json_decode($row['template_json'], true) ?: []) : $default_template;
    if (!empty($tpl['cover_background_path'])) {
        $old_rel = ltrim($tpl['cover_background_path'], '/');
        $old_full = $upload_dir . basename($old_rel);
        if (file_exists($old_full) && strpos($old_rel, 'album_backgrounds/') !== false) {
            @unlink($old_full);
        }
    }
    $tpl['cover_background_path'] = $web_path;
    $json = json_encode($tpl, JSON_UNESCAPED_UNICODE);
    $stmt = $pdo->prepare("
        INSERT INTO album_templates (route_id, template_json)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE template_json = ?, updated_at = NOW()
    ");
    $stmt->execute([$route_id, $json, $json]);
    echo json_encode(['success' => true, 'file_path' => $web_path]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}