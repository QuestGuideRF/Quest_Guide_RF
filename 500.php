<?php
http_response_code(500);
$page_title = 'Ошибка сервера';
try {
    require_once __DIR__ . '/includes/init.php';
    $is_logged_in = isLoggedIn();
    if ($is_logged_in) {
        require_once __DIR__ . '/includes/header.php';
    }
} catch (Exception $e) {
    $is_logged_in = false;
}
?>
<!DOCTYPE html>
<html lang="ru">
<?php if (!$is_logged_in): ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Ошибка сервера | QuestGuideRF</title>
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
            <div class="error-code">500</div>
            <div class="error-icon">⚠️</div>
            <h1>Ошибка сервера</h1>
            <p class="error-description">
                Произошла внутренняя ошибка сервера.<br>
                Мы уже работаем над её устранением. Пожалуйста, попробуйте позже.
            </p>
            <p class="text-muted text-small">
                Если проблема повторяется, свяжитесь с поддержкой через Telegram бота.
            </p>
            <div class="error-actions">
                <a href="/" class="btn btn-primary">
                    <span>🏠</span>
                    На главную
                </a>
                <a href="https://t.me/questguiderf_bot" class="btn btn-outline" target="_blank">
                    <span>🤖</span>
                    Связаться с поддержкой
                </a>
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