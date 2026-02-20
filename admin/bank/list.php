<?php
$page_title = 'Банк грошей';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'balance_desc';
$where = [];
$params = [];
if ($search) {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$orderBy = 'tb.balance DESC';
switch ($sort) {
    case 'balance_asc':
        $orderBy = 'tb.balance ASC';
        break;
    case 'deposited_desc':
        $orderBy = 'tb.total_deposited DESC';
        break;
    case 'spent_desc':
        $orderBy = 'tb.total_spent DESC';
        break;
    case 'name_asc':
        $orderBy = 'u.first_name ASC, u.last_name ASC';
        break;
}
$stmt = $pdo->prepare("
    SELECT
        u.id,
        u.telegram_id,
        u.username,
        u.first_name,
        u.last_name,
        u.created_at,
        COALESCE(tb.balance, 0) as balance,
        COALESCE(tb.total_deposited, 0) as total_deposited,
        COALESCE(tb.total_spent, 0) as total_spent,
        COALESCE(tb.total_transferred_out, 0) as total_transferred_out,
        COALESCE(tb.total_transferred_in, 0) as total_transferred_in
    FROM users u
    LEFT JOIN token_balances tb ON u.id = tb.user_id
    $whereClause
    ORDER BY $orderBy
    LIMIT 100
");
$stmt->execute($params);
$users = $stmt->fetchAll();
$stats = $pdo->query("
    SELECT
        COUNT(DISTINCT user_id) as users_with_balance,
        COALESCE(SUM(balance), 0) as total_balance,
        COALESCE(SUM(total_deposited), 0) as total_deposited,
        COALESCE(SUM(total_spent), 0) as total_spent,
        COALESCE(SUM(total_transferred_out), 0) as total_transferred
    FROM token_balances
    WHERE balance > 0 OR total_deposited > 0
")->fetch();
$recent_transactions = $pdo->query("
    SELECT
        tt.*,
        u.username,
        u.first_name,
        ru.username as related_username,
        ru.first_name as related_first_name,
        r.name as route_name
    FROM token_transactions tt
    JOIN users u ON tt.user_id = u.id
    LEFT JOIN users ru ON tt.related_user_id = ru.id
    LEFT JOIN routes r ON tt.related_route_id = r.id
    ORDER BY tt.created_at DESC
    LIMIT 10
")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-coins me-2"></i>Банк грошей</h2>
    <div>
        <a href="transactions.php" class="btn btn-outline-primary">
            <i class="fas fa-history me-2"></i>Все транзакции
        </a>
        <a href="add_tokens.php?action=subtract" class="btn btn-outline-danger me-2">
            <i class="fas fa-minus me-2"></i>Списать гроши
        </a>
        <a href="add_tokens.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Начислить гроши
        </a>
    </div>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-primary"><?= number_format($stats['total_balance'], 0, ',', ' ') ?> грошей</h4>
                <p class="text-muted mb-0">Всего грошей в системе</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-success"><?= number_format($stats['total_deposited'], 0, ',', ' ') ?> грошей</h4>
                <p class="text-muted mb-0">Всего пополнено</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4 class="text-warning"><?= number_format($stats['total_spent'], 0, ',', ' ') ?> грошей</h4>
                <p class="text-muted mb-0">Всего потрачено</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card">
            <div class="card-body text-center">
                <h4><?= number_format($stats['users_with_balance']) ?></h4>
                <p class="text-muted mb-0">Пользователей с балансом</p>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-users me-2"></i>Балансы пользователей</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="row g-3 mb-4">
                    <div class="col-md-6">
                        <input type="text" name="search" class="form-control"
                               placeholder="Поиск по имени или username"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="sort" class="form-select">
                            <option value="balance_desc" <?= $sort === 'balance_desc' ? 'selected' : '' ?>>По балансу (убыв.)</option>
                            <option value="balance_asc" <?= $sort === 'balance_asc' ? 'selected' : '' ?>>По балансу (возр.)</option>
                            <option value="deposited_desc" <?= $sort === 'deposited_desc' ? 'selected' : '' ?>>По пополнениям</option>
                            <option value="spent_desc" <?= $sort === 'spent_desc' ? 'selected' : '' ?>>По тратам</option>
                            <option value="name_asc" <?= $sort === 'name_asc' ? 'selected' : '' ?>>По имени</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Пользователь</th>
                                <th class="text-end">Баланс</th>
                                <th class="text-end">Пополнено</th>
                                <th class="text-end">Потрачено</th>
                                <th class="text-center">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar bg-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                            <?= strtoupper(substr($user['first_name'] ?: $user['username'] ?: 'U', 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-semibold">
                                                <?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?>
                                            </div>
                                            <small class="text-muted">
                                                <?php if ($user['username']): ?>
                                                    @<?= htmlspecialchars($user['username']) ?>
                                                <?php else: ?>
                                                    ID: <?= $user['telegram_id'] ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <span class="badge bg-<?= $user['balance'] > 0 ? 'success' : 'secondary' ?> fs-6">
                                        <?= number_format($user['balance'], 0, ',', ' ') ?> грошей
                                    </span>
                                </td>
                                <td class="text-end text-success">
                                    +<?= number_format($user['total_deposited'], 0, ',', ' ') ?> грошей
                                </td>
                                <td class="text-end text-warning">
                                    -<?= number_format($user['total_spent'], 0, ',', ' ') ?> грошей
                                </td>
                                <td class="text-center">
                                    <a href="user.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary" title="Подробнее">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="add_tokens.php?user_id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-success" title="Начислить">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                    <a href="add_tokens.php?user_id=<?= $user['id'] ?>&action=subtract" class="btn btn-sm btn-outline-danger" title="Списать">
                                        <i class="fas fa-minus"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    Пользователи не найдены
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-history me-2"></i>Последние операции</h5>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($recent_transactions as $tx): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <?php
                                $icon = 'fas fa-question';
                                $badge_class = 'secondary';
                                $sign = '';
                                switch ($tx['type']) {
                                    case 'deposit':
                                        $icon = 'fas fa-arrow-down';
                                        $badge_class = 'success';
                                        $sign = '+';
                                        break;
                                    case 'purchase':
                                        $icon = 'fas fa-shopping-cart';
                                        $badge_class = 'warning';
                                        $sign = '-';
                                        break;
                                    case 'transfer_out':
                                        $icon = 'fas fa-arrow-right';
                                        $badge_class = 'danger';
                                        $sign = '-';
                                        break;
                                    case 'transfer_in':
                                        $icon = 'fas fa-arrow-left';
                                        $badge_class = 'info';
                                        $sign = '+';
                                        break;
                                    case 'refund':
                                        $icon = 'fas fa-undo';
                                        $badge_class = 'secondary';
                                        $sign = '+';
                                        break;
                                    case 'adjustment':
                                        $icon = 'fas fa-minus-circle';
                                        $badge_class = 'danger';
                                        $sign = '-';
                                        break;
                                }
                                ?>
                                <i class="<?= $icon ?> text-<?= $badge_class ?> me-2"></i>
                                <strong>
                                    <?= htmlspecialchars($tx['first_name'] ?? $tx['username'] ?? 'User') ?>
                                </strong>
                                <br>
                                <small class="text-muted">
                                    <?= htmlspecialchars($tx['description'] ?? '') ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= $badge_class ?>">
                                    <?= $sign ?><?= number_format($tx['amount'], 0) ?> грошей
                                </span>
                                <br>
                                <small class="text-muted">
                                    <?= date('d.m H:i', strtotime($tx['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($recent_transactions)): ?>
                    <div class="list-group-item text-center text-muted py-4">
                        Транзакций пока нет
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="transactions.php" class="btn btn-sm btn-outline-primary w-100">
                    Все транзакции
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>