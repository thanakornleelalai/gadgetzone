<?php
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Shop';

// ── Read & sanitise GET params ────────────────────────────
$catSlug = isset($_GET['cat'])    ? sanitize($_GET['cat'])    : '';
$search  = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$badge   = isset($_GET['badge'])  ? strtoupper(sanitize($_GET['badge'])) : '';
$sort    = $_GET['sort'] ?? 'newest';
$minP    = isset($_GET['min']) ? max(0, (int)$_GET['min']) : 0;
$maxP    = isset($_GET['max']) ? (int)$_GET['max'] : 300000;
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 9;
$offset  = ($page - 1) * $perPage;

if (!in_array($badge, ['NEW','HOT','SALE'], true)) { $badge = ''; }

// Resolve category slug -> id and name
$catId = 0; $catName = '';
if ($catSlug !== '') {
    $stmt = $conn->prepare("SELECT id, name FROM categories WHERE slug=? LIMIT 1");
    $stmt->bind_param('s', $catSlug);
    $stmt->execute();
    if ($row = $stmt->get_result()->fetch_assoc()) { $catId = (int)$row['id']; $catName = $row['name']; }
    $stmt->close();
}

// ── Build dynamic WHERE clause (prepared) ─────────────────
$where  = ['1=1'];
$params = [];
$types  = '';

if ($catId > 0)       { $where[] = 'p.category_id = ?'; $params[] = $catId; $types .= 'i'; }
if ($search !== '')   { $where[] = '(p.name LIKE ? OR p.description LIKE ?)';
                        $like = '%' . $search . '%'; $params[] = $like; $params[] = $like; $types .= 'ss'; }
if ($badge !== '')    { $where[] = 'p.badge = ?'; $params[] = $badge; $types .= 's'; }
$where[] = 'p.price >= ? AND p.price <= ?'; $params[] = $minP; $params[] = $maxP; $types .= 'ii';
$whereSql = implode(' AND ', $where);

// Sort map
$orderMap = [
    'newest'    => 'p.created_at DESC, p.id DESC',
    'popular'   => 'p.featured DESC, p.stock ASC',
    'rating'    => 'p.id DESC',
    'price_asc' => 'p.price ASC',
    'price_desc'=> 'p.price DESC',
];
$orderSql = $orderMap[$sort] ?? $orderMap['newest'];

// ── Count total for pagination ────────────────────────────
$countSql = "SELECT COUNT(*) AS c FROM products p WHERE $whereSql";
$stmt = $conn->prepare($countSql);
if ($types) { $stmt->bind_param($types, ...$params); }
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['c'];
$stmt->close();
$totalPages = max(1, (int)ceil($total / $perPage));
if ($page > $totalPages) { $page = $totalPages; $offset = ($page - 1) * $perPage; }

// ── Fetch products ────────────────────────────────────────
$listSql = "SELECT p.*, c.name AS category_name FROM products p
            LEFT JOIN categories c ON c.id=p.category_id
            WHERE $whereSql ORDER BY $orderSql LIMIT ? OFFSET ?";
$stmt = $conn->prepare($listSql);
$lt = $types . 'ii';
$lp = array_merge($params, [$perPage, $offset]);
$stmt->bind_param($lt, ...$lp);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Sidebar categories with counts
$sideCats = [];
$sql = "SELECT c.*, COUNT(p.id) AS cnt FROM categories c
        LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.id";
if ($res = $conn->query($sql)) { while ($r = $res->fetch_assoc()) { $sideCats[] = $r; } $res->free(); }

// Helper: build a URL preserving current filters but overriding some keys
function shopUrl(array $override = [])
{
    $base = ['cat'=>$_GET['cat']??'','search'=>$_GET['search']??'','badge'=>$_GET['badge']??'',
             'sort'=>$_GET['sort']??'','min'=>$_GET['min']??'','max'=>$_GET['max']??'','page'=>$_GET['page']??''];
    $merged = array_merge($base, $override);
    $merged = array_filter($merged, fn($v) => $v !== '' && $v !== null);
    return url('pages/shop.php') . (empty($merged) ? '' : '?' . http_build_query($merged));
}

