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
$task_id = $input['id'] ?? null;
$point_id = $input['point_id'] ?? null;
if (!$point_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Point ID is required']);
    exit;
}
try {
    if ($task_id) {
        $stmt = $pdo->prepare("
            UPDATE tasks
            SET task_text = ?,
                task_text_en = ?,
                task_type = ?,
                text_answer = ?,
                text_answer_hint = ?,
                accept_partial_match = ?,
                max_attempts = ?,
                `order` = ?
            WHERE id = ? AND point_id = ?
        ");
        $stmt->execute([
            $input['task_text'] ?? '',
            !empty($input['task_text_en']) ? $input['task_text_en'] : null,
            $input['task_type'] ?? 'photo',
            !empty($input['text_answer']) ? $input['text_answer'] : null,
            !empty($input['text_answer_hint']) ? $input['text_answer_hint'] : null,
            isset($input['accept_partial_match']) ? 1 : 0,
            $input['max_attempts'] ?? 3,
            $input['order'] ?? 0,
            $task_id,
            $point_id
        ]);
        echo json_encode(['success' => true, 'id' => $task_id]);
    } else {
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(`order`), -1) + 1 as next_order FROM tasks WHERE point_id = ?");
        $stmt->execute([$point_id]);
        $next_order = $stmt->fetch()['next_order'];
        $stmt = $pdo->prepare("
            INSERT INTO tasks (point_id, task_text, task_text_en, task_type, text_answer, text_answer_hint,
                             accept_partial_match, max_attempts, `order`)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $point_id,
            $input['task_text'] ?? '',
            !empty($input['task_text_en']) ? $input['task_text_en'] : null,
            $input['task_type'] ?? 'photo',
            !empty($input['text_answer']) ? $input['text_answer'] : null,
            !empty($input['text_answer_hint']) ? $input['text_answer_hint'] : null,
            isset($input['accept_partial_match']) ? 1 : 0,
            $input['max_attempts'] ?? 3,
            $input['order'] ?? $next_order
        ]);
        $new_id = $pdo->lastInsertId();
        echo json_encode(['success' => true, 'id' => $new_id]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}