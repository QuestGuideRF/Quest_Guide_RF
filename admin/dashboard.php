<?php
$page_title = 'Дашборд';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB()->getConnection();
$stats = [];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$stats['total_users'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['users_month'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM routes WHERE is_active = 1");
$stats['active_routes'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user_progress WHERE status = 'COMPLETED'");
$stats['completed_quests'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user_progress WHERE status = 'COMPLETED' AND completed_at >= CURDATE()");
$stats['completed_today'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user_progress WHERE status = 'COMPLETED' AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['completed_week'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'SUCCESS' AND created_at >= CURDATE()");
$stats['revenue_today'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'SUCCESS' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stats['revenue_week'] = $stmt->fetch()['total'];
$stmt = $pdo->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'SUCCESS' AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$stats['revenue_month'] = $stmt->fetch()['total'];
$stmt = $pdo->query("
    SELECT id, telegram_id, username, first_name, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
");
$recent_users = $stmt->fetchAll();
$stmt = $pdo->query("
    SELECT up.id, up.completed_at, u.first_name, u.username, r.name as route_name
    FROM user_progress up
    JOIN users u ON up.user_id = u.id
    JOIN routes r ON up.route_id = r.id
    WHERE up.status = 'COMPLETED'
    ORDER BY up.completed_at DESC
    LIMIT 5
");
$recent_completions = $stmt->fetchAll();
$stmt = $pdo->query("
    SELECT p.id, p.amount, p.created_at, u.first_name, u.username, r.name as route_name
    FROM payments p
    JOIN users u ON p.user_id = u.id
    JOIN routes r ON p.route_id = r.id
    WHERE p.status = 'SUCCESS'
    ORDER BY p.created_at DESC
    LIMIT 5
");
$recent_payments = $stmt->fetchAll();
$stmt = $pdo->query("
    SELECT r.name, COUNT(up.id) as completions
    FROM routes r
    LEFT JOIN user_progress up ON r.id = up.route_id AND up.status = 'COMPLETED'
    GROUP BY r.id
    ORDER BY completions DESC
    LIMIT 5
");
$top_routes = $stmt->fetchAll();
?>
<<<<<<< HEAD
=======
<!-- Быстрые действия -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-bolt me-2"></i>Быстрые действия</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="/admin/cities/create.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-city me-2"></i>Добавить город
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/routes/create.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-route me-2"></i>Создать маршрут
                        </a>
                    </div>
                    <div class="col-md-3">
<<<<<<< HEAD
                        <a href="/admin/points/list.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-map-pin me-2"></i>Управление точками
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/hints/list.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-lightbulb me-2"></i>Подсказки
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/audio/list.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-headphones me-2"></i>Управление аудио
                        </a>
                    </div>
                    <?php if (!isModerator()): ?>
                    <div class="col-md-3">
=======
                        <a href="/admin/points/create.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-map-pin me-2"></i>Добавить точку
                        </a>
                    </div>
                    <div class="col-md-3">
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <a href="/admin/moderation/photos.php" class="btn btn-outline-warning w-100">
                            <i class="fas fa-images me-2"></i>Модерация фото
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/reviews/list.php?status=pending" class="btn btn-outline-warning w-100">
                            <i class="fas fa-star me-2"></i>Отзывы на модерации
                        </a>
                    </div>
                    <div class="col-md-3">
<<<<<<< HEAD
=======
                        <a href="/admin/audio/list.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-headphones me-2"></i>Управление аудио
                        </a>
                    </div>
                    <div class="col-md-3">
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                        <a href="/admin/analytics/overview.php" class="btn btn-outline-info w-100">
                            <i class="fas fa-chart-bar me-2"></i>Аналитика
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="/admin/settings.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-cog me-2"></i>Настройки
                        </a>
                    </div>
<<<<<<< HEAD
                    <?php endif; ?>
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-4">
<<<<<<< HEAD
=======
    <!-- Статистика карточки -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(102, 126, 234, 0.2); color: #667eea;">
                <i class="fas fa-users"></i>
            </div>
            <h3><?= number_format($stats['total_users']) ?></h3>
            <p class="text-muted mb-0">Всего пользователей</p>
            <small class="text-success">+<?= $stats['users_month'] ?> за месяц</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(156, 39, 176, 0.2); color: #9c27b0;">
                <i class="fas fa-route"></i>
            </div>
            <h3><?= number_format($stats['active_routes']) ?></h3>
            <p class="text-muted mb-0">Активных маршрутов</p>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(76, 175, 80, 0.2); color: #4caf50;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3><?= number_format($stats['completed_quests']) ?></h3>
            <p class="text-muted mb-0">Пройдено квестов</p>
            <small class="text-success">Сегодня: <?= $stats['completed_today'] ?> | Неделя: <?= $stats['completed_week'] ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(255, 152, 0, 0.2); color: #ff9800;">
<<<<<<< HEAD
                <i class="fas fa-coins"></i>
            </div>
            <h3><?= number_format($stats['revenue_month']) ?> грошей</h3>
            <p class="text-muted mb-0">Выручка за месяц</p>
            <small>Сегодня: <?= number_format($stats['revenue_today']) ?> грошей | Неделя: <?= number_format($stats['revenue_week']) ?> грошей</small>
=======
                <i class="fas fa-ruble-sign"></i>
            </div>
            <h3><?= number_format($stats['revenue_month']) ?>₽</h3>
            <p class="text-muted mb-0">Выручка за месяц</p>
            <small>Сегодня: <?= number_format($stats['revenue_today']) ?>₽ | Неделя: <?= number_format($stats['revenue_week']) ?>₽</small>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        </div>
    </div>
</div>
<div class="row g-4 mt-4">
<<<<<<< HEAD
=======
    <!-- График регистраций -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Регистрации за последние 7 дней</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="registrationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD
=======
    <!-- График выручки -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-chart-area me-2"></i>Выручка за последние 7 дней</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row g-4 mt-4">
<<<<<<< HEAD
=======
    <!-- Топ маршрутов -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-star me-2"></i>Топ-5 маршрутов</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="topRoutesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
<<<<<<< HEAD
=======
    <!-- Последние события -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-history me-2"></i>Последние события</h5>
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#recent-users">Регистрации</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#recent-completions">Прохождения</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#recent-payments">Платежи</a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
<<<<<<< HEAD
=======
                    <!-- Последние регистрации -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    <div class="tab-pane fade show active" id="recent-users">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_users as $user): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-user-plus text-success me-2"></i>
                                            <strong><?= htmlspecialchars($user['first_name']) ?></strong>
                                            <?php if ($user['username']): ?>
                                                <small class="text-muted">@<?= htmlspecialchars($user['username']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($user['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
<<<<<<< HEAD
=======
                    <!-- Последние прохождения -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    <div class="tab-pane fade" id="recent-completions">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_completions as $completion): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <strong><?= htmlspecialchars($completion['first_name']) ?></strong>
                                            завершил
                                            <span class="badge bg-primary"><?= htmlspecialchars($completion['route_name']) ?></span>
                                        </div>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($completion['completed_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
<<<<<<< HEAD
=======
                    <!-- Последние платежи -->
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    <div class="tab-pane fade" id="recent-payments">
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_payments as $payment): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
<<<<<<< HEAD
                                            <i class="fas fa-coins text-warning me-2"></i>
                                            <strong><?= htmlspecialchars($payment['first_name']) ?></strong>
                                            оплатил
                                            <span class="badge bg-success"><?= $payment['amount'] ?> грошей</span>
=======
                                            <i class="fas fa-ruble-sign text-warning me-2"></i>
                                            <strong><?= htmlspecialchars($payment['first_name']) ?></strong>
                                            оплатил
                                            <span class="badge bg-success"><?= $payment['amount'] ?>₽</span>
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                                            <small class="text-muted"><?= htmlspecialchars($payment['route_name']) ?></small>
                                        </div>
                                        <small class="text-muted"><?= date('d.m.Y H:i', strtotime($payment['created_at'])) ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
fetch('/admin/api/stats.php?type=registrations&days=7')
    .then(r => r.json())
    .then(data => {
        new Chart(document.getElementById('registrationsChart'), {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Регистрации',
                    data: data.values,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.2)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                        labels: { color: '#e4e6eb' }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: { color: '#b0b3b8' }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: { color: '#b0b3b8' },
                        beginAtZero: true
                    }
                }
            }
        });
    });
fetch('/admin/api/stats.php?type=revenue&days=7')
    .then(r => r.json())
    .then(data => {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
<<<<<<< HEAD
                    label: 'Выручка (грошей)',
=======
                    label: 'Выручка (₽)',
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
                    data: data.values,
                    backgroundColor: '#ff9800',
                    borderColor: '#ff9800',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                        labels: { color: '#e4e6eb' }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: { color: '#b0b3b8' }
                    },
                    y: {
                        grid: {
                            color: 'rgba(255, 255, 255, 0.1)',
                            borderColor: 'rgba(255, 255, 255, 0.1)'
                        },
                        ticks: { color: '#b0b3b8' },
                        beginAtZero: true
                    }
                }
            }
        });
    });
const topRoutesData = <?= json_encode($top_routes) ?>;
new Chart(document.getElementById('topRoutesChart'), {
    type: 'bar',
    data: {
        labels: topRoutesData.map(r => r.name),
        datasets: [{
            label: 'Прохождений',
            data: topRoutesData.map(r => r.completions),
            backgroundColor: '#9c27b0',
            borderColor: '#9c27b0',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false,
                labels: { color: '#e4e6eb' }
            }
        },
        scales: {
            x: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)',
                    borderColor: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: { color: '#b0b3b8' },
                beginAtZero: true
            },
            y: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.1)',
                    borderColor: 'rgba(255, 255, 255, 0.1)'
                },
                ticks: { color: '#b0b3b8' }
            }
        }
    }
});
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>