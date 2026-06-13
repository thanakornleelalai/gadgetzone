<?php
require_once __DIR__ . '/includes/db.php';
$page_title = 'Home';

// Featured products (max 6)
$featured = [];
$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id=p.category_id
        WHERE p.featured=1 ORDER BY p.created_at DESC LIMIT 6";
if ($res = $conn->query($sql)) { while ($r = $res->fetch_assoc()) { $featured[] = $r; } $res->free(); }

// New arrivals (4 newest)
$newest = [];
$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id=p.category_id
        ORDER BY p.created_at DESC, p.id DESC LIMIT 4";
if ($res = $conn->query($sql)) { while ($r = $res->fetch_assoc()) { $newest[] = $r; } $res->free(); }

// Categories with product counts
$cats = [];
$sql = "SELECT c.*, COUNT(p.id) AS cnt FROM categories c
        LEFT JOIN products p ON p.category_id=c.id GROUP BY c.id ORDER BY c.id";
if ($res = $conn->query($sql)) { while ($r = $res->fetch_assoc()) { $cats[] = $r; } $res->free(); }

// Deal of the day: a featured product with the biggest discount
$deal = null;
$sql = "SELECT p.*, c.name AS category_name FROM products p
        LEFT JOIN categories c ON c.id=p.category_id
        WHERE p.old_price IS NOT NULL AND p.old_price > p.price
        ORDER BY (p.old_price - p.price) DESC LIMIT 1";
if ($res = $conn->query($sql)) { $deal = $res->fetch_assoc(); $res->free(); }

require_once __DIR__ . '/includes/header.php';
?>

<!-- ── 1. HERO ─────────────────────────────────────────── -->
<section class="hero">
    <div class="container hero-grid">
        <div class="hero-copy reveal">
            <span class="hero-eyebrow">⚡ New season drops are live</span>
            <h1 class="hero-title">Your World.<br><span class="accent">Next-Level</span> Technology.</h1>
            <p class="hero-sub">Discover handpicked flagship gadgets — smartphones, laptops, audio and more — at prices that move as fast as the tech.</p>
            <div class="hero-cta">
                <a href="<?= url('pages/shop.php') ?>" class="btn btn-primary btn-lg">Shop Now</a>
                <a href="<?= url('pages/shop.php?badge=SALE') ?>" class="btn btn-outline btn-lg">Explore Deals</a>
            </div>
            <div class="hero-stats">
                <div><strong>500+</strong><span>Products</span></div>
                <div><strong>50K+</strong><span>Happy Customers</span></div>
                <div><strong>4.9★</strong><span>Average Rating</span></div>
            </div>
        </div>
        <div class="hero-media reveal">
            <div class="hero-glow"></div>
            <img src="https://images.unsplash.com/photo-1511707171634-5f897ff02aa9?w=900&q=80" alt="Featured gadget">
            <div class="hero-float-badge">
                <span class="hf-title">🔥 Hot Deal Today</span>
                <span class="hf-sub">Up to 40% Off</span>
            </div>
        </div>
    </div>
</section>

<!-- ── 2. FEATURE STRIP ────────────────────────────────── -->
<section class="feature-strip">
    <div class="container feature-strip-inner">
        <?php
        $features = [
            ['🚚','Free Delivery','On orders over ৳5,000'],
            ['↩️','7-Day Returns','Hassle-free refunds'],
            ['🛡️','2-Year Warranty','On all gadgets'],
            ['💬','24/7 Support','Always here to help'],
            ['🔒','Secure Payment','Encrypted checkout'],
        ];
        foreach ($features as $f): ?>
            <div class="feature-item">
                <span class="feature-icon"><?= $f[0] ?></span>
                <div><strong><?= $f[1] ?></strong><span><?= $f[2] ?></span></div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ── 3. CATEGORY GRID ────────────────────────────────── -->
<section class="container section">
    <div class="section-head">
        <h2 class="section-title">Shop by <span class="accent">Category</span></h2>
        <a href="<?= url('pages/shop.php') ?>" class="section-link">View all →</a>
    </div>
    <div class="category-grid">
        <?php foreach ($cats as $c): ?>
            <a class="category-card reveal" href="<?= url('pages/shop.php?cat=' . urlencode($c['slug'])) ?>">
                <span class="cat-icon"><?= e($c['icon']) ?></span>
                <h3><?= e($c['name']) ?></h3>
                <span class="cat-count"><?= (int)$c['cnt'] ?> items</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- ── 4. FEATURED PRODUCTS ────────────────────────────── -->
