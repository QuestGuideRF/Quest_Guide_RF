<?php
require_once __DIR__ . '/../includes/init.php';
$route_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$route_id) {
    header('Location: /index.php');
    exit;
}
$route = getDB()->fetch(
    'SELECT r.*, c.name as city_name
     FROM routes r
     JOIN cities c ON r.city_id = c.id
     WHERE r.id = ? AND r.is_active = 1',
    [$route_id]
);
if (!$route) {
    header('Location: /404.php');
    exit;
}
$points = getDB()->fetchAll(
    'SELECT * FROM points WHERE route_id = ? ORDER BY `order`',
    [$route_id]
);
$tags = getDB()->fetchAll(
    'SELECT t.* FROM tags t
     JOIN route_tags rt ON t.id = rt.tag_id
     WHERE rt.route_id = ?
     ORDER BY t.type, t.name',
    [$route_id]
);
$tags_by_type = [];
foreach ($tags as $tag) {
    $tags_by_type[$tag['type']][] = $tag;
}
$reviews = getDB()->fetchAll(
<<<<<<< HEAD
    'SELECT r.*, u.first_name, u.username, u.is_profile_public
=======
    'SELECT r.*, u.first_name, u.username
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     WHERE r.route_id = ?
     ORDER BY r.created_at DESC
     LIMIT 10',
    [$route_id]
);
$avg_rating = 0;
if (!empty($reviews)) {
    $avg_rating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}
