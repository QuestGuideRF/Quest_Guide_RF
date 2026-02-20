<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$reviews = getDB()->fetchAll(
    'SELECT rv.*, rt.name as route_name, rt.name_en as route_name_en
     FROM reviews rv
     JOIN routes rt ON rv.route_id = rt.id
     WHERE rv.user_id = ?
     ORDER BY rv.created_at DESC',
    [$user['id']]
);
$page_title = t('reviews');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>⭐ <?= t('reviews') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Ваши отзывы' : 'Your reviews' ?></p>
    </div>
    <?php if (empty($reviews)): ?>
    <div class="empty-state">
        <div class="empty-icon">⭐</div>
        <p><?= $lang === 'ru' ? 'Вы ещё не оставляли отзывов' : 'You haven\'t left any reviews yet' ?></p>
        <p class="text-muted"><?= $lang === 'ru' ? 'После прохождения квеста вы можете оставить отзыв в боте' : 'After completing a quest you can leave a review in the bot' ?></p>
        <a href="/routes.php" class="btn btn-primary"><?= t('routes') ?></a>
    </div>
    <?php else: ?>
    <?php foreach ($reviews as $r): ?>
    <div class="quest-card" style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:0.5rem;">
            <div>
                <strong><?= e(getLocalizedField($r, 'route_name', $lang)) ?></strong>
                <div style="color:var(--warning);"><?= str_repeat('★', (int)$r['rating']) ?><?= str_repeat('☆', 5 - (int)$r['rating']) ?></div>
            </div>
            <span class="text-muted text-small"><?= date('d.m.Y', strtotime($r['created_at'])) ?></span>
        </div>
        <?php if (!empty($r['text'])): ?>
        <p style="margin-top:0.75rem;"><?= nl2br(e($r['text'])) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>