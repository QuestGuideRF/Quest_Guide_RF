<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/auth.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
$admin = getCurrentAdmin();
$page_title = $page_title ?? 'Админ-панель';
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($page_title) ?> - QuestGuideRF Admin</title>
    <!-- Favicons -->
    <link rel="apple-touch-icon" sizes="180x180" href="/favicons/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicons/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicons/favicon-16x16.png">
    <link rel="manifest" href="/favicons/site.webmanifest">
    <link rel="shortcut icon" href="/favicons/favicon.ico">
    <meta name="theme-color" content="#1a1d29">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/admin/assets/css/admin.css">
    <!-- Chart.js Dark Theme Configuration -->
    <script>
        (function() {
            function initChartTheme() {
                if (typeof Chart === 'undefined') {
                    setTimeout(initChartTheme, 100);
                    return;
                }
                try {
                    Chart.defaults.color = '#e4e6eb';
                    Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
                    Chart.defaults.backgroundColor = 'rgba(102, 126, 234, 0.1)';
                    if (!Chart.defaults.plugins) {
                        Chart.defaults.plugins = {};
                    }
                    if (!Chart.defaults.plugins.legend) {
                        Chart.defaults.plugins.legend = {};
                    }
                    if (!Chart.defaults.plugins.legend.labels) {
                        Chart.defaults.plugins.legend.labels = {};
                    }
                    Chart.defaults.plugins.legend.labels.color = '#e4e6eb';
                    if (!Chart.defaults.scales) {
                        Chart.defaults.scales = {};
                    }
                    if (!Chart.defaults.scales.grid) {
                        Chart.defaults.scales.grid = {};
                    }
                    Chart.defaults.scales.grid.color = 'rgba(255, 255, 255, 0.1)';
                    Chart.defaults.scales.grid.borderColor = 'rgba(255, 255, 255, 0.1)';
                    if (!Chart.defaults.scales.ticks) {
                        Chart.defaults.scales.ticks = {};
                    }
                    Chart.defaults.scales.ticks.color = '#b0b3b8';
                } catch (e) {
                    console.warn('Chart.js theme initialization error:', e);
                }
            }
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initChartTheme);
            } else {
                initChartTheme();
            }
        })();
    </script>
    <style>
        :root {
            --sidebar-width: 250px;
            --header-height: 60px;
            --primary-color:
            --success-color:
            --danger-color:
            --warning-color:
            --info-color:
            --bg-primary:
            --bg-secondary:
            --bg-tertiary:
            --text-primary:
            --text-secondary:
            --border-color: rgba(255,255,255,0.1);
            --hover-bg: rgba(255,255,255,0.05);
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-primary);
        }
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background:
            color: white;
            overflow-y: auto;
            z-index: 1000;
            border-right: 1px solid var(--border-color);
        }
        .sidebar .logo {
            padding: 20px;
            font-size: 24px;
            font-weight: bold;
            border-bottom: 1px solid var(--border-color);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            display: block;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
        }
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: var(--bg-primary);
        }
        .top-bar {
            background: var(--bg-secondary);
            padding: 15px 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .content {
            padding: 30px;
        }
        .stat-card {
            background: var(--bg-secondary);
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            transition: transform 0.3s;
            border: 1px solid var(--border-color);
            color: var(--text-primary);
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.5);
        }
        .stat-card .icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 15px;
        }
        .table-actions {
            display: flex;
            gap: 5px;
        }
        .badge-custom {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
        }
        h1, h2, h3, h4, h5, h6 {
            color: var(--text-primary) !important;
        }
        .btn-primary,
        a.btn-primary {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color:
        }
        .btn-primary:hover,
        a.btn-primary:hover {
            background-color:
            border-color:
            color:
        }
        .btn-secondary {
            background-color: var(--bg-tertiary) !important;
            border-color: var(--border-color) !important;
            color: var(--text-primary) !important;
        }
        .btn-secondary:hover {
            background-color: var(--hover-bg) !important;
        }
        .btn-danger {
            background-color: var(--danger-color) !important;
            border-color: var(--danger-color) !important;
        }
        .btn-success {
            background-color: var(--success-color) !important;
            border-color: var(--success-color) !important;
        }
        .btn-warning {
            background-color: var(--warning-color) !important;
            border-color: var(--warning-color) !important;
            color:
        }
        .btn-info {
            background-color: var(--info-color) !important;
            border-color: var(--info-color) !important;
        }
        .btn-outline-primary,
        a.btn-outline-primary {
            color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            background-color: transparent !important;
        }
        .btn-outline-primary:hover,
        a.btn-outline-primary:hover {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color:
        }
        .btn-outline-primary.active,
        a.btn-outline-primary.active {
            background-color: var(--primary-color) !important;
            border-color: var(--primary-color) !important;
            color:
        }
        hr {
            border-color: var(--border-color) !important;
        }
        .mobile-menu-toggle {
            display: none;
        }
        .sidebar-overlay {
            display: none;
        }
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block !important;
                position: fixed;
                top: 15px;
                left: 15px;
                z-index: 1051;
                background: var(--bg-secondary);
                border: 1px solid var(--border-color);
                color: var(--text-primary);
                padding: 10px 15px;
                border-radius: 5px;
                cursor: pointer;
                box-shadow: 0 2px 4px rgba(0,0,0,0.3);
            }
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                z-index: 1050;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .sidebar-overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 1049;
            }
            .sidebar-overlay.show {
                display: block;
            }
            .main-content {
                margin-left: 0 !important;
            }
            .top-bar {
                padding: 10px 15px;
                flex-wrap: wrap;
            }
            .content {
                padding: 15px;
            }
            .d-md-none {
                display: none !important;
            }
            .d-md-block {
                display: block !important;
            }
        }
    </style>
