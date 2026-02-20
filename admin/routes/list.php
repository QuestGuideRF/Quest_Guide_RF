<?php
$page_title = 'Управление маршрутами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
<<<<<<< HEAD
require_once __DIR__ . '/../includes/auth.php';
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
$pdo = getDB()->getConnection();
$city_id = isset($_GET['city_id']) ? (int)$_GET['city_id'] : null;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
<<<<<<< HEAD
if (isModerator()) {
    $where[] = "r.creator_id = ?";
    $params[] = $_SESSION['admin_id'];
}
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
if ($city_id) {
    $where[] = "r.city_id = ?";
    $params[] = $city_id;
}
if ($status === 'active') {
    $where[] = "r.is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "r.is_active = 0";
}
if ($search) {
    $where[] = "(r.name LIKE ? OR r.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT r.*,
           c.name as city_name,
           COUNT(DISTINCT p.id) as points_count,
           COUNT(DISTINCT up.id) as completions_count
    FROM routes r
    JOIN cities c ON r.city_id = c.id
    LEFT JOIN points p ON r.id = p.route_id
    LEFT JOIN user_progress up ON r.id = up.route_id AND up.status = 'COMPLETED'
    $whereClause
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->execute($params);
$routes = $stmt->fetchAll();
$cities = $pdo->query("SELECT * FROM cities ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-route me-2"></i>Маршруты</h2>
    <a href="/admin/routes/create.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Создать маршрут
    </a>
</div>
<<<<<<< HEAD
=======
<!-- Фильтры и поиск -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Город</label>
                <select name="city_id" class="form-select">
                    <option value="">Все города</option>
                    <?php foreach ($cities as $city): ?>
                        <option value="<?= $city['id'] ?>" <?= $city_id == $city['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($city['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Неактивные</option>
                </select>
            </div>
            <div class="col-md-4">
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
<<<<<<< HEAD
=======
<!-- Массовые действия -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card mb-4">
    <div class="card-body">
        <form id="bulkActionsForm">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select id="bulkAction" class="form-select">
                        <option value="">Массовые действия...</option>
                        <option value="activate">Активировать</option>
                        <option value="deactivate">Деактивировать</option>
                        <option value="change_price">Изменить цену</option>
                        <option value="assign_tags">Назначить теги</option>
                        <option value="delete">Удалить</option>
                        <option value="export">Экспорт в CSV</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="applyBulkAction" class="btn btn-secondary w-100">
                        Применить
                    </button>
                </div>
                <div class="col-md-7 text-end">
                    <span id="selectedCount" class="text-muted">Выбрано: 0</span>
                </div>
            </div>
        </form>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Модальное окно для назначения тегов -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="modal fade" id="assignTagsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Назначить теги маршрутам</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Выберите теги для назначения выбранным маршрутам:</p>
                <div id="tagsList" class="row g-2">
<<<<<<< HEAD
=======
                    <!-- Теги будут загружены через AJAX -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary" onclick="confirmAssignTags()">Назначить</button>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Таблица маршрутов -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Название</th>
                        <th>Город</th>
                        <th>Цена</th>
                        <th>Точек</th>
                        <th>Прохождений</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($routes as $route): ?>
                        <tr>
                            <td><input type="checkbox" class="route-checkbox" value="<?= $route['id'] ?>"></td>
                            <td><?= $route['id'] ?></td>
                            <td>
                                <strong><?= htmlspecialchars($route['name']) ?></strong>
                                <?php if ($route['route_type'] == 'WALKING'): ?>
                                    <i class="fas fa-walking text-muted ms-2" title="Пеший"></i>
                                <?php else: ?>
                                    <i class="fas fa-biking text-muted ms-2" title="Велосипедный"></i>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($route['city_name']) ?></td>
<<<<<<< HEAD
                            <td><?= number_format($route['price']) ?> грошей</td>
=======
                            <td><?= number_format($route['price']) ?>₽</td>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                            <td>
                                <span class="badge bg-info"><?= $route['points_count'] ?></span>
                            </td>
                            <td>
                                <span class="badge bg-success"><?= $route['completions_count'] ?></span>
                            </td>
                            <td>
                                <?php if ($route['is_active']): ?>
                                    <span class="badge bg-success">Активен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Неактивен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="/admin/routes/edit.php?id=<?= $route['id'] ?>"
                                       class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="/admin/points/list.php?route_id=<?= $route['id'] ?>"
                                       class="btn btn-sm btn-info" title="Точки">
                                        <i class="fas fa-map-pin"></i>
                                    </a>
                                    <button type="button"
<<<<<<< HEAD
=======
                                            class="btn btn-sm btn-secondary"
                                            onclick="cloneRoute(<?= $route['id'] ?>, '<?= htmlspecialchars(addslashes($route['name'])) ?>')"
                                            title="Клонировать">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button type="button"
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                                            class="btn btn-sm btn-<?= $route['is_active'] ? 'warning' : 'success' ?>"
                                            onclick="toggleStatus(<?= $route['id'] ?>)"
                                            title="<?= $route['is_active'] ? 'Деактивировать' : 'Активировать' ?>">
                                        <i class="fas fa-<?= $route['is_active'] ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteRoute(<?= $route['id'] ?>)"
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
        <?php if (empty($routes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Маршруты не найдены</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.route-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});
document.querySelectorAll('.route-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});
function updateSelectedCount() {
    const count = document.querySelectorAll('.route-checkbox:checked').length;
    document.getElementById('selectedCount').textContent = `Выбрано: ${count}`;
}
document.getElementById('applyBulkAction').addEventListener('click', function() {
    const action = document.getElementById('bulkAction').value;
    const selected = Array.from(document.querySelectorAll('.route-checkbox:checked')).map(cb => cb.value);
    if (!action) {
        alert('Выберите действие');
        return;
    }
    if (selected.length === 0) {
        alert('Выберите хотя бы один маршрут');
        return;
    }
    if (action === 'delete' && !confirm(`Удалить ${selected.length} маршрутов?`)) {
        return;
    }
    if (action === 'change_price') {
        const newPrice = prompt('Введите новую цену:');
        if (!newPrice || isNaN(newPrice)) return;
        fetch('/admin/api/bulk_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'change_price', ids: selected, price: newPrice})
        }).then(() => location.reload());
        return;
    }
    if (action === 'assign_tags') {
        showAssignTagsModal(selected);
        return;
    }
    fetch('/admin/api/bulk_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action, ids: selected})
    }).then(() => location.reload());
});
let selectedRouteIds = [];
function showAssignTagsModal(routeIds) {
    selectedRouteIds = routeIds;
    fetch('/admin/api/get_tags.php')
        .then(r => r.json())
        .then(data => {
            const tagsList = document.getElementById('tagsList');
            tagsList.innerHTML = '';
            const tagsByType = {};
            data.tags.forEach(tag => {
                if (!tagsByType[tag.type]) tagsByType[tag.type] = [];
                tagsByType[tag.type].push(tag);
            });
            Object.keys(tagsByType).forEach(type => {
                const typeDiv = document.createElement('div');
                typeDiv.className = 'col-12';
                typeDiv.innerHTML = `<strong>${type}</strong>`;
                tagsList.appendChild(typeDiv);
                tagsByType[type].forEach(tag => {
                    const tagDiv = document.createElement('div');
                    tagDiv.className = 'col-md-6';
                    tagDiv.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input tag-checkbox" type="checkbox" value="${tag.id}" id="tag_${tag.id}">
                            <label class="form-check-label" for="tag_${tag.id}">
                                ${tag.icon} ${tag.name}
                            </label>
                        </div>
                    `;
                    tagsList.appendChild(tagDiv);
                });
            });
            const modal = new bootstrap.Modal(document.getElementById('assignTagsModal'));
            modal.show();
        });
}
function confirmAssignTags() {
    const selectedTags = Array.from(document.querySelectorAll('.tag-checkbox:checked')).map(cb => cb.value);
    if (selectedTags.length === 0) {
        alert('Выберите хотя бы один тег');
        return;
    }
    fetch('/admin/api/bulk_assign_tags.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            route_ids: selectedRouteIds,
            tag_ids: selectedTags,
            action: 'assign'
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert(`Теги назначены! Обработано: ${data.affected} связей`);
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function toggleStatus(id) {
    fetch('/admin/api/bulk_actions.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({action: 'toggle_status', ids: [id]})
    }).then(() => location.reload());
}
function deleteRoute(id) {
    if (confirm('Удалить этот маршрут?')) {
        fetch('/admin/api/bulk_actions.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'delete', ids: [id]})
        }).then(() => location.reload());
    }
}
<<<<<<< HEAD
=======
function cloneRoute(id, name) {
    const newName = prompt('Введите название для клона:', name + ' (копия)');
    if (!newName) return;
    fetch('/admin/api/clone_route.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({route_id: id, new_name: newName})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Маршрут успешно склонирован!');
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>