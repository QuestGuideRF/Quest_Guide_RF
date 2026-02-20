<?php
define('APP_INIT', true);
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit_log.php';
$pdo = getDB()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = trim($_POST['code'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $discount_type = $_POST['discount_type'] ?? 'percentage';
    $discount_value = $_POST['discount_value'] ?? null;
    $route_id = $_POST['route_id'] ?? null;
    $max_uses = $_POST['max_uses'] ?? null;
    $valid_from = $_POST['valid_from'] ?? null;
    $valid_until = $_POST['valid_until'] ?? null;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    if (empty($code)) {
        $error = 'Код промокода обязателен';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ?");
        $stmt->execute([$code]);
        if ($stmt->fetch()) {
            $error = 'Промокод с таким кодом уже существует';
        } else {
            if ($discount_type === 'free_route') {
                $discount_value = 0;
            }
            if ($discount_type === 'free_route' && !$route_id) {
                $route_id = $_POST['free_route_id'] ?? null;
            }
            if (!$route_id && isset($_POST['route_id']) && $_POST['route_id']) {
                $route_id = $_POST['route_id'];
            }
            $admin = getCurrentAdmin();
            $stmt = $pdo->prepare("
                INSERT INTO promo_codes (code, description, discount_type, discount_value, route_id, max_uses, valid_from, valid_until, is_active, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $normalized_route_id = null;
            if ($route_id && $route_id !== '' && $route_id !== '0') {
                $normalized_route_id = (int)$route_id;
            }
            $stmt->execute([
                $code,
                $description ?: null,
                $discount_type,
                $discount_value ?: null,
                $normalized_route_id,
                $max_uses ?: null,
                $valid_from ?: null,
                $valid_until ?: null,
                $is_active,
                $admin['id']
            ]);
            $promo_id = $pdo->lastInsertId();
            logAudit('promo_code', $promo_id, 'create', null, $_POST, 'Промокод создан');
            $_SESSION['success'] = 'Промокод успешно создан';
            header('Location: /admin/promo_codes/list.php?success=created');
            exit;
        }
    }
}
$page_title = 'Создание промокода';
require_once __DIR__ . '/../includes/header.php';
$routes_stmt = $pdo->query("SELECT id, name FROM routes WHERE is_active = 1 ORDER BY name");
$routes = $routes_stmt->fetchAll();
?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-plus me-2"></i>Создание промокода</h4>
            </div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Код промокода *</label>
                        <input type="text" name="code" class="form-control" required
                               value="<?= htmlspecialchars($_POST['code'] ?? '') ?>"
                               placeholder="Например: SUMMER2024">
                        <small class="form-text text-muted">Уникальный код, который будут вводить пользователи</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Описание промокода"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тип скидки *</label>
                        <select name="discount_type" class="form-select" id="discount_type" required>
                            <option value="percentage" <?= ($_POST['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Процентная скидка</option>
                            <option value="fixed" <?= ($_POST['discount_type'] ?? '') === 'fixed' ? 'selected' : '' ?>>Фиксированная сумма</option>
                            <option value="free_route" <?= ($_POST['discount_type'] ?? '') === 'free_route' ? 'selected' : '' ?>>Бесплатный маршрут</option>
                        </select>
                    </div>
                    <div class="mb-3" id="discount_value_group">
                        <label class="form-label" id="discount_value_label">Значение скидки *</label>
                        <div class="input-group">
                            <input type="number" name="discount_value" class="form-control"
                                   step="0.01" min="0"
                                   value="<?= htmlspecialchars($_POST['discount_value'] ?? '') ?>"
                                   id="discount_value_input">
                            <span class="input-group-text" id="discount_value_suffix">%</span>
                        </div>
                    </div>
                    <div class="mb-3" id="route_group" style="display: none;">
                        <label class="form-label">Маршрут *</label>
                        <select name="free_route_id" class="form-select" id="free_route_select">
                            <option value="">Выберите маршрут</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= $route['id'] ?>" <?= ($_POST['free_route_id'] ?? '') == $route['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($route['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3" id="route_specific_group">
                        <label class="form-label">Применить к конкретному маршруту (опционально)</label>
                        <select name="route_id" class="form-select" id="route_specific_select">
                            <option value="">Для всех маршрутов</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= $route['id'] ?>" <?= ($_POST['route_id'] ?? '') == $route['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($route['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">Если не выбран, промокод будет действовать для всех маршрутов</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Максимальное количество использований</label>
                        <input type="number" name="max_uses" class="form-control" min="1"
                               value="<?= htmlspecialchars($_POST['max_uses'] ?? '') ?>"
                               placeholder="Оставьте пустым для неограниченного использования">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Действителен с</label>
                            <input type="datetime-local" name="valid_from" class="form-control"
                                   value="<?= htmlspecialchars($_POST['valid_from'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Действителен до</label>
                            <input type="datetime-local" name="valid_until" class="form-control"
                                   value="<?= htmlspecialchars($_POST['valid_until'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                                   <?= (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Активен
                            </label>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="/admin/promo_codes/list.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Назад
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Создать промокод
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('discount_type').addEventListener('change', function() {
    const type = this.value;
    const valueGroup = document.getElementById('discount_value_group');
    const routeGroup = document.getElementById('route_group');
    const valueInput = document.getElementById('discount_value_input');
    const valueLabel = document.getElementById('discount_value_label');
    const valueSuffix = document.getElementById('discount_value_suffix');
    const routeSelect = document.getElementById('free_route_select');
    if (type === 'free_route') {
        valueGroup.style.display = 'none';
        routeGroup.style.display = 'block';
        routeSelect.required = true;
        valueInput.required = false;
    } else {
        valueGroup.style.display = 'block';
        routeGroup.style.display = 'none';
        routeSelect.required = false;
        valueInput.required = true;
        if (type === 'percentage') {
            valueLabel.textContent = 'Процент скидки *';
            valueSuffix.textContent = '%';
            valueInput.max = 100;
        } else {
<<<<<<< HEAD
            valueLabel.textContent = 'Сумма скидки (гроши) *';
            valueSuffix.textContent = ' грошей';
=======
            valueLabel.textContent = 'Сумма скидки (₽) *';
            valueSuffix.textContent = '₽';
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            valueInput.max = null;
        }
    }
});
document.getElementById('discount_type').dispatchEvent(new Event('change'));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>