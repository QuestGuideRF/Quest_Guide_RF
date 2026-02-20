<?php
<<<<<<< HEAD
/** Страница ошибки 404 */
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
http_response_code(404);
$page_title = 'Страница не найдена';
require_once __DIR__ . '/includes/init.php';
$is_logged_in = isLoggedIn();
if ($is_logged_in) {
    require_once __DIR__ . '/includes/header.php';
}
?>
<!DOCTYPE html>
<html lang="ru">
<?php if (!$is_logged_in): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<<<<<<< HEAD
    <title>404 - Страница не найдена | QuestGuideRF — квесты и экскурсии по Москве</title>
    <meta name="description" content="Страница не найдена. QuestGuideRF (квестгидрф) — интерактивные квесты и экскурсии по Москве. Перейдите на главную или откройте бота в Telegram.">
    <meta name="keywords" content="questguiderf, QuestGuideRF, квестгидрф, квесты Москва, экскурсии по Москве">
=======
    <title>404 - Страница не найдена | QuestGuideRF</title>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <link rel="stylesheet" href="/assets/css/style.css">
    <script>
        (function() {
            function getPreferredTheme() {
                const stored = localStorage.getItem('theme');
                if (stored) return stored;
                if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                    return 'dark';
                }
                return 'light';
            }
            function applyTheme(theme) {
                let actualTheme = theme;
                if (theme === 'auto') {
                    actualTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
                }
                document.documentElement.setAttribute('data-theme', actualTheme);
            }
            applyTheme(getPreferredTheme());
        })();
    </script>
</head>
<body>
<?php endif; ?>
<main class="main-content">
    <div class="container">
        <div class="error-page">
            <div class="error-code">404</div>
            <div class="error-icon">🗺️</div>
            <h1>Страница не найдена</h1>
            <p class="error-description">
                К сожалению, запрашиваемая страница не существует.<br>
                Возможно, вы перешли по устаревшей ссылке или ввели неверный адрес.
            </p>
            <div class="error-actions">
                <?php if ($is_logged_in): ?>
                    <a href="/dashboard.php" class="btn btn-primary">
                        <span>🏠</span>
                        На главную
                    </a>
                    <a href="/routes.php" class="btn btn-outline">
                        <span>🗺️</span>
                        К экскурсиям
                    </a>
                <?php else: ?>
                    <a href="/" class="btn btn-primary">
                        <span>🏠</span>
                        На главную
                    </a>
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-outline" target="_blank">
                        <span>🤖</span>
                        Открыть бота
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>
<?php if (!$is_logged_in): ?>
    <script src="/assets/js/theme.js"></script>
    <script src="/assets/js/main.js"></script>
</body>
</html>
<?php else: ?>
    <?php require_once __DIR__ . '/includes/footer.php'; ?>
<?php endif; ?>