$avg_time_result = getDB()->fetch(
    'SELECT AVG(TIMESTAMPDIFF(MINUTE, started_at, completed_at)) as avg_time
     FROM user_progress
     WHERE route_id = ? AND status = "COMPLETED" AND completed_at IS NOT NULL',
    [$route_id]
);
$avg_time = $avg_time_result['avg_time'] ?? null;
$completions = getDB()->fetch(
    'SELECT COUNT(*) as count FROM user_progress WHERE route_id = ? AND status = "COMPLETED"',
    [$route_id]
)['count'];
$page_title = $route['name'] . ' - ' . $route['city_name'];
$page_description = $route['description'] ?? "Квест-экскурсия по маршруту {$route['name']} в городе {$route['city_name']}";
require_once __DIR__ . '/../includes/header.php';
?>
<<<<<<< HEAD
<main class="main-content page-route-view">
<div class="container route-view-container">
    <nav class="route-breadcrumb" aria-label="breadcrumb">
        <a href="/dashboard.php">Главная</a>
        <span class="route-breadcrumb-sep">→</span>
        <a href="/routes.php">Маршруты</a>
        <span class="route-breadcrumb-sep">→</span>
        <span class="route-breadcrumb-current"><?= htmlspecialchars($route['name']) ?></span>
    </nav>
    <header class="route-hero">
        <h1 class="route-title"><?= htmlspecialchars($route['name']) ?></h1>
        <p class="route-city"><?= htmlspecialchars($route['city_name']) ?></p>
    </header>
    <div class="route-layout">
        <div class="route-main">
            <?php if ($route['description']): ?>
            <section class="route-card route-card-description">
                <h2 class="route-card-title">📝 Описание</h2>
                <div class="route-description-text"><?= nl2br(htmlspecialchars($route['description'])) ?></div>
            </section>
            <?php endif; ?>
            <?php if (!empty($reviews)): ?>
            <section class="route-card route-card-reviews">
                <div class="route-reviews-header">
                    <h2 class="route-card-title">⭐ Отзывы (<?= count($reviews) ?>)</h2>
                    <div class="route-rating-badge">
                        <span class="route-stars"><?php for ($i = 1; $i <= 5; $i++): ?><?= $i <= round($avg_rating) ? '★' : '☆' ?><?php endfor; ?></span>
                        <span class="route-rating-value"><?= number_format($avg_rating, 2) ?> / 5</span>
                    </div>
                </div>
                <div class="route-reviews-list">
                    <?php foreach ($reviews as $review): ?>
                    <article class="route-review-item">
                        <div class="route-review-head">
                            <div class="route-review-author">
                                <?php if ($review['is_profile_public'] ?? true): ?>
                                <a href="/<?= e($review['username'] ?: $review['user_id']) ?>" style="color: inherit; text-decoration: none;">
                                    <strong><?= e($review['first_name']) ?></strong>
                                </a>
                                <?php if ($review['username']): ?>
                                    <span class="route-review-username">@<?= e($review['username']) ?></span>
                                <?php endif; ?>
                                <?php else: ?>
                                <strong>Пользователь</strong>
                                <span class="route-review-username" style="font-style:italic;">профиль скрыт</span>
                                <?php endif; ?>
                            </div>
                            <span class="route-review-stars"><?php for ($i = 1; $i <= $review['rating']; $i++): ?>⭐<?php endfor; ?></span>
                        </div>
                        <?php if ($review['text']): ?>
                        <p class="route-review-text"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                        <?php endif; ?>
                        <time class="route-review-date"><?= date('d.m.Y H:i', strtotime($review['created_at'])) ?></time>
                    </article>
                    <?php endforeach; ?>
                </div>
            </section>
            <?php endif; ?>
        </div>
        <aside class="route-sidebar">
            <div class="route-card route-card-info">
                <h2 class="route-card-title">📊 Информация</h2>
                <ul class="route-info-list">
                    <li><span class="route-info-label">💰 Цена</span><span class="route-info-value"><?= $route['price'] ?> грошей</span></li>
                    <?php if ($route['estimated_duration']): ?>
                    <li><span class="route-info-label">⏱️ Длительность</span><span class="route-info-value">~<?= formatDuration($route['estimated_duration']) ?></span></li>
                    <?php endif; ?>
                    <?php if ($avg_time): ?>
                    <li><span class="route-info-label">⏰ Среднее время</span><span class="route-info-value">~<?= formatDuration($avg_time) ?></span></li>
                    <?php endif; ?>
                    <?php if ($route['distance']): ?>
                    <li><span class="route-info-label">📏 Расстояние</span><span class="route-info-value"><?= formatDistance($route['distance']) ?></span></li>
                    <?php endif; ?>
                    <?php if ($route['difficulty']): ?>
                    <li><span class="route-info-label">⭐ Сложность</span><span class="route-info-value"><?= ([1 => 'Легкий', 2 => 'Средний', 3 => 'Сложный'])[$route['difficulty']] ?? 'Средний' ?></span></li>
                    <?php endif; ?>
                    <li><span class="route-info-label">📍 Точек</span><span class="route-info-value"><?= count($points) ?></span></li>
                    <li><span class="route-info-label">👥 Прохождений</span><span class="route-info-value"><?= $completions ?></span></li>
                </ul>
                <a href="https://t.me/<?= e(BOT_USERNAME) ?>?start=route_<?= $route_id ?>" class="btn btn-primary route-cta" target="_blank">▶️ Начать в боте</a>
                <?php if (isLoggedIn()): ?>
                <a href="/routes.php" class="btn btn-outline route-back">◀️ К списку маршрутов</a>
                <?php endif; ?>
            </div>
            <?php if (!empty($tags)): ?>
            <div class="route-card route-card-tags">
                <h2 class="route-card-title">🏷 Теги</h2>
                <div class="route-tags">
                    <?php foreach ($tags as $tag): ?>
                    <span class="route-tag"><?= $tag['icon'] ?> <?= htmlspecialchars($tag['name']) ?></span>
