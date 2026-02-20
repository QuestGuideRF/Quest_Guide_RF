<?php
if (!defined('APP_INIT')) exit;
$i18n_ru = [
    'home' => 'Главная', 'routes' => 'Маршруты', 'photos' => 'Фото', 'bank' => 'Банк', 'achievements' => 'Достижения',
    'certificates' => 'Сертификаты', 'reviews' => 'Отзывы', 'settings' => 'Настройки', 'partner' => 'Партнёрка',
    'login' => 'Войти', 'logout' => 'Выйти', 'start_quest' => 'Начать квест', 'points' => 'Точек',
    'duration' => 'Длительность', 'price' => 'Цена', 'groshi' => 'грошей', 'completed' => 'Завершено',
    'in_progress' => 'В процессе', 'hint' => 'Подсказка', 'im_here' => 'Я на месте',
    'upload_photo' => 'Загрузить фото', 'skip' => 'Пропустить', 'next_point' => 'Следующая точка',
    'quest_completed' => 'Квест завершён!', 'balance' => 'Баланс', 'get_token' => 'Получить токен',
];
$i18n_en = [
    'home' => 'Home', 'routes' => 'Routes', 'photos' => 'Photos', 'bank' => 'Bank', 'achievements' => 'Achievements',
    'certificates' => 'Certificates', 'reviews' => 'Reviews', 'settings' => 'Settings', 'partner' => 'Partner',
    'login' => 'Login', 'logout' => 'Logout', 'start_quest' => 'Start quest', 'points' => 'Points',
    'duration' => 'Duration', 'price' => 'Price', 'groshi' => 'tokens', 'completed' => 'Completed',
    'in_progress' => 'In progress', 'hint' => 'Hint', 'im_here' => 'I\'m here',
    'upload_photo' => 'Upload photo', 'skip' => 'Skip', 'next_point' => 'Next point',
    'quest_completed' => 'Quest completed!', 'balance' => 'Balance', 'get_token' => 'Get token',
];
function t($key, $lang = null) {
    global $i18n_ru, $i18n_en;
    $lang = $lang ?? (function_exists('getCurrentLanguage') ? getCurrentLanguage() : 'ru');
    $arr = $lang === 'en' ? $i18n_en : $i18n_ru;
    return $arr[$key] ?? $key;
}
function getCurrentLanguage() {
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $u = getCurrentUser();
        if ($u && !empty($u['language'])) return $u['language'];
    }
    return $_SESSION['language'] ?? 'ru';
}
function setLanguage($lang) {
    $_SESSION['language'] = $lang;
    if (function_exists('isLoggedIn') && isLoggedIn()) {
        $u = getCurrentUser();
        if ($u && isset($u['id'])) {
            getDB()->query('UPDATE users SET language = ? WHERE id = ?', [$lang, $u['id']]);
        }
    }
}