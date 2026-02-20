<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../includes/init.php';
$token = $_GET['token'] ?? '';
if (empty($token)) {
    showAuthError('Токен не указан', 'Используйте команду /web в боте для получения ссылки.');
}
$session = getDB()->fetch(
    'SELECT telegram_id, expires_at FROM user_sessions
     WHERE token = ? AND is_used = FALSE',
    [$token]
);
if (!$session) {
    showAuthError('Токен недействителен', 'Токен недействителен или уже использован. Получите новую ссылку в боте: отправьте /web');
}
$expires_at_utc = new DateTime($session['expires_at'], new DateTimeZone('UTC'));
$now_utc = new DateTime('now', new DateTimeZone('UTC'));
if ($now_utc > $expires_at_utc) {
    showAuthError('Токен истек', 'Срок действия токена истек (5 минут). Получите новую ссылку в боте: отправьте /web');
}
getDB()->query(
    'UPDATE user_sessions SET is_used = TRUE, used_at = NOW() WHERE token = ?',
    [$token]
);
$user = getDB()->fetch(
    'SELECT * FROM users WHERE telegram_id = ?',
    [$session['telegram_id']]
);
if (!$user) {
    showAuthError('Пользователь не найден', 'Пользователь не найден в системе. Сначала запустите бота: /start');
}
loginUser($user);
header('Location: /dashboard.php');
exit;
function showAuthError($title, $message) {
    http_response_code(401);
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="mobile-web-app-capable" content="yes">
        <title>Ошибка авторизации - <?= SITE_NAME ?></title>
        <link rel="stylesheet" href="/assets/css/style.css">
        <style>
            .error-page {
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 2rem;
                background: var(--bg-primary);
            }
            .error-container {
                max-width: 500px;
                width: 100%;
                text-align: center;
            }
            .error-icon {
                font-size: 4rem;
                margin-bottom: 1.5rem;
            }
            .error-title {
                font-size: 2rem;
                font-weight: bold;
                color: var(--text-primary);
                margin-bottom: 1rem;
            }
            .error-message {
                color: var(--text-secondary);
                margin-bottom: 2rem;
                line-height: 1.6;
            }
            .error-actions {
                display: flex;
                flex-direction: column;
                gap: 1rem;
            }
            .btn {
                display: inline-block;
                padding: 0.75rem 1.5rem;
                border-radius: var(--radius-md);
                text-decoration: none;
                font-weight: 500;
                transition: var(--transition);
            }
            .btn-primary {
                background: var(--primary);
                color: white;
            }
            .btn-primary:hover {
                background: var(--primary-hover);
            }
            .btn-outline {
                background: transparent;
                color: var(--primary);
                border: 2px solid var(--primary);
            }
            .btn-outline:hover {
                background: var(--primary);
                color: white;
            }
        </style>
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
        <div class="error-page">
            <div class="error-container">
                <div class="error-icon">🔒</div>
                <h1 class="error-title"><?= htmlspecialchars($title) ?></h1>
                <p class="error-message"><?= htmlspecialchars($message) ?></p>
                <div class="error-actions">
                    <a href="/" class="btn btn-primary">
                        🏠 На главную
                    </a>
                    <a href="https://t.me/<?= BOT_USERNAME ?>" class="btn btn-outline" target="_blank">
                        🤖 Открыть бота
                    </a>
                </div>
            </div>
        </div>
        <script src="/assets/js/theme.js"></script>
        <script src="/assets/js/main.js"></script>
    </body>
    </html>
    <?php
    exit;
}
?>