=======
<main class="main-content">
<div class="container py-4">
    <!-- Хлебные крошки -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/">Главная</a></li>
            <li class="breadcrumb-item"><a href="/routes.php">Маршруты</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($route['name']) ?></li>
        </ol>
    </nav>
    <!-- Заголовок -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-3"><?= htmlspecialchars($route['name']) ?></h1>
            <p class="text-muted lead"><?= htmlspecialchars($route['city_name']) ?></p>
        </div>
    </div>
    <div class="row">
        <!-- Основная информация -->
        <div class="col-md-8">
            <!-- Описание -->
            <?php if ($route['description']): ?>
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="card-title">📝 Описание</h3>
                    <p><?= nl2br(htmlspecialchars($route['description'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
            <!-- Отзывы -->
            <?php if (!empty($reviews)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="mb-0">⭐ Отзывы (<?= count($reviews) ?>)</h3>
                    <div class="text-warning">
                        <?php for($i = 1; $i <= 5; $i++): ?>
                            <?= $i <= round($avg_rating) ? '★' : '☆' ?>
                        <?php endfor; ?>
                        <span class="ms-2"><?= number_format($avg_rating, 1) ?> / 5</span>
                    </div>
                </div>
                <div class="card-body">
                    <?php foreach ($reviews as $review): ?>
                    <div class="review-item mb-3 p-3" style="background: var(--bg-tertiary); border-radius: 5px;">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <strong><?= htmlspecialchars($review['first_name']) ?></strong>
                                <?php if ($review['username']): ?>
                                    <span class="text-muted">@<?= htmlspecialchars($review['username']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-warning">
                                <?php for($i = 1; $i <= $review['rating']; $i++): ?>⭐<?php endfor; ?>
                            </div>
                        </div>
                        <?php if ($review['text']): ?>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                        <?php endif; ?>
                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($review['created_at'])) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <!-- Боковая панель -->
        <div class="col-md-4">
            <!-- Информация -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">📊 Информация</h4>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-2"><strong>💰 Цена:</strong> <?= $route['price'] ?>₽</li>
                        <?php if ($route['estimated_duration']): ?>
                        <li class="mb-2"><strong>⏱️ Длительность:</strong> ~<?= formatDuration($route['estimated_duration']) ?></li>
                        <?php endif; ?>
                        <?php if ($avg_time): ?>
                        <li class="mb-2"><strong>⏰ Среднее время:</strong> ~<?= formatDuration($avg_time) ?></li>
                        <?php endif; ?>
                        <?php if ($route['distance']): ?>
                        <li class="mb-2"><strong>📏 Расстояние:</strong> <?= formatDistance($route['distance']) ?></li>
                        <?php endif; ?>
                        <?php if ($route['difficulty']): ?>
                        <li class="mb-2"><strong>⭐ Сложность:</strong>
                            <?php
                            $difficulty_names = [1 => 'Легкий', 2 => 'Средний', 3 => 'Сложный'];
                            echo $difficulty_names[$route['difficulty']] ?? 'Средний';
                            ?>
                        </li>
                        <?php endif; ?>
                        <li class="mb-2"><strong>📍 Точек:</strong> <?= count($points) ?></li>
                        <li class="mb-2"><strong>👥 Прохождений:</strong> <?= $completions ?></li>
                    </ul>
                    <hr>
                    <a href="https://t.me/<?= e(BOT_USERNAME) ?>?start=route_<?= $route_id ?>"
                       class="btn btn-primary w-100 mb-2" target="_blank">
                        ▶️ Начать в боте
                    </a>
                    <?php if (isLoggedIn()): ?>
                    <a href="/routes.php" class="btn btn-outline-secondary w-100">
                        ◀️ К списку маршрутов
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <!-- Теги -->
            <?php if (!empty($tags)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h4 class="mb-0">🏷 Теги</h4>
                </div>
                <div class="card-body">
                    <?php foreach ($tags as $tag): ?>
                        <span class="badge bg-secondary me-1 mb-1">
                            <?= $tag['icon'] ?> <?= htmlspecialchars($tag['name']) ?>
                        </span>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
<<<<<<< HEAD
        </aside>
=======
        </div>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>