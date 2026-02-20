<?php
$page_title = 'История изменений';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/audit_log.php';
require_once __DIR__ . '/../../includes/db.php';
$entity_type = isset($_GET['entity_type']) ? $_GET['entity_type'] : null;
$entity_id = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : null;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
$logs = getAuditLog($entity_type, $entity_id, $limit);
$entity_types = ['route', 'point', 'city', 'hint', 'tag', 'user', 'review'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-history me-2"></i>История изменений</h2>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Тип сущности</label>
                <select name="entity_type" class="form-select">
                    <option value="">Все типы</option>
                    <?php foreach ($entity_types as $type): ?>
                        <option value="<?= $type ?>" <?= $entity_type === $type ? 'selected' : '' ?>>
                            <?= ucfirst($type) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ID сущности</label>
                <input type="number" name="entity_id" class="form-control" value="<?= $entity_id ?>" placeholder="ID">
            </div>
            <div class="col-md-2">
                <label class="form-label">Лимит</label>
                <input type="number" name="limit" class="form-control" value="<?= $limit ?>" min="10" max="1000">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Фильтр
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
                        <th>Дата</th>
                        <th>Администратор</th>
                        <th>Тип</th>
                        <th>ID</th>
                        <th>Действие</th>
                        <th>Изменения</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td>
                                <small><?= date('d.m.Y H:i:s', strtotime($log['created_at'])) ?></small>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($log['first_name'] ?? 'Unknown') ?></strong>
                                <?php if ($log['username']): ?>
                                    <br><small class="text-muted">@<?= htmlspecialchars($log['username']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info"><?= htmlspecialchars($log['entity_type']) ?></span>
                            </td>
                            <td><?= $log['entity_id'] ?></td>
                            <td>
                                <?php
                                $action_colors = [
                                    'create' => 'success',
                                    'update' => 'primary',
                                    'delete' => 'danger'
                                ];
                                $color = $action_colors[$log['action']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($log['action']) ?></span>
                            </td>
                            <td>
                                <small><?= htmlspecialchars(mb_substr($log['changes'] ?? '', 0, 100)) ?></small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-info" onclick="showLogDetails(<?= $log['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="logDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали изменения</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="logDetailsContent">
            </div>
        </div>
    </div>
</div>
<script>
function showLogDetails(logId) {
    fetch(`/admin/api/get_audit_log.php?id=${logId}`)
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const log = data.log;
                const content = document.getElementById('logDetailsContent');
                let html = `
                    <p><strong>Дата:</strong> ${new Date(log.created_at).toLocaleString('ru-RU')}</p>
                    <p><strong>Администратор:</strong> ${log.first_name} ${log.username ? '@' + log.username : ''}</p>
                    <p><strong>Действие:</strong> ${log.action} на ${log.entity_type}
                    ${log.changes ? `<p><strong>Описание:</strong> ${log.changes}</p>` : ''}
                `;
                if (log.old_data || log.new_data) {
                    html += '<hr><h6>Изменения данных:</h6>';
                    if (log.old_data) {
                        html += '<h6>Было:</h6><pre class="bg-light p-2">' + JSON.stringify(JSON.parse(log.old_data), null, 2) + '</pre>';
                    }
                    if (log.new_data) {
                        html += '<h6>Стало:</h6><pre class="bg-light p-2">' + JSON.stringify(JSON.parse(log.new_data), null, 2) + '</pre>';
                    }
                }
                content.innerHTML = html;
                const modal = new bootstrap.Modal(document.getElementById('logDetailsModal'));
                modal.show();
            }
        });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>