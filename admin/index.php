<?php
$admin_title = 'Dashboard';
require_once __DIR__ . '/layout.php';

// Stats
$totalProducts = (int)($conn->query("SELECT COUNT(*) c FROM products")->fetch_assoc()['c'] ?? 0);
$totalOrders   = (int)($conn->query("SELECT COUNT(*) c FROM orders")->fetch_assoc()['c'] ?? 0);
$totalUsers    = (int)($conn->query("SELECT COUNT(*) c FROM users")->fetch_assoc()['c'] ?? 0);
$revenue       = (float)($conn->query("SELECT COALESCE(SUM(total_amount),0) s FROM orders WHERE status<>'cancelled'")->fetch_assoc()['s'] ?? 0);

$pending     = $pendingOrders; // from layout.php
$lowStock    = (int)($conn->query("SELECT COUNT(*) c FROM products WHERE stock < 15")->fetch_assoc()['c'] ?? 0);
$newUsersMo  = (int)($conn->query("SELECT COUNT(*) c FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetch_assoc()['c'] ?? 0);

// Recent orders
$recent = [];
$sql = "SELECT o.*, u.first_name, u.last_name FROM orders o
        LEFT JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8";
if ($res = $conn->query($sql)) { while ($r = $res->fetch_assoc()) { $recent[] = $r; } $res->free(); }

// Top selling products
$topProducts = [];
$sql = "SELECT p.id, p.name, p.price, p.image_url, COALESCE(SUM(oi.quantity),0) AS sold
        FROM products p
        LEFT JOIN order_items oi ON oi.product_id = p.id
        GROUP BY p.id
        ORDER BY sold DESC, p.id DESC
        LIMIT 5";
if ($res = $conn->query($sql)) { while ($r = $res->fetch_assoc()) { $topProducts[] = $r; } $res->free(); }
?>
<div class="kpi-grid">
    <div class="kpi-card"><span class="kpi-icon">💰</span><div><span class="kpi-num"><?= formatPrice($revenue) ?></span><span class="kpi-label">Total Revenue</span></div></div>
    <div class="kpi-card">
        <span class="kpi-icon">🧾</span>
        <div>
            <span class="kpi-num"><?= $totalOrders ?></span>
            <span class="kpi-label">Orders <?php if ($pending): ?><em class="kpi-sub-badge amber"><?= $pending ?> pending</em><?php endif; ?></span>
        </div>
    </div>
    <div class="kpi-card"><span class="kpi-icon">📦</span><div><span class="kpi-num"><?= $totalProducts ?></span><span class="kpi-label">Products</span></div></div>
    <div class="kpi-card">
        <span class="kpi-icon">👥</span>
        <div>
            <span class="kpi-num"><?= $totalUsers ?></span>
            <span class="kpi-label">Users <?php if ($newUsersMo): ?><em class="kpi-sub-badge green">+<?= $newUsersMo ?> this month</em><?php endif; ?></span>
        </div>
    </div>
</div>

<div class="alert-row">
    <?php if ($pending): ?><div class="mini-alert amber"><?= $pending ?> order<?= $pending==1?'':'s' ?> pending</div><?php endif; ?>
    <?php if ($lowStock): ?><div class="mini-alert red"><?= $lowStock ?> product<?= $lowStock==1?'':'s' ?> low on stock</div><?php endif; ?>
</div>

<div class="dashboard-grid">
    <div class="admin-card">
        <div class="admin-card-head"><h3>Recent Orders</h3><a href="<?= url('admin/orders.php') ?>" class="btn btn-ghost sm">View all →</a></div>
        <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php if ($recent): foreach ($recent as $o): ?>
                <tr>
                    <td><strong><?= e($o['order_number']) ?></strong></td>
                    <td><?= e(trim(($o['first_name'] ?? '') . ' ' . ($o['last_name'] ?? '')) ?: 'Guest') ?></td>
                    <td><?= formatPrice($o['total_amount']) ?></td>
                    <td><span class="status-badge sb-<?= statusColor($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                    <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                    <td><a class="btn btn-ghost sm" href="<?= url('admin/orders.php?id=' . (int)$o['id']) ?>">View</a></td>
                </tr>
            <?php endforeach; else: ?>
                <tr><td colspan="6" class="muted center">No orders yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
        </div>
    </div>

    <div class="admin-card">
        <div class="admin-card-head"><h3>Top Selling</h3></div>
        <?php if ($topProducts): ?>
            <ul class="top-list">
                <?php foreach ($topProducts as $tp): ?>
                    <li>
                        <img src="<?= e($tp['image_url']) ?>" alt="">
                        <div class="top-meta">
                            <strong><?= e($tp['name']) ?></strong>
                            <span><?= formatPrice($tp['price']) ?></span>
                        </div>
                        <span class="top-sold"><?= (int)$tp['sold'] ?> sold</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">No sales data yet.</p>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/footer.php'; ?>
