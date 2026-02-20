<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$ref_link = SITE_URL . '/auth/telegram.php?ref=' . ($user['id'] ?? '');
$ref_count = 0;
if (isset($user['id'])) {
    $r = getDB()->fetch('SELECT COUNT(*) as c FROM users WHERE referred_by_id = ?', [$user['id']]);
    $ref_count = $r['c'] ?? 0;
}
$page_title = t('partner');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>🤝 <?= t('partner') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Партнёрская программа' : 'Partner program' ?></p>
    </div>
    <div class="quest-card" style="max-width:600px;">
        <p><?= $lang === 'ru' ? 'Приглашайте друзей! Отправьте им ссылку или воспользуйтесь ботом.' : 'Invite friends! Send them the link or use the bot.' ?></p>
        <div style="margin:1rem 0;">
            <label style="display:block;margin-bottom:0.5rem;font-weight:500;"><?= $lang === 'ru' ? 'Ваша реферальная ссылка' : 'Your referral link' ?></label>
            <input type="text" value="<?= e($ref_link) ?>" readonly style="width:100%;padding:0.75rem;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-tertiary);color:var(--text-primary);">
        </div>
        <div class="stat-card" style="margin-top:1rem;">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <div class="stat-value"><?= $ref_count ?></div>
                <div class="stat-label"><?= $lang === 'ru' ? 'Приглашено' : 'Invited' ?></div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>