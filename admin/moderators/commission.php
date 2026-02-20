<?php
$page_title = 'Настройка комиссии';
require_once '../includes/header.php';
function getSetting($pdo, $key, $default = null) {
    $stmt = $pdo->prepare("SELECT value FROM platform_settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $result = $stmt->fetchColumn();
    return $result !== false ? $result : $default;
}
function setSetting($pdo, $key, $value, $description = null) {
    $stmt = $pdo->prepare("
        INSERT INTO platform_settings (`key`, value, description)
        VALUES (?, ?, ?)
        ON DUPLICATE KEY UPDATE value = VALUES(value)
    ");
    $stmt->execute([$key, $value, $description]);
}
$commission_percent = getSetting($pdo, 'commission_percent', '10');
$commission_min = getSetting($pdo, 'commission_min', '3');
$commission_max = getSetting($pdo, 'commission_max', '30');
$moderator_enabled = getSetting($pdo, 'moderator_enabled', '1');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $new_percent = floatval($_POST['commission_percent'] ?? 10);
        $new_min = floatval($_POST['commission_min'] ?? 3);
        $new_max = floatval($_POST['commission_max'] ?? 30);
        $new_enabled = isset($_POST['moderator_enabled']) ? '1' : '0';
        if ($new_percent < $new_min || $new_percent > $new_max) {
            $error = "Комиссия должна быть между минимумом и максимумом";
        } else {
            setSetting($pdo, 'commission_percent', $new_percent, 'Процент комиссии платформы');
            setSetting($pdo, 'commission_min', $new_min, 'Минимальный процент комиссии');
            setSetting($pdo, 'commission_max', $new_max, 'Максимальный процент комиссии');
            setSetting($pdo, 'moderator_enabled', $new_enabled, 'Включена ли система модераторов');
            logAudit($_SESSION['admin_user']['id'], 'update_commission', 'platform_settings', null, [
                'commission_percent' => $new_percent,
                'moderator_enabled' => $new_enabled
            ], "Обновлены настройки комиссии");
            $commission_percent = $new_percent;
            $commission_min = $new_min;
            $commission_max = $new_max;
            $moderator_enabled = $new_enabled;
            $success = "Настройки сохранены";
        }
    } catch (Exception $e) {
        $error = "Ошибка: " . $e->getMessage();
    }
}
$platform_stats = $pdo->query("
    SELECT
        COALESCE(SUM(platform_amount), 0) as total_commission,
        COUNT(*) as total_transactions
    FROM platform_earnings
")->fetch();
$monthly_stats = $pdo->query("
    SELECT
        DATE_FORMAT(created_at, '%Y-%m') as month,
        SUM(platform_amount) as commission,
        SUM(moderator_amount) as moderator_paid,
        COUNT(*) as sales
    FROM platform_earnings
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
")->fetchAll();
?>
<div class="container-fluid">
    <h2><i class="fas fa-percentage me-2"></i>Настройка комиссии</h2>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog me-2"></i>Настройки комиссии</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="moderator_enabled"
                                       name="moderator_enabled" <?= $moderator_enabled == '1' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="moderator_enabled">
                                    <strong>Система модераторов активна</strong>
                                </label>
                            </div>
                            <small class="text-muted">Когда отключено, модераторы не могут создавать маршруты</small>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <label class="form-label">Текущая комиссия платформы (%)</label>
                            <input type="number" name="commission_percent" class="form-control"
                                   value="<?= htmlspecialchars($commission_percent) ?>"
                                   min="<?= $commission_min ?>" max="<?= $commission_max ?>" step="0.1">
                            <small class="text-muted">
                                Эта сумма удерживается с каждой продажи маршрута модератора
                            </small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Минимум (%)</label>
                                    <input type="number" name="commission_min" class="form-control"
                                           value="<?= htmlspecialchars($commission_min) ?>" min="0" max="100" step="0.1">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Максимум (%)</label>
                                    <input type="number" name="commission_max" class="form-control"
                                           value="<?= htmlspecialchars($commission_max) ?>" min="0" max="100" step="0.1">
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Пример:</strong> При продаже маршрута за 500 грошей с комиссией <?= $commission_percent ?>%:
                            <ul class="mb-0 mt-2">
                                <li>Платформа получит: <strong><?= number_format(500 * $commission_percent / 100, 0) ?> грошей</strong></li>
                                <li>Модератор получит: <strong><?= number_format(500 * (100 - $commission_percent) / 100, 0) ?> грошей</strong></li>
                            </ul>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Доход платформы</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center mb-4">
                        <div class="col-6">
                            <h2 class="text-success"><?= number_format($platform_stats['total_commission'] ?? 0, 0, '.', ' ') ?> грошей</h2>
                            <p class="text-muted mb-0">Всего комиссий</p>
                        </div>
                        <div class="col-6">
                            <h2 class="text-primary"><?= $platform_stats['total_transactions'] ?? 0 ?></h2>
                            <p class="text-muted mb-0">Продаж</p>
                        </div>
                    </div>
                    <?php if (!empty($monthly_stats)): ?>
                        <h6>Помесячная статистика:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Месяц</th>
                                        <th>Продаж</th>
                                        <th>Комиссия</th>
                                        <th>Модераторам</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($monthly_stats as $stat): ?>
                                        <tr>
                                            <td><?= $stat['month'] ?></td>
                                            <td><?= $stat['sales'] ?></td>
                                            <td class="text-success"><?= number_format($stat['commission'], 0) ?> грошей</td>
                                            <td class="text-primary"><?= number_format($stat['moderator_paid'], 0) ?> грошей</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted py-3">
                            <i class="fas fa-chart-bar fa-2x mb-2"></i>
                            <p>Пока нет продаж маршрутов модераторов</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once '../includes/footer.php'; ?>