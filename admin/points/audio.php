<?php
$page_title = 'Управление аудио для точки';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$point_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$point_id) {
    header('Location: /admin/points/list.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_audio'])) {
    try {
        $_SESSION['success'] = 'Аудио успешно сгенерировано';
        header("Location: /admin/points/edit.php?id=$point_id");
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при генерации: ' . $e->getMessage();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio_file_ru'])) {
    try {
        $upload_dir = __DIR__ . '/../../uploads/audio/points/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = pathinfo($_FILES['audio_file_ru']['name'], PATHINFO_EXTENSION);
        $filename = 'point_' . $point_id . '_ru_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['audio_file_ru']['tmp_name'], $upload_path)) {
            $audio_path = '/uploads/audio/points/' . $filename;
            $stmt = $pdo->prepare("
                UPDATE points
                SET audio_file_path_ru = ?,
                    audio_enabled = 1
                WHERE id = ?
            ");
            $stmt->execute([$audio_path, $point_id]);
            $_SESSION['success'] = 'Аудиофайл (русский) успешно загружен';
            header("Location: /admin/points/audio.php?id=$point_id");
            exit;
        }
    } catch (Exception $e) {
        $error = 'Ошибка при загрузке: ' . $e->getMessage();
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['audio_file_en'])) {
    try {
        $upload_dir = __DIR__ . '/../../uploads/audio/points/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $ext = pathinfo($_FILES['audio_file_en']['name'], PATHINFO_EXTENSION);
        $filename = 'point_' . $point_id . '_en_' . time() . '.' . $ext;
        $upload_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['audio_file_en']['tmp_name'], $upload_path)) {
            $audio_path = '/uploads/audio/points/' . $filename;
            $stmt = $pdo->prepare("
                UPDATE points
                SET audio_file_path_en = ?,
                    audio_enabled = 1
                WHERE id = ?
            ");
            $stmt->execute([$audio_path, $point_id]);
            $_SESSION['success'] = 'Аудиофайл (английский) успешно загружен';
            header("Location: /admin/points/audio.php?id=$point_id");
            exit;
        }
    } catch (Exception $e) {
        $error = 'Ошибка при загрузке: ' . $e->getMessage();
    }
}
$stmt = $pdo->prepare("
    SELECT p.*, r.name as route_name
    FROM points p
    JOIN routes r ON p.route_id = r.id
    WHERE p.id = ?
");
$stmt->execute([$point_id]);
$point = $stmt->fetch();
if (!$point) {
    header('Location: /admin/points/list.php');
    exit;
}
$stmt = $pdo->prepare("SELECT task_text, task_text_en FROM tasks WHERE point_id = ? ORDER BY `order` ASC LIMIT 1");
$stmt->execute([$point_id]);
$first_task = $stmt->fetch();
$point['task_text'] = $first_task['task_text'] ?? '';
$point['task_text_en'] = $first_task['task_text_en'] ?? '';
?>
<?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-microphone me-2"></i>Управление аудио</h5>
                <small class="text-muted">
                    Точка: <?= htmlspecialchars($point['name']) ?>
                    (<?= htmlspecialchars($point['route_name']) ?>)
                </small>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <h6 class="mb-3">🇷🇺 Русский аудио</h6>
                    <?php
                    $audio_ru = $point['audio_file_path_ru'] ?? $point['audio_file_path'] ?? null;
                    $audio_ru_path = $audio_ru && file_exists(__DIR__ . '/../..' . $audio_ru) ? $audio_ru : null;
                    ?>
                    <?php if ($audio_ru_path): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Аудиофайл загружен
                            <audio controls class="w-100 mt-2">
                                <source src="<?= htmlspecialchars($audio_ru_path) ?>" type="audio/mpeg">
                                Ваш браузер не поддерживает аудио.
                            </audio>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Аудиофайл не загружен
                        </div>
                    <?php endif; ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <strong>Текст для озвучки (RU):</strong>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($point['audio_text'] ?? $point['task_text'] ?? '')) ?></p>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-2">
                            <input type="file" name="audio_file_ru" class="form-control"
                                   accept="audio/mp3,audio/mpeg,audio/wav">
                            <small class="text-muted">
                                Загрузить аудиофайл (MP3, WAV, до 5 МБ)
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-upload me-2"></i>Загрузить (RU)
                        </button>
                    </form>
                </div>
                <hr>
                <div class="mb-4">
                    <h6 class="mb-3">🇬🇧 English Audio</h6>
                    <?php
                    $audio_en = $point['audio_file_path_en'] ?? null;
                    $audio_en_path = $audio_en && file_exists(__DIR__ . '/../..' . $audio_en) ? $audio_en : null;
                    ?>
                    <?php if ($audio_en_path): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Audio file uploaded
                            <audio controls class="w-100 mt-2">
                                <source src="<?= htmlspecialchars($audio_en_path) ?>" type="audio/mpeg">
                                Your browser does not support audio.
                            </audio>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Audio file not uploaded
                        </div>
                    <?php endif; ?>
                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <strong>Text for audio (EN):</strong>
                            <p class="mb-0"><?= nl2br(htmlspecialchars($point['audio_text_en'] ?? $point['task_text_en'] ?? '')) ?></p>
                        </div>
                    </div>
                    <form method="POST" enctype="multipart/form-data" class="mb-3">
                        <div class="mb-2">
                            <input type="file" name="audio_file_en" class="form-control"
                                   accept="audio/mp3,audio/mpeg,audio/wav">
                            <small class="text-muted">
                                Upload audio file (MP3, WAV, up to 5 MB)
                            </small>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm">
                            <i class="fas fa-upload me-2"></i>Upload (EN)
                        </button>
                    </form>
                </div>
                <hr>
                <h6 class="mb-3">🎤 Сгенерировать аудио автоматически (TTS)</h6>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    Бот автоматически создаст аудио для обоих языков при первом запросе пользователя.
                    Используется Edge TTS для русского и английского языков.
                </div>
                <div class="d-flex gap-2 mt-4">
                    <a href="/admin/points/edit.php?id=<?= $point_id ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Назад к точке
                    </a>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h6><i class="fas fa-info-circle me-2"></i>Информация</h6>
            </div>
            <div class="card-body">
                <p><strong>Статус аудио:</strong>
                    <?php if ($point['audio_enabled']): ?>
                        <span class="badge bg-success">Включено</span>
                    <?php else: ?>
                        <span class="badge bg-secondary">Выключено</span>
                    <?php endif; ?>
                </p>
                <p><strong>Язык:</strong> <?= htmlspecialchars($point['audio_language'] ?: 'ru') ?></p>
                <p class="mb-0">
                    <small class="text-muted">
                        Аудиогид будет доступен пользователям через кнопку "🎧 Аудиогид"
                        при прохождении точки в боте.
                    </small>
                </p>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>