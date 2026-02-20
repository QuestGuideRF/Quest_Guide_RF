<?php
require_once __DIR__ . '/../../includes/init.php';
requireAuth();
$user = getCurrentUser();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard.php');
    exit;
}
$progress_id = (int)($_POST['progress_id'] ?? 0);
$point_id = (int)($_POST['point_id'] ?? 0);
$answer = trim((string)($_POST['answer'] ?? ''));
if (!$progress_id || !$point_id) {
    header('Location: /dashboard.php');
    exit;
}
$progress = getDB()->fetch('SELECT * FROM user_progress WHERE id = ? AND user_id = ?', [$progress_id, $user['id']]);
if (!$progress || $progress['current_point_id'] != $point_id) {
    header('Location: /dashboard.php');
    exit;
}
$task = getDB()->fetch('SELECT * FROM tasks WHERE point_id = ? ORDER BY `order` LIMIT 1', [$point_id]);
$point_row = getDB()->fetch('SELECT * FROM points WHERE id = ?', [$point_id]);
if (!$point_row) {
    header('Location: /dashboard.php');
    exit;
}
$text_answer = ($task && !empty($task['text_answer'])) ? $task['text_answer'] : ($point_row['text_answer'] ?? '');
$accept_partial = ($task && isset($task['accept_partial_match'])) ? (bool)$task['accept_partial_match'] : (bool)($point_row['accept_partial_match'] ?? false);
if (!$text_answer && (!$task || !in_array($task['task_type'], ['text', 'riddle']))) {
    header('Location: /quest/point.php?progress_id=' . $progress_id);
    exit;
}
$correct = false;
if ($text_answer) {
    $variants = array_map('trim', explode('|', $text_answer));
    $norm = mb_strtolower($answer);
    foreach ($variants as $v) {
        $vnorm = mb_strtolower($v);
        if ($accept_partial) {
            if (strpos($vnorm, $norm) !== false || strpos($norm, $vnorm) !== false) {
                $correct = true;
                break;
            }
        } else {
            if ($vnorm === $norm) {
                $correct = true;
                break;
            }
        }
    }
}
if (!$correct) {
    $_SESSION['flash_error'] = 'Неверный ответ. Попробуйте ещё раз.';
    header('Location: /quest/point.php?progress_id=' . $progress_id);
    exit;
}
header('Location: /quest/next.php?progress_id=' . $progress_id);
exit;