<?php
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$route_id = (int)($_GET['id'] ?? 0);
if (!$route_id) {
    header('Location: /routes.php');
    exit;
}
$route = getDB()->fetch(
    'SELECT r.*, c.name as city_name, c.name_en as city_name_en
     FROM routes r JOIN cities c ON r.city_id = c.id
     WHERE r.id = ? AND r.is_active = 1',
    [$route_id]
);
if (!$route) {
    header('Location: /404.php');
    exit;
}
$points_count = getDB()->fetch('SELECT COUNT(*) as c FROM points WHERE route_id = ?', [$route_id])['c'];
$balance = getBalance($user['id']);
$has_paid = getDB()->fetch(
    'SELECT 1 FROM payments WHERE user_id = ? AND route_id = ? AND status = "SUCCESS"',
    [$user['id'], $route_id]
);
$lang = getCurrentLanguage();
$page_title = getLocalizedField($route, 'name', $lang);
require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <div class="route-hero">
        <h1><?= e(getLocalizedField($route, 'name', $lang)) ?></h1>
        <p><?= e(getLocalizedField($route, 'city_name', $lang)) ?></p>
    </div>
    <?php if ($route['description']): ?>
    <div class="route-card" style="margin-bottom:1.5rem;">
        <h2><?= $lang === 'ru' ? 'Описание' : 'Description' ?></h2>
        <div><?= nl2br(e(getLocalizedField($route, 'description', $lang))) ?></div>
    </div>
    <?php endif; ?>
    <div class="route-card">
        <ul>
            <li><?= t('price') ?>: <?= $route['price'] ?> <?= t('groshi') ?></li>
            <li><?= t('points') ?>: <?= $points_count ?></li>
            <?php if ($route['estimated_duration']): ?>
            <li><?= t('duration') ?>: <?= formatDuration($route['estimated_duration']) ?></li>
            <?php endif; ?>
        </ul>
        <?php if ($has_paid || $balance >= $route['price']): ?>
        <a href="/quest/start.php?route_id=<?= $route_id ?>" class="btn btn-primary"><?= t('start_quest') ?></a>
        <?php else: ?>
        <p style="color:var(--warning);margin-bottom:1rem;">
            <?= $lang === 'ru' ? 'Недостаточно грошей. Пополните баланс или получите токен в боте.' : 'Insufficient balance. Top up or get token from bot.' ?>
        </p>
        <a href="/bank.php" class="btn btn-primary"><?= t('bank') ?></a>
        <?php endif; ?>
    </div>
    <a href="/routes.php" class="btn btn-outline" style="margin-top:1rem;">← <?= $lang === 'ru' ? 'К списку' : 'Back' ?></a>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>