<?php
$page_title = 'Начислить токены';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$error = '';
$success = '';
$preset_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    $amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    if (!$user_id) {
        $error = 'Выберите пользователя';
    } elseif ($amount <= 0 || $amount > 1000000) {
        $error = 'Укажите корректную сумму (от 1 до 1 000 000)';
    } else {
        $user_check = $pdo->prepare("SELECT id, first_name, username FROM users WHERE id = ?");
        $user_check->execute([$user_id]);
        $target_user = $user_check->fetch();
        if (!$target_user) {
            $error = 'Пользователь не найден';
        } else {
            try {
                $pdo->beginTransaction();
                $balance_stmt = $pdo->prepare("SELECT * FROM token_balances WHERE user_id = ?");
                $balance_stmt->execute([$user_id]);
                $balance = $balance_stmt->fetch();
                if (!$balance) {
                    $pdo->prepare("
                        INSERT INTO token_balances (user_id, balance, total_deposited, total_spent, total_transferred_out, total_transferred_in)
                        VALUES (?, 0, 0, 0, 0, 0)
                    ")->execute([$user_id]);
                    $balance = ['balance' => 0];
                }
                $balance_before = (float)$balance['balance'];
                $balance_after = $balance_before + $amount;
                $pdo->prepare("
                    UPDATE token_balances
                    SET balance = balance + ?,
                        total_deposited = total_deposited + ?
                    WHERE user_id = ?
                ")->execute([$amount, $amount, $user_id]);
                $desc = $description ?: 'Начисление администратором';
                $pdo->prepare("
                    INSERT INTO token_transactions
                    (user_id, type, amount, balance_before, balance_after, description, payment_method, status, created_at)
                    VALUES (?, 'deposit', ?, ?, ?, ?, 'system', 'completed', NOW())
                ")->execute([$user_id, $amount, $balance_before, $balance_after, $desc]);
                $pdo->commit();
                $user_name = $target_user['first_name'] ?: $target_user['username'] ?: "ID: $user_id";
                $success = "Успешно начислено {$amount}₽ пользователю {$user_name}";
                require_once __DIR__ . '/../includes/audit_log.php';
                logAudit('token_balance', $user_id, 'token_add', ['balance_before' => $balance_before], [
                    'amount' => $amount,
                    'balance_after' => $balance_after,
                    'description' => $desc
                ], "Начислено {$amount} токенов пользователю {$user_name}");
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Ошибка: ' . $e->getMessage();
            }
        }
    }
}
$users = $pdo->query("
    SELECT u.id, u.telegram_id, u.username, u.first_name, u.last_name,
           COALESCE(tb.balance, 0) as balance
    FROM users u
    LEFT JOIN token_balances tb ON u.id = tb.user_id
    ORDER BY u.first_name, u.username
    LIMIT 500
")->fetchAll();
$preset_user = null;
if ($preset_user_id) {
    foreach ($users as $u) {
        if ($u['id'] == $preset_user_id) {
            $preset_user = $u;
            break;
        }
    }
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-plus-circle me-2"></i>Начислить токены</h2>
    <a href="list.php" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-2"></i>Назад к банку
    </a>
</div>
<?php if ($error): ?>
<div class="alert alert-danger">
    <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
</div>
<?php endif; ?>
<?php if ($success): ?>
<div class="alert alert-success">
    <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success) ?>
</div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Форма начисления</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Пользователь *</label>
                        <select name="user_id" class="form-select" required id="userSelect">
                            <option value="">Выберите пользователя...</option>
                            <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                    data-balance="<?= $u['balance'] ?>"
                                    <?= $preset_user_id == $u['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['first_name'] . ' ' . ($u['last_name'] ?? '')) ?>
                                <?php if ($u['username']): ?>
                                    (@<?= htmlspecialchars($u['username']) ?>)
                                <?php endif; ?>
                                - Баланс: <?= number_format($u['balance'], 0) ?> ₽
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php if ($preset_user): ?>
                    <div class="alert alert-info mb-3">
                        <strong>Выбран пользователь:</strong><br>
                        <?= htmlspecialchars($preset_user['first_name'] . ' ' . ($preset_user['last_name'] ?? '')) ?>
                        <?php if ($preset_user['username']): ?>
                            (@<?= htmlspecialchars($preset_user['username']) ?>)
                        <?php endif; ?>
                        <br>
                        <strong>Текущий баланс:</strong> <?= number_format($preset_user['balance'], 0) ?> ₽
                    </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Сумма (₽) *</label>
                        <input type="number" name="amount" class="form-control"
                               min="1" max="1000000" step="1" required
                               placeholder="Введите сумму">
                        <div class="form-text">От 1 до 1 000 000 ₽</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Быстрый выбор суммы</label>
                        <div class="btn-group w-100">
                            <button type="button" class="btn btn-outline-secondary quick-amount" data-amount="100">100 ₽</button>
                            <button type="button" class="btn btn-outline-secondary quick-amount" data-amount="500">500 ₽</button>
                            <button type="button" class="btn btn-outline-secondary quick-amount" data-amount="1000">1000 ₽</button>
                            <button type="button" class="btn btn-outline-secondary quick-amount" data-amount="5000">5000 ₽</button>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Описание (опционально)</label>
                        <input type="text" name="description" class="form-control"
                               placeholder="Причина начисления" maxlength="200">
                        <div class="form-text">Например: "Бонус за отзыв", "Компенсация"</div>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="fas fa-plus-circle me-2"></i>Начислить токены
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Информация</h5>
            </div>
            <div class="card-body">
                <h6>Как это работает:</h6>
                <ul>
                    <li>Выберите пользователя из списка</li>
                    <li>Укажите сумму начисления в токенах (1 токен = 1 ₽)</li>
                    <li>Опционально укажите причину начисления</li>
                    <li>Токены будут немедленно зачислены на баланс</li>
                </ul>
                <h6 class="mt-4">Важно:</h6>
                <ul>
                    <li>Действие записывается в журнал аудита</li>
                    <li>Пользователь увидит начисление в истории операций</li>
                    <li>Отменить начисление нельзя (только создать списание)</li>
                </ul>
                <div class="alert alert-warning mt-4 mb-0">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Внимание:</strong> Используйте эту функцию только для легитимных начислений: бонусы, компенсации, акции.
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.querySelectorAll('.quick-amount').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelector('input[name="amount"]').value = this.dataset.amount;
    });
});
document.getElementById('userSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    if (option.dataset.balance !== undefined) {
    }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>