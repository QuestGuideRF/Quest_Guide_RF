<?php
$page_title = 'Управление подсказками';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$point_id = isset($_GET['point_id']) ? (int)$_GET['point_id'] : null;
$point = null;
if ($point_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, r.name as route_name, r.id as route_id
        FROM points p
        JOIN routes r ON p.route_id = r.id
        WHERE p.id = ?
    ");
    $stmt->execute([$point_id]);
    $point = $stmt->fetch();
}
$where = $point_id ? "WHERE h.point_id = $point_id" : "";
$stmt = $pdo->query("
    SELECT h.*, p.name as point_name, p.id as point_id,
           r.name as route_name, r.id as route_id
    FROM hints h
    JOIN points p ON h.point_id = p.id
    JOIN routes r ON p.route_id = r.id
    $where
    ORDER BY r.name, p.order, h.level, h.order
");
$hints = $stmt->fetchAll();
$existing_levels = [];
$missing_levels = [];
if ($point_id) {
    foreach ($hints as $h) {
        $existing_levels[] = (int)$h['level'];
    }
    $all_levels = [1, 2, 3];
    $missing_levels = array_diff($all_levels, $existing_levels);
}
$level_names = [1 => '💡 Легкая', 2 => '🔦 Средняя', 3 => '🎯 Детальная'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-lightbulb me-2"></i>Подсказки</h2>
        <?php if ($point): ?>
            <p class="text-muted mb-0">
                Точка: <strong><?= htmlspecialchars($point['name'] ?? '') ?></strong>
                (<a href="/admin/routes/edit.php?id=<?= $point['route_id'] ?>">
                    <?= htmlspecialchars($point['route_name']) ?>
                </a>)
            </p>
        <?php endif; ?>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-info" onclick="copySelectedHints()" id="copyBtn" style="display: none;">
            <i class="fas fa-copy me-2"></i>Копировать выбранные
        </button>
        <a href="/admin/hints/create.php<?= $point_id ? "?point_id=$point_id" : '' ?>"
           class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Добавить подсказки
        </a>
    </div>
</div>
<?php if ($point_id && !empty($missing_levels)): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between mb-3">
    <div>
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Не хватает уровней:</strong>
        <?php foreach ($missing_levels as $lvl): ?>
            <span class="badge bg-warning text-dark ms-1"><?= $level_names[$lvl] ?></span>
        <?php endforeach; ?>
    </div>
    <a href="/admin/hints/create.php?point_id=<?= $point_id ?>" class="btn btn-warning btn-sm">
        <i class="fas fa-plus me-1"></i>Добавить недостающие
    </a>
</div>
<?php elseif ($point_id && empty($missing_levels) && !empty($hints)): ?>
<div class="alert alert-success mb-3">
    <i class="fas fa-check-circle me-2"></i>
    <strong>Все 3 уровня подсказок созданы!</strong>
</div>
<?php endif; ?>
<!-- Таблица подсказок -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAllHints"></th>
                        <th>Точка</th>
                        <th>Уровень</th>
                        <th>Текст</th>
                        <th>EN</th>
                        <th>Карта</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($hints as $hint): ?>
                        <tr>
                            <td><input type="checkbox" class="hint-checkbox" value="<?= $hint['id'] ?>"></td>
                            <td>
                                <a href="/admin/points/edit.php?id=<?= $hint['point_id'] ?>">
                                    <?= htmlspecialchars($hint['point_name']) ?>
                                </a>
                                <?php if (!$point_id): ?>
                                    <br><small class="text-muted"><?= htmlspecialchars($hint['route_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $hint['level'] == 1 ? 'success' : ($hint['level'] == 2 ? 'warning' : 'danger') ?>">
                                    <?= $hint['level'] == 1 ? 'Легкая' : ($hint['level'] == 2 ? 'Средняя' : 'Детальная') ?>
                                </span>
                            </td>
                            <td>
                                <div style="max-width: 350px;">
                                    <?= htmlspecialchars(substr($hint['text'], 0, 100)) ?><?= strlen($hint['text']) > 100 ? '...' : '' ?>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($hint['text_en'])): ?>
                                    <span class="badge bg-success" title="<?= htmlspecialchars(substr($hint['text_en'], 0, 100)) ?>">
                                        <i class="fas fa-check"></i>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary" title="Нет перевода">
                                        <i class="fas fa-times"></i>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($hint['has_map']): ?>
                                    <span class="badge bg-info">
                                        <i class="fas fa-map me-1"></i>Да
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="/admin/hints/edit.php?id=<?= $hint['id'] ?>"
                                       class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-secondary"
                                            onclick="copyHint(<?= $hint['id'] ?>)"
                                            title="Копировать">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button type="button"
                                            class="btn btn-sm btn-danger"
                                            onclick="deleteHint(<?= $hint['id'] ?>)"
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
        <?php if (empty($hints)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Подсказки не найдены</p>
                <?php if ($point): ?>
                    <a href="/admin/hints/create.php?point_id=<?= $point_id ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить первую подсказку
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
document.getElementById('selectAllHints').addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.hint-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateCopyButton();
});
document.querySelectorAll('.hint-checkbox').forEach(cb => {
    cb.addEventListener('change', updateCopyButton);
});
function updateCopyButton() {
    const selected = document.querySelectorAll('.hint-checkbox:checked').length;
    const btn = document.getElementById('copyBtn');
    if (selected > 0) {
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
}
function copyHint(id) {
    const targetPointId = prompt('Введите ID точки, в которую скопировать подсказку:');
    if (!targetPointId) return;
    fetch('/admin/api/copy_hint.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({hint_id: id, target_point_id: targetPointId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Подсказка успешно скопирована!');
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function copySelectedHints() {
    const selected = Array.from(document.querySelectorAll('.hint-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Выберите подсказки для копирования');
        return;
    }
    const targetPointId = prompt('Введите ID точки, в которую скопировать подсказки:');
    if (!targetPointId) return;
    fetch('/admin/api/copy_hint.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({hint_ids: selected, target_point_id: targetPointId})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('Подсказки успешно скопированы!');
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function deleteHint(id) {
    if (confirm('Удалить эту подсказку?')) {
        fetch('/admin/api/delete_hint.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({hint_id: id})
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