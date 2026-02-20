<?php
$page_title = 'Заявки на модератора';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/audit_log.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = intval($_POST['request_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($request_id && in_array($action, ['approve', 'reject'])) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM moderator_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            if ($request) {
                $new_status = ($action === 'approve') ? 'approved' : 'rejected';
                $stmt = $pdo->prepare("
                    UPDATE moderator_requests
                    SET status = ?, admin_comment = ?, reviewed_by = ?, reviewed_at = NOW()
                    WHERE id = ?
                ");
                $admin_id = $_SESSION['admin_id'] ?? null;
                if (!$admin_id) {
                    throw new Exception('Сессия администратора не найдена');
                }
                $stmt->execute([$new_status, $comment ?: null, $admin_id, $request_id]);
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE users SET role = 'MODERATOR' WHERE id = ?");
                    $stmt->execute([$request['user_id']]);
                    $stmt = $pdo->prepare("
                        INSERT IGNORE INTO moderator_balances (user_id, balance, total_earned, total_withdrawn)
                        VALUES (?, 0, 0, 0)
                    ");
                    $stmt->execute([$request['user_id']]);
                    $success = "Заявка одобрена. Пользователь стал модератором.";
                } else {
                    $success = "Заявка отклонена.";
                }
                logAudit("moderator_requests", $request_id, "moderator_request_{$action}", null, [
                    'user_id' => $request['user_id'],
                    'comment' => $comment
                ], "Заявка на модератора " . ($action === 'approve' ? 'одобрена' : 'отклонена'));
            }
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Ошибка: " . $e->getMessage();
        }
    }
}
$status_filter = $_GET['status'] ?? 'pending';
$where = "1=1";
if ($status_filter !== 'all') {
    $where = "mr.status = " . $pdo->quote($status_filter);
}
$stmt = $pdo->query("
    SELECT mr.*, u.username, u.first_name, u.telegram_id,
           reviewer.username as reviewer_username
    FROM moderator_requests mr
    JOIN users u ON mr.user_id = u.id
    LEFT JOIN users reviewer ON mr.reviewed_by = reviewer.id
    WHERE {$where}
    ORDER BY mr.created_at DESC
");
$requests = $stmt->fetchAll();
$stats = $pdo->query("
    SELECT
        SUM(status = 'pending') as pending,
        SUM(status = 'approved') as approved,
        SUM(status = 'rejected') as rejected
    FROM moderator_requests
")->fetch();
?>
<div class="container-fluid">
    <h2><i class="fas fa-user-plus me-2"></i>Заявки на модератора</h2>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= $stats['pending'] ?? 0 ?></h3>
                    <p class="mb-0">Ожидают</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['approved'] ?? 0 ?></h3>
                    <p class="mb-0">Одобрено</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['rejected'] ?? 0 ?></h3>
                    <p class="mb-0">Отклонено</p>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-body">
            <div class="btn-group">
                <a href="?status=pending" class="btn btn-<?= $status_filter === 'pending' ? 'warning' : 'outline-warning' ?>">
                    Ожидающие (<?= $stats['pending'] ?? 0 ?>)
                </a>
                <a href="?status=approved" class="btn btn-<?= $status_filter === 'approved' ? 'success' : 'outline-success' ?>">
                    Одобренные
                </a>
                <a href="?status=rejected" class="btn btn-<?= $status_filter === 'rejected' ? 'danger' : 'outline-danger' ?>">
                    Отклонённые
                </a>
                <a href="?status=all" class="btn btn-<?= $status_filter === 'all' ? 'primary' : 'outline-primary' ?>">
                    Все
                </a>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($requests)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-inbox fa-3x mb-3"></i>
                    <p>Заявок нет</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Сообщение</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($requests as $req): ?>
                                <tr>
                                    <td><?= $req['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($req['first_name'] ?? 'Без имени') ?></strong><br>
                                        <small class="text-muted">
                                            @<?= htmlspecialchars($req['username'] ?? 'no_username') ?>
                                            (<?= $req['telegram_id'] ?>)
                                        </small>
                                    </td>
                                    <td style="max-width: 300px;">
                                        <div class="text-truncate" title="<?= htmlspecialchars($req['message']) ?>">
                                            <?= htmlspecialchars($req['message']) ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = match($req['status']) {
                                            'pending' => 'warning',
                                            'approved' => 'success',
                                            'rejected' => 'danger',
                                            default => 'secondary'
                                        };
                                        $status_text = match($req['status']) {
                                            'pending' => 'Ожидает',
                                            'approved' => 'Одобрена',
                                            'rejected' => 'Отклонена',
                                            default => $req['status']
                                        };
                                        ?>
                                        <span class="badge bg-<?= $badge_class ?>"><?= $status_text ?></span>
                                        <?php if ($req['admin_comment']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($req['admin_comment']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('d.m.Y H:i', strtotime($req['created_at'])) ?>
                                        <?php if ($req['reviewed_at']): ?>
                                            <br><small class="text-muted">
                                                Обработана: <?= date('d.m.Y H:i', strtotime($req['reviewed_at'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                    data-bs-target="#approveModal"
                                                    data-id="<?= $req['id'] ?>"
                                                    data-name="<?= htmlspecialchars($req['first_name'] ?? $req['username']) ?>">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                                    data-bs-target="#rejectModal"
                                                    data-id="<?= $req['id'] ?>"
                                                    data-name="<?= htmlspecialchars($req['first_name'] ?? $req['username']) ?>">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" id="approve_request_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Одобрить заявку</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Одобрить заявку от <strong id="approve_name"></strong>?</p>
                    <p class="text-muted">Пользователь получит права модератора и сможет создавать маршруты.</p>
                    <div class="mb-3">
                        <label class="form-label">Комментарий (необязательно)</label>
                        <textarea name="comment" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Одобрить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject_request_id">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">Отклонить заявку</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Отклонить заявку от <strong id="reject_name"></strong>?</p>
                    <div class="mb-3">
                        <label class="form-label">Причина отклонения</label>
                        <textarea name="comment" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-danger">Отклонить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('[data-bs-target="#approveModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('approve_request_id').value = btn.dataset.id;
        document.getElementById('approve_name').textContent = btn.dataset.name;
    });
});
document.querySelectorAll('[data-bs-target="#rejectModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('reject_request_id').value = btn.dataset.id;
        document.getElementById('reject_name').textContent = btn.dataset.name;
    });
});
</script>
<?php require_once '../includes/footer.php'; ?>