<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
require_once __DIR__ . '/../includes/audit_log.php';
$pdo = getDB()->getConnection();
$route_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$route_id) {
    header('Location: /admin/routes/list.php');
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM routes WHERE id = ?");
$stmt->execute([$route_id]);
$old_route = $stmt->fetch(PDO::FETCH_ASSOC);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            UPDATE routes
            SET name = ?,
                name_en = ?,
                description = ?,
                description_en = ?,
                city_id = ?,
                price = ?,
                route_type = ?,
                is_active = ?,
                estimated_duration = ?,
                max_hints_per_route = ?,
                difficulty = ?,
                duration_minutes = ?,
                season = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['name_en'] ?? null,
            $_POST['description'],
            $_POST['description_en'] ?? null,
            $_POST['city_id'],
            $_POST['price'],
            $_POST['route_type'],
            isset($_POST['is_active']) ? 1 : 0,
            $_POST['estimated_duration'],
            $_POST['max_hints_per_route'],
            $_POST['difficulty'],
            $_POST['duration_minutes'],
            $_POST['season'],
            $route_id
        ]);
        $pdo->exec("DELETE FROM route_tags WHERE route_id = $route_id");
        if (!empty($_POST['tags'])) {
            $stmt = $pdo->prepare("INSERT INTO route_tags (route_id, tag_id) VALUES (?, ?)");
            foreach ($_POST['tags'] as $tag_id) {
                $stmt->execute([$route_id, $tag_id]);
            }
        }
        $pdo->commit();
        logAudit('route', $route_id, 'update', $old_route, $_POST, 'Маршрут обновлен');
        $_SESSION['success'] = 'Маршрут успешно обновлен';
        header('Location: /admin/routes/list.php');
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$page_title = 'Редактирование маршрута';
require_once __DIR__ . '/../includes/header.php';
if (!isset($old_route)) {
    $stmt = $pdo->prepare("SELECT * FROM routes WHERE id = ?");
    $stmt->execute([$route_id]);
    $route = $stmt->fetch();
    if (!$route) {
        header('Location: /admin/routes/list.php');
        exit;
    }
} else {
    $route = $old_route;
}
$cities = $pdo->query("SELECT * FROM cities ORDER BY name")->fetchAll();
$stmt = $pdo->prepare("
    SELECT p.*, COUNT(h.id) as hints_count
    FROM points p
    LEFT JOIN hints h ON p.id = h.point_id
    WHERE p.route_id = ?
    GROUP BY p.id
    ORDER BY p.order
");
$stmt->execute([$route_id]);
$points = $stmt->fetchAll();
$all_tags = $pdo->query("SELECT * FROM tags ORDER BY type, name")->fetchAll();
$tags_by_type = [];
foreach ($all_tags as $tag) {
    $tags_by_type[$tag['type']][] = $tag;
}
$stmt = $pdo->prepare("SELECT tag_id FROM route_tags WHERE route_id = ?");
$stmt->execute([$route_id]);
$selected_tags = array_column($stmt->fetchAll(), 'tag_id');
$type_names = [
    'topic' => 'Темы',
    'age' => 'Возраст',
    'difficulty' => 'Сложность',
    'duration' => 'Длительность',
    'season' => 'Сезон'
];
?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i>Редактирование маршрута</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Название маршрута (Русский) *</label>
                        <div class="input-group">
                            <input type="text" name="name" id="route_name_ru" class="form-control"
                                   value="<?= htmlspecialchars($route['name']) ?>" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="translateField('route_name_ru', 'route_name_en')" title="Перевести на английский">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название маршрута (English)</label>
                        <input type="text" name="name_en" id="route_name_en" class="form-control"
                               value="<?= htmlspecialchars($route['name_en'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (Русский) *</label>
                        <div class="input-group">
                            <textarea name="description" id="route_description_ru" class="form-control" rows="5" required><?= htmlspecialchars($route['description']) ?></textarea>
                            <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('route_description_ru', 'route_description_en')" title="Перевести на английский" style="margin-top: 0;">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (English)</label>
                        <textarea name="description_en" id="route_description_en" class="form-control" rows="5"><?= htmlspecialchars($route['description_en'] ?? '') ?></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Город *</label>
                            <select name="city_id" class="form-select" required>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>"
                                            <?= $route['city_id'] == $city['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($city['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Цена (₽) *</label>
                            <input type="number" name="price" class="form-control"
                                   value="<?= $route['price'] ?>" min="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Тип маршрута *</label>
                            <select name="route_type" class="form-select" required>
                                <option value="WALKING" <?= $route['route_type'] == 'WALKING' ? 'selected' : '' ?>>
                                    🚶 Пеший
                                </option>
                                <option value="CYCLING" <?= $route['route_type'] == 'CYCLING' ? 'selected' : '' ?>>
                                    🚴 Велосипедный
                                </option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Сложность *</label>
                            <select name="difficulty" class="form-select" required>
                                <option value="1" <?= ($route['difficulty'] ?? 2) == 1 ? 'selected' : '' ?>>
                                    ⭐ Легкий
                                </option>
                                <option value="2" <?= ($route['difficulty'] ?? 2) == 2 ? 'selected' : '' ?>>
                                    ⭐⭐ Средний
                                </option>
                                <option value="3" <?= ($route['difficulty'] ?? 2) == 3 ? 'selected' : '' ?>>
                                    ⭐⭐⭐ Сложный
                                </option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ориентировочное время (минуты) *</label>
                            <input type="number" name="estimated_duration" class="form-control"
                                   value="<?= $route['estimated_duration'] ?>" min="0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Максимум подсказок *</label>
                            <input type="number" name="max_hints_per_route" class="form-control"
                                   value="<?= $route['max_hints_per_route'] ?>" min="0" max="10" required>
                            <small class="text-muted">Количество подсказок на весь маршрут</small>
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-3">Дополнительные параметры</h6>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Длительность (минуты)</label>
                            <input type="number" name="duration_minutes" class="form-control"
                                   value="<?= $route['duration_minutes'] ?? 60 ?>" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Сезон</label>
                            <select name="season" class="form-select">
                                <option value="all" <?= ($route['season'] ?? 'all') == 'all' ? 'selected' : '' ?>>🔄 Круглый год</option>
                                <option value="winter" <?= ($route['season'] ?? 'all') == 'winter' ? 'selected' : '' ?>>❄️ Зима</option>
                                <option value="spring" <?= ($route['season'] ?? 'all') == 'spring' ? 'selected' : '' ?>>🌸 Весна</option>
                                <option value="summer" <?= ($route['season'] ?? 'all') == 'summer' ? 'selected' : '' ?>>☀️ Лето</option>
                                <option value="autumn" <?= ($route['season'] ?? 'all') == 'autumn' ? 'selected' : '' ?>>🍂 Осень</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-3">🏷️ Теги и категории</h6>
                    <?php foreach ($tags_by_type as $type => $type_tags): ?>
                        <div class="mb-3">
                            <label class="form-label"><strong><?= $type_names[$type] ?></strong></label>
                            <div class="row">
                                <?php foreach ($type_tags as $tag): ?>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox"
                                                   name="tags[]" value="<?= $tag['id'] ?>"
                                                   id="tag_<?= $tag['id'] ?>"
                                                   <?= in_array($tag['id'], $selected_tags) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="tag_<?= $tag['id'] ?>">
                                                <?= $tag['icon'] ?> <?= htmlspecialchars($tag['name']) ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Теги помогают пользователям находить подходящие маршруты через фильтры.</small>
                        <a href="/admin/tags/list.php" target="_blank" class="alert-link">Управление тегами</a>
                    </div>
                    <div class="mb-3">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" <?= $route['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Маршрут активен
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
                        </button>
                        <a href="/admin/routes/list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-chart-bar me-2"></i>Статистика</h6>
            </div>
            <div class="card-body">
                <?php
                $stats = $pdo->prepare("
                    SELECT
                        COUNT(DISTINCT up.user_id) as total_users,
                        COUNT(CASE WHEN up.status = 'COMPLETED' THEN 1 END) as completions,
                        AVG(CASE WHEN up.status = 'COMPLETED' THEN TIMESTAMPDIFF(MINUTE, up.started_at, up.completed_at) END) as avg_duration
                    FROM user_progress up
                    WHERE up.route_id = ?
                ");
                $stats->execute([$route_id]);
                $route_stats = $stats->fetch();
                ?>
                <div class="mb-3">
                    <small class="text-muted">Прошли маршрут</small>
                    <h4><?= $route_stats['total_users'] ?></h4>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Завершили</small>
                    <h4><?= $route_stats['completions'] ?></h4>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Среднее время</small>
                    <h4><?= round($route_stats['avg_duration'] ?? 0) ?> мин</h4>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6><i class="fas fa-map-pin me-2"></i>Точки (<?= count($points) ?>)</h6>
                <a href="/admin/points/list.php?route_id=<?= $route_id ?>" class="btn btn-sm btn-primary">
                    Управление
                </a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush" id="pointsList">
                    <?php foreach ($points as $point): ?>
                        <div class="list-group-item point-item" data-id="<?= $point['id'] ?>" data-order="<?= $point['order'] ?>" style="cursor: move;">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <i class="fas fa-grip-vertical text-muted me-2"></i>
                                    <span class="badge bg-secondary me-2"><?= $point['order'] ?></span>
                                    <strong><?= htmlspecialchars($point['name']) ?></strong>
                                    <?php if ($point['hints_count'] > 0): ?>
                                        <span class="badge bg-info ms-2" title="Подсказок">
                                            💡 <?= $point['hints_count'] ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($points)): ?>
                        <div class="list-group-item text-center text-muted">
                            Точки не добавлены
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Визуальный редактор маршрута -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-map me-2"></i>Визуальный редактор маршрута</h5>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#routeMap">
                            <i class="fas fa-map-marked-alt me-2"></i>Карта маршрута
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#routePreview">
                            <i class="fas fa-eye me-2"></i>Предпросмотр
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#routeReorder">
                            <i class="fas fa-sort me-2"></i>Изменение порядка
                        </a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="tab-pane fade show active" id="routeMap">
                        <div id="map" style="height: 500px; width: 100%;"></div>
                        <div class="mt-3">
                            <button class="btn btn-sm btn-primary" onclick="centerMap()">
                                <i class="fas fa-crosshairs me-2"></i>Центрировать карту
                            </button>
                            <button class="btn btn-sm btn-info" onclick="calculateRoute()">
                                <i class="fas fa-route me-2"></i>Рассчитать расстояние
                            </button>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="routePreview">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Предпросмотр того, как маршрут будет выглядеть для пользователя
                        </div>
                        <div class="card">
                            <div class="card-body">
                                <h4><?= htmlspecialchars($route['name']) ?></h4>
                                <p><?= htmlspecialchars($route['description']) ?></p>
                                <div class="row">
                                    <div class="col-md-3">
                                        <strong>Цена:</strong> <?= number_format($route['price']) ?>₽
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Тип:</strong> <?= $route['route_type'] == 'WALKING' ? '🚶 Пеший' : '🚴 Велосипедный' ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Сложность:</strong>
                                        <?php
                                        $difficulty_names = [1 => '⭐ Легкий', 2 => '⭐⭐ Средний', 3 => '⭐⭐⭐ Сложный'];
                                        echo $difficulty_names[$route['difficulty'] ?? 2] ?? '⭐⭐ Средний';
                                        ?>
                                    </div>
                                    <div class="col-md-3">
                                        <strong>Время:</strong> <?= $route['estimated_duration'] ?> мин
                                    </div>
                                </div>
                                <hr>
                                <h5>Точки маршрута:</h5>
                                <ol>
                                    <?php foreach ($points as $point): ?>
                                        <li><?= htmlspecialchars($point['name']) ?></li>
                                    <?php endforeach; ?>
                                </ol>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="routeReorder">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Перетащите точки для изменения порядка. Изменения сохраняются автоматически.
                        </div>
                        <div id="sortablePoints" class="list-group">
                            <?php foreach ($points as $point): ?>
                                <div class="list-group-item point-sortable" data-id="<?= $point['id'] ?>" style="cursor: move;">
                                    <i class="fas fa-grip-vertical text-muted me-2"></i>
                                    <span class="badge bg-secondary me-2 order-badge"><?= $point['order'] ?></span>
                                    <strong><?= htmlspecialchars($point['name']) ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Yandex Maps API -->
<script src="https://api-maps.yandex.ru/2.1/?apikey=d6e8ba68-8f5e-47b5-b59e-f71e03067a91&lang=ru_RU" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
function translateField(fromId, toId) {
    const fromField = document.getElementById(fromId);
    const toField = document.getElementById(toId);
    const text = fromField.value.trim();
    if (!text) {
        alert('Сначала заполните поле на русском языке');
        return;
    }
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch('/admin/api/translate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({text: text, from: 'ru', to: 'en'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toField.value = data.translated;
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-language"></i>';
                btn.disabled = false;
            }, 2000);
        } else {
            alert('Ошибка перевода: ' + (data.error || 'Неизвестная ошибка'));
            btn.innerHTML = '<i class="fas fa-language"></i>';
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('Ошибка: ' + err.message);
        btn.innerHTML = '<i class="fas fa-language"></i>';
        btn.disabled = false;
    });
}
let map, routePolyline;
const points = <?= json_encode($points) ?>;
ymaps.ready(function() {
    map = new ymaps.Map('map', {
        center: points.length > 0 ? [points[0].latitude, points[0].longitude] : [55.7558, 37.6173],
        zoom: 13
    });
    points.forEach((point, index) => {
        const placemark = new ymaps.Placemark(
            [point.latitude, point.longitude],
            {
                balloonContent: `<strong>${point.name}</strong><br>Порядок: ${point.order}`,
                iconCaption: `${point.order}. ${point.name}`
            },
            {
                preset: 'islands#blueCircleDotIcon',
                draggable: false
            }
        );
        map.geoObjects.add(placemark);
    });
    if (points.length > 1) {
        const coordinates = points.map(p => [p.latitude, p.longitude]);
        routePolyline = new ymaps.Polyline(coordinates, {}, {
            strokeColor: '#667eea',
            strokeWidth: 4,
            strokeOpacity: 0.7
        });
        map.geoObjects.add(routePolyline);
        map.setBounds(map.geoObjects.getBounds());
    }
});
function centerMap() {
    if (map && points.length > 0) {
        map.setBounds(map.geoObjects.getBounds());
    }
}
function calculateRoute() {
    if (points.length < 2) {
        alert('Нужно минимум 2 точки для расчета маршрута');
        return;
    }
    alert('Расчет расстояния (функция в разработке)');
}
if (document.getElementById('sortablePoints')) {
    const sortable = Sortable.create(document.getElementById('sortablePoints'), {
        animation: 150,
        handle: '.fa-grip-vertical',
        onEnd: function(evt) {
            const items = Array.from(document.querySelectorAll('.point-sortable'));
            const newOrder = items.map((item, index) => ({
                id: parseInt(item.dataset.id),
                order: index + 1
            }));
            items.forEach((item, index) => {
                item.querySelector('.order-badge').textContent = index + 1;
            });
            fetch('/admin/api/reorder_points.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({points: newOrder})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка при сохранении порядка: ' + (data.error || 'Неизвестная ошибка'));
                }
            });
        }
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>