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
$point_id = isset($_GET['point_id']) ? (int)$_GET['point_id'] : null;
$point = null;
if ($point_id) {
    $stmt = $pdo->prepare("
        SELECT p.*, r.name as route_name
        FROM points p
        JOIN routes r ON p.route_id = r.id
        WHERE p.id = ?
    ");
    $stmt->execute([$point_id]);
    $point = $stmt->fetch();
}
$existing_levels = [];
if ($point_id) {
    $stmt = $pdo->prepare("SELECT level FROM hints WHERE point_id = ?");
    $stmt->execute([$point_id]);
    $existing_levels = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $created_count = 0;
        $levels = [1, 2, 3];
        foreach ($levels as $level) {
            $text_key = "text_$level";
            $text_en_key = "text_en_$level";
            if (empty($_POST[$text_key])) {
                continue;
            }
            if (in_array($level, $existing_levels)) {
                continue;
            }
            $stmt = $pdo->prepare("
                INSERT INTO hints (point_id, level, text, text_en, has_map, map_image_path, image_path, `order`, created_at)
                VALUES (?, ?, ?, ?, 0, NULL, NULL, 0, NOW())
            ");
            $stmt->execute([
                $_POST['point_id'],
                $level,
                $_POST[$text_key],
                $_POST[$text_en_key] ?? null
            ]);
            $created_count++;
        }
        if ($created_count > 0) {
            $_SESSION['success'] = "Создано подсказок: $created_count";
        } else {
            $_SESSION['warning'] = 'Не было создано ни одной подсказки (возможно, все уже существуют или поля пустые)';
        }
        header('Location: /admin/points/edit.php?id=' . $_POST['point_id']);
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при создании: ' . $e->getMessage();
    }
}
$points = $pdo->query("
    SELECT p.id, p.name, r.name as route_name
    FROM points p
    JOIN routes r ON p.route_id = r.id
    ORDER BY r.name, p.order
")->fetchAll();
$page_title = 'Создание подсказок';
require_once __DIR__ . '/../includes/header.php';
$level_names = [
    1 => ['name' => 'Легкая', 'icon' => '💡', 'desc' => 'общее направление', 'example' => 'Ищите в районе Красной площади'],
    2 => ['name' => 'Средняя', 'icon' => '🔦', 'desc' => 'более конкретно', 'example' => 'Рядом с метро Площадь Революции, со стороны ГУМа'],
    3 => ['name' => 'Детальная', 'icon' => '🎯', 'desc' => 'почти точное место', 'example' => 'Здание с красными стенами, вход с правой стороны'],
];
?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-lg-10 mx-auto">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h5><i class="fas fa-plus me-2"></i>Создание подсказок (все 3 уровня)</h5>
                    <?php if ($point): ?>
                        <small class="text-muted">
                            Точка: <?= htmlspecialchars($point['name']) ?>
                            (<?= htmlspecialchars($point['route_name']) ?>)
                        </small>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline-primary" onclick="translateAll()" id="translateAllBtn">
                    <i class="fas fa-language me-1"></i> Перевести все на EN
                </button>
            </div>
            <div class="card-body">
                <form method="POST" id="hintsForm">
                    <div class="mb-4">
                        <label class="form-label">Точка *</label>
                        <select name="point_id" id="point_id_select" class="form-select" required <?= $point_id ? '' : '' ?>>
                            <?php foreach ($points as $p): ?>
                                <option value="<?= $p['id'] ?>" <?= $point_id == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name']) ?> - <?= htmlspecialchars($p['route_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php foreach ($level_names as $level => $info): ?>
                        <?php $exists = in_array($level, $existing_levels); ?>
                        <div class="card mb-3 <?= $exists ? 'border-success' : '' ?>">
                            <div class="card-header <?= $exists ? 'bg-success text-white' : 'bg-light' ?>">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>
                                        <?= $info['icon'] ?> <strong><?= $info['name'] ?></strong> — <?= $info['desc'] ?>
                                    </span>
                                    <?php if ($exists): ?>
                                        <span class="badge bg-white text-success">✓ Уже создана</span>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                                onclick="translateField('text_<?= $level ?>', 'text_en_<?= $level ?>')"
                                                title="Перевести на EN">
                                            <i class="fas fa-language"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body <?= $exists ? 'bg-light' : '' ?>">
                                <?php if ($exists): ?>
                                    <div class="text-muted text-center py-2">
                                        <i class="fas fa-check-circle text-success me-1"></i>
                                        Подсказка этого уровня уже существует.
                                        <a href="/admin/hints/list.php?point_id=<?= $point_id ?>">Редактировать</a>
                                    </div>
                                <?php else: ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="form-label">Текст (Русский)</label>
                                            <textarea name="text_<?= $level ?>" id="text_<?= $level ?>"
                                                      class="form-control hint-text-ru" rows="4"
                                                      placeholder="<?= htmlspecialchars($info['example']) ?>"></textarea>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Text (English)</label>
                                            <textarea name="text_en_<?= $level ?>" id="text_en_<?= $level ?>"
                                                      class="form-control hint-text-en" rows="4"
                                                      placeholder="English translation..."></textarea>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save me-2"></i>Сохранить подсказки
                        </button>
                        <a href="/admin/hints/list.php<?= $point_id ? "?point_id=$point_id" : '' ?>"
                           class="btn btn-secondary btn-lg">
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
    if (!fromField || !toField) return Promise.resolve(false);
    const text = fromField.value.trim();
    if (!text) {
        return Promise.resolve(false);
    }
    return fetch('/admin/api/translate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({text: text, from: 'ru', to: 'en'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toField.value = data.translated;
            return true;
        }
        return false;
    })
    .catch(() => false);
}
async function translateAll() {
    const btn = document.getElementById('translateAllBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Перевожу...';
    const levels = [1, 2, 3];
    let translated = 0;
    for (const level of levels) {
        const fromField = document.getElementById('text_' + level);
        const toField = document.getElementById('text_en_' + level);
        if (fromField && toField && fromField.value.trim() && !toField.value.trim()) {
            const success = await translateField('text_' + level, 'text_en_' + level);
            if (success) translated++;
            await new Promise(r => setTimeout(r, 300));
        }
    }
    btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Готово (' + translated + ')';
    setTimeout(() => {
        btn.innerHTML = '<i class="fas fa-language me-1"></i> Перевести все на EN';
        btn.disabled = false;
    }, 2000);
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>