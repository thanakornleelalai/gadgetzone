<?php
/**
 * admin/orders.php
 * Order management — list, search, paginate, inspect items, update status.
 * POST is handled BEFORE layout.php emits any HTML.
 * Detail view: ?id=ORDER_ID
 */
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id     = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $valid  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if ($id > 0 && in_array($status, $valid, true)) {
            $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
            $stmt->bind_param('si', $status, $id);
            $stmt->execute();
            $stmt->close();
        }
        $back = !empty($_POST['return_to_detail']) ? ('?id=' . $id . '&') : '?';
        header('Location: ' . url('admin/orders.php' . $back . 'flash=' . urlencode('Order status updated.')));
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        header('Location: ' . url('admin/orders.php?flash=' . urlencode('Order deleted.')));
        exit;
    }
}

$admin_title = 'Orders';
require_once __DIR__ . '/layout.php';

$flash  = isset($_GET['flash']) ? sanitize($_GET['flash']) : '';
$filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$valid  = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
$viewId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// ─── DETAIL VIEW ─────────────────────────────────────────────
if ($viewId > 0) {
    $stmt = $conn->prepare("SELECT o.*, u.first_name, u.last_name, u.email, u.phone
                            FROM orders o LEFT JOIN users u ON u.id=o.user_id
                            WHERE o.id=? LIMIT 1");
    $stmt->bind_param('i', $viewId);
    $stmt->execute();
    $ord = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$ord) {
        echo '<div class="alert">Order not found. <a href="' . url('admin/orders.php') . '">Back to orders</a></div>';
        require_once __DIR__ . '/footer.php';
        exit;
    }

    $stmt = $conn->prepare("SELECT oi.*, p.name, p.image_url
                            FROM order_items oi LEFT JOIN products p ON p.id=oi.product_id
                            WHERE oi.order_id=?");
    $stmt->bind_param('i', $viewId);
    $stmt->execute();
    $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $custName = trim(($ord['first_name'] ?? '') . ' ' . ($ord['last_name'] ?? '')) ?: 'Guest';
    ?>
    <?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>
    <div class="admin-card-head bare">
        <a href="<?= url('admin/orders.php') ?>" class="btn btn-ghost sm">← All orders</a>
        <h2 style="margin:0;font-size:1.2rem;">Order <?= e($ord['order_number']) ?></h2>
    </div>

    <div class="dashboard-grid">
        <div class="admin-card">
            <div class="admin-card-head"><h3>Customer</h3></div>
            <p><strong><?= e($custName) ?></strong></p>
            <p class="muted"><?= e($ord['email'] ?? '—') ?></p>
            <p class="muted"><?= e($ord['phone'] ?? '—') ?></p>

            <div class="admin-card-head" style="margin-top:18px"><h3>Shipping Address</h3></div>
            <p class="muted" style="white-space:pre-wrap"><?= e($ord['shipping_address']) ?></p>

            <?php if (!empty($ord['notes'])): ?>
                <div class="admin-card-head" style="margin-top:18px"><h3>Order Notes</h3></div>
                <p class="muted" style="white-space:pre-wrap"><?= e($ord['notes']) ?></p>
            <?php endif; ?>
        </div>

        <div class="admin-card">
            <div class="admin-card-head"><h3>Status & Payment</h3></div>
            <p>Payment: <strong><?= e(ucfirst($ord['payment_method'])) ?></strong></p>
            <p>Date: <strong><?= date('M j, Y g:i A', strtotime($ord['created_at'])) ?></strong></p>
            <p>Current status: <span class="status-badge sb-<?= statusColor($ord['status']) ?>"><?= ucfirst($ord['status']) ?></span></p>

            <form method="POST" style="margin-top:14px">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?= (int)$ord['id'] ?>">
                <input type="hidden" name="return_to_detail" value="1">
                <div class="field">
                    <label>Change status</label>
                    <select name="status" class="mini-select">
                        <?php foreach ($valid as $s): ?>
                            <option value="<?= $s ?>" <?= $ord['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-primary sm">Update Status</button>
            </form>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head"><h3>Items</h3></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th></th><th>Product</th><th>Unit Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php foreach ($items as $it): ?>
                <tr>
                    <td><img class="cell-thumb" src="<?= e($it['image_url'] ?? '') ?>" alt=""></td>
                    <td><?= e($it['name'] ?? 'Product') ?></td>
                    <td><?= formatPrice($it['price']) ?></td>
                    <td><?= (int)$it['quantity'] ?></td>
                    <td><strong><?= formatPrice($it['price'] * $it['quantity']) ?></strong></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($items)): ?><tr><td colspan="5" class="muted center">No line items.</td></tr><?php endif; ?>
            </tbody>
            <tfoot>
                <tr><td colspan="4" style="text-align:right"><strong>Grand Total</strong></td>
                    <td><strong style="color:var(--accent-light);font-size:1.1rem"><?= formatPrice($ord['total_amount']) ?></strong></td></tr>
            </tfoot>
        </table>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/footer.php';
    exit;
}

// ─── LIST VIEW ───────────────────────────────────────────────
$where  = [];
$params = [];
$types  = '';
if (in_array($filter, $valid, true)) { $where[] = 'o.status=?'; $params[] = $filter; $types .= 's'; }
if ($search !== '') {
    $like = "%$search%";
    $where[] = '(o.order_number LIKE ? OR u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like, $like, $like);
    $types .= 'ssss';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// Count for pagination
$countSql = "SELECT COUNT(*) c FROM orders o LEFT JOIN users u ON u.id=o.user_id $whereSql";
if ($params) {
    $st = $conn->prepare($countSql); $st->bind_param($types, ...$params); $st->execute();
    $totalRows = (int)($st->get_result()->fetch_assoc()['c'] ?? 0); $st->close();
} else {
    $totalRows = (int)($conn->query($countSql)->fetch_assoc()['c'] ?? 0);
}

$perPage = 15;
$page    = max(1, (int)($_GET['page'] ?? 1));
$pages   = max(1, (int)ceil($totalRows / $perPage));
$page    = min($page, $pages);
$offset  = ($page - 1) * $perPage;

$sql = "SELECT o.*, u.first_name, u.last_name, u.email
        FROM orders o LEFT JOIN users u ON u.id=o.user_id
        $whereSql
        ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset";
if ($params) {
    $st = $conn->prepare($sql); $st->bind_param($types, ...$params); $st->execute();
    $orders = $st->get_result()->fetch_all(MYSQLI_ASSOC); $st->close();
} else {
    $orders = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

// Item counts for displayed page
$itemsByOrder = [];
if ($orders) {
    $ids = implode(',', array_map(fn($o) => (int)$o['id'], $orders));
    $isql = "SELECT order_id, COUNT(*) c FROM order_items WHERE order_id IN ($ids) GROUP BY order_id";
    if ($res = $conn->query($isql)) {
        while ($r = $res->fetch_assoc()) { $itemsByOrder[(int)$r['order_id']] = (int)$r['c']; }
        $res->free();
    }
}

// Helper to build query strings preserving filters
$qsBase = http_build_query(array_filter([
    'status' => $filter ?: null,
    'q'      => $search ?: null,
]));
?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-card-head bare">
    <form method="GET" class="admin-search">
        <?php if ($filter): ?><input type="hidden" name="status" value="<?= e($filter) ?>"><?php endif; ?>
        <input type="search" name="q" placeholder="Search by order # or customer…" value="<?= e($search) ?>">
        <button class="btn btn-ghost sm" type="submit">Search</button>
    </form>
    <div class="filter-tabs">
        <a href="<?= url('admin/orders.php' . ($search?'?q='.urlencode($search):'')) ?>" class="<?= $filter===''?'active':'' ?>">All</a>
        <?php foreach ($valid as $s):
            $q = http_build_query(array_filter(['status'=>$s,'q'=>$search ?: null]));
        ?>
            <a href="<?= url('admin/orders.php?' . $q) ?>" class="<?= $filter===$s?'active':'' ?>"><?= ucfirst($s) ?></a>
        <?php endforeach; ?>
    </div>
</div>

<div class="admin-card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th>Order #</th><th>Customer</th><th>Date</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o):
            $itemCount = $itemsByOrder[(int)$o['id']] ?? 0;
            $custName = trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: 'Guest';
        ?>
            <tr>
                <td><a href="<?= url('admin/orders.php?id=' . (int)$o['id']) ?>"><strong><?= e($o['order_number']) ?></strong></a></td>
                <td><?= e($custName) ?><br><small class="muted"><?= e($o['email'] ?? '—') ?></small></td>
                <td><?= date('M j, Y', strtotime($o['created_at'])) ?><br><small class="muted"><?= date('g:i A', strtotime($o['created_at'])) ?></small></td>
                <td><?= $itemCount ?></td>
                <td><strong><?= formatPrice($o['total_amount']) ?></strong></td>
                <td><?= e(ucfirst($o['payment_method'])) ?></td>
                <td><span class="status-badge sb-<?= statusColor($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                <td class="actions-cell">
                    <a class="icon-act" title="View" href="<?= url('admin/orders.php?id=' . (int)$o['id']) ?>">👁️</a>

                    <form method="POST" class="inline-form status-form">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                        <select name="status" onchange="this.form.submit()" class="mini-select">
                            <?php foreach ($valid as $s): ?>
                                <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <form method="POST" class="inline-form" onsubmit="return confirm('Delete this order permanently?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int)$o['id'] ?>">
                        <button class="icon-act danger" title="Delete">🗑️</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($orders)): ?><tr><td colspan="8" class="muted center">No orders found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <a href="<?= url('admin/orders.php?' . ($qsBase ? $qsBase . '&' : '') . 'page=' . ($page - 1)) ?>">← Prev</a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
                <a class="<?= $i===$page?'active':'' ?>" href="<?= url('admin/orders.php?' . ($qsBase ? $qsBase . '&' : '') . 'page=' . $i) ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $pages): ?>
                <a href="<?= url('admin/orders.php?' . ($qsBase ? $qsBase . '&' : '') . 'page=' . ($page + 1)) ?>">Next →</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
