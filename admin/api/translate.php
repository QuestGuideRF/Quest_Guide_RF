<?php
require_once __DIR__ . '/../includes/auth.php';
header('Content-Type: application/json');
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
$input = json_decode(file_get_contents('php://input'), true);
$text = $input['text'] ?? '';
$from = $input['from'] ?? 'ru';
$to = $input['to'] ?? 'en';
if (empty($text)) {
    http_response_code(400);
    echo json_encode(['error' => 'Text required']);
    exit;
}
try {
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=$from&tl=$to&dt=t&q=" . urlencode($text);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        throw new Exception('Translation service unavailable');
    }
    $result = json_decode($response, true);
    if (!$result || !isset($result[0])) {
        throw new Exception('Invalid translation response');
    }
    $translated = '';
    foreach ($result[0] as $segment) {
        if (isset($segment[0])) {
            $translated .= $segment[0];
        }
    }
    echo json_encode(['success' => true, 'translated' => $translated]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}