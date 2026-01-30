<?php
$page_title = 'Мои сертификаты';
require_once __DIR__ . '/includes/init.php';
requireAuth();
$user = getCurrentUser();
$db = getDB();
$certificates = $db->fetchAll("
    SELECT c.*, r.name as route_name, r.name_en as route_name_en,
           up.completed_at
    FROM certificates c
    JOIN routes r ON c.route_id = r.id
    JOIN user_progress up ON c.progress_id = up.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
", [$user['id']]);
if (empty($certificates)) {
    error_log("No certificates found for user_id: " . $user['id']);
} else {
    error_log("Found " . count($certificates) . " certificates for user_id: " . $user['id']);
    foreach ($certificates as $cert) {
        error_log("Certificate ID: " . $cert['id'] . ", Path: " . $cert['file_path']);
    }
}
$grouped = [];
foreach ($certificates as $cert) {
    $file_path = __DIR__ . '/..' . $cert['file_path'];
    $file_exists = file_exists($file_path);
    if (!$file_exists) {
        $alt_paths = [
            ($_SERVER['DOCUMENT_ROOT'] ?? '') . $cert['file_path'],
            '/var/www/u3372144/data/www/questguiderf.ru' . $cert['file_path'],
            '/www/questguiderf.ru' . $cert['file_path'],
        ];
        foreach ($alt_paths as $alt_path) {
            if (!empty($alt_path) && file_exists($alt_path)) {
                $file_exists = true;
                error_log("Certificate file found at alternative path: $alt_path");
                break;
            }
        }
    }
    $grouped[$cert['progress_id']][$cert['language']] = $cert;
    $grouped[$cert['progress_id']][$cert['language']]['file_exists'] = $file_exists;
}
$completed_without_certs = $db->fetchAll("
    SELECT up.id as progress_id, up.completed_at,
           r.id as route_id, r.name as route_name, r.name_en as route_name_en,
           r.distance
    FROM user_progress up
    JOIN routes r ON up.route_id = r.id
    LEFT JOIN certificates c ON c.progress_id = up.id
    WHERE up.user_id = ? AND up.status = 'COMPLETED' AND c.id IS NULL
    ORDER BY up.completed_at DESC
", [$user['id']]);
$unique_progress_ids = [];
$completed_without_certs = array_filter($completed_without_certs, function($quest) use (&$unique_progress_ids) {
    if (in_array($quest['progress_id'], $unique_progress_ids)) {
        return false;
    }
    $unique_progress_ids[] = $quest['progress_id'];
    return true;
});
require_once __DIR__ . '/includes/header.php';
?>
<style>
@media (max-width: 768px) {
    .certificate-image-container {
        min-height: 250px !important;
        padding: 10px;
    }
    .certificate-image-container img {
        max-height: 300px !important;
    }
}
.certificate-card {
    transition: transform 0.2s;
}
.certificate-card:hover {
    transform: translateY(-5px);
}
</style>
<div class="container py-4">
    <h1 class="mb-4">🏆 <?= t('certificates') ?></h1>
    <?php
    if (isset($_GET['debug'])) {
        echo "<div class='alert alert-info'>";
        echo "<strong>Debug Info:</strong><br>";
        echo "Total certificates in DB: " . count($certificates) . "<br>";
        echo "Grouped certificates: " . count($grouped) . "<br>";
        echo "Completed without certs: " . count($completed_without_certs) . "<br>";
        echo "__DIR__: " . __DIR__ . "<br>";
        echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "<br>";
        if (!empty($certificates)) {
            echo "<br><strong>Certificates from DB:</strong><br>";
            foreach ($certificates as $cert) {
                $full_path = __DIR__ . '/..' . $cert['file_path'];
                $doc_root_path = ($_SERVER['DOCUMENT_ROOT'] ?? '') . $cert['file_path'];
                echo "ID: {$cert['id']}, Progress: {$cert['progress_id']}, Lang: {$cert['language']}<br>";
                echo "&nbsp;&nbsp;Path in DB: {$cert['file_path']}<br>";
                echo "&nbsp;&nbsp;Full path (__DIR__): $full_path - " . (file_exists($full_path) ? 'EXISTS' : 'NOT FOUND') . "<br>";
                echo "&nbsp;&nbsp;Doc root path: $doc_root_path - " . (file_exists($doc_root_path) ? 'EXISTS' : 'NOT FOUND') . "<br>";
            }
        }
        if (!empty($grouped)) {
            echo "<br><strong>Grouped certificates:</strong><br>";
            foreach ($grouped as $progress_id => $certs) {
                echo "Progress ID: $progress_id<br>";
                foreach ($certs as $lang => $cert) {
                    echo "&nbsp;&nbsp;Lang: $lang, Path: {$cert['file_path']}<br>";
                }
            }
        }
        echo "</div>";
    }
    ?>
    <?php if (empty($grouped) && empty($completed_without_certs)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <div class="mb-3" style="font-size: 4rem;">📜</div>
                <h3><?= t('no_certificates') ?></h3>
                <p class="text-muted"><?= t('complete_quest_for_certificate') ?></p>
                <a href="/routes.php" class="btn btn-primary"><?= t('view_routes') ?></a>
            </div>
        </div>
    <?php else: ?>
        <?php if (!empty($completed_without_certs)): ?>
        <div class="card mb-4 border-warning">
            <div class="card-header bg-warning bg-opacity-10">
                <h5 class="mb-0">📜 <?= t('create_certificates_for_completed') ?></h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($completed_without_certs as $quest): ?>
                    <div class="col-md-6">
                        <div class="d-flex justify-content-between align-items-center p-3 border rounded">
                            <div>
                                <strong><?= htmlspecialchars($quest['route_name']) ?></strong>
                                <br>
                                <small class="text-muted">
                                    <?= t('completed') ?>: <?= date('d.m.Y', strtotime($quest['completed_at'])) ?>
                                </small>
                            </div>
                            <button class="btn btn-primary btn-sm"
                                    onclick="createCertificates(<?= $quest['progress_id'] ?>, this)">
                                <span class="spinner-border spinner-border-sm d-none" role="status"></span>
                                <span class="btn-text"><?= t('create_certificate') ?></span>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($grouped)): ?>
        <div class="row g-4">
            <?php foreach ($grouped as $progress_id => $certs): ?>
                <?php
                $cert = $certs['ru'] ?? $certs['en'] ?? null;
                if (!$cert) continue;
                $ru_exists = false;
                $en_exists = false;
                if (isset($certs['ru'])) {
                    $paths_to_check = [
                        __DIR__ . '/..' . $certs['ru']['file_path'],
                        ($_SERVER['DOCUMENT_ROOT'] ?? '') . $certs['ru']['file_path'],
                        '/var/www/u3372144/data/www/questguiderf.ru' . $certs['ru']['file_path'],
                        '/www/questguiderf.ru' . $certs['ru']['file_path'],
                    ];
                    foreach ($paths_to_check as $path) {
                        if (!empty($path) && file_exists($path)) {
                            $ru_exists = true;
                            break;
                        }
                    }
                }
                if (isset($certs['en'])) {
                    $paths_to_check = [
                        __DIR__ . '/..' . $certs['en']['file_path'],
                        ($_SERVER['DOCUMENT_ROOT'] ?? '') . $certs['en']['file_path'],
                        '/var/www/u3372144/data/www/questguiderf.ru' . $certs['en']['file_path'],
                        '/www/questguiderf.ru' . $certs['en']['file_path'],
                    ];
                    foreach ($paths_to_check as $path) {
                        if (!empty($path) && file_exists($path)) {
                            $en_exists = true;
                            break;
                        }
                    }
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 certificate-card">
                        <div class="card-img-top position-relative certificate-image-container" style="min-height: 200px; background: #f5f5f5; display: flex; align-items: center; justify-content: center; padding: 15px;">
                            <?php if ($ru_exists): ?>
                                <img src="<?= htmlspecialchars($certs['ru']['file_path']) ?>"
                                     alt="Certificate"
                                     class="w-100"
                                     style="object-fit: contain; max-height: 400px; height: auto;"
                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center text-muted p-4\'>📜 Сертификат</div>'">
                            <?php elseif ($en_exists): ?>
                                <img src="<?= htmlspecialchars($certs['en']['file_path']) ?>"
                                     alt="Certificate"
                                     class="w-100"
                                     style="object-fit: contain; max-height: 400px; height: auto;"
                                     onerror="this.style.display='none'; this.parentElement.innerHTML='<div class=\'d-flex align-items-center justify-content-center text-muted p-4\'>📜 Сертификат</div>'">
                            <?php else: ?>
                                <div class="d-flex align-items-center justify-content-center text-muted p-4">
                                    📜 Сертификат
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($cert['route_name']) ?></h5>
                            <p class="card-text text-muted">
                                <small>
                                    📅 <?= date('d.m.Y', strtotime($cert['completed_at'])) ?>
                                </small>
                            </p>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-flex gap-2">
                                <?php if (isset($certs['ru'])): ?>
                                    <a href="<?= htmlspecialchars($certs['ru']['file_path']) ?>"
                                       class="btn btn-outline-primary btn-sm flex-grow-1"
                                       download
                                       title="Скачать сертификат на русском">
                                        <span style="font-size: 1.2em;">🇷🇺</span> <span class="d-none d-md-inline">RU</span>
                                    </a>
                                <?php endif; ?>
                                <?php if (isset($certs['en'])): ?>
                                    <a href="<?= htmlspecialchars($certs['en']['file_path']) ?>"
                                       class="btn btn-outline-primary btn-sm flex-grow-1"
                                       download
                                       title="Download certificate in English">
                                        <span style="font-size: 1.2em;">🇬🇧</span> <span class="d-none d-md-inline">EN</span>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
<script>
async function createCertificates(progressId, button) {
    const btnText = button.querySelector('.btn-text');
    const spinner = button.querySelector('.spinner-border');
    button.disabled = true;
    btnText.textContent = '<?= t('creating') ?>...';
    spinner.classList.remove('d-none');
    try {
        const formData = new FormData();
        formData.append('progress_id', progressId);
        const response = await fetch('/api/create_certificates.php', {
            method: 'POST',
            body: formData
        });
        if (!response.ok) {
            const text = await response.text();
            console.error('HTTP Error:', response.status, text);
            throw new Error('Ошибка сервера. Попробуйте позже.');
        }
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('Non-JSON response:', text);
            throw new Error('Сервер вернул неверный формат ответа. Проверьте логи сервера.');
        }
        let result;
        try {
            result = await response.json();
        } catch (e) {
            const text = await response.text();
            console.error('JSON parse error:', e, 'Response:', text);
            throw new Error('Ошибка при обработке ответа сервера.');
        }
        if (result.success) {
            btnText.textContent = '<?= t('created') ?>!';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
            console.log('Certificates created:', result.certificates);
            setTimeout(() => {
                window.location.href = window.location.href;
            }, 500);
        } else {
            alert('Ошибка: ' + (result.error || 'Не удалось создать сертификаты'));
            button.disabled = false;
            btnText.textContent = '<?= t('create_certificate') ?>';
            spinner.classList.add('d-none');
        }
    } catch (error) {
        console.error('Error:', error);
        let errorMsg = 'Ошибка при создании сертификатов';
        if (error.message) {
            errorMsg += ': ' + error.message;
        }
        alert(errorMsg);
        button.disabled = false;
        btnText.textContent = '<?= t('create_certificate') ?>';
        spinner.classList.add('d-none');
    }
}
</script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>