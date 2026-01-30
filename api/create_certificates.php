<?php
ob_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    return true;
});
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_clean();
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Fatal error occurred. Check server logs.']);
        exit;
    }
});
header('Content-Type: application/json');
define('APP_INIT', true);
try {
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/../includes/db.php';
    require_once __DIR__ . '/../includes/auth.php';
    require_once __DIR__ . '/../includes/generate_certificate.php';
    session_start();
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Initialization error: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Fatal initialization error: ' . $e->getMessage()]);
    exit;
}
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'error' => 'User not found']);
    exit;
}
$progress_id = isset($_POST['progress_id']) ? (int)$_POST['progress_id'] : 0;
if (!$progress_id) {
    echo json_encode(['success' => false, 'error' => 'Progress ID required']);
    exit;
}
$pdo = getDB()->getConnection();
$stmt = $pdo->prepare("SELECT * FROM user_progress WHERE id = ? AND user_id = ? AND status = 'COMPLETED'");
$stmt->execute([$progress_id, $user['id']]);
$progress = $stmt->fetch();
if (!$progress) {
    echo json_encode(['success' => false, 'error' => 'Progress not found']);
    exit;
}
$stmt = $pdo->prepare("SELECT * FROM certificates WHERE progress_id = ?");
$stmt->execute([$progress_id]);
$existing_certs = $stmt->fetchAll(PDO::FETCH_ASSOC);
$need_recreate = false;
if (!empty($existing_certs)) {
    foreach ($existing_certs as $cert) {
        $file_path = __DIR__ . '/..' . $cert['file_path'];
        if (!file_exists($file_path)) {
            $need_recreate = true;
            $del_stmt = $pdo->prepare("DELETE FROM certificates WHERE id = ?");
            $del_stmt->execute([$cert['id']]);
        }
    }
    if (!$need_recreate) {
        echo json_encode(['success' => false, 'error' => 'Certificates already exist']);
        exit;
    }
}
try {
    ob_clean();
    if (!function_exists('imagecreatefrompng')) {
        throw new Exception('GD library is not installed');
    }
    $result = createCertificates($progress_id);
    if ($result['ru'] || $result['en']) {
        echo json_encode([
            'success' => true,
            'certificates' => [
                'ru' => $result['ru'] ? $result['ru']['path'] : null,
                'en' => $result['en'] ? $result['en']['path'] : null
            ]
        ]);
    } else {
        $error_msg = 'Failed to generate certificates. Please check server configuration.';
        error_log("Certificate generation failed for progress_id: $progress_id");
        echo json_encode(['success' => false, 'error' => $error_msg]);
    }
} catch (Exception $e) {
    ob_clean();
    error_log("Exception in create_certificates.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} catch (Error $e) {
    ob_clean();
    error_log("Fatal error in create_certificates.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Fatal error: ' . $e->getMessage()]);
} catch (Throwable $e) {
    ob_clean();
    error_log("Throwable in create_certificates.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => 'Error: ' . $e->getMessage()]);
}