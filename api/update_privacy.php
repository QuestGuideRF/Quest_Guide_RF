<?php
require_once __DIR__ . '/../includes/init.php';
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}
$user = getCurrentUser();
if (!$user) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$is_public = isset($input['is_profile_public']) ? (int)$input['is_profile_public'] : 1;
$is_public = $is_public ? 1 : 0;
try {
    getDB()->execute(
        'UPDATE users SET is_profile_public = ? WHERE id = ?',
        [$is_public, $user['id']]
    );
    echo json_encode([
        'success' => true,
        'is_profile_public' => $is_public
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database error']);
}