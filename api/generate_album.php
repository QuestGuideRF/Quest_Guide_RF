<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
define('APP_INIT', true);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/i18n.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$tfpdf_path = __DIR__ . '/../lib/tfpdf/tfpdf.php';
if (!file_exists($tfpdf_path)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    die("Библиотека tFPDF не найдена (lib/tfpdf/tfpdf.php). Проверьте, что папка lib/tfpdf загружена на сервер.");
}
require_once $tfpdf_path;
$pdo = getDB()->getConnection();
$is_preview = isset($_GET['preview']);
$progress_id = isset($_GET['progress_id']) ? (int)$_GET['progress_id'] : 0;
if (!$progress_id && isset($_GET['route_id']) && $is_preview) {
    $route_id_param = (int)$_GET['route_id'];
    $stmt = $pdo->prepare("SELECT id FROM user_progress WHERE route_id = ? AND status = 'COMPLETED' ORDER BY completed_at DESC LIMIT 1");
    $stmt->execute([$route_id_param]);
    $row = $stmt->fetch();
    if ($row) {
        $progress_id = (int)$row['id'];
    } else {
        http_response_code(404);
        die('No completed progress found for this route.');
    }
}
if (!$progress_id) {
    http_response_code(400);
    die('progress_id is required');
}
$stmt = $pdo->prepare("
    SELECT
        up.id as progress_id,
        up.user_id,
        up.route_id,
        up.started_at,
        up.completed_at,
        up.points_completed,
        TIMESTAMPDIFF(MINUTE, up.started_at, up.completed_at) as minutes,
        u.first_name,
        u.last_name,
        u.language as user_language,
        r.name as route_name,
        r.name_en as route_name_en,
        r.distance,
        r.estimated_duration,
        c.name as city_name,
        c.name_en as city_name_en
    FROM user_progress up
    JOIN users u ON up.user_id = u.id
    JOIN routes r ON up.route_id = r.id
    LEFT JOIN cities c ON r.city_id = c.id
    WHERE up.id = ? AND up.status = 'COMPLETED'
");
$stmt->execute([$progress_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$data) {
    http_response_code(404);
    die('Progress not found or not completed.');
}
if (!$is_preview) {
    if (!isLoggedIn()) {
        http_response_code(401);
        die('Unauthorized');
    }
    $current_user = getCurrentUser();
    if (!$current_user || (int)$current_user['id'] !== (int)$data['user_id']) {
        http_response_code(403);
        die('Forbidden');
    }
}
$lang = $data['user_language'] ?: 'ru';
$route_id = (int)$data['route_id'];
$user_id = (int)$data['user_id'];
$default_template = [
    'page_orientation' => 'landscape',
    'photo_position' => 'right',
    'show_point_name' => true,
    'show_fact_text' => true,
    'show_photo_date' => true,
    'show_address' => false,
    'custom_caption' => '',
    'font_size' => 14,
    'show_page_numbers' => true,
    'cover_title' => '',
    'cover_subtitle' => '',
    'cover_background_path' => '',
    'show_certificate_on_first_page' => false,
];
$template = $default_template;
try {
    $stmt = $pdo->prepare("SELECT template_json FROM album_templates WHERE route_id = ?");
    $stmt->execute([$route_id]);
    $tpl_row = $stmt->fetch();
    if ($tpl_row && $tpl_row['template_json']) {
        $parsed = json_decode($tpl_row['template_json'], true);
        if (is_array($parsed)) {
            $template = array_merge($default_template, $parsed);
        }
    }
} catch (Exception $e) {
}
$stmt = $pdo->prepare("
    SELECT
        p.id, p.order, p.name, p.name_en, p.address,
        p.fact_text, p.fact_text_en,
        p.latitude, p.longitude,
        uph.file_path as photo_path,
        uph.created_at as photo_date
    FROM points p
    LEFT JOIN (
        SELECT point_id, file_path, created_at
        FROM user_photos
        WHERE user_id = ?
    ) uph ON p.id = uph.point_id
    WHERE p.route_id = ?
    ORDER BY p.`order` ASC
");
$stmt->execute([$user_id, $route_id]);
$points = $stmt->fetchAll(PDO::FETCH_ASSOC);
$points_with_photos = array_filter($points, function ($pt) {
    return !empty($pt['photo_path']);
});
function loc($row, $field, $lang) {
    if ($lang === 'en' && !empty($row[$field . '_en'])) {
        return $row[$field . '_en'];
    }
    return $row[$field] ?? '';
}
function strip_emoji_for_pdf($text) {
    if ($text === null || $text === '') return $text;
    if (!is_string($text)) return $text;
    $cleaned = preg_replace('/[\x{1F300}-\x{1F9FF}\x{2600}-\x{26FF}\x{2700}-\x{27BF}\x{1F600}-\x{1F64F}\x{1F680}-\x{1F6FF}\x{1F1E0}-\x{1F1FF}\x{1F900}-\x{1F9FF}\x{1F000}-\x{1F02F}\x{1F0A0}-\x{1F0FF}\x{1F200}-\x{1F2FF}]/u', '', $text);
    return preg_replace('/\s+/u', ' ', trim($cleaned));
}
$route_name = loc($data, 'route_name', $lang);
$city_name = loc($data, 'city_name', $lang);
$user_name = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));
if (empty(trim($user_name))) {
    $user_name = $lang === 'ru' ? 'Участник' : 'Participant';
}
$mins = (int)$data['minutes'];
$hrs = floor($mins / 60);
$m = $mins % 60;
if ($lang === 'ru') {
    $time_text = ($hrs > 0 ? $hrs . ' ч ' : '') . $m . ' мин';
} else {
    $time_text = ($hrs > 0 ? $hrs . ' h ' : '') . $m . ' min';
}
$completed_date = $data['completed_at'] ? date('d.m.Y', strtotime($data['completed_at'])) : '';
$started_date = $data['started_at'] ? date('d.m.Y', strtotime($data['started_at'])) : '';
$cert_path = null;
try {
    $cert_lang = $lang;
    $stmt = $pdo->prepare("SELECT file_path FROM certificates WHERE progress_id = ? AND language = ? LIMIT 1");
    $stmt->execute([$progress_id, $cert_lang]);
    $cert_row = $stmt->fetch();
    if ($cert_row) {
        $full_cert = $_SERVER['DOCUMENT_ROOT'] . $cert_row['file_path'];
        if (!file_exists($full_cert)) {
            $full_cert = __DIR__ . '/..' . $cert_row['file_path'];
        }
        if (file_exists($full_cert)) {
            $cert_path = $full_cert;
        }
    }
} catch (Exception $e) {
}
$orientation = ($template['page_orientation'] === 'portrait') ? 'P' : 'L';
$fontname = 'DejaVu';
$tfpdf_font_path = __DIR__ . '/../lib/tfpdf/font';
if (!is_dir($tfpdf_font_path . '/unifont')) {
    http_response_code(500);
    die('Шрифты tFPDF не найдены: lib/tfpdf/font/unifont/');
}
if (!defined('FPDF_FONTPATH')) {
    define('FPDF_FONTPATH', $tfpdf_font_path . '/');
}
$pdf = new tFPDF($orientation, 'mm', 'A4');
$pdf->SetCreator('QuestGuideRF', true);
$pdf->SetAuthor($user_name, true);
$pdf->SetTitle($route_name . ' — ' . ($lang === 'ru' ? 'Фотоальбом' : 'Photo Album'), true);
$pdf->SetAutoPageBreak(false, 0);
$pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
$pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
$pdf->AddFont('DejaVu', 'I', 'DejaVuSans-Oblique.ttf', true);
$page_w = ($orientation === 'L') ? 297 : 210;
$page_h = ($orientation === 'L') ? 210 : 297;
$cover_bg_path = null;
if (!empty($template['cover_background_path'])) {
    $bg_paths = [
        $_SERVER['DOCUMENT_ROOT'] . $template['cover_background_path'],
        __DIR__ . '/..' . $template['cover_background_path'],
    ];
    foreach ($bg_paths as $bp) {
        if (file_exists($bp)) {
            $cover_bg_path = $bp;
            break;
        }
    }
}
$cert_first = !empty($template['show_certificate_on_first_page']) && $cert_path;
if ($cert_first) {
    $pdf->AddPage($orientation);
    $pdf->SetFillColor(252, 250, 245);
    $pdf->Rect(0, 0, $page_w, $page_h, 'F');
    $margin = 15;
    $img_max_w = $page_w - $margin * 2;
    $img_max_h = $page_h - $margin * 2;
    $pdf->Image($cert_path, $margin, $margin, $img_max_w, $img_max_h);
}
$pdf->AddPage($orientation);
if ($cover_bg_path) {
    $pdf->Image($cover_bg_path, 0, 0, $page_w, $page_h);
} else {
    $pdf->SetFillColor(252, 250, 245);
    $pdf->Rect(0, 0, $page_w, $page_h, 'F');
}
$pdf->SetDrawColor(180, 160, 130);
$pdf->SetLineWidth(0.8);
$pdf->Rect(5, 5, $page_w - 10, $page_h - 10);
$pdf->SetLineWidth(0.3);
$pdf->Rect(8, 8, $page_w - 16, $page_h - 16);
$center_x = $page_w / 2;
$center_y = $page_h / 2;
$text_dark = $cover_bg_path;
$c_title = $text_dark ? [40, 40, 40] : [80, 65, 50];
$c_sub = $text_dark ? [50, 50, 50] : [100, 80, 60];
$c_muted = $text_dark ? [60, 60, 60] : [140, 120, 100];
$c_meta = $text_dark ? [70, 70, 70] : [120, 100, 80];
$c_small = $text_dark ? [80, 80, 80] : [150, 135, 120];
$cover_title = strip_emoji_for_pdf(!empty($template['cover_title']) ? $template['cover_title'] : ($lang === 'ru' ? 'Фотоальбом' : 'Photo Album'));
$pdf->SetFont($fontname, 'B', 28);
$pdf->SetTextColor($c_title[0], $c_title[1], $c_title[2]);
$pdf->SetXY(20, $center_y - 50);
$pdf->Cell($page_w - 40, 15, $cover_title, 0, 1, 'C');
$pdf->SetFont($fontname, 'B', 22);
$pdf->SetTextColor($c_sub[0], $c_sub[1], $c_sub[2]);
$pdf->SetXY(20, $center_y - 30);
$pdf->Cell($page_w - 40, 12, strip_emoji_for_pdf($route_name), 0, 1, 'C');
$cover_subtitle = strip_emoji_for_pdf(!empty($template['cover_subtitle']) ? $template['cover_subtitle'] : $city_name);
if ($cover_subtitle) {
    $pdf->SetFont($fontname, '', 16);
    $pdf->SetTextColor($c_muted[0], $c_muted[1], $c_muted[2]);
    $pdf->SetXY(20, $center_y - 14);
    $pdf->Cell($page_w - 40, 10, $cover_subtitle, 0, 1, 'C');
}
$pdf->SetFont($fontname, '', 14);
$pdf->SetTextColor($c_meta[0], $c_meta[1], $c_meta[2]);
$pdf->SetXY(20, $center_y + 10);
$pdf->Cell($page_w - 40, 8, strip_emoji_for_pdf($user_name), 0, 1, 'C');
$date_line = ($lang === 'ru' ? 'Дата прохождения: ' : 'Completion date: ') . $completed_date;
$pdf->SetFont($fontname, '', 12);
$pdf->SetTextColor($c_small[0], $c_small[1], $c_small[2]);
$pdf->SetXY(20, $center_y + 22);
$pdf->Cell($page_w - 40, 7, $date_line, 0, 1, 'C');
$time_line = ($lang === 'ru' ? 'Время: ' : 'Time: ') . $time_text;
$pdf->SetXY(20, $center_y + 30);
$pdf->Cell($page_w - 40, 7, $time_line, 0, 1, 'C');
$points_line = ($lang === 'ru' ? 'Точек: ' : 'Points: ') . count($points_with_photos);
$pdf->SetXY(20, $center_y + 38);
$pdf->Cell($page_w - 40, 7, $points_line, 0, 1, 'C');
if ($cert_path && !$cert_first) {
    $pdf->AddPage($orientation);
    $pdf->SetFillColor(252, 250, 245);
    $pdf->Rect(0, 0, $page_w, $page_h, 'F');
    $margin = 15;
    $img_max_w = $page_w - $margin * 2;
    $img_max_h = $page_h - $margin * 2;
    $pdf->Image($cert_path, $margin, $margin, $img_max_w, $img_max_h);
}
$page_num = 0;
foreach ($points_with_photos as $point) {
    $page_num++;
    $pdf->AddPage($orientation);
    $pdf->SetFillColor(252, 250, 245);
    $pdf->Rect(0, 0, $page_w, $page_h, 'F');
    $pdf->SetDrawColor(200, 190, 175);
    $pdf->SetLineWidth(0.4);
    $pdf->Rect(4, 4, $page_w - 8, $page_h - 8);
    $photo_on_right = ($template['photo_position'] !== 'left');
    $half_w = ($page_w - 30) / 2;
    $margin_x = 15;
    $margin_y = 18;
    $text_x = $photo_on_right ? $margin_x : ($margin_x + $half_w + 5);
    $photo_x = $photo_on_right ? ($margin_x + $half_w + 5) : $margin_x;
    $content_h = $page_h - $margin_y * 2;
    $photo_full_path = null;
    $possible_paths = [
        $_SERVER['DOCUMENT_ROOT'] . '/' . ltrim($point['photo_path'], '/'),
        __DIR__ . '/../' . ltrim($point['photo_path'], '/'),
        $point['photo_path'],
    ];
    foreach ($possible_paths as $pp) {
        if (file_exists($pp)) {
            $photo_full_path = $pp;
            break;
        }
    }
    if ($photo_full_path) {
        $frame_padding = 3;
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(190, 180, 165);
        $pdf->SetLineWidth(0.3);
        $pdf->Rect(
            $photo_x - $frame_padding,
            $margin_y - $frame_padding,
            $half_w + $frame_padding * 2,
            $content_h + $frame_padding * 2,
            'DF'
        );
        $pdf->Image($photo_full_path, $photo_x, $margin_y, $half_w, $content_h);
    }
    $text_y = $margin_y + 5;
    $font_size = (int)$template['font_size'] ?: 14;
    $pdf->SetFont($fontname, '', 10);
    $pdf->SetTextColor(180, 165, 145);
    $num_label = ($lang === 'ru' ? 'Точка' : 'Point') . ' ' . $point['order'];
    $pdf->SetXY($text_x, $text_y);
    $pdf->Cell($half_w, 6, $num_label, 0, 1, 'L');
    $text_y += 10;
    if ($template['show_point_name']) {
        $point_name = strip_emoji_for_pdf(loc($point, 'name', $lang));
        $pdf->SetFont($fontname, 'B', $font_size + 4);
        $pdf->SetTextColor(70, 55, 40);
        $pdf->SetXY($text_x, $text_y);
        $pdf->MultiCell($half_w, 8, $point_name, 0, 'L', false);
        $text_y = $pdf->GetY() + 5;
    }
    $pdf->SetDrawColor(200, 190, 175);
    $pdf->SetLineWidth(0.2);
    $pdf->Line($text_x, $text_y, $text_x + $half_w * 0.6, $text_y);
    $text_y += 6;
    if ($template['show_address'] && !empty($point['address'])) {
        $pdf->SetFont($fontname, '', $font_size - 2);
        $pdf->SetTextColor(140, 125, 110);
        $pdf->SetXY($text_x, $text_y);
        $addr_icon = ($lang === 'ru') ? 'Адрес: ' : 'Address: ';
        $pdf->MultiCell($half_w, 6, $addr_icon . strip_emoji_for_pdf($point['address']), 0, 'L', false);
        $text_y = $pdf->GetY() + 4;
    }
    if ($template['show_photo_date'] && !empty($point['photo_date'])) {
        $pdf->SetFont($fontname, '', $font_size - 2);
        $pdf->SetTextColor(150, 135, 120);
        $pdf->SetXY($text_x, $text_y);
        $date_label = ($lang === 'ru') ? 'Фото: ' : 'Photo: ';
        $pdf->Cell($half_w, 6, $date_label . date('d.m.Y, H:i', strtotime($point['photo_date'])), 0, 1, 'L');
        $text_y += 10;
    }
    if ($template['show_fact_text']) {
        $fact = strip_emoji_for_pdf(loc($point, 'fact_text', $lang));
        if (!empty($fact)) {
            $pdf->SetFont($fontname, 'I', $font_size - 1);
            $pdf->SetTextColor(100, 85, 70);
            $pdf->SetXY($text_x, $text_y);
            $max_chars = (int)(($page_h - $margin_y - $text_y - 20) / 6 * 50);
            if ($max_chars > 0 && mb_strlen($fact, 'UTF-8') > $max_chars) {
                $fact = mb_substr($fact, 0, $max_chars - 3, 'UTF-8') . '...';
            }
            $pdf->MultiCell($half_w, 6, $fact, 0, 'L', false);
            $text_y = $pdf->GetY() + 4;
        }
    }
    if (!empty($template['custom_caption'])) {
        $pdf->SetFont($fontname, '', $font_size - 2);
        $pdf->SetTextColor(160, 145, 130);
        $pdf->SetXY($text_x, $text_y);
        $pdf->MultiCell($half_w, 5, strip_emoji_for_pdf($template['custom_caption']), 0, 'L', false);
    }
    if ($template['show_page_numbers']) {
        $pdf->SetFont($fontname, '', 9);
        $pdf->SetTextColor(180, 170, 155);
        $pdf->SetXY(0, $page_h - 12);
        $pdf->Cell($page_w, 6, $page_num, 0, 0, 'C');
    }
}
$filename = 'album_' . $route_id . '_' . $progress_id . '.pdf';
if ($is_preview) {
    $pdf->Output($filename, 'I');
} else {
    $pdf->Output($filename, 'D');
}