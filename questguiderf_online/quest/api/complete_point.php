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
if (!$progress_id || !$point_id) {
    header('Location: /dashboard.php');
    exit;
}
$progress = getDB()->fetch('SELECT * FROM user_progress WHERE id = ? AND user_id = ?', [$progress_id, $user['id']]);
if (!$progress || $progress['current_point_id'] != $point_id) {
    header('Location: /dashboard.php');
    exit;
}
header('Location: /quest/next.php?progress_id=' . $progress_id);
exit;