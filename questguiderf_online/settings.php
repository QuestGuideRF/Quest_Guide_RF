<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$updated = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['theme'])) {
        $theme = in_array($_POST['theme'], ['light', 'dark', 'auto']) ? $_POST['theme'] : 'dark';
        $_SESSION['theme'] = $theme;
        $updated = true;
    }
    if (isset($_POST['is_profile_public']) && !isset($_POST['theme'])) {
        $is_public = (int)$_POST['is_profile_public'] ? 1 : 0;
        getDB()->query('UPDATE users SET is_profile_public = ? WHERE id = ?', [$is_public, $user['id']]);
        $user['is_profile_public'] = $is_public;
        $updated = true;
    }
}
$page_title = t('settings');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>⚙️ <?= t('settings') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Настройки аккаунта' : 'Account settings' ?></p>
    </div>
    <?php if ($updated): ?>
    <div class="alert" style="background:var(--success);color:white;padding:1rem;border-radius:var(--radius-md);margin-bottom:1.5rem;">
        <?= $lang === 'ru' ? 'Настройки сохранены' : 'Settings saved' ?>
    </div>
    <?php endif; ?>
    <div class="settings-grid" style="display:grid;gap:1.5rem;max-width:600px;">
        <div class="quest-card">
            <h2>👤 <?= $lang === 'ru' ? 'Профиль' : 'Profile' ?></h2>
            <div class="profile-info" style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                <div class="avatar-upload-container profile-avatar-wrap">
                    <img src="<?= e(($user['photo_url'] ?? null) ?: getDefaultAvatar($user['first_name'])) ?>"
                         alt="" class="profile-avatar" id="profile-avatar">
                    <label for="avatar-input" class="avatar-upload-label-btn">📷</label>
                </div>
                <input type="file" id="avatar-input" accept="image/*" style="display:none">
                <div>
                    <h3 style="margin:0;"><?= e($user['first_name'] ?? '') ?> <?= e($user['last_name'] ?? '') ?></h3>
                    <p class="text-muted" style="margin:0.25rem 0 0;">@<?= e($user['username'] ?? 'user') ?></p>
                </div>
            </div>
            <div id="upload-status" style="display:none;margin-top:0.5rem;font-size:0.875rem;"></div>
        </div>
        <div class="quest-card">
            <h2>🎨 <?= $lang === 'ru' ? 'Оформление' : 'Appearance' ?></h2>
            <p class="text-muted" style="margin-bottom:1rem;"><?= $lang === 'ru' ? 'Тема интерфейса' : 'Interface theme' ?></p>
            <form method="post" class="theme-form" style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <button type="submit" name="theme" value="dark" class="btn theme-btn <?= ($_SESSION['theme'] ?? 'dark') === 'dark' ? 'btn-primary' : 'btn-outline' ?>">🌙 <?= $lang === 'ru' ? 'Тёмная' : 'Dark' ?></button>
                <button type="submit" name="theme" value="light" class="btn theme-btn <?= ($_SESSION['theme'] ?? '') === 'light' ? 'btn-primary' : 'btn-outline' ?>">☀️ <?= $lang === 'ru' ? 'Светлая' : 'Light' ?></button>
                <button type="submit" name="theme" value="auto" class="btn theme-btn <?= ($_SESSION['theme'] ?? '') === 'auto' ? 'btn-primary' : 'btn-outline' ?>">⚙️ <?= $lang === 'ru' ? 'Авто' : 'Auto' ?></button>
            </form>
        </div>
        <div class="quest-card">
            <h2>🔒 <?= $lang === 'ru' ? 'Приватность' : 'Privacy' ?></h2>
            <p class="text-muted" style="margin-bottom:1rem;"><?= $lang === 'ru' ? 'При публичном профиле другие видят ваш профиль, достижения и отзывы.' : 'When public, others can view your profile, achievements and reviews.' ?></p>
            <form method="post" id="privacy-form">
                <div style="display:flex;gap:1rem;flex-wrap:wrap;">
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                        <input type="radio" name="is_profile_public" value="1" <?= ($user['is_profile_public'] ?? 1) ? 'checked' : '' ?>>
                        🌐 <?= $lang === 'ru' ? 'Публичный' : 'Public' ?>
                    </label>
                    <label style="display:flex;align-items:center;gap:0.5rem;cursor:pointer;">
                        <input type="radio" name="is_profile_public" value="0" <?= !($user['is_profile_public'] ?? 1) ? 'checked' : '' ?>>
                        🔒 <?= $lang === 'ru' ? 'Скрытый' : 'Hidden' ?>
                    </label>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var savedTheme = '<?= e($_SESSION['theme'] ?? 'dark') ?>';
    if (savedTheme) {
        localStorage.setItem('theme', savedTheme);
        var applied = savedTheme === 'auto' ? (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light') : savedTheme;
        document.documentElement.setAttribute('data-theme', applied);
    }
});
document.querySelectorAll('.theme-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        localStorage.setItem('theme', this.value);
    });
});
document.querySelectorAll('input[name="is_profile_public"]').forEach(function(r) {
    r.addEventListener('change', function() {
        var fd = new FormData(document.getElementById('privacy-form'));
        fd.append('is_profile_public', this.value);
        fetch('/api/update_privacy.php', { method: 'POST', headers: {'Content-Type': 'application/json'}, body: JSON.stringify({is_profile_public: parseInt(this.value)}) }).then(function(res) { return res.json(); }).then(function(d) {
            if (d.success) location.reload();
        });
    });
});
document.getElementById('avatar-input').addEventListener('change', async function(e) {
    var file = e.target.files[0];
    if (!file) return;
    if (file.size > 5 * 1024 * 1024) {
        showStatus('<?= $lang === 'ru' ? 'Файл слишком большой (макс 5 МБ)' : 'File too large (max 5 MB)' ?>', true);
        return;
    }
    if (!file.type.match(/^image\/(jpeg|png|gif|webp)$/)) {
        showStatus('<?= $lang === 'ru' ? 'Неверный формат' : 'Invalid format' ?>', true);
        return;
    }
    showStatus('<?= $lang === 'ru' ? 'Загрузка...' : 'Uploading...' ?>', false);
    var fd = new FormData();
    fd.append('avatar', file);
    try {
        var r = await fetch('/api/upload_avatar.php', { method: 'POST', body: fd });
        var d = await r.json();
        if (d.success) {
            document.getElementById('profile-avatar').src = d.photo_url + '?t=' + Date.now();
            document.querySelectorAll('.user-avatar-small, .user-avatar').forEach(function(img) { if (img.src) img.src = d.photo_url + '?t=' + Date.now(); });
            showStatus('<?= $lang === 'ru' ? 'Готово' : 'Done' ?>', false);
            setTimeout(function() { document.getElementById('upload-status').style.display = 'none'; }, 2000);
        } else {
            showStatus(d.error || 'Error', true);
        }
    } catch (err) {
        showStatus(err.message, true);
    }
});
function showStatus(msg, err) {
    var el = document.getElementById('upload-status');
    el.textContent = msg;
    el.style.display = 'block';
    el.style.color = err ? 'var(--danger)' : 'var(--success)';
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>