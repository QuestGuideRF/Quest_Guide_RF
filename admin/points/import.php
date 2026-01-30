<?php
$page_title = 'Импорт точек';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$route_id = isset($_GET['route_id']) ? (int)$_GET['route_id'] : null;
$error = null;
$success = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    try {
        if ($_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Ошибка загрузки файла');
        }
        $file = fopen($_FILES['csv_file']['tmp_name'], 'r');
        $header = fgetcsv($file);
        $imported = 0;
        $pdo->beginTransaction();
        while (($row = fgetcsv($file)) !== false) {
            if (count($row) < 4) continue;
            $name = $row[0];
            $latitude = floatval($row[1]);
            $longitude = floatval($row[2]);
            $order = intval($row[3]);
            $task_text = $row[4] ?? '';
            $fact_text = $row[5] ?? '';
            $audio_text = $row[6] ?? '';
            $audio_enabled = isset($row[7]) && $row[7] == '1' ? 1 : 0;
            $stmt = $pdo->prepare("
                INSERT INTO points (route_id, name, latitude, longitude, `order`, fact_text, audio_text, audio_enabled, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([
                $route_id,
                $name,
                $latitude,
                $longitude,
                $order,
                $fact_text,
                $audio_text,
                $audio_enabled
            ]);
            $point_id = $pdo->lastInsertId();
            if ($task_text !== '') {
                $stmt = $pdo->prepare("INSERT INTO tasks (point_id, `order`, task_text, task_type, created_at) VALUES (?, 0, ?, 'photo', NOW())");
                $stmt->execute([$point_id, $task_text]);
            }
            $imported++;
        }
        $pdo->commit();
        fclose($file);
        $success = "Успешно импортировано $imported точек";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Ошибка импорта: ' . $e->getMessage();
    }
}
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Импорт точек из CSV</h5>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Маршрут *</label>
                        <select name="route_id" class="form-select" required>
                            <option value="">Выберите маршрут</option>
                            <?php foreach ($routes as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $route_id == $r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">CSV файл *</label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                        <small class="text-muted">Формат: Название, Широта, Долгота, Порядок, Задание, Факт, Аудио текст, Аудио включено</small>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Импортировать
                        </button>
                        <a href="/admin/points/list.php" class="btn btn-secondary">
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
                <h6>Формат CSV</h6>
                <p class="small">Файл должен содержать следующие колонки:</p>
                <ol class="small">
                    <li>Название</li>
                    <li>Широта</li>
                    <li>Долгота</li>
                    <li>Порядок</li>
                    <li>Задание (опционально)</li>
                    <li>Факт (опционально)</li>
                    <li>Аудио текст (опционально)</li>
                    <li>Аудио включено (0 или 1)</li>
                </ol>
                <a href="/admin/points/export.php?route_id=<?= $route_id ?>" class="btn btn-sm btn-info">
                    <i class="fas fa-download me-2"></i>Скачать пример
                </a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>