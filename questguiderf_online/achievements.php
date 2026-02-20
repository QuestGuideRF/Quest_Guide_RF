<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$user_achievements = getDB()->fetchAll(
    'SELECT ua.*, a.name, a.name_en, a.description, a.description_en, a.icon
     FROM user_achievements ua
     JOIN achievements a ON ua.achievement_id = a.id
     WHERE ua.user_id = ?
     ORDER BY ua.earned_at DESC',
    [$user['id']]
);
$all_achievements = getDB()->fetchAll('SELECT * FROM achievements WHERE is_hidden = 0 ORDER BY `order`');
$unlocked_ids = array_column($user_achievements, 'achievement_id');
$page_title = t('achievements');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>🏆 <?= t('achievements') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Ваши достижения' : 'Your achievements' ?> (<?= count($user_achievements) ?>/<?= count($all_achievements) ?>)</p>
    </div>
    <div class="stats-grid" style="margin-bottom:2rem;">
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-content">
                <div class="stat-value"><?= count($user_achievements) ?></div>
                <div class="stat-label"><?= $lang === 'ru' ? 'Получено' : 'Unlocked' ?></div>
            </div>
        </div>
    </div>
    <div class="achievement-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:1rem;">
        <?php foreach ($all_achievements as $a): ?>
        <?php $unlocked = in_array($a['id'], $unlocked_ids); ?>
        <div class="quest-card" style="opacity:<?= $unlocked ? '1' : '0.6' ?>">
            <div style="display:flex;align-items:flex-start;gap:1rem;">
                <span style="font-size:2.5rem;"><?= e($a['icon']) ?></span>
                <div>
                    <h3 style="margin:0 0 0.5rem;"><?= e(getLocalizedField($a, 'name', $lang)) ?></h3>
                    <p class="text-muted" style="font-size:0.875rem;margin:0;"><?= e(getLocalizedField($a, 'description', $lang)) ?></p>
                    <?php if ($unlocked): ?>
                    <span class="badge badge-success" style="margin-top:0.5rem;"><?= $lang === 'ru' ? 'Получено' : 'Unlocked' ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>