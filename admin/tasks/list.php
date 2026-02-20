<?php
$page_title = 'Задачи модерации';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$status = isset($_GET['status']) ? $_GET['status'] : 'pending';
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';
$assigned_to = isset($_GET['assigned_to']) ? (int)$_GET['assigned_to'] : null;
$where = [];
$params = [];
if ($status !== 'all') {
    $where[] = "mt.status = ?";
    $params[] = $status;
}
if ($priority) {
    $where[] = "mt.priority = ?";
    $params[] = $priority;
}
if ($assigned_to) {
    $where[] = "mt.assigned_to = ?";
    $params[] = $assigned_to;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->query("SHOW TABLES LIKE 'moderation_tasks'");
$table_exists = $stmt->rowCount() > 0;
if ($table_exists) {
    $stmt = $pdo->prepare("
        SELECT mt.*, u.first_name as assigned_name, u.username as assigned_username
        FROM moderation_tasks mt
        LEFT JOIN users u ON mt.assigned_to = u.id
        $whereClause
        ORDER BY
            CASE mt.priority
                WHEN 'urgent' THEN 1
                WHEN 'high' THEN 2
                WHEN 'medium' THEN 3
                WHEN 'low' THEN 4
            END,
            mt.created_at DESC
    ");
    $stmt->execute($params);
    $tasks = $stmt->fetchAll();
} else {
    $tasks = [];
}
$admins = $pdo->query("SELECT id, first_name, username FROM users WHERE role = 'ADMIN' ORDER BY first_name")->fetchAll();
$current_admin = getCurrentAdmin();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tasks me-2"></i>Задачи модерации</h2>
    <button class="btn btn-primary" onclick="createTaskFromPending()">
        <i class="fas fa-plus me-2"></i>Создать задачи из ожидающих
    </button>
</div>
<?php if (!$table_exists): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Таблица <code>moderation_tasks</code> не существует. Выполните SQL скрипт <code>admin/database/create_audit_log.sql</code>
    </div>
<?php endif; ?>
<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Статус</label>
                <select name="status" class="form-select">
                    <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>Все</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Ожидающие</option>
                    <option value="in_progress" <?= $status === 'in_progress' ? 'selected' : '' ?>>В работе</option>
                    <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>Завершенные</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Приоритет</label>
                <select name="priority" class="form-select">
                    <option value="">Все</option>
                    <option value="urgent" <?= $priority === 'urgent' ? 'selected' : '' ?>>Срочный</option>
                    <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>Высокий</option>
                    <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>Средний</option>
                    <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>Низкий</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Назначено</label>
                <select name="assigned_to" class="form-select">
                    <option value="">Все</option>
                    <?php foreach ($admins as $admin): ?>
                        <option value="<?= $admin['id'] ?>" <?= $assigned_to == $admin['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($admin['first_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-filter me-2"></i>Фильтр
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Таблица задач -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Тип</th>
                        <th>ID</th>
                        <th>Приоритет</th>
                        <th>Статус</th>
                        <th>Назначено</th>
                        <th>Описание</th>
                        <th>Создано</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tasks as $task): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary"><?= htmlspecialchars($task['type']) ?></span>
                            </td>
                            <td><?= $task['entity_id'] ?></td>
                            <td>
                                <?php
                                $priority_colors = [
                                    'urgent' => 'danger',
                                    'high' => 'warning',
                                    'medium' => 'info',
                                    'low' => 'secondary'
                                ];
                                $color = $priority_colors[$task['priority']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($task['priority']) ?></span>
                            </td>
                            <td>
                                <?php
                                $status_colors = [
                                    'pending' => 'warning',
                                    'in_progress' => 'primary',
                                    'completed' => 'success',
                                    'cancelled' => 'secondary'
                                ];
                                $color = $status_colors[$task['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= htmlspecialchars($task['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($task['assigned_name']): ?>
                                    <?= htmlspecialchars($task['assigned_name']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Не назначено</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small><?= htmlspecialchars(mb_substr($task['description'] ?? '', 0, 50)) ?></small>
                            </td>
                            <td>
                                <small><?= date('d.m.Y H:i', strtotime($task['created_at'])) ?></small>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <button class="btn btn-sm btn-primary" onclick="assignTask(<?= $task['id'] ?>)">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <button class="btn btn-sm btn-success" onclick="completeTask(<?= $task['id'] ?>)">
                                        <i class="fas fa-check"></i>
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
function createTaskFromPending() {
    if (confirm('Создать задачи для всех ожидающих фото и отзывов?')) {
        fetch('/admin/api/create_tasks.php', {method: 'POST'})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert(`Создано задач: ${data.count}`);
                    location.reload();
                } else {
                    alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
                }
            });
    }
}
function assignTask(taskId) {
    fetch('/admin/api/assign_task.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({task_id: taskId})
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
function completeTask(taskId) {
    fetch('/admin/api/complete_task.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({task_id: taskId})
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
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>