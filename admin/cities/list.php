<?php
$page_title = 'Управление городами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$is_moderator = function_exists('isModerator') && isModerator();
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
if ($status === 'active') {
    $where[] = "c.is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "c.is_active = 0";
}
if ($search) {
    $where[] = "(c.name LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT c.*,
           COUNT(DISTINCT r.id) as routes_count,
           COUNT(DISTINCT u.id) as users_count,
           COUNT(DISTINCT up.id) as completions_count
    FROM cities c
    LEFT JOIN routes r ON c.id = r.city_id
    LEFT JOIN user_progress up ON r.id = up.route_id
    LEFT JOIN users u ON up.user_id = u.id
    $whereClause
    GROUP BY c.id
    ORDER BY c.name
");
$stmt->execute($params);
$cities = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-city me-2"></i>Города</h2>
    <a href="/admin/cities/create.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Добавить город
    </a>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Неактивные</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Поиск</label>
                <input type="text" name="search" class="form-control" placeholder="Название или описание" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Найти
                </button>
            </div>
        </form>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form id="bulkActionsForm">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <select id="bulkAction" class="form-select">
                        <option value="">Массовые действия...</option>
                        <option value="activate">Активировать</option>
                        <option value="deactivate">Деактивировать</option>
                        <option value="delete">Удалить</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="applyBulkAction" class="btn btn-secondary w-100">
                        Применить
                    </button>
                </div>
                <div class="col-md-6 text-end">
                    <span id="selectedCount" class="text-muted">Выбрано: 0</span>
                </div>
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
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th>Маршрутов</th>
                        <th>Пользователей</th>
                        <th>Прохождений</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cities as $city):
                        $can_edit_row = !$is_moderator || (isset($city['creator_id']) && (int)$city['creator_id'] === (int)$_SESSION['admin_id']);
                    ?>
                        <tr>
                            <td><?php if ($can_edit_row): ?><input type="checkbox" class="city-checkbox" value="<?= $city['id'] ?>"><?php endif; ?></td>
                            <td><?= $city['id'] ?></td>
                            <td><strong><?= htmlspecialchars($city['name']) ?></strong></td>
                            <td>
                                <small class="text-muted">
                                    <?= htmlspecialchars(mb_substr($city['description'] ?? '', 0, 50)) ?>
                                    <?= mb_strlen($city['description'] ?? '') > 50 ? '...' : '' ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= $city['routes_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?= $city['users_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $city['completions_count'] ?></span>
                            </td>
                            <td>
                                <?php if ($city['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Неактивен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php $can_edit = $can_edit_row; ?>
                                <div class="table-actions">
                                    <?php if ($can_edit): ?>
                                    <a href="/admin/cities/edit.php?id=<?= $city['id'] ?>"
                                       class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php endif; ?>
                                    <a href="/admin/routes/list.php?city_id=<?= $city['id'] ?>"
                                       class="btn btn-sm btn-info" title="Маршруты">
                                        <i class="fas fa-route"></i>
                                    </a>
                                    <?php if ($can_edit): ?>
                                    <button type="button"
                                            class="btn btn-sm btn-<?= $city['is_active'] ? 'warning' : 'success' ?>"
                                            onclick="toggleStatus(<?= $city['id'] ?>)"
                                            title="<?= $city['is_active'] ? 'Деактивировать' : 'Активировать' ?>">
                                        <i class="fas fa-<?= $city['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteCity(<?= $city['id'] ?>)"
                                            title="Удалить">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($cities)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Города не найдены</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.city-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});
document.querySelectorAll('.city-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});
function updateSelectedCount() {
    const count = document.querySelectorAll('.city-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = `Выбрано: ${count}`;
}
document.getElementById('applyBulkAction').addEventListener('click', function() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.city-checkbox:checked')).map(cb => cb.value);
    if (!action) {
        alert('Выберите действие');
        return;
    }
    if (selected.length === 0) {
        alert('Выберите хотя бы один город');
        return;
    }
    if (action === 'delete' && !confirm(`Удалить ${selected.length} городов?`)) {
        return;
    }
    fetch('/admin/api/bulk_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: action === 'activate' ? 'activate_cities' : (action === 'deactivate' ? 'deactivate_cities' : 'delete_cities'), ids: selected})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
});
function toggleStatus(id) {
    fetch('/admin/api/bulk_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'toggle_city_status', ids: [id]})
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
function deleteCity(id) {
    if (confirm('Удалить этот город? Все связанные маршруты также будут удалены.')) {
        fetch('/admin/api/bulk_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete_cities', ids: [id]})
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