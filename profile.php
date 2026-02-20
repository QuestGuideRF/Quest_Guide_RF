<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
$current_lang = getCurrentLanguage();
$current_user = getCurrentUser();
$username = $_GET['username'] ?? $_GET['u'] ?? null;
if ($username) {
    $username = ltrim($username, '@');
}
if (!$username) {
    header('Location: /dashboard.php');
    exit;
}
$profile_user = getDB()->fetch(
    'SELECT * FROM users WHERE LOWER(username) = LOWER(?) LIMIT 1',
    [$username]
);
if (!$profile_user) {
    $page_title = $current_lang === 'en' ? 'User not found' : 'Пользователь не найден';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="main-content profile-empty-state">
        <div class="profile-empty-state-inner">
            <div class="profile-empty-card">
                <div class="profile-empty-icon">😕</div>
                <h1 class="profile-empty-title"><?= $current_lang === 'en' ? 'User not found' : 'Пользователь не найден' ?></h1>
                <p class="profile-empty-desc"><?= $current_lang === 'en' ? 'The user @' . e($username) . ' does not exist.' : 'Пользователь @' . e($username) . ' не существует.' ?></p>
                <a href="/dashboard.php" class="btn btn-primary profile-empty-btn"><?= $current_lang === 'en' ? 'Go to Home' : 'На главную' ?></a>
            </div>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
$is_own_profile = $current_user && $current_user['id'] == $profile_user['id'];
$is_profile_public = (bool)($profile_user['is_profile_public'] ?? true);
if (!$is_profile_public && !$is_own_profile) {
    $page_title = $current_lang === 'en' ? 'Profile hidden' : 'Профиль скрыт';
    require_once __DIR__ . '/includes/header.php';
    ?>
    <main class="main-content profile-empty-state">
        <div class="profile-empty-state-inner">
            <div class="profile-empty-card">
                <div class="profile-empty-icon">🔒</div>
                <h1 class="profile-empty-title"><?= $current_lang === 'en' ? 'Profile is private' : 'Профиль скрыт' ?></h1>
                <p class="profile-empty-desc"><?= $current_lang === 'en' ? 'This user has chosen to hide their profile.' : 'Данный пользователь скрыл профиль.' ?></p>
                <a href="/dashboard.php" class="btn btn-primary profile-empty-btn"><?= $current_lang === 'en' ? 'Go to Home' : 'На главную' ?></a>
            </div>
        </div>
    </main>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}
$stats = getDB()->fetch(
    'SELECT
        COUNT(CASE WHEN status = "completed" THEN 1 END) as completed_routes,
        COUNT(*) as total_routes,
        SUM(points_completed) as total_points,
        SUM(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as total_time
     FROM user_progress
     WHERE user_id = ?',
    [$profile_user['id']]
);
$stats['completed_routes'] = $stats['completed_routes'] ?? 0;
$stats['total_points'] = $stats['total_points'] ?? 0;
$stats['total_time'] = $stats['total_time'] ?? 0;
$photos_count = getDB()->fetch(
    'SELECT COUNT(*) as count FROM user_photos WHERE user_id = ?',
    [$profile_user['id']]
)['count'] ?? 0;
$achievements = getDB()->fetchAll(
    'SELECT a.* FROM achievements a
     JOIN user_achievements ua ON a.id = ua.achievement_id
     WHERE ua.user_id = ?
     ORDER BY ua.earned_at DESC',
    [$profile_user['id']]
);
$reviews = getDB()->fetchAll(
    'SELECT r.*, rt.name as route_name, rt.name_en as route_name_en, c.name as city_name
     FROM reviews r
     JOIN routes rt ON r.route_id = rt.id
     JOIN cities c ON rt.city_id = c.id
     WHERE r.user_id = ?
     ORDER BY r.created_at DESC
     LIMIT 5',
    [$profile_user['id']]
);
$photos = getDB()->fetchAll(
    'SELECT up.*, p.name as point_name, r.name as route_name
     FROM user_photos up
     JOIN points p ON up.point_id = p.id
     JOIN routes r ON p.route_id = r.id
     WHERE up.user_id = ?
     ORDER BY up.created_at DESC
     LIMIT 6',
    [$profile_user['id']]
);
$display_name = $profile_user['first_name'] . ($profile_user['last_name'] ? ' ' . $profile_user['last_name'] : '');
$page_title = $display_name . ' - ' . ($current_lang === 'en' ? 'Profile' : 'Профиль');
$page_description = $current_lang === 'en'
    ? "Profile of {$display_name} on QuestGuideRF. View achievements, completed quests and reviews."
    : "Профиль пользователя {$display_name} на QuestGuideRF. Смотрите достижения, пройденные квесты и отзывы.";
require_once __DIR__ . '/includes/header.php';
?>
<style>
.profile-page { padding: 2rem 0; }
.profile-header {
    display: flex;
    align-items: center;
    gap: 2rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
}
.profile-avatar-large {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid var(--primary);
}
.profile-info h1 {
    margin: 0 0 0.5rem 0;
    font-size: 2rem;
}
.profile-info .username {
    color: var(--text-secondary);
    font-size: 1.1rem;
}
.profile-info .member-since {
    color: var(--text-muted);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}
.profile-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
    gap: 1rem;
    margin-bottom: 2rem;
}
.profile-stat-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    text-align: center;
}
.profile-stat-card .stat-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary);
}
.profile-stat-card .stat-label {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-top: 0.5rem;
}
.profile-section {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}
.profile-section h2 {
    margin: 0 0 1rem 0;
    font-size: 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.achievements-list {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.achievement-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}
.achievement-badge .icon {
    font-size: 1.5rem;
}
.reviews-list { display: flex; flex-direction: column; gap: 1rem; }
.review-item {
    background: var(--bg-tertiary);
    border-radius: var(--radius-md);
    padding: 1rem;
}
.review-item .route-name {
    font-weight: 600;
    color: var(--primary);
    margin-bottom: 0.5rem;
}
.review-item .rating { color: var(--warning); }
.review-item .text { margin: 0.5rem 0; color: var(--text-primary); }
.review-item .date { color: var(--text-muted); font-size: 0.85rem; }
.photos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
    gap: 1rem;
}
.photo-item {
    aspect-ratio: 1;
    border-radius: var(--radius-md);
    overflow: hidden;
}
.photo-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.profile-empty-state {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 200px);
    padding: 2rem;
}
.profile-empty-state-inner {
    width: 100%;
    max-width: 420px;
    margin: 0 auto;
}
.profile-empty-card {
    text-align: center;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 3rem 2rem;
    box-shadow: 0 4px 20px var(--shadow);
}
.profile-empty-icon {
    font-size: 4rem;
    line-height: 1;
    margin-bottom: 1.5rem;
    opacity: 0.9;
}
.profile-empty-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--text-primary);
    margin: 0 0 0.75rem 0;
}
.profile-empty-desc {
    color: var(--text-secondary);
    font-size: 1rem;
    line-height: 1.5;
    margin: 0 0 1.5rem 0;
}
.profile-empty-btn {
    display: inline-block;
    padding: 0.65rem 1.5rem;
    font-weight: 500;
}
.own-profile-badge {
    background: var(--success);
    color: white;
    padding: 0.25rem 0.75rem;
    border-radius: var(--radius-sm);
    font-size: 0.8rem;
    margin-left: 1rem;
}
@media (max-width: 768px) {
    .profile-header { flex-direction: column; text-align: center; }
    .profile-avatar-large { width: 100px; height: 100px; }
}
</style>
<main class="main-content profile-page">
<div class="container">
    <div class="profile-header">
        <img src="<?= e($profile_user['photo_url'] ?: getDefaultAvatar($profile_user['first_name'])) ?>"
             alt="<?= e($display_name) ?>"
             class="profile-avatar-large">
        <div class="profile-info">
            <h1>
                <?= e($display_name) ?>
                <?php if ($is_own_profile): ?>
                <span class="own-profile-badge"><?= $current_lang === 'en' ? 'Your profile' : 'Ваш профиль' ?></span>
                <?php endif; ?>
            </h1>
            <?php if ($profile_user['username']): ?>
            <p class="username">@<?= e($profile_user['username']) ?></p>
            <?php endif; ?>
            <p class="member-since">
                <?= $current_lang === 'en' ? 'Member since' : 'На сайте с' ?> <?= formatDate($profile_user['created_at']) ?>
            </p>
        </div>
    </div>
    <div class="profile-stats-grid">
        <div class="profile-stat-card">
            <div class="stat-value"><?= $stats['completed_routes'] ?></div>
            <div class="stat-label"><?= $current_lang === 'en' ? 'Quests completed' : 'Квестов пройдено' ?></div>
        </div>
        <div class="profile-stat-card">
            <div class="stat-value"><?= $stats['total_points'] ?></div>
            <div class="stat-label"><?= $current_lang === 'en' ? 'Points visited' : 'Точек посещено' ?></div>
        </div>
        <div class="profile-stat-card">
            <div class="stat-value"><?= count($achievements) ?></div>
            <div class="stat-label"><?= $current_lang === 'en' ? 'Achievements' : 'Достижений' ?></div>
        </div>
        <div class="profile-stat-card">
            <div class="stat-value"><?= $photos_count ?></div>
            <div class="stat-label"><?= $current_lang === 'en' ? 'Photos' : 'Фото' ?></div>
        </div>
    </div>
    <?php if (!empty($achievements)): ?>
    <div class="profile-section">
        <h2>🏆 <?= $current_lang === 'en' ? 'Achievements' : 'Достижения' ?></h2>
        <div class="achievements-list">
            <?php foreach ($achievements as $ach): ?>
            <div class="achievement-badge" title="<?= e($ach['description']) ?>">
                <?php $img_url = getAchievementImageUrl($ach['id']); ?>
                <?php if ($img_url): ?>
                    <img src="<?= e($img_url) ?>" alt="<?= e($ach['name']) ?>" style="width:32px;height:32px;border-radius:6px;">
                <?php else: ?>
                    <span class="icon"><?= e($ach['icon']) ?></span>
                <?php endif; ?>
                <span><?= e($ach['name']) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($photos)): ?>
    <div class="profile-section">
        <h2>📸 <?= $current_lang === 'en' ? 'Recent photos' : 'Последние фото' ?></h2>
        <div class="photos-grid">
            <?php foreach ($photos as $photo): ?>
            <div class="photo-item" title="<?= e($photo['point_name']) ?>">
                <img src="<?= e($photo['file_path']) ?>" alt="<?= e($photo['point_name']) ?>" loading="lazy">
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (!empty($reviews)): ?>
    <div class="profile-section">
        <h2>⭐ <?= $current_lang === 'en' ? 'Reviews' : 'Отзывы' ?></h2>
        <div class="reviews-list">
            <?php foreach ($reviews as $review): ?>
            <div class="review-item">
                <div class="route-name">
                    <a href="/routes/view.php?id=<?= $review['route_id'] ?>">
                        <?= getLocalizedName(['name' => $review['route_name'], 'name_en' => $review['route_name_en'] ?? null]) ?>
                    </a>
                </div>
                <div class="rating">
                    <?php for($i = 1; $i <= $review['rating']; $i++): ?>⭐<?php endfor; ?>
                </div>
                <?php if ($review['text']): ?>
                <p class="text"><?= nl2br(e($review['text'])) ?></p>
                <?php endif; ?>
                <div class="date"><?= formatDate($review['created_at']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    <?php if (empty($achievements) && empty($photos) && empty($reviews) && $stats['completed_routes'] == 0): ?>
    <div class="profile-section" style="text-align: center;">
        <p class="text-muted">
            <?= $current_lang === 'en'
                ? 'This user has not completed any quests yet.'
                : 'Этот пользователь ещё не прошёл ни одного квеста.' ?>
        </p>
    </div>
    <?php endif; ?>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>