<?php
require_once __DIR__ . '/../includes/db.php';

// ── Server-side fallback: handle remove BEFORE any HTML output ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_id'])) {
    removeFromCart((int)$_POST['remove_id']);
    header('Location: ' . url('pages/cart.php'));
    exit;
}
// Optional: server-side quantity update fallback
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    updateCartQty((int)$_POST['update_id'], (int)($_POST['update_qty'] ?? 1));
    header('Location: ' . url('pages/cart.php'));
    exit;
}

$page_title = 'Shopping Cart';
$items    = getCartItems();
$subtotal = getCartTotal();
$shipping = getShippingFee($subtotal);
$total    = $subtotal + $shipping;
$count    = getCartCount();
$remaining = max(0, FREE_SHIPPING_THRESHOLD - $subtotal);

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> › <span>Shopping Cart</span></nav>
    <h1 class="page-title">Shopping Cart <span class="muted">(<?= (int)$count ?> item<?= $count == 1 ? '' : 's' ?>)</span></h1>

    <?php if (empty($items)): ?>
        <div class="empty-state">
            <div class="empty-icon">🛍️</div>
            <h3>Your cart is empty</h3>
            <p>Looks like you haven’t added anything yet. Let’s fix that.</p>
            <a href="<?= url('pages/shop.php') ?>" class="btn btn-primary btn-lg">Start Shopping</a>
        </div>
    <?php else: ?>
        <div class="cart-layout">
            <!-- Items -->
            <div class="cart-items">
                <table class="cart-table">
                    <thead>
                        <tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $row):
                        $p = $row['product']; ?>
                        <tr class="cart-row" data-id="<?= (int)$p['id'] ?>">
                            <td class="ci-product">
                                <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>">
                                <div>
                                    <a href="<?= url('pages/product.php?slug=' . urlencode($p['slug'])) ?>" class="ci-name"><?= e($p['name']) ?></a>
                                    <span class="ci-cat"><?= e($p['category_name']) ?></span>
                                </div>
                            </td>
                            <td class="ci-price" data-price="<?= (float)$p['price'] ?>"><?= formatPrice($p['price']) ?></td>
                            <td>
                                <div class="qty-control" data-qty>
                                    <button type="button" class="qty-minus" aria-label="Decrease">−</button>
                                    <input type="number" class="qty-input cart-qty" value="<?= (int)$row['qty'] ?>" min="1" max="99" data-id="<?= (int)$p['id'] ?>">
                                    <button type="button" class="qty-plus" aria-label="Increase">+</button>
                                </div>
                            </td>
                            <td class="ci-subtotal"><?= formatPrice($row['subtotal']) ?></td>
                            <td>
                                <!-- Remove: a real POST form is the server-side fallback; JS intercepts submit for AJAX -->
                                <form method="POST" action="<?= url('pages/cart.php') ?>" class="remove-form">
                                    <input type="hidden" name="remove_id" value="<?= (int)$p['id'] ?>">
                                    <button type="submit" class="ci-remove" aria-label="Remove item">×</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <a href="<?= url('pages/shop.php') ?>" class="btn btn-ghost">← Continue Shopping</a>
            </div>

            <!-- Order summary -->
            <aside class="cart-summary">
                <h3>Order Summary</h3>
                <div class="summary-row"><span>Subtotal</span><strong id="sumSubtotal"><?= formatPrice($subtotal) ?></strong></div>
                <div class="summary-row"><span>Shipping</span><strong id="sumShipping"><?= $shipping > 0 ? formatPrice($shipping) : 'Free' ?></strong></div>
                <div class="ship-progress" id="shipProgress" style="<?= $remaining > 0 ? '' : 'display:none;' ?>">
                    Add <strong id="sumRemaining"><?= formatPrice($remaining) ?></strong> more for free shipping!
                </div>
                <div class="summary-row total"><span>Total</span><strong id="sumTotal"><?= formatPrice($total) ?></strong></div>

                <div class="coupon-row">
                    <input type="text" placeholder="Coupon code" id="couponInput">
                    <button class="btn btn-ghost" onclick="GZ.applyCoupon()">Apply</button>
                </div>

                <a href="<?= url('pages/checkout.php') ?>" class="btn btn-primary btn-lg btn-block">Proceed to Checkout</a>

                <div class="pay-row">💳 Visa · Mastercard · PayPal · Payoneer</div>
                <div class="secure-badge">🔒 Secure Checkout Guaranteed</div>
            </aside>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
