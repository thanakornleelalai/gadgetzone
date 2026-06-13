<?php
/**
 * pages/cart_action.php
 * AJAX endpoint for cart operations. Returns JSON.
 * Actions: add | update | remove | clear
 */
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['product_id'] ?? 0);
$qty    = (int)($_POST['quantity'] ?? 1);

switch ($action) {
    case 'add':
        // Validate the product exists and is in stock before adding.
        $stmt = $conn->prepare("SELECT name, stock FROM products WHERE id=? LIMIT 1");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $prod = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if (!$prod) {
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }
        addToCart($id, max(1, $qty));
        $msg = e($prod['name']) . ' added to cart';
        break;

    case 'update':
        updateCartQty($id, $qty);
        $msg = 'Cart updated';
        break;

    case 'remove':
        removeFromCart($id);
        $msg = 'Item removed';
        break;

    case 'clear':
        clearCart();
        $msg = 'Cart cleared';
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
}

$subtotal = getCartTotal();
$shipping = getShippingFee($subtotal);

// Per-item subtotal for the row that was just changed (handy for the cart page).
$itemSubtotal = null;
if ($id > 0 && isset(getCart()[$id])) {
    $stmt = $conn->prepare("SELECT price FROM products WHERE id=? LIMIT 1");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($row) {
        $itemSubtotal = formatPrice((float)$row['price'] * getCart()[$id]);
    }
}

echo json_encode([
    'success'          => true,
    'message'          => $msg,
    'cart_count'       => getCartCount(),
    'subtotal'         => $subtotal,
    'formatted_subtotal' => formatPrice($subtotal),
    'shipping'         => $shipping,
    'formatted_shipping' => $shipping > 0 ? formatPrice($shipping) : 'Free',
    'formatted_total'  => formatPrice($subtotal + $shipping),
    'item_subtotal'    => $itemSubtotal,
    'free_threshold'   => FREE_SHIPPING_THRESHOLD,
    'remaining_for_free' => max(0, FREE_SHIPPING_THRESHOLD - $subtotal),
    'formatted_remaining' => formatPrice(max(0, FREE_SHIPPING_THRESHOLD - $subtotal)),
]);