$from = $total ? $offset + 1 : 0;
$to   = min($offset + $perPage, $total);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> › <span>Shop<?= $catName ? ' › ' . e($catName) : '' ?></span></nav>

    <div class="shop-layout">
        <!-- Sidebar filters -->
        <aside class="shop-sidebar">
            <form method="GET" action="<?= url('pages/shop.php') ?>" id="filterForm">
                <?php if ($badge): ?><input type="hidden" name="badge" value="<?= e($badge) ?>"><?php endif; ?>
                <?php if ($search): ?><input type="hidden" name="search" value="<?= e($search) ?>"><?php endif; ?>

                <div class="filter-block">
                    <h4>Category</h4>
                    <label class="radio-row">
                        <input type="radio" name="cat" value="" <?= $catSlug === '' ? 'checked' : '' ?> onchange="this.form.submit()">
                        <span>All Categories</span>
                    </label>
                    <?php foreach ($sideCats as $c): ?>
                        <label class="radio-row">
                            <input type="radio" name="cat" value="<?= e($c['slug']) ?>" <?= $catSlug === $c['slug'] ? 'checked' : '' ?> onchange="this.form.submit()">
                            <span><?= e($c['icon']) ?> <?= e($c['name']) ?></span>
                            <em><?= (int)$c['cnt'] ?></em>
                        </label>
                    <?php endforeach; ?>
                </div>

                <div class="filter-block">
                    <h4>Price Range</h4>
                    <div class="price-labels"><span>৳<span id="minLabel"><?= $minP ?></span></span><span>৳<span id="maxLabel"><?= $maxP ?></span></span></div>
                    <input type="range" name="max" id="priceRange" min="0" max="300000" step="1000" value="<?= $maxP ?>"
                           oninput="document.getElementById('maxLabel').textContent = (+this.value).toLocaleString()">
                    <input type="hidden" name="min" value="<?= $minP ?>">
                    <input type="hidden" name="sort" value="<?= e($sort) ?>">
                </div>

                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-block">Apply Filters</button>
                    <a href="<?= url('pages/shop.php') ?>" class="btn btn-ghost btn-block">Clear All</a>
                </div>
            </form>
        </aside>

        <!-- Main content -->
        <section class="shop-main">
            <div class="shop-toolbar">
                <p class="results-count">
                    <?php if ($total): ?>
                        Showing <strong><?= $from ?>–<?= $to ?></strong> of <strong><?= $total ?></strong> results<?= $catName ? ' in ' . e($catName) : '' ?>
                    <?php else: ?>
                        No results<?= $catName ? ' in ' . e($catName) : '' ?>
                    <?php endif; ?>
                </p>
                <form method="GET" action="<?= url('pages/shop.php') ?>" class="sort-form">
                    <?php foreach (['cat'=>$catSlug,'search'=>$search,'badge'=>$badge,'min'=>$minP,'max'=>$maxP] as $k=>$v): ?>
                        <?php if ($v !== '' && $v !== 0): ?><input type="hidden" name="<?= $k ?>" value="<?= e($v) ?>"><?php endif; ?>
                    <?php endforeach; ?>
                    <label>Sort by</label>
                    <select name="sort" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort==='newest'?'selected':'' ?>>Newest</option>
                        <option value="popular"    <?= $sort==='popular'?'selected':'' ?>>Most Popular</option>
                        <option value="rating"     <?= $sort==='rating'?'selected':'' ?>>Top Rated</option>
                        <option value="price_asc"  <?= $sort==='price_asc'?'selected':'' ?>>Price: Low → High</option>
                        <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Price: High → Low</option>
                    </select>
                </form>
            </div>

            <?php if ($products): ?>
                <div class="product-grid grid-3">
                    <?php foreach ($products as $p) { echo renderProductCard($p); } ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="pagination">
                    <?php if ($page > 1): ?>
                        <a class="page-btn" href="<?= shopUrl(['page'=>$page-1]) ?>">‹ Prev</a>
                    <?php else: ?>
                        <span class="page-btn disabled">‹ Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="page-btn active"><?= $i ?></span>
                        <?php else: ?>
                            <a class="page-btn" href="<?= shopUrl(['page'=>$i]) ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a class="page-btn" href="<?= shopUrl(['page'=>$page+1]) ?>">Next ›</a>
                    <?php else: ?>
                        <span class="page-btn disabled">Next ›</span>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">🔍</div>
                    <h3>No products found</h3>
                    <p>Try adjusting your filters or search term.</p>
                    <a href="<?= url('pages/shop.php') ?>" class="btn btn-primary">Clear Filters</a>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
