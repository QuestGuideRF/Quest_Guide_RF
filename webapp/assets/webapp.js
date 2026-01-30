function getInitData() {
    if (window.Telegram && window.Telegram.WebApp) {
        return window.Telegram.WebApp.initData;
    }
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('tgWebAppData') || urlParams.get('_auth');
}
async function authenticateWithTelegram() {
    const initData = getInitData();
    if (!initData) {
        console.error('Init data not found');
        showError('Не удалось получить данные авторизации. Убедитесь, что вы открыли приложение через Telegram.');
        return false;
    }
    try {
        const response = await fetch('/webapp/auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                initData: initData
            }),
            credentials: 'include'
        });
        const result = await response.json();
        if (result.success) {
            window.location.href = '/dashboard.php';
        } else {
            showError(result.message || 'Ошибка авторизации');
        }
    } catch (error) {
        console.error('Auth error:', error);
        showError('Ошибка соединения с сервером. Проверьте подключение к интернету.');
    }
}
function showError(message) {
    const loadingDiv = document.getElementById('loading');
    const errorDiv = document.getElementById('error');
    const errorMessage = document.getElementById('errorMessage');
    if (loadingDiv) loadingDiv.style.display = 'none';
    if (errorDiv) errorDiv.style.display = 'block';
    if (errorMessage) errorMessage.textContent = message;
}
document.addEventListener('DOMContentLoaded', function() {
    if (window.Telegram && window.Telegram.WebApp) {
        window.Telegram.WebApp.expand();
        const theme = window.Telegram.WebApp.colorScheme;
        document.documentElement.setAttribute('data-theme', theme);
        window.Telegram.WebApp.onEvent('themeChanged', function() {
            const newTheme = window.Telegram.WebApp.colorScheme;
            document.documentElement.setAttribute('data-theme', newTheme);
        });
        window.Telegram.WebApp.BackButton.show();
        window.Telegram.WebApp.BackButton.onClick(function() {
            window.Telegram.WebApp.close();
        });
        window.Telegram.WebApp.enableClosingConfirmation();
    }
    authenticateWithTelegram();
});