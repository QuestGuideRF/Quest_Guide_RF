<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$page_title = t('settings_title');
$page_description = $current_lang === 'en'
    ? "QuestGuideRF account settings. Manage your profile, theme, and other preferences."
    : "Настройки вашего аккаунта QuestGuideRF. Управляйте профилем, темой оформления и другими параметрами.";
$page_keywords = $current_lang === 'en'
    ? "settings, profile, account, preferences, QuestGuideRF"
    : "настройки, профиль, аккаунт, параметры, QuestGuideRF";
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>⚙️ <?= t('settings_title') ?></h1>
        <p class="text-muted"><?= t('settings_subtitle') ?></p>
    </div>
    <div class="settings-grid">
        <!-- Профиль -->
        <div class="settings-card">
            <h2>👤 <?= t('profile') ?></h2>
            <div class="profile-info">
                <div class="avatar-upload-container">
                    <img src="<?= e($user['photo_url'] ?: getDefaultAvatar($user['first_name'])) ?>"
                         alt="<?= e($user['first_name']) ?>"
                         class="profile-avatar"
                         id="profile-avatar">
                    <div class="avatar-upload-overlay">
                        <label for="avatar-input" class="avatar-upload-btn">
                            📷 <?= t('change_avatar') ?>
                        </label>
                    </div>
                    <input type="file" id="avatar-input" accept="image/*" style="display: none;">
                </div>
                <div>
                    <h3><?= e($user['first_name']) ?> <?= e($user['last_name']) ?></h3>
                    <p class="text-muted">@<?= e($user['username'] ?: 'user') ?></p>
                    <p class="text-muted text-small">
                        Telegram ID: <?= e($user['telegram_id']) ?>
                    </p>
                </div>
            </div>
            <p class="text-muted text-small mt-3">
                <?= t('avatar_upload_hint') ?>
            </p>
            <div id="upload-status" class="upload-status" style="display: none;"></div>
        </div>
        <!-- Язык -->
        <div class="settings-card">
            <h2>🌍 <?= t('language_setting') ?></h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3><?= t('language_setting') ?></h3>
                    <p class="text-muted"><?= t('language_description') ?></p>
                </div>
                <div class="language-switcher-settings" style="display: flex; gap: 10px; margin-top: 15px;">
                    <a href="?lang=ru" class="btn <?= $current_lang === 'ru' ? 'btn-primary' : 'btn-outline-primary' ?>" style="flex: 1;">
                        🇷🇺 <?= t('language_russian') ?>
                    </a>
                    <a href="?lang=en" class="btn <?= $current_lang === 'en' ? 'btn-primary' : 'btn-outline-primary' ?>" style="flex: 1;">
                        🇺🇸 <?= t('language_english') ?>
                    </a>
                </div>
            </div>
        </div>
        <!-- Внешний вид -->
        <div class="settings-card">
            <h2>🎨 <?= t('appearance') ?></h2>
            <div class="setting-item">
                <div class="setting-info">
                    <h3><?= t('theme') ?></h3>
                    <p class="text-muted"><?= t('theme_description') ?></p>
                </div>
                <div class="theme-switcher">
                    <button class="theme-option" data-theme="light">
                        <span>☀️</span>
                        <span><?= t('theme_light') ?></span>
                    </button>
                    <button class="theme-option" data-theme="dark">
                        <span>🌙</span>
                        <span><?= t('theme_dark') ?></span>
                    </button>
                    <button class="theme-option" data-theme="auto">
                        <span>⚙️</span>
                        <span><?= t('theme_auto') ?></span>
                    </button>
                </div>
            </div>
        </div>
        <!-- Статистика аккаунта -->
        <div class="settings-card">
            <h2>📊 <?= t('statistics') ?></h2>
            <div class="account-stats">
                <div class="stat-row">
                    <span><?= t('registration_date') ?></span>
                    <strong><?= formatDate($user['created_at']) ?></strong>
                </div>
                <div class="stat-row">
                    <span><?= t('last_login') ?></span>
                    <strong><?= formatDateTime($user['last_login']) ?></strong>
                </div>
            </div>
        </div>
        <!-- Telegram бот -->
        <div class="settings-card">
            <h2>🤖 <?= t('telegram_bot') ?></h2>
            <p class="text-muted mb-3">
                <?= t('telegram_bot_description') ?>
            </p>
            <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary" target="_blank">
                <span>🤖</span>
                <?= t('open_bot') ?>
            </a>
        </div>
        <!-- Выход -->
        <div class="settings-card danger">
            <h2>🚪 <?= t('logout') ?></h2>
            <p class="text-muted mb-3">
                <?= t('logout_description') ?>
            </p>
            <a href="/api/logout.php" class="btn btn-danger">
                <?= t('logout_button') ?>
            </a>
        </div>
    </div>
</div>
<script>
function applyTheme(theme) {
    let actualTheme = theme;
    if (theme === 'auto') {
        actualTheme = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    document.documentElement.setAttribute('data-theme', actualTheme);
}
document.querySelectorAll('.theme-option').forEach(btn => {
    const theme = btn.dataset.theme;
    const currentTheme = localStorage.getItem('theme') || 'auto';
    if (theme === currentTheme) {
        btn.classList.add('active');
    }
    btn.addEventListener('click', () => {
        document.querySelectorAll('.theme-option').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        localStorage.setItem('theme', theme);
        applyTheme(theme);
    });
});
document.getElementById('avatar-input').addEventListener('change', async function(e) {
    const file = e.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        showUploadStatus('❌ <?= t('file_too_large') ?>', 'error');
        return;
    }
    if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
        showUploadStatus('❌ <?= t('invalid_file_type') ?>', 'error');
        return;
    }
    showUploadStatus('⏳ <?= t('uploading') ?>', 'info');
    const formData = new FormData();
    formData.append('avatar', file);
    try {
        const response = await fetch('/api/upload_avatar.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        if (data.success) {
            document.getElementById('profile-avatar').src = data.photo_url + '?t=' + Date.now();
            showUploadStatus('✅ <?= t('avatar_updated') ?>', 'success');
            const headerAvatar = document.querySelector('.user-avatar img');
            if (headerAvatar) {
                headerAvatar.src = data.photo_url + '?t=' + Date.now();
            }
        } else {
            showUploadStatus('❌ ' + data.error, 'error');
        }
    } catch (error) {
        showUploadStatus('❌ <?= t('upload_error') ?>: ' + error.message, 'error');
    }
});
function showUploadStatus(message, type) {
    const status = document.getElementById('upload-status');
    status.textContent = message;
    status.className = 'upload-status upload-status-' + type;
    status.style.display = 'block';
    if (type === 'success') {
        setTimeout(() => {
            status.style.display = 'none';
        }, 3000);
    }
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>