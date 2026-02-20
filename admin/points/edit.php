<?php
$page_title = 'Редактирование точки';
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../includes/db.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
$pdo = getDB()->getConnection();
$point_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$point_id) {
    header('Location: /admin/points/list.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE points
            SET name = ?,
                name_en = ?,
                audio_text = ?,
                audio_text_en = ?,
                fact_text = ?,
                fact_text_en = ?,
                latitude = ?,
                longitude = ?,
                task_type = ?,
                text_answer = ?,
                text_answer_hint = ?,
                accept_partial_match = ?,
                max_attempts = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'] ?? null,
            $_POST['name_en'] ?? null,
            $_POST['audio_text'] ?? null,
            $_POST['audio_text_en'] ?? null,
            $_POST['fact_text'] ?? null,
            $_POST['fact_text_en'] ?? null,
            $_POST['latitude'],
            $_POST['longitude'],
            $_POST['task_type'] ?? 'photo',
            $_POST['text_answer'] ?? null,
            $_POST['text_answer_hint'] ?? null,
            isset($_POST['accept_partial_match']) ? 1 : 0,
            $_POST['max_attempts'] ?? 3,
            $point_id
        ]);
        $_SESSION['success'] = 'Точка успешно обновлена';
        header('Location: /admin/points/list.php?route_id=' . $_POST['route_id']);
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$stmt = $pdo->prepare("
    SELECT p.*, r.name as route_name, r.id as route_id
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
$stmt = $pdo->prepare("SELECT * FROM hints WHERE point_id = ? ORDER BY level, `order`");
$stmt->execute([$point_id]);
$hints = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE point_id = ? ORDER BY `order` ASC");
$stmt->execute([$point_id]);
$tasks = $stmt->fetchAll();
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
        <div class="card mb-4">
            <div class="card-header">
                <h5><i class="fas fa-edit me-2"></i>Редактирование точки</h5>
                <small class="text-muted">
                    Маршрут: <a href="/admin/routes/edit.php?id=<?= $point['route_id'] ?>">
                        <?= htmlspecialchars($point['route_name']) ?>
                    </a>
                </small>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="route_id" value="<?= $point['route_id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Название точки (Русский) *</label>
                        <div class="input-group">
                            <input type="text" name="name" id="name_ru" class="form-control"
                                   value="<?= htmlspecialchars($point['name'] ?? '') ?>" required>
                            <button type="button" class="btn btn-outline-secondary" onclick="translateField('name_ru', 'name_en')" title="Перевести на английский">
                                <i class="fas fa-language"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название точки (English)</label>
                        <input type="text" name="name_en" id="name_en" class="form-control"
                               value="<?= htmlspecialchars($point['name_en'] ?? '') ?>">
                    </div>
                    <div class="card border-primary mb-4">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-1 me-2"></i>Этап 1: Заметки и как дойти</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Заметки / Текст для аудио (Русский)</label>
                                <div class="input-group">
                                    <textarea name="audio_text" id="audio_text_ru" class="form-control" rows="3" maxlength="3500" placeholder="Дополнительная информация или текст для аудиогида..."><?= htmlspecialchars($point['audio_text'] ?? '') ?></textarea>
                                    <button type="button" class="btn btn-outline-secondary" onclick="translateField('audio_text_ru', 'audio_text_en')" title="Перевести на английский">
                                        <i class="fas fa-language"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Эта информация будет отправлена первой вместе с инструкцией "Как добраться"</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Заметки / Текст для аудио (English)</label>
                                <textarea name="audio_text_en" id="audio_text_en" class="form-control" rows="3" maxlength="3500" placeholder="Additional information or text for audio guide..."><?= htmlspecialchars($point['audio_text_en'] ?? '') ?></textarea>
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
                                    <textarea name="fact_text" id="fact_text_ru" class="form-control" rows="3" maxlength="3500" placeholder="Интересный факт о месте..."><?= htmlspecialchars($point['fact_text'] ?? '') ?></textarea>
                                    <button type="button" class="btn btn-outline-secondary" onclick="translateField('fact_text_ru', 'fact_text_en')" title="Перевести на английский">
                                        <i class="fas fa-language"></i>
                                    </button>
                                </div>
                                <small class="text-muted">Факт будет отправлен после нажатия кнопки "Я на месте" вместе с аудиогидами</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Факт (English)</label>
                                <textarea name="fact_text_en" id="fact_text_en" class="form-control" rows="3" maxlength="3500" placeholder="Interesting fact about the place..."><?= htmlspecialchars($point['fact_text_en'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card border-warning mb-4">
                        <div class="card-header bg-warning text-dark">
                            <h6 class="mb-0"><i class="fas fa-3 me-2"></i>Этап 3: Задания</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">Задания будут отправлены после нажатия кнопки "Приступить к заданию"</span>
                                <button type="button" class="btn btn-sm btn-primary" onclick="addTask()">
                                    <i class="fas fa-plus me-1"></i>Добавить задание
                                </button>
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <small>Вы можете добавить несколько заданий к одной точке. Например, сделать фото и ответить на вопрос. В тексте задания можно указать "Как добраться" - эта информация будет автоматически извлечена и отправлена на этапе 1.</small>
                            </div>
                            <div id="tasks-container">
                                <?php if (empty($tasks)): ?>
                                    <div class="text-center text-muted py-3">
                                        <p>Задания не добавлены. Используйте кнопку "Добавить задание" выше.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($tasks as $index => $task): ?>
                                        <div class="card mb-3 task-item" data-task-id="<?= $task['id'] ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <span class="badge bg-primary">Задание <?= $index + 1 ?></span>
                                                <div>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveTask(<?= $task['id'] ?>, 'up')" title="Вверх">
                                                        <i class="fas fa-arrow-up"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveTask(<?= $task['id'] ?>, 'down')" title="Вниз">
                                                        <i class="fas fa-arrow-down"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask(<?= $task['id'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-3">
                                                    <label class="form-label">Тип задания *</label>
                                                    <select class="form-select task-type" onchange="toggleTaskTypeFields(this)">
                                                        <option value="photo" <?= $task['task_type'] == 'photo' ? 'selected' : '' ?>>📷 Фото</option>
                                                        <option value="text" <?= $task['task_type'] == 'text' ? 'selected' : '' ?>>✍️ Текстовый ответ</option>
                                                        <option value="riddle" <?= $task['task_type'] == 'riddle' ? 'selected' : '' ?>>🧩 Загадка</option>
                                                    </select>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Задание (Русский) *</label>
                                                    <div class="input-group">
                                                        <textarea class="form-control task-text-ru" rows="3" maxlength="3500" required placeholder="Что нужно найти на этой точке... Можно указать 'Как добраться' в начале текста"><?= htmlspecialchars($task['task_text'] ?? '') ?></textarea>
                                                        <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateTaskField(this)" title="Перевести на английский" style="margin-top: 0;">
                                                            <i class="fas fa-language"></i>
                                                        </button>
                                                    </div>
                                                    <small class="text-muted">Можно указать "🚇 Как добраться: ..." в начале текста - эта информация будет автоматически извлечена</small>
                                                </div>
                                                <div class="mb-3">
                                                    <label class="form-label">Задание (English)</label>
                                                    <textarea class="form-control task-text-en" rows="3" maxlength="3500" placeholder="What to find at this point..."><?= htmlspecialchars($task['task_text_en'] ?? '') ?></textarea>
                                                </div>
                                                <div class="task-text-answer-fields" style="display: <?= in_array($task['task_type'] ?? '', ['text', 'riddle']) ? 'block' : 'none' ?>;">
                                                    <div class="card bg-light">
                                                        <div class="card-body">
                                                            <h6 class="card-title"><i class="fas fa-keyboard me-2"></i>Настройки текстового ответа</h6>
                                                            <div class="mb-3">
                                                                <label class="form-label">Правильный ответ</label>
                                                                <input type="text" class="form-control task-text-answer"
                                                                       value="<?= htmlspecialchars($task['text_answer'] ?? '') ?>"
                                                                       placeholder="шлем|каска (несколько вариантов через |)">
                                                                <small class="text-muted">Для нескольких правильных ответов используйте разделитель | (например: шлем|каска)</small>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Подсказка к ответу</label>
                                                                <input type="text" class="form-control task-text-hint"
                                                                       value="<?= htmlspecialchars($task['text_answer_hint'] ?? '') ?>"
                                                                       placeholder="Например: Начинается на букву К">
                                                            </div>
                                                            <div class="row">
                                                                <div class="col-md-6 mb-3">
                                                                    <label class="form-label">Макс. попыток</label>
                                                                    <input type="number" class="form-control task-max-attempts"
                                                                           value="<?= htmlspecialchars($task['max_attempts'] ?? 3) ?>" min="1" max="10">
                                                                </div>
                                                                <div class="col-md-6 mb-3">
                                                                    <div class="form-check form-switch mt-4">
                                                                        <input class="form-check-input task-partial-match" type="checkbox"
                                                                               <?= !empty($task['accept_partial_match']) ? 'checked' : '' ?>>
                                                                        <label class="form-check-label">Частичное совпадение</label>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="task_type" value="<?= htmlspecialchars($point['task_type'] ?? 'photo') ?>">
                    <input type="hidden" name="text_answer" value="<?= htmlspecialchars($point['text_answer'] ?? '') ?>">
                    <input type="hidden" name="text_answer_hint" value="<?= htmlspecialchars($point['text_answer_hint'] ?? '') ?>">
                    <input type="hidden" name="max_attempts" value="<?= htmlspecialchars($point['max_attempts'] ?? 3) ?>">
                    <input type="hidden" name="accept_partial_match" value="<?= !empty($point['accept_partial_match']) ? '1' : '0' ?>">
                    <div class="card border-info mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0"><i class="fas fa-map-marker-alt me-2"></i>Координаты</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Широта *</label>
                                    <input type="text" name="latitude" class="form-control" inputmode="decimal"
                                           pattern="-?[0-9]*[.,]?[0-9]+" value="<?= htmlspecialchars($point['latitude'] ?? '') ?>" required
                                           placeholder="55.754775">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Долгота *</label>
                                    <input type="text" name="longitude" class="form-control" inputmode="decimal"
                                           pattern="-?[0-9]*[.,]?[0-9]+" value="<?= htmlspecialchars($point['longitude'] ?? '') ?>" required
                                           placeholder="37.616099">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
                        </button>
                        <a href="/admin/points/list.php?route_id=<?= $point['route_id'] ?>"
                           class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6><i class="fas fa-lightbulb me-2"></i>Подсказки (<?= count($hints) ?>)</h6>
                <a href="/admin/hints/create.php?point_id=<?= $point_id ?>"
                   class="btn btn-sm btn-primary">
                    Добавить
                </a>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($hints as $hint): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="badge bg-<?= $hint['level'] == 1 ? 'success' : ($hint['level'] == 2 ? 'warning' : 'danger') ?>">
                                        Уровень <?= $hint['level'] ?>
                                    </span>
                                    <p class="mb-1 mt-2 small"><?= htmlspecialchars(substr($hint['text'], 0, 100)) ?>...</p>
                                    <?php if ($hint['has_map']): ?>
                                        <small class="text-muted"><i class="fas fa-map me-1"></i>Есть карта</small>
                                    <?php endif; ?>
                                </div>
                                <a href="/admin/hints/edit.php?id=<?= $hint['id'] ?>"
                                   class="btn btn-sm btn-link">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <?php if (empty($hints)): ?>
                        <div class="list-group-item text-center text-muted">
                            Подсказки не добавлены
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <h6><i class="fas fa-map-marker-alt me-2"></i>Расположение</h6>
                <div class="small text-muted mb-2">
                    <strong>Координаты:</strong><br>
                    <?php if (!empty($point['latitude']) && !empty($point['longitude'])): ?>
                        <?= htmlspecialchars($point['latitude']) ?>, <?= htmlspecialchars($point['longitude']) ?>
                    <?php else: ?>
                        Не указаны
                    <?php endif; ?>
                </div>
                <?php if (!empty($point['latitude']) && !empty($point['longitude'])): ?>
                    <a href="https://www.google.com/maps?q=<?= htmlspecialchars($point['latitude']) ?>,<?= htmlspecialchars($point['longitude']) ?>"
                       target="_blank" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-map me-2"></i>Открыть на карте
                    </a>
                <?php else: ?>
                    <button type="button" class="btn btn-sm btn-outline-secondary w-100" disabled>
                        <i class="fas fa-map me-2"></i>Координаты не указаны
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<script>
const pointId = <?= $point_id ?>;
let taskCounter = <?= count($tasks) ?>;
function toggleTaskTypeFields(selectElement) {
    const taskItem = selectElement.closest('.task-item');
    const textFields = taskItem.querySelector('.task-text-answer-fields');
    if (selectElement.value === 'text' || selectElement.value === 'riddle') {
        textFields.style.display = 'block';
    } else {
        textFields.style.display = 'none';
    }
}
function addTask() {
    taskCounter++;
    const tasksContainer = document.getElementById('tasks-container');
    const emptyMessage = tasksContainer.querySelector('.text-center');
    if (emptyMessage) {
        emptyMessage.remove();
    }
    const taskHtml = `
        <div class="card mb-3 task-item" data-task-id="new-${taskCounter}">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="badge bg-primary">Задание ${taskCounter}</span>
                <div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveTask('new-${taskCounter}', 'up')" title="Вверх">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="moveTask('new-${taskCounter}', 'down')" title="Вниз">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-danger" onclick="deleteTask('new-${taskCounter}')">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Тип задания *</label>
                    <select class="form-select task-type" onchange="toggleTaskTypeFields(this)">
                        <option value="photo" selected>📷 Фото</option>
                        <option value="text">✍️ Текстовый ответ</option>
                        <option value="riddle">🧩 Загадка</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Задание (Русский) *</label>
                    <div class="input-group">
                        <textarea class="form-control task-text-ru" rows="2" maxlength="3500" required></textarea>
                        <button type="button" class="btn btn-outline-secondary align-self-start" onclick="translateTaskField(this)" title="Перевести на английский" style="margin-top: 0;">
                            <i class="fas fa-language"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Задание (English)</label>
                    <textarea class="form-control task-text-en" rows="2" maxlength="3500"></textarea>
                </div>
                <div class="task-text-answer-fields" style="display: none;">
                    <div class="card bg-light">
                        <div class="card-body">
                            <h6 class="card-title"><i class="fas fa-keyboard me-2"></i>Настройки текстового ответа</h6>
                            <div class="mb-3">
                                <label class="form-label">Правильный ответ</label>
                                <input type="text" class="form-control task-text-answer" placeholder="шлем|каска (несколько вариантов через |)">
                                <small class="text-muted">Для нескольких правильных ответов используйте разделитель | (например: шлем|каска)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Подсказка к ответу</label>
                                <input type="text" class="form-control task-text-hint" placeholder="Например: Начинается на букву К">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Макс. попыток</label>
                                    <input type="number" class="form-control task-max-attempts" value="3" min="1" max="10">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check form-switch mt-4">
                                        <input class="form-check-input task-partial-match" type="checkbox">
                                        <label class="form-check-label">Частичное совпадение</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    tasksContainer.insertAdjacentHTML('beforeend', taskHtml);
    saveAllTasks();
}
function deleteTask(taskId) {
    if (!confirm('Удалить это задание?')) return;
    const taskItem = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskItem) return;
    if (typeof taskId === 'number' || (typeof taskId === 'string' && !taskId.startsWith('new-'))) {
        fetch('/admin/api/delete_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({task_id: taskId})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                taskItem.remove();
                updateTaskNumbers();
                checkEmptyTasks();
            } else {
                alert('Ошибка при удалении: ' + (data.error || 'Неизвестная ошибка'));
            }
        })
        .catch(err => alert('Ошибка: ' + err.message));
    } else {
        taskItem.remove();
        updateTaskNumbers();
        checkEmptyTasks();
    }
}
function moveTask(taskId, direction) {
    const taskItem = document.querySelector(`[data-task-id="${taskId}"]`);
    if (!taskItem) return;
    const tasksContainer = document.getElementById('tasks-container');
    const allTasks = Array.from(tasksContainer.querySelectorAll('.task-item'));
    const currentIndex = allTasks.indexOf(taskItem);
    if (direction === 'up' && currentIndex > 0) {
        tasksContainer.insertBefore(taskItem, allTasks[currentIndex - 1]);
    } else if (direction === 'down' && currentIndex < allTasks.length - 1) {
        tasksContainer.insertBefore(allTasks[currentIndex + 1], taskItem);
    }
    updateTaskNumbers();
    saveAllTasks();
}
function updateTaskNumbers() {
    const tasks = document.querySelectorAll('.task-item');
    tasks.forEach((task, index) => {
        const badge = task.querySelector('.badge');
        if (badge) {
            badge.textContent = `Задание ${index + 1}`;
        }
    });
}
function checkEmptyTasks() {
    const tasksContainer = document.getElementById('tasks-container');
    const tasks = tasksContainer.querySelectorAll('.task-item');
    if (tasks.length === 0) {
        tasksContainer.innerHTML = '<div class="text-center text-muted py-3"><p>Задания не добавлены. Используйте кнопку "Добавить задание" выше.</p></div>';
    }
}
function saveAllTasks() {
    const tasks = document.querySelectorAll('.task-item');
    const tasksData = [];
    tasks.forEach((taskItem, index) => {
        const taskId = taskItem.dataset.taskId;
        const taskData = {
            id: taskId.startsWith('new-') ? null : parseInt(taskId),
            point_id: pointId,
            task_text: taskItem.querySelector('.task-text-ru').value,
            task_text_en: taskItem.querySelector('.task-text-en').value || null,
            task_type: taskItem.querySelector('.task-type').value,
            text_answer: taskItem.querySelector('.task-text-answer')?.value || null,
            text_answer_hint: taskItem.querySelector('.task-text-hint')?.value || null,
            accept_partial_match: taskItem.querySelector('.task-partial-match')?.checked || false,
            max_attempts: parseInt(taskItem.querySelector('.task-max-attempts')?.value || 3),
            order: index
        };
        tasksData.push(taskData);
    });
    if (tasksData.length === 0) {
        return Promise.resolve();
    }
    return Promise.all(tasksData.map(taskData => {
        return fetch('/admin/api/save_task.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(taskData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success && data.id && taskData.id === null) {
                const taskItem = document.querySelector(`[data-task-id="${taskData.id === null ? 'new-' + (tasksData.indexOf(taskData) + 1) : taskData.id}"]`);
                if (taskItem) {
                    taskItem.dataset.taskId = data.id;
                }
            }
            return data;
        });
    }))
    .then(results => {
        const orderData = tasksData.map((task, index) => ({
            id: task.id || results[index].id,
            order: index
        }));
        if (orderData.length === 0) return;
        return fetch('/admin/api/reorder_tasks.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({tasks: orderData})
        });
    })
    .catch(err => console.error('Ошибка при сохранении заданий:', err));
}
document.addEventListener('DOMContentLoaded', function() {
    const tasksContainer = document.getElementById('tasks-container');
    if (tasksContainer) {
        tasksContainer.addEventListener('input', debounce(saveAllTasks, 1000));
        tasksContainer.addEventListener('change', debounce(saveAllTasks, 500));
    }
    const form = document.querySelector('form[method="POST"]');
    if (form) {
        form.addEventListener('submit', function(e) {
            const tasks = document.querySelectorAll('.task-item');
            if (tasks.length === 0) return;
            e.preventDefault();
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Сохранение заданий...';
            }
            saveAllTasks().then(function() {
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-save me-2"></i>Сохранить';
                    btn.disabled = false;
                }
                form.submit();
            }).catch(function(err) {
                console.error(err);
                if (btn) {
                    btn.innerHTML = '<i class="fas fa-save me-2"></i>Сохранить';
                    btn.disabled = false;
                }
                alert('Не удалось сохранить задания. Проверьте консоль.');
            });
        });
    }
});
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}
function toggleTaskTypeFieldsOld() {
    const taskType = document.getElementById('task_type');
    if (taskType) {
        const textFields = document.getElementById('text_answer_fields');
        if (textFields) {
            if (taskType.value === 'text' || taskType.value === 'riddle') {
                textFields.style.display = 'block';
            } else {
                textFields.style.display = 'none';
            }
        }
    }
}
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
function translateTaskField(button) {
    const taskItem = button.closest('.task-item');
    const fromField = taskItem.querySelector('.task-text-ru');
    const toField = taskItem.querySelector('.task-text-en');
    const text = fromField.value.trim();
    if (!text) {
        alert('Сначала заполните поле на русском языке');
        return;
    }
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    fetch('/admin/api/translate.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({text: text, from: 'ru', to: 'en'})
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            toField.value = data.translated;
            button.innerHTML = '<i class="fas fa-check text-success"></i>';
            setTimeout(() => {
                button.innerHTML = '<i class="fas fa-language"></i>';
                button.disabled = false;
            }, 2000);
            saveAllTasks();
        } else {
            alert('Ошибка перевода: ' + (data.error || 'Неизвестная ошибка'));
            button.innerHTML = '<i class="fas fa-language"></i>';
            button.disabled = false;
        }
    })
    .catch(err => {
        alert('Ошибка: ' + err.message);
        button.innerHTML = '<i class="fas fa-language"></i>';
        button.disabled = false;
    });
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>