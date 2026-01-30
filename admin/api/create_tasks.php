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
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'moderation_tasks'");
    if ($stmt->rowCount() == 0) {
        http_response_code(400);
        echo json_encode(['error' => 'Table moderation_tasks does not exist']);
        exit;
    }
    $pdo->beginTransaction();
    $count = 0;
    $stmt = $pdo->query("
        SELECT up_photos.id
        FROM user_photos up_photos
        WHERE NOT EXISTS (
            SELECT 1 FROM moderation_tasks mt
            WHERE mt.type = 'photo' AND mt.entity_id = up_photos.id
        )
        AND NOT EXISTS (
            SELECT 1 FROM user_progress up
            WHERE up.user_id = up_photos.user_id
            AND up.current_point_id = up_photos.point_id
            AND up.updated_at >= up_photos.created_at
        )
        LIMIT 50
    ");
    $photos = $stmt->fetchAll();
    foreach ($photos as $photo) {
        $stmt = $pdo->prepare("
            INSERT INTO moderation_tasks (type, entity_id, priority, status, description)
            VALUES ('photo', ?, 'medium', 'pending', 'Фото ожидает модерации')
        ");
        $stmt->execute([$photo['id']]);
        $count++;
    }
    $stmt = $pdo->query("
        SELECT r.id
        FROM reviews r
        WHERE r.is_approved = 0 AND r.is_hidden = 0
        AND NOT EXISTS (
            SELECT 1 FROM moderation_tasks mt
            WHERE mt.type = 'review' AND mt.entity_id = r.id
        )
        LIMIT 50
    ");
    $reviews = $stmt->fetchAll();
    foreach ($reviews as $review) {
        $stmt = $pdo->prepare("
            INSERT INTO moderation_tasks (type, entity_id, priority, status, description)
            VALUES ('review', ?, 'low', 'pending', 'Отзыв ожидает модерации')
        ");
        $stmt->execute([$review['id']]);
        $count++;
    }
    $pdo->commit();
    echo json_encode(['success' => true, 'count' => $count]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}