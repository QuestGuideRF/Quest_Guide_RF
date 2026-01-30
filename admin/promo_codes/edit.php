<?php
require_once __DIR__ . '/../includes/audit_log.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$promo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $pdo->prepare("SELECT * FROM promo_codes WHERE id = ?");
$stmt->execute([$promo_id]);
$old_promo = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$old_promo) {
    header('Location: /admin/promo_codes/list.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $code = trim($_POST['code'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] ?? 'percentage';
        $discount_value = $_POST['discount_value'] ?? null;
        $route_id = $_POST['route_id'] ?? null;
        $max_uses = $_POST['max_uses'] ?? null;
        $valid_from = $_POST['valid_from'] ?? null;
        $valid_until = $_POST['valid_until'] ?? null;
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        if ($code !== $old_promo['code']) {
            $stmt = $pdo->prepare("SELECT id FROM promo_codes WHERE code = ? AND id != ?");
            $stmt->execute([$code, $promo_id]);
            if ($stmt->fetch()) {
                $error = 'Промокод с таким кодом уже существует';
            }
        }
        if (!isset($error)) {
            if ($discount_type === 'free_route') {
                $discount_value = 0;
            }
            if ($discount_type === 'free_route' && !$route_id) {
                $route_id = $_POST['free_route_id'] ?? null;
            }
            $stmt = $pdo->prepare("
                UPDATE promo_codes
                SET code = ?, description = ?, discount_type = ?, discount_value = ?,
                    route_id = ?, max_uses = ?, valid_from = ?, valid_until = ?,
                    is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                $code,
                $description ?: null,
                $discount_type,
                $discount_value ?: null,
                $route_id ?: null,
                $max_uses ?: null,
                $valid_from ?: null,
                $valid_until ?: null,
                $is_active,
                $promo_id
            ]);
            logAudit('promo_code', $promo_id, 'update', $old_promo, $_POST, 'Промокод обновлен');
            $_SESSION['success'] = 'Промокод успешно обновлен';
            header("Location: /admin/promo_codes/list.php");
            exit;
        }
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$promo = $old_promo;
$page_title = 'Редактирование промокода';
require_once __DIR__ . '/../includes/header.php';
if (!$promo) {
    header('Location: /admin/promo_codes/list.php');
    exit;
}
$routes_stmt = $pdo->query("SELECT id, name FROM routes WHERE is_active = 1 ORDER BY name");
$routes = $routes_stmt->fetchAll();
$uses_stmt = $pdo->prepare("
    SELECT COUNT(*) as total_uses,
           COUNT(DISTINCT user_id) as unique_users,
           MIN(used_at) as first_use,
           MAX(used_at) as last_use
    FROM promo_code_uses
    WHERE promo_code_id = ?
");
$uses_stmt->execute([$promo_id]);
$usage_stats = $uses_stmt->fetch();
?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4><i class="fas fa-edit me-2"></i>Редактирование промокода</h4>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Код промокода *</label>
                        <input type="text" name="code" class="form-control" required
                               value="<?= htmlspecialchars($promo['code']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание</label>
                        <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($promo['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Тип скидки *</label>
                        <select name="discount_type" class="form-select" id="discount_type" required>
                            <option value="percentage" <?= $promo['discount_type'] === 'percentage' ? 'selected' : '' ?>>Процентная скидка</option>
                            <option value="fixed" <?= $promo['discount_type'] === 'fixed' ? 'selected' : '' ?>>Фиксированная сумма</option>
                            <option value="free_route" <?= $promo['discount_type'] === 'free_route' ? 'selected' : '' ?>>Бесплатный маршрут</option>
                        </select>
                    </div>
                    <div class="mb-3" id="discount_value_group">
                        <label class="form-label" id="discount_value_label">Значение скидки *</label>
                        <div class="input-group">
                            <input type="number" name="discount_value" class="form-control"
                                   step="0.01" min="0"
                                   value="<?= htmlspecialchars($promo['discount_value'] ?? '') ?>"
                                   id="discount_value_input">
                            <span class="input-group-text" id="discount_value_suffix">%</span>
                        </div>
                    </div>
                    <div class="mb-3" id="route_group" style="display: none;">
                        <label class="form-label">Маршрут *</label>
                        <select name="free_route_id" class="form-select" id="free_route_select">
                            <option value="">Выберите маршрут</option>
                            <?php foreach ($routes as $route): ?>
                                <option value="<?= $route['id'] ?>" <?= ($promo['route_id'] ?? '') == $route['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($route['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Максимальное количество использований</label>
                        <input type="number" name="max_uses" class="form-control" min="1"
                               value="<?= htmlspecialchars($promo['max_uses'] ?? '') ?>"
                               placeholder="Оставьте пустым для неограниченного использования">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Действителен с</label>
                            <input type="datetime-local" name="valid_from" class="form-control"
                                   value="<?= $promo['valid_from'] ? date('Y-m-d\TH:i', strtotime($promo['valid_from'])) : '' ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Действителен до</label>
                            <input type="datetime-local" name="valid_until" class="form-control"
                                   value="<?= $promo['valid_until'] ? date('Y-m-d\TH:i', strtotime($promo['valid_until'])) : '' ?>">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active"
                                   <?= $promo['is_active'] ? 'checked' : '' ?>>
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
                            <i class="fas fa-save me-2"></i>Сохранить изменения
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5>Статистика использования</h5>
            </div>
            <div class="card-body">
                <p><strong>Всего использований:</strong> <?= $usage_stats['total_uses'] ?? 0 ?></p>
                <p><strong>Уникальных пользователей:</strong> <?= $usage_stats['unique_users'] ?? 0 ?></p>
                <?php if ($usage_stats['first_use']): ?>
                    <p><strong>Первое использование:</strong><br>
                    <small><?= date('d.m.Y H:i', strtotime($usage_stats['first_use'])) ?></small></p>
                    <p><strong>Последнее использование:</strong><br>
                    <small><?= date('d.m.Y H:i', strtotime($usage_stats['last_use'])) ?></small></p>
                <?php else: ?>
                    <p class="text-muted">Промокод еще не использовался</p>
                <?php endif; ?>
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
            valueLabel.textContent = 'Сумма скидки (₽) *';
            valueSuffix.textContent = '₽';
            valueInput.max = null;
        }
    }
});
document.getElementById('discount_type').dispatchEvent(new Event('change'));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>