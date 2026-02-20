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
$photo_id = $input['photo_id'] ?? null;
$action = $input['action'] ?? '';
$reason = $input['reason'] ?? null;
if (!$photo_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No photo ID provided']);
    exit;
}
try {
    $stmt = $pdo->prepare("
        SELECT up_photos.*, u.telegram_id, pt.order as point_order, pt.route_id
        FROM user_photos up_photos
        JOIN users u ON up_photos.user_id = u.id
        JOIN points pt ON up_photos.point_id = pt.id
        WHERE up_photos.id = ?
    ");
    $stmt->execute([$photo_id]);
    $photo = $stmt->fetch();
    if (!$photo) {
        http_response_code(404);
        echo json_encode(['error' => 'Photo not found']);
        exit;
    }
    $admin = getCurrentAdmin();
    if ($action === 'approve') {
        $stmt = $pdo->prepare("
            SELECT pt.name AS point_name, pt.fact_text,
                   (SELECT t.task_text FROM tasks t WHERE t.point_id = pt.id ORDER BY t.`order` ASC LIMIT 1) AS task_text,
                   r.name AS route_name, r.id AS route_id
            FROM points pt
            JOIN routes r ON pt.route_id = r.id
            WHERE pt.id = ?
        ");
        $stmt->execute([$photo['point_id']]);
        $point_info = $stmt->fetch();
        $stmt = $pdo->prepare("
            SELECT p.id, p.name, p.`order`,
                   (SELECT t.task_text FROM tasks t WHERE t.point_id = p.id ORDER BY t.`order` ASC LIMIT 1) AS task_text
            FROM points p
            WHERE p.route_id = ? AND p.`order` > ?
            ORDER BY p.`order` ASC
            LIMIT 1
        ");
        $stmt->execute([$photo['route_id'], $photo['point_order']]);
        $next_point = $stmt->fetch();
        $stmt = $pdo->prepare("
            SELECT id FROM user_progress
            WHERE user_id = ? AND route_id = ?
            LIMIT 1
        ");
        $stmt->execute([$photo['user_id'], $photo['route_id']]);
        $progress = $stmt->fetch();
        if ($progress) {
            $stmt = $pdo->prepare("
                UPDATE user_progress
                SET current_point_id = ?,
                    current_point_order = ?,
                    points_completed = points_completed + 1,
                    updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$photo['point_id'], $photo['point_order'], $progress['id']]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO user_progress (user_id, route_id, current_point_id, current_point_order, points_completed, status)
                VALUES (?, ?, ?, ?, 1, 'IN_PROGRESS')
            ");
            $stmt->execute([$photo['user_id'], $photo['route_id'], $photo['point_id'], $photo['point_order']]);
        }
        $message_text = "✅ <b>Администратор принял ваше фото!</b>\n\n";
        $message_text .= "📍 Точка засчитана: " . htmlspecialchars($point_info['point_name']) . "\n\n";
        if ($next_point) {
            $message_text .= "Следующая точка: " . htmlspecialchars($next_point['name']) . "\n\n";
            $message_text .= htmlspecialchars($next_point['task_text']) . "\n\n";
            $message_text .= "📸 Отправьте фото, когда будете на месте!";
        } else {
            $message_text .= "🎉 Поздравляем! Вы завершили маршрут \"" . htmlspecialchars($point_info['route_name']) . "\"!";
        }
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '❌ Прервать квест',
                        'callback_data' => 'cancel_quest:' . $photo['route_id']
                    ]
                ]
            ]
        ];
        $bot_token = BOT_TOKEN;
        $telegram_id = $photo['telegram_id'];
        $api_url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
        $post_data = [
            'chat_id' => $telegram_id,
            'text' => $message_text,
            'parse_mode' => 'HTML',
            'reply_markup' => json_encode($keyboard)
        ];
        $ch = curl_init($api_url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
        echo json_encode([
            'success' => true,
            'message' => 'Фото принято'
        ]);
    } elseif ($action === 'reject') {
        if (!$reason) {
            http_response_code(400);
            echo json_encode(['error' => 'Reason required']);
            exit;
        }
        $stmt = $pdo->prepare("DELETE FROM user_photos WHERE id = ?");
        $stmt->execute([$photo_id]);
        echo json_encode([
            'success' => true,
            'message' => 'Фото отклонено'
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}