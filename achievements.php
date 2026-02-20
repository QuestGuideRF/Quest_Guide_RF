<?php
<<<<<<< HEAD
/** Страница достижений */
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
if ($user) {
    syncUserAchievements($user['id']);
}
$all_achievements = getDB()->fetchAll('SELECT id, name, name_en, description, description_en, icon, category, category_en, `order`, is_hidden, condition_type, condition_value, created_at, updated_at FROM achievements ORDER BY category, `order`');
$user_achievements_ids = $user ? getDB()->fetchAll(
    'SELECT achievement_id, earned_at FROM user_achievements WHERE user_id = ?',
    [$user['id']]
) : [];
=======
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
requireAuth();
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$all_achievements = getDB()->fetchAll('SELECT * FROM achievements ORDER BY category, `order`');
$user_achievements_ids = getDB()->fetchAll(
    'SELECT achievement_id, earned_at FROM user_achievements WHERE user_id = ?',
    [$user['id']]
);
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
$earned_map = [];
foreach ($user_achievements_ids as $ua) {
    $earned_map[$ua['achievement_id']] = $ua['earned_at'];
}
<<<<<<< HEAD
$user_progress_data = ['routes_completed' => 0, 'points_completed' => 0, 'photos_taken' => 0, 'total_achievements' => 0, 'referrals_paid' => 0];
if ($user) {
    $routes_completed = getDB()->fetch(
        "SELECT COUNT(*) as cnt FROM user_progress WHERE user_id = ? AND status IN ('completed', 'COMPLETED')",
        [$user['id']]
    )['cnt'] ?? 0;
    $points_completed = getDB()->fetch(
        'SELECT SUM(points_completed) as cnt FROM user_progress WHERE user_id = ?',
        [$user['id']]
    )['cnt'] ?? 0;
    $photos_taken = getDB()->fetch(
        'SELECT COUNT(*) as cnt FROM user_photos WHERE user_id = ?',
        [$user['id']]
    )['cnt'] ?? 0;
    $referrals_paid = getDB()->fetch(
        'SELECT COALESCE(paid_referrals_count, 0) as cnt FROM users WHERE id = ?',
        [$user['id']]
    )['cnt'] ?? 0;
    $user_progress_data = [
        'routes_completed' => (int)$routes_completed,
        'points_completed' => (int)$points_completed,
        'photos_taken' => (int)$photos_taken,
        'total_achievements' => count($earned_map),
        'referrals_paid' => (int)$referrals_paid,
    ];
}
function getAchievementProgress($condition_type, $condition_value, $user_progress_data) {
    $current = 0;
    $target = $condition_value ?? 1;
    switch ($condition_type) {
        case 'routes_completed':
            $current = $user_progress_data['routes_completed'];
            break;
        case 'points_completed':
            $current = $user_progress_data['points_completed'];
            break;
        case 'photos_taken':
            $current = $user_progress_data['photos_taken'];
            break;
        case 'all_achievements':
            $current = $user_progress_data['total_achievements'];
            break;
        case 'referrals_paid':
            $current = $user_progress_data['referrals_paid'] ?? 0;
            break;
        case 'perfect_route':
        case 'fast_completion':
        case 'night_quest':
        case 'early_bird':
            $target = 1;
            break;
        default:
            $target = 1;
    }
    return ['current' => min($current, $target), 'target' => $target];
}
$achievements_by_category = [];
foreach ($all_achievements as $achievement) {
    $categoryKey = $achievement['category'] ?? 'Общие';
    $category = ($current_lang === 'en' && !empty($achievement['category_en'])) ? $achievement['category_en'] : $categoryKey;
    if (!isset($achievements_by_category[$category])) {
        $achievements_by_category[$category] = [];
    }
    $achievement['display_name'] = ($current_lang === 'en' && !empty($achievement['name_en'])) ? $achievement['name_en'] : $achievement['name'];
    $achievement['display_description'] = ($current_lang === 'en' && !empty($achievement['description_en'])) ? $achievement['description_en'] : $achievement['description'];
    $achievement['earned'] = isset($earned_map[$achievement['id']]);
    $achievement['earned_at'] = $earned_map[$achievement['id']] ?? null;
    if ($user && !$achievement['earned']) {
        $progress = getAchievementProgress($achievement['condition_type'], $achievement['condition_value'], $user_progress_data);
        $achievement['progress_current'] = $progress['current'];
        $achievement['progress_target'] = $progress['target'];
        $achievement['progress_percent'] = $progress['target'] > 0 ? round(($progress['current'] / $progress['target']) * 100) : 0;
    } else {
        $achievement['progress_current'] = null;
        $achievement['progress_target'] = null;
        $achievement['progress_percent'] = $achievement['earned'] ? 100 : 0;
    }
=======
$achievements_by_category = [];
foreach ($all_achievements as $achievement) {
    $category = $achievement['category'] ?? ($current_lang === 'en' ? 'General' : 'Общие');
    if (!isset($achievements_by_category[$category])) {
        $achievements_by_category[$category] = [];
    }
    $achievement['earned'] = isset($earned_map[$achievement['id']]);
    $achievement['earned_at'] = $earned_map[$achievement['id']] ?? null;
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    $achievements_by_category[$category][] = $achievement;
}
$total_achievements = count($all_achievements);
$earned_achievements = count($earned_map);
$progress_percent = $total_achievements > 0 ? round(($earned_achievements / $total_achievements) * 100) : 0;
$page_title = t('achievements_title');
$page_description = $current_lang === 'en'
    ? "Your achievements in QuestGuideRF. Track your progress on earning all rewards for completing quests and excursions."
    : "Ваши достижения в QuestGuideRF. Отслеживайте прогресс по получению всех наград за прохождение квестов и экскурсий.";
$page_keywords = $current_lang === 'en'
    ? "achievements, rewards, progress, quests, excursions, gamification, QuestGuideRF"
    : "достижения, награды, прогресс, квесты, экскурсии, геймификация, QuestGuideRF";
require_once __DIR__ . '/includes/header.php';
?>
<main class="main-content">
<div class="container">
    <div class="page-header">
        <h1>🏆 <?= t('achievements_title') ?></h1>
        <p class="text-muted"><?= t('achievements_subtitle') ?></p>
<<<<<<< HEAD
        <?php if (!$user): ?>
        <p class="text-muted"><?= $current_lang === 'en' ? 'Sign in to see your earned achievements.' : 'Войдите, чтобы видеть полученные достижения.' ?></p>
        <a href="/pages/login.php" class="btn btn-primary"><?= t('login') ?></a>
        <?php endif; ?>
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    </div>
    <div class="achievements-progress">
        <div class="progress-header">
            <h3><?= t('progress') ?></h3>
            <span><?= $earned_achievements ?> / <?= $total_achievements ?></span>
        </div>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?= $progress_percent ?>%"></div>
        </div>
        <p class="text-muted text-center"><?= $progress_percent ?>% <?= t('achievements_earned') ?></p>
    </div>
<<<<<<< HEAD
    <?php if ($user):
        $referral_levels = getDB()->fetchAll('SELECT * FROM referral_levels WHERE is_active = 1 ORDER BY level');
        $user_referral_data = getDB()->fetch('SELECT referral_level, paid_referrals_count, is_partner FROM users WHERE id = ?', [$user['id']]);
        $current_ref_level = $user_referral_data['referral_level'] ?? 0;
        $paid_refs = $user_referral_data['paid_referrals_count'] ?? 0;
        $is_partner = $user_referral_data['is_partner'] ?? 0;
        $next_level = null;
        foreach ($referral_levels as $lvl) {
            if ($lvl['level'] > $current_ref_level) {
                $next_level = $lvl;
                break;
            }
        }
    ?>
    <section class="section referral-achievements">
        <h2>🤝 <?= $current_lang === 'en' ? 'Referral Achievements' : 'Партнёрские достижения' ?></h2>
        <p class="text-muted"><?= $current_lang === 'en' ? 'Invite friends who buy quests and get rewards!' : 'Приглашайте друзей, которые купят квесты, и получайте награды!' ?></p>
        <div class="referral-progress-card">
            <div class="referral-stats">
                <div class="stat-item">
                    <span class="stat-value"><?= $paid_refs ?></span>
                    <span class="stat-label"><?= $current_lang === 'en' ? 'Friends invited' : 'Друзей пригласили' ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?= $current_ref_level ?>/4</span>
                    <span class="stat-label"><?= $current_lang === 'en' ? 'Current level' : 'Текущий уровень' ?></span>
                </div>
                <?php if ($is_partner): ?>
                <div class="stat-item partner">
                    <span class="stat-value">👑</span>
                    <span class="stat-label"><?= $current_lang === 'en' ? 'Partner' : 'Партнёр' ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($next_level):
                $progress_to_next = min(100, round(($paid_refs / $next_level['required_referrals']) * 100));
                $remaining = max(0, $next_level['required_referrals'] - $paid_refs);
            ?>
            <div class="next-level-progress">
                <div class="progress-header">
                    <span><?= $current_lang === 'en' ? 'To level' : 'До уровня' ?> "<?= e($current_lang === 'en' && $next_level['name_en'] ? $next_level['name_en'] : $next_level['name']) ?>" <?= e($next_level['icon']) ?></span>
                    <span><?= $paid_refs ?> / <?= $next_level['required_referrals'] ?></span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" style="width: <?= $progress_to_next ?>%"></div>
                </div>
                <p class="text-muted text-center"><?= $current_lang === 'en' ? "Invite $remaining more friends" : "Пригласите ещё $remaining друзей" ?></p>
            </div>
            <?php endif; ?>
        </div>
        <div class="referral-levels-grid">
            <?php foreach ($referral_levels as $lvl):
                $is_earned = $current_ref_level >= $lvl['level'];
                $is_current = $current_ref_level == $lvl['level'] - 1;
                $lvl_progress = min(100, round(($paid_refs / $lvl['required_referrals']) * 100));
            ?>
            <div class="referral-level-card <?= $is_earned ? 'earned' : '' ?> <?= $is_current ? 'current' : '' ?>">
                <div class="level-icon">
                    <?= $is_earned ? e($lvl['icon']) : '🔒' ?>
                </div>
                <div class="level-content">
                    <h3><?= e($current_lang === 'en' && $lvl['name_en'] ? $lvl['name_en'] : $lvl['name']) ?></h3>
                    <p class="level-requirement"><?= $lvl['required_referrals'] ?> <?= $current_lang === 'en' ? 'friends' : 'друзей' ?></p>
                    <p class="level-reward text-muted">
                        <?= e($current_lang === 'en' && $lvl['description_en'] ? $lvl['description_en'] : $lvl['description']) ?>
                    </p>
                    <?php if (!$is_earned): ?>
                    <div class="level-progress-bar">
                        <div class="progress-bar-mini">
                            <div class="progress-fill-mini" style="width: <?= $lvl_progress ?>%"></div>
                        </div>
                        <span class="progress-text-mini"><?= $paid_refs ?> / <?= $lvl['required_referrals'] ?></span>
                    </div>
                    <?php else: ?>
                    <p class="level-earned-text">✅ <?= $current_lang === 'en' ? 'Achieved!' : 'Получено!' ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <?php foreach ($achievements_by_category as $category => $achievements): ?>
    <section class="section">
        <h2><?= e($category) ?></h2>
        <div class="achievements-grid">
            <?php foreach ($achievements as $achievement): ?>
            <div class="achievement-card <?= $achievement['earned'] ? 'earned' : 'locked' ?>">
                <div class="achievement-icon">
<<<<<<< HEAD
                    <?php
                    $img_url = getAchievementImageUrl($achievement['id']);
                    if ($img_url): ?>
                        <img src="<?= e($img_url) ?>" alt="<?= e($achievement['display_name']) ?>" loading="lazy">
                    <?php else: ?>
                        <?= $achievement['earned'] ? e($achievement['icon']) : '🔒' ?>
                    <?php endif; ?>
                </div>
                <div class="achievement-content">
                    <h3><?= $achievement['earned'] || !$achievement['is_hidden'] ? e($achievement['display_name']) : '???' ?></h3>
                    <p class="text-muted">
                        <?= $achievement['earned'] || !$achievement['is_hidden'] ? e($achievement['display_description']) : t('hidden_achievement') ?>
=======
                    <?= $achievement['earned'] ? e($achievement['icon']) : '🔒' ?>
                </div>
                <div class="achievement-content">
                    <h3><?= $achievement['earned'] || !$achievement['is_hidden'] ? e($achievement['name']) : '???' ?></h3>
                    <p class="text-muted">
                        <?= $achievement['earned'] || !$achievement['is_hidden'] ? e($achievement['description']) : t('hidden_achievement') ?>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    </p>
                    <?php if ($achievement['earned']): ?>
                    <p class="achievement-date text-small text-muted">
                        <?= t('earned') ?>: <?= formatDate($achievement['earned_at']) ?>
                    </p>
<<<<<<< HEAD
                    <?php elseif ($user && !$achievement['is_hidden'] && $achievement['progress_target'] !== null): ?>
                    <div class="achievement-progress-bar" style="margin-top: 0.5rem;">
                        <div class="progress-bar-mini">
                            <div class="progress-fill-mini" style="width: <?= $achievement['progress_percent'] ?>%"></div>
                        </div>
                        <span class="progress-text-mini">
                            <?= $achievement['progress_current'] ?> / <?= $achievement['progress_target'] ?>
                        </span>
                    </div>
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endforeach; ?>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>