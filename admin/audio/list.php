<?php
$page_title = 'Управление аудиогидом';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$audio_enabled = isset($_GET['audio_enabled']) ? $_GET['audio_enabled'] : '';
$where = [];
$params = [];
if ($route_id) {
    $where[] = "p.route_id = ?";
    $params[] = $route_id;
}
if ($audio_enabled === '1') {
    $where[] = "p.audio_enabled = 1";
} elseif ($audio_enabled === '0') {
    $where[] = "p.audio_enabled = 0";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT p.*, r.name as route_name, r.id as route_id,
           r.name_en as route_name_en,
           (SELECT t.task_text FROM tasks t WHERE t.point_id = p.id ORDER BY t.`order` ASC LIMIT 1) AS first_task_text
    FROM points p
    JOIN routes r ON p.route_id = r.id
    $whereClause
    ORDER BY r.name, p.order
");
$stmt->execute($params);
$points = $stmt->fetchAll();
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-headphones me-2"></i>Управление аудиогидом</h2>
    <div class="d-flex gap-2">
        <button class="btn btn-success" onclick="bulkEnableAudio()">
            <i class="fas fa-check me-2"></i>Включить выбранные
        </button>
        <button class="btn btn-warning" onclick="bulkDisableAudio()">
            <i class="fas fa-times me-2"></i>Выключить выбранные
        </button>
    </div>
</div>
<!-- Фильтры -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label">Маршрут</label>
                <select name="route_id" class="form-select">
                    <option value="">Все маршруты</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $route_id == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">Статус аудио</label>
                <select name="audio_enabled" class="form-select">
                    <option value="">Все</option>
                    <option value="1" <?= $audio_enabled === '1' ? 'selected' : '' ?>>Включен</option>
                    <option value="0" <?= $audio_enabled === '0' ? 'selected' : '' ?>>Выключен</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Найти
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Таблица точек -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Точка</th>
                        <th>Маршрут</th>
                        <th>Аудио текст</th>
                        <th>Аудио файлы</th>
                        <th>Статус</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($points as $point): ?>
                        <tr>
                            <td><input type="checkbox" class="point-checkbox" value="<?= $point['id'] ?>"></td>
                            <td>
                                <strong><?= htmlspecialchars($point['name']) ?></strong>
                                <br><small class="text-muted">Порядок: <?= $point['order'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($point['route_name']) ?></td>
                            <td>
                                <small>
                                    <?= htmlspecialchars(mb_substr($point['audio_text'] ?? ($point['first_task_text'] ?? ''), 0, 100)) ?>
                                    <?= mb_strlen($point['audio_text'] ?? ($point['first_task_text'] ?? '')) > 100 ? '...' : '' ?>
                                </small>
                            </td>
                            <td>
                                <div class="d-flex gap-1 flex-column align-items-start">
                                    <?php if (!empty($point['audio_file_path_ru'])): ?>
                                        <div class="d-flex align-items-center gap-2 w-100">
                                            <button type="button" class="btn btn-sm btn-success d-flex align-items-center"
                                                    onclick="playAudio(<?= $point['id'] ?>, 'ru', '<?= htmlspecialchars($point['audio_file_path_ru'], ENT_QUOTES) ?>')"
                                                    title="Прослушать (RU)">
                                                <i class="fas fa-play me-1"></i>RU
                                            </button>
                                            <span class="badge bg-success" title="Файл загружен">
                                                <i class="fas fa-check-circle"></i> MP3
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2 w-100">
                                            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center"
                                                    onclick="showUploadModal(<?= $point['id'] ?>, 'ru')"
                                                    title="Загрузить (RU)">
                                                <i class="fas fa-upload me-1"></i>RU
                                            </button>
                                            <span class="badge bg-secondary" title="Файл отсутствует">
                                                <i class="fas fa-times-circle"></i> Нет
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($point['audio_file_path_en'])): ?>
                                        <div class="d-flex align-items-center gap-2 w-100">
                                            <button type="button" class="btn btn-sm btn-success d-flex align-items-center"
                                                    onclick="playAudio(<?= $point['id'] ?>, 'en', '<?= htmlspecialchars($point['audio_file_path_en'], ENT_QUOTES) ?>')"
                                                    title="Прослушать (EN)">
                                                <i class="fas fa-play me-1"></i>EN
                                            </button>
                                            <span class="badge bg-success" title="Файл загружен">
                                                <i class="fas fa-check-circle"></i> MP3
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center gap-2 w-100">
                                            <button type="button" class="btn btn-sm btn-outline-secondary d-flex align-items-center"
                                                    onclick="showUploadModal(<?= $point['id'] ?>, 'en')"
                                                    title="Загрузить (EN)">
                                                <i class="fas fa-upload me-1"></i>EN
                                            </button>
                                            <span class="badge bg-secondary" title="Файл отсутствует">
                                                <i class="fas fa-times-circle"></i> Нет
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <?php if ($point['audio_enabled']): ?>
                                    <span class="badge bg-success">Включен</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Выключен</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a href="/admin/points/edit.php?id=<?= $point['id'] ?>" class="btn btn-sm btn-primary" title="Редактировать">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" class="btn btn-sm btn-<?= $point['audio_enabled'] ? 'warning' : 'success' ?>"
                                            onclick="toggleAudio(<?= $point['id'] ?>, <?= $point['audio_enabled'] ? 0 : 1 ?>)"
                                            title="<?= $point['audio_enabled'] ? 'Выключить' : 'Включить' ?>">
                                        <i class="fas fa-<?= $point['audio_enabled'] ? 'volume-up' : 'volume-mute' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<!-- Модальное окно для прослушивания и загрузки аудио -->
<div class="modal fade" id="audioModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Аудиогид</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="audioPlayerSection" style="display: none;">
                    <h6>Прослушать аудио</h6>
                    <audio id="audioPlayer" controls class="w-100 mb-3" style="max-width: 100%;">
                        Ваш браузер не поддерживает аудио.
                    </audio>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteAudio()">
                            <i class="fas fa-trash me-1"></i>Удалить файл
                        </button>
                    </div>
                </div>
                <div id="audioUploadSection">
                    <h6>Загрузить MP3 файл (до 20 МБ)</h6>
                    <form id="audioUploadForm" enctype="multipart/form-data">
                        <input type="hidden" id="uploadPointId" name="point_id">
                        <input type="hidden" id="uploadLanguage" name="language">
                        <div class="mb-3">
                            <label for="audioFileInput" class="form-label">Выберите файл</label>
                            <input type="file" class="form-control" id="audioFileInput" name="audio_file"
                                   accept=".mp3,.m4a,.wav,.ogg" required>
                            <div class="form-text">Максимальный размер: 20 МБ. Форматы: MP3, M4A, WAV, OGG</div>
                        </div>
                        <div class="progress mb-3" style="display: none;" id="uploadProgress">
                            <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Загрузить
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.point-checkbox').forEach(cb => cb.checked = this.checked);
});
let currentPointId = null;
let currentLanguage = null;
let currentAudioPath = null;
function playAudio(pointId, language, audioPath) {
    currentPointId = pointId;
    currentLanguage = language;
    currentAudioPath = audioPath;
    const modal = new bootstrap.Modal(document.getElementById('audioModal'));
    document.getElementById('audioPlayerSection').style.display = 'block';
    document.getElementById('audioUploadSection').style.display = 'none';
    const audioPlayer = document.getElementById('audioPlayer');
    audioPlayer.src = audioPath;
    audioPlayer.load();
    modal.show();
}
function showUploadModal(pointId, language) {
    currentPointId = pointId;
    currentLanguage = language;
    currentAudioPath = null;
    const modal = new bootstrap.Modal(document.getElementById('audioModal'));
    document.getElementById('audioPlayerSection').style.display = 'none';
    document.getElementById('audioUploadSection').style.display = 'block';
    document.getElementById('uploadPointId').value = pointId;
    document.getElementById('uploadLanguage').value = language;
    document.getElementById('audioFileInput').value = '';
    document.getElementById('uploadProgress').style.display = 'none';
    modal.show();
}
function deleteAudio() {
    if (!confirm('Вы уверены, что хотите удалить этот аудиофайл?')) {
        return;
    }
    const fieldName = currentLanguage === 'ru' ? 'audio_file_path_ru' : 'audio_file_path_en';
    fetch('/admin/api/bulk_points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_status',
            ids: [currentPointId],
            data: {
                [fieldName]: ''
            }
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('audioModal')).hide();
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    })
    .catch(error => {
        alert('Ошибка: ' + error.message);
    });
}
document.getElementById('audioUploadForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('point_id', document.getElementById('uploadPointId').value);
    formData.append('language', document.getElementById('uploadLanguage').value);
    formData.append('audio_file', document.getElementById('audioFileInput').files[0]);
    const progressBar = document.getElementById('uploadProgress');
    const progressBarInner = progressBar.querySelector('.progress-bar');
    progressBar.style.display = 'block';
    progressBarInner.style.width = '0%';
    const xhr = new XMLHttpRequest();
    xhr.upload.addEventListener('progress', function(e) {
        if (e.lengthComputable) {
            const percentComplete = (e.loaded / e.total) * 100;
            progressBarInner.style.width = percentComplete + '%';
        }
    });
    xhr.addEventListener('load', function() {
        if (xhr.status === 200) {
            try {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    bootstrap.Modal.getInstance(document.getElementById('audioModal')).hide();
                    location.reload();
                } else {
                    alert('Ошибка: ' + (response.error || 'Неизвестная ошибка'));
                    progressBar.style.display = 'none';
                }
            } catch (e) {
                console.error('Parse error:', e, xhr.responseText);
                alert('Ошибка обработки ответа сервера');
                progressBar.style.display = 'none';
            }
        } else if (xhr.status === 401 || xhr.status === 403) {
            alert('Ошибка доступа. Пожалуйста, войдите в систему заново.');
            progressBar.style.display = 'none';
        } else {
            try {
                const response = JSON.parse(xhr.responseText);
                alert('Ошибка: ' + (response.error || 'Ошибка загрузки файла (код ' + xhr.status + ')'));
            } catch (e) {
                alert('Ошибка загрузки файла (код ' + xhr.status + ')');
            }
            progressBar.style.display = 'none';
        }
    });
    xhr.addEventListener('error', function() {
        alert('Ошибка соединения');
        progressBar.style.display = 'none';
    });
    xhr.open('POST', '/admin/api/upload_audio.php');
    xhr.send(formData);
});
document.getElementById('audioModal').addEventListener('hidden.bs.modal', function() {
    const audioPlayer = document.getElementById('audioPlayer');
    audioPlayer.pause();
    audioPlayer.src = '';
    document.getElementById('audioUploadForm').reset();
    document.getElementById('uploadProgress').style.display = 'none';
});
function toggleAudio(pointId, enabled) {
    fetch('/admin/api/bulk_points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_status',
            ids: [pointId],
            data: {audio_enabled: enabled}
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function bulkEnableAudio() {
    const selected = Array.from(document.querySelectorAll('.point-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Выберите точки');
        return;
    }
    fetch('/admin/api/bulk_points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_status',
            ids: selected,
            data: {audio_enabled: true}
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
function bulkDisableAudio() {
    const selected = Array.from(document.querySelectorAll('.point-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('Выберите точки');
        return;
    }
    fetch('/admin/api/bulk_points.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'update_status',
            ids: selected,
            data: {audio_enabled: false}
        })
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Ошибка: ' + (data.error || 'Неизвестная ошибка'));
        }
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>