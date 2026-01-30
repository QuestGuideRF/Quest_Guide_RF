<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$hint_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$hint_id) {
    header('Location: /admin/hints/list.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $map_path = $_POST['existing_map_path'];
        $has_map = $_POST['existing_has_map'];
        $image_path = $_POST['existing_image_path'] ?? null;
        if (isset($_FILES['map_image']) && $_FILES['map_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/hints/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if ($map_path) {
                $old_file = __DIR__ . '/../..' . $map_path;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $ext = pathinfo($_FILES['map_image']['name'], PATHINFO_EXTENSION);
            $filename = 'point_' . $_POST['point_id'] . '_level_' . $_POST['level'] . '_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['map_image']['tmp_name'], $upload_path)) {
                $map_path = '/uploads/hints/' . $filename;
                $has_map = 1;
            }
        }
        if (isset($_FILES['hint_image']) && $_FILES['hint_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/hints/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            if ($image_path) {
                $old_file = __DIR__ . '/../..' . $image_path;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $ext = pathinfo($_FILES['hint_image']['name'], PATHINFO_EXTENSION);
            $filename = 'hint_' . $_POST['point_id'] . '_level_' . $_POST['level'] . '_img_' . time() . '.' . $ext;
            $upload_path = $upload_dir . $filename;
            if (move_uploaded_file($_FILES['hint_image']['tmp_name'], $upload_path)) {
                $image_path = '/uploads/hints/' . $filename;
            }
        }
        if (isset($_POST['delete_hint_image']) && $_POST['delete_hint_image'] == '1') {
            if ($image_path) {
                $old_file = __DIR__ . '/../..' . $image_path;
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $image_path = null;
        }
        $stmt = $pdo->prepare("
            UPDATE hints
            SET level = ?,
                text = ?,
                text_en = ?,
                has_map = ?,
                map_image_path = ?,
                image_path = ?,
                `order` = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['level'],
            $_POST['text'],
            $_POST['text_en'] ?? null,
            $has_map,
            $map_path,
            $image_path,
            $_POST['order'],
            $hint_id
        ]);
        $_SESSION['success'] = 'Подсказка успешно обновлена';
        header('Location: /admin/points/edit.php?id=' . $_POST['point_id']);
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$stmt = $pdo->prepare("
    SELECT h.*, p.name as point_name, p.id as point_id, r.name as route_name
    FROM hints h
    JOIN points p ON h.point_id = p.id
    JOIN routes r ON p.route_id = r.id
    WHERE h.id = ?
");
$stmt->execute([$hint_id]);
$hint = $stmt->fetch();
if (!$hint) {
    header('Location: /admin/hints/list.php');
    exit;
}
$page_title = 'Редактирование подсказки';
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
                <h5><i class="fas fa-edit me-2"></i>Редактирование подсказки</h5>
                <small class="text-muted">
                    Точка: <?= htmlspecialchars($hint['point_name']) ?>
                    (<?= htmlspecialchars($hint['route_name']) ?>)
                </small>
            </div>
            <div class="card-body">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="point_id" value="<?= $hint['point_id'] ?>">
                    <input type="hidden" name="existing_map_path" value="<?= htmlspecialchars($hint['map_image_path']) ?>">
                    <input type="hidden" name="existing_has_map" value="<?= $hint['has_map'] ?>">
                    <input type="hidden" name="existing_image_path" value="<?= htmlspecialchars($hint['image_path'] ?? '') ?>">
                    <div class="mb-3">
                        <label class="form-label">Уровень детализации *</label>
                        <select name="level" class="form-select" required>
                            <option value="1" <?= $hint['level'] == 1 ? 'selected' : '' ?>>
                                1 - Легкая (общее направление)
                            </option>
                            <option value="2" <?= $hint['level'] == 2 ? 'selected' : '' ?>>
                                2 - Средняя (более конкретно)
                            </option>
                            <option value="3" <?= $hint['level'] == 3 ? 'selected' : '' ?>>
                                3 - Детальная (почти точное место)
                            </option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Текст подсказки (Русский) *</label>
                        <div class="input-group">
                            <textarea name="text" id="hint_text_ru" class="form-control" rows="5" required><?= htmlspecialchars($hint['text']) ?></textarea>
                            <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateField('hint_text_ru', 'hint_text_en')" title="Перевести на английский" style="margin-top: 0;">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Текст подсказки (English)</label>
                        <textarea name="text_en" id="hint_text_en" class="form-control" rows="5"><?= htmlspecialchars($hint['text_en'] ?? '') ?></textarea>
                    </div>
                    <?php if (!empty($hint['image_path'])): ?>
                        <div class="mb-3">
                            <label class="form-label">Текущее фото подсказки</label>
                            <div class="position-relative d-inline-block">
                                <img src="<?= htmlspecialchars($hint['image_path']) ?>"
                                     class="img-fluid" style="max-height: 200px; border-radius: 8px;">
                                <div class="form-check mt-2">
                                    <input type="checkbox" name="delete_hint_image" value="1" class="form-check-input" id="deleteHintImage">
                                    <label class="form-check-label text-danger" for="deleteHintImage">Удалить фото</label>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <i class="fas fa-image me-1"></i>
                            <?= !empty($hint['image_path']) ? 'Заменить фото подсказки' : 'Загрузить фото подсказки' ?>
                        </label>
                        <input type="file" name="hint_image" class="form-control" accept="image/*"
                               onchange="previewImage(this, 'hintImagePreview')">
                        <small class="text-muted">Фото будет отображаться вместе с текстом подсказки в боте</small>
                        <div class="mt-2">
                            <img id="hintImagePreview" style="display: none; max-width: 100%; height: auto; border-radius: 8px;">
                        </div>
                    </div>
                    <?php if ($hint['has_map'] && $hint['map_image_path']): ?>
                        <div class="mb-3">
                            <label class="form-label">Текущая карта</label>
                            <div>
                                <img src="<?= htmlspecialchars($hint['map_image_path']) ?>"
                                     class="img-fluid" style="max-height: 200px; border-radius: 8px;">
                            </div>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="form-label">
                            <?= $hint['has_map'] ? 'Заменить карту' : 'Загрузить карту' ?>
                        </label>
                        <input type="file" name="map_image" class="form-control" accept="image/*"
                               onchange="previewImage(this, 'mapPreview')">
                        <div class="mt-2">
                            <img id="mapPreview" style="display: none; max-width: 100%; height: auto; border-radius: 8px;">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Порядок</label>
                        <input type="number" name="order" class="form-control"
                               value="<?= $hint['order'] ?>" min="0">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
                        </button>
                        <a href="/admin/points/edit.php?id=<?= $hint['point_id'] ?>"
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