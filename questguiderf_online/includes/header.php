<?php
require_once __DIR__ . '/i18n.php';
if (isset($_GET['lang']) && in_array($_GET['lang'], ['ru', 'en'])) {
    setLanguage($_GET['lang']);
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    $uri = preg_replace('/[?&]lang=[^&]*(&|$)/', '$1', $uri);
    $uri = preg_replace('/&$/', '', $uri);
    $uri = rtrim($uri, '?');
    header('Location: ' . ($uri ?: '/'));
    exit;
}
$header_user = isLoggedIn() ? getCurrentUser() : null;
$current_lang = getCurrentLanguage();
$page_title = $page_title ?? t('home');
$page_title_full = isset($page_title) ? e($page_title) . ' - ' . SITE_NAME : SITE_NAME;
$site_url = rtrim(SITE_URL, '/');
$og_image = $site_url . '/favicons/android-chrome-512x512.png';
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title_full ?></title>
    <meta name="description" content="QuestGuideRF - интерактивные экскурсии-квесты по России. Квесты-экскурсии онлайн.">
    <meta name="theme-color" content="#1f2937">
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="shortcut icon" href="/favicons/favicon.ico">
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        (function() {
            var theme = localStorage.getItem('theme') || '<?= e($_SESSION['theme'] ?? 'dark') ?>' || 'dark';
            if (theme === 'auto') theme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', theme);
        })();
    </script>
</head>
<body>
<header class="header">
    <div class="container">
        <nav class="navbar">
            <div class="navbar-brand">
                <a href="/dashboard.php">
                    <span class="logo">🎯</span>
                    <span class="brand-name"><?= SITE_NAME ?></span>
                </a>
            </div>
            <button class="navbar-toggler" id="navbar-toggler" aria-label="Menu">
                <span></span><span></span><span></span>
            </button>
            <div class="navbar-menu" id="navbar-menu">
                <a href="<?= $header_user ? '/dashboard.php' : '/' ?>" class="nav-link <?= in_array(basename($_SERVER['PHP_SELF'] ?? ''), ['dashboard.php', 'index.php']) ? 'active' : '' ?>">
                    <span class="nav-icon">🏠</span>
                    <span><?= t('home') ?></span>
                </a>
                <a href="/routes.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'routes.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🗺️</span>
                    <span><?= t('routes') ?></span>
                </a>
                <?php if ($header_user): ?>
                <a href="/photos.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'photos.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📸</span>
                    <span><?= t('photos') ?></span>
                </a>
                <a href="/bank.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'bank.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🏦</span>
                    <span><?= t('bank') ?></span>
                </a>
                <a href="/achievements.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'achievements.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🏆</span>
                    <span><?= t('achievements') ?></span>
                </a>
                <a href="/reviews.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'reviews.php' ? 'active' : '' ?>">
                    <span class="nav-icon">⭐</span>
                    <span><?= t('reviews') ?></span>
                </a>
                <a href="/certificates.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'certificates.php' ? 'active' : '' ?>">
                    <span class="nav-icon">📜</span>
                    <span><?= t('certificates') ?></span>
                </a>
                <a href="/partner.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'partner.php' ? 'active' : '' ?>">
                    <span class="nav-icon">🤝</span>
                    <span><?= t('partner') ?></span>
                </a>
                <a href="/settings.php" class="nav-link <?= basename($_SERVER['PHP_SELF'] ?? '') === 'settings.php' ? 'active' : '' ?>">
                    <span class="nav-icon">⚙️</span>
                    <span><?= t('settings') ?></span>
                </a>
                <?php endif; ?>
            </div>
            <div class="navbar-actions">
                <div class="language-switcher">
                    <a href="?lang=ru" class="nav-link <?= $current_lang === 'ru' ? 'active' : '' ?>" title="Русский" data-lang="ru">🇷🇺 RU</a>
                    <a href="?lang=en" class="nav-link <?= $current_lang === 'en' ? 'active' : '' ?>" title="English" data-lang="en">🇺🇸 EN</a>
                </div>
                <?php if ($header_user): ?>
                <div class="user-menu">
                    <button class="user-menu-toggle" id="user-menu-toggle">
                        <img src="<?= e(($header_user['photo_url'] ?? null) ?: getDefaultAvatar($header_user['first_name'] ?? 'User')) ?>"
                             alt="<?= e($header_user['first_name'] ?? 'User') ?>"
                             class="user-avatar-small">
                    </button>
                    <div class="user-menu-dropdown" id="user-menu-dropdown">
                        <div class="user-menu-header">
                            <img src="<?= e(($header_user['photo_url'] ?? null) ?: getDefaultAvatar($header_user['first_name'] ?? 'User')) ?>"
                                 alt="<?= e($header_user['first_name'] ?? 'User') ?>"
                                 class="user-avatar">
                            <div>
                                <div class="user-name"><?= e($header_user['first_name'] ?? 'User') ?></div>
                                <div class="user-username text-muted">@<?= e($header_user['username'] ?? 'user') ?></div>
                            </div>
                        </div>
                        <div class="user-menu-divider"></div>
                        <a href="/settings.php" class="user-menu-item">
                            <span>⚙️</span>
                            <?= $current_lang === 'ru' ? 'Настройки' : 'Settings' ?>
                        </a>
                        <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="user-menu-item" target="_blank">
                            <span>🤖</span>
                            <?= $current_lang === 'ru' ? 'Открыть бота' : 'Open bot' ?>
                        </a>
                        <div class="user-menu-divider"></div>
                        <a href="/logout.php" class="user-menu-item text-danger">
                            <span>🚪</span>
                            <?= t('logout') ?>
                        </a>
                    </div>
                </div>
                <?php else: ?>
                <a href="/" class="btn btn-primary"><?= t('login') ?></a>
                <?php endif; ?>
            </div>
        </nav>
    </div>
</header>
<main class="main">