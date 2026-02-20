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
if ($old_route && isModerator() && (empty($old_route['creator_id']) || (int)$old_route['creator_id'] !== (int)$_SESSION['admin_id'])) {
    http_response_code(403);
    $page_title = 'Доступ запрещён';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-danger">Доступ запрещён. Вы можете редактировать только свои маршруты.</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
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
    if (isModerator() && (empty($route['creator_id']) || (int)$route['creator_id'] !== (int)$_SESSION['admin_id'])) {
        http_response_code(403);
        $page_title = 'Доступ запрещён';
        require_once __DIR__ . '/../includes/header.php';
        echo '<div class="alert alert-danger">Доступ запрещён. Вы можете редактировать только свои маршруты.</div>';
        require_once __DIR__ . '/../includes/footer.php';
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
$yandex_maps_key = defined('YANDEX_MAPS_API_KEY') ? YANDEX_MAPS_API_KEY : '';
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
                            <label class="form-label">Цена (гроши) *</label>
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
                <div class="mb-3">
                    <small class="text-muted">Расстояние (пеший маршрут)</small>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h4 id="routeDistanceDisplay" class="mb-0"><?= isset($route['distance']) && $route['distance'] !== null && $route['distance'] !== '' ? number_format((float)$route['distance'], 1, '.', ' ') . ' км' : '—' ?></h4>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="btnCalculateDistance" data-route-id="<?= (int)$route_id ?>" title="Рассчитать по точкам маршрута (Яндекс.Карты)">
                            <i class="fas fa-route me-1"></i>Рассчитать расстояние
                        </button>
                    </div>
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
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-map-marked-alt me-2"></i>Карта маршрута</h5>
            </div>
            <div class="card-body">
                <?php if ($yandex_maps_key === ''): ?>
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-key me-2"></i>
                    <strong>Ключ Яндекс.Карт не задан.</strong> Добавьте в <code>.env</code>: <code>YANDEX_MAPS_API_KEY=ваш_ключ</code>. Ключ можно получить в <a href="https://developer.tech.yandex.ru/" target="_blank" rel="noopener">Кабинете разработчика Яндекса</a> (JavaScript API и Маршрутизация).
                </div>
                <?php else: ?>
                <div class="alert alert-info mb-3 small">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Если карта показывает «Неверный ключ»:</strong> в <a href="https://developer.tech.yandex.ru/" target="_blank" rel="noopener">настройках ключа</a> обязательно укажите <strong>Ограничение по HTTP Referer</strong> и добавьте домен этого сайта, например <code>https://questguiderf.ru</code>.
                </div>
                <?php endif; ?>
                <div id="map" style="height: 400px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>
<?php if ($yandex_maps_key !== ''): ?>
<script src="https://api-maps.yandex.ru/2.1/?apikey=<?= htmlspecialchars($yandex_maps_key) ?>&lang=ru_RU" type="text/javascript"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var routePoints = <?= json_encode(array_map(function ($p) {
        return [
            'lat' => (float)($p['latitude'] ?? 0),
            'lon' => (float)($p['longitude'] ?? 0),
            'name' => $p['name'] ?? '',
            'order' => (int)($p['order'] ?? 0)
        ];
    }, $points)) ?>;
    if (typeof ymaps === 'undefined') return;
    ymaps.ready(function() {
        var mapEl = document.getElementById('map');
        if (!mapEl) return;
        var center = [55.7539, 37.6208];
        var zoom = 12;
        if (routePoints.length > 0) {
            var first = routePoints[0];
            if (first.lat && first.lon) {
                center = [first.lat, first.lon];
            }
        }
        var map = new ymaps.Map('map', {
            center: center,
            zoom: zoom,
            controls: ['zoomControl', 'typeSelector', 'fullscreenControl']
        });
        var placemarks = [];
        var bounds = [];
        for (var i = 0; i < routePoints.length; i++) {
            var p = routePoints[i];
            if (!p.lat || !p.lon) continue;
            var pm = new ymaps.Placemark(
                [p.lat, p.lon],
                {
                    balloonContent: '<strong>#' + (p.order) + '</strong> ' + (p.name || 'Точка'),
                    iconCaption: p.order
                },
                { preset: 'islands#blueCircleDotIconWithCaption' }
            );
            map.geoObjects.add(pm);
            placemarks.push(pm);
            bounds.push([p.lat, p.lon]);
        }
        if (bounds.length >= 2) {
            map.setBounds(bounds, { checkZoomRange: true, zoomMargin: 50 });
        } else if (bounds.length === 1) {
            map.setCenter(bounds[0], 15);
        }
        if (bounds.length >= 2) {
            var line = new ymaps.Polyline(
                bounds.map(function(b) { return b; }),
                {},
                { strokeColor: '#0066ff', strokeWidth: 4, strokeOpacity: 0.8 }
            );
            map.geoObjects.add(line);
        }
        var btnCalc = document.getElementById('btnCalculateDistance');
        if (btnCalc) {
            btnCalc.addEventListener('click', function() {
                var routeId = btnCalc.getAttribute('data-route-id');
                if (!routeId || bounds.length < 2) {
                    alert('Нужно минимум 2 точки с координатами для расчёта расстояния.');
                    return;
                }
                btnCalc.disabled = true;
                btnCalc.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Расчёт...';
                ymaps.route(bounds, { routingMode: 'pedestrian' }).then(
                    function(route) {
                        var meters = route.getLength();
                        var distanceKm = Math.round(meters / 1000 * 100) / 100;
                        fetch('/admin/api/calculate_route_distance.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ route_id: parseInt(routeId, 10), distance_km: distanceKm })
                        })
                        .then(function(r) { return r.json(); })
                        .then(function(data) {
                            if (data.success) {
                                var el = document.getElementById('routeDistanceDisplay');
                                if (el) el.textContent = distanceKm.toFixed(1) + ' км';
                                btnCalc.innerHTML = '<i class="fas fa-check me-1"></i>Рассчитано';
                            } else {
                                alert('Ошибка: ' + (data.error || 'Не удалось сохранить'));
                                btnCalc.innerHTML = '<i class="fas fa-route me-1"></i>Рассчитать расстояние';
                            }
                            btnCalc.disabled = false;
                        })
                        .catch(function() {
                            alert('Ошибка запроса к серверу');
                            btnCalc.innerHTML = '<i class="fas fa-route me-1"></i>Рассчитать расстояние';
                            btnCalc.disabled = false;
                        });
                    },
                    function(err) {
                        alert('Не удалось построить маршрут: ' + (err && err.message ? err.message : 'проверьте координаты точек'));
                        btnCalc.innerHTML = '<i class="fas fa-route me-1"></i>Рассчитать расстояние';
                        btnCalc.disabled = false;
                    }
                );
            });
        }
    });
});
</script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>