<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$initData = $input['initData'] ?? '';
if (empty($initData)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Init data не предоставлен'
    ]);
    exit;
}
$validationResult = validateTelegramInitData($initData, BOT_TOKEN);
if (!$validationResult['valid']) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => $validationResult['error'] ?? 'Неверная подпись initData'
    ]);
    exit;
}
$userData = $validationResult['user'];
$telegramId = $userData['id'] ?? null;
if (!$telegramId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Данные пользователя не найдены в initData'
    ]);
    exit;
}
$authDate = $validationResult['auth_date'] ?? 0;
$currentTime = time();
if ($currentTime - $authDate > 86400) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Данные авторизации устарели'
    ]);
    exit;
}
$user = getDB()->fetch(
    'SELECT * FROM users WHERE telegram_id = ?',
    [$telegramId]
);
if (!$user) {
    $username = $userData['username'] ?? null;
    $firstName = $userData['first_name'] ?? null;
    $lastName = $userData['last_name'] ?? null;
    $languageCode = $userData['language_code'] ?? 'ru';
    $language = ($languageCode === 'en' || strpos($languageCode, 'en') === 0) ? 'en' : 'ru';
    getDB()->query(
        'INSERT INTO users (telegram_id, username, first_name, last_name, language, created_at)
         VALUES (?, ?, ?, ?, ?, NOW())',
        [$telegramId, $username, $firstName, $lastName, $language]
    );
    $user = getDB()->fetch(
        'SELECT * FROM users WHERE telegram_id = ?',
        [$telegramId]
    );
} else {
    getDB()->query(
        'UPDATE users SET
            username = ?,
            first_name = ?,
            last_name = ?,
            last_login = NOW()
         WHERE telegram_id = ?',
        [
            $userData['username'] ?? $user['username'],
            $userData['first_name'] ?? $user['first_name'],
            $userData['last_name'] ?? $user['last_name'],
            $telegramId
        ]
    );
    $user = getDB()->fetch(
        'SELECT * FROM users WHERE telegram_id = ?',
        [$telegramId]
    );
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
loginUser($user);
echo json_encode([
    'success' => true,
    'message' => 'Авторизация успешна',
    'user' => [
        'id' => $user['id'],
        'telegram_id' => $user['telegram_id'],
        'username' => $user['username']
    ]
]);