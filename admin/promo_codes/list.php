<?php
$page_title = 'Управление промокодами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
if ($status === 'active') {
    $where[] = "pc.is_active = 1";
} elseif ($status === 'inactive') {
    $where[] = "pc.is_active = 0";
} elseif ($status === 'expired') {
    $where[] = "pc.valid_until < NOW()";
}
if ($search) {
    $where[] = "(pc.code LIKE ? OR pc.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT pc.*,
           r.name as route_name,
           COUNT(DISTINCT pcu.id) as uses_count
    FROM promo_codes pc
    LEFT JOIN routes r ON pc.route_id = r.id
    LEFT JOIN promo_code_uses pcu ON pc.id = pcu.promo_code_id
    $whereClause
    GROUP BY pc.id
    ORDER BY pc.created_at DESC
");
$stmt->execute($params);
$promo_codes = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-ticket-alt me-2"></i>Промокоды</h2>
    <a href="/admin/promo_codes/create.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Создать промокод
    </a>
</div>
<!-- Фильтры и поиск -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="">Все</option>
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Активные</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Неактивные</option>
                    <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>Истекшие</option>
                </select>
            </div>
            <div class="col-md-7">
                <label class="form-label">Поиск</label>
                <input type="text" name="search" class="form-control" placeholder="Код или описание" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Найти
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Массовые действия -->
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
<!-- Таблица промокодов -->
<div class="card">
    <div class="card-body">
        <?php if (empty($promo_codes)): ?>
            <div class="text-center py-5">
                <i class="fas fa-ticket-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">Промокоды не найдены</p>
                <a href="/admin/promo_codes/create.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Создать первый промокод
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th>Код</th>
                            <th>Тип</th>
                            <th>Значение</th>
                            <th>Маршрут</th>
                            <th>Использований</th>
                            <th>Действителен до</th>
                            <th>Статус</th>
                            <th width="150">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($promo_codes as $promo): ?>
                            <tr data-id="<?= $promo['id'] ?>">
                                <td>
                                    <input type="checkbox" class="item-checkbox" value="<?= $promo['id'] ?>">
                                </td>
                                <td>
                                    <strong><?= htmlspecialchars($promo['code']) ?></strong>
                                    <?php if ($promo['description']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars(mb_substr($promo['description'], 0, 50)) ?><?= mb_strlen($promo['description']) > 50 ? '...' : '' ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $type_labels = [
                                        'percentage' => 'Процент',
                                        'fixed' => 'Фиксированная',
                                        'free_route' => 'Бесплатный маршрут'
                                    ];
                                    echo $type_labels[$promo['discount_type']] ?? $promo['discount_type'];
                                    ?>
                                </td>
                                <td>
                                    <?php if ($promo['discount_type'] === 'percentage'): ?>
                                        <?= number_format($promo['discount_value'], 0) ?>%
                                    <?php elseif ($promo['discount_type'] === 'fixed'): ?>
                                        <?= number_format($promo['discount_value'], 2) ?>₽
                                    <?php elseif ($promo['discount_type'] === 'free_route'): ?>
                                        <span class="badge bg-success">Бесплатно</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($promo['route_name']): ?>
                                        <?= htmlspecialchars($promo['route_name']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Все маршруты</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?= $promo['uses_count'] ?>
                                    <?php if ($promo['max_uses']): ?>
                                        / <?= $promo['max_uses'] ?>
                                    <?php else: ?>
                                        / ∞
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($promo['valid_until']): ?>
                                        <?= date('d.m.Y H:i', strtotime($promo['valid_until'])) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Без ограничений</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $is_expired = $promo['valid_until'] && strtotime($promo['valid_until']) < time();
                                    $is_max_uses = $promo['max_uses'] && $promo['uses_count'] >= $promo['max_uses'];
                                    ?>
                                    <?php if (!$promo['is_active']): ?>
                                        <span class="badge bg-secondary">Неактивен</span>
                                    <?php elseif ($is_expired): ?>
                                        <span class="badge bg-danger">Истек</span>
                                    <?php elseif ($is_max_uses): ?>
                                        <span class="badge bg-warning">Исчерпан</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Активен</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/admin/promo_codes/edit.php?id=<?= $promo['id'] ?>" class="btn btn-sm btn-primary" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-promo" data-id="<?= $promo['id'] ?>" title="Удалить">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('selectAll')?.addEventListener('change', function() {
        document.querySelectorAll('.item-checkbox').forEach(cb => cb.checked = this.checked);
        updateSelectedCount();
    });
    document.querySelectorAll('.item-checkbox').forEach(cb => {
        cb.addEventListener('change', updateSelectedCount);
    });
    function updateSelectedCount() {
        const count = document.querySelectorAll('.item-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = `Выбрано: ${count}`;
    }
    document.getElementById('applyBulkAction')?.addEventListener('click', function() {
        const action = document.getElementById('bulkAction').value;
        const selected = Array.from(document.querySelectorAll('.item-checkbox:checked')).map(cb => cb.value);
        if (!action || selected.length === 0) {
            alert('Выберите действие и элементы');
            return;
        }
        if (confirm(`Применить действие "${action}" к ${selected.length} элементу(ам)?`)) {
            fetch('/admin/api/bulk_promo_codes.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action, ids: selected})
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            })
            .catch(e => {
                alert('Ошибка: ' + e.message);
            });
        }
    });
    document.querySelectorAll('.delete-promo').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            if (confirm('Удалить промокод?')) {
                fetch(`/admin/api/delete_promo_code.php?id=${id}`, {method: 'POST'})
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                    }
                })
                .catch(e => alert('Ошибка: ' + e.message));
            }
        });
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>