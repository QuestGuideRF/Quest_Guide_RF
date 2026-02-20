<?php
$page_title = 'Управление отзывами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$status = isset($_GET['status']) ? $_GET['status'] : '';
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
if ($status === 'approved') {
    $where[] = "COALESCE(r.is_approved, 0) = 1";
} elseif ($status === 'pending') {
    $where[] = "COALESCE(r.is_approved, 0) = 0 AND COALESCE(r.is_hidden, 0) = 0";
} elseif ($status === 'hidden') {
    $where[] = "COALESCE(r.is_hidden, 0) = 1";
}
if ($route_id) {
    $where[] = "r.route_id = ?";
    $params[] = $route_id;
}
if ($rating) {
    $where[] = "r.rating = ?";
    $params[] = $rating;
}
if ($search) {
    $where[] = "(r.text LIKE ? OR u.first_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT r.*, u.first_name, u.username, u.telegram_id, rt.name as route_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN routes rt ON r.route_id = rt.id
    $whereClause
    ORDER BY r.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-star me-2"></i>Отзывы</h2>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Одобренные</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>На модерации</option>
                    <option value="hidden" <?= $status === 'hidden' ? 'selected' : '' ?>>Скрытые</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Маршрут</label>
                <select name="route_id" class="form-select">
                    <option value="">Все маршруты</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $route_id == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Рейтинг</label>
                <select name="rating" class="form-select">
                    <option value="">Все</option>
                    <option value="5" <?= $rating == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐</option>
                    <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐</option>
                    <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>⭐⭐⭐</option>
                    <option value="2" <?= $rating == 2 ? 'selected' : '' ?>>⭐⭐</option>
                    <option value="1" <?= $rating == 1 ? 'selected' : '' ?>>⭐</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Поиск</label>
                <input type="text" name="search" class="form-control" placeholder="Текст отзыва или пользователь" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Пользователь</th>
                        <th>Маршрут</th>
                        <th>Рейтинг</th>
                        <th>Комментарий</th>
                        <th>Статус</th>
                        <th>Дата</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reviews as $review): ?>
                        <tr>
                            <td><?= $review['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($review['first_name']) ?></strong>
                                <?php if ($review['username']): ?>
                                    <br><small class="text-muted">@<?= htmlspecialchars($review['username']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($review['route_name']) ?></td>
                            <td>
                                <?php
                                $ratingVal = (int)($review['rating'] ?? 0);
                                $ratingVal = max(1, min(5, $ratingVal));
                                for ($i = 1; $i <= 5; $i++):
                                    $filled = $i <= $ratingVal;
                                ?>
                                    <i class="fa-star <?= $filled ? 'fas text-warning' : 'far text-muted' ?>"></i>
                                <?php endfor; ?>
                                <small class="text-muted ms-1"><?= $ratingVal ?>/5</small>
                            </td>
                            <td>
                                <small><?= htmlspecialchars(mb_substr($review['text'] ?? '', 0, 100)) ?>
                                <?= mb_strlen($review['text'] ?? '') > 100 ? '...' : '' ?></small>
                            </td>
                            <td>
                                <?php
                                $is_approved = isset($review['is_approved']) ? (bool)$review['is_approved'] : false;
                                $is_hidden = isset($review['is_hidden']) ? (bool)$review['is_hidden'] : false;
                                ?>
                                <?php if ($is_approved): ?>
                                    <span class="badge bg-success">Одобрен</span>
                                <?php elseif ($is_hidden): ?>
                                    <span class="badge bg-secondary">Скрыт</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">На модерации</span>
                                <?php endif; ?>
                            </td>
                            <td><small><?= date('d.m.Y H:i', strtotime($review['created_at'])) ?></small></td>
                            <td>
                                <div class="table-actions">
                                    <button type="button" class="btn btn-sm btn-success" onclick="approveReview(<?= $review['id'] ?>)" title="Одобрить">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="hideReview(<?= $review['id'] ?>)" title="Скрыть">
                                        <i class="fas fa-eye-slash"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteReview(<?= $review['id'] ?>)" title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function approveReview(id) {
    fetch('/admin/api/moderate_review.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({review_id: id, action: 'approve'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function hideReview(id) {
    fetch('/admin/api/moderate_review.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({review_id: id, action: 'hide'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function deleteReview(id) {
    if (confirm('Удалить этот отзыв?')) {
        fetch('/admin/api/moderate_review.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({review_id: id, action: 'delete'})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
            }
        });
    }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>