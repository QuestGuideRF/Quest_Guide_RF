<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$certs = getDB()->fetchAll(
    'SELECT c.*, r.name as route_name, r.name_en as route_name_en
     FROM certificates c
     JOIN routes r ON c.route_id = r.id
     WHERE c.user_id = ?
     ORDER BY c.created_at DESC',
    [$user['id']]
);
$completed_without_cert = getDB()->fetchAll(
    'SELECT up.id as progress_id, r.id as route_id, r.name, r.name_en
     FROM user_progress up
     JOIN routes r ON up.route_id = r.id
     WHERE up.user_id = ? AND up.status IN ("COMPLETED","completed")
     AND NOT EXISTS (SELECT 1 FROM certificates c WHERE c.progress_id = up.id)',
    [$user['id']]
);
$page_title = t('certificates');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>📜 <?= t('certificates') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Сертификаты за пройденные квесты' : 'Certificates for completed quests' ?></p>
    </div>
    <?php if (empty($certs) && empty($completed_without_cert)): ?>
    <div class="empty-state">
        <div class="empty-icon">📜</div>
        <p><?= $lang === 'ru' ? 'У вас пока нет сертификатов' : 'No certificates yet' ?></p>
        <p class="text-muted"><?= $lang === 'ru' ? 'Завершите квест, чтобы получить сертификат' : 'Complete a quest to get a certificate' ?></p>
        <a href="/routes.php" class="btn btn-primary"><?= t('routes') ?></a>
    </div>
    <?php else: ?>
    <?php foreach ($certs as $c): ?>
    <div class="quest-card" style="margin-bottom:1rem;">
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
            <div>
                <strong><?= e(getLocalizedField($c, 'route_name', $lang)) ?></strong>
            </div>
            <?php if (!empty($c['file_path']) && file_exists(BASE_DIR . '/' . ltrim($c['file_path'], '/'))): ?>
            <a href="/<?= ltrim($c['file_path'], '/') ?>" target="_blank" class="btn btn-outline"><?= $lang === 'ru' ? 'Скачать' : 'Download' ?></a>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (!empty($completed_without_cert)): ?>
    <p class="text-muted" style="margin-top:1.5rem;"><?= $lang === 'ru' ? 'Сертификаты для этих маршрутов можно создать в боте.' : 'Certificates for these routes can be created in the bot.' ?></p>
    <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>