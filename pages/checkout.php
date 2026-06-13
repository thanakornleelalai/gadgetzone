<?php
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Checkout';

$items    = getCartItems();
$subtotal = getCartTotal();
$shipping = getShippingFee($subtotal);
$total    = $subtotal + $shipping;

// Redirect if cart empty
if (empty($items)) {
    header('Location: ' . url('pages/cart.php'));
    exit;
}

$user   = getCurrentUser();
$errors = [];

// Stripe availability (keys configured & look valid)
$pk = getSetting('stripe_publishable_key', '');
$sk = getSetting('stripe_secret_key', '');
$stripeEnabled = (strpos($pk, 'pk_') === 0 && strpos($pk, 'REPLACE') === false
               && strpos($sk, 'sk_') === 0 && strpos($sk, 'REPLACE') === false);

// ── Handle order submission (non-Stripe) ──────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'place_order') {
    $first  = sanitize($_POST['first_name'] ?? '');
    $last   = sanitize($_POST['last_name'] ?? '');
    $email  = sanitize($_POST['email'] ?? '');
    $phone  = sanitize($_POST['phone'] ?? '');
    $street = sanitize($_POST['address'] ?? '');
    $city   = sanitize($_POST['city'] ?? '');
    $notes  = sanitize($_POST['notes'] ?? '');
    $pay    = sanitize($_POST['payment_method'] ?? '');

    if ($first === '' || $last === '')          { $errors[] = 'First and last name are required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'A valid email address is required.'; }
    if ($phone === '' || !preg_match('/^[0-9+\-\s()]{6,20}$/', $phone)) { $errors[] = 'A valid phone number is required.'; }
    if ($street === '')                          { $errors[] = 'Street address is required.'; }
    if ($city === '')                            { $errors[] = 'City is required.'; }
    $validPays = ['cod', 'bkash', 'nagad'];
    if (!in_array($pay, $validPays, true))       { $errors[] = 'Please select a valid payment method.'; }

    if (empty($errors)) {
        $orderNo = generateOrderNumber();
        $shipAddr = "$first $last\n$street, $city\nPhone: $phone\nEmail: $email";
        $uid = $user ? (int)$user['id'] : null;
        $payMethodLabel = ['cod'=>'Cash on Delivery','bkash'=>'bKash','nagad'=>'Nagad'][$pay];

        $stmt = $conn->prepare(
            "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, notes, payment_status)
             VALUES (?, ?, ?, 'pending', ?, ?, ?, 'unpaid')"
        );
        $stmt->bind_param('isdsss', $uid, $orderNo, $total, $payMethodLabel, $shipAddr, $notes);
        $stmt->execute();
        $orderId = $stmt->insert_id;
        $stmt->close();

        // Order items
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
        foreach ($items as $row) {
            $pid = (int)$row['product']['id'];
            $qty = (int)$row['qty'];
            $prc = (float)$row['product']['price'];
            $itemStmt->bind_param('iiid', $orderId, $pid, $qty, $prc);
            $itemStmt->execute();
        }
        $itemStmt->close();

        clearCart();
        $_SESSION['last_order'] = $orderNo;
        header('Location: ' . url('pages/order_success.php?order=' . urlencode($orderNo)));
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb">
        <a href="<?= url('index.php') ?>">Home</a> › <a href="<?= url('pages/cart.php') ?>">Cart</a> › <span>Checkout</span>
    </nav>
    <h1 class="page-title">Checkout</h1>

    <?php if ($errors): ?>
        <div class="alert alert-error">
            <strong>Please fix the following:</strong>
            <ul><?php foreach ($errors as $err) { echo '<li>' . e($err) . '</li>'; } ?></ul>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= url('pages/checkout.php') ?>" id="checkoutForm" class="checkout-layout">
        <input type="hidden" name="action" value="place_order" id="checkoutAction">

        <div class="checkout-form">
            <!-- Section 1: Contact -->
            <section class="checkout-section">
                <div class="cs-head"><span class="step-num">1</span><h3>Contact Information</h3></div>
                <div class="form-grid">
                    <div class="field"><label>First Name *</label><input type="text" name="first_name" required value="<?= e($_POST['first_name'] ?? ($user['first_name'] ?? '')) ?>"></div>
                    <div class="field"><label>Last Name *</label><input type="text" name="last_name" required value="<?= e($_POST['last_name'] ?? ($user['last_name'] ?? '')) ?>"></div>
                    <div class="field"><label>Email Address *</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? ($user['email'] ?? '')) ?>"></div>
                    <div class="field"><label>Phone Number *</label><input type="tel" name="phone" required value="<?= e($_POST['phone'] ?? ($user['phone'] ?? '')) ?>"></div>
                </div>
            </section>

            <!-- Section 2: Shipping -->
            <section class="checkout-section">
                <div class="cs-head"><span class="step-num">2</span><h3>Shipping Address</h3></div>
                <div class="field"><label>Street Address *</label><input type="text" name="address" required value="<?= e($_POST['address'] ?? ($user['address'] ?? '')) ?>"></div>
                <div class="form-grid">
                    <div class="field"><label>City *</label><input type="text" name="city" required value="<?= e($_POST['city'] ?? ($user['city'] ?? '')) ?>"></div>
                    <div class="field"><label>Country</label><input type="text" value="Bangladesh" readonly></div>
                </div>
                <div class="field"><label>Order Notes (optional)</label><textarea name="notes" rows="3" placeholder="Delivery instructions, landmarks…"><?= e($_POST['notes'] ?? '') ?></textarea></div>
            </section>

            <!-- Section 3: Payment -->
            <section class="checkout-section">
                <div class="cs-head"><span class="step-num">3</span><h3>Payment Method</h3></div>
                <div class="pay-options">
                    <label class="pay-card">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <span class="pay-card-body"><span class="pay-emoji">💵</span><span><strong>Cash on Delivery</strong><small>Pay when your order arrives</small></span></span>
                    </label>
                    <label class="pay-card">
                        <input type="radio" name="payment_method" value="bkash">
                        <span class="pay-card-body"><span class="pay-badge bkash">bKash</span><span><strong>bKash</strong><small>Pay with your bKash wallet</small></span></span>
                    </label>
                    <label class="pay-card">
                        <input type="radio" name="payment_method" value="nagad">
                        <span class="pay-card-body"><span class="pay-badge nagad">Nagad</span><span><strong>Nagad</strong><small>Pay with your Nagad wallet</small></span></span>
                    </label>
                    <?php if ($stripeEnabled): ?>
                    <label class="pay-card">
                        <input type="radio" name="payment_method" value="stripe">
                        <span class="pay-card-body"><span class="pay-emoji">💳</span><span><strong>Credit / Debit Card</strong><small>Secure payment via Stripe</small></span></span>
                    </label>
                    <?php endif; ?>
                </div>
                <?php if (!$stripeEnabled): ?>
                    <p class="pay-note">💳 Card payments (Stripe) are not configured. Add keys in Admin → Settings to enable.</p>
                <?php endif; ?>
            </section>
        </div>

        <!-- Order review -->
        <aside class="order-review">
            <div class="cs-head"><span class="step-num check">✓</span><h3>Order Review</h3></div>
            <div class="review-items">
                <?php foreach ($items as $row): $p = $row['product']; ?>
                    <div class="review-item">
                        <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>">
                        <div class="ri-info"><span class="ri-name"><?= e($p['name']) ?></span><span class="ri-qty">Qty: <?= (int)$row['qty'] ?></span></div>
                        <span class="ri-sub"><?= formatPrice($row['subtotal']) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="summary-row"><span>Subtotal</span><strong><?= formatPrice($subtotal) ?></strong></div>
            <div class="summary-row"><span>Shipping</span><strong><?= $shipping > 0 ? formatPrice($shipping) : 'Free' ?></strong></div>
            <div class="summary-row total"><span>Total</span><strong><?= formatPrice($total) ?></strong></div>

            <button type="submit" class="btn btn-primary btn-lg btn-block" id="placeOrderBtn">Place Order – <?= formatPrice($total) ?></button>
            <div class="secure-badge">🔒 Your information is secure &amp; encrypted</div>
        </aside>
    </form>
</div>

<script>
// If "Credit/Debit Card" is chosen, redirect the form to the Stripe endpoint.
(function () {
    var form = document.getElementById('checkoutForm');
    if (!form) return;
    form.addEventListener('submit', function () {
        var sel = form.querySelector('input[name="payment_method"]:checked');
        if (sel && sel.value === 'stripe') {
            form.action = '<?= url('pages/stripe_checkout.php') ?>';
            document.getElementById('checkoutAction').value = 'stripe';
        }
    });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
