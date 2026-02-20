<?php
<<<<<<< HEAD
/** Настройки пользователя */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
=======
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
    <?php if (!$user): ?>
    <div class="settings-card" style="max-width: 400px; margin: 2rem auto; text-align: center; padding: 2rem;">
        <h2 style="margin-bottom: 1rem;"><?= $current_lang === 'en' ? 'Sign in to your account' : 'Войти в аккаунт' ?></h2>
        <p class="text-muted" style="margin-bottom: 1.5rem;">
            <?= $current_lang === 'en' ? 'Use Telegram to sign in and access your profile, settings and statistics.' : 'Войдите через Telegram, чтобы получить доступ к профилю, настройкам и статистике.' ?>
        </p>
        <a href="/pages/login.php" class="btn btn-primary btn-lg"><?= $current_lang === 'en' ? 'Sign in with Telegram' : 'Войти через Telegram' ?></a>
    </div>
    <?php else: ?>
    <div class="settings-grid">
=======
    <div class="settings-grid">
        <!-- Профиль -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
=======
        <!-- Язык -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
=======
        <!-- Внешний вид -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
        <div class="settings-card">
            <h2>🔒 <?= $current_lang === 'en' ? 'Privacy' : 'Приватность' ?></h2>
            <div class="setting-item privacy-setting">
                <p class="privacy-desc text-muted">
                    <?= $current_lang === 'en'
                        ? 'When profile is public, other users can view your profile, achievements and reviews.'
                        : 'При публичном профиле другие пользователи видят ваш профиль, достижения и отзывы.' ?>
                </p>
                <div class="privacy-options">
                    <label class="privacy-option <?= ($user['is_profile_public'] ?? 1) ? 'active' : '' ?>">
                        <input type="radio" name="profile_visibility" value="1" <?= ($user['is_profile_public'] ?? 1) ? 'checked' : '' ?>>
                        <span class="privacy-option-icon">🌐</span>
                        <span class="privacy-option-label"><?= $current_lang === 'en' ? 'Public' : 'Публичный' ?></span>
                    </label>
                    <label class="privacy-option <?= !($user['is_profile_public'] ?? 1) ? 'active' : '' ?>">
                        <input type="radio" name="profile_visibility" value="0" <?= !($user['is_profile_public'] ?? 1) ? 'checked' : '' ?>>
                        <span class="privacy-option-icon">🔒</span>
                        <span class="privacy-option-label"><?= $current_lang === 'en' ? 'Hidden' : 'Скрыт' ?></span>
                    </label>
                </div>
                <p class="privacy-profile-link text-muted text-small">
                    <?= $current_lang === 'en' ? 'Your profile:' : 'Ваш профиль:' ?>
                    <a href="/<?= e($user['username'] ?: $user['id']) ?>" target="_blank" rel="noopener"><?= SITE_URL ?>/<?= e($user['username'] ?: $user['id']) ?></a>
                </p>
            </div>
        </div>
=======
        <!-- Статистика аккаунта -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
=======
        <!-- Telegram бот -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
=======
        <!-- Выход -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
    <?php endif; ?>
</div>
<?php if ($user): ?>
=======
</div>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
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
<<<<<<< HEAD
document.querySelectorAll('input[name="profile_visibility"]').forEach(function(radio) {
    radio.addEventListener('change', async function() {
        const isPublic = this.value === '1';
        var options = document.querySelectorAll('.privacy-option');
        options.forEach(function(el) { el.classList.remove('active'); });
        this.closest('.privacy-option').classList.add('active');
        try {
            var response = await fetch('/api/update_privacy.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({is_profile_public: isPublic ? 1 : 0})
            });
            var data = await response.json();
            if (!data.success) {
                var targetVal = String(data.is_profile_public ?? 1);
                options.forEach(function(el) {
                    var input = el.querySelector('input');
                    input.checked = input.value === targetVal;
                    el.classList.toggle('active', input.checked);
                });
                alert(data.error || 'Error');
            }
        } catch (e) {
            options.forEach(function(el) {
                var input = el.querySelector('input');
                input.checked = input.value === '1';
                el.classList.toggle('active', input.checked);
            });
            alert('Error: ' + e.message);
        }
    });
});
</script>
<?php endif; ?>
=======
</script>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<?php require_once __DIR__ . '/includes/footer.php'; ?>