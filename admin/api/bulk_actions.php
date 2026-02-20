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
if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['error' => 'No IDs provided']);
    exit;
}
if (isModerator() && in_array($action, ['activate', 'deactivate', 'toggle_status', 'change_price', 'delete', 'export'], true)) {
    $placeholders_ids = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id FROM routes WHERE id IN ($placeholders_ids) AND creator_id = ?");
    $stmt->execute(array_merge($ids, [$_SESSION['admin_id']]));
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    if (empty($ids)) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit;
    }
}
$placeholders = implode(',', array_fill(0, count($ids), '?'));
try {
    switch ($action) {
        case 'activate':
            $stmt = $pdo->prepare("UPDATE routes SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'deactivate':
            $stmt = $pdo->prepare("UPDATE routes SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'toggle_status':
            $stmt = $pdo->prepare("UPDATE routes SET is_active = NOT is_active WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'change_price':
            $price = $input['price'] ?? 0;
            $params = array_merge([$price], $ids);
            $stmt = $pdo->prepare("UPDATE routes SET price = ? WHERE id IN ($placeholders)");
            $stmt->execute($params);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'delete':
            $stmt = $pdo->prepare("DELETE FROM routes WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'deleted' => $stmt->rowCount()]);
            break;
        case 'export':
            $stmt = $pdo->prepare("
                SELECT r.*, c.name as city_name
                FROM routes r
                JOIN cities c ON r.city_id = c.id
                WHERE r.id IN ($placeholders)
            ");
            $stmt->execute($ids);
            $routes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="routes_' . date('Y-m-d') . '.csv"');
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($output, ['ID', 'Название', 'Город', 'Цена', 'Тип', 'Статус', 'Описание']);
            foreach ($routes as $route) {
                fputcsv($output, [
                    $route['id'],
                    $route['name'],
                    $route['city_name'],
                    $route['price'],
                    $route['route_type'],
                    $route['is_active'] ? 'Активен' : 'Неактивен',
                    strip_tags($route['description'])
                ]);
            }
            fclose($output);
            exit;
        case 'activate_cities':
            $stmt = $pdo->prepare("UPDATE cities SET is_active = 1 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'deactivate_cities':
            $stmt = $pdo->prepare("UPDATE cities SET is_active = 0 WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'toggle_city_status':
            $stmt = $pdo->prepare("UPDATE cities SET is_active = NOT is_active WHERE id IN ($placeholders)");
            $stmt->execute($ids);
            echo json_encode(['success' => true, 'affected' => $stmt->rowCount()]);
            break;
        case 'delete_cities':
            $stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM routes WHERE city_id IN ($placeholders)");
            $stmt->execute($ids);
            $routes_count = $stmt->fetch()['cnt'];
            if ($routes_count > 0) {
                http_response_code(400);
                echo json_encode(['error' => "Невозможно удалить: в городах есть $routes_count маршрутов"]);
                exit;
            }
            $stmt = $pdo->prepare("DELETE FROM cities WHERE id IN ($placeholders)");
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