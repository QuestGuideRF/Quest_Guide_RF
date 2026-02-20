<?php
$page_title = 'Глобальный поиск';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$query = isset($_GET['q']) ? trim($_GET['q']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$results = [];
if ($query) {
    $search = "%$query%";
    if ($type === 'all' || $type === 'cities') {
        $stmt = $pdo->prepare("SELECT id, name, description, 'city' as type FROM cities WHERE name LIKE ? OR description LIKE ? LIMIT 10");
        $stmt->execute([$search, $search]);
        $cities = $stmt->fetchAll();
        foreach ($cities as $city) {
            $results[] = $city;
        }
    }
    if ($type === 'all' || $type === 'routes') {
        $stmt = $pdo->prepare("SELECT id, name, description, 'route' as type FROM routes WHERE name LIKE ? OR description LIKE ? LIMIT 10");
        $stmt->execute([$search, $search]);
        $routes = $stmt->fetchAll();
        foreach ($routes as $route) {
            $results[] = $route;
        }
    }
    if ($type === 'all' || $type === 'points') {
        $stmt = $pdo->prepare("
            SELECT p.id, p.name,
                   COALESCE((SELECT t.task_text FROM tasks t WHERE t.point_id = p.id ORDER BY t.`order` ASC LIMIT 1), '') AS description,
                   'point' AS type
            FROM points p
            WHERE p.name LIKE ?
               OR EXISTS (SELECT 1 FROM tasks t WHERE t.point_id = p.id AND t.task_text LIKE ?)
            LIMIT 10
        ");
        $stmt->execute([$search, $search]);
        $points = $stmt->fetchAll();
        foreach ($points as $point) {
            $results[] = $point;
        }
    }
    if ($type === 'all' || $type === 'users') {
        $stmt = $pdo->prepare("SELECT id, first_name as name, username as description, 'user' as type FROM users WHERE first_name LIKE ? OR username LIKE ? LIMIT 10");
        $stmt->execute([$search, $search]);
        $users = $stmt->fetchAll();
        foreach ($users as $user) {
            $results[] = $user;
        }
    }
}
?>
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-search me-2"></i>Глобальный поиск</h5>
                <form method="GET" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <input type="text" name="q" class="form-control form-control-lg"
                                   placeholder="Введите запрос для поиска..." value="<?= htmlspecialchars($query) ?>">
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-select form-select-lg">
                                <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Все</option>
                                <option value="cities" <?= $type === 'cities' ? 'selected' : '' ?>>Города</option>
                                <option value="routes" <?= $type === 'routes' ? 'selected' : '' ?>>Маршруты</option>
                                <option value="points" <?= $type === 'points' ? 'selected' : '' ?>>Точки</option>
                                <option value="users" <?= $type === 'users' ? 'selected' : '' ?>>Пользователи</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                <i class="fas fa-search me-2"></i>Найти
                            </button>
                        </div>
                    </div>
                </form>
                <?php if ($query): ?>
                    <h6>Результаты поиска (<?= count($results) ?>)</h6>
                    <div class="list-group">
                        <?php foreach ($results as $result): ?>
                            <a href="<?php
                                switch ($result['type']) {
                                    case 'city':
                                        echo "/admin/cities/edit.php?id=" . $result['id'];
                                        break;
                                    case 'route':
                                        echo "/admin/routes/edit.php?id=" . $result['id'];
                                        break;
                                    case 'point':
                                        echo "/admin/points/edit.php?id=" . $result['id'];
                                        break;
                                    case 'user':
                                        echo "/admin/users/list.php?search=" . urlencode($result['name']);
                                        break;
                                    default:
                                        echo "#";
                                }
                            ?>" class="list-group-item list-group-item-action">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <?php
                                            $icons = [
                                                'city' => '🏙️',
                                                'route' => '🗺️',
                                                'point' => '📍',
                                                'user' => '👤'
                                            ];
                                            echo $icons[$result['type']] ?? '📄';
                                            ?>
                                            <?= htmlspecialchars($result['name']) ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?= htmlspecialchars(mb_substr($result['description'] ?? '', 0, 100)) ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-secondary"><?= $result['type'] ?></span>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                    <?php if (empty($results)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Ничего не найдено по запросу "<?= htmlspecialchars($query) ?>"
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Введите запрос для поиска по городам, маршрутам, точкам и пользователям
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>