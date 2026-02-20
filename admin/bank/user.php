<?php
$page_title = 'Баланс пользователя';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$user_id) {
    header('Location: list.php');
    exit;
}
$stmt = $pdo->prepare("
    SELECT
        u.*,
        COALESCE(tb.balance, 0) as balance,
        COALESCE(tb.total_deposited, 0) as total_deposited,
        COALESCE(tb.total_spent, 0) as total_spent,
        COALESCE(tb.total_transferred_out, 0) as total_transferred_out,
        COALESCE(tb.total_transferred_in, 0) as total_transferred_in
    FROM users u
    LEFT JOIN token_balances tb ON u.id = tb.user_id
    WHERE u.id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: list.php');
    exit;
}
$page_title = 'Баланс: ' . ($user['first_name'] ?: $user['username'] ?: 'User');
$transactions_stmt = $pdo->prepare("
    SELECT
        tt.*,
        ru.username as related_username,
        ru.first_name as related_first_name,
        r.name as route_name
    FROM token_transactions tt
    LEFT JOIN users ru ON tt.related_user_id = ru.id
    LEFT JOIN routes r ON tt.related_route_id = r.id
    WHERE tt.user_id = ?
    ORDER BY tt.created_at DESC
    LIMIT 50
");
$transactions_stmt->execute([$user_id]);
$transactions = $transactions_stmt->fetchAll();
$month_stats = $pdo->prepare("
    SELECT
        COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) as deposits,
        COALESCE(SUM(CASE WHEN type = 'purchase' THEN amount ELSE 0 END), 0) as purchases,
        COALESCE(SUM(CASE WHEN type IN ('transfer_out') THEN amount ELSE 0 END), 0) as transfers_out,
        COALESCE(SUM(CASE WHEN type IN ('transfer_in') THEN amount ELSE 0 END), 0) as transfers_in
    FROM token_transactions
    WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$month_stats->execute([$user_id]);
$stats = $month_stats->fetch();
$type_labels = [
    'deposit' => ['label' => 'Пополнение', 'icon' => 'fas fa-arrow-down', 'class' => 'success', 'sign' => '+'],
    'purchase' => ['label' => 'Покупка', 'icon' => 'fas fa-shopping-cart', 'class' => 'warning', 'sign' => '-'],
    'transfer_out' => ['label' => 'Исх. перевод', 'icon' => 'fas fa-arrow-right', 'class' => 'danger', 'sign' => '-'],
    'transfer_in' => ['label' => 'Вх. перевод', 'icon' => 'fas fa-arrow-left', 'class' => 'info', 'sign' => '+'],
    'refund' => ['label' => 'Возврат', 'icon' => 'fas fa-undo', 'class' => 'secondary', 'sign' => '+'],
<<<<<<< HEAD
    'adjustment' => ['label' => 'Списание админом', 'icon' => 'fas fa-minus-circle', 'class' => 'danger', 'sign' => '-'],
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>
        <i class="fas fa-user me-2"></i>
        <?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?>
        <?php if ($user['username']): ?>
        <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
        <?php endif; ?>
    </h2>
    <div>
<<<<<<< HEAD
        <a href="add_tokens.php?user_id=<?= $user_id ?>" class="btn btn-success me-2">
            <i class="fas fa-plus me-2"></i>Начислить
        </a>
        <a href="add_tokens.php?user_id=<?= $user_id ?>&action=subtract" class="btn btn-danger me-2">
            <i class="fas fa-minus me-2"></i>Списать
        </a>
=======
        <a href="add_tokens.php?user_id=<?= $user_id ?>" class="btn btn-success">
            <i class="fas fa-plus me-2"></i>Начислить
        </a>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        <a href="list.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-2"></i>Назад
        </a>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Информация о пользователе -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <div class="avatar bg-primary rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($user['first_name'] ?: $user['username'] ?: 'U', 0, 1)) ?>
                </div>
                <h4><?= htmlspecialchars($user['first_name'] . ' ' . ($user['last_name'] ?? '')) ?></h4>
                <?php if ($user['username']): ?>
                <p class="text-muted">@<?= htmlspecialchars($user['username']) ?></p>
                <?php endif; ?>
                <p class="text-muted mb-0">
                    <small>Telegram ID: <?= $user['telegram_id'] ?></small>
                </p>
                <p class="text-muted">
                    <small>Регистрация: <?= date('d.m.Y', strtotime($user['created_at'])) ?></small>
                </p>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="row g-3">
            <div class="col-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
<<<<<<< HEAD
                        <h2 class="mb-0"><?= number_format($user['balance'], 0, ',', ' ') ?> грошей</h2>
=======
                        <h2 class="mb-0"><?= number_format($user['balance'], 0, ',', ' ') ?> ₽</h2>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <p class="mb-0 opacity-75">Текущий баланс</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card bg-success bg-opacity-10">
                    <div class="card-body text-center">
<<<<<<< HEAD
                        <h4 class="text-success mb-0">+<?= number_format($user['total_deposited'], 0, ',', ' ') ?> грошей</h4>
=======
                        <h4 class="text-success mb-0">+<?= number_format($user['total_deposited'], 0, ',', ' ') ?> ₽</h4>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <p class="text-muted mb-0">Всего пополнено</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
<<<<<<< HEAD
                        <h5 class="text-warning mb-0">-<?= number_format($user['total_spent'], 0, ',', ' ') ?> грошей</h5>
=======
                        <h5 class="text-warning mb-0">-<?= number_format($user['total_spent'], 0, ',', ' ') ?> ₽</h5>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <small class="text-muted">Потрачено</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
<<<<<<< HEAD
                        <h5 class="text-danger mb-0">-<?= number_format($user['total_transferred_out'], 0, ',', ' ') ?> грошей</h5>
=======
                        <h5 class="text-danger mb-0">-<?= number_format($user['total_transferred_out'], 0, ',', ' ') ?> ₽</h5>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <small class="text-muted">Переведено</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body text-center">
<<<<<<< HEAD
                        <h5 class="text-info mb-0">+<?= number_format($user['total_transferred_in'], 0, ',', ' ') ?> грошей</h5>
=======
                        <h5 class="text-info mb-0">+<?= number_format($user['total_transferred_in'], 0, ',', ' ') ?> ₽</h5>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <small class="text-muted">Получено</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Статистика за месяц -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Активность за 30 дней</h5>
    </div>
    <div class="card-body">
        <div class="row text-center">
            <div class="col">
<<<<<<< HEAD
                <h5 class="text-success">+<?= number_format($stats['deposits'], 0) ?> грошей</h5>
                <small class="text-muted">Пополнений</small>
            </div>
            <div class="col">
                <h5 class="text-warning">-<?= number_format($stats['purchases'], 0) ?> грошей</h5>
                <small class="text-muted">Покупок</small>
            </div>
            <div class="col">
                <h5 class="text-danger">-<?= number_format($stats['transfers_out'], 0) ?> грошей</h5>
                <small class="text-muted">Исх. переводов</small>
            </div>
            <div class="col">
                <h5 class="text-info">+<?= number_format($stats['transfers_in'], 0) ?> грошей</h5>
=======
                <h5 class="text-success">+<?= number_format($stats['deposits'], 0) ?> ₽</h5>
                <small class="text-muted">Пополнений</small>
            </div>
            <div class="col">
                <h5 class="text-warning">-<?= number_format($stats['purchases'], 0) ?> ₽</h5>
                <small class="text-muted">Покупок</small>
            </div>
            <div class="col">
                <h5 class="text-danger">-<?= number_format($stats['transfers_out'], 0) ?> ₽</h5>
                <small class="text-muted">Исх. переводов</small>
            </div>
            <div class="col">
                <h5 class="text-info">+<?= number_format($stats['transfers_in'], 0) ?> ₽</h5>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                <small class="text-muted">Вх. переводов</small>
            </div>
        </div>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- История транзакций -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-history me-2"></i>История операций</h5>
        <a href="transactions.php?user_id=<?= $user_id ?>" class="btn btn-sm btn-outline-primary">
            Все операции
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Дата</th>
                        <th>Тип</th>
                        <th>Описание</th>
                        <th class="text-end">Сумма</th>
                        <th class="text-end">Баланс</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $tx): ?>
                    <?php
                        $type_info = $type_labels[$tx['type']] ?? ['label' => $tx['type'], 'icon' => 'fas fa-question', 'class' => 'secondary', 'sign' => ''];
                    ?>
                    <tr>
                        <td>
                            <div><?= date('d.m.Y', strtotime($tx['created_at'])) ?></div>
                            <small class="text-muted"><?= date('H:i', strtotime($tx['created_at'])) ?></small>
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
                                <br><span class="text-muted">
                                    <?= $tx['type'] === 'transfer_in' ? '← от' : '→' ?>
                                    @<?= htmlspecialchars($tx['related_username']) ?>
                                </span>
                                <?php endif; ?>
                                <?php if ($tx['route_name']): ?>
                                <br><span class="text-muted">🗺 <?= htmlspecialchars($tx['route_name']) ?></span>
                                <?php endif; ?>
                            </small>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold text-<?= $type_info['sign'] === '+' ? 'success' : 'danger' ?>">
<<<<<<< HEAD
                                <?= $type_info['sign'] ?><?= number_format($tx['amount'], 0, ',', ' ') ?> грошей
                            </span>
                        </td>
                        <td class="text-end text-muted">
                            <?= number_format($tx['balance_after'], 0, ',', ' ') ?> грошей
=======
                                <?= $type_info['sign'] ?><?= number_format($tx['amount'], 0, ',', ' ') ?> ₽
                            </span>
                        </td>
                        <td class="text-end text-muted">
                            <?= number_format($tx['balance_after'], 0, ',', ' ') ?> ₽
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
                            Операций пока нет
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>