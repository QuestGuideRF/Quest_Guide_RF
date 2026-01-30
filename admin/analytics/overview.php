<?php
$page_title = 'Аналитика';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
$stmt = $pdo->query("
    SELECT
        COUNT(DISTINCT u.id) as total_users,
        COUNT(DISTINCT CASE WHEN u.created_at >= DATE_SUB(NOW(), INTERVAL $days DAY) THEN u.id END) as new_users,
        COUNT(DISTINCT r.id) as total_routes,
        COUNT(DISTINCT up.id) as total_progress,
        COUNT(DISTINCT CASE WHEN up.status = 'COMPLETED' THEN up.id END) as completed_quests,
        COUNT(DISTINCT p.id) as total_payments,
        SUM(DISTINCT p.amount) as total_revenue
    FROM users u
    LEFT JOIN routes r ON 1=1
    LEFT JOIN user_progress up ON u.id = up.user_id
    LEFT JOIN payments p ON u.id = p.user_id AND p.status = 'SUCCESS'
");
$stats = $stmt->fetch();
$conversion_rate = $stats['total_users'] > 0
    ? round(($stats['total_payments'] / $stats['total_users']) * 100, 2)
    : 0;
$completion_rate = $stats['total_progress'] > 0
    ? round(($stats['completed_quests'] / $stats['total_progress']) * 100, 2)
    : 0;
$avg_check = $stats['total_payments'] > 0
    ? round($stats['total_revenue'] / $stats['total_payments'], 2)
    : 0;
$stmt = $pdo->prepare("
    SELECT
        DATE(created_at) as date,
        COUNT(*) as count
    FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
");
$stmt->execute([$days]);
$registrations = $stmt->fetchAll();
$stmt = $pdo->query("
    SELECT r.name, SUM(p.amount) as revenue, COUNT(DISTINCT p.id) as payments
    FROM routes r
    JOIN payments p ON r.id = p.route_id AND p.status = 'SUCCESS'
    GROUP BY r.id
    ORDER BY revenue DESC
    LIMIT 10
");
$top_routes_revenue = $stmt->fetchAll();
$stmt = $pdo->query("
    SELECT HOUR(created_at) as hour, COUNT(*) as count
    FROM user_progress
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL $days DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour
");
$activity_by_hour = $stmt->fetchAll();
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-chart-line me-2"></i>Детальная аналитика</h2>
    <div class="btn-group">
        <a href="?days=7" class="btn btn-<?= $days == 7 ? 'primary' : 'outline-primary' ?>">7 дней</a>
        <a href="?days=30" class="btn btn-<?= $days == 30 ? 'primary' : 'outline-primary' ?>">30 дней</a>
        <a href="?days=90" class="btn btn-<?= $days == 90 ? 'primary' : 'outline-primary' ?>">90 дней</a>
    </div>
</div>
<!-- Ключевые метрики -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(102, 126, 234, 0.2); color: #667eea;">
                <i class="fas fa-users"></i>
            </div>
            <h3><?= number_format((int)($stats['total_users'] ?? 0)) ?></h3>
            <p class="text-muted mb-0">Всего пользователей</p>
            <small class="text-success">+<?= (int)($stats['new_users'] ?? 0) ?> за период</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(76, 175, 80, 0.2); color: #4caf50;">
                <i class="fas fa-percent"></i>
            </div>
            <h3><?= $conversion_rate ?>%</h3>
            <p class="text-muted mb-0">Конверсия в покупку</p>
            <small><?= $stats['total_payments'] ?> из <?= $stats['total_users'] ?></small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(255, 152, 0, 0.2); color: #ff9800;">
                <i class="fas fa-ruble-sign"></i>
            </div>
            <h3><?= number_format((float)($stats['total_revenue'] ?? 0)) ?>₽</h3>
            <p class="text-muted mb-0">Общая выручка</p>
            <small>Средний чек: <?= number_format((float)($avg_check ?? 0)) ?>₽</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="icon" style="background: rgba(156, 39, 176, 0.2); color: #9c27b0;">
                <i class="fas fa-check-circle"></i>
            </div>
            <h3><?= $completion_rate ?>%</h3>
            <p class="text-muted mb-0">Завершаемость</p>
            <small><?= $stats['completed_quests'] ?> из <?= $stats['total_progress'] ?></small>
        </div>
    </div>
</div>
<!-- Графики -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-chart-line me-2"></i>Регистрации по дням</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="registrationsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><i class="fas fa-clock me-2"></i>Активность по часам</h5>
                <div style="position: relative; height: 300px;">
                    <canvas id="activityChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Топ маршрутов -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-trophy me-2"></i>Топ-10 маршрутов по выручке</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>№</th>
                        <th>Маршрут</th>
                        <th>Платежей</th>
                        <th>Выручка</th>
                        <th>Средний чек</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $i = 1; foreach ($top_routes_revenue as $route): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($route['name']) ?></td>
                            <td><?= $route['payments'] ?></td>
                            <td><strong><?= number_format((float)($route['revenue'] ?? 0)) ?>₽</strong></td>
                            <td><?= number_format((float)(($route['revenue'] ?? 0) / max(1, $route['payments'] ?? 1))) ?>₽</td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
const registrationsData = <?= json_encode($registrations) ?>;
new Chart(document.getElementById('registrationsChart'), {
    type: 'line',
    data: {
        labels: registrationsData.map(d => d.date),
        datasets: [{
            label: 'Регистрации',
            data: registrationsData.map(d => d.count),
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
const activityData = <?= json_encode($activity_by_hour) ?>;
new Chart(document.getElementById('activityChart'), {
    type: 'bar',
    data: {
        labels: activityData.map(d => d.hour + ':00'),
        datasets: [{
            label: 'Активность',
            data: activityData.map(d => d.count),
            backgroundColor: '#9c27b0',
            borderColor: '#9c27b0',
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
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>