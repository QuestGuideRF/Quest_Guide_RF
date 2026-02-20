<?php
$page_title = 'Партнерка';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
if (isModerator()) {
    header('Location: /admin/dashboard.php?error=no_access');
    exit;
}
$pdo = getDB()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['referral_reward_amount'])) {
        $value = (int) $_POST['referral_reward_amount'];
        if ($value < 0) {
            $error = 'Введите неотрицательное число.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO platform_settings (`key`, value) VALUES ('referral_reward_amount', ?)
                    ON DUPLICATE KEY UPDATE value = ?
                ");
                $stmt->execute([(string) $value, (string) $value]);
                $success = 'Сумма вознаграждения сохранена: ' . $value . ' грошей за покупку приглашённого.';
            } catch (Exception $e) {
                $error = 'Ошибка при сохранении: ' . $e->getMessage();
            }
        }
    }
    if (isset($_POST['review_reward_amount'])) {
        $value = (int) $_POST['review_reward_amount'];
        $enabled = isset($_POST['review_reward_enabled']) ? '1' : '0';
        try {
            $stmt = $pdo->prepare("INSERT INTO platform_settings (`key`, value) VALUES ('review_reward_amount', ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([(string) $value, (string) $value]);
            $stmt = $pdo->prepare("INSERT INTO platform_settings (`key`, value) VALUES ('review_reward_enabled', ?) ON DUPLICATE KEY UPDATE value = ?");
            $stmt->execute([$enabled, $enabled]);
            $success = 'Настройки бонуса за отзыв сохранены.';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
    if (isset($_POST['update_level'])) {
        $level_id = (int) $_POST['level_id'];
        $required = (int) $_POST['required_referrals'];
        $reward_value = isset($_POST['reward_value']) ? (float) $_POST['reward_value'] : null;
        $name = $_POST['level_name'] ?? '';
        $description = $_POST['level_description'] ?? '';
        $reward_type = $_POST['reward_type'] ?? 'tokens_per_referral';
        $allowed_types = ['tokens_per_referral', 'discount_code', 'percent_of_sales', 'free_route', 'special'];
        if (!in_array($reward_type, $allowed_types, true)) {
            $reward_type = 'tokens_per_referral';
        }
        try {
            $stmt = $pdo->prepare("UPDATE referral_levels SET required_referrals = ?, reward_value = ?, reward_type = ?, name = ?, description = ? WHERE id = ?");
            $stmt->execute([$required, $reward_value, $reward_type, $name, $description, $level_id]);
            $success = 'Уровень обновлён.';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
$current = 10;
$review_reward = 10;
$review_enabled = true;
try {
    $stmt = $pdo->query("SELECT value FROM platform_settings WHERE `key` = 'referral_reward_amount'");
    $row = $stmt->fetch();
    if ($row && $row['value'] !== null && $row['value'] !== '') {
        $current = (int) $row['value'];
    }
    $stmt = $pdo->query("SELECT value FROM platform_settings WHERE `key` = 'review_reward_amount'");
    $row = $stmt->fetch();
    if ($row && $row['value'] !== null) {
        $review_reward = (int) $row['value'];
    }
    $stmt = $pdo->query("SELECT value FROM platform_settings WHERE `key` = 'review_reward_enabled'");
    $row = $stmt->fetch();
    if ($row) {
        $review_enabled = $row['value'] === '1';
    }
} catch (Exception $e) {
}
$referral_levels = [];
try {
    $stmt = $pdo->query("SELECT * FROM referral_levels ORDER BY level");
    $referral_levels = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
}
$stats = [
    'referred_users' => 0,
    'referred_purchases' => 0,
    'total_referral_earned' => 0,
    'total_review_rewards' => 0,
];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE referred_by_id IS NOT NULL");
    $stats['referred_users'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->query("
        SELECT COUNT(*) FROM payments p
        INNER JOIN users u ON p.user_id = u.id
        WHERE u.referred_by_id IS NOT NULL AND p.status = 'SUCCESS'
    ");
    $stats['referred_purchases'] = (int) $stmt->fetchColumn();
    $stmt = $pdo->query("
        SELECT COALESCE(SUM(amount), 0) FROM token_transactions
        WHERE type = 'referral_reward'
    ");
    $stats['total_referral_earned'] = (float) $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COALESCE(SUM(reward_amount), 0) FROM reviews WHERE reward_given = 1");
    $stats['total_review_rewards'] = (float) $stmt->fetchColumn();
} catch (Exception $e) {
}
?>
<div class="referral-admin-page">
    <div class="page-hero mb-4">
        <h1 class="mb-2"><i class="fas fa-hand-holding-heart me-2"></i>Партнёрка</h1>
    </div>
    <?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show shadow-sm">
        <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card referral-stat-card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="referral-stat-icon bg-primary bg-opacity-25 rounded-3 me-3">
                        <i class="fas fa-users text-primary"></i>
                    </div>
                    <div>
                        <div class="text-muted small">По ссылкам</div>
                        <div class="fs-4 fw-bold"><?= $stats['referred_users'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card referral-stat-card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="referral-stat-icon bg-success bg-opacity-25 rounded-3 me-3">
                        <i class="fas fa-shopping-cart text-success"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Покупок</div>
                        <div class="fs-4 fw-bold"><?= $stats['referred_purchases'] ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card referral-stat-card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="referral-stat-icon bg-warning bg-opacity-25 rounded-3 me-3">
                        <i class="fas fa-coins text-warning"></i>
                    </div>
                    <div>
                        <div class="text-muted small">Выплачено (реф.)</div>
                        <div class="fs-4 fw-bold"><?= number_format($stats['total_referral_earned'], 0, '.', ' ') ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-3">
            <div class="card referral-stat-card h-100 border-0 shadow-sm">
                <div class="card-body d-flex align-items-center">
                    <div class="referral-stat-icon bg-info bg-opacity-25 rounded-3 me-3">
                        <i class="fas fa-star text-info"></i>
                    </div>
                    <div>
                        <div class="text-muted small">За отзывы</div>
                        <div class="fs-4 fw-bold"><?= number_format($stats['total_review_rewards'], 0, '.', ' ') ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-cog me-2 text-primary"></i>Базовые настройки</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        Пользователи делятся реферальной ссылкой из бота (например, t.me/бот?start=ref_123).
                    </p>
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label for="referral_reward_amount" class="form-label">Грошей за одну покупку приглашённого (уровень 1)</label>
                            <input type="number" class="form-control" id="referral_reward_amount"
                                   name="referral_reward_amount" min="0" step="1" value="<?= (int) $current ?>"
                                   required>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Сохранить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-star me-2 text-warning"></i>Бонус за отзыв</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small">
                        Пользователи получают гроши за оставленный отзыв на маршрут.
                    </p>
                    <form method="POST" class="row g-3">
                        <div class="col-md-6">
                            <label for="review_reward_amount" class="form-label">Грошей за отзыв</label>
                            <input type="number" class="form-control" id="review_reward_amount"
                                   name="review_reward_amount" min="0" step="1" value="<?= (int) $review_reward ?>">
                        </div>
                        <div class="col-md-6 d-flex align-items-end pb-2">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="review_reward_enabled"
                                       name="review_reward_enabled" <?= $review_enabled ? 'checked' : '' ?>>
                                <label class="form-check-label" for="review_reward_enabled">Бонусы за отзывы включены</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Сохранить настройки отзывов
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Уровни партнёрской программы</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-4">
                        Настройте требования и награды для каждого уровня. Реферал засчитывается при первой покупке приглашённого. Для каждого уровня можно выбрать тип награды: <strong>гроши</strong>, <strong>промокод</strong> (скидка %) или <strong>% от продаж</strong>.
                    </p>
                    <?php
                    $reward_type_labels = [
                        'tokens_per_referral' => ['Гроши за реферала', 'coins'],
                        'discount_code' => ['Промокод скидки', 'percent'],
                        'percent_of_sales' => ['% от продаж', 'percent'],
                        'free_route' => ['Бесплатный квест', 'gift'],
                        'special' => ['Особая награда', 'crown'],
                    ];
                    ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead>
                                <tr>
                                    <th style="width: 60px;">Ур.</th>
                                    <th>Название</th>
                                    <th class="text-center">Друзей</th>
                                    <th>Тип награды</th>
                                    <th class="text-end">Значение</th>
                                    <th style="width: 80px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referral_levels as $lvl): ?>
                                <tr>
                                    <td><span class="badge rounded-pill bg-primary"><?= $lvl['level'] ?></span></td>
                                    <td><span class="me-1"><?= htmlspecialchars($lvl['icon']) ?></span><?= htmlspecialchars($lvl['name']) ?></td>
                                    <td class="text-center"><?= $lvl['required_referrals'] ?></td>
                                    <td>
                                        <?php
                                        $info = $reward_type_labels[$lvl['reward_type']] ?? [$lvl['reward_type'], ''];
                                        echo htmlspecialchars($info[0]);
                                        ?>
                                    </td>
                                    <td class="text-end">
                                        <?php
                                        if ($lvl['reward_value'] !== null && $lvl['reward_value'] != '') {
                                            $rt = $lvl['reward_type'] ?? '';
                                            echo (strpos($rt, 'percent') !== false || $rt === 'discount_code') ? number_format($lvl['reward_value'], 0) . '%' : number_format($lvl['reward_value'], 0) . ' г';
                                        } else {
                                            echo '—';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                data-bs-toggle="modal" data-bs-target="#editLevel<?= $lvl['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 1rem;">
                <div class="card-header py-3">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2 text-info"></i>Как это работает</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 small">
                        <li class="mb-3 d-flex">
                            <span class="badge bg-primary rounded-pill me-2">1</span>
                            <span><strong>Гроши</strong> — фиксированная сумма на баланс за каждого реферала.</span>
                        </li>
                        <li class="mb-3 d-flex">
                            <span class="badge bg-primary rounded-pill me-2">2</span>
                            <span><strong>Промокод</strong> — одноразовый код со скидкой (%).</span>
                        </li>
                        <li class="mb-3 d-flex">
                            <span class="badge bg-primary rounded-pill me-2">3</span>
                            <span><strong>% от продаж</strong> — процент от каждой покупки приглашённого.</span>
                        </li>
                        <li class="mb-3 d-flex">
                            <span class="badge bg-primary rounded-pill me-2">4</span>
                            <span><strong>Бесплатный квест</strong> — один маршрут в подарок.</span>
                        </li>
                        <li class="d-flex">
                            <span class="badge bg-primary rounded-pill me-2">5</span>
                            <span><strong>Особая награда</strong> — статус партнёра, экскурсия и т.д.</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
<?php
$reward_type_options = [
    'tokens_per_referral' => '💰 Гроши за реферала',
    'discount_code' => '🎫 Промокод скидки (%)',
    'percent_of_sales' => '📊 % от продаж',
    'free_route' => '🎁 Бесплатный квест',
    'special' => '👑 Особая награда',
];
foreach ($referral_levels as $lvl):
    $current_type = $lvl['reward_type'] ?? 'tokens_per_referral';
    if (!array_key_exists($current_type, $reward_type_options)) {
        $current_type = 'tokens_per_referral';
    }
?>
<div class="modal fade" id="editLevel<?= $lvl['id'] ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST">
                <input type="hidden" name="update_level" value="1">
                <input type="hidden" name="level_id" value="<?= $lvl['id'] ?>">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать уровень <?= $lvl['level'] ?> — <?= htmlspecialchars($lvl['icon'] . ' ' . $lvl['name']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Название уровня</label>
                        <input type="text" class="form-control" name="level_name" value="<?= htmlspecialchars($lvl['name']) ?>" placeholder="Например: Начало пути">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание награды (для пользователей)</label>
                        <textarea class="form-control" name="level_description" rows="2" placeholder="Что получит пользователь"><?= htmlspecialchars($lvl['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Требуется друзей (с покупкой)</label>
                        <input type="number" class="form-control" name="required_referrals" value="<?= (int) $lvl['required_referrals'] ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тип награды</label>
                        <select class="form-select" name="reward_type" id="reward_type_<?= $lvl['id'] ?>">
                            <?php foreach ($reward_type_options as $val => $label): ?>
                            <option value="<?= htmlspecialchars($val) ?>" <?= $current_type === $val ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="reward_value_<?= $lvl['id'] ?>">Значение награды</label>
                        <div class="input-group">
                            <input type="number" class="form-control" name="reward_value" id="reward_value_<?= $lvl['id'] ?>" value="<?= $lvl['reward_value'] !== null && $lvl['reward_value'] !== '' ? (float) $lvl['reward_value'] : '' ?>" step="0.01" min="0" placeholder="<?= in_array($current_type, ['discount_code', 'percent_of_sales'], true) ? '15' : '20' ?>">
                            <span class="input-group-text reward-value-suffix"><?= in_array($current_type, ['discount_code', 'percent_of_sales'], true) ? '%' : 'грошей' ?></span>
                        </div>
                        <div class="form-text">Гроши — сумма на баланс; промокод и % от продаж — число процентов (1–100).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Сохранить</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>
<style>
.referral-admin-page .page-hero {
    padding: 1.5rem 1.75rem;
    margin-bottom: 1.5rem;
    background: linear-gradient(135deg, var(--primary-color) 0%,
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(102, 126, 234, 0.35);
}
.referral-admin-page .page-hero-title {
    font-size: 1.85rem;
    font-weight: 700;
    color:
    margin: 0 0 0.25rem 0;
    text-shadow: 0 1px 2px rgba(0,0,0,0.15);
}
.referral-admin-page .page-hero-subtitle {
    font-size: 0.95rem;
    color: rgba(255,255,255,0.9) !important;
    margin: 0;
}
.referral-admin-page .referral-stat-card {
    border-radius: 14px;
    overflow: hidden;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    border: 1px solid var(--border-color);
}
.referral-admin-page .referral-stat-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 28px rgba(0,0,0,0.35) !important;
}
.referral-admin-page .referral-stat-card .card-body {
    padding: 1.35rem 1.5rem;
    min-height: 92px;
    display: flex;
    align-items: center;
}
.referral-admin-page .referral-stat-icon {
    width: 56px;
    height: 56px;
    min-width: 56px;
    min-height: 56px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.45rem;
    border-radius: 12px;
}
.referral-admin-page .referral-stat-card .text-muted {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-weight: 600;
}
.referral-admin-page .referral-stat-card .fs-4 { font-size: 1.6rem !important; letter-spacing: -0.02em; }
.referral-admin-page .card {
    border-radius: 14px;
    overflow: hidden;
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
}
.referral-admin-page .card-header {
    border-bottom: 1px solid var(--border-color);
    background: var(--bg-tertiary);
    padding: 1.1rem 1.35rem;
    font-weight: 600;
}
.referral-admin-page .card-header h5 { color: var(--text-primary) !important; }
.referral-admin-page .card-body { padding: 1.35rem 1.5rem; }
.referral-admin-page .card-body .form-label { font-weight: 500; color: var(--text-secondary); }
.referral-admin-page .table-responsive {
    border-radius: 10px;
    overflow: hidden;
    border: 1px solid var(--border-color);
}
.referral-admin-page .table { margin-bottom: 0; }
.referral-admin-page .table thead th {
    padding: 1rem 1.15rem;
    font-weight: 600;
    font-size: 0.82rem;
    text-transform: uppercase;
    letter-spacing: 0.03em;
}
.referral-admin-page .table tbody td { padding: 0.9rem 1.15rem; vertical-align: middle; }
.referral-admin-page .table .badge.rounded-pill { font-size: 0.8rem; padding: 0.4em 0.7em; }
.referral-admin-page .card.sticky-top { border-radius: 14px; }
.referral-admin-page .card.sticky-top .list-unstyled li {
    padding: 0.6rem 0;
    border-bottom: 1px solid var(--border-color);
}
.referral-admin-page .card.sticky-top .list-unstyled li:last-child { border-bottom: none; }
.referral-admin-page .card.sticky-top .badge.rounded-pill { min-width: 1.75rem; }
.referral-admin-page code {
    background: rgba(255,255,255,0.1);
    padding: 0.25em 0.5em;
    border-radius: 6px;
    font-size: 0.88em;
}
.referral-admin-page .alert { border-radius: 12px; border: none; }
.referral-admin-page .btn-primary {
    background: linear-gradient(135deg, var(--primary-color) 0%,
    border: none;
    padding: 0.5rem 1.25rem;
    font-weight: 600;
}
.referral-admin-page .btn-primary:hover {
    background: linear-gradient(135deg,
    transform: translateY(-1px);
}
.referral-admin-page .btn-outline-primary:hover {
    background: var(--primary-color);
    color:
}
</style>
<script>
document.querySelectorAll('[id^="reward_type_"]').forEach(function(sel) {
    sel.addEventListener('change', function() {
        var modal = this.closest('.modal');
        var suffix = modal ? modal.querySelector('.reward-value-suffix') : null;
        if (suffix) {
            var isPercent = this.value === 'discount_code' || this.value === 'percent_of_sales';
            suffix.textContent = isPercent ? '%' : 'грошей';
        }
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>