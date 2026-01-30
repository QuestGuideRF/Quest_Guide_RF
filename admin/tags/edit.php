<?php
$page_title = 'Редактирование тега';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$tag_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if (!$tag_id) {
    header('Location: /admin/tags/list.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE tags
            SET name = ?,
                slug = ?,
                icon = ?,
                color = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $_POST['name'],
            $_POST['slug'],
            $_POST['icon'] ?: null,
            $_POST['color'] ?: null,
            $tag_id
        ]);
        $_SESSION['success'] = 'Тег успешно обновлен';
        header('Location: /admin/tags/list.php');
        exit;
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$stmt = $pdo->prepare("
    SELECT t.*, COUNT(DISTINCT rt.route_id) as usage_count
    FROM tags t
    LEFT JOIN route_tags rt ON t.id = rt.tag_id
    WHERE t.id = ?
    GROUP BY t.id
");
$stmt->execute([$tag_id]);
$tag = $stmt->fetch();
if (!$tag) {
    header('Location: /admin/tags/list.php');
    exit;
}
$type_names = [
    'topic' => 'Тема',
    'age' => 'Возраст',
    'difficulty' => 'Сложность',
    'duration' => 'Длительность',
    'season' => 'Сезон'
];
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
                <h5><i class="fas fa-edit me-2"></i>Редактирование тега</h5>
                <small class="text-muted">Тип: <?= $type_names[$tag['type']] ?></small>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Название *</label>
                        <input type="text" name="name" class="form-control"
                               value="<?= htmlspecialchars($tag['name']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Slug *</label>
                        <input type="text" name="slug" class="form-control"
                               value="<?= htmlspecialchars($tag['slug']) ?>" required>
                        <small class="text-muted">Используется в URL, должен быть уникальным</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Иконка (эмодзи)</label>
                        <input type="text" name="icon" class="form-control"
                               value="<?= htmlspecialchars($tag['icon']) ?>">
                        <small class="text-muted">
                            Текущая: <?= $tag['icon'] ? '<span style="font-size: 24px;">' . htmlspecialchars($tag['icon']) . '</span>' : 'не установлена' ?>
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Цвет (HEX)</label>
                        <input type="color" name="color" class="form-control form-control-color"
                               value="<?= htmlspecialchars($tag['color'] ?: '#4682B4') ?>">
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить
                        </button>
                        <a href="/admin/tags/list.php" class="btn btn-secondary">
                            <i class="fas fa-times me-2"></i>Отмена
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h6><i class="fas fa-info-circle me-2"></i>Информация</h6>
            </div>
            <div class="card-body">
                <p><strong>Тип:</strong> <?= $type_names[$tag['type']] ?></p>
                <p><strong>Использований:</strong> <?= $tag['usage_count'] ?></p>
                <p><strong>Создан:</strong> <?= date('d.m.Y H:i', strtotime($tag['created_at'])) ?></p>
            </div>
        </div>
        <?php if ($tag['usage_count'] > 0): ?>
            <div class="card">
                <div class="card-header">
                    <h6><i class="fas fa-route me-2"></i>Используется в маршрутах</h6>
                </div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT r.id, r.name
                        FROM routes r
                        JOIN route_tags rt ON r.id = rt.route_id
                        WHERE rt.tag_id = ?
                        LIMIT 10
                    ");
                    $stmt->execute([$tag_id]);
                    $routes = $stmt->fetchAll();
                    ?>
                    <ul class="list-unstyled mb-0">
                        <?php foreach ($routes as $route): ?>
                            <li class="mb-2">
                                <a href="/admin/routes/edit.php?id=<?= $route['id'] ?>">
                                    <?= htmlspecialchars($route['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php if ($tag['usage_count'] > 10): ?>
                        <p class="text-muted mb-0 mt-2">
                            и еще <?= $tag['usage_count'] - 10 ?> маршрутов...
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>