<?php
if (!defined('APP_INIT')) {
    die('Direct access not allowed');
}
function generateCertificate($progress_id, $language = 'ru') {
    try {
        $pdo = getDB()->getConnection();
        $pdo->exec("SET NAMES utf8mb4");
        $stmt = $pdo->prepare("
            SELECT
                up.id as progress_id,
                up.started_at,
                up.completed_at,
                TIMESTAMPDIFF(MINUTE, up.started_at, up.completed_at) as minutes,
                u.id as user_id,
                u.first_name,
                u.last_name,
                r.id as route_id,
                r.name as route_name,
                r.name_en as route_name_en,
                r.distance
            FROM user_progress up
            JOIN users u ON up.user_id = u.id
            JOIN routes r ON up.route_id = r.id
            WHERE up.id = ? AND up.status = 'COMPLETED'
        ");
        $stmt->execute([$progress_id]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$data) {
            return false;
        }
        $template_path = __DIR__ . '/../assets/certificate_foto/certificate.png';
        if (!file_exists($template_path)) {
            return false;
        }
        $font_path = null;
        $font_paths = [
            __DIR__ . '/../assets/fonts/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            '/usr/share/fonts/dejavu/DejaVuSans.ttf',
            '/usr/share/fonts/TTF/DejaVuSans.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/Windows/Fonts/arial.ttf',
            '/Windows/Fonts/arialbd.ttf',
        ];
        foreach ($font_paths as $f) {
            if (file_exists($f)) {
                $font_path = $f;
                break;
            }
        }
        $use_ttf = ($font_path !== null && function_exists('imagettftext'));
        if (!function_exists('imagecreatefrompng')) {
            error_log("GD library not available");
            return false;
        }
        $image = @imagecreatefrompng($template_path);
        if (!$image) {
            error_log("Failed to load certificate template: $template_path");
            return false;
        }
        imagesavealpha($image, true);
        $dark = imagecolorallocate($image, 60, 50, 40);
        $gray = imagecolorallocate($image, 100, 90, 80);
        $w = imagesx($image);
        $h = imagesy($image);
        $cx = $w / 2;
        $user_name = trim($data['first_name'] . ' ' . $data['last_name']);
        if (empty(trim($user_name))) {
            $user_name = $language == 'ru' ? "Участник" : "Participant";
        }
        if ($language == 'en' && !empty($data['route_name_en'])) {
            $route_name = $data['route_name_en'];
        } else {
            $route_name = $data['route_name'] ?: ($language == 'ru' ? "Квест" : "Quest");
        }
        $mins = (int)$data['minutes'];
        $hrs = floor($mins / 60);
        $m = $mins % 60;
        if ($language == 'ru') {
            $time_text = "Время прохождения: " . ($hrs > 0 ? $hrs . " ч. " : "") . $m . " мин.";
            $dist = (float)($data['distance'] ?? 0);
            $distance_text = "Расстояние: " . number_format($dist, 1, '.', '') . " км";
        } else {
            $time_text = "Completion time: " . ($hrs > 0 ? $hrs . " h " : "") . $m . " min";
            $dist = (float)($data['distance'] ?? 0);
            $distance_text = "Distance: " . number_format($dist, 1, '.', '') . " km";
        }
        $y1 = $h * 0.32;
        $gap = $h * 0.08;
        $drawCenteredText = function($img, $text, $cx, $y, $color, $font_path, $size, $use_ttf) {
            if ($use_ttf && $font_path) {
                $box = @imagettfbbox($size, 0, $font_path, $text);
                if ($box !== false) {
                    $tw = abs($box[4] - $box[0]);
                    $x = (int)($cx - $tw/2);
                    @imagettftext($img, $size, 0, $x, (int)$y, $color, $font_path, $text);
                    return;
                }
            }
            $font_size = min(5, max(1, (int)($size / 10)));
            $char_width = imagefontwidth($font_size);
            $text_width = strlen($text) * $char_width;
            $x = (int)($cx - $text_width / 2);
            imagestring($img, $font_size, $x, (int)$y - imagefontheight($font_size), $text, $color);
        };
        try {
            $drawCenteredText($image, $user_name, $cx, $y1, $dark, $font_path, 48, $use_ttf);
            $drawCenteredText($image, $route_name, $cx, $y1 + $gap, $gray, $font_path, 36, $use_ttf);
            $drawCenteredText($image, $time_text, $cx, $y1 + $gap * 2, $gray, $font_path, 28, $use_ttf);
            $drawCenteredText($image, $distance_text, $cx, $y1 + $gap * 2.7, $gray, $font_path, 28, $use_ttf);
        } catch (Exception $e) {
            error_log("Exception in text rendering: " . $e->getMessage());
            imagedestroy($image);
            return false;
        }
        $save_dir = __DIR__ . '/../assets/certificate_foto/generated/' . $data['user_id'];
        if (!is_dir($save_dir)) {
            if (!mkdir($save_dir, 0755, true)) {
                error_log("Failed to create directory: $save_dir");
                imagedestroy($image);
                return false;
            }
        }
        $filename = 'cert_' . $progress_id . '_' . $language . '_' . time() . '.png';
        $save_path = $save_dir . '/' . $filename;
        $relative_path = '/assets/certificate_foto/generated/' . $data['user_id'] . '/' . $filename;
        if (!imagepng($image, $save_path)) {
            error_log("Failed to save certificate image: $save_path");
            imagedestroy($image);
            return false;
        }
        imagedestroy($image);
        return [
            'path' => $relative_path,
            'user_id' => $data['user_id'],
            'route_id' => $data['route_id'],
            'progress_id' => $progress_id,
            'language' => $language
        ];
    } catch (Exception $e) {
        error_log("Exception in generateCertificate: " . $e->getMessage());
        return false;
    } catch (Error $e) {
        error_log("Fatal error in generateCertificate: " . $e->getMessage());
        return false;
    }
}
function createCertificates($progress_id) {
    $pdo = getDB()->getConnection();
    try {
        $cert_ru = generateCertificate($progress_id, 'ru');
        $cert_en = generateCertificate($progress_id, 'en');
        if ($cert_ru) {
            $stmt = $pdo->prepare("INSERT INTO certificates (user_id, route_id, progress_id, language, file_path) VALUES (?, ?, ?, 'ru', ?)");
            $stmt->execute([$cert_ru['user_id'], $cert_ru['route_id'], $progress_id, $cert_ru['path']]);
            error_log("Certificate RU saved: user_id={$cert_ru['user_id']}, path={$cert_ru['path']}");
        } else {
            error_log("Certificate RU generation failed for progress_id: $progress_id");
        }
        if ($cert_en) {
            $stmt = $pdo->prepare("INSERT INTO certificates (user_id, route_id, progress_id, language, file_path) VALUES (?, ?, ?, 'en', ?)");
            $stmt->execute([$cert_en['user_id'], $cert_en['route_id'], $progress_id, $cert_en['path']]);
            error_log("Certificate EN saved: user_id={$cert_en['user_id']}, path={$cert_en['path']}");
        } else {
            error_log("Certificate EN generation failed for progress_id: $progress_id");
        }
        return ['ru' => $cert_ru, 'en' => $cert_en];
    } catch (Exception $e) {
        error_log("Error creating certificates: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        return ['ru' => false, 'en' => false];
    } catch (Error $e) {
        error_log("Fatal error creating certificates: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
        return ['ru' => false, 'en' => false];
    }
}