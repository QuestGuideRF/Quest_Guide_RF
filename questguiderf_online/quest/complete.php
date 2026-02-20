<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$progress_id = (int)($_GET['progress_id'] ?? 0);
if (!$progress_id) {
    header('Location: /dashboard.php');
    exit;
}
$progress = getDB()->fetch(
    'SELECT up.*, r.name as route_name, r.name_en as route_name_en FROM user_progress up
     JOIN routes r ON up.route_id = r.id
     WHERE up.id = ? AND up.user_id = ?',
    [$progress_id, $user['id']]
);
if (!$progress || !in_array($progress['status'], ['COMPLETED', 'completed'])) {
    header('Location: /dashboard.php');
    exit;
}
$lang = getCurrentLanguage();
$route_name = getLocalizedField($progress, 'route_name', $lang);
$page_title = t('quest_completed');
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="quest-card" style="max-width:600px;margin:2rem auto;text-align:center;">
        <h1>🎉 <?= t('quest_completed') ?></h1>
        <p><?= e($route_name) ?></p>
        <a href="/dashboard.php" class="btn btn-primary"><?= t('home') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>