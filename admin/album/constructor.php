<?php
$page_title = 'Конструктор альбома';
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
try {
    $pdo->query("SELECT 1 FROM album_templates LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
        $sqlWithFk = "CREATE TABLE IF NOT EXISTS album_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            route_id INT UNSIGNED NOT NULL,
            template_json TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY route_id (route_id),
            CONSTRAINT album_templates_route_fk FOREIGN KEY (route_id) REFERENCES routes(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        $sqlNoFk = "CREATE TABLE IF NOT EXISTS album_templates (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            route_id INT UNSIGNED NOT NULL,
            template_json TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY route_id (route_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        try {
            $pdo->exec($sqlWithFk);
        } catch (PDOException $fkE) {
            $pdo->exec($sqlNoFk);
        }
    } else {
        throw $e;
    }
}
$route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : 0;
$default_template = [
    'page_orientation' => 'landscape',
    'photo_position' => 'right',
    'show_point_name' => true,
    'show_fact_text' => true,
    'show_photo_date' => true,
    'show_address' => false,
    'custom_caption' => '',
    'font_size' => 14,
    'show_page_numbers' => true,
    'cover_title' => '',
    'cover_subtitle' => '',
    'cover_background_path' => '',
    'show_certificate_on_first_page' => false,
];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_route_id = intval($_POST['route_id'] ?? 0);
    if ($post_route_id) {
        $tpl = [
            'page_orientation' => $_POST['page_orientation'] ?? 'landscape',
            'photo_position' => $_POST['photo_position'] ?? 'right',
            'show_point_name' => isset($_POST['show_point_name']),
            'show_fact_text' => isset($_POST['show_fact_text']),
            'show_photo_date' => isset($_POST['show_photo_date']),
            'show_address' => isset($_POST['show_address']),
            'custom_caption' => $_POST['custom_caption'] ?? '',
            'font_size' => intval($_POST['font_size'] ?? 14),
            'show_page_numbers' => isset($_POST['show_page_numbers']),
            'cover_title' => $_POST['cover_title'] ?? '',
            'cover_subtitle' => $_POST['cover_subtitle'] ?? '',
            'cover_background_path' => trim($_POST['cover_background_path'] ?? ''),
            'show_certificate_on_first_page' => isset($_POST['show_certificate_on_first_page']),
        ];
        $json = json_encode($tpl, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("
            INSERT INTO album_templates (route_id, template_json)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE template_json = ?, updated_at = NOW()
        ");
        $stmt->execute([$post_route_id, $json, $json]);
        header("Location: /admin/album/constructor.php?route_id=$post_route_id&saved=1");
        exit;
    }
}
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$template = $default_template;
if ($route_id) {
    $stmt = $pdo->prepare("SELECT template_json FROM album_templates WHERE route_id = ?");
    $stmt->execute([$route_id]);
    $row = $stmt->fetch();
    if ($row && $row['template_json']) {
        $parsed = json_decode($row['template_json'], true);
        if (is_array($parsed)) {
            $template = array_merge($default_template, $parsed);
        }
    }
}
$preview_progress_id = 0;
if ($route_id) {
    $stmt = $pdo->prepare("SELECT id FROM user_progress WHERE route_id = ? AND status = 'COMPLETED' ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute([$route_id]);
    $pr = $stmt->fetch();
    if ($pr) $preview_progress_id = (int)$pr['id'];
}
require_once __DIR__ . '/../includes/header.php';
?>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check-circle me-2"></i>Шаблон альбома сохранён!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-book-open me-2"></i>Конструктор альбома</h2>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label">Маршрут</label>
                <select name="route_id" class="form-select">
                    <option value="">— Выберите маршрут —</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $r['id'] == $route_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search me-1"></i>Выбрать</button>
        </form>
    </div>
</div>
<?php if ($route_id): ?>
<form method="POST" id="templateForm">
    <input type="hidden" name="route_id" value="<?= $route_id ?>">
    <div class="row">
        <div class="col-lg-7 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-eye me-2"></i>Превью макета страницы</h5>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 350px;">
                    <div id="pagePreview" class="album-preview-page">
                        <div id="previewLeft" class="album-preview-half album-preview-text">
                            <div class="preview-label">Точка 1</div>
                            <div id="prevName" class="preview-field preview-name">Название точки</div>
                            <div class="preview-divider"></div>
                            <div id="prevAddress" class="preview-field preview-muted">Адрес: ул. Примерная, 1</div>
                            <div id="prevDate" class="preview-field preview-muted">Фото: 08.02.2026, 19:03</div>
                            <div id="prevFact" class="preview-field preview-fact">Интересный факт о точке...</div>
                            <div id="prevCaption" class="preview-field preview-muted preview-caption"></div>
                        </div>
                        <div id="previewRight" class="album-preview-half album-preview-photo">
                            <div class="photo-placeholder">
                                <i class="fas fa-image" style="font-size: 40px; color: #ccc;"></i>
                                <div style="color: #bbb; margin-top: 8px; font-size: 12px;">Фото</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Настройки макета</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted mb-3">Отображение полей</h6>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="show_point_name" name="show_point_name"
                               <?= $template['show_point_name'] ? 'checked' : '' ?> onchange="updatePreview()">
                        <label class="form-check-label" for="show_point_name">Название точки</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="show_fact_text" name="show_fact_text"
                               <?= $template['show_fact_text'] ? 'checked' : '' ?> onchange="updatePreview()">
                        <label class="form-check-label" for="show_fact_text">Интересный факт</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="show_photo_date" name="show_photo_date"
                               <?= $template['show_photo_date'] ? 'checked' : '' ?> onchange="updatePreview()">
                        <label class="form-check-label" for="show_photo_date">Дата и время фото</label>
                    </div>
                    <div class="form-check form-switch mb-2">
                        <input class="form-check-input" type="checkbox" id="show_address" name="show_address"
                               <?= $template['show_address'] ? 'checked' : '' ?> onchange="updatePreview()">
                        <label class="form-check-label" for="show_address">Адрес точки</label>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="show_page_numbers" name="show_page_numbers"
                               <?= $template['show_page_numbers'] ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_page_numbers">Нумерация страниц</label>
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3">Расположение</h6>
                    <div class="mb-3">
                        <label class="form-label">Ориентация страницы</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="page_orientation" id="orient_landscape"
                                       value="landscape" <?= $template['page_orientation'] === 'landscape' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="orient_landscape">Альбомная</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="page_orientation" id="orient_portrait"
                                       value="portrait" <?= $template['page_orientation'] === 'portrait' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="orient_portrait">Портретная</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Позиция фото</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="photo_position" id="photo_right"
                                       value="right" <?= $template['photo_position'] === 'right' ? 'checked' : '' ?> onchange="updatePreview()">
                                <label class="form-check-label" for="photo_right">Справа</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="photo_position" id="photo_left"
                                       value="left" <?= $template['photo_position'] === 'left' ? 'checked' : '' ?> onchange="updatePreview()">
                                <label class="form-check-label" for="photo_left">Слева</label>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="font_size">Размер шрифта</label>
                        <input type="number" class="form-control" id="font_size" name="font_size"
                               value="<?= (int)$template['font_size'] ?>" min="10" max="24" style="width: 100px;">
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3">Тексты</h6>
                    <div class="mb-3">
                        <label class="form-label" for="custom_caption">Подпись к каждому фото</label>
                        <textarea class="form-control" id="custom_caption" name="custom_caption" rows="2"
                                  placeholder="Текст, который будет под каждым фото..." onchange="updatePreview()"><?= htmlspecialchars($template['custom_caption']) ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cover_title">Заголовок обложки</label>
                        <input type="text" class="form-control" id="cover_title" name="cover_title"
                               value="<?= htmlspecialchars($template['cover_title']) ?>" placeholder="Фотоальбом">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="cover_subtitle">Подзаголовок обложки</label>
                        <input type="text" class="form-control" id="cover_subtitle" name="cover_subtitle"
                               value="<?= htmlspecialchars($template['cover_subtitle']) ?>" placeholder="Город / описание">
                    </div>
                    <hr>
                    <h6 class="text-muted mb-3">Обложка и сертификат</h6>
                    <div class="mb-3">
                        <label class="form-label">Фон обложки</label>
                        <input type="hidden" name="cover_background_path" id="cover_background_path" value="<?= htmlspecialchars($template['cover_background_path'] ?? '') ?>">
                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <input type="file" class="form-control form-control-sm" id="background_file" accept="image/jpeg,image/png,image/gif,image/webp" style="max-width: 220px;">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="uploadBackgroundBtn">
                                <i class="fas fa-upload me-1"></i>Загрузить
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="removeBackgroundBtn" <?= empty($template['cover_background_path'] ?? '') ? 'style="display:none"' : '' ?>>
                                <i class="fas fa-trash me-1"></i>Удалить
                            </button>
                        </div>
                        <div id="backgroundPreview" class="mt-2" <?= empty($template['cover_background_path'] ?? '') ? 'style="display:none"' : '' ?>>
                            <img id="backgroundPreviewImg" src="<?= htmlspecialchars($template['cover_background_path'] ?? '') ?>" alt="" style="max-height: 80px; border-radius: 6px; border: 1px solid var(--border-color);">
                        </div>
                        <div class="form-text">JPG, PNG, GIF, WebP. Макс. 5 МБ. Рекомендуется 297×210 мм (альбомная) или 210×297 мм (портретная).</div>
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="show_certificate_on_first_page" name="show_certificate_on_first_page"
                               <?= !empty($template['show_certificate_on_first_page']) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="show_certificate_on_first_page">Сертификат на первой странице</label>
                        <div class="form-text">Если включено — сертификат будет страницей 1, обложка — страницей 2.</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="d-flex gap-3 mb-4">
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save me-2"></i>Сохранить шаблон
        </button>
        <?php if ($preview_progress_id): ?>
            <a href="/api/generate_album.php?progress_id=<?= $preview_progress_id ?>&preview=1"
               class="btn btn-outline-success btn-lg" target="_blank">
                <i class="fas fa-file-pdf me-2"></i>Предпросмотр PDF
            </a>
        <?php else: ?>
            <button type="button" class="btn btn-outline-secondary btn-lg" disabled
                    title="Нет завершённых прохождений для этого маршрута">
                <i class="fas fa-file-pdf me-2"></i>Предпросмотр PDF (нет данных)
            </button>
        <?php endif; ?>
    </div>
</form>
<?php endif; ?>
<style>
.album-preview-page {
    display: flex;
    width: 520px;
    height: 340px;
    border: 2px solid var(--border-color, rgba(255,255,255,0.15));
    border-radius: 6px;
    overflow: hidden;
    background:
    box-shadow: 0 4px 20px rgba(0,0,0,0.15);
}
.album-preview-half {
    width: 50%;
    height: 100%;
    padding: 18px;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
    justify-content: center;
}
.album-preview-text {
    background:
    color:
}
.preview-label { font-size: 9px; color:
.preview-name { font-weight: bold; font-size: 14px; margin-bottom: 6px; color:
.preview-muted { font-size: 10px; color:
.preview-fact { font-size: 11px; color:
.preview-muted.preview-caption { margin-top: 8px; }
.album-preview-photo {
    background:
    border-left: 1px solid
    display: flex;
    align-items: center;
    justify-content: center;
}
.album-preview-photo.photo-left {
    border-left: none;
    border-right: 1px solid
}
.photo-placeholder {
    text-align: center;
    padding: 30px;
    border: 2px dashed
    border-radius: 4px;
    width: 85%;
    aspect-ratio: 3/4;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}
.preview-divider {
    width: 40%;
    height: 1px;
    background:
    margin: 6px 0;
}
.preview-field.hidden {
    display: none;
}
</style>
<script>
function updatePreview() {
    var showNameEl = document.getElementById('show_point_name');
    if (!showNameEl) return;
    var showName = showNameEl.checked;
    var showFact = document.getElementById('show_fact_text').checked;
    var showDate = document.getElementById('show_photo_date').checked;
    var showAddr = document.getElementById('show_address').checked;
    var photoRightEl = document.getElementById('photo_right');
    var photoRight = photoRightEl ? photoRightEl.checked : true;
    var captionEl = document.getElementById('custom_caption');
    var caption = captionEl ? captionEl.value : '';
    document.getElementById('prevName').classList.toggle('hidden', !showName);
    document.getElementById('prevFact').classList.toggle('hidden', !showFact);
    document.getElementById('prevDate').classList.toggle('hidden', !showDate);
    document.getElementById('prevAddress').classList.toggle('hidden', !showAddr);
    var prevCaption = document.getElementById('prevCaption');
    if (caption.trim()) {
        prevCaption.textContent = caption;
        prevCaption.classList.remove('hidden');
    } else {
        prevCaption.classList.add('hidden');
    }
    var page = document.getElementById('pagePreview');
    var left = document.getElementById('previewLeft');
    var right = document.getElementById('previewRight');
    if (photoRight) {
        left.classList.add('album-preview-text');
        left.classList.remove('album-preview-photo');
        right.classList.add('album-preview-photo');
        right.classList.remove('album-preview-text', 'photo-left');
        right.style.borderLeft = '1px solid #e8e2d8';
        right.style.borderRight = 'none';
        page.style.flexDirection = 'row';
    } else {
        page.style.flexDirection = 'row-reverse';
        right.classList.add('photo-left');
        right.style.borderLeft = 'none';
        right.style.borderRight = '1px solid #e8e2d8';
    }
}
if (document.getElementById('show_point_name')) {
    updatePreview();
}
var uploadBackgroundBtn = document.getElementById('uploadBackgroundBtn');
if (uploadBackgroundBtn) {
    uploadBackgroundBtn.addEventListener('click', function() {
    var fileInput = document.getElementById('background_file');
    if (!fileInput.files.length) {
        alert('Выберите файл');
        return;
    }
    var fd = new FormData();
    fd.append('route_id', <?= (int)$route_id ?>);
    fd.append('background_file', fileInput.files[0]);
    this.disabled = true;
    fetch('/admin/api/upload_album_background.php', { method: 'POST', body: fd })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('cover_background_path').value = data.file_path;
                document.getElementById('backgroundPreviewImg').src = data.file_path;
                document.getElementById('backgroundPreview').style.display = 'block';
                document.getElementById('removeBackgroundBtn').style.display = 'inline-block';
                fileInput.value = '';
            } else {
                alert(data.error || 'Ошибка загрузки');
            }
        })
        .catch(function() { alert('Ошибка загрузки'); })
        .finally(function() { document.getElementById('uploadBackgroundBtn').disabled = false; });
    });
}
var removeBackgroundBtn = document.getElementById('removeBackgroundBtn');
if (removeBackgroundBtn) {
    removeBackgroundBtn.addEventListener('click', function() {
    document.getElementById('cover_background_path').value = '';
    document.getElementById('backgroundPreviewImg').src = '';
    document.getElementById('backgroundPreview').style.display = 'none';
    this.style.display = 'none';
    document.getElementById('background_file').value = '';
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>