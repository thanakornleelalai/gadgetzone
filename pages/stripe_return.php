<?php
/**
 * pages/stripe_return.php
 * Stripe redirects here after payment. Verify the session, mark the order
 * paid, clear the cart, and forward to the success page.
 */
require_once __DIR__ . '/../includes/db.php';

$sessionId = $_GET['session_id'] ?? '';
if ($sessionId === '') { header('Location: ' . url('pages/cart.php')); exit; }

$sk = getSetting('stripe_secret_key', '');

// Retrieve the session from Stripe
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions/' . urlencode($sessionId));
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD        => $sk . ':',
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
curl_close($ch);

$session = json_decode($response, true);
$paid = $session && (($session['payment_status'] ?? '') === 'paid');

// Find the order by session id
$stmt = $conn->prepare("SELECT id, order_number, payment_status FROM orders WHERE stripe_session_id=? LIMIT 1");
$stmt->bind_param('s', $sessionId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: ' . url('pages/checkout.php?stripe_error=' . urlencode('Order not found for this session.')));
    exit;
}

if ($paid) {
    if ($order['payment_status'] !== 'paid') {
        $stmt = $conn->prepare("UPDATE orders SET payment_status='paid', status='processing' WHERE id=?");
        $stmt->bind_param('i', $order['id']);
        $stmt->execute();
        $stmt->close();
    }
    clearCart();
    $_SESSION['last_order'] = $order['order_number'];
    header('Location: ' . url('pages/order_success.php?order=' . urlencode($order['order_number'])));
    exit;
}

// Not paid — send back to checkout with a message
header('Location: ' . url('pages/checkout.php?stripe_error=' . urlencode('Payment was not completed. Please try again.')));
exit;
