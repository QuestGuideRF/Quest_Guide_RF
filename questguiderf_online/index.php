<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
if (isLoggedIn()) {
    header('Location: /dashboard.php');
    exit;
}
$error = $_GET['error'] ?? '';
$token = $_GET['token'] ?? '';
$page_title = t('login');
require_once __DIR__ . '/includes/header.php';
$current_lang = getCurrentLanguage();
$msg = '';
if ($error === 'no_token') $msg = $current_lang === 'ru' ? 'Укажите токен в ссылке.' : 'Provide token in the URL.';
elseif ($error === 'invalid_token') $msg = $current_lang === 'ru' ? 'Токен недействителен или уже использован.' : 'Token invalid or already used.';
elseif ($error === 'expired_token') $msg = $current_lang === 'ru' ? 'Срок действия токена истёк.' : 'Token expired.';
elseif ($error === 'user_not_found') $msg = $current_lang === 'ru' ? 'Пользователь не найден. Сначала запустите бота.' : 'User not found. Start the bot first.';
?>
<div class="container" style="max-width:480px;margin:4rem auto;text-align:center;">
    <h1 style="margin-bottom:1.5rem;"><?= SITE_NAME ?></h1>
    <p style="color:var(--text-secondary);margin-bottom:2rem;">
        <?= $current_lang === 'ru' ? 'Интерактивные квесты‑экскурсии онлайн. Войдите по токену из бота.' : 'Interactive quest tours online. Login with token from the bot.' ?>
    </p>
    <?php if ($msg): ?>
    <p style="color:var(--danger);margin-bottom:1rem;"><?= e($msg) ?></p>
    <?php endif; ?>
    <?php if ($token): ?>
    <p><a href="/auth/telegram.php?token=<?= e($token) ?>" class="btn btn-primary"><?= t('login') ?></a></p>
    <?php else: ?>
    <div class="card" style="padding:2rem;text-align:left;">
        <p style="margin-bottom:1rem;">
            <?= $current_lang === 'ru' ? 'Отправьте <strong>/web</strong> в боте' : 'Send <strong>/web</strong> in the bot' ?>
            <a href="https://t.me/<?= BOT_USERNAME ?>" target="_blank">@<?= BOT_USERNAME ?></a>
            <?= $current_lang === 'ru' ? 'и перейдите по ссылке с токеном.' : 'and follow the link with the token.' ?>
        </p>
        <p style="margin-bottom:1rem;">
            <?= $current_lang === 'ru' ? 'Или вставьте токен из ссылки:' : 'Or paste the token from the link:' ?>
        </p>
        <form method="get" action="/auth/telegram.php">
            <input type="text" name="token" placeholder="token" style="width:100%;padding:0.75rem;margin-bottom:1rem;border-radius:0.5rem;border:1px solid var(--border-color);">
            <button type="submit" class="btn btn-primary"><?= t('login') ?></button>
        </form>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>