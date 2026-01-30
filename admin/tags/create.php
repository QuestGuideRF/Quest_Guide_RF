<?php
$page_title = 'Создание тега';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $slug = $_POST['slug'] ?: transliterate($_POST['name']);
        $stmt = $pdo->prepare("
            INSERT INTO tags (name, slug, type, icon, color, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $_POST['name'],
            $slug,
            $_POST['type'],
            $_POST['icon'] ?: null,
            $_POST['color'] ?: null
        ]);
        $_SESSION['success'] = 'Тег успешно создан';
        header('Location: /admin/tags/list.php');
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при создании: ' . $e->getMessage();
    }
}
function transliterate($text) {
    $converter = array(
        'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd',
        'е' => 'e', 'ё' => 'e', 'ж' => 'zh', 'з' => 'z', 'и' => 'i',
        'й' => 'y', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n',
        'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't',
        'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c', 'ч' => 'ch',
        'ш' => 'sh', 'щ' => 'sch', 'ь' => '', 'ы' => 'y', 'ъ' => '',
        'э' => 'e', 'ю' => 'yu', 'я' => 'ya'
    );
    $text = mb_strtolower($text);
    $text = strtr($text, $converter);
    $text = mb_ereg_replace('[^-0-9a-z]', '-', $text);
    $text = mb_ereg_replace('[-]+', '-', $text);
    $text = trim($text, '-');
    return $text;
}
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
                <h5><i class="fas fa-plus me-2"></i>Создание нового тега</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Тип тега *</label>
                        <select name="type" class="form-select" required>
                            <option value="">Выберите тип</option>
                            <option value="topic">Тема</option>
                            <option value="age">Возраст</option>
                            <option value="difficulty">Сложность</option>
                            <option value="duration">Длительность</option>
                            <option value="season">Сезон</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="Например: История" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug (URL-friendly)</label>
                        <input type="text" name="slug" class="form-control"
                               placeholder="Оставьте пустым для автоматической генерации">
                        <small class="text-muted">Будет сгенерирован автоматически из названия</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Иконка (эмодзи)</label>
                        <input type="text" name="icon" class="form-control"
                               placeholder="🏛️">
                        <small class="text-muted">Можно вставить эмодзи из <a href="https://emojipedia.org/" target="_blank">Emojipedia</a></small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Цвет (HEX)</label>
                        <input type="color" name="color" class="form-control form-control-color" value="#4682B4">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Создать тег
                        </button>
                        <a href="/admin/tags/list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>