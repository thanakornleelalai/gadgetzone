<?php
require_once __DIR__ . '/../includes/db.php';
requireLogin();
$page_title = 'My Account';

$user = getCurrentUser();
$tab  = $_GET['tab'] ?? 'dashboard';
$validTabs = ['dashboard', 'orders', 'profile', 'password'];
if (!in_array($tab, $validTabs, true)) { $tab = 'dashboard'; }

$notice = ['type' => '', 'msg' => ''];

// ── Handle profile update ─────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    $first = sanitize($_POST['first_name'] ?? '');
    $last  = sanitize($_POST['last_name'] ?? '');
    $phone = sanitize($_POST['phone'] ?? '');
    $addr  = sanitize($_POST['address'] ?? '');
    $city  = sanitize($_POST['city'] ?? '');
    $avatarName = $user['avatar'];

    // Avatar upload
    if (!empty($_FILES['avatar']['name']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
        finfo_close($finfo);
        if (isset($allowed[$mime]) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
            $dir = __DIR__ . '/../assets/uploads/avatars/';
            if (!is_dir($dir)) { @mkdir($dir, 0775, true); }
            $fname = 'u' . $user['id'] . '_' . time() . '.' . $allowed[$mime];
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dir . $fname)) {
                // remove old file
                if ($avatarName && is_file($dir . $avatarName)) { @unlink($dir . $avatarName); }
                $avatarName = $fname;
            }
        } else {
            $notice = ['type' => 'error', 'msg' => 'Avatar must be JPG/PNG/WebP/GIF under 2MB.'];
        }
    }

    if ($notice['type'] !== 'error') {
        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, address=?, city=?, avatar=? WHERE id=?");
        $stmt->bind_param('ssssssi', $first, $last, $phone, $addr, $city, $avatarName, $user['id']);
        $stmt->execute();
        $stmt->close();
        $user = getCurrentUser();
        $notice = ['type' => 'success', 'msg' => 'Profile updated successfully.'];
    }
    $tab = 'profile';
}

// ── Handle password change ────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'change_password') {
    $cur  = $_POST['current'] ?? '';
    $new  = $_POST['new'] ?? '';
    $conf = $_POST['confirm'] ?? '';

    if (!password_verify($cur, $user['password'])) {
        $notice = ['type' => 'error', 'msg' => 'Current password is incorrect.'];
    } elseif (strlen($new) < 6) {
        $notice = ['type' => 'error', 'msg' => 'New password must be at least 6 characters.'];
    } elseif ($new !== $conf) {
        $notice = ['type' => 'error', 'msg' => 'New passwords do not match.'];
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->bind_param('si', $hash, $user['id']);
        $stmt->execute();
        $stmt->close();
        $user = getCurrentUser();
        $notice = ['type' => 'success', 'msg' => 'Password updated successfully.'];
    }
    $tab = 'password';
}

// ── Order data ────────────────────────────────────────────
function fetchOrders($conn, $uid, $limit = null)
{
    $sql = "SELECT o.*, (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id=o.id) AS item_count
            FROM orders o WHERE o.user_id=? ORDER BY o.created_at DESC";
    if ($limit) { $sql .= " LIMIT " . (int)$limit; }
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $uid);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

$allOrders = fetchOrders($conn, $user['id']);
$totalOrders = count($allOrders);
$delivered = count(array_filter($allOrders, fn($o) => $o['status'] === 'delivered'));
$totalSpent = array_sum(array_column($allOrders, 'total_amount'));
$recent = array_slice($allOrders, 0, 5);

