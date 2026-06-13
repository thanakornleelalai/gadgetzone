<?php
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Order Confirmed';

$orderNo = isset($_GET['order']) ? sanitize($_GET['order']) : ($_SESSION['last_order'] ?? '');
if ($orderNo === '') { header('Location: ' . url('index.php')); exit; }

$stmt = $conn->prepare("SELECT * FROM orders WHERE order_number=? LIMIT 1");
$stmt->bind_param('s', $orderNo);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) { header('Location: ' . url('index.php')); exit; }

// Items
$stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url, p.slug
                        FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id
                        WHERE oi.order_id=?");
$stmt->bind_param('i', $order['id']);
$stmt->execute();
$lines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container section">
    <div class="success-card">
        <div class="success-check">✓</div>
        <h1>Thank you for your order!</h1>
        <p class="success-sub">Your order has been placed successfully. A confirmation has been sent to your email.</p>

        <div class="success-meta">
            <div><span>Order Number</span><strong><?= e($order['order_number']) ?></strong></div>
            <div><span>Date</span><strong><?= date('M j, Y', strtotime($order['created_at'])) ?></strong></div>
            <div><span>Payment</span><strong><?= e($order['payment_method']) ?></strong></div>
            <div><span>Total</span><strong class="accent"><?= formatPrice($order['total_amount']) ?></strong></div>
        </div>

        <div class="success-items">
            <?php foreach ($lines as $l): ?>
                <div class="review-item">
                    <img src="<?= e($l['image_url']) ?>" alt="<?= e($l['name']) ?>">
                    <div class="ri-info"><span class="ri-name"><?= e($l['name']) ?></span><span class="ri-qty">Qty: <?= (int)$l['quantity'] ?></span></div>
                    <span class="ri-sub"><?= formatPrice($l['price'] * $l['quantity']) ?></span>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="success-actions">
            <a href="<?= url('pages/shop.php') ?>" class="btn btn-primary">Continue Shopping</a>
            <?php if (isLoggedIn()): ?>
                <a href="<?= url('pages/myaccount.php?tab=orders') ?>" class="btn btn-outline">View My Orders</a>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
