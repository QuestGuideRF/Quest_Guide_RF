<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/i18n.php';
$current_lang = getCurrentLanguage();
$page_title = t('reviews_title');
$page_description = $current_lang === 'en'
    ? "User reviews of QuestGuideRF quest-excursions"
    : "Отзывы пользователей о квест-экскурсиях QuestGuideRF";
$route_id = isset($_GET['route']) ? (int)$_GET['route'] : 0;
$rating = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;
$where = [];
$params = [];
if ($route_id) {
    $where[] = 'r.route_id = ?';
    $params[] = $route_id;
}
if ($rating) {
    $where[] = 'r.rating = ?';
    $params[] = $rating;
}
$where_clause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
$reviews = getDB()->fetchAll(
    "SELECT r.*,
            u.first_name, u.username,
            rt.name as route_name, rt.name_en as route_name_en,
            c.name as city_name, c.name_en as city_name_en
     FROM reviews r
     JOIN users u ON r.user_id = u.id
     JOIN routes rt ON r.route_id = rt.id
     JOIN cities c ON rt.city_id = c.id
     {$where_clause}
     ORDER BY r.created_at DESC
     LIMIT 100",
    $params
);
$routes = getDB()->fetchAll(
    'SELECT id, name, name_en FROM routes WHERE is_active = 1 ORDER BY name'
);
try {
    $stats = getDB()->fetch(
        'SELECT
            COUNT(*) as total_reviews,
            AVG(rating) as avg_rating,
            COUNT(DISTINCT route_id) as routes_with_reviews
         FROM reviews'
    );
    $stats['total_reviews'] = $stats['total_reviews'] ?? 0;
    $stats['avg_rating'] = $stats['avg_rating'] ?? 0;
    $stats['routes_with_reviews'] = $stats['routes_with_reviews'] ?? 0;
} catch (Exception $e) {
    $stats = [
        'total_reviews' => 0,
        'avg_rating' => 0,
        'routes_with_reviews' => 0,
    ];
}
require_once __DIR__ . '/includes/header.php';
?>
<style>
.main-content {
    min-height: calc(100vh - 200px);
    padding: 2rem 0;
}
.reviews-page .card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px var(--shadow);
    transition: var(--transition);
}
.reviews-page .card:hover {
    box-shadow: 0 4px 8px var(--shadow);
}
.reviews-page .card-body {
    padding: 1.5rem;
}
.reviews-page .card-header {
    background: var(--bg-tertiary);
    border-bottom: 1px solid var(--border-color);
    padding: 1rem 1.5rem;
    border-radius: var(--radius-lg) var(--radius-lg) 0 0;
}
.reviews-page .text-warning {
    color: var(--warning);
}
.reviews-page .badge {
    padding: 0.25rem 0.5rem;
    border-radius: var(--radius-sm);
    background: var(--bg-tertiary);
    color: var(--text-primary);
}
.reviews-page .stats-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    text-align: center;
    box-shadow: 0 2px 4px var(--shadow);
}
.reviews-page .stats-card h3 {
    font-size: 2rem;
    font-weight: bold;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}
