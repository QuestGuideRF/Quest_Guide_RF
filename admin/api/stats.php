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
$type = $_GET['type'] ?? '';
$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$response = [];
try {
    switch ($type) {
        case 'registrations':
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as date, COUNT(*) as count
                FROM users
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $labels = [];
            $values = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d.m', strtotime($date));
                $found = false;
                foreach ($data as $row) {
                    if ($row['date'] == $date) {
                        $values[] = (int)$row['count'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $values[] = 0;
                }
            }
            $response = ['labels' => $labels, 'values' => $values];
            break;
        case 'revenue':
            $stmt = $pdo->prepare("
                SELECT DATE(created_at) as date, COALESCE(SUM(amount), 0) as total
                FROM payments
                WHERE status = 'SUCCESS'
                  AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(created_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $labels = [];
            $values = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d.m', strtotime($date));
                $found = false;
                foreach ($data as $row) {
                    if ($row['date'] == $date) {
                        $values[] = (float)$row['total'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $values[] = 0;
                }
            }
            $response = ['labels' => $labels, 'values' => $values];
            break;
        case 'completions':
            $stmt = $pdo->prepare("
                SELECT DATE(completed_at) as date, COUNT(*) as count
                FROM user_progress
                WHERE status = 'COMPLETED'
                  AND completed_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
                GROUP BY DATE(completed_at)
                ORDER BY date ASC
            ");
            $stmt->execute([$days]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $labels = [];
            $values = [];
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d.m', strtotime($date));
                $found = false;
                foreach ($data as $row) {
                    if ($row['date'] == $date) {
                        $values[] = (int)$row['count'];
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $values[] = 0;
                }
            }
            $response = ['labels' => $labels, 'values' => $values];
            break;
        default:
            http_response_code(400);
            $response = ['error' => 'Invalid type'];
    }
} catch (Exception $e) {
    http_response_code(500);
    $response = ['error' => $e->getMessage()];
}
echo json_encode($response);