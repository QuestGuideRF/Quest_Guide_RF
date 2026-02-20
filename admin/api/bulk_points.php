<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$ids = $input['ids'] ?? [];
$data = $input['data'] ?? [];
if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'No IDs provided']);
    exit;
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
try {
    switch ($action) {
        case 'update_coordinates':
            $latitude = $data['latitude'] ?? null;
            $longitude = $data['longitude'] ?? null;
            if ($latitude === null || $longitude === null) {
                throw new Exception('Координаты не указаны');
            }
            $stmt = $pdo->prepare("UPDATE points SET latitude = ?, longitude = ? WHERE id IN ($placeholders)");
            $stmt->execute(array_merge([$latitude, $longitude], $ids));
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'update_status':
            $audio_enabled = $data['audio_enabled'] ?? null;
            $audio_file_path_ru = $data['audio_file_path_ru'] ?? null;
            $audio_file_path_en = $data['audio_file_path_en'] ?? null;
            $updates = [];
            $params = [];
            if ($audio_enabled !== null) {
                $updates[] = "audio_enabled = ?";
                $params[] = $audio_enabled ? 1 : 0;
            }
            if ($audio_file_path_ru !== null) {
                $updates[] = "audio_file_path_ru = ?";
                $params[] = $audio_file_path_ru;
                if (empty($audio_file_path_ru)) {
                    $stmt_check = $pdo->prepare("SELECT audio_file_path_ru FROM points WHERE id IN ($placeholders)");
                    $stmt_check->execute($ids);
                    $old_files = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($old_files as $old_file) {
                        if ($old_file && file_exists(__DIR__ . '/../..' . $old_file)) {
                            @unlink(__DIR__ . '/../..' . $old_file);
                        }
                    }
                }
            }
            if ($audio_file_path_en !== null) {
                $updates[] = "audio_file_path_en = ?";
                $params[] = $audio_file_path_en;
                if (empty($audio_file_path_en)) {
                    $stmt_check = $pdo->prepare("SELECT audio_file_path_en FROM points WHERE id IN ($placeholders)");
                    $stmt_check->execute($ids);
                    $old_files = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
                    foreach ($old_files as $old_file) {
                        if ($old_file && file_exists(__DIR__ . '/../..' . $old_file)) {
                            @unlink(__DIR__ . '/../..' . $old_file);
                        }
                    }
                }
            }
            if (empty($updates)) {
                throw new Exception('Нет данных для обновления');
            }
            $stmt = $pdo->prepare("UPDATE points SET " . implode(', ', $updates) . " WHERE id IN ($placeholders)");
            $stmt->execute(array_merge($params, $ids));
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM points WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            break;
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}