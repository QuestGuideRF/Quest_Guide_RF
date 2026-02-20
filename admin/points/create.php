<?php
$page_title = 'Создание точки';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
$pdo = getDB()->getConnection();
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$route = null;
if ($route_id) {
    $stmt = $pdo->prepare("SELECT * FROM routes WHERE id = ?");
    $stmt->execute([$route_id]);
    $route = $stmt->fetch();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("SELECT COALESCE(MAX(`order`), 0) + 1 as next_order FROM points WHERE route_id = ?");
        $stmt->execute([$_POST['route_id'] ?? 0]);
        $next_order = $stmt->fetch()['next_order'];
        $stmt = $pdo->prepare("
            INSERT INTO points (route_id, name, name_en, audio_text, audio_text_en,
                               fact_text, fact_text_en,
                               latitude, longitude, `order`, task_type, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_POST['route_id'] ?? null,
            $_POST['name'] ?? '',
            !empty($_POST['name_en']) ? $_POST['name_en'] : null,
            !empty($_POST['audio_text']) ? $_POST['audio_text'] : null,
            !empty($_POST['audio_text_en']) ? $_POST['audio_text_en'] : null,
            !empty($_POST['fact_text']) ? $_POST['fact_text'] : null,
            !empty($_POST['fact_text_en']) ? $_POST['fact_text_en'] : null,
            $_POST['latitude'] ?? null,
            $_POST['longitude'] ?? null,
            $next_order,
            'photo'
        ]);
        $point_id = $pdo->lastInsertId();
        $_SESSION['success'] = 'Точка успешно создана';
        header("Location: /admin/points/edit.php?id=$point_id");
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при создании: ' . $e->getMessage();
    }
}
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
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
                <h5><i class="fas fa-plus me-2"></i>Создание новой точки</h5>
                <?php if ($route): ?>
                    <small class="text-muted">Маршрут: <?= htmlspecialchars($route['name']) ?></small>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <form method="POST">
                    <?php if ($route_id): ?>
                        <input type="hidden" name="route_id" value="<?= $route_id ?>">
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">Маршрут *</label>
                        <select name="route_id" class="form-select" required <?= $route_id ? 'readonly' : '' ?>>
                            <?php foreach ($routes as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $route_id == $r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название точки (Русский) *</label>
                        <div class="input-group">
                            <input type="text" name="name" id="point_name_ru" class="form-control"
                                   placeholder="Например: Красная площадь" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="translateField('point_name_ru', 'point_name_en')" title="Перевести на английский">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название точки (English)</label>
                        <input type="text" name="name_en" id="point_name_en" class="form-control"
                               placeholder="For example: Red Square">
                    </div>
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-1 me-2"></i>Этап 1: Заметки и как дойти</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Заметки / Текст для аудио (Русский)</label>
                                <div class="input-group">
                                    <textarea name="audio_text" id="point_audio_text_ru" class="form-control" rows="3"
                                              placeholder="Дополнительная информация или текст для аудиогида..." maxlength="3500"></textarea>
                                    <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('point_audio_text_ru', 'point_audio_text_en')" title="Перевести на английский" style="margin-top: 0;">
                                        <i class="fas fa-language"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Эта информация будет отправлена первой вместе с инструкцией "Как добраться"</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Заметки / Текст для аудио (English)</label>
                                <textarea name="audio_text_en" id="point_audio_text_en" class="form-control" rows="3"
                                          placeholder="Additional information or text for audio guide..." maxlength="3500"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card border-success mb-4">
                        <div class="card-header bg-success text-white">
                            <h6 class="mb-0"><i class="fas fa-2 me-2"></i>Этап 2: Факт</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Факт (Русский)</label>
                                <div class="input-group">
                                    <textarea name="fact_text" id="point_fact_text_ru" class="form-control" rows="3"
                                              placeholder="Интересный факт о месте..." maxlength="3500"></textarea>
                                    <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('point_fact_text_ru', 'point_fact_text_en')" title="Перевести на английский" style="margin-top: 0;">
                                        <i class="fas fa-language"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Факт будет отправлен после нажатия кнопки "Я на месте" вместе с аудиогидами</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Факт (English)</label>
                                <textarea name="fact_text_en" id="point_fact_text_en" class="form-control" rows="3"
                                          placeholder="Interesting fact about the place..." maxlength="3500"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card border-info mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Координаты</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Широта (Latitude) *</label>
                                    <input type="text" name="latitude" class="form-control" inputmode="decimal"
                                           pattern="-?[0-9]*[.,]?[0-9]+" placeholder="55.753215" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Долгота (Longitude) *</label>
                                    <input type="text" name="longitude" class="form-control" inputmode="decimal"
                                           pattern="-?[0-9]*[.,]?[0-9]+" placeholder="37.622504" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Как получить координаты:</strong><br>
                        1. Откройте Google Maps<br>
                        2. Найдите нужное место<br>
                        3. Кликните правой кнопкой мыши<br>
                        4. Скопируйте координаты (первое число - широта, второе - долгота)
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Создать точку
                        </button>
                        <a href="/admin/points/list.php<?= $route_id ? "?route_id=$route_id" : '' ?>"
                           class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
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