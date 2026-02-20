<?php
$page_title = 'Управление точками';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
<<<<<<< HEAD
require_once __DIR__ . '/../includes/auth.php';
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
$pdo = getDB()->getConnection();
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$route = null;
if ($route_id) {
    $stmt = $pdo->prepare("SELECT * FROM routes WHERE id = ?");
    $stmt->execute([$route_id]);
    $route = $stmt->fetch();
<<<<<<< HEAD
    if ($route && isModerator() && (empty($route['creator_id']) || (int)$route['creator_id'] !== (int)$_SESSION['admin_id'])) {
        http_response_code(403);
        $page_title = 'Доступ запрещён';
        require_once __DIR__ . '/../includes/header.php';
        echo '<div class="alert alert-danger">Доступ запрещён. Вы можете управлять только точками своих маршрутов.</div>';
        require_once __DIR__ . '/../includes/footer.php';
        exit;
    }
}
$where = $route_id ? "WHERE p.route_id = $route_id" : "";
if (isModerator() && !$where) {
    $where = "WHERE r.creator_id = " . (int)$_SESSION['admin_id'];
}
=======
}
$where = $route_id ? "WHERE p.route_id = $route_id" : "";
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
$stmt = $pdo->prepare("
    SELECT p.*, r.name as route_name, r.id as route_id,
           COUNT(DISTINCT h.id) as hints_count,
           COUNT(DISTINCT up_photos.id) as photos_count
    FROM points p
    JOIN routes r ON p.route_id = r.id
    LEFT JOIN hints h ON p.id = h.point_id
    LEFT JOIN user_photos up_photos ON p.id = up_photos.point_id
    $where
    GROUP BY p.id
    ORDER BY r.name, p.order
");
$stmt->execute();
$points = $stmt->fetchAll();
<<<<<<< HEAD
if (isModerator()) {
    $routes_stmt = $pdo->prepare("SELECT id, name FROM routes WHERE creator_id = ? ORDER BY name");
    $routes_stmt->execute([$_SESSION['admin_id']]);
    $routes = $routes_stmt->fetchAll();
} else {
    $routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
}
=======
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-map-pin me-2"></i>Точки</h2>
        <?php if ($route): ?>
            <p class="text-muted mb-0">Маршрут: <strong><?= htmlspecialchars($route['name']) ?></strong></p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <a href="/admin/points/import.php<?= $route_id ? "?route_id=$route_id" : '' ?>" class="btn btn-info">
            <i class="fas fa-upload me-2"></i>Импорт
        </a>
        <a href="/admin/points/export.php?route_id=<?= $route_id ?>" class="btn btn-secondary">
            <i class="fas fa-download me-2"></i>Экспорт
        </a>
        <a href="/admin/points/create.php<?= $route_id ? "?route_id=$route_id" : '' ?>" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Добавить точку
        </a>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Фильтры -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<?php if (!$route_id): ?>
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Маршрут</label>
                    <select name="route_id" class="form-select" onchange="this.form.submit()">
                        <option value="">Все маршруты</option>
                        <?php foreach ($routes as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= $route_id == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>
<<<<<<< HEAD
=======
<!-- Таблица точек -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Название</th>
                        <?php if (!$route_id): ?>
                            <th>Маршрут</th>
                        <?php endif; ?>
                        <th>Координаты</th>
                        <th>Подсказки</th>
                        <th>Фото</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody id="pointsList">
                    <?php foreach ($points as $point): ?>
                        <tr data-id="<?= $point['id'] ?>">
                            <td>
                                <span class="badge bg-secondary"><?= $point['order'] ?></span>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($point['name']) ?></strong>
                                <?php if (!empty($point['is_bonus'])): ?>
                                    <span class="badge bg-warning ms-2">Бонус</span>
                                <?php endif; ?>
                            </td>
                            <?php if (!$route_id): ?>
                                <td>
                                    <a href="?route_id=<?= $point['route_id'] ?>">
                                        <?= htmlspecialchars($point['route_name']) ?>
                                    </a>
                                </td>
                            <?php endif; ?>
                            <td>
                                <small class="text-muted">
                                    <?php
                                    $lat = $point['latitude'] ?? 0;
                                    $lng = $point['longitude'] ?? 0;
                                    echo round($lat, 6) . ', ' . round($lng, 6);
                                    ?>
                                </small>
                            </td>
                            <td>
                                <?php if ($point['hints_count'] > 0): ?>
                                    <span class="badge bg-info">💡 <?= $point['hints_count'] ?></span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $point['photos_count'] ?></span>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="/admin/points/edit.php?id=<?= $point['id'] ?>"
                                       class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/admin/hints/list.php?point_id=<?= $point['id'] ?>"
                                       class="btn btn-sm btn-info" title="Подсказки">
                                        <i class="fas fa-lightbulb"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="deletePoint(<?= $point['id'] ?>)"
                                            title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($points)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Точки не найдены</p>
                <?php if ($route): ?>
                    <a href="/admin/points/create.php?route_id=<?= $route_id ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить первую точку
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
function deletePoint(id) {
    if (confirm('Удалить эту точку? Все связанные подсказки и фото также будут удалены.')) {
        fetch('/admin/api/delete_point.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({point_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
            }
        });
    }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>