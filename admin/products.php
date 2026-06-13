<?php
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

// ── Handle POST actions BEFORE layout/output ──────────────
$flash = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save') {
        $id          = (int)($_POST['id'] ?? 0);
        $name        = sanitize($_POST['name'] ?? '');
        $category_id = (int)($_POST['category_id'] ?? 0);
        $price       = (float)($_POST['price'] ?? 0);
        $old_price   = $_POST['old_price'] !== '' ? (float)$_POST['old_price'] : null;
        $description = sanitize($_POST['description'] ?? '');
        $badge       = in_array($_POST['badge'] ?? '', ['NEW','HOT','SALE'], true) ? $_POST['badge'] : '';
        $stock       = (int)($_POST['stock'] ?? 0);
        $featured    = isset($_POST['featured']) ? 1 : 0;
        $image_url   = sanitize($_POST['image_url'] ?? '');

        // slug
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($name));
        $slug = trim($slug, '-') ?: ('product-' . time());

        // Image upload (overrides image_url if a file is provided).
        // On serverless hosts (Vercel) the filesystem is read-only — detect and
        // fall back to whatever Image URL field was filled in.
        if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp','image/gif'=>'gif'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $_FILES['image_file']['tmp_name']);
            finfo_close($finfo);

            $dir = __DIR__ . '/uploads/';
            $fsWritable = (is_dir($dir) && is_writable($dir)) || @mkdir($dir, 0775, true);

            if (!$fsWritable) {
                // Read-only filesystem (Vercel / serverless). Surface a clear message
                // instead of letting move_uploaded_file emit warnings that break header().
                $flash = 'File upload not available on this host — please paste an Image URL instead.';
                header('Location: ' . url('admin/products.php?flash=' . urlencode($flash)));
                exit;
            }

            if (isset($allowed[$mime]) && $_FILES['image_file']['size'] <= 4*1024*1024) {
                $fname = $slug . '_' . time() . '.' . $allowed[$mime];
                if (@move_uploaded_file($_FILES['image_file']['tmp_name'], $dir . $fname)) {
                    $image_url = url('admin/uploads/' . $fname);
                }
            }
        }
        if ($image_url === '') { $image_url = 'https://images.unsplash.com/photo-1518770660439-4636190af475?w=600&q=80'; }

        if ($id > 0) {
            // Ensure unique slug (exclude self)
            $stmt = $conn->prepare("SELECT id FROM products WHERE slug=? AND id<>?");
            $stmt->bind_param('si', $slug, $id); $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) { $slug .= '-' . $id; }
            $stmt->close();

            $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, price=?, old_price=?, image_url=?, badge=?, stock=?, featured=? WHERE id=?");
            $stmt->bind_param('isssddssiii', $category_id, $name, $slug, $description, $price, $old_price, $image_url, $badge, $stock, $featured, $id);
            $stmt->execute(); $stmt->close();
            $flash = 'Product updated.';
        } else {
            $stmt = $conn->prepare("SELECT id FROM products WHERE slug=?");
            $stmt->bind_param('s', $slug); $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) { $slug .= '-' . time(); }
            $stmt->close();

            $stmt = $conn->prepare("INSERT INTO products (category_id, name, slug, description, price, old_price, image_url, badge, stock, featured) VALUES (?,?,?,?,?,?,?,?,?,?)");
            $stmt->bind_param('isssddssii', $category_id, $name, $slug, $description, $price, $old_price, $image_url, $badge, $stock, $featured);
            $stmt->execute(); $stmt->close();
            $flash = 'Product created.';
        }
        header('Location: ' . url('admin/products.php?flash=' . urlencode($flash)));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Safety: block deletion if product has order history.
        $stmt = $conn->prepare("SELECT COUNT(*) c FROM order_items WHERE product_id=?");
        $stmt->bind_param('i', $id); $stmt->execute();
        $orderCount = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
        $stmt->close();
        if ($orderCount > 0) {
            header('Location: ' . url('admin/products.php?flash=' . urlencode(
                "Cannot delete: this product appears in {$orderCount} order(s). Set stock to 0 to hide it instead."
            )));
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
        $stmt->bind_param('i', $id); $stmt->execute(); $stmt->close();
        header('Location: ' . url('admin/products.php?flash=' . urlencode('Product deleted.')));
        exit;
    }
}

$admin_title = 'Products';
require_once __DIR__ . '/layout.php';

$flash = isset($_GET['flash']) ? sanitize($_GET['flash']) : '';
$cats = [];
if ($res = $conn->query("SELECT id, name FROM categories ORDER BY name")) { while ($r=$res->fetch_assoc()){$cats[]=$r;} $res->free(); }

$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$catFilter = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;

$where  = [];
$params = [];
$types  = '';
if ($search !== '') { $where[] = 'p.name LIKE ?'; $params[] = "%$search%"; $types .= 's'; }
if ($catFilter > 0) { $where[] = 'p.category_id = ?'; $params[] = $catFilter; $types .= 'i'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT p.*, c.name AS category_name
        FROM products p LEFT JOIN categories c ON c.id=p.category_id
        $whereSql
        ORDER BY p.id DESC";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $products = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// JSON of categories for the JS modal
$catsJson = json_encode($cats);
?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-card-head bare">
    <form method="GET" class="admin-search">
        <input type="search" name="q" placeholder="Search products…" value="<?= e($search) ?>">
        <select name="cat" class="mini-select" onchange="this.form.submit()">
            <option value="0">All categories</option>
            <?php foreach ($cats as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= $catFilter===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-ghost sm" type="submit">Search</button>
    </form>
    <button class="btn btn-primary" onclick="ProductAdmin.openNew()">+ Add Product</button>
</div>

<div class="admin-card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th></th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Badge</th><th>Featured</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($products as $p): ?>
            <tr>
                <td><img class="cell-thumb" src="<?= e($p['image_url']) ?>" alt=""></td>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category_name']) ?></td>
                <td><?= formatPrice($p['price']) ?><?php if($p['old_price']):?><br><small class="muted strike"><?= formatPrice($p['old_price']) ?></small><?php endif;?></td>
                <td class="<?= $p['stock']<15?'low-stock':'' ?>"><?= (int)$p['stock'] ?></td>
                <td><?= $p['badge'] ? '<span class="status-badge sb-amber">'.e($p['badge']).'</span>' : '<span class="muted">—</span>' ?></td>
                <td><?= $p['featured'] ? '⭐' : '—' ?></td>
                <td class="actions-cell">
                    <button class="icon-act" title="Edit" onclick='ProductAdmin.openEdit(<?= json_encode($p, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>✏️</button>
                    <form method="POST" class="inline-form" onsubmit="return confirm('Delete this product?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                        <button class="icon-act danger" title="Delete">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($products)): ?><tr><td colspan="8" class="muted center">No products found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
  window.GZ_CATEGORIES = <?= $catsJson ?>;
  window.GZ_SAVE_URL = '<?= url('admin/products.php') ?>';
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
