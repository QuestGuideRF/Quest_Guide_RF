<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../includes/init.php';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>QuestGuideRF - Личный кабинет</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--tg-theme-bg-color,
            color: var(--tg-theme-text-color,
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        .loading-container {
            text-align: center;
        }
        .loading-spinner {
            width: 48px;
            height: 48px;
            border: 4px solid var(--tg-theme-hint-color,
            border-top-color: var(--tg-theme-button-color,
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        .loading-text {
            color: var(--tg-theme-text-color,
            font-size: 16px;
        }
        .error-container {
            text-align: center;
            padding: 2rem;
        }
        .error-icon {
            font-size: 48px;
            margin-bottom: 1rem;
        }
        .error-message {
            color: var(--tg-theme-destructive-text-color,
            margin-bottom: 1rem;
            font-size: 16px;
        }
        .retry-button {
            background: var(--tg-theme-button-color,
            color: var(--tg-theme-button-text-color,
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 1rem;
        }
        .retry-button:active {
            opacity: 0.8;
        }
    </style>
</head>
<body>
    <div id="loading" class="loading-container">
        <div class="loading-spinner"></div>
        <div class="loading-text">Загрузка...</div>
    </div>
    <div id="error" class="error-container" style="display: none;">
        <div class="error-icon">⚠️</div>
        <div class="error-message" id="errorMessage"></div>
        <button class="retry-button" onclick="location.reload()">Повторить</button>
    </div>
    <script src="/webapp/assets/webapp.js"></script>
</body>
</html>