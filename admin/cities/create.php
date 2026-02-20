<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/audit_log.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
<<<<<<< HEAD
        $creator_id = (isModerator() && isset($_SESSION['admin_id'])) ? $_SESSION['admin_id'] : null;
        $stmt = $pdo->prepare("
            INSERT INTO cities (name, name_en, description, description_en, is_active, creator_id, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
=======
        $stmt = $pdo->prepare("
            INSERT INTO cities (name, name_en, description, description_en, is_active, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['name_en'] ?? null,
            $_POST['description'] ?? null,
            $_POST['description_en'] ?? null,
<<<<<<< HEAD
            isset($_POST['is_active']) ? 1 : 0,
            $creator_id
=======
            isset($_POST['is_active']) ? 1 : 0
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        ]);
        $city_id = $pdo->lastInsertId();
        logAudit('city', $city_id, 'create', null, $_POST, 'Город создан');
        $_SESSION['success'] = 'Город успешно создан';
        header("Location: /admin/cities/edit.php?id=$city_id");
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при создании: ' . $e->getMessage();
    }
}
$page_title = 'Создание города';
require_once __DIR__ . '/../includes/header.php';
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
            <div class="card-body">
                <h5 class="card-title">Создать новый город</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Название (Русский) *</label>
                        <div class="input-group">
                            <input type="text" name="name" id="city_name_ru" class="form-control" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="translateField('city_name_ru', 'city_name_en')" title="Перевести на английский">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название (English)</label>
                        <input type="text" name="name_en" id="city_name_en" class="form-control">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (Русский)</label>
                        <div class="input-group">
                            <textarea name="description" id="city_description_ru" class="form-control" rows="4"></textarea>
                            <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('city_description_ru', 'city_description_en')" title="Перевести на английский" style="margin-top: 0;">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (English)</label>
                        <textarea name="description_en" id="city_description_en" class="form-control" rows="4"></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" checked>
                            <label class="form-check-label" for="is_active">
                                Активен
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Создать
                        </button>
                        <a href="/admin/cities/list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Подсказки</h6>
                <ul class="small text-muted">
                    <li>Название города будет отображаться в списке маршрутов</li>
                    <li>Описание поможет пользователям понять особенности города</li>
                    <li>Неактивные города не будут отображаться в боте</li>
                </ul>
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