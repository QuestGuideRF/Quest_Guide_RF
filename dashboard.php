<?php
<<<<<<< HEAD
/** Личный кабинет */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$page_title = $current_lang === 'en' ? 'Quests and excursions in Moscow' : 'Квесты и экскурсии по Москве';
$page_description = $current_lang === 'en'
    ? "QuestGuideRF (questguiderf, Квестгидрф) — interactive quests and excursions in Moscow and Russia. Telegram bot for self-guided tours, photo quests, and sightseeing. Your quest guide and tour guide in Moscow."
    : "QuestGuideRF (квестгидрф, questguiderf) — интерактивные квесты и экскурсии по Москве и России. Квесты-экскурсии в Telegram: пешеходные маршруты, фото-квесты по достопримечательностям. Экскурсовод по Москве, квесты Красная площадь, Кремль.";
$page_keywords = $current_lang === 'en'
    ? "questguiderf, QuestGuideRF, quest guide rf, rdtcnublha, quests Moscow, excursions Moscow, tour guide Moscow, interactive quests, photo quests, Telegram quests, sightseeing Moscow, Квестгидрф"
    : "questguiderf, QuestGuideRF, квестгидрф, йгуыепгшвука, rdtcnublha, экскурсии, экскурсовод, экскурсовод по Москве, квесты по Москве, квесты Москва, интерактивные экскурсии, квесты-экскурсии, пешеходные экскурсии, личный кабинет, квесты Красная площадь, Кремль, достопримечательности Москвы, квест в Telegram";
if ($user) {
    $stats = getUserStats($user['id']);
    $level_info = getUserLevel($stats['total_points']);
    $active_quests = getDB()->fetchAll(
        'SELECT up.*, r.name as route_name, r.name_en as route_name_en, r.price, c.name as city_name, c.name_en as city_name_en
         FROM user_progress up
         JOIN routes r ON up.route_id = r.id
         JOIN cities c ON r.city_id = c.id
         WHERE up.user_id = ? AND up.status = "in_progress"
         ORDER BY up.started_at DESC
         LIMIT 5',
        [$user['id']]
    );
    $recent_achievements = getDB()->fetchAll(
        'SELECT ua.*, a.name, a.name_en, a.description, a.description_en, a.icon
         FROM user_achievements ua
         JOIN achievements a ON ua.achievement_id = a.id
         WHERE ua.user_id = ?
         ORDER BY ua.earned_at DESC
         LIMIT 3',
        [$user['id']]
    );
} else {
    $stats = ['completed_routes' => 0, 'total_points' => 0, 'total_time' => 0, 'achievements_count' => 0];
    $level_info = getUserLevel(0);
    $active_quests = [];
    $recent_achievements = [];
}
=======
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$page_title = t('home');
$page_description = $current_lang === 'en'
    ? "QuestGuideRF main page - your personal dashboard with statistics, active quests and achievements. Track your progress on quest-excursions."
    : "Главная страница QuestGuideRF - ваш личный кабинет с статистикой, активными квестами и достижениями. Отслеживайте прогресс по экскурсиям-квестам.";
$page_keywords = $current_lang === 'en'
    ? "dashboard, statistics, quests, excursions, achievements, progress, QuestGuideRF"
    : "личный кабинет, статистика, квесты, экскурсии, достижения, прогресс, QuestGuideRF";
$stats = getUserStats($user['id']);
$level_info = getUserLevel($stats['total_points']);
$active_quests = getDB()->fetchAll(
    'SELECT up.*, r.name as route_name, r.name_en as route_name_en, r.price, c.name as city_name, c.name_en as city_name_en
     FROM user_progress up
     JOIN routes r ON up.route_id = r.id
     JOIN cities c ON r.city_id = c.id
     WHERE up.user_id = ? AND up.status = "in_progress"
     ORDER BY up.started_at DESC
     LIMIT 5',
    [$user['id']]
);
$recent_achievements = getDB()->fetchAll(
    'SELECT ua.*, a.name, a.description, a.icon
     FROM user_achievements ua
     JOIN achievements a ON ua.achievement_id = a.id
     WHERE ua.user_id = ?
     ORDER BY ua.earned_at DESC
     LIMIT 3',
    [$user['id']]
);
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
require_once __DIR__ . '/includes/header.php';
?>
<style>
@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr !important;
        gap: 0.75rem;
    }
    .stat-card {
        padding: 1rem;
        flex-direction: row;
        text-align: left;
        gap: 0.75rem;
    }
    .stat-icon {
        font-size: 1.75rem;
        flex-shrink: 0;
    }
    .stat-content {
        flex: 1;
        min-width: 0;
    }
    .stat-value {
        font-size: 1.25rem;
        line-height: 1.2;
    }
    .stat-label {
        font-size: 0.7rem;
        line-height: 1.2;
        word-break: break-word;
    }
}
</style>
<main class="main-content">
<div class="container">
<<<<<<< HEAD
    <div class="welcome-section">
        <?php if ($user): ?>
=======
    <!-- Приветствие -->
    <div class="welcome-section">
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        <div class="user-avatar">
            <img src="<?= e($user['photo_url'] ?: getDefaultAvatar($user['first_name'])) ?>"
                 alt="<?= e($user['first_name']) ?>">
        </div>
        <div class="user-info">
            <h1><?= t('hello') ?>, <?= e($user['first_name']) ?>! 👋</h1>
            <p class="level-badge">
                <?= t('level') ?> <?= $level_info['level'] ?>
                <span class="level-progress">
                    (<?= $level_info['current_points'] ?>/<?= $level_info['points_to_next'] + $level_info['current_points'] ?>)
                </span>
            </p>
        </div>
