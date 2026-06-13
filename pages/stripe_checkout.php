<?php
/**
 * pages/stripe_checkout.php
 * Validates checkout form, creates a pending order, then a Stripe Checkout
 * session, stores the session id on the order, and redirects to Stripe.
 */
require_once __DIR__ . '/../includes/db.php';

$items    = getCartItems();
$subtotal = getCartTotal();
$shipping = getShippingFee($subtotal);
$total    = $subtotal + $shipping;

if (empty($items)) { header('Location: ' . url('pages/cart.php')); exit; }

$pk = getSetting('stripe_publishable_key', '');
$sk = getSetting('stripe_secret_key', '');
$stripeEnabled = (strpos($pk, 'pk_') === 0 && strpos($pk, 'REPLACE') === false
               && strpos($sk, 'sk_') === 0 && strpos($sk, 'REPLACE') === false);

function stripeFail($msg)
{
    header('Location: ' . url('pages/checkout.php?stripe_error=' . urlencode($msg)));
    exit;
}

if (!$stripeEnabled) { stripeFail('Card payments are not configured.'); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ' . url('pages/checkout.php')); exit; }

// Validate the same required fields as checkout
$first  = sanitize($_POST['first_name'] ?? '');
$last   = sanitize($_POST['last_name'] ?? '');
$email  = sanitize($_POST['email'] ?? '');
$phone  = sanitize($_POST['phone'] ?? '');
$street = sanitize($_POST['address'] ?? '');
$city   = sanitize($_POST['city'] ?? '');
$notes  = sanitize($_POST['notes'] ?? '');

if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $street === '' || $city === '') {
    stripeFail('Please complete all required fields before paying by card.');
}

$user    = getCurrentUser();
$uid     = $user ? (int)$user['id'] : null;
$orderNo = generateOrderNumber();
$shipAddr = "$first $last\n$street, $city\nPhone: $phone\nEmail: $email";

// Create the pending (unpaid) order first
$stmt = $conn->prepare(
    "INSERT INTO orders (user_id, order_number, total_amount, status, payment_method, shipping_address, notes, payment_status)
     VALUES (?, ?, ?, 'pending', 'Credit/Debit Card (Stripe)', ?, ?, 'unpaid')"
);
$stmt->bind_param('isdss', $uid, $orderNo, $total, $shipAddr, $notes);
$stmt->execute();
$orderId = $stmt->insert_id;
$stmt->close();

$itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?,?,?,?)");
foreach ($items as $row) {
    $pid = (int)$row['product']['id']; $qty = (int)$row['qty']; $prc = (float)$row['product']['price'];
    $itemStmt->bind_param('iiid', $orderId, $pid, $qty, $prc);
    $itemStmt->execute();
}
$itemStmt->close();

// Build Stripe line items (amounts converted from BDT to active currency)
$currency = getStripeCurrencyCode();
$fields = [
    'mode'                => 'payment',
    'success_url'         => (isHttps() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('pages/stripe_return.php') . '?session_id={CHECKOUT_SESSION_ID}',
    'cancel_url'          => (isHttps() ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . url('pages/checkout.php') . '?stripe_error=' . urlencode('Payment cancelled.'),
    'customer_email'      => $email,
    'client_reference_id' => $orderNo,
    'metadata[order_id]'  => $orderId,
    'metadata[order_no]'  => $orderNo,
];

$i = 0;
foreach ($items as $row) {
    $p = $row['product'];
    $fields["line_items[$i][price_data][currency]"]              = $currency;
    $fields["line_items[$i][price_data][product_data][name]"]    = $p['name'];
    $fields["line_items[$i][price_data][unit_amount]"]           = getStripeAmount((float)$p['price']);
    $fields["line_items[$i][quantity]"]                          = (int)$row['qty'];
    $i++;
}
if ($shipping > 0) {
    $fields["line_items[$i][price_data][currency]"]           = $currency;
    $fields["line_items[$i][price_data][product_data][name]"] = 'Shipping';
    $fields["line_items[$i][price_data][unit_amount]"]        = getStripeAmount((float)$shipping);
    $fields["line_items[$i][quantity]"]                       = 1;
}

function isHttps()
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || ($_SERVER['SERVER_PORT'] ?? '') == 443
        || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

// Call Stripe API
$ch = curl_init('https://api.stripe.com/v1/checkout/sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_USERPWD        => $sk . ':',
    CURLOPT_POSTFIELDS     => http_build_query($fields),
    CURLOPT_TIMEOUT        => 30,
]);
$response = curl_exec($ch);
$curlErr  = curl_error($ch);
curl_close($ch);

if ($response === false) { stripeFail('Could not reach Stripe: ' . $curlErr); }
$data = json_decode($response, true);

if (!empty($data['error'])) {
    stripeFail('Stripe error: ' . ($data['error']['message'] ?? 'unknown'));
}
if (empty($data['url']) || empty($data['id'])) {
    stripeFail('Stripe did not return a checkout session.');
}

// Save the session id on the order
$stmt = $conn->prepare("UPDATE orders SET stripe_session_id=? WHERE id=?");
$stmt->bind_param('si', $data['id'], $orderId);
$stmt->execute();
$stmt->close();

// Off to Stripe's hosted page
header('Location: ' . $data['url']);
exit;
