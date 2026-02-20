<?php
$page_title = 'Квиз — вопросы по маршрутам';
if (!defined('APP_INIT')) {
    define('APP_INIT', true);
}
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/../includes/auth.php';
if (!isAdminLoggedIn()) {
    header('Location: /admin/login.php');
    exit;
}
require_once __DIR__ . '/../../includes/db.php';
$pdo = getDB()->getConnection();
$route_id = isset($_GET['route_id']) ? intval($_GET['route_id']) : 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'create') {
        $stmt = $pdo->prepare("INSERT INTO quiz_questions (route_id, question, question_en, option_a, option_a_en, option_b, option_b_en, option_c, option_c_en, option_d, option_d_en, correct_option, reward_amount, `order`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            intval($_POST['route_id']),
            $_POST['question'] ?? '',
            $_POST['question_en'] ?? null,
            $_POST['option_a'] ?? '',
            $_POST['option_a_en'] ?? null,
            $_POST['option_b'] ?? '',
            $_POST['option_b_en'] ?? null,
            $_POST['option_c'] ?? '',
            $_POST['option_c_en'] ?? null,
            $_POST['option_d'] ?? '',
            $_POST['option_d_en'] ?? null,
            $_POST['correct_option'] ?? 'a',
            floatval($_POST['reward_amount'] ?? 0),
            intval($_POST['order'] ?? 0),
        ]);
        header("Location: /admin/quiz/index.php?route_id=" . intval($_POST['route_id']) . "&saved=1");
        exit;
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM quiz_questions WHERE id = ?")->execute([intval($_POST['question_id'])]);
        header("Location: /admin/quiz/index.php?route_id=" . $route_id . "&deleted=1");
        exit;
    }
}
$routes = $pdo->query("SELECT id, name FROM routes ORDER BY name")->fetchAll();
$questions = [];
if ($route_id) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_questions WHERE route_id = ? ORDER BY `order`");
    $stmt->execute([$route_id]);
    $questions = $stmt->fetchAll();
}
require_once __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2><i class="fas fa-question-circle"></i> Квиз по маршрутам</h2>
</div>
<?php if (isset($_GET['saved'])): ?>
    <div class="alert alert-success alert-dismissible fade show"><i class="fas fa-check"></i> Вопрос сохранён <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if (isset($_GET['deleted'])): ?>
    <div class="alert alert-warning alert-dismissible fade show"><i class="fas fa-trash"></i> Вопрос удалён <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 align-items-end">
            <div class="flex-grow-1">
                <label class="form-label">Маршрут</label>
                <select name="route_id" class="form-select">
                    <option value="">— Выберите маршрут —</option>
                    <?php foreach ($routes as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $r['id'] == $route_id ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Показать</button>
        </form>
    </div>
</div>
<?php if ($route_id): ?>
    <div class="card mb-4">
        <div class="card-header"><h5 class="mb-0">Вопросы (<?= count($questions) ?>)</h5></div>
        <div class="card-body">
            <?php if (empty($questions)): ?>
                <p class="text-muted">Нет вопросов для этого маршрута. Добавьте ниже.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>№</th>
                                <th>Вопрос</th>
                                <th>Прав. ответ</th>
                                <th>Награда</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($questions as $q): ?>
                                <tr>
                                    <td><?= $q['order'] ?></td>
                                    <td><?= htmlspecialchars(mb_strlen($q['question']) > 80 ? mb_substr($q['question'], 0, 80) . '...' : $q['question']) ?></td>
                                    <td><span class="badge bg-success"><?= strtoupper($q['correct_option']) ?></span></td>
                                    <td><?= $q['reward_amount'] ?> грошей</td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Удалить вопрос?')">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                            <button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0"><i class="fas fa-plus"></i> Добавить вопрос</h5>
            <button type="button" class="btn btn-outline-primary" id="translateAllQuizBtn" onclick="translateAllQuiz()">
                <i class="fas fa-language me-1"></i> Перевести всё (RU → EN)
            </button>
        </div>
        <div class="card-body">
            <form method="POST" id="quizQuestionForm">
                <input type="hidden" name="action" value="create">
                <input type="hidden" name="route_id" value="<?= $route_id ?>">
                <h6 class="text-muted mb-2 mt-2">🇷🇺 Русский</h6>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Вопрос (RU) *</label>
                        <textarea name="question" id="question" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><label class="form-label">Вариант A (RU) *</label><input type="text" name="option_a" id="option_a" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Вариант B (RU) *</label><input type="text" name="option_b" id="option_b" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Вариант C (RU) *</label><input type="text" name="option_c" id="option_c" class="form-control" required></div>
                    <div class="col-md-3"><label class="form-label">Вариант D (RU) *</label><input type="text" name="option_d" id="option_d" class="form-control" required></div>
                </div>
                <h6 class="text-muted mb-2 mt-4">🇬🇧 English</h6>
                <div class="row mb-3">
                    <div class="col-12">
                        <label class="form-label">Question (EN)</label>
                        <textarea name="question_en" id="question_en" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3"><label class="form-label">Option A (EN)</label><input type="text" name="option_a_en" id="option_a_en" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Option B (EN)</label><input type="text" name="option_b_en" id="option_b_en" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Option C (EN)</label><input type="text" name="option_c_en" id="option_c_en" class="form-control"></div>
                    <div class="col-md-3"><label class="form-label">Option D (EN)</label><input type="text" name="option_d_en" id="option_d_en" class="form-control"></div>
                </div>
                <hr class="my-4">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Правильный ответ *</label>
                        <select name="correct_option" class="form-select">
                            <option value="a">A</option><option value="b">B</option><option value="c">C</option><option value="d">D</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Награда (гроши)</label>
                        <input type="number" name="reward_amount" class="form-control" value="2" min="0" step="0.01">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Порядок</label>
                        <input type="number" name="order" class="form-control" value="<?= count($questions) + 1 ?>" min="0">
                    </div>
                </div>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Сохранить вопрос</button>
            </form>
        </div>
    </div>
    <script>
    function translateQuizField(fromId, toId) {
        var fromField = document.getElementById(fromId);
        var toField = document.getElementById(toId);
        if (!fromField || !toField) return Promise.resolve(false);
        var text = fromField.value.trim();
        if (!text) return Promise.resolve(false);
        return fetch('/admin/api/translate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({text: text, from: 'ru', to: 'en'})
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                toField.value = data.translated;
                return true;
            }
            return false;
        })
        .catch(function() { return false; });
    }
    async function translateAllQuiz() {
        var btn = document.getElementById('translateAllQuizBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Перевожу...';
        var pairs = [
            ['question', 'question_en'],
            ['option_a', 'option_a_en'],
            ['option_b', 'option_b_en'],
            ['option_c', 'option_c_en'],
            ['option_d', 'option_d_en']
        ];
        var translated = 0;
        for (var i = 0; i < pairs.length; i++) {
            var fromEl = document.getElementById(pairs[i][0]);
            if (fromEl && fromEl.value.trim()) {
                var ok = await translateQuizField(pairs[i][0], pairs[i][1]);
                if (ok) translated++;
                await new Promise(function(r) { setTimeout(r, 350); });
            }
        }
        btn.innerHTML = '<i class="fas fa-check text-success me-1"></i> Готово (' + translated + ')';
        setTimeout(function() {
            btn.innerHTML = '<i class="fas fa-language me-1"></i> Перевести всё (RU → EN)';
            btn.disabled = false;
        }, 2000);
    }
    </script>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>