<section class="container section">
    <div class="section-head">
        <h2 class="section-title">Featured <span class="accent">Products</span></h2>
        <a href="<?= url('pages/shop.php') ?>" class="section-link">View all →</a>
    </div>
    <div class="product-grid">
        <?php foreach ($featured as $p) { echo renderProductCard($p); } ?>
    </div>
</section>

<!-- ── 5. DEAL OF THE DAY ──────────────────────────────── -->
<?php if ($deal):
    $dealLink = url('pages/product.php?slug=' . urlencode($deal['slug'])); ?>
<section class="container section">
    <div class="deal-banner reveal">
        <div class="deal-info">
            <span class="deal-eyebrow">⚡ Deal of the Day</span>
            <h2><?= e($deal['name']) ?></h2>
            <p><?= e($deal['description']) ?></p>
            <div class="deal-price">
                <span class="price-now"><?= formatPrice($deal['price']) ?></span>
                <span class="price-old"><?= formatPrice($deal['old_price']) ?></span>
                <?php $pct = round(100 * ($deal['old_price'] - $deal['price']) / $deal['old_price']); ?>
                <span class="deal-pct">-<?= $pct ?>%</span>
            </div>
            <div class="countdown" id="dealCountdown" aria-label="Time left">
                <div><span data-cd="h">00</span><small>Hours</small></div>
                <div><span data-cd="m">00</span><small>Mins</small></div>
                <div><span data-cd="s">00</span><small>Secs</small></div>
            </div>
            <div class="deal-cta">
                <button class="btn btn-primary add-to-cart" data-id="<?= (int)$deal['id'] ?>">Add to Cart</button>
                <a href="<?= url('pages/shop.php') ?>" class="btn btn-outline">View Shop</a>
            </div>
        </div>
        <div class="deal-image">
            <a href="<?= $dealLink ?>"><img src="<?= e($deal['image_url']) ?>" alt="<?= e($deal['name']) ?>"></a>
        </div>
        <div class="deal-meta">
            <div class="deal-rating"><?= starHtml(productRating($deal['id'])) ?><span><?= productRating($deal['id']) ?> / 5</span></div>
            <ul class="deal-perks">
                <li>🚚 Free express delivery</li>
                <li>🛡️ 2-year warranty</li>
                <li>↩️ 7-day easy returns</li>
                <li>📦 In stock — ships today</li>
            </ul>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ── 6. NEW ARRIVALS ─────────────────────────────────── -->
<section class="container section">
    <div class="section-head">
        <h2 class="section-title">New <span class="accent">Arrivals</span></h2>
        <a href="<?= url('pages/shop.php?sort=newest') ?>" class="section-link">View all →</a>
    </div>
    <div class="product-grid grid-4">
        <?php foreach ($newest as $p) { echo renderProductCard($p); } ?>
    </div>
</section>

<!-- ── 7. TESTIMONIALS ─────────────────────────────────── -->
<section class="container section">
    <div class="section-head center">
        <h2 class="section-title">What Customers <span class="accent">Say</span></h2>
    </div>
    <div class="testimonial-grid">
        <?php
        $reviews = [
            ['★★★★★','Lightning-fast shipping and the packaging felt premium. My new Galaxy arrived a day early!','Ayesha Rahman','Dhaka, BD'],
            ['★★★★★','Best prices I could find anywhere. The deal countdown saved me nearly 40% on a laptop.','Tanvir Hossain','Chattogram, BD'],
            ['★★★★★','Support actually answered at 2am. Replaced my charger no questions asked. Loyal customer now.','Maya Lin','Singapore'],
        ];
        foreach ($reviews as $r): ?>
            <div class="testimonial-card reveal">
                <div class="t-stars"><?= $r[0] ?></div>
                <p class="t-text">“<?= e($r[1]) ?>”</p>
                <div class="t-author">
                    <span class="t-avatar"><?= e(initials(explode(' ', $r[2])[0], explode(' ', $r[2])[1] ?? '')) ?></span>
                    <div><strong><?= e($r[2]) ?></strong><span><?= e($r[3]) ?></span></div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- ── 8. NEWSLETTER ───────────────────────────────────── -->
<section class="newsletter">
    <div class="container newsletter-inner">
        <h2>Get Exclusive Deals First 🎁</h2>
        <p>Subscribe and be the first to know about drops, discounts and flash sales.</p>
        <form class="newsletter-form" onsubmit="GZ.subscribe(event)">
            <input type="email" name="email" placeholder="Enter your email address" required>
            <button type="submit" class="btn">Subscribe</button>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
