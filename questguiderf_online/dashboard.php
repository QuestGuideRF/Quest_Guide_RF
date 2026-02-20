<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
if (isUserBanned($user)) {
    header('Location: /?banned=1');
    exit;
}
$stats = getUserStats($user['id']);
$balance = getBalance($user['id']);
$active = getDB()->fetchAll(
    'SELECT up.*, r.name, r.name_en, c.name as city_name, c.name_en as city_name_en
     FROM user_progress up
     JOIN routes r ON up.route_id = r.id
     JOIN cities c ON r.city_id = c.id
     WHERE up.user_id = ? AND up.status IN ("in_progress","IN_PROGRESS")
     ORDER BY up.started_at DESC LIMIT 5',
    [$user['id']]
);
$lang = getCurrentLanguage();
$page_title = t('home');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="welcome-section">
        <img src="<?= e($user['photo_url'] ?: getDefaultAvatar($user['first_name'])) ?>" alt="" class="user-avatar" width="80" height="80">
        <div class="user-info">
            <h1><?= $lang === 'ru' ? 'Привет' : 'Hello' ?>, <?= e($user['first_name']) ?>! 👋</h1>
        </div>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value"><?= $balance ?></div>
                <div class="stat-label"><?= t('balance') ?> (<?= t('groshi') ?>)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🗺️</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['completed_routes'] ?></div>
                <div class="stat-label"><?= $lang === 'ru' ? 'Маршрутов пройдено' : 'Routes completed' ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📍</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['total_points'] ?></div>
                <div class="stat-label"><?= $lang === 'ru' ? 'Точек пройдено' : 'Points completed' ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['achievements_count'] ?></div>
                <div class="stat-label"><?= t('achievements') ?></div>
            </div>
        </div>
    </div>
    <?php if (!empty($active)): ?>
    <section class="section">
        <h2 class="section-title"><?= $lang === 'ru' ? 'Активные квесты' : 'Active quests' ?></h2>
        <?php foreach ($active as $a):
            $total_pts = getDB()->fetch('SELECT COUNT(*) as c FROM points WHERE route_id = ?', [$a['route_id']])['c'];
            $done = (int)($a['points_completed'] ?? 0);
            $pct = $total_pts > 0 ? round($done / $total_pts * 100) : 0;
        ?>
        <div class="quest-card" style="margin-bottom:1rem;">
            <h3><?= getLocalizedField($a, 'name', $lang) ?></h3>
            <p><?= getLocalizedField($a, 'city_name', $lang) ?></p>
            <div class="progress-bar" style="height:8px;background:var(--bg-tertiary);border-radius:4px;overflow:hidden;margin:0.75rem 0;">
                <div style="height:100%;width:<?= $pct ?>%;background:var(--primary);transition:width 0.3s;"></div>
            </div>
            <p class="text-muted text-small"><?= $done ?> / <?= $total_pts ?> <?= $lang === 'ru' ? 'точек' : 'points' ?> (<?= $pct ?>%)</p>
            <a href="/quest/point.php?progress_id=<?= $a['id'] ?>" class="btn btn-primary"><?= $lang === 'ru' ? 'Продолжить' : 'Continue' ?></a>
        </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
    <div style="margin-top:2rem;">
        <a href="/routes.php" class="btn btn-primary"><?= t('routes') ?></a>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>