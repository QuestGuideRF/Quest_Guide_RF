<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$token_balance = null;
try {
    $pdo = getDB()->getConnection();
    $stmt = $pdo->prepare('SELECT * FROM token_balances WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    $token_balance = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
$payments = getDB()->fetchAll(
    'SELECT p.*, r.name as route_name, r.name_en as route_name_en, c.name as city_name, c.name_en as city_name_en
     FROM payments p
     JOIN routes r ON p.route_id = r.id
     JOIN cities c ON r.city_id = c.id
     WHERE p.user_id = ?
     ORDER BY p.created_at DESC',
    [$user['id']]
);
$total_spent = array_sum(array_column(
    array_filter($payments, function($p) { return $p['status'] == 'success'; }),
    'amount'
));
$page_title = t('bank_title');
$page_description = $current_lang === 'en'
    ? "QuestGuideRF token bank - balance, top-ups and purchase history of quest-excursions."
    : "Банк токенов QuestGuideRF - баланс, пополнения и история покупок экскурсий-квестов.";
$page_keywords = $current_lang === 'en'
    ? "token bank, balance, purchases, payments, QuestGuideRF"
    : "банк токенов, баланс, покупки, платежи, QuestGuideRF";
require_once __DIR__ . '/includes/header.php';
?>
<main class="main-content">
<div class="container">
    <div class="page-header">
        <h1>🏦 <?= t('bank_title') ?></h1>
        <p class="text-muted"><?= t('bank_subtitle') ?></p>
    </div>
    <?php if ($token_balance !== null): ?>
    <!-- Token balance stats -->
    <div class="stats-row" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon">💰</div>
            <div class="stat-content">
                <div class="stat-value"><?= formatPrice((float)($token_balance['balance'] ?? 0)) ?></div>
                <div class="stat-label"><?= t('bank_balance') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📥</div>
            <div class="stat-content">
                <div class="stat-value"><?= formatPrice((float)($token_balance['total_deposited'] ?? 0)) ?></div>
                <div class="stat-label"><?= t('bank_total_deposited') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📤</div>
            <div class="stat-content">
                <div class="stat-value"><?= formatPrice((float)($token_balance['total_spent'] ?? 0)) ?></div>
                <div class="stat-label"><?= t('bank_total_spent') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">💸</div>
            <div class="stat-content">
                <div class="stat-value"><?= formatPrice((float)($token_balance['total_transferred_out'] ?? 0) + (float)($token_balance['total_transferred_in'] ?? 0)) ?></div>
                <div class="stat-label"><?= t('bank_total_transferred') ?></div>
            </div>
        </div>
    </div>
    <p class="text-muted" style="margin-bottom: 1.5rem;">
        <?= t('bank_bot_hint') ?>
        <a href="https://t.me/<?= e(BOT_USERNAME) ?>?start=token" target="_blank">@<?= e(BOT_USERNAME) ?></a>
    </p>
    <?php endif; ?>
    <!-- Purchases section (moved from Payments page) -->
    <section class="section">
        <div class="section-header">
            <h2>💳 <?= t('bank_purchases') ?></h2>
        </div>
        <?php if (!empty($payments)): ?>
        <div class="stats-row" style="margin-bottom: 1.5rem;">
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-content">
                    <div class="stat-value"><?= formatPrice($total_spent) ?></div>
                    <div class="stat-label"><?= t('total_spent') ?></div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🎫</div>
                <div class="stat-content">
                    <div class="stat-value"><?= count($payments) ?></div>
                    <div class="stat-label"><?= t('total_purchases') ?></div>
                </div>
            </div>
        </div>
        <div class="payments-list">
            <?php foreach ($payments as $payment): ?>
            <div class="payment-card">
                <div class="payment-header">
                    <div class="payment-icon">
                        <?= $payment['status'] == 'success' ? '✅' : ($payment['status'] == 'pending' ? '⏳' : '❌') ?>
                    </div>
                    <div class="payment-info">
                        <h3><?= getLocalizedName(['name' => $payment['route_name'], 'name_en' => $payment['route_name_en'] ?? null]) ?></h3>
                        <p class="text-muted"><?= getLocalizedName(['name' => $payment['city_name'], 'name_en' => $payment['city_name_en'] ?? null]) ?></p>
                    </div>
                    <div class="payment-amount">
                        <?= formatPrice($payment['amount']) ?>
                    </div>
                </div>
                <div class="payment-details">
                    <div class="payment-meta">
                        <span><?= getPaymentStatusBadge($payment['status']) ?></span>
                        <span class="text-muted">
                            <?= formatDateTime($payment['created_at']) ?>
                        </span>
                    </div>
                    <?php if (!empty($payment['telegram_payment_charge_id'])): ?>
                    <div class="payment-id text-muted text-small">
                        <?= t('payment_id') ?>: <?= e($payment['telegram_payment_charge_id']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">💳</div>
            <h2><?= t('no_purchases_yet') ?></h2>
            <p><?= t('buy_first_route') ?></p>
            <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary" target="_blank">
                <?= t('open_bot') ?>
            </a>
        </div>
        <?php endif; ?>
    </section>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>