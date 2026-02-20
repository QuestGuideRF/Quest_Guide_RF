<?php
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/db.php';
function isAdminLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    $pdo = getDB()->getConnection();
    try {
        $stmt = $pdo->prepare("
            SELECT u.id, u.telegram_id, u.username, u.first_name, u.role
            FROM users u
<<<<<<< HEAD
            WHERE u.id = ? AND (u.role IN ('ADMIN', 'admin', 'MODERATOR', 'moderator'))
=======
            WHERE u.id = ? AND (u.role = 'ADMIN' OR u.role = 'admin')
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
        ");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();
        return $admin !== false;
    } catch (Exception $e) {
        error_log("isAdminLoggedIn error: " . $e->getMessage());
        return false;
    }
}
function getCurrentAdmin() {
    if (!isAdminLoggedIn()) {
        return null;
    }
    $pdo = getDB()->getConnection();
    $stmt = $pdo->prepare("
        SELECT u.id, u.telegram_id, u.username, u.first_name, u.last_name, u.photo_url, u.role
        FROM users u
        WHERE u.id = ?
    ");
    $stmt->execute([$_SESSION['admin_id']]);
    return $stmt->fetch();
}
function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /403.php?reason=not_authorized');
        exit;
    }
}
function loginAdminByToken($token) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (empty($token)) {
        error_log("Empty token provided");
        return false;
    }
    $pdo = getDB()->getConnection();
    try {
        $stmt = $pdo->prepare("
            SELECT us.telegram_id, us.is_used, us.expires_at, us.created_at
            FROM user_sessions us
            WHERE us.token = ?
            LIMIT 1
        ");
        $stmt->execute([$token]);
        $token_data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$token_data) {
            error_log("ADMIN LOGIN ERROR: Token not found in database. Token: " . substr($token, 0, 20) . "...");
            $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM user_sessions WHERE token LIKE '" . substr($token, 0, 10) . "%'");
            $similar = $stmt->fetch();
            error_log("ADMIN LOGIN DEBUG: Similar tokens count: " . ($similar['cnt'] ?? 0));
            return false;
        }
        error_log("ADMIN LOGIN DEBUG: Token found. Telegram ID: " . $token_data['telegram_id'] . ", Used: " . $token_data['is_used'] . ", Expires: " . $token_data['expires_at']);
        if ($token_data['is_used'] == 1) {
            error_log("ADMIN LOGIN ERROR: Token already used: " . substr($token, 0, 20) . "...");
            return false;
        }
        $stmt = $pdo->prepare("
            SELECT TIMESTAMPDIFF(SECOND, UTC_TIMESTAMP(), expires_at) as seconds_left
            FROM user_sessions
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        $time_check = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$time_check || $time_check['seconds_left'] < -120) {
            error_log("ADMIN LOGIN ERROR: Token expired. Expires: " . $token_data['expires_at'] . ", Seconds left: " . ($time_check['seconds_left'] ?? 'NULL') . ", UTC now: " . date('Y-m-d H:i:s', time()));
            return false;
        }
        error_log("ADMIN LOGIN DEBUG: Token valid. Seconds left: " . $time_check['seconds_left']);
        $stmt = $pdo->prepare("
            SELECT u.id, u.telegram_id, u.role, u.username, u.first_name
            FROM users u
            WHERE u.telegram_id = ?
            LIMIT 1
        ");
        $stmt->execute([$token_data['telegram_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            error_log("User not found for telegram_id: " . $token_data['telegram_id']);
            return false;
        }
        error_log("ADMIN LOGIN DEBUG: User found. ID: " . $user['id'] . ", Telegram ID: " . $user['telegram_id'] . ", Role: '" . $user['role'] . "'");
        $role = strtoupper(trim($user['role']));
        $is_admin = ($role === 'ADMIN');
        error_log("ADMIN LOGIN DEBUG: Role check. Original: '" . $user['role'] . "', Uppercase: '" . $role . "', Is admin by role: " . ($is_admin ? 'YES' : 'NO'));
        if (!$is_admin) {
            $admin_ids_str = '';
            if (defined('ADMIN_IDS')) {
                $admin_ids_str = ADMIN_IDS;
            }
            if (empty($admin_ids_str)) {
                $admin_ids_str = $_ENV['ADMIN_IDS'] ?? '';
            }
            if (empty($admin_ids_str)) {
                $admin_ids_str = getenv('ADMIN_IDS') ?: '';
            }
            if (empty($admin_ids_str)) {
                $env_file = __DIR__ . '/../../.env';
                if (file_exists($env_file)) {
                    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    foreach ($lines as $line) {
                        if (strpos(trim($line), 'ADMIN_IDS=') === 0) {
                            $admin_ids_str = trim(substr($line, strlen('ADMIN_IDS=')));
                            break;
                        }
                    }
                }
            }
            error_log("ADMIN LOGIN DEBUG: Checking ADMIN_IDS: " . ($admin_ids_str ?: 'empty') . " for telegram_id: " . $user['telegram_id']);
            if (!empty($admin_ids_str)) {
                $admin_ids = explode(',', $admin_ids_str);
                $admin_ids = array_map('trim', $admin_ids);
                $telegram_id_str = (string)$user['telegram_id'];
                if (in_array($telegram_id_str, $admin_ids)) {
                    $is_admin = true;
                    $stmt = $pdo->prepare("UPDATE users SET role = 'ADMIN' WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    error_log("Updated user role to ADMIN for telegram_id: " . $user['telegram_id']);
                } else {
                    error_log("Telegram ID " . $telegram_id_str . " not in ADMIN_IDS list: " . implode(', ', $admin_ids));
                }
            } else {
                error_log("ADMIN_IDS is empty in config");
            }
        }
<<<<<<< HEAD
        $is_moderator = ($role === 'MODERATOR');
        if (!$is_admin && !$is_moderator) {
            error_log("ADMIN LOGIN ERROR: User is not admin or moderator. Role: " . $user['role'] . ", Telegram ID: " . $user['telegram_id']);
=======
        if (!$is_admin) {
            error_log("ADMIN LOGIN ERROR: User is not admin. Role: " . $user['role'] . ", Telegram ID: " . $user['telegram_id']);
            error_log("ADMIN LOGIN DEBUG: Checking ADMIN_IDS from config...");
            error_log("ADMIN LOGIN DEBUG: ADMIN_IDS constant: " . (defined('ADMIN_IDS') ? ADMIN_IDS : 'NOT DEFINED'));
            error_log("ADMIN LOGIN DEBUG: ADMIN_IDS from ENV: " . ($_ENV['ADMIN_IDS'] ?? 'NOT SET'));
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
            return false;
        }
        error_log("ADMIN LOGIN SUCCESS: User is admin. ID: " . $user['id'] . ", Telegram ID: " . $user['telegram_id'] . ", Role: " . $user['role']);
        $stmt = $pdo->prepare("
            UPDATE user_sessions
            SET is_used = 1, used_at = NOW()
            WHERE token = ?
        ");
        $stmt->execute([$token]);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_telegram_id'] = $user['telegram_id'];
        $_SESSION['admin_login_time'] = time();
        $_SESSION['admin_logged_in'] = true;
        error_log("ADMIN LOGIN SUCCESS: Session created. Admin ID: " . $user['id'] . ", Telegram ID: " . $user['telegram_id']);
        return true;
    } catch (Exception $e) {
        error_log("Admin login error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}
function logoutAdmin() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_telegram_id']);
    unset($_SESSION['admin_login_time']);
    unset($_SESSION['admin_logged_in']);
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
function isSuperAdmin() {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    $superAdminIds = explode(',', getenv('SUPER_ADMIN_IDS') ?: '');
    return in_array($admin['telegram_id'], $superAdminIds);
<<<<<<< HEAD
}
function isModerator() {
    $admin = getCurrentAdmin();
    if (!$admin) {
        return false;
    }
    $role = strtoupper(trim($admin['role'] ?? ''));
    return $role === 'MODERATOR';
=======
>>>>>>> 2ed20ce8af442d6700b46589978e78c41bb0322c
}