$avatarUrl = $user['avatar'] ? url('assets/uploads/avatars/' . $user['avatar']) : '';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
    <nav class="breadcrumb"><a href="<?= url('index.php') ?>">Home</a> › <span>My Account</span></nav>

    <div class="account-layout">
        <!-- Sidebar -->
        <aside class="account-sidebar">
            <div class="account-user">
                <?php if ($avatarUrl): ?>
                    <img class="account-avatar-img" src="<?= e($avatarUrl) ?>" alt="avatar">
                <?php else: ?>
                    <span class="account-avatar"><?= e(initials($user['first_name'], $user['last_name'])) ?></span>
                <?php endif; ?>
                <strong><?= e($user['first_name'] . ' ' . $user['last_name']) ?></strong>
                <span><?= e($user['email']) ?></span>
            </div>
            <nav class="account-nav">
                <a href="?tab=dashboard" class="<?= $tab==='dashboard'?'active':'' ?>">📊 Dashboard</a>
                <a href="?tab=orders"    class="<?= $tab==='orders'?'active':'' ?>">📦 My Orders</a>
                <a href="?tab=profile"   class="<?= $tab==='profile'?'active':'' ?>">👤 Profile</a>
                <a href="?tab=password"  class="<?= $tab==='password'?'active':'' ?>">🔑 Change Password</a>
                <a href="<?= url('pages/logout.php') ?>" class="logout-link">🚪 Logout</a>
            </nav>
        </aside>

        <!-- Content -->
        <section class="account-content">
            <?php if ($notice['msg']): ?>
                <div class="alert alert-<?= $notice['type'] === 'success' ? 'success' : 'error' ?>"><?= e($notice['msg']) ?></div>
            <?php endif; ?>

            <?php if ($tab === 'dashboard'): ?>
                <h2>Welcome back, <?= e($user['first_name']) ?>! 👋</h2>
                <div class="stat-cards">
                    <div class="stat-card"><span class="stat-num"><?= $totalOrders ?></span><span class="stat-label">Total Orders</span></div>
                    <div class="stat-card"><span class="stat-num"><?= $delivered ?></span><span class="stat-label">Delivered</span></div>
                    <div class="stat-card"><span class="stat-num"><?= formatPrice($totalSpent) ?></span><span class="stat-label">Total Spent</span></div>
                </div>

                <h3 class="block-title">Recent Orders</h3>
                <?php if ($recent): ?>
                    <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Order #</th><th>Date</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
                        <tbody>
                        <?php foreach ($recent as $o): ?>
                            <tr>
                                <td><?= e($o['order_number']) ?></td>
                                <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                                <td><?= formatPrice($o['total_amount']) ?></td>
                                <td><span class="status-badge sb-<?= statusColor($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><?= e($o['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p class="muted">You haven’t placed any orders yet. <a href="<?= url('pages/shop.php') ?>">Start shopping →</a></p>
                <?php endif; ?>

            <?php elseif ($tab === 'orders'): ?>
                <h2>My Orders</h2>
                <?php if ($allOrders): ?>
                    <div class="table-wrap">
                    <table class="data-table">
                        <thead><tr><th>Order #</th><th>Date</th><th>Items</th><th>Total</th><th>Status</th><th>Payment</th></tr></thead>
                        <tbody>
                        <?php foreach ($allOrders as $o): ?>
                            <tr>
                                <td><?= e($o['order_number']) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($o['created_at'])) ?></td>
                                <td><?= (int)$o['item_count'] ?></td>
                                <td><?= formatPrice($o['total_amount']) ?></td>
                                <td><span class="status-badge sb-<?= statusColor($o['status']) ?>"><?= ucfirst($o['status']) ?></span></td>
                                <td><?= e($o['payment_method']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                <?php else: ?>
                    <p class="muted">No orders yet. <a href="<?= url('pages/shop.php') ?>">Browse the shop →</a></p>
                <?php endif; ?>

            <?php elseif ($tab === 'profile'): ?>
                <h2>Profile</h2>
                <form method="POST" action="?tab=profile" enctype="multipart/form-data" class="account-form">
                    <input type="hidden" name="action" value="update_profile">
                    <div class="avatar-edit">
                        <?php if ($avatarUrl): ?>
                            <img class="account-avatar-img lg" id="avatarPreview" src="<?= e($avatarUrl) ?>" alt="avatar">
                        <?php else: ?>
                            <span class="account-avatar lg" id="avatarPreview"><?= e(initials($user['first_name'], $user['last_name'])) ?></span>
                        <?php endif; ?>
                        <label class="btn btn-ghost">Change Photo<input type="file" name="avatar" accept="image/*" hidden id="avatarInput"></label>
                    </div>
                    <div class="form-grid">
                        <div class="field"><label>First Name</label><input type="text" name="first_name" value="<?= e($user['first_name']) ?>" required></div>
                        <div class="field"><label>Last Name</label><input type="text" name="last_name" value="<?= e($user['last_name']) ?>" required></div>
                    </div>
                    <div class="field"><label>Email <small>(cannot be changed)</small></label><input type="email" value="<?= e($user['email']) ?>" disabled></div>
                    <div class="field"><label>Phone Number</label><input type="tel" name="phone" value="<?= e($user['phone']) ?>"></div>
                    <div class="field"><label>Address</label><textarea name="address" rows="3"><?= e($user['address']) ?></textarea></div>
                    <div class="field"><label>City</label><input type="text" name="city" value="<?= e($user['city']) ?>"></div>
                    <button type="submit" class="btn btn-primary btn-lg">Save Changes</button>
                </form>

            <?php elseif ($tab === 'password'): ?>
                <h2>Change Password</h2>
                <form method="POST" action="?tab=password" class="account-form narrow">
                    <input type="hidden" name="action" value="change_password">
                    <div class="field"><label>Current Password *</label><input type="password" name="current" required></div>
                    <div class="field"><label>New Password * <small>(min 6 chars)</small></label><input type="password" name="new" required minlength="6"></div>
                    <div class="field"><label>Confirm New Password *</label><input type="password" name="confirm" required minlength="6"></div>
                    <button type="submit" class="btn btn-primary btn-lg">Update Password</button>
                </form>
            <?php endif; ?>
        </section>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
