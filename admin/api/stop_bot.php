<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}
try {
    $project_root = dirname(dirname(dirname(__FILE__)));
    $stop_script = $project_root . '/bot/stop.sh';
    if (!file_exists($stop_script)) {
        exec('killall -9 python 2>/dev/null', $kill_output, $kill_return);
        exec('pkill -9 -f "bot.main" 2>/dev/null', $pkill_output, $pkill_return);
        if ($kill_return === 0 || $pkill_return === 0) {
            echo json_encode(['success' => true, 'message' => 'Бот остановлен']);
            exit;
        }
        throw new Exception('Скрипт остановки не найден и не удалось остановить процессы напрямую.');
    }
    chmod($stop_script, 0755);
    $command = 'bash ' . escapeshellarg($stop_script) . ' > /dev/null 2>&1 &';
    exec($command, $output, $return_var);
    exec('killall -9 python 2>/dev/null', $kill_output, $kill_return);
    exec('pkill -9 -f "bot.main" 2>/dev/null', $pkill_output, $pkill_return);
    echo json_encode(['success' => true, 'message' => 'Бот остановлен']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}