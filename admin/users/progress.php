<?php
$page_title = 'Прогресс пользователя';
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
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if (!$user_id) {
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-danger">Пользователь не указан</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$user_id]);
$user = $user->fetch();
if (!$user) {
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="alert alert-danger">Пользователь не найден</div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $progress_id = intval($_POST['progress_id'] ?? 0);
    if ($progress_id) {
        $progress = $pdo->prepare("SELECT * FROM user_progress WHERE id = ?");
        $progress->execute([$progress_id]);
        $progress = $progress->fetch();
        if ($progress) {
            switch ($action) {
                case 'next_point':
                    $next = $pdo->prepare("SELECT * FROM points WHERE route_id = ? AND `order` > ? ORDER BY `order` LIMIT 1");
                    $next->execute([$progress['route_id'], $progress['current_point_order']]);
                    $next_point = $next->fetch();
                    if ($next_point) {
                        $pdo->prepare("UPDATE user_progress SET current_point_id = ?, current_point_order = ?, points_completed = points_completed + 1 WHERE id = ?")
                            ->execute([$next_point['id'], $next_point['order'], $progress_id]);
                    }
                    break;
                case 'prev_point':
                    $prev = $pdo->prepare("SELECT * FROM points WHERE route_id = ? AND `order` < ? ORDER BY `order` DESC LIMIT 1");
                    $prev->execute([$progress['route_id'], $progress['current_point_order']]);
                    $prev_point = $prev->fetch();
                    if ($prev_point) {
                        $completed = max(0, $progress['points_completed'] - 1);
                        $pdo->prepare("UPDATE user_progress SET current_point_id = ?, current_point_order = ?, points_completed = ? WHERE id = ?")
                            ->execute([$prev_point['id'], $prev_point['order'], $completed, $progress_id]);
                    }
                    break;
                case 'set_point':
                    $point_id = intval($_POST['point_id'] ?? 0);
                    if ($point_id) {
                        $pt = $pdo->prepare("SELECT * FROM points WHERE id = ?");
                        $pt->execute([$point_id]);
                        $pt = $pt->fetch();
                        if ($pt) {
                            $pdo->prepare("UPDATE user_progress SET current_point_id = ?, current_point_order = ? WHERE id = ?")
                                ->execute([$pt['id'], $pt['order'], $progress_id]);
                        }
                    }
                    break;
                case 'reset':
                    $first = $pdo->prepare("SELECT * FROM points WHERE route_id = ? ORDER BY `order` LIMIT 1");
                    $first->execute([$progress['route_id']]);
                    $first_point = $first->fetch();
                    if ($first_point) {
                        $pdo->prepare("UPDATE user_progress SET current_point_id = ?, current_point_order = ?, points_completed = 0, status = 'IN_PROGRESS', completed_at = NULL WHERE id = ?")
                            ->execute([$first_point['id'], $first_point['order'], $progress_id]);
                    }
                    break;
                case 'complete':
                    $pdo->prepare("UPDATE user_progress SET status = 'COMPLETED', completed_at = NOW() WHERE id = ?")
                        ->execute([$progress_id]);
                    break;
            }
        }
    }
    header("Location: /admin/users/progress.php?user_id=$user_id&saved=1");
    exit;
}
require_once __DIR__ . '/../includes/header.php';
$stmt = $pdo->prepare("
    SELECT up.*, r.name as route_name, p.name as point_name, p.`order` as point_order
    FROM user_progress up
    JOIN routes r ON up.route_id = r.id
    LEFT JOIN points p ON up.current_point_id = p.id
    WHERE up.user_id = ?
    ORDER BY up.started_at DESC
");
$stmt->execute([$user_id]);
$progresses = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-tasks"></i> Прогресс: <?= htmlspecialchars($user['first_name']) ?>
            <?php if ($user['username']): ?>
                <small class="text-muted">(@<?= htmlspecialchars($user['username']) ?>)</small>
            <?php endif; ?>
        </h2>
        <a href="/admin/users/list.php" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left"></i> Назад</a>
    </div>
</div>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="fas fa-check"></i> Прогресс обновлён
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if (empty($progresses)): ?>
    <div class="alert alert-info">У пользователя нет активных прохождений</div>
<?php else: ?>
    <?php foreach ($progresses as $prog): ?>
        <?php
        $route_points = $pdo->prepare("SELECT id, name, `order` FROM points WHERE route_id = ? ORDER BY `order`");
        $route_points->execute([$prog['route_id']]);
        $all_points = $route_points->fetchAll();
        $total_points = count($all_points);
        ?>
        <div class="card mb-4" style="min-width: 0;">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-1">
                <h5 class="mb-0">
                    <i class="fas fa-route"></i> <?= htmlspecialchars($prog['route_name']) ?>
                    <span class="badge bg-<?= $prog['status'] === 'COMPLETED' ? 'success' : ($prog['status'] === 'PAUSED' ? 'warning' : 'primary') ?>">
                        <?= $prog['status'] ?>
                    </span>
                </h5>
                <small class="text-muted">ID: <?= $prog['id'] ?></small>
            </div>
            <div class="card-body" style="overflow-x: hidden;">
                <div class="row mb-3 g-2">
                    <div class="col-6 col-md-3">
                        <strong>Текущая точка:</strong><br>
                        <?= htmlspecialchars($prog['point_name'] ?? 'N/A') ?> (order: <?= $prog['point_order'] ?? '?' ?>)
                    </div>
                    <div class="col-6 col-md-3">
                        <strong>Пройдено точек:</strong><br>
                        <?= $prog['points_completed'] ?> / <?= $total_points ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <strong>Начато:</strong><br>
                        <?= $prog['started_at'] ? date('d.m.Y H:i', strtotime($prog['started_at'])) : '-' ?>
                    </div>
                    <div class="col-6 col-md-3">
                        <strong>Заработано:</strong><br>
                        <?= number_format($prog['total_earned'] ?? 0) ?> грошей
                    </div>
                </div>
                <div class="progress mb-3" style="height: 20px; min-width: 0; max-width: 100%;">
                    <?php $pct = $total_points > 0 ? round($prog['points_completed'] / $total_points * 100) : 0; ?>
                    <div class="progress-bar" style="width: <?= $pct ?>%; min-width: 2em;"><?= $pct ?>%</div>
                </div>
                <?php if ($prog['status'] !== 'COMPLETED'): ?>
                    <div class="d-flex gap-2 flex-wrap">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="progress_id" value="<?= $prog['id'] ?>">
                            <input type="hidden" name="action" value="prev_point">
                            <button type="submit" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Перевести на предыдущую точку?')">
                                <i class="fas fa-arrow-left"></i> Пред. точка
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="progress_id" value="<?= $prog['id'] ?>">
                            <input type="hidden" name="action" value="next_point">
                            <button type="submit" class="btn btn-sm btn-outline-primary" onclick="return confirm('Перевести на следующую точку?')">
                                <i class="fas fa-arrow-right"></i> След. точка
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="progress_id" value="<?= $prog['id'] ?>">
                            <input type="hidden" name="action" value="set_point">
                            <select name="point_id" class="form-select form-select-sm d-inline-block" style="width: auto;">
                                <?php foreach ($all_points as $pt): ?>
                                    <option value="<?= $pt['id'] ?>" <?= $pt['id'] == $prog['current_point_id'] ? 'selected' : '' ?>>
                                        <?= $pt['order'] ?>. <?= htmlspecialchars($pt['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-info" onclick="return confirm('Перевести на выбранную точку?')">
                                <i class="fas fa-map-pin"></i> Перейти
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="progress_id" value="<?= $prog['id'] ?>">
                            <input type="hidden" name="action" value="reset">
                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Сбросить прогресс? Пользователь начнёт сначала.')">
                                <i class="fas fa-undo"></i> Сбросить
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="progress_id" value="<?= $prog['id'] ?>">
                            <input type="hidden" name="action" value="complete">
                            <button type="submit" class="btn btn-sm btn-outline-success" onclick="return confirm('Завершить квест вручную?')">
                                <i class="fas fa-check"></i> Завершить
                            </button>
                        </form>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>