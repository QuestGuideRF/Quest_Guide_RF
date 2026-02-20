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
if (!$progress) {
    header('Location: /dashboard.php');
    exit;
}
$point_id = $progress['current_point_id'];
$point = getDB()->fetch('SELECT * FROM points WHERE id = ?', [$point_id]);
if (!$point) {
    header('Location: /dashboard.php');
    exit;
}
$tasks = getDB()->fetchAll('SELECT * FROM tasks WHERE point_id = ? ORDER BY `order`', [$point_id]);
$hints = getDB()->fetchAll('SELECT * FROM hints WHERE point_id = ? ORDER BY `order`', [$point_id]);
$lang = getCurrentLanguage();
$point_name = getLocalizedField($point, 'name', $lang);
$fact = getLocalizedField($point, 'fact_text', $lang);
$directions = getLocalizedField($point, 'audio_text', $lang) ?: $fact;
$task = $tasks[0] ?? null;
$task_text = $task ? getLocalizedField($task, 'task_text', $lang) : '';
$task_type = ($task && isset($task['task_type'])) ? $task['task_type'] : ($point['task_type'] ?? 'photo');
$already_done = getDB()->fetch(
    'SELECT 1 FROM user_photos WHERE user_id = ? AND point_id = ? AND moderation_status IN ("approved","pending")',
    [$user['id'], $point_id]
);
$total_points = getDB()->fetch('SELECT COUNT(*) as c FROM points WHERE route_id = ?', [$progress['route_id']])['c'];
$page_title = $point_name;
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="quest-card" style="max-width:800px;margin:0 auto;">
        <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-error" style="margin-bottom:1rem;"><?= e($_SESSION['flash_error']) ?></div>
        <?php unset($_SESSION['flash_error']); endif; ?>
        <nav style="margin-bottom:1rem;">
            <a href="/dashboard.php">← <?= t('home') ?></a>
        </nav>
        <h1><?= e($point_name) ?></h1>
        <p style="color:var(--text-secondary);"><?= $progress['points_completed'] + 1 ?> / <?= $total_points ?></p>
        <?php
        $audio_path = $lang === 'en' && !empty($point['audio_file_path_en']) ? $point['audio_file_path_en'] : ($point['audio_file_path_ru'] ?? $point['audio_file_path'] ?? null);
        if (!empty($point['audio_enabled']) && $audio_path):
            $audio_url = (strpos($audio_path, 'http') === 0 || strpos($audio_path, '/') === 0) ? $audio_path : rtrim(SITE_URL, '/') . '/' . ltrim($audio_path, '/');
        ?>
        <div class="audio-guide" style="margin:1rem 0;">
            <audio controls style="width:100%;max-width:400px;">
                <source src="<?= e($audio_url) ?>" type="audio/mpeg">
            </audio>
            <p class="text-small text-muted"><?= $lang === 'ru' ? 'Аудиогид' : 'Audio guide' ?></p>
        </div>
        <?php endif; ?>
        <?php if ($fact): ?>
        <section style="margin:1.5rem 0;">
            <h3><?= $lang === 'ru' ? 'Факт' : 'Fact' ?></h3>
            <div style="white-space:pre-wrap;"><?= nl2br(e($fact)) ?></div>
        </section>
        <?php endif; ?>
        <?php if ($directions && $directions !== $fact): ?>
        <section style="margin:1.5rem 0;">
            <h3><?= $lang === 'ru' ? 'Как добраться' : 'How to get there' ?></h3>
            <div style="white-space:pre-wrap;"><?= nl2br(e($directions)) ?></div>
        </section>
        <?php endif; ?>
        <?php if ($task_text): ?>
        <section style="margin:1.5rem 0;">
            <h3><?= $lang === 'ru' ? 'Задание' : 'Task' ?></h3>
            <div style="white-space:pre-wrap;"><?= nl2br(e($task_text)) ?></div>
        </section>
        <?php endif; ?>
        <?php if (!$already_done): ?>
        <?php if ($task_type === 'photo'): ?>
        <form action="/quest/api/upload_photo.php" method="post" enctype="multipart/form-data" class="photo-upload-form">
            <input type="hidden" name="progress_id" value="<?= $progress_id ?>">
            <input type="hidden" name="point_id" value="<?= $point_id ?>">
            <div class="file-upload-wrap">
                <label class="file-upload-label">
                    <input type="file" name="photo" id="quest-photo-input" accept="image/*" required class="file-upload-input">
                    <span class="file-upload-icon">📷</span>
                    <span class="file-upload-text" data-empty="<?= $lang === 'ru' ? 'Выберите фото' : 'Choose photo' ?>" data-selected="<?= $lang === 'ru' ? 'Файл выбран' : 'File selected' ?>"><?= $lang === 'ru' ? 'Выберите фото' : 'Choose photo' ?></span>
                </label>
            </div>
            <button type="submit" class="btn btn-primary btn-upload-submit"><?= t('upload_photo') ?></button>
        </form>
        <?php elseif ($task_type === 'text' || $task_type === 'riddle'): ?>
        <form action="/quest/api/check_answer.php" method="post">
            <input type="hidden" name="progress_id" value="<?= $progress_id ?>">
            <input type="hidden" name="point_id" value="<?= $point_id ?>">
            <input type="text" name="answer" class="quest-answer-input" placeholder="<?= $lang === 'ru' ? 'Ваш ответ' : 'Your answer' ?>" required>
            <button type="submit" class="btn btn-primary"><?= $lang === 'ru' ? 'Проверить' : 'Check' ?></button>
        </form>
        <?php else: ?>
        <form action="/quest/api/complete_point.php" method="post">
            <input type="hidden" name="progress_id" value="<?= $progress_id ?>">
            <input type="hidden" name="point_id" value="<?= $point_id ?>">
            <button type="submit" class="btn btn-primary"><?= t('im_here') ?></button>
        </form>
        <?php endif; ?>
        <?php if (!empty($hints)): ?>
        <details style="margin-top:1.5rem;">
            <summary><?= t('hint') ?></summary>
            <?php foreach ($hints as $h): ?>
            <div style="margin-top:0.5rem;"><?= nl2br(e(getLocalizedField($h, 'text', $lang))) ?></div>
            <?php endforeach; ?>
        </details>
        <?php endif; ?>
        <?php else: ?>
        <p style="color:var(--success);">✅ <?= $lang === 'ru' ? 'Точка пройдена' : 'Point completed' ?></p>
        <a href="/quest/next.php?progress_id=<?= $progress_id ?>" class="btn btn-primary"><?= t('next_point') ?></a>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('quest-photo-input') && document.getElementById('quest-photo-input').addEventListener('change', function() {
    var label = this.closest('.file-upload-wrap').querySelector('.file-upload-text');
    if (label) label.textContent = this.files.length ? (label.getAttribute('data-selected') || 'Файл выбран') : (label.getAttribute('data-empty') || 'Выберите фото');
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>