<?php
require_once __DIR__ . '/../includes/db.php';

$slug = isset($_GET['slug']) ? sanitize($_GET['slug']) : '';
if ($slug === '') { header('Location: ' . url('pages/shop.php')); exit; }

$stmt = $conn->prepare("SELECT p.*, c.name AS category_name, c.slug AS category_slug
                        FROM products p LEFT JOIN categories c ON c.id=p.category_id
                        WHERE p.slug=? LIMIT 1");
$stmt->bind_param('s', $slug);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    http_response_code(404);
    $page_title = 'Not found';
    require_once __DIR__ . '/../includes/header.php';
    echo '<div class="container section"><div class="empty-state"><div class="empty-icon">😕</div><h3>Product not found</h3><a class="btn btn-primary" href="' . url('pages/shop.php') . '">Back to Shop</a></div></div>';
    require_once __DIR__ . '/../includes/footer.php';
    exit;
}

$page_title = $product['name'];
$rating = productRating($product['id']);
$hasOld = !empty($product['old_price']) && $product['old_price'] > $product['price'];

// Related products (same category, excluding this one)
$related = [];
$stmt = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p
                        LEFT JOIN categories c ON c.id=p.category_id
                        WHERE p.category_id=? AND p.id<>? ORDER BY RAND() LIMIT 4");
$stmt->bind_param('ii', $product['category_id'], $product['id']);
$stmt->execute();
$related = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a> ›
        <a href="<?= url('pages/shop.php') ?>">Shop</a> ›
        <a href="<?= url('pages/shop.php?cat=' . urlencode($product['category_slug'])) ?>"><?= e($product['category_name']) ?></a> ›
        <span><?= e($product['name']) ?></span>
    </nav>

    <div class="product-detail">
        <div class="pd-gallery">
            <?php if ($product['badge']): ?><span class="pc-badge badge-<?= e(strtolower($product['badge'])) ?>"><?= e($product['badge']) ?></span><?php endif; ?>
            <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
        </div>

        <div class="pd-info">
            <span class="pd-cat"><?= e($product['category_name']) ?></span>
            <h1><?= e($product['name']) ?></h1>
            <div class="pd-rating"><?= starHtml($rating) ?> <span><?= $rating ?> · 120 reviews</span></div>

            <div class="pd-price">
                <span class="price-now"><?= formatPrice($product['price']) ?></span>
                <?php if ($hasOld): ?>
                    <span class="price-old"><?= formatPrice($product['old_price']) ?></span>
                    <?php $pct = round(100 * ($product['old_price'] - $product['price']) / $product['old_price']); ?>
                    <span class="deal-pct">Save <?= $pct ?>%</span>
                <?php endif; ?>
            </div>

            <p class="pd-desc"><?= e($product['description']) ?></p>

            <div class="pd-stock <?= $product['stock'] > 0 ? 'in' : 'out' ?>">
                <?= $product['stock'] > 0 ? '✓ In stock (' . (int)$product['stock'] . ' available)' : '✗ Out of stock' ?>
            </div>

            <?php if ($product['stock'] > 0): ?>
            <div class="pd-actions">
                <div class="qty-control" data-qty>
                    <button type="button" class="qty-minus" aria-label="Decrease">−</button>
                    <input type="number" class="qty-input" value="1" min="1" max="99">
                    <button type="button" class="qty-plus" aria-label="Increase">+</button>
                </div>
                <button class="btn btn-primary btn-lg add-to-cart" data-id="<?= (int)$product['id'] ?>" data-qty-source>Add to Cart</button>
                <a class="btn btn-outline btn-lg" href="<?= url('pages/cart.php') ?>">View Cart</a>
            </div>
            <?php endif; ?>

            <ul class="pd-perks">
                <li>🚚 Free delivery on orders over ৳5,000</li>
                <li>🛡️ 2-year official warranty</li>
                <li>↩️ 7-day easy returns</li>
                <li>🔒 Secure encrypted checkout</li>
            </ul>
        </div>
    </div>

    <?php if ($related): ?>
    <section class="section">
        <div class="section-head"><h2 class="section-title">Related <span class="accent">Products</span></h2></div>
        <div class="product-grid grid-4">
            <?php foreach ($related as $p) { echo renderProductCard($p); } ?>
        </div>
    </section>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
