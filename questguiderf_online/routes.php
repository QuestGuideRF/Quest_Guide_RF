<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$city_filter = isset($_GET['city']) ? (int)$_GET['city'] : 0;
$where = ['r.is_active = 1'];
$params = [];
if ($city_filter) {
    $where[] = 'r.city_id = ?';
    $params[] = $city_filter;
}
$routes = getDB()->fetchAll(
    'SELECT r.*, c.name as city_name, c.name_en as city_name_en,
            (SELECT COUNT(*) FROM points WHERE route_id = r.id) as points_count,
            (SELECT AVG(rating) FROM reviews WHERE route_id = r.id AND is_approved = 1 AND is_hidden = 0) as avg_rating
     FROM routes r
     JOIN cities c ON r.city_id = c.id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY r.order, r.name',
    $params
);
$cities = getDB()->fetchAll('SELECT id, name, name_en FROM cities WHERE is_active = 1 ORDER BY name');
$page_title = t('routes');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <h1 class="page-header"><?= t('routes') ?></h1>
    <?php if (!empty($cities)): ?>
    <div style="margin-bottom:1.5rem;">
        <a href="/routes.php" class="btn btn-outline"><?= $lang === 'ru' ? 'Все' : 'All' ?></a>
        <?php foreach ($cities as $c): ?>
        <a href="/routes.php?city=<?= $c['id'] ?>" class="btn btn-outline"><?= getLocalizedField($c, 'name', $lang) ?></a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <div style="display:grid;gap:1rem;">
    <?php foreach ($routes as $r): ?>
        <div class="route-card" style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
            <div>
                <h3><?= e(getLocalizedField($r, 'name', $lang)) ?></h3>
                <p><?= e(getLocalizedField($r, 'city_name', $lang)) ?> · <?= $r['points_count'] ?: 0 ?> <?= t('points') ?></p>
                <?php if ($r['avg_rating']): ?>
                <span>⭐ <?= number_format($r['avg_rating'], 1) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <span style="font-weight:bold;margin-right:1rem;"><?= $r['price'] ?> <?= t('groshi') ?></span>
                <a href="/routes/view.php?id=<?= $r['id'] ?>" class="btn btn-primary"><?= $lang === 'ru' ? 'Подробнее' : 'Details' ?></a>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>