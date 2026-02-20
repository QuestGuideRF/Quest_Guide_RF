<?php
$page_title = 'Модераторы';
require_once '../includes/header.php';
$stmt = $pdo->query("
    SELECT u.*,
           mb.balance, mb.total_earned, mb.total_withdrawn,
           (SELECT COUNT(*) FROM routes WHERE creator_id = u.id) as routes_count,
           (SELECT COUNT(*) FROM moderator_transactions WHERE user_id = u.id AND type = 'earning') as sales_count
    FROM users u
    LEFT JOIN moderator_balances mb ON u.id = mb.user_id
    WHERE u.role = 'MODERATOR'
    ORDER BY u.created_at DESC
");
$moderators = $stmt->fetchAll();
$total_stats = $pdo->query("
    SELECT
        COUNT(DISTINCT u.id) as total_moderators,
        COALESCE(SUM(mb.total_earned), 0) as total_paid,
        (SELECT COUNT(*) FROM routes WHERE creator_id IS NOT NULL) as total_routes,
        (SELECT COUNT(*) FROM moderator_transactions WHERE type = 'earning') as total_sales
    FROM users u
    LEFT JOIN moderator_balances mb ON u.id = mb.user_id
    WHERE u.role = 'MODERATOR'
")->fetch();
?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users-cog me-2"></i>Модераторы</h2>
        <a href="requests.php" class="btn btn-warning">
            <i class="fas fa-user-plus me-2"></i>Заявки
            <?php
            $pending = $pdo->query("SELECT COUNT(*) FROM moderator_requests WHERE status = 'pending'")->fetchColumn();
            if ($pending > 0):
            ?>
                <span class="badge bg-danger"><?= $pending ?></span>
            <?php endif; ?>
        </a>
    </div>
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body text-center">
                    <h3><?= $total_stats['total_moderators'] ?? 0 ?></h3>
                    <p class="mb-0">Модераторов</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body text-center">
                    <h3><?= $total_stats['total_routes'] ?? 0 ?></h3>
                    <p class="mb-0">Маршрутов создано</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body text-center">
                    <h3><?= $total_stats['total_sales'] ?? 0 ?></h3>
                    <p class="mb-0">Продаж</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body text-center">
                    <h3><?= number_format($total_stats['total_paid'] ?? 0, 0, '.', ' ') ?> грошей</h3>
                    <p class="mb-0">Выплачено</p>
                </div>
            </div>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <?php if (empty($moderators)): ?>
                <div class="text-center text-muted py-5">
                    <i class="fas fa-users-slash fa-3x mb-3"></i>
                    <p>Модераторов пока нет</p>
                    <a href="requests.php" class="btn btn-primary">Посмотреть заявки</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Маршрутов</th>
                                <th>Продаж</th>
                                <th>Заработано</th>
                                <th>Баланс</th>
                                <th>Выведено</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($moderators as $mod): ?>
                                <tr>
                                    <td><?= $mod['id'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($mod['first_name'] ?? 'Без имени') ?></strong><br>
                                        <small class="text-muted">
                                            @<?= htmlspecialchars($mod['username'] ?? 'no_username') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $mod['routes_count'] ?></span>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $mod['sales_count'] ?></span>
                                    </td>
                                    <td class="text-success">
                                        <strong><?= number_format($mod['total_earned'] ?? 0, 0, '.', ' ') ?> грошей</strong>
                                    </td>
                                    <td>
                                        <?= number_format($mod['balance'] ?? 0, 0, '.', ' ') ?> грошей
                                    </td>
                                    <td class="text-muted">
                                        <?= number_format($mod['total_withdrawn'] ?? 0, 0, '.', ' ') ?> грошей
                                    </td>
                                    <td>
                                        <?= date('d.m.Y', strtotime($mod['created_at'])) ?>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?= $mod['id'] ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
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
<?php require_once '../includes/footer.php'; ?>