.reviews-page .stats-card p {
    color: var(--text-secondary);
    margin: 0;
}
.reviews-page .filter-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.reviews-page .filter-row {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    align-items: center;
}
.reviews-page .filter-row-top {
    display: flex;
    gap: 1rem;
    align-items: flex-end;
    flex-wrap: wrap;
    justify-content: center;
    width: 100%;
}
.reviews-page .filter-row-bottom {
    display: flex;
    gap: 0.5rem;
    justify-content: center;
    width: 100%;
}
.reviews-page .filter-group {
    flex: 0 0 auto;
    min-width: 200px;
}
.reviews-page .filter-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    text-align: center;
}
.reviews-page .filter-select-compact {
    width: 100% !important;
    min-width: 200px !important;
    max-width: 250px !important;
    padding: 0.5rem 2rem 0.5rem 0.75rem !important;
    font-size: 0.95rem !important;
    border: 1px solid var(--border-color) !important;
    border-radius: var(--radius-md) !important;
    background: var(--bg-primary) !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M4 6L8 10L12 6' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E") !important;
    background-repeat: no-repeat !important;
    background-position: right 0.75rem center !important;
    background-size: 14px !important;
    color: var(--text-primary) !important;
    -webkit-appearance: none !important;
    -moz-appearance: none !important;
    appearance: none !important;
    cursor: pointer !important;
    transition: all 0.2s ease !important;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05) !important;
}
.reviews-page .filter-select-compact:hover {
    border-color: var(--primary) !important;
    box-shadow: 0 2px 8px rgba(37, 99, 235, 0.15) !important;
}
.reviews-page .filter-select-compact:focus {
    outline: none !important;
    border-color: var(--primary) !important;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1) !important;
}
.reviews-page .filter-actions {
    display: flex;
    gap: 0.5rem;
    flex: 0 0 auto;
}
.reviews-page .review-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: 0 2px 4px var(--shadow);
}
.reviews-page .review-card:hover {
    box-shadow: 0 4px 8px var(--shadow);
}
.reviews-page .review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1rem;
}
.reviews-page .review-user {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.reviews-page .review-rating {
    color: var(--warning);
    font-size: 1.2rem;
}
.reviews-page .review-route {
    color: var(--primary);
    font-weight: 500;
    text-decoration: none;
}
.reviews-page .review-route:hover {
    text-decoration: underline;
}
.reviews-page .review-text {
    color: var(--text-primary);
    line-height: 1.6;
    margin-bottom: 0.5rem;
}
.reviews-page .review-date {
    color: var(--text-muted);
    font-size: 0.875rem;
}
.reviews-page .filter-card {
    background: var(--bg-secondary);
    border: 1px solid var(--border-color);
    border-radius: var(--radius-lg);
    padding: 1.5rem;
    margin-bottom: 2rem;
}
.reviews-page .form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: var(--text-primary);
    text-align: center;
    font-size: 0.9rem;
}
.reviews-page .form-select {
    width: 100%;
    max-width: 100%;
    padding: 0.5rem 2rem 0.5rem 0.75rem;
    font-size: 0.95rem;
    border: 1px solid var(--border-color);
    border-radius: var(--radius-md);
    background: var(--bg-primary);
    color: var(--text-primary);
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='14' height='14' viewBox='0 0 16 16' fill='none'%3E%3Cpath d='M4 6L8 10L12 6' stroke='%232563eb' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 14px;
    cursor: pointer;
    transition: all 0.2s ease;
    text-align: center;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}
