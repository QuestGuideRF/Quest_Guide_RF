<?php
$page_title = 'Пользователи';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$role = isset($_GET['role']) ? $_GET['role'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where = [];
$params = [];
if ($role) {
    $where[] = "u.role = ?";
    $params[] = $role;
}
if ($search) {
    $where[] = "(u.first_name LIKE ? OR u.last_name LIKE ? OR u.username LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $pdo->prepare("
    SELECT u.*,
           COUNT(DISTINCT up.id) as routes_count,
           COUNT(DISTINCT CASE WHEN up.status = 'COMPLETED' THEN up.id END) as completed_count,
           SUM(DISTINCT p.amount) as total_paid
    FROM users u
    LEFT JOIN user_progress up ON u.id = up.user_id
    LEFT JOIN payments p ON u.id = p.user_id AND p.status = 'SUCCESS'
    $whereClause
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT 100
");
$stmt->execute($params);
$users = $stmt->fetchAll();
$total_users = $pdo->query("SELECT COUNT(*) as cnt FROM users")->fetch()['cnt'];
$admins_count = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE role = 'ADMIN'")->fetch()['cnt'];
$banned_count = $pdo->query("SELECT COUNT(*) as cnt FROM users WHERE is_banned = 1")->fetch()['cnt'];
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-users me-2"></i>Пользователи</h2>
</div>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h4><?= number_format($total_users) ?></h4>
                <p class="text-muted mb-0">Всего пользователей</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h4><?= number_format($admins_count) ?></h4>
                <p class="text-muted mb-0">Администраторов</p>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <h4><?= number_format($banned_count) ?></h4>
                <p class="text-muted mb-0">Заблокировано</p>
            </div>
        </div>
    </div>
</div>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Роль</label>
                <select name="role" class="form-select">
                    <option value="">Все</option>
                    <option value="USER" <?= $role === 'USER' ? 'selected' : '' ?>>Пользователи</option>
                    <option value="ADMIN" <?= $role === 'ADMIN' ? 'selected' : '' ?>>Администраторы</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Поиск</label>
                <input type="text" name="search" class="form-control"
                       placeholder="Имя или username" value="<?= htmlspecialchars($search) ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search me-2"></i>Найти
                </button>
            </div>
        </form>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Пользователь</th>
                        <th>Роль</th>
                        <th>Маршруты</th>
                        <th>Завершено</th>
                        <th>Оплачено</th>
                        <th>Регистрация</th>
                        <th>Действия</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php
                                    $avatar_path = null;
                                    if (!empty($user['photo_url'])) {
                                        $avatar_path = $user['photo_url'];
                                        if ($avatar_path[0] !== '/') {
                                            $avatar_path = '/' . $avatar_path;
                                        }
                                    } elseif (!empty($user['id'])) {
                                        $possible_paths = [
                                            "/uploads/avatars/{$user['id']}/avatar_*.jpg",
                                            "/uploads/avatars/{$user['id']}/avatar_*.png",
                                        ];
                                        foreach ($possible_paths as $pattern) {
                                            $files = glob($_SERVER['DOCUMENT_ROOT'] . $pattern);
                                            if (!empty($files)) {
                                                $avatar_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $files[0]);
                                                break;
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if ($avatar_path): ?>
                                        <img src="<?= htmlspecialchars($avatar_path) ?>"
                                             class="rounded-circle me-2" width="32" height="32"
                                             data-fallback="<?= htmlspecialchars(strtoupper(substr($user['first_name'], 0, 1))) ?>"
                                             onerror="var s=this.getAttribute('data-fallback'); if(s){ this.style.display='none'; var d=document.createElement('div'); d.className='rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2'; d.style.cssText='width:32px;height:32px'; d.textContent=s; this.parentNode.insertBefore(d,this.nextSibling); }">
                                    <?php else: ?>
                                        <div class="rounded-circle bg-secondary text-white d-flex align-items-center justify-content-center me-2"
                                             style="width: 32px; height: 32px;">
                                            <?= strtoupper(substr($user['first_name'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <strong><?= htmlspecialchars($user['first_name']) ?></strong>
                                        <?php if ($user['username']): ?>
                                            <br><small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($user['is_banned']): ?>
                                            <span class="badge bg-danger ms-2">Заблокирован</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-<?= $user['role'] == 'ADMIN' ? 'danger' : 'secondary' ?>">
                                    <?= $user['role'] ?>
                                </span>
                            </td>
                            <td><?= $user['routes_count'] ?></td>
                            <td><?= $user['completed_count'] ?></td>
                            <td><?= number_format($user['total_paid'] ?? 0) ?> грошей</td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d.m.Y', strtotime($user['created_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <div class="table-actions">
                                    <?php
                                    $telegram_link = '';
                                    if (!empty($user['username'])) {
                                        $telegram_link = 'https://t.me/' . htmlspecialchars($user['username']);
                                    } else {
                                        $telegram_link = 'tg://user?id=' . $user['telegram_id'];
                                    }
                                    ?>
                                    <a href="<?= $telegram_link ?>"
                                       target="_blank" class="btn btn-sm btn-primary" title="Telegram">
                                        <i class="fab fa-telegram"></i>
                                    </a>
                                    <a href="/admin/users/progress.php?user_id=<?= $user['id'] ?>"
                                       class="btn btn-sm btn-info" title="Прогресс">
                                        <i class="fas fa-tasks"></i>
                                    </a>
                                    <?php if ($user['role'] != 'ADMIN'): ?>
                                        <button class="btn btn-sm btn-<?= $user['is_banned'] ? 'success' : 'warning' ?>"
                                                onclick="toggleBan(<?= $user['id'] ?>, <?= $user['is_banned'] ?>)"
                                                title="<?= $user['is_banned'] ? 'Разблокировать' : 'Заблокировать' ?>">
                                            <i class="fas fa-<?= $user['is_banned'] ? 'unlock' : 'ban' ?>"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($users)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>Пользователи не найдены</p>
            </div>
        <?php endif; ?>
    </div>
</div>
<script>
function toggleBan(userId, isBanned) {
    const action = isBanned ? 'разблокировать' : 'заблокировать';
    if (confirm(`Вы уверены, что хотите ${action} этого пользователя?`)) {
        fetch('/admin/api/toggle_ban.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({user_id: userId})
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