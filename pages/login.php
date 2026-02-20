<?php
if (!defined('APP_INIT')) {
    require_once __DIR__ . '/../includes/init.php';
}
$page_title = 'Вход';
$page_description = "Войдите в личный кабинет QuestGuideRF через Telegram. Просматривайте свои экскурсии, достижения и фотографии.";
$page_keywords = "войти, авторизация, QuestGuideRF, Telegram, личный кабинет, экскурсии, квесты";
$current_url = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$site_url = rtrim(SITE_URL, '/');
$og_image = $site_url . '/favicons/android-chrome-512x512.png';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($page_title) ?> - <?= SITE_NAME ?></title>
<<<<<<< HEAD
=======
    <!-- Основные SEO мета-теги -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <meta name="description" content="<?= e($page_description) ?>">
    <meta name="keywords" content="<?= e($page_keywords) ?>">
    <meta name="author" content="<?= SITE_NAME ?>">
    <meta name="robots" content="noindex, nofollow">
    <meta name="language" content="Russian">
    <link rel="canonical" href="<?= $current_url ?>">
<<<<<<< HEAD
=======
    <!-- Open Graph / Facebook -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= $current_url ?>">
    <meta property="og:title" content="<?= e($page_title) ?> - <?= SITE_NAME ?>">
    <meta property="og:description" content="<?= e($page_description) ?>">
    <meta property="og:image" content="<?= $og_image ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    <meta property="og:locale" content="ru_RU">
<<<<<<< HEAD
=======
    <!-- Twitter Card -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?= $current_url ?>">
    <meta name="twitter:title" content="<?= e($page_title) ?> - <?= SITE_NAME ?>">
    <meta name="twitter:description" content="<?= e($page_description) ?>">
    <meta name="twitter:image" content="<?= $og_image ?>">
<<<<<<< HEAD
    <meta name="theme-color" content="#4a90e2">
=======
    <!-- Дополнительные мета-теги -->
    <meta name="theme-color" content="#4a90e2">
    <!-- Favicons -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="shortcut icon" href="/favicons/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="logo">🎯</div>
                <h1>QuestGuideRF</h1>
                <p>Интерактивные экскурсии-квесты</p>
            </div>
            <div class="login-content">
                <p class="text-muted mb-4">
                    Войдите через Telegram, чтобы увидеть свою статистику,
                    пройденные маршруты и достижения.
                </p>
                <div class="text-center mb-4">
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>?start=auth" target="_blank" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 16px; font-weight: 600;">
                        🔐 Войти через Telegram
                    </a>
                </div>
                <div class="login-info mt-4">
                    <p class="text-small text-muted">
                        💡 Для входа вам нужен аккаунт Telegram.
                        Если вы еще не пользовались ботом, начните с команды /start.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <script src="/assets/js/theme.js"></script>
</body>