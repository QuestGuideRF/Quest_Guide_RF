<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
require_once __DIR__ . '/../includes/audit_log.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$city_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = null;
$stmt = $pdo->prepare("SELECT * FROM cities WHERE id = ?");
$stmt->execute([$city_id]);
$old_city = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$old_city) {
    header('Location: /admin/cities/list.php');
    exit;
}
if (isModerator()) {
    $creator_id = isset($old_city['creator_id']) ? (int)$old_city['creator_id'] : null;
    if ($creator_id !== (int)$_SESSION['admin_id']) {
        header('Location: /admin/cities/list.php?error=access_denied');
        exit;
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE cities
            SET name = ?, name_en = ?, description = ?, description_en = ?, is_active = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['name_en'] ?? null,
            $_POST['description'] ?? null,
            $_POST['description_en'] ?? null,
            isset($_POST['is_active']) ? 1 : 0,
            $city_id
        ]);
        logAudit('city', $city_id, 'update', $old_city, $_POST, 'Город обновлен');
        $_SESSION['success'] = 'Город успешно обновлен';
        header("Location: /admin/cities/list.php");
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$city = $old_city;
$page_title = 'Редактирование города';
require_once __DIR__ . '/../includes/header.php';
$stmt = $pdo->prepare("
    SELECT
        COUNT(DISTINCT r.id) as routes_count,
        COUNT(DISTINCT p.id) as points_count,
        COUNT(DISTINCT up.id) as completions_count,
        COUNT(DISTINCT u.id) as users_count
    FROM cities c
    LEFT JOIN routes r ON c.id = r.city_id
    LEFT JOIN points p ON r.id = p.route_id
    LEFT JOIN user_progress up ON r.id = up.route_id AND up.status = 'COMPLETED'
    LEFT JOIN users u ON up.user_id = u.id
    WHERE c.id = ?
");
$stmt->execute([$city_id]);
$stats = $stmt->fetch();
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
                <h5 class="card-title">Редактировать город</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Название (Русский) *</label>
                        <div class="input-group">
                            <input type="text" name="name" id="city_name_ru" class="form-control" value="<?= htmlspecialchars($city['name']) ?>" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="translateField('city_name_ru', 'city_name_en')" title="Перевести на английский">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название (English)</label>
                        <input type="text" name="name_en" id="city_name_en" class="form-control" value="<?= htmlspecialchars($city['name_en'] ?? '') ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (Русский)</label>
                        <div class="input-group">
                            <textarea name="description" id="city_description_ru" class="form-control" rows="4"><?= htmlspecialchars($city['description'] ?? '') ?></textarea>
                            <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('city_description_ru', 'city_description_en')" title="Перевести на английский" style="margin-top: 0;">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Описание (English)</label>
                        <textarea name="description_en" id="city_description_en" class="form-control" rows="4"><?= htmlspecialchars($city['description_en'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= $city['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                Активен
                            </label>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
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
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title">Статистика</h6>
                <div class="row text-center">
                    <div class="col-6 mb-3">
                        <div class="stat-card">
                            <h4><?= $stats['routes_count'] ?></h4>
                            <small class="text-muted">Маршрутов</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card">
                            <h4><?= $stats['points_count'] ?></h4>
                            <small class="text-muted">Точек</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card">
                            <h4><?= $stats['completions_count'] ?></h4>
                            <small class="text-muted">Прохождений</small>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="stat-card">
                            <h4><?= $stats['users_count'] ?></h4>
                            <small class="text-muted">Пользователей</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h6 class="card-title">Быстрые действия</h6>
                <div class="d-grid gap-2">
                    <a href="/admin/routes/list.php?city_id=<?= $city_id ?>" class="btn btn-sm btn-info">
                        <i class="fas fa-route me-2"></i>Маршруты города
                    </a>
                    <a href="/admin/routes/create.php?city_id=<?= $city_id ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus me-2"></i>Добавить маршрут
                    </a>
                </div>
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