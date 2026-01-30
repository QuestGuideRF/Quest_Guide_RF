<?php
$page_title = 'Сертификаты';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;
$total = $pdo->query("SELECT COUNT(*) FROM certificates")->fetchColumn();
$total_pages = ceil($total / $per_page);
$stmt = $pdo->prepare("
    SELECT c.*,
           u.first_name, u.last_name, u.username, u.telegram_id,
           r.name as route_name,
           up.completed_at
    FROM certificates c
    JOIN users u ON c.user_id = u.id
    JOIN routes r ON c.route_id = r.id
    JOIN user_progress up ON c.progress_id = up.id
    ORDER BY c.created_at DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$per_page, $offset]);
$certificates = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-certificate me-2"></i>Сертификаты</h2>
    <span class="badge bg-info fs-6">Всего: <?= $total ?></span>
</div>
<div class="card">
    <div class="card-body">
        <?php if (empty($certificates)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-certificate fa-3x mb-3"></i>
                <p>Сертификаты не найдены</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Пользователь</th>
                            <th>Маршрут</th>
                            <th>Язык</th>
                            <th>Дата</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certificates as $cert): ?>
                            <tr>
                                <td><?= $cert['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($cert['first_name'] . ' ' . $cert['last_name']) ?></strong>
                                    <?php if ($cert['username']): ?>
                                        <br><small class="text-muted">@<?= htmlspecialchars($cert['username']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($cert['route_name']) ?></td>
                                <td>
                                    <?= $cert['language'] == 'ru' ? '🇷🇺 RU' : '🇬🇧 EN' ?>
                                </td>
                                <td><?= date('d.m.Y H:i', strtotime($cert['created_at'])) ?></td>
                                <td>
                                    <a href="<?= htmlspecialchars($cert['file_path']) ?>"
                                       class="btn btn-sm btn-primary"
                                       target="_blank">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= htmlspecialchars($cert['file_path']) ?>"
                                       class="btn btn-sm btn-success"
                                       download>
                                        <i class="fas fa-download"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($total_pages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>