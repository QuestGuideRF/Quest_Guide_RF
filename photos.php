<?php
<<<<<<< HEAD
/** Галерея фотографий */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$route_filter = isset($_GET['route']) ? intval($_GET['route']) : null;
$photos = $user ? getUserPhotos($user['id'], $route_filter) : [];
$user_routes = $user ? getDB()->fetchAll(
=======
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$route_filter = isset($_GET['route']) ? intval($_GET['route']) : null;
$photos = getUserPhotos($user['id'], $route_filter);
$user_routes = getDB()->fetchAll(
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    'SELECT DISTINCT r.id, r.name, r.name_en, c.name as city_name, c.name_en as city_name_en
     FROM user_progress up
     JOIN routes r ON up.route_id = r.id
     JOIN cities c ON r.city_id = c.id
     WHERE up.user_id = ?
     ORDER BY r.name',
    [$user['id']]
<<<<<<< HEAD
) : [];
=======
);
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
$page_title = t('photos_title');
$page_description = $current_lang === 'en'
    ? "Your photos from QuestGuideRF quest-excursions. View all photos taken during route completion at attractions."
    : "Ваши фотографии с экскурсий-квестов QuestGuideRF. Просматривайте все фото, сделанные во время прохождения маршрутов по достопримечательностям.";
$page_keywords = $current_lang === 'en'
    ? "photos, quest photos, excursion photos, attraction photos, gallery, QuestGuideRF"
    : "фотографии, фото квестов, фото экскурсий, фото достопримечательностей, галерея, QuestGuideRF";
require_once __DIR__ . '/includes/header.php';
?>
<style>
@media (max-width: 768px) {
    .filters {
        margin-bottom: 1.5rem;
    }
    .filters label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
    }
    .filter-select {
        width: 100%;
        padding: 0.75rem;
        font-size: 16px;
        border: 1px solid var(--border-color);
        border-radius: var(--radius-md);
        background: var(--bg-secondary);
        color: var(--text-primary);
        -webkit-appearance: none;
        -moz-appearance: none;
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23666' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        background-size: 12px;
    }
}
</style>
<main class="main-content">
<div class="container">
    <div class="page-header">
        <h1>📸 <?= t('photos_title') ?></h1>
        <p class="text-muted"><?= t('photos_subtitle') ?></p>
    </div>
<<<<<<< HEAD
    <?php if (!$user): ?>
    <p class="text-muted"><?= $current_lang === 'en' ? 'Sign in to see your photos from quests.' : 'Войдите, чтобы видеть свои фото с квестов.' ?></p>
    <a href="/pages/login.php" class="btn btn-primary mb-4"><?= t('login') ?></a>
    <?php endif; ?>
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <?php if (!empty($user_routes)): ?>
    <div class="filters">
        <label for="route-filter"><?= t('filter_by_route') ?></label>
        <select id="route-filter" class="filter-select" onchange="filterByRoute(this.value)">
            <option value=""><?= t('all_routes_filter') ?></option>
            <?php foreach ($user_routes as $route): ?>
            <option value="<?= $route['id'] ?>" <?= $route_filter == $route['id'] ? 'selected' : '' ?>>
                <?= getLocalizedName(['name' => $route['city_name'], 'name_en' => $route['city_name_en'] ?? null]) ?> - <?= getLocalizedName(['name' => $route['name'], 'name_en' => $route['name_en'] ?? null]) ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <?php if (empty($photos)): ?>
    <div class="empty-state">
        <div class="empty-icon">📸</div>
        <h2><?= t('no_photos_yet') ?></h2>
        <p><?= t('start_quest_and_photo') ?></p>
        <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary" target="_blank">
            <?= t('open_bot') ?>
        </a>
    </div>
    <?php else: ?>
    <div class="photo-gallery">
        <?php foreach ($photos as $photo): ?>
        <div class="photo-item" data-photo-id="<?= $photo['id'] ?>">
            <div class="photo-wrapper">
                <?php
                $file_exists = file_exists(__DIR__ . $photo['file_path']);
                ?>
                <img src="<?= e($photo['file_path']) ?>"
                     alt="<?= e($photo['point_name']) ?>"
                     loading="lazy"
                     onerror="this.src='/assets/img/no-photo.png'; this.onerror=null;"
                     onclick="openPhotoModal(<?= $photo['id'] ?>)">
                <?php if (!$file_exists): ?>
                <div class="photo-error"><?= t('file_not_found') ?></div>
                <?php endif; ?>
            </div>
            <div class="photo-info">
                <div class="photo-title"><?= e($photo['point_name']) ?></div>
                <div class="photo-meta text-muted text-small">
                    <?= e($photo['route_name']) ?> • <?= formatDate($photo['created_at']) ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
<<<<<<< HEAD
=======
<!-- Модальное окно для просмотра фото -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div id="photo-modal" class="modal">
    <div class="modal-content">
        <button class="modal-close" onclick="closePhotoModal()">&times;</button>
        <div class="modal-body">
            <img id="modal-photo" src="" alt="">
            <div class="modal-info">
                <h3 id="modal-title"></h3>
                <p id="modal-meta" class="text-muted"></p>
            </div>
        </div>
    </div>
</div>
<script>
function filterByRoute(routeId) {
    const lang = new URLSearchParams(window.location.search).get('lang') || '';
    const langParam = lang ? '&lang=' + lang : '';
    if (routeId) {
        window.location.href = '/photos.php?route=' + routeId + langParam;
    } else {
        window.location.href = '/photos.php' + (lang ? '?lang=' + lang : '');
    }
}
const photos = <?= json_encode($photos) ?>;
function openPhotoModal(photoId) {
    const photo = photos.find(p => p.id == photoId);
    if (!photo) return;
    document.getElementById('modal-photo').src = photo.file_path;
    document.getElementById('modal-title').textContent = photo.point_name;
    document.getElementById('modal-meta').textContent =
        photo.route_name + ' • ' + new Date(photo.created_at).toLocaleDateString('ru-RU');
    document.getElementById('photo-modal').classList.add('active');
    document.body.style.overflow = 'hidden';
}
function closePhotoModal() {
    document.getElementById('photo-modal').classList.remove('active');
    document.body.style.overflow = '';
}
document.getElementById('photo-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePhotoModal();
    }
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePhotoModal();
    }
});
</script>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>