</head>
<body>
    <!-- Кнопка меню для мобильных -->
    <button class="mobile-menu-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>
    <!-- Overlay для закрытия меню -->
    <div class="sidebar-overlay" onclick="toggleSidebar()"></div>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <i class="fas fa-map-marked-alt"></i> QuestGuideRF
            <button class="btn btn-link text-white d-md-none float-end" onclick="toggleSidebar()" style="padding: 0; margin-top: -5px; text-decoration: none; display: none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <nav class="mt-4">
            <a href="/admin/dashboard.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
                <i class="fas fa-chart-line me-2"></i> Дашборд
            </a>
            <a href="/admin/search/global.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/search/') !== false ? 'active' : '' ?>">
                <i class="fas fa-search me-2"></i> Поиск
            </a>
            <a href="/admin/cities/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/cities/') !== false ? 'active' : '' ?>">
                <i class="fas fa-city me-2"></i> Города
            </a>
            <a href="/admin/routes/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/routes/') !== false ? 'active' : '' ?>">
                <i class="fas fa-route me-2"></i> Маршруты
            </a>
            <a href="/admin/points/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/points/') !== false ? 'active' : '' ?>">
                <i class="fas fa-map-pin me-2"></i> Точки
            </a>
            <a href="/admin/hints/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/hints/') !== false ? 'active' : '' ?>">
                <i class="fas fa-lightbulb me-2"></i> Подсказки
            </a>
            <a href="/admin/tags/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/tags/') !== false ? 'active' : '' ?>">
                <i class="fas fa-tags me-2"></i> Теги
            </a>
            <a href="/admin/moderation/photos.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/moderation/') !== false ? 'active' : '' ?>">
                <i class="fas fa-images me-2"></i> Модерация
            </a>
            <a href="/admin/reviews/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/reviews/') !== false ? 'active' : '' ?>">
                <i class="fas fa-star me-2"></i> Отзывы
            </a>
            <a href="/admin/audio/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/audio/') !== false ? 'active' : '' ?>">
                <i class="fas fa-headphones me-2"></i> Аудиогид
            </a>
            <a href="/admin/analytics/overview.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/analytics/') !== false ? 'active' : '' ?>">
                <i class="fas fa-chart-bar me-2"></i> Аналитика
            </a>
            <a href="/admin/media/library.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/media/') !== false ? 'active' : '' ?>">
                <i class="fas fa-folder-open me-2"></i> Медиабиблиотека
            </a>
            <a href="/admin/tasks/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/tasks/') !== false ? 'active' : '' ?>">
                <i class="fas fa-tasks me-2"></i> Задачи
            </a>
            <a href="/admin/audit/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/audit/') !== false ? 'active' : '' ?>">
                <i class="fas fa-history me-2"></i> История
            </a>
            <a href="/admin/users/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/users/') !== false ? 'active' : '' ?>">
                <i class="fas fa-users me-2"></i> Пользователи
            </a>
            <a href="/admin/bank/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/bank/') !== false ? 'active' : '' ?>">
                <i class="fas fa-coins me-2"></i> Банк токенов
            </a>
            <a href="/admin/promo_codes/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/promo_codes/') !== false ? 'active' : '' ?>">
                <i class="fas fa-ticket-alt me-2"></i> Промокоды
            </a>
            <a href="/admin/certificates/list.php" class="nav-link <?= strpos($_SERVER['PHP_SELF'], '/certificates/') !== false ? 'active' : '' ?>">
                <i class="fas fa-certificate me-2"></i> Сертификаты
            </a>
            <hr style="border-color: rgba(255,255,255,0.1);">
            <a href="/admin/settings.php" class="nav-link">
                <i class="fas fa-cog me-2"></i> Настройки
            </a>
            <a href="/admin/logout.php" class="nav-link">
                <i class="fas fa-sign-out-alt me-2"></i> Выход
            </a>
        </nav>
    </div>
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h4 class="mb-0"><?= htmlspecialchars($page_title) ?></h4>
            </div>
            <div class="d-flex align-items-center gap-3">
                <div class="dropdown">
                    <button class="btn btn-link text-decoration-none dropdown-toggle" type="button" data-bs-toggle="dropdown" style="color: var(--text-primary);">
                        <i class="fas fa-user-circle me-2"></i>
                        <?= htmlspecialchars($admin['first_name']) ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="/settings.php"><i class="fas fa-user me-2"></i> Мой профиль</a></li>
                        <li><hr class="dropdown-divider" style="border-color: var(--border-color);"></li>
                        <li><a class="dropdown-item" href="/admin/logout.php"><i class="fas fa-sign-out-alt me-2"></i> Выход</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- Content -->
        <div class="content">