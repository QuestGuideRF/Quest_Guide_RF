<?php
$page_title = 'Медиабиблиотека';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';
$where = [];
$params = [];
if ($search) {
    $where[] = "(up.file_path LIKE ? OR pt.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($type === 'photos') {
    $where[] = "up.id IS NOT NULL";
} elseif ($type === 'reference') {
    $where[] = "ri.id IS NOT NULL";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT
        up.id,
        up.file_path,
        up.created_at,
        u.first_name,
        pt.name as point_name,
        r.name as route_name,
        'user_photo' as media_type
    FROM user_photos up
    JOIN users u ON up.user_id = u.id
    JOIN points pt ON up.point_id = pt.id
    JOIN routes r ON pt.route_id = r.id
    $whereClause
    ORDER BY up.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$user_photos = $stmt->fetchAll();
$where_ref = [];
$params_ref = [];
if ($search) {
    $where_ref[] = "(ri.file_path LIKE ? OR pt.name LIKE ?)";
    $params_ref[] = "%$search%";
    $params_ref[] = "%$search%";
}
$whereClause_ref = $where_ref ? 'WHERE ' . implode(' AND ', $where_ref) : '';
$stmt_ref = $pdo->prepare("
    SELECT
        ri.id,
        ri.file_path,
        ri.created_at,
        pt.name as point_name,
        r.name as route_name,
        'reference' as media_type
    FROM reference_images ri
    JOIN points pt ON ri.point_id = pt.id
    JOIN routes r ON pt.route_id = r.id
    $whereClause_ref
    ORDER BY ri.created_at DESC
    LIMIT 100
");
$stmt_ref->execute($params_ref);
$reference_images = $stmt_ref->fetchAll();
$all_media = array_merge($user_photos, $reference_images);
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-images me-2"></i>Медиабиблиотека</h2>
    <div>
        <span class="badge bg-info"><?= count($all_media) ?> файлов</span>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Тип</label>
                <select name="type" class="form-select">
                    <option value="all" <?= $type === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="photos" <?= $type === 'photos' ? 'selected' : '' ?>>Фото пользователей</option>
                    <option value="reference" <?= $type === 'reference' ? 'selected' : '' ?>>Референсные</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Поиск</label>
                <input type="text" name="search" class="form-control" placeholder="Путь к файлу или название точки" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Найти
                </button>
            </div>
        </form>
    </div>
</div>
<div class="row g-4">
    <?php foreach ($all_media as $media): ?>
        <div class="col-md-3">
            <div class="card">
                <?php
                $photo_path = $media['file_path'];
                if (!empty($photo_path) && $photo_path[0] !== '/') {
                    $photo_path = '/' . $photo_path;
                }
                ?>
                <a href="<?= htmlspecialchars($photo_path) ?>" target="_blank">
                    <img src="<?= htmlspecialchars($photo_path) ?>"
                         class="card-img-top"
                         style="height: 200px; object-fit: cover;"
                         onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'200\' height=\'200\'%3E%3Crect fill=\'%23ddd\' width=\'200\' height=\'200\'/%3E%3Ctext fill=\'%23999\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EФото не найдено%3C/text%3E%3C/svg%3E'; this.onerror=null;">
                </a>
                <div class="card-body">
                    <h6 class="card-title">
                        <span class="badge bg-<?= $media['media_type'] === 'user_photo' ? 'primary' : 'info' ?>">
                            <?= $media['media_type'] === 'user_photo' ? 'Фото' : 'Референс' ?>
                        </span>
                    </h6>
                    <p class="card-text small mb-1">
                        <strong><?= htmlspecialchars($media['point_name']) ?></strong>
                        <br>
                        <small class="text-muted"><?= htmlspecialchars($media['route_name']) ?></small>
                    </p>
                    <?php if ($media['media_type'] === 'user_photo' && isset($media['first_name'])): ?>
                        <p class="card-text small text-muted mb-1">
                            <i class="fas fa-user me-1"></i><?= htmlspecialchars($media['first_name']) ?>
                        </p>
                    <?php endif; ?>
                    <p class="card-text small text-muted">
                        <i class="fas fa-clock me-1"></i><?= date('d.m.Y H:i', strtotime($media['created_at'])) ?>
                    </p>
                    <div class="d-grid gap-1">
                        <a href="<?= htmlspecialchars($photo_path) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-external-link-alt me-1"></i>Открыть
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php if (empty($all_media)): ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted">Медиафайлы не найдены</p>
        </div>
    </div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>