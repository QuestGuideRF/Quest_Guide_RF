<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isLoggedIn()) {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}
$user = getCurrentUser();
$input = json_decode(file_get_contents('php://input'), true);
$is_public = isset($input['is_profile_public']) ? (int)$input['is_profile_public'] : 1;
$is_public = $is_public ? 1 : 0;
try {
    getDB()->query('UPDATE users SET is_profile_public = ? WHERE id = ?', [$is_public, $user['id']]);
    echo json_encode(['success' => true, 'is_profile_public' => $is_public]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Error']);
}