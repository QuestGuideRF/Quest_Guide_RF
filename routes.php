<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
$user = getCurrentUser();
$current_lang = getCurrentLanguage();
$page_title = t('my_routes_title');
$page_description = $current_lang === 'en'
    ? "View all your quest-excursions in QuestGuideRF. Active, completed and available routes in Moscow and other cities of Russia."
    : "Просмотрите все ваши экскурсии-квесты в QuestGuideRF. Активные, завершенные и доступные маршруты по Москве и другим городам России.";
$page_keywords = $current_lang === 'en'
    ? "my routes, routes, quests, active quests, completed quests, Moscow routes, QuestGuideRF"
    : "мои экскурсии, маршруты, квесты, активные квесты, завершенные квесты, маршруты Москвы, QuestGuideRF";
$user_routes = [];
if ($user) {
    try {
        $user_routes = getDB()->fetchAll(
            'SELECT up.*, r.name as route_name, r.name_en as route_name_en, r.price, r.estimated_duration,
                    r.distance, r.route_type, r.description, r.description_en,
                    c.name as city_name, c.name_en as city_name_en
             FROM user_progress up
             JOIN routes r ON up.route_id = r.id
             JOIN cities c ON r.city_id = c.id
             WHERE up.user_id = ?
             ORDER BY
                CASE
                    WHEN up.status IN ("in_progress", "IN_PROGRESS") THEN 1
                    WHEN up.status IN ("completed", "COMPLETED") THEN 2
                    ELSE 3
                END,
                up.started_at DESC',
            [$user['id']]
        );
    } catch (Exception $e) {
        error_log("Error fetching user routes: " . $e->getMessage());
        $user_routes = [];
    }
}
$routes_by_status = [
    'in_progress' => [],
    'completed' => [],
    'abandoned' => [],
];
if ($user) {
    foreach ($user_routes as $route) {
        $total_points = getDB()->fetch(
            'SELECT COUNT(*) as count FROM points WHERE route_id = ?',
            [$route['route_id']]
        )['count'];
        $route['total_points'] = $total_points;
        $route['progress_percent'] = $total_points > 0
            ? round(($route['points_completed'] / $total_points) * 100)
            : 0;
        $route['points'] = getDB()->fetchAll(
            'SELECT p.*,
                    uph.file_path as photo_path,
                    uph.created_at as photo_taken_at,
                    CASE
                        WHEN uph.id IS NOT NULL THEN "completed"
                        WHEN p.order = ? THEN "current"
                        ELSE "pending"
                    END as point_status
             FROM points p
             LEFT JOIN user_photos uph ON p.id = uph.point_id AND uph.user_id = ?
             WHERE p.route_id = ?
             ORDER BY p.order',
            [$route['current_point_order'], $user['id'], $route['route_id']]
        );
        $status = strtolower($route['status']);
        if ($status === 'in_progress' || $status === 'IN_PROGRESS') {
            $routes_by_status['in_progress'][] = $route;
        } elseif ($status === 'completed' || $status === 'COMPLETED') {
            $routes_by_status['completed'][] = $route;
        } elseif ($status === 'abandoned' || $status === 'ABANDONED') {
            $routes_by_status['abandoned'][] = $route;
        }
    }
}
$filter_city = isset($_GET['city']) ? (int)$_GET['city'] : 0;
$filter_tag = isset($_GET['tag']) ? (int)$_GET['tag'] : 0;
$filter_type = isset($_GET['type']) ? $_GET['type'] : '';
$filter_difficulty = isset($_GET['difficulty']) ? (int)$_GET['difficulty'] : 0;
$sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'name';
$sort_order = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'DESC' : 'ASC';
$cities = getDB()->fetchAll('SELECT id, name, name_en FROM cities WHERE is_active = 1 ORDER BY name');
$tags = getDB()->fetchAll(
    'SELECT id, name, name_en, type, icon, color FROM tags ORDER BY type, name'
);
$tags_by_type = [];
foreach ($tags as $tag) {
    $tags_by_type[$tag['type']][] = $tag;
}
try {
    $where_conditions = ['r.is_active = 1'];
    $params = [];
    if ($filter_city) {
        $where_conditions[] = 'r.city_id = ?';
        $params[] = $filter_city;
    }
    if ($filter_type && in_array($filter_type, ['WALKING', 'CYCLING', 'walking', 'cycling'])) {
        $where_conditions[] = 'r.route_type = ?';
        $params[] = strtoupper($filter_type);
    }
    if ($filter_difficulty) {
        $where_conditions[] = 'r.difficulty = ?';
        $params[] = $filter_difficulty;
    }
    if ($filter_tag) {
        $where_conditions[] = 'r.id IN (
            SELECT route_id FROM route_tags WHERE tag_id = ?
        )';
        $params[] = $filter_tag;
    }
    $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    $order_by = 'r.order ASC, r.name ASC';
    switch ($sort_by) {
        case 'price':
            $order_by = "r.price $sort_order";
            break;
        case 'duration':
            $order_by = "r.estimated_duration $sort_order";
            break;
        case 'distance':
            $order_by = "r.distance $sort_order";
            break;
        case 'name':
        default:
            $order_by = "r.name $sort_order";
            break;
    }
    $all_routes = getDB()->fetchAll(
        "SELECT r.*, c.name as city_name, c.name_en as city_name_en,
                COUNT(DISTINCT p.id) as points_count,
                GROUP_CONCAT(DISTINCT t.id) as tag_ids,
                GROUP_CONCAT(DISTINCT t.name) as tag_names,
                GROUP_CONCAT(DISTINCT t.name_en) as tag_names_en,
                GROUP_CONCAT(DISTINCT t.icon) as tag_icons
         FROM routes r
         JOIN cities c ON r.city_id = c.id
         LEFT JOIN points p ON r.id = p.route_id
         LEFT JOIN route_tags rt ON r.id = rt.route_id
         LEFT JOIN tags t ON rt.tag_id = t.id
         $where_clause
         GROUP BY r.id
         ORDER BY $order_by",
        $params
    );
    foreach ($all_routes as &$route) {
        $route['total_points'] = (int)$route['points_count'];
        $route['tags'] = [];
        if ($route['tag_ids']) {
            $tag_ids = explode(',', $route['tag_ids']);
            $tag_names = explode(',', $route['tag_names']);
            $tag_names_en = $route['tag_names_en'] ? explode(',', $route['tag_names_en']) : [];
            $tag_icons = explode(',', $route['tag_icons']);
            foreach ($tag_ids as $idx => $tag_id) {
                $route['tags'][] = [
                    'id' => $tag_id,
                    'name' => $tag_names[$idx] ?? '',
                    'name_en' => $tag_names_en[$idx] ?? null,
                    'icon' => $tag_icons[$idx] ?? '',
                ];
            }
        }
    }
    unset($route);
} catch (Exception $e) {
    error_log("Error fetching all routes: " . $e->getMessage());
    $all_routes = [];
}
require_once __DIR__ . '/includes/header.php';
?>
<style>
.routes-page .filter-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.routes-page .filter-row {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}
.routes-page .filter-row-top {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
}
.routes-page .filter-row-bottom {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    width: 100%;
}
.routes-page .filter-group {
    flex: 0 0 auto;
    min-width: 200px;
}
.routes-page .filter-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    text-align: center;
}
.routes-page .filter-select-compact {
    width: 100% !important;
    min-width: 200px !important;
    max-width: 250px !important;
    padding: 0.5rem 2rem 0.5rem 0.75rem !important;
    font-size: 0.95rem !important;
    border: 1px solid var(--border-color) !important;
    border-radius: var(--radius-md) !important;
    background: var(--bg-primary) !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M4 6L8 10L12 6' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 14px !important;
    color: var(--text-primary) !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}
