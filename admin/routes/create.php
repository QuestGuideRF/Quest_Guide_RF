<?php
$page_title = 'Создание маршрута';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/audit_log.php';
require_once __DIR__ . '/../../includes/db.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
$pdo = getDB()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO routes (name, name_en, description, description_en, city_id, price, route_type, is_active,
                               difficulty, estimated_duration, max_hints_per_route, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['name_en'] ?? null,
            $_POST['description'],
            $_POST['description_en'] ?? null,
            $_POST['city_id'],
            $_POST['price'],
            $_POST['route_type'],
            isset($_POST['is_active']) ? 1 : 0,
            $_POST['difficulty'],
            $_POST['estimated_duration'],
            $_POST['max_hints_per_route']
        ]);
        $route_id = $pdo->lastInsertId();
        logAudit('route', $route_id, 'create', null, $_POST, 'Маршрут создан');
        $_SESSION['success'] = 'Маршрут успешно создан';
        header("Location: /admin/routes/edit.php?id=$route_id");
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при создании: ' . $e->getMessage();
    }
}
$cities = $pdo->query("SELECT * FROM cities ORDER BY name")->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-plus me-2"></i>Создание нового маршрута</h5>
            </div>
            <div class="card-body">
                <form method="POST" id="createRouteForm" data-autosave>
                    <div class="mb-3">
                        <label class="form-label">Название маршрута (Русский) *</label>
                        <div class="input-group">
                            <input type="text" name="name" id="route_name_ru" class="form-control"
                                   placeholder="Например: Историческая Москва" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="translateField('route_name_ru', 'route_name_en')" title="Перевести на английский">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название маршрута (English)</label>
                        <input type="text" name="name_en" id="route_name_en" class="form-control"
                               placeholder="For example: Historical Moscow">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (Русский) *</label>
                        <div class="input-group">
                            <textarea name="description" id="route_description_ru" class="form-control" rows="5"
                                      placeholder="Подробное описание маршрута..." required></textarea>
                            <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('route_description_ru', 'route_description_en')" title="Перевести на английский" style="margin-top: 0;">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (English)</label>
                        <textarea name="description_en" id="route_description_en" class="form-control" rows="5"
                                  placeholder="Detailed route description..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Город *</label>
                            <select name="city_id" class="form-select" required>
                                <option value="">Выберите город</option>
                                <?php foreach ($cities as $city): ?>
                                    <option value="<?= $city['id'] ?>">
                                        <?= htmlspecialchars($city['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Цена (₽) *</label>
                            <input type="number" name="price" class="form-control"
                                   value="0" min="0" required>
                            <small class="text-muted">Укажите 0 для бесплатного маршрута</small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Тип маршрута *</label>
                            <select name="route_type" class="form-select" required>
                                <option value="WALKING">🚶 Пеший</option>
                                <option value="CYCLING">🚴 Велосипедный</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Сложность *</label>
                            <select name="difficulty" class="form-select" required>
                                <option value="1">⭐ Легкий</option>
                                <option value="2" selected>⭐⭐ Средний</option>
                                <option value="3">⭐⭐⭐ Сложный</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Ориентировочное время (минуты) *</label>
                            <input type="number" name="estimated_duration" class="form-control"
                                   value="60" min="10" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Максимум подсказок *</label>
                            <input type="number" name="max_hints_per_route" class="form-control"
                                   value="3" min="0" max="10" required>
                            <small class="text-muted">Количество подсказок на весь маршрут</small>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active"
                                   id="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Маршрут активен (сразу доступен пользователям)
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Создать маршрут
                        </button>
                        <a href="/admin/routes/list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-body">
                <h6><i class="fas fa-info-circle me-2"></i>Что дальше?</h6>
                <p class="text-muted mb-0">
                    После создания маршрута вы сможете добавить точки и настроить подсказки.
                </p>
            </div>
        </div>
    </div>
</div>
<script>
function translateField(fromId, toId) {
    const fromField = document.getElementById(fromId);
    const toField = document.getElementById(toId);
    const text = fromField.value.trim();
    if (!text) {
        alert('Сначала заполните поле на русском языке');
        return;
    }
    const btn = event.target.closest('button');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch('/admin/api/translate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({text: text, from: 'ru', to: 'en'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toField.value = data.translated;
            btn.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                btn.innerHTML = '<i class="fas fa-language"></i>';
                btn.disabled = false;
            }, 2000);
        } else {
            alert('Ошибка перевода: ' + (data.error || 'Неизвестная ошибка'));
            btn.innerHTML = '<i class="fas fa-language"></i>';
            btn.disabled = false;
        }
    })
    .catch(err => {
        alert('Ошибка: ' + err.message);
        btn.innerHTML = '<i class="fas fa-language"></i>';
        btn.disabled = false;
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>