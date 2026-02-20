<?php
$page_title = 'Настройки заработка';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $settings = [
        'review_reward_amount'    => $_POST['review_reward_amount'] ?? '10',
        'review_reward_enabled'   => isset($_POST['review_reward_enabled']) ? '1' : '0',
        'survey_reward_amount'    => $_POST['survey_reward_amount'] ?? '5',
        'survey_reward_enabled'   => isset($_POST['survey_reward_enabled']) ? '1' : '0',
        'quiz_reward_per_correct' => $_POST['quiz_reward_per_correct'] ?? '2',
    ];
    foreach ($settings as $key => $value) {
        $stmt = $pdo->prepare("INSERT INTO platform_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
        $stmt->execute([$key, $value, $value]);
    }
    if (!empty($_POST['route_limits'])) {
        foreach ($_POST['route_limits'] as $route_id => $limit) {
            $limit_val = $limit === '' ? null : floatval($limit);
            $pdo->prepare("UPDATE routes SET max_earnings = ? WHERE id = ?")->execute([$limit_val, intval($route_id)]);
        }
    }
    header("Location: /admin/earnings/index.php?saved=1");
    exit;
}
function getSetting($pdo, $key, $default = '') {
    $stmt = $pdo->prepare("SELECT value FROM platform_settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['value'] : $default;
}
$review_reward_amount = getSetting($pdo, 'review_reward_amount', '10');
$review_reward_enabled = getSetting($pdo, 'review_reward_enabled', '1');
$survey_reward_amount = getSetting($pdo, 'survey_reward_amount', '5');
$survey_reward_enabled = getSetting($pdo, 'survey_reward_enabled', '1');
$quiz_reward_per_correct = getSetting($pdo, 'quiz_reward_per_correct', '2');
$routes = $pdo->query("SELECT id, name, price, max_earnings FROM routes ORDER BY name")->fetchAll();
$referral_levels = $pdo->query("SELECT * FROM referral_levels ORDER BY required_referrals")->fetchAll();
$promo_count = $pdo->query("SELECT COUNT(*) as cnt FROM promo_codes WHERE is_active = 1")->fetch()['cnt'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-coins"></i> Настройки заработка</h2>
</div>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check"></i> Настройки сохранены <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<form method="POST">
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-star"></i> Отзыв</h5></div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="review_reward_enabled" id="rev_en" <?= $review_reward_enabled === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="rev_en">Награда включена</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Награда за отзыв (гроши)</label>
                        <input type="number" name="review_reward_amount" class="form-control" value="<?= htmlspecialchars($review_reward_amount) ?>" min="0" step="0.01">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Опрос</h5></div>
                <div class="card-body">
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="survey_reward_enabled" id="srv_en" <?= $survey_reward_enabled === '1' ? 'checked' : '' ?>>
                        <label class="form-check-label" for="srv_en">Награда включена</label>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Награда за опрос (гроши)</label>
                        <input type="number" name="survey_reward_amount" class="form-control" value="<?= htmlspecialchars($survey_reward_amount) ?>" min="0" step="0.01">
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-header"><h5 class="mb-0"><i class="fas fa-question-circle"></i> Квиз</h5></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Награда за правильный ответ (гроши)</label>
                        <input type="number" name="quiz_reward_per_correct" class="form-control" value="<?= htmlspecialchars($quiz_reward_per_correct) ?>" min="0" step="0.01">
                    </div>
                    <a href="/admin/quiz/index.php" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i> Управление вопросами</a>
                </div>
            </div>
        </div>
    </div>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0"><i class="fas fa-route"></i> Лимит заработка по маршрутам</h5></div>
        <div class="card-body">
            <p class="text-muted">Максимальная сумма, которую пользователь может заработать за одно прохождение квеста (бонусы + квиз + опрос + отзыв). Пусто = без лимита.</p>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead><tr><th>Маршрут</th><th>Цена</th><th>Лимит заработка (гроши)</th></tr></thead>
                    <tbody>
                        <?php foreach ($routes as $r): ?>
                            <tr>
                                <td><?= htmlspecialchars($r['name']) ?></td>
                                <td><?= $r['price'] ?> грошей</td>
                                <td>
                                    <input type="number" name="route_limits[<?= $r['id'] ?>]" class="form-control form-control-sm" style="width: 150px"
                                           value="<?= $r['max_earnings'] !== null ? htmlspecialchars($r['max_earnings']) : '' ?>"
                                           min="0" step="0.01" placeholder="Без лимита">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <button type="submit" class="btn btn-success btn-lg"><i class="fas fa-save"></i> Сохранить все настройки</button>
</form>
<div class="card mt-4">
    <div class="card-header"><h5 class="mb-0"><i class="fas fa-link"></i> Связанные разделы</h5></div>
    <div class="card-body">
        <div class="d-flex gap-3 flex-wrap">
            <a href="/admin/referral/index.php" class="btn btn-outline-primary"><i class="fas fa-users"></i> Реферальная программа (<?= count($referral_levels) ?> уровней)</a>
            <a href="/admin/promo_codes/list.php" class="btn btn-outline-primary"><i class="fas fa-ticket-alt"></i> Промокоды (<?= $promo_count ?> активных)</a>
            <a href="/admin/quiz/index.php" class="btn btn-outline-primary"><i class="fas fa-question-circle"></i> Квизы</a>
            <a href="/admin/reviews/list.php" class="btn btn-outline-primary"><i class="fas fa-star"></i> Отзывы</a>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>