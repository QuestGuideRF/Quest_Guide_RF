<?php
$page_title = 'История транзакций';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$type = isset($_GET['type']) ? $_GET['type'] : '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;
$where = [];
$params = [];
if ($type) {
    $where[] = "tt.type = ?";
    $params[] = $type;
}
if ($user_id) {
    $where[] = "tt.user_id = ?";
    $params[] = $user_id;
}
if ($date_from) {
    $where[] = "DATE(tt.created_at) >= ?";
    $params[] = $date_from;
}
if ($date_to) {
    $where[] = "DATE(tt.created_at) <= ?";
    $params[] = $date_to;
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM token_transactions tt $whereClause");
$count_stmt->execute($params);
$total = $count_stmt->fetchColumn();
$total_pages = ceil($total / $per_page);
$stmt = $pdo->prepare("
    SELECT
        tt.*,
        u.username,
        u.first_name,
        u.last_name,
        u.telegram_id,
        ru.username as related_username,
        ru.first_name as related_first_name,
        r.name as route_name
    FROM token_transactions tt
    JOIN users u ON tt.user_id = u.id
    LEFT JOIN users ru ON tt.related_user_id = ru.id
    LEFT JOIN routes r ON tt.related_route_id = r.id
    $whereClause
    ORDER BY tt.created_at DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$transactions = $stmt->fetchAll();
$stats_params = $params;
$stats_stmt = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as deposits,
        COALESCE(SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END), 0) as purchases,
        COALESCE(SUM(CASE WHEN type = 'transfer_out' THEN amount ELSE 0 END), 0) as transfers,
        COUNT(*) as total_count
    FROM token_transactions tt
    $whereClause
");
$stats_stmt->execute($stats_params);
$stats = $stats_stmt->fetch();
$type_labels = [
    'deposit' => ['label' => 'Пополнение', 'icon' => 'fas fa-arrow-down', 'class' => 'success'],
    'purchase' => ['label' => 'Покупка', 'icon' => 'fas fa-shopping-cart', 'class' => 'warning'],
    'transfer_out' => ['label' => 'Исх. перевод', 'icon' => 'fas fa-arrow-right', 'class' => 'danger'],
    'transfer_in' => ['label' => 'Вх. перевод', 'icon' => 'fas fa-arrow-left', 'class' => 'info'],
    'refund' => ['label' => 'Возврат', 'icon' => 'fas fa-undo', 'class' => 'secondary'],
<<<<<<< HEAD
    'adjustment' => ['label' => 'Списание админом', 'icon' => 'fas fa-minus-circle', 'class' => 'danger'],
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-history me-2"></i>История транзакций</h2>
    <a href="list.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Назад к банку
    </a>
</div>
<<<<<<< HEAD
=======
<!-- Статистика -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card bg-success bg-opacity-10">
            <div class="card-body text-center">
<<<<<<< HEAD
                <h4 class="text-success"><?= number_format($stats['deposits'], 0, ',', ' ') ?> грошей</h4>
=======
                <h4 class="text-success"><?= number_format($stats['deposits'], 0, ',', ' ') ?> ₽</h4>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                <p class="text-muted mb-0">Пополнений</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-warning bg-opacity-10">
            <div class="card-body text-center">
<<<<<<< HEAD
                <h4 class="text-warning"><?= number_format($stats['purchases'], 0, ',', ' ') ?> грошей</h4>
=======
                <h4 class="text-warning"><?= number_format($stats['purchases'], 0, ',', ' ') ?> ₽</h4>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                <p class="text-muted mb-0">Покупок</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card bg-info bg-opacity-10">
            <div class="card-body text-center">
<<<<<<< HEAD
                <h4 class="text-info"><?= number_format($stats['transfers'], 0, ',', ' ') ?> грошей</h4>
=======
                <h4 class="text-info"><?= number_format($stats['transfers'], 0, ',', ' ') ?> ₽</h4>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                <p class="text-muted mb-0">Переводов</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4><?= number_format($stats['total_count']) ?></h4>
                <p class="text-muted mb-0">Всего операций</p>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Фильтры -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Тип операции</label>
                <select name="type" class="form-select">
                    <option value="">Все типы</option>
                    <?php foreach ($type_labels as $key => $val): ?>
                    <option value="<?= $key ?>" <?= $type === $key ? 'selected' : '' ?>>
                        <?= $val['label'] ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Дата с</label>
                <input type="date" name="date_from" class="form-control" value="<?= $date_from ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Дата по</label>
                <input type="date" name="date_to" class="form-control" value="<?= $date_to ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="fas fa-filter me-2"></i>Применить
                </button>
                <a href="transactions.php" class="btn btn-outline-secondary">
                    <i class="fas fa-times"></i>
                </a>
            </div>
        </form>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Таблица транзакций -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Пользователь</th>
                        <th>Тип</th>
                        <th>Описание</th>
                        <th class="text-end">Сумма</th>
                        <th class="text-end">Баланс после</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                    <?php
                        $type_info = $type_labels[$tx['type']] ?? ['label' => $tx['type'], 'icon' => 'fas fa-question', 'class' => 'secondary'];
                        $is_income = in_array($tx['type'], ['deposit', 'transfer_in', 'refund']);
                    ?>
                    <tr>
                        <td><small class="text-muted">
                        <td>
                            <div><?= date('d.m.Y', strtotime($tx['created_at'])) ?></div>
                            <small class="text-muted"><?= date('H:i:s', strtotime($tx['created_at'])) ?></small>
                        </td>
                        <td>
                            <a href="user.php?id=<?= $tx['user_id'] ?>" class="text-decoration-none">
                                <strong><?= htmlspecialchars($tx['first_name'] . ' ' . ($tx['last_name'] ?? '')) ?></strong>
                                <?php if ($tx['username']): ?>
                                <br><small class="text-muted">@<?= htmlspecialchars($tx['username']) ?></small>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td>
                            <span class="badge bg-<?= $type_info['class'] ?>">
                                <i class="<?= $type_info['icon'] ?> me-1"></i>
                                <?= $type_info['label'] ?>
                            </span>
                        </td>
                        <td>
                            <small>
                                <?= htmlspecialchars($tx['description'] ?? '') ?>
                                <?php if ($tx['related_username']): ?>
                                <br><span class="text-muted">→ @<?= htmlspecialchars($tx['related_username']) ?></span>
                                <?php endif; ?>
                                <?php if ($tx['route_name']): ?>
                                <br><span class="text-muted">🗺 <?= htmlspecialchars($tx['route_name']) ?></span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td class="text-end">
                            <span class="text-<?= $is_income ? 'success' : 'danger' ?> fw-bold">
<<<<<<< HEAD
                                <?= $is_income ? '+' : '-' ?><?= number_format($tx['amount'], 0, ',', ' ') ?> грошей
=======
                                <?= $is_income ? '+' : '-' ?><?= number_format($tx['amount'], 0, ',', ' ') ?> ₽
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="text-muted">
<<<<<<< HEAD
                                <?= number_format($tx['balance_after'], 0, ',', ' ') ?> грошей
=======
                                <?= number_format($tx['balance_after'], 0, ',', ' ') ?> ₽
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            Транзакции не найдены
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<<<<<<< HEAD
=======
        <!-- Пагинация -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        <?php if ($total_pages > 1): ?>
        <nav class="mt-4">
            <ul class="pagination justify-content-center">
                <?php
                $query_params = $_GET;
                unset($query_params['page']);
                $base_url = '?' . http_build_query($query_params);
                ?>
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $base_url ?>&page=<?= $page - 1 ?>">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= $base_url ?>&page=<?= $i ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $total_pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $base_url ?>&page=<?= $page + 1 ?>">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>