<<<<<<< HEAD
        <?php else: ?>
        <div class="user-avatar">
            <img src="<?= e(getDefaultAvatar('')) ?>" alt="">
        </div>
        <div class="user-info">
            <h1><?= t('welcome_guest') ?></h1>
            <p class="level-badge text-muted">
                <?= t('sign_in_to_see_stats') ?>
            </p>
            <p class="text-muted" style="margin-top: 0.5rem; font-size: 0.95rem;">
                <?= $current_lang === 'en' ? 'Quest-excursions in Moscow and Russia via Telegram. QuestGuideRF — your quest guide and sightseeing tours.' : 'Квесты-экскурсии по Москве и России в Telegram. QuestGuideRF (квестгидрф) — экскурсовод по Москве и достопримечательностям.' ?>
            </p>
        </div>
        <?php endif; ?>
    </div>
=======
    </div>
    <!-- Статистика -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">🗺️</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['completed_routes'] ?></div>
                <div class="stat-label"><?= t('routes_completed') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📍</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['total_points'] ?></div>
                <div class="stat-label"><?= t('points_completed') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">⏱️</div>
            <div class="stat-content">
                <div class="stat-value"><?= formatDuration($stats['total_time']) ?></div>
                <div class="stat-label"><?= t('time_in_quests') ?></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">🏆</div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['achievements_count'] ?></div>
                <div class="stat-label"><?= t('achievements') ?></div>
            </div>
        </div>
    </div>
<<<<<<< HEAD
=======
    <!-- Активные квесты -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <?php if (count($active_quests) > 0): ?>
    <section class="section">
        <div class="section-header">
            <h2><?= t('active_quests') ?></h2>
            <a href="/routes.php" class="btn btn-outline btn-sm"><?= t('all_routes') ?></a>
        </div>
        <div class="quests-list">
            <?php foreach ($active_quests as $quest):
                $progress = getRouteProgress($quest['id']);
            ?>
            <div class="quest-card">
                <div class="quest-info">
                    <h3><?= getLocalizedName(['name' => $quest['route_name'], 'name_en' => $quest['route_name_en'] ?? null]) ?></h3>
                    <p class="text-muted"><?= getLocalizedName(['name' => $quest['city_name'], 'name_en' => $quest['city_name_en'] ?? null]) ?></p>
                    <div class="progress-info">
                        <span><?= t('completed_progress') ?>: <?= $quest['points_completed'] ?> / <?= $progress['total_points'] ?></span>
                    </div>
                </div>
                <div class="quest-progress">
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $progress['progress_percent'] ?>%"></div>
                    </div>
                    <div class="progress-percent"><?= $progress['progress_percent'] ?>%</div>
                </div>
                <div class="quest-actions">
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary btn-sm" target="_blank">
                        <?= t('continue_in_bot') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
<<<<<<< HEAD
=======
    <!-- Последние достижения -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <?php if (count($recent_achievements) > 0): ?>
    <section class="section">
        <div class="section-header">
            <h2><?= t('recent_achievements') ?></h2>
            <a href="/achievements.php" class="btn btn-outline btn-sm"><?= t('all_achievements') ?></a>
        </div>
        <div class="achievements-grid">
<<<<<<< HEAD
            <?php foreach ($recent_achievements as $achievement):
                $ach_name = ($current_lang === 'en' && !empty($achievement['name_en'])) ? $achievement['name_en'] : $achievement['name'];
                $ach_desc = ($current_lang === 'en' && !empty($achievement['description_en'])) ? $achievement['description_en'] : $achievement['description'];
            ?>
            <div class="achievement-card earned">
                <div class="achievement-icon">
                    <?php $img_url = getAchievementImageUrl($achievement['id']); ?>
                    <?php if ($img_url): ?>
                        <img src="<?= e($img_url) ?>" alt="<?= e($ach_name) ?>" loading="lazy">
                    <?php else: ?>
                        <?= e($achievement['icon']) ?>
                    <?php endif; ?>
                </div>
                <div class="achievement-info">
                    <h3><?= e($ach_name) ?></h3>
                    <p class="text-muted text-small"><?= e($ach_desc) ?></p>
=======
            <?php foreach ($recent_achievements as $achievement): ?>
            <div class="achievement-card">
                <div class="achievement-icon"><?= e($achievement['icon']) ?></div>
                <div class="achievement-info">
                    <h3><?= e($achievement['name']) ?></h3>
                    <p class="text-muted text-small"><?= e($achievement['description']) ?></p>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    <p class="text-muted text-small">
                        <?= formatDate($achievement['earned_at']) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
<<<<<<< HEAD
=======
    <!-- Быстрые действия -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <section class="section">
        <h2><?= t('quick_actions') ?></h2>
        <div class="quick-actions">
            <a href="/routes.php" class="action-card">
                <div class="action-icon">🗺️</div>
                <div class="action-text"><?= t('my_routes') ?></div>
            </a>
            <a href="/photos.php" class="action-card">
                <div class="action-icon">📸</div>
                <div class="action-text"><?= t('photos') ?></div>
            </a>
            <a href="/bank.php" class="action-card">
                <div class="action-icon">🏦</div>
                <div class="action-text"><?= t('bank') ?></div>
            </a>
            <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="action-card" target="_blank">
                <div class="action-icon">🤖</div>
                <div class="action-text"><?= t('open_bot') ?></div>
            </a>
        </div>
    </section>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>