.reviews-page .form-select:hover {
    border-color: var(--primary);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.15);
    transform: translateY(-1px);
}
.reviews-page .form-select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1), 0 4px 12px rgba(37, 99, 235, 0.15);
    transform: translateY(-1px);
}
.reviews-page .row.g-3 {
    align-items: center;
    justify-content: center;
}
.reviews-page .col-md-4 {
    text-align: center;
    display: flex;
    flex-direction: column;
    align-items: center;
}
.reviews-page .filter-card .row {
    margin: 0;
}
@media (max-width: 768px) {
    .reviews-page .filter-row-top {
        flex-direction: column;
        align-items: stretch;
        width: 100%;
    }
    .reviews-page .filter-group {
        width: 100%;
        min-width: 100%;
    }
    .reviews-page .filter-select-compact {
        font-size: 16px;
        max-width: 100%;
    }
    .reviews-page .filter-row-bottom {
        width: 100%;
        flex-direction: column;
    }
    .reviews-page .filter-row-bottom .btn {
        width: 100%;
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}
</style>
<main class="main-content reviews-page">
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1>⭐ <?= t('reviews_title') ?></h1>
            <p class="text-muted"><?= t('reviews_subtitle') ?></p>
        </div>
    </div>
    <!-- Статистика -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <h3><?= $stats['total_reviews'] ?></h3>
                <p><?= t('total_reviews') ?></p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <h3>
                    <?php
                    $avg_rating = $stats['avg_rating'] ?? 0;
                    for($i = 1; $i <= 5; $i++): ?>
                        <?= $i <= round($avg_rating) ? '★' : '☆' ?>
                    <?php endfor; ?>
                </h3>
                <p><?= number_format($avg_rating, 1) ?> / 5</p>
            </div>
        </div>
        <div class="col-md-4 mb-3">
            <div class="stats-card">
                <h3><?= $stats['routes_with_reviews'] ?></h3>
                <p><?= t('routes_with_reviews') ?></p>
            </div>
        </div>
    </div>
    <!-- Фильтры -->
    <div class="filter-card">
            <form method="get">
                <?php if (isset($_GET['lang'])): ?>
                    <input type="hidden" name="lang" value="<?= htmlspecialchars($_GET['lang']) ?>">
                <?php endif; ?>
                <div class="filter-row">
                    <div class="filter-row-top">
                        <div class="filter-group">
                            <label class="filter-label"><?= t('route_filter') ?></label>
                            <select name="route" class="filter-select-compact">
                                <option value=""><?= t('all_routes_filter') ?></option>
                                <?php foreach ($routes as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= $route_id == $r['id'] ? 'selected' : '' ?>>
                                    <?= getLocalizedName(['name' => $r['name'], 'name_en' => $r['name_en'] ?? null]) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label"><?= t('rating_filter') ?></label>
                            <select name="rating" class="filter-select-compact">
                                <option value=""><?= t('any_rating') ?></option>
                                <option value="5" <?= $rating == 5 ? 'selected' : '' ?>>⭐⭐⭐⭐⭐ (5)</option>
                                <option value="4" <?= $rating == 4 ? 'selected' : '' ?>>⭐⭐⭐⭐ (4)</option>
                                <option value="3" <?= $rating == 3 ? 'selected' : '' ?>>⭐⭐⭐ (3)</option>
                                <option value="2" <?= $rating == 2 ? 'selected' : '' ?>>⭐⭐ (2)</option>
                                <option value="1" <?= $rating == 1 ? 'selected' : '' ?>>⭐ (1)</option>
                            </select>
                        </div>
                    </div>
                    <div class="filter-row-bottom">
                        <button type="submit" class="btn btn-primary btn-sm">🔍 <?= t('search') ?></button>
                        <a href="/reviews.php<?= isset($_GET['lang']) ? '?lang=' . htmlspecialchars($_GET['lang']) : '' ?>" class="btn btn-outline-secondary btn-sm">🔄 <?= t('reset') ?></a>
                    </div>
                </div>
            </form>
    </div>
    <!-- Отзывы -->
    <?php if (empty($reviews)): ?>
    <div class="text-center py-5">
        <p class="text-muted"><?= t('no_reviews') ?></p>
    </div>
    <?php else: ?>
                <div class="row">
                    <?php foreach ($reviews as $review): ?>
                    <div class="col-md-6 mb-3">
                        <div class="review-card">
                            <div class="review-header">
                                <div class="review-user">
                                    <div>
                                        <h5 class="mb-0"><?= htmlspecialchars($review['first_name']) ?></h5>
                                        <?php if ($review['username']): ?>
                                            <small class="text-muted">@<?= htmlspecialchars($review['username']) ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="review-rating">
                                    <?php for($i = 1; $i <= $review['rating']; $i++): ?>⭐<?php endfor; ?>
                                </div>
                            </div>
                            <p class="mb-2">
                                <a href="/routes/view.php?id=<?= $review['route_id'] ?><?= isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '' ?>" class="review-route">
                                    <strong><?= getLocalizedName(['name' => $review['route_name'], 'name_en' => $review['route_name_en'] ?? null]) ?></strong>
                                </a>
                                <small class="text-muted">• <?= getLocalizedName(['name' => $review['city_name'], 'name_en' => $review['city_name_en'] ?? null]) ?></small>
                            </p>
                            <?php if ($review['text']): ?>
                                <p class="review-text"><?= nl2br(htmlspecialchars($review['text'])) ?></p>
                            <?php endif; ?>
                            <small class="review-date">
                                <?= date('d.m.Y H:i', strtotime($review['created_at'])) ?>
                            </small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
    <?php endif; ?>
</div>
</main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>