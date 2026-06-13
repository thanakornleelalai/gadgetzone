<?php
/**
 * includes/functions.php
 * Helpers for authentication, cart, settings, and formatting.
 */

// ── Input sanitising ──────────────────────────────────────
function sanitize($data)
{
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

function e($data) // shorthand for echoing escaped output
{
    return htmlspecialchars((string)$data, ENT_QUOTES, 'UTF-8');
}

// ── Settings ──────────────────────────────────────────────
function getSetting($key, $default = null)
{
    global $conn;
    $stmt = $conn->prepare("SELECT setting_value FROM settings WHERE setting_key=? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $stmt->bind_result($val);
    $found = $stmt->fetch();
    $stmt->close();
    return $found ? $val : $default;
}

function setSetting($key, $value)
{
    global $conn;
    $stmt = $conn->prepare(
        "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
    );
    $stmt->bind_param('ss', $key, $value);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
}

// ── Authentication ────────────────────────────────────────
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && (int)$_SESSION['user_id'] > 0;
}

function getCurrentUser()
{
    global $conn;
    if (!isLoggedIn()) {
        return null;
    }
    $id   = (int)$_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $user ?: null;
}

function isAdmin()
{
    $u = getCurrentUser();
    return $u && in_array($u['role'], ['admin', 'super_admin'], true);
}

function isSuperAdmin()
{
    $u = getCurrentUser();
    return $u && $u['role'] === 'super_admin';
}

function requireLogin()
{
    if (!isLoggedIn()) {
        $redirect = urlencode($_SERVER['REQUEST_URI'] ?? url('pages/myaccount.php'));
        header('Location: ' . url('pages/login.php') . '?redirect=' . $redirect);
        exit;
    }
}

function requireAdmin()
{
    if (!isAdmin()) {
        header('Location: ' . url('pages/login.php'));
        exit;
    }
}

// ── Cart (session: $_SESSION['cart'] = [product_id => qty]) ─
function getCart()
{
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    return $_SESSION['cart'];
}

function addToCart($id, $qty = 1)
{
    $id  = (int)$id;
    $qty = max(1, (int)$qty);
    getCart();
    $_SESSION['cart'][$id] = min(99, ($_SESSION['cart'][$id] ?? 0) + $qty);
    return true;
}

function updateCartQty($id, $qty)
{
    $id  = (int)$id;
    $qty = (int)$qty;
    getCart();
    if ($qty <= 0) {
        unset($_SESSION['cart'][$id]);
    } else {
        $_SESSION['cart'][$id] = min(99, $qty);
    }
    return true;
}

function removeFromCart($id)
{
    $id = (int)$id;
    getCart();
    unset($_SESSION['cart'][$id]);
    return true;
}

function clearCart()
{
    $_SESSION['cart'] = [];
}

function getCartCount()
{
    return array_sum(getCart());
}

/**
 * Returns detailed cart rows joined with product data.
 * [ ['product'=>[...], 'qty'=>n, 'subtotal'=>price*qty], ... ]
 */
function getCartItems()
{
    global $conn;
    $cart = getCart();
    if (empty($cart)) {
        return [];
    }
    $ids = array_map('intval', array_keys($cart));
    $in  = implode(',', $ids);
    $sql = "SELECT p.*, c.name AS category_name, c.slug AS category_slug
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            WHERE p.id IN ($in)";
    $rows = [];
    if ($res = $conn->query($sql)) {
        while ($p = $res->fetch_assoc()) {
            $qty = (int)$cart[$p['id']];
            $rows[] = [
                'product'  => $p,
                'qty'      => $qty,
                'subtotal' => (float)$p['price'] * $qty,
            ];
        }
        $res->free();
    }
    return $rows;
}

function getCartTotal()
{
    $total = 0;
    foreach (getCartItems() as $row) {
        $total += $row['subtotal'];
    }
    return $total;
}

function getShippingFee($subtotal)
{
    return $subtotal >= FREE_SHIPPING_THRESHOLD ? 0 : SHIPPING_FEE;
}

// ── Orders ────────────────────────────────────────────────
function generateOrderNumber()
{
    return 'GZ-' . strtoupper(uniqid());
}

// ── Misc ──────────────────────────────────────────────────
function initials($first, $last = '')
{
    $a = mb_substr(trim($first), 0, 1);
    $b = mb_substr(trim($last), 0, 1);
    return strtoupper($a . $b) ?: 'U';
}

function statusColor($status)
{
    return [
        'pending'    => 'amber',
        'processing' => 'blue',
        'shipped'    => 'purple',
        'delivered'  => 'green',
        'cancelled'  => 'red',
    ][$status] ?? 'amber';
}

/** Pseudo-rating derived from product id so stars are stable per product. */
function productRating($id)
{
    return round(4.3 + (($id * 7) % 7) / 10, 1); // 4.3 – 4.9
}

function starHtml($rating)
{
    $full = floor($rating);
    $half = ($rating - $full) >= 0.5;
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $full) {
            $html .= '★';
        } elseif ($i == $full + 1 && $half) {
            $html .= '✬';
        } else {
            $html .= '<span class="star-empty">★</span>';
        }
    }
    return $html;
}

/**
 * Renders a single product card (used on home + shop pages).
 * $p must contain: id, name, slug, price, old_price, image_url, badge, category_name
 */
function renderProductCard($p)
{
    $rating   = productRating($p['id']);
    $link     = url('pages/product.php?slug=' . urlencode($p['slug']));
    $hasOld   = !empty($p['old_price']) && $p['old_price'] > $p['price'];
    $badge    = $p['badge'] ?? '';
    $catName  = $p['category_name'] ?? '';
    ob_start(); ?>
    <article class="product-card reveal">
        <a class="pc-image" href="<?= $link ?>">
            <?php if ($badge): ?><span class="pc-badge badge-<?= e(strtolower($badge)) ?>"><?= e($badge) ?></span><?php endif; ?>
            <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
        </a>
        <div class="pc-body">
            <?php if ($catName): ?><span class="pc-cat"><?= e($catName) ?></span><?php endif; ?>
            <h3 class="pc-name"><a href="<?= $link ?>"><?= e($p['name']) ?></a></h3>
            <div class="pc-rating"><?= starHtml($rating) ?> <span class="pc-rating-num"><?= $rating ?></span></div>
            <div class="pc-price">
                <span class="price-now"><?= formatPrice($p['price']) ?></span>
                <?php if ($hasOld): ?><span class="price-old"><?= formatPrice($p['old_price']) ?></span><?php endif; ?>
            </div>
            <button class="btn btn-add add-to-cart" data-id="<?= (int)$p['id'] ?>">Add to Cart</button>
        </div>
    </article>
    <?php
    return ob_get_clean();
}
