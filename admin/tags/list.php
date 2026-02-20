<?php
$page_title = 'Управление тегами';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$type = isset($_GET['type']) ? $_GET['type'] : '';
$where = $type ? "WHERE type = '$type'" : "";
$stmt = $pdo->query("
    SELECT t.*, COUNT(DISTINCT rt.route_id) as usage_count
    FROM tags t
    LEFT JOIN route_tags rt ON t.id = rt.tag_id
    $where
    GROUP BY t.id
    ORDER BY t.type, t.name
");
$tags = $stmt->fetchAll();
$tags_by_type = [];
foreach ($tags as $tag) {
    $tags_by_type[$tag['type']][] = $tag;
}
$type_names = [
    'topic' => 'Темы',
    'age' => 'Возраст',
    'difficulty' => 'Сложность',
    'duration' => 'Длительность',
    'season' => 'Сезон'
];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-tags me-2"></i>Теги и категории</h2>
    <a href="/admin/tags/create.php" class="btn btn-primary">
        <i class="fas fa-plus me-2"></i>Создать тег
    </a>
</div>
<<<<<<< HEAD
=======
<!-- Фильтр по типу -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="card mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="?" class="btn btn-<?= !$type ? 'primary' : 'outline-primary' ?>">Все</a>
            <a href="?type=topic" class="btn btn-<?= $type === 'topic' ? 'primary' : 'outline-primary' ?>">Темы</a>
            <a href="?type=age" class="btn btn-<?= $type === 'age' ? 'primary' : 'outline-primary' ?>">Возраст</a>
            <a href="?type=difficulty" class="btn btn-<?= $type === 'difficulty' ? 'primary' : 'outline-primary' ?>">Сложность</a>
            <a href="?type=duration" class="btn btn-<?= $type === 'duration' ? 'primary' : 'outline-primary' ?>">Длительность</a>
            <a href="?type=season" class="btn btn-<?= $type === 'season' ? 'primary' : 'outline-primary' ?>">Сезон</a>
        </div>
    </div>
</div>
<<<<<<< HEAD
=======
<!-- Теги по типам -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<?php foreach ($tags_by_type as $tag_type => $type_tags): ?>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-folder me-2"></i><?= $type_names[$tag_type] ?></h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Иконка</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Цвет</th>
                            <th>Использований</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($type_tags as $tag): ?>
                            <tr>
                                <td>
                                    <?php if ($tag['icon']): ?>
                                        <span style="font-size: 24px;"><?= htmlspecialchars($tag['icon']) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?= htmlspecialchars($tag['name']) ?></strong></td>
                                <td><code><?= htmlspecialchars($tag['slug']) ?></code></td>
                                <td>
                                    <?php if ($tag['color']): ?>
                                        <div class="d-flex align-items-center">
                                            <div style="width: 30px; height: 30px; background: <?= htmlspecialchars($tag['color']) ?>; border-radius: 5px; border: 1px solid #ddd;"></div>
                                            <code class="ms-2"><?= htmlspecialchars($tag['color']) ?></code>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= $tag['usage_count'] ?></span>
                                </td>
                                <td>
                                    <div class="table-actions">
                                        <a href="/admin/tags/edit.php?id=<?= $tag['id'] ?>"
                                           class="btn btn-sm btn-primary" title="Редактировать">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php if ($tag['usage_count'] == 0): ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-danger"
                                                    onclick="deleteTag(<?= $tag['id'] ?>)"
                                                    title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php else: ?>
                                            <button type="button"
                                                    class="btn btn-sm btn-secondary"
                                                    disabled
                                                    title="Используется в маршрутах">
                                                <i class="fas fa-lock"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endforeach; ?>
<?php if (empty($tags)): ?>
    <div class="card">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-inbox fa-3x mb-3"></i>
            <p>Теги не найдены</p>
            <a href="/admin/tags/create.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Создать первый тег
            </a>
        </div>
    </div>
<?php endif; ?>
<script>
function deleteTag(id) {
    if (confirm('Удалить этот тег?')) {
        fetch('/admin/api/delete_tag.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({tag_id: id})
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Ошибка: ' + data.error);
            }
        });
    }
}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>