<?php
header('Content-Type: application/json');
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$ids = $data['ids'] ?? [];
if (empty($action) || empty($ids)) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}
try {
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    switch ($action) {
        case 'activate':
            $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            break;
        case 'deactivate':
            $stmt = $pdo->prepare("UPDATE promo_codes SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            break;
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM promo_codes WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            break;
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
            exit;
    }
    echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}