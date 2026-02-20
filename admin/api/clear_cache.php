<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}
try {
    $cache_dir = sys_get_temp_dir() . '/admin_cache_*';
    foreach (glob($cache_dir) as $file) {
        unlink($file);
    }
    echo json_encode(['success' => true, 'message' => 'Cache cleared']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}