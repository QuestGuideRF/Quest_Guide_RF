<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
$pdo = getDB()->getConnection();
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
if (isModerator()) {
    header('Location: /admin/dashboard.php?error=no_access');
    exit;
}
$page_title = 'Настройки';
require_once __DIR__ . '/includes/header.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST['settings'] as $key => $value) {
            $stmt = $pdo->prepare("
                INSERT INTO system_settings (`key`, `value`)
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE `value` = ?
            ");
            $stmt->execute([$key, $value, $value]);
        }
        $success = 'Настройки успешно сохранены';
    } catch (Exception $e) {
        $error = 'Ошибка при сохранении: ' . $e->getMessage();
    }
}
$stmt = $pdo->query("SELECT * FROM system_settings");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['key']] = $row['value'];
}
?>
<?php if (isset($success)): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
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
                <h5><i class="fas fa-cog me-2"></i>Системные настройки</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <h6 class="mb-3">Уведомления</h6>
                    <div class="mb-3">
                        <input type="hidden" name="settings[restart_notifications_enabled]" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="settings[restart_notifications_enabled]"
                                   value="1" id="restart_notifications"
                                   <?= ($settings['restart_notifications_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="restart_notifications">
                                Уведомления о перезапуске бота
                            </label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <input type="hidden" name="settings[payment_notifications_enabled]" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="settings[payment_notifications_enabled]"
                                   value="1" id="payment_notifications"
                                   <?= ($settings['payment_notifications_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="payment_notifications">
                                Уведомления о пополнении баланса пользователями (в Telegram админам)
                            </label>
                        </div>
                        <small class="text-muted">При включении админы получают в боте сообщение о каждом успешном пополнении (ЮKassa, Stars).</small>
                    </div>
                    <div class="mb-3">
                        <input type="hidden" name="settings[channel_stats_enabled]" value="0">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox"
                                   name="settings[channel_stats_enabled]"
                                   value="1" id="channel_stats_enabled"
                                   <?= ($settings['channel_stats_enabled'] ?? '1') == '1' ? 'checked' : '' ?>>
                            <label class="form-check-label" for="channel_stats_enabled">
                                Ежедневная отправка статистики канала админам в Telegram
                            </label>
                        </div>
                        <small class="text-muted">При включении бот раз в сутки присылает админам число подписчиков канала и изменение за сутки.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Время отправки статистики канала (МСК)</label>
                        <input type="time" name="settings[channel_stats_time]" class="form-control" style="max-width: 8rem;"
                               value="<?= htmlspecialchars(substr($settings['channel_stats_time'] ?? '08:00', 0, 5)) ?>">
                        <small class="text-muted">В какое время по Москве отправлять отчёт (например 08:00).</small>
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-3">Настройки по умолчанию для маршрутов</h6>
                    <div class="mb-3">
                        <label class="form-label">Цена по умолчанию (гроши)</label>
                        <input type="number" name="settings[default_route_price]"
                               class="form-control" value="<?= $settings['default_route_price'] ?? '399' ?>" min="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Длительность по умолчанию (минуты)</label>
                        <input type="number" name="settings[default_route_duration]"
                               class="form-control" value="<?= $settings['default_route_duration'] ?? '60' ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Максимум подсказок по умолчанию</label>
                        <input type="number" name="settings[default_max_hints]"
                               class="form-control" value="<?= $settings['default_max_hints'] ?? '3' ?>" min="0" max="10">
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-3">Шаблоны текстов</h6>
                    <div class="mb-3">
                        <label class="form-label">Шаблон описания маршрута</label>
                        <textarea name="settings[route_description_template]" class="form-control" rows="3"><?= htmlspecialchars($settings['route_description_template'] ?? 'Увлекательный маршрут по историческим местам города.') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Шаблон текста задания для точки</label>
                        <textarea name="settings[point_task_template]" class="form-control" rows="2"><?= htmlspecialchars($settings['point_task_template'] ?? 'Найдите указанное место и сделайте фото.') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Шаблон текста факта для точки</label>
                        <textarea name="settings[point_fact_template]" class="form-control" rows="2"><?= htmlspecialchars($settings['point_fact_template'] ?? 'Интересный факт об этом месте.') ?></textarea>
                    </div>
                    <hr class="my-4">
                    <h6 class="mb-3">Безопасность</h6>
                    <div class="mb-3">
                        <label class="form-label">Максимум попыток входа</label>
                        <input type="number" name="settings[max_login_attempts]"
                               class="form-control" value="<?= $settings['max_login_attempts'] ?? '5' ?>" min="1">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Время жизни токена (минуты)</label>
                        <input type="number" name="settings[token_lifetime_minutes]"
                               class="form-control" value="<?= $settings['token_lifetime_minutes'] ?? '10' ?>" min="5">
                    </div>
                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Сохранить настройки
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header bg-danger text-white">
                <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Пранк</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Действия, которые необратимо изменят систему.</p>
                <button class="btn btn-outline-danger" onclick="clearCache()">
                    <i class="fas fa-trash me-2"></i>Очистить кеш
                </button>
                <button class="btn btn-outline-danger ms-2" onclick="clearSessions()">
                    <i class="fas fa-sign-out-alt me-2"></i>Сбросить все сессии
                </button>
                <button class="btn btn-outline-danger ms-2" onclick="stopBot()">
                    <i class="fas fa-stop me-2"></i>Выключить бота
                </button>
            </div>
        </div>
    </div>
</div>
<script>
function clearCache() {
    if (confirm('Очистить весь кеш? Это может временно замедлить работу системы.')) {
        fetch('/admin/api/clear_cache.php', {method: 'POST'})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification('Кеш успешно очищен', 'success');
                }
            });
    }
}
function clearSessions() {
    if (confirm('Сбросить все сессии? Все пользователи будут разлогинены.')) {
        fetch('/admin/api/clear_sessions.php', {method: 'POST'})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification('Сессии сброшены', 'success');
                }
            });
    }
}
function stopBot() {
    if (confirm('Вы уверены, что хотите выключить бота? Бот перестанет отвечать на сообщения.')) {
        fetch('/admin/api/stop_bot.php', {method: 'POST'})
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showNotification('Бот успешно остановлен', 'success');
                } else {
                    showNotification('Ошибка: ' + (data.error || 'Не удалось остановить бота'), 'danger');
                }
            })
            .catch(error => {
                showNotification('Ошибка при остановке бота: ' + error.message, 'danger');
            });
    }
}
function showNotification(message, type) {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} alert-dismissible fade show`;
    alert.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.querySelector('.content').insertBefore(alert, document.querySelector('.content').firstChild);
    setTimeout(() => alert.remove(), 5000);
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>