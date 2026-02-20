<?php
$page_title = 'Модерация фото';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$where = [];
$params = [];
if ($status === 'pending') {
    $where[] = "NOT EXISTS (
        SELECT 1 FROM user_progress up
        WHERE up.user_id = up_photos.user_id
        AND up.route_id = pt.route_id
        AND up.current_point_id = up_photos.point_id
        AND up.updated_at >= up_photos.created_at
    )";
} elseif ($status === 'rejected') {
}
if ($route_id) {
    $where[] = "pt.route_id = ?";
    $params[] = $route_id;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT up_photos.*,
           u.first_name, u.username, u.telegram_id,
           r.name as route_name,
           pt.name as point_name,
           pt.`order` as point_order
    FROM user_photos up_photos
    JOIN users u ON up_photos.user_id = u.id
    JOIN points pt ON up_photos.point_id = pt.id
    JOIN routes r ON pt.route_id = r.id
    $whereClause
    ORDER BY up_photos.created_at DESC
    LIMIT 50
");
$stmt->execute($params);
$photos = $stmt->fetchAll();
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-images me-2"></i>Модерация фото</h2>
    <div class="d-flex gap-2 align-items-center">
        <span class="badge bg-warning" id="pendingCount"><?= count($photos) ?></span>
        <span class="ms-2">на модерации</span>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>На модерации</option>
                    <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Отклоненные</option>
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Маршрут</label>
                <select name="route_id" class="form-select">
                    <option value="">Все маршруты</option>
                    <?php foreach ($routes as $route): ?>
                        <option value="<?= $route['id'] ?>" <?= $route_id == $route['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($route['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Фильтр
                </button>
            </div>
        </form>
    </div>
</div>
<?php if (empty($photos)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
            <h4>Все фото проверены!</h4>
            <p class="text-muted">Нет фото, ожидающих модерации</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($photos as $photo): ?>
            <div class="col-md-6" id="photo-<?= $photo['id'] ?>">
                <div class="card">
                    <div class="row g-0">
                        <div class="col-md-5">
                            <?php
                            $photo_path = $photo['file_path'];
                            if (!empty($photo_path) && $photo_path[0] !== '/') {
                                $photo_path = '/' . $photo_path;
                            }
                            $full_path = $_SERVER['DOCUMENT_ROOT'] . $photo_path;
                            if (!file_exists($full_path)) {
                                $alt_path = str_replace('/photos/', '/uploads/users/', $photo_path);
                                $alt_full_path = $_SERVER['DOCUMENT_ROOT'] . $alt_path;
                                if (file_exists($alt_full_path)) {
                                    $photo_path = $alt_path;
                                }
                            }
                            ?>
                            <a href="<?= htmlspecialchars($photo_path) ?>" target="_blank">
                                <img src="<?= htmlspecialchars($photo_path) ?>"
                                     class="img-fluid rounded-start"
                                     style="height: 100%; object-fit: cover;"
                                     alt="Фото"
                                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EФото не найдено%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                            </a>
                        </div>
                        <div class="col-md-7">
                            <div class="card-body">
                                <h6 class="card-title">
                                    <?= htmlspecialchars($photo['route_name']) ?>
                                    <span class="badge bg-info ms-2">Точка <?= $photo['point_order'] ?></span>
                                </h6>
                                <p class="card-text mb-2">
                                    <strong><?= htmlspecialchars($photo['point_name']) ?></strong>
                                </p>
                                <hr>
                                <div class="d-flex align-items-center mb-3">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <div>
                                        <strong><?= htmlspecialchars($photo['first_name']) ?></strong>
                                        <?php if ($photo['username']): ?>
                                            <small class="text-muted">@<?= htmlspecialchars($photo['username']) ?></small>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('d.m.Y H:i', strtotime($photo['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="d-grid gap-2">
                                    <a href="https://t.me/<?= htmlspecialchars($photo['username'] ?: $photo['telegram_id']) ?>"
                                       target="_blank"
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fab fa-telegram me-2"></i>Написать
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>