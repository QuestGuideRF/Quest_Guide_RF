<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$tb = getDB()->fetch('SELECT * FROM token_balances WHERE user_id = ?', [$user['id']]);
$payments = getDB()->fetchAll(
    'SELECT p.*, r.name as route_name, r.name_en as route_name_en
     FROM payments p
     JOIN routes r ON p.route_id = r.id
     WHERE p.user_id = ? AND p.status = "SUCCESS"
     ORDER BY p.created_at DESC LIMIT 50',
    [$user['id']]
);
$page_title = t('bank');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>🏦 <?= t('bank') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Баланс и история покупок' : 'Balance and purchase history' ?></p>
    </div>
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format((float)($tb['balance'] ?? 0), 0, ',', ' ') ?></div>
                <div class="stat-label"><?= t('balance') ?> (<?= t('groshi') ?>)</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📥</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format((float)($tb['total_deposited'] ?? 0), 0, ',', ' ') ?></div>
                <div class="stat-label"><?= $lang === 'ru' ? 'Пополнено' : 'Deposited' ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📤</div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format((float)($tb['total_spent'] ?? 0), 0, ',', ' ') ?></div>
                <div class="stat-label"><?= $lang === 'ru' ? 'Потрачено' : 'Spent' ?></div>
            </div>
        </div>
    </div>
    <section class="section">
        <h2 class="section-title"><?= $lang === 'ru' ? 'История покупок' : 'Purchase history' ?></h2>
        <?php if (empty($payments)): ?>
        <div class="empty-state">
            <p class="text-muted"><?= $lang === 'ru' ? 'Пока нет покупок' : 'No purchases yet' ?></p>
        </div>
        <?php else: ?>
        <div class="route-list">
            <?php foreach ($payments as $p): ?>
            <div class="quest-card" style="margin-bottom:1rem;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;">
                    <div>
                        <strong><?= e(getLocalizedField($p, 'route_name', $lang)) ?></strong>
                        <div class="text-muted text-small"><?= date('d.m.Y H:i', strtotime($p['created_at'])) ?></div>
                    </div>
                    <span class="badge badge-success"><?= number_format($p['amount'], 0, ',', ' ') ?> <?= t('groshi') ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </section>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>