<?php
$page_title = 'Запросы на вывод';
require_once '../includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = intval($_POST['request_id'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');
    if ($request_id && in_array($action, ['complete', 'reject'])) {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("SELECT * FROM withdrawal_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            if ($request) {
                if ($action === 'complete') {
                    $stmt = $pdo->prepare("SELECT balance FROM moderator_balances WHERE user_id = ?");
                    $stmt->execute([$request['user_id']]);
                    $balance = $stmt->fetchColumn();
                    if ($balance < $request['amount']) {
                        throw new Exception("Недостаточно средств на балансе модератора");
                    }
                    $stmt = $pdo->prepare("
                        UPDATE moderator_balances
                        SET balance = balance - ?, total_withdrawn = total_withdrawn + ?
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$request['amount'], $request['amount'], $request['user_id']]);
                    $stmt = $pdo->prepare("
                        INSERT INTO moderator_transactions (user_id, type, amount, description, created_at)
                        VALUES (?, 'withdrawal', ?, ?, NOW())
                    ");
                    $stmt->execute([$request['user_id'], $request['amount'], "Вывод средств"]);
                    $stmt = $pdo->prepare("
                        UPDATE withdrawal_requests
                        SET status = 'completed', admin_comment = ?, processed_by = ?, processed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$comment ?: 'Выполнено', $_SESSION['admin_user']['id'], $request_id]);
                    $success = "Вывод выполнен. Средства списаны с баланса модератора.";
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE withdrawal_requests
                        SET status = 'rejected', admin_comment = ?, processed_by = ?, processed_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$comment, $_SESSION['admin_user']['id'], $request_id]);
                    $success = "Запрос отклонён.";
                }
                logAudit($_SESSION['admin_user']['id'], "withdrawal_{$action}", "withdrawal_requests", $request_id, [
                    'user_id' => $request['user_id'],
                    'amount' => $request['amount']
                ], "Запрос на вывод " . ($action === 'complete' ? 'выполнен' : 'отклонён'));
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
    $where = "wr.status = " . $pdo->quote($status_filter);
}
$stmt = $pdo->query("
    SELECT wr.*, u.username, u.first_name, u.telegram_id,
           mb.balance as current_balance,
           processor.username as processor_username
    FROM withdrawal_requests wr
    JOIN users u ON wr.user_id = u.id
    LEFT JOIN moderator_balances mb ON wr.user_id = mb.user_id
    LEFT JOIN users processor ON wr.processed_by = processor.id
    WHERE {$where}
    ORDER BY wr.created_at DESC
");
$requests = $stmt->fetchAll();
$stats = $pdo->query("
    SELECT
        SUM(status = 'pending') as pending,
        SUM(status = 'completed') as completed,
        SUM(status = 'rejected') as rejected,
        SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_paid
    FROM withdrawal_requests
")->fetch();
?>
<div class="container-fluid">
    <h2><i class="fas fa-money-bill-wave me-2"></i>Запросы на вывод</h2>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= $stats['pending'] ?? 0 ?></h3>
                    <p class="mb-0">Ожидают</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['completed'] ?? 0 ?></h3>
                    <p class="mb-0">Выполнено</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body text-center">
                    <h3><?= $stats['rejected'] ?? 0 ?></h3>
                    <p class="mb-0">Отклонено</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= number_format($stats['total_paid'] ?? 0, 0, '.', ' ') ?> грошей</h3>
                    <p class="mb-0">Всего выплачено</p>
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
                <a href="?status=completed" class="btn btn-<?= $status_filter === 'completed' ? 'success' : 'outline-success' ?>">
                    Выполненные
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
                    <p>Запросов на вывод нет</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Модератор</th>
                                <th>Сумма</th>
                                <th>Баланс</th>
                                <th>Реквизиты</th>
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
                                        <small class="text-muted">@<?= htmlspecialchars($req['username'] ?? 'no_username') ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-primary"><?= number_format($req['amount'], 0) ?> грошей</strong>
                                    </td>
                                    <td>
                                        <?= number_format($req['current_balance'] ?? 0, 0) ?> грошей
                                        <?php if (($req['current_balance'] ?? 0) < $req['amount']): ?>
                                            <br><small class="text-danger">Недостаточно!</small>
                                        <?php endif; ?>
                                    </td>
                                    <td style="max-width: 200px;">
                                        <small><?= htmlspecialchars($req['payment_details']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $badge_class = match($req['status']) {
                                            'pending' => 'warning',
                                            'processing' => 'info',
                                            'completed' => 'success',
                                            'rejected' => 'danger',
                                            default => 'secondary'
                                        };
                                        $status_text = match($req['status']) {
                                            'pending' => 'Ожидает',
                                            'processing' => 'В обработке',
                                            'completed' => 'Выполнен',
                                            'rejected' => 'Отклонён',
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
                                    </td>
                                    <td>
                                        <?php if ($req['status'] === 'pending'): ?>
                                            <button class="btn btn-sm btn-success" data-bs-toggle="modal"
                                                    data-bs-target="#completeModal"
                                                    data-id="<?= $req['id'] ?>"
                                                    data-amount="<?= $req['amount'] ?>"
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
<div class="modal fade" id="completeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="action" value="complete">
                <input type="hidden" name="request_id" id="complete_request_id">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Подтвердить вывод</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Подтвердить вывод <strong id="complete_amount"></strong> грошей для <strong id="complete_name"></strong>?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Убедитесь, что вы уже перевели деньги модератору по указанным реквизитам!
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Комментарий (необязательно)</label>
                        <textarea name="comment" class="form-control" rows="2" placeholder="Например: Переведено на карту"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-success">Подтвердить вывод</button>
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
                    <h5 class="modal-title">Отклонить запрос</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Отклонить запрос на вывод от <strong id="reject_name"></strong>?</p>
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
document.querySelectorAll('[data-bs-target="#completeModal"]').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('complete_request_id').value = btn.dataset.id;
        document.getElementById('complete_amount').textContent = btn.dataset.amount;
        document.getElementById('complete_name').textContent = btn.dataset.name;
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