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
    'SELECT r.*, u.first_name, u.username
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
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>