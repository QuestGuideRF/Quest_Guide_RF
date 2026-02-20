<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$lang = getCurrentLanguage();
$route_filter = isset($_GET['route']) ? (int)$_GET['route'] : null;
$photos = getUserPhotos($user['id'], $route_filter);
$user_routes = getDB()->fetchAll(
    'SELECT DISTINCT r.id, r.name, r.name_en
     FROM user_progress up
     JOIN routes r ON up.route_id = r.id
     WHERE up.user_id = ?
     ORDER BY r.name',
    [$user['id']]
);
$page_title = t('photos');
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
    <div class="page-header">
        <h1>📸 <?= t('photos') ?></h1>
        <p class="text-muted"><?= $lang === 'ru' ? 'Фотографии с прохождения квестов' : 'Photos from quests' ?></p>
    </div>
    <?php if (!empty($user_routes)): ?>
    <div class="filters" style="margin-bottom:1.5rem;">
        <form method="get">
            <label><?= $lang === 'ru' ? 'Маршрут' : 'Route' ?></label>
            <select name="route" class="filter-select" onchange="this.form.submit()" style="padding:0.5rem 1rem;border-radius:var(--radius-md);border:1px solid var(--border-color);background:var(--bg-tertiary);color:var(--text-primary);">
                <option value=""><?= $lang === 'ru' ? 'Все' : 'All' ?></option>
                <?php foreach ($user_routes as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $route_filter == $r['id'] ? 'selected' : '' ?>><?= e(getLocalizedField($r, 'name', $lang)) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>
    <?php endif; ?>
    <?php if (empty($photos)): ?>
    <div class="empty-state">
        <div class="empty-icon">📷</div>
        <p><?= $lang === 'ru' ? 'Пока нет фотографий' : 'No photos yet' ?></p>
        <a href="/routes.php" class="btn btn-primary"><?= t('routes') ?></a>
    </div>
    <?php else: ?>
    <div class="photos-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:1rem;">
        <?php foreach ($photos as $ph): ?>
        <div class="photo-card" style="background:var(--bg-tertiary);border-radius:var(--radius-lg);overflow:hidden;">
            <?php
            $photosBase = defined('PHOTOS_PATH') ? resolvePath(PHOTOS_PATH) : resolvePath(UPLOAD_PATH);
            $relPath = str_replace('/', DIRECTORY_SEPARATOR, $ph['file_path']);
            $fullPath = $photosBase . DIRECTORY_SEPARATOR . $relPath;
            if (!file_exists($fullPath) && defined('UPLOAD_PATH') && UPLOAD_PATH !== (defined('PHOTOS_PATH') ? PHOTOS_PATH : '')) {
                $fullPath = resolvePath(UPLOAD_PATH) . DIRECTORY_SEPARATOR . $relPath;
            }
            if (!file_exists($fullPath)) {
                $imgPath = getDefaultAvatar('?');
            } else {
                $photosPathRaw = defined('PHOTOS_PATH') ? trim(PHOTOS_PATH) : (defined('UPLOAD_PATH') ? trim(UPLOAD_PATH) : '');
                $useProxy = ($photosPathRaw !== '' && (strpos($photosPathRaw, '/') === 0 || preg_match('#^[A-Za-z]:[\\\\/]#', $photosPathRaw)));
                $imgPath = $useProxy
                    ? rtrim(SITE_URL, '/') . '/api/serve_photo.php?path=' . urlencode($ph['file_path'])
                    : rtrim(defined('PHOTOS_URL') ? PHOTOS_URL : UPLOAD_URL, '/') . '/' . $ph['file_path'];
            }
            ?>
            <a href="<?= e($imgPath) ?>" target="_blank">
                <img src="<?= e($imgPath) ?>" alt="" loading="lazy" style="width:100%;aspect-ratio:1;object-fit:cover;">
            </a>
            <div style="padding:0.75rem;font-size:0.875rem;">
                <strong><?= e(getLocalizedField($ph, 'point_name', $lang)) ?></strong>
                <div class="text-muted"><?= e(getLocalizedField($ph, 'route_name', $lang)) ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>