.routes-page .filter-select-compact:hover {
    border-color: var(--primary) !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15) !important;
}
.routes-page .filter-select-compact:focus {
    outline: none !important;
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
}
.routes-page .filter-actions {
    display: flex;
    gap: 0.5rem;
    flex: 0 0 auto;
}
@media (max-width: 768px) {
    .routes-page .filter-row-top {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
    }
    .routes-page .filter-group {
        width: 100%;
        min-width: 100%;
    }
    .routes-page .filter-select-compact {
        font-size: 16px !important;
        max-width: 100% !important;
    }
    .routes-page .filter-row-bottom {
        width: 100%;
        flex-direction: column;
    }
    .routes-page .filter-row-bottom .btn {
        width: 100%;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}
</style>
<main class="main-content routes-page">
<div class="container">
    <div class="page-header">
        <h1>🗺️ <?= $user ? t('my_routes_title') : t('available_routes') ?></h1>
        <p class="text-muted"><?= $user ? t('my_routes_subtitle') : ($current_lang === 'en' ? 'Discover amazing quest-excursions' : 'Откройте для себя удивительные квест-экскурсии') ?></p>
    </div>
    <!-- Раздел: Мои экскурсии -->
    <?php if ($user && !empty($user_routes)): ?>
    <div class="section-divider">
        <h2 class="section-title"><?= t('my_routes_title') ?></h2>
    </div>
    <?php elseif ($user && empty($user_routes)): ?>
    <div class="empty-state">
        <div class="empty-icon">🗺️</div>
        <h2><?= t('no_routes_yet') ?></h2>
        <p><?= t('start_first_adventure') ?></p>
        <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary" target="_blank">
            <?= t('open_bot') ?>
        </a>
    </div>
    <?php endif; ?>
    <?php if ($user && !empty($user_routes)): ?>
    <!-- Активные маршруты -->
    <?php if (!empty($routes_by_status['in_progress'])): ?>
    <section class="section">
        <h2><?= t('in_progress') ?> (<?= count($routes_by_status['in_progress']) ?>)</h2>
        <div class="routes-list">
            <?php foreach ($routes_by_status['in_progress'] as $route): ?>
            <div class="route-card">
                <div class="route-header" onclick="toggleRouteDetails(<?= $route['route_id'] ?>)">
                    <div class="route-type">
                        <?= (strtoupper($route['route_type']) == 'WALKING' || $route['route_type'] == 'walking') ? '🚶' : '🚴' ?>
                    </div>
                    <div class="route-info">
                        <h3><?= getLocalizedName(['name' => $route['route_name'], 'name_en' => $route['route_name_en'] ?? null]) ?></h3>
                        <p class="text-muted"><?= getLocalizedName(['name' => $route['city_name'], 'name_en' => $route['city_name_en'] ?? null]) ?></p>
                    </div>
                    <div class="route-status">
                        <?= getStatusBadge($route['status']) ?>
                    </div>
                    <button class="route-toggle" type="button">
                        <span class="toggle-icon">▼</span>
                    </button>
                </div>
                <div class="route-meta">
                    <span>⏱️ <?= formatDuration($route['estimated_duration']) ?></span>
                    <span>📍 <?= formatDistance($route['distance']) ?></span>
                    <span>💰 <?= formatPrice($route['price']) ?></span>
                </div>
                <div class="route-progress-section">
                    <div class="progress-info">
                        <span><?= t('completed_progress') ?>: <?= $route['points_completed'] ?> / <?= $route['total_points'] ?></span>
                        <span><?= $route['progress_percent'] ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $route['progress_percent'] ?>%"></div>
                    </div>
                </div>
                <!-- Детальная информация о точках -->
                <div class="route-details" id="route-details-<?= $route['route_id'] ?>" style="display: none;">
                    <div class="points-list">
                        <?php foreach ($route['points'] as $point): ?>
                        <div class="point-item <?= $point['point_status'] ?>">
                            <div class="point-header">
                                <div class="point-status-icon">
                                    <?php if ($point['point_status'] == 'completed'): ?>
                                        ✅
                                    <?php elseif ($point['point_status'] == 'current'): ?>
                                        ⏳
                                    <?php else: ?>
                                        ⭕️
                                    <?php endif; ?>
                                </div>
                                <div class="point-info">
                                    <h4><?= $point['order'] ?>. <?= e($point['name']) ?></h4>
                                    <p class="text-muted"><?= e($point['description'] ?? '') ?></p>
                                    <?php if ($point['require_pose']): ?>
                                    <span class="point-requirement">
                                        🤸 <?= t('pose_required') ?>: <?php
                                            $poses = [
                                                'hands_up' => t('pose_hands_up'),
                                                'heart' => t('pose_heart'),
                                                'point' => t('pose_point')
                                            ];
                                            echo $poses[$point['require_pose']] ?? $point['require_pose'];
                                        ?>
                                    </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($point['point_status'] == 'completed' && $point['photo_path']): ?>
                            <div class="point-photo">
                                <img src="<?= e($point['photo_path']) ?>"
                                     alt="<?= e($point['name']) ?>"
                                     loading="lazy"
                                     onerror="this.src='/assets/img/no-photo.png';">
                                <div class="photo-info">
                                    <span class="text-muted text-small">
                                        📅 <?= formatDate($point['photo_taken_at']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                            <?php if ($point['task_text']): ?>
                            <div class="point-task">
                                <strong><?= t('task') ?>:</strong> <?= nl2br(e($point['task_text'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="route-footer">
                    <span class="text-muted text-small">
                        <?= t('started') ?>: <?= formatDate($route['started_at']) ?>
                    </span>
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary btn-sm" target="_blank">
                        <?= t('continue_quest') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <!-- Завершенные маршруты -->
    <?php if (!empty($routes_by_status['completed'])): ?>
    <section class="section">
        <h2><?= t('completed') ?> (<?= count($routes_by_status['completed']) ?>)</h2>
        <div class="routes-list">
            <?php foreach ($routes_by_status['completed'] as $route): ?>
            <div class="route-card completed">
                <div class="route-header" onclick="toggleRouteDetails(<?= $route['route_id'] ?>)">
                    <div class="route-type">
                        <?= (strtoupper($route['route_type']) == 'WALKING' || $route['route_type'] == 'walking') ? '🚶' : '🚴' ?>
                    </div>
                    <div class="route-info">
                        <h3><?= getLocalizedName(['name' => $route['route_name'], 'name_en' => $route['route_name_en'] ?? null]) ?></h3>
                        <p class="text-muted"><?= getLocalizedName(['name' => $route['city_name'], 'name_en' => $route['city_name_en'] ?? null]) ?></p>
                    </div>
                    <div class="route-status">
                        <?= getStatusBadge($route['status']) ?>
                    </div>
                    <button class="route-toggle" type="button">
                        <span class="toggle-icon">▼</span>
                    </button>
                </div>
                <div class="route-meta">
                    <span>📍 <?= $route['points_completed'] ?> / <?= $route['total_points'] ?> <?= t('points') ?></span>
                    <?php if ($route['completed_at']):
                        $duration = (strtotime($route['completed_at']) - strtotime($route['started_at'])) / 60;
                    ?>
                    <span>⏱️ <?= formatDuration($duration) ?></span>
                    <?php endif; ?>
                </div>
                <!-- Детальная информация о точках -->
                <div class="route-details" id="route-details-<?= $route['route_id'] ?>" style="display: none;">
                    <div class="points-list">
                        <?php foreach ($route['points'] as $point): ?>
                        <div class="point-item completed">
                            <div class="point-header">
                                <div class="point-status-icon">✅</div>
                                <div class="point-info">
                                    <h4><?= $point['order'] ?>. <?= e($point['name']) ?></h4>
                                    <p class="text-muted"><?= e($point['description']) ?></p>
                                </div>
                            </div>
                            <?php if ($point['photo_path']): ?>
                            <div class="point-photo">
                                <img src="<?= e($point['photo_path']) ?>"
                                     alt="<?= e($point['name']) ?>"
                                     loading="lazy"
                                     onerror="this.src='/assets/img/no-photo.png';">
                                <div class="photo-info">
                                    <span class="text-muted text-small">
                                        📅 <?= formatDate($point['photo_taken_at']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="route-footer">
                    <span class="text-muted text-small">
                        <?= t('completed') ?>: <?= formatDate($route['completed_at']) ?>
                    </span>
                    <a href="/photos.php?route=<?= $route['route_id'] ?><?= isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '' ?>" class="btn btn-outline btn-sm">
                        <?= t('photos') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <!-- Прерванные маршруты -->
    <?php if (!empty($routes_by_status['abandoned'])): ?>
    <section class="section">
        <h2><?= t('abandoned') ?> (<?= count($routes_by_status['abandoned']) ?>)</h2>
        <div class="routes-list">
            <?php foreach ($routes_by_status['abandoned'] as $route): ?>
            <div class="route-card abandoned">
                <div class="route-header" onclick="toggleRouteDetails(<?= $route['route_id'] ?>)">
                    <div class="route-type">
                        <?= $route['route_type'] == 'walking' ? '🚶' : '🚴' ?>
                    </div>
                    <div class="route-info">
                        <h3><?= getLocalizedName(['name' => $route['route_name'], 'name_en' => $route['route_name_en'] ?? null]) ?></h3>
                        <p class="text-muted"><?= getLocalizedName(['name' => $route['city_name'], 'name_en' => $route['city_name_en'] ?? null]) ?></p>
                    </div>
                    <div class="route-status">
                        <?= getStatusBadge($route['status']) ?>
                    </div>
                    <button class="route-toggle" type="button">
                        <span class="toggle-icon">▼</span>
                    </button>
                </div>
                <div class="route-meta">
                    <span>📍 <?= $route['points_completed'] ?> / <?= $route['total_points'] ?> <?= t('points') ?></span>
                </div>
                <div class="route-footer">
                    <span class="text-muted text-small">
                        <?= t('started') ?>: <?= formatDate($route['started_at']) ?>
                    </span>
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>" class="btn btn-primary btn-sm" target="_blank">
                        <?= t('continue_quest') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
    <?php endif; ?>
    <!-- Раздел: Все экскурсии -->
    <?php if (!empty($all_routes)): ?>
    <div class="section-divider" style="<?= $user && !empty($user_routes) ? 'margin-top: 3rem; padding-top: 2rem;' : '' ?>">
        <h2 class="section-title"><?= t('all_routes') ?></h2>
        <p class="text-muted text-center" style="margin-top: 0.75rem; font-size: 0.95rem;">
            <?= $current_lang === 'en' ? 'Discover all available quest-excursions and start your adventure' : 'Откройте для себя все доступные квест-экскурсии и начните приключение' ?>
        </p>
    </div>
    <!-- Фильтры и сортировка -->
    <div class="filter-card" style="margin-bottom: 2rem;">
        <form method="get" class="filter-form">
            <?php if (isset($_GET['lang'])): ?>
                <input type="hidden" name="lang" value="<?= htmlspecialchars($_GET['lang']) ?>">
            <?php endif; ?>
            <div class="filter-row">
                <div class="filter-row-top">
                    <div class="filter-group">
                        <label class="filter-label"><?= t('city') ?></label>
                        <select name="city" class="filter-select-compact">
                            <option value=""><?= t('all_cities') ?></option>
                            <?php foreach ($cities as $city): ?>
                            <option value="<?= $city['id'] ?>" <?= $filter_city == $city['id'] ? 'selected' : '' ?>>
                                <?= getLocalizedName(['name' => $city['name'], 'name_en' => $city['name_en'] ?? null]) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label"><?= t('type') ?></label>
                        <select name="type" class="filter-select-compact">
                            <option value=""><?= t('all_types') ?></option>
                            <option value="WALKING" <?= $filter_type == 'WALKING' ? 'selected' : '' ?>><?= t('walking') ?></option>
                            <option value="CYCLING" <?= $filter_type == 'CYCLING' ? 'selected' : '' ?>><?= t('cycling') ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label"><?= t('difficulty') ?></label>
                        <select name="difficulty" class="filter-select-compact">
                            <option value=""><?= t('any_difficulty') ?></option>
                            <option value="1" <?= $filter_difficulty == 1 ? 'selected' : '' ?>><?= t('easy') ?></option>
                            <option value="2" <?= $filter_difficulty == 2 ? 'selected' : '' ?>><?= t('medium') ?></option>
                            <option value="3" <?= $filter_difficulty == 3 ? 'selected' : '' ?>><?= t('hard') ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label"><?= t('tag') ?></label>
                        <select name="tag" class="filter-select-compact">
                            <option value=""><?= t('all_tags') ?></option>
                            <?php foreach ($tags as $tag): ?>
                            <option value="<?= $tag['id'] ?>" <?= $filter_tag == $tag['id'] ? 'selected' : '' ?>>
                                <?= $tag['icon'] ?> <?= getLocalizedName(['name' => $tag['name'], 'name_en' => $tag['name_en'] ?? null]) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label"><?= t('sort_by') ?></label>
                        <select name="sort" class="filter-select-compact">
                            <option value="name" <?= $sort_by == 'name' ? 'selected' : '' ?>><?= t('sort_name') ?></option>
                            <option value="price" <?= $sort_by == 'price' ? 'selected' : '' ?>><?= t('sort_price') ?></option>
                            <option value="duration" <?= $sort_by == 'duration' ? 'selected' : '' ?>><?= t('sort_duration') ?></option>
                            <option value="distance" <?= $sort_by == 'distance' ? 'selected' : '' ?>><?= t('sort_distance') ?></option>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label class="filter-label"><?= t('order') ?></label>
                        <select name="order" class="filter-select-compact">
                            <option value="asc" <?= $sort_order == 'ASC' ? 'selected' : '' ?>><?= t('ascending') ?></option>
                            <option value="desc" <?= $sort_order == 'DESC' ? 'selected' : '' ?>><?= t('descending') ?></option>
                        </select>
                    </div>
                </div>
                <div class="filter-row-bottom">
                    <button type="submit" class="btn btn-primary btn-sm">🔍 <?= t('apply_filters') ?></button>
                    <a href="/routes.php<?= isset($_GET['lang']) ? '?lang=' . htmlspecialchars($_GET['lang']) : '' ?>" class="btn btn-outline-secondary btn-sm">🔄 <?= t('reset') ?></a>
                </div>
            </div>
        </form>
    </div>
    <section class="section">
        <div class="routes-list">
            <?php foreach ($all_routes as $route): ?>
            <div class="route-card available" onclick="window.location.href='/routes/view.php?id=<?= $route['id'] ?><?= isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '' ?>'">
                <div class="route-header">
                    <div class="route-type">
                        <?= (strtoupper($route['route_type']) == 'WALKING' || $route['route_type'] == 'walking') ? '🚶' : '🚴' ?>
                    </div>
                    <div class="route-info">
                        <h3><?= getLocalizedName(['name' => $route['name'], 'name_en' => $route['name_en'] ?? null]) ?></h3>
                        <p class="text-muted"><?= getLocalizedName(['name' => $route['city_name'], 'name_en' => $route['city_name_en'] ?? null]) ?></p>
                        <?php if ($route['description']):
                            $description = getLocalizedName(['name' => $route['description'], 'name_en' => $route['description_en'] ?? null]);
                            $description_short = mb_strlen($description) > 150 ? mb_substr($description, 0, 150) . '...' : $description;
                        ?>
                        <p class="text-muted text-small"><?= e($description_short) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($route['tags'])): ?>
                        <div class="route-tags" style="margin-top: 0.5rem; display: flex; flex-wrap: wrap; gap: 0.25rem;">
                            <?php foreach ($route['tags'] as $tag): ?>
                            <span class="badge" style="background: <?= $tag['color'] ?? '#6c757d' ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-size: 0.75rem;">
                                <?= $tag['icon'] ?> <?= getLocalizedName(['name' => $tag['name'], 'name_en' => $tag['name_en'] ?? null]) ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="route-meta">
                    <?php if ($route['estimated_duration']): ?>
                    <span>⏱️ <?= formatDuration($route['estimated_duration']) ?></span>
                    <?php endif; ?>
                    <?php if ($route['distance']): ?>
                    <span>📍 <?= formatDistance($route['distance']) ?></span>
                    <?php endif; ?>
                    <span>💰 <?= formatPrice($route['price']) ?></span>
                    <?php if ($route['total_points']): ?>
                    <span>📍 <?= $route['total_points'] ?> <?= t('points') ?></span>
                    <?php endif; ?>
                    <?php if ($route['difficulty']): ?>
                    <span>
                        <?php
                        $difficulty_icons = [1 => '⭐', 2 => '⭐⭐', 3 => '⭐⭐⭐'];
                        echo $difficulty_icons[$route['difficulty']] ?? '';
                        ?>
                    </span>
                    <?php endif; ?>
                </div>
                <div class="route-footer" onclick="event.stopPropagation()">
                    <a href="/routes/view.php?id=<?= $route['id'] ?><?= isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '' ?>" class="btn btn-outline btn-sm">
                        <?= t('view_details') ?>
                    </a>
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>?start=route_<?= $route['id'] ?>" class="btn btn-primary btn-sm" target="_blank">
                        <?= t('start_quest') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>
</main>
<script>
function toggleRouteDetails(routeId) {
    const details = document.getElementById('route-details-' + routeId);
    const card = details.closest('.route-card');
    const icon = card.querySelector('.toggle-icon');
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.textContent = '▲';
        card.classList.add('expanded');
    } else {
        details.style.display = 'none';
        icon.textContent = '▼';
        card.classList.remove('expanded');
    }
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>