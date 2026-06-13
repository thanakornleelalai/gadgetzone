<?php
/**
 * admin/layout.php
 * Admin head + sidebar. Enforces admin role. Expects optional $admin_title.
 * Pages may set $extraHead before including this file to inject <style>/<script>.
 */
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$admin = getCurrentUser();
$__current = basename($_SERVER['PHP_SELF']);
$__cssVer  = @filemtime(__DIR__ . '/admin.css') ?: time();

// Pending orders count — used by sidebar badge + topbar bell.
$pendingOrders = 0;
if ($res = $conn->query("SELECT COUNT(*) c FROM orders WHERE status='pending'")) {
    $pendingOrders = (int)($res->fetch_assoc()['c'] ?? 0);
    $res->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($admin_title) ? e($admin_title) . ' — Admin' : 'Admin — GadgetZone' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('admin/admin.css') ?>?v=<?= $__cssVer ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🛠️</text></svg>">
    <?= $extraHead ?? '' ?>
</head>
<body data-base="<?= e(BASE_URL) ?>">
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="admin-brand" href="<?= url('index.php') ?>" title="Back to storefront">
            <span class="brand-mark">⚡</span> Gadget<span class="brand-accent">Zone</span>
        </a>
        <nav class="admin-nav">
            <a href="<?= url('admin/index.php') ?>"    class="<?= $__current==='index.php'?'active':'' ?>">📊 Dashboard</a>
            <a href="<?= url('admin/products.php') ?>" class="<?= $__current==='products.php'?'active':'' ?>">📦 Products</a>
            <a href="<?= url('admin/orders.php') ?>"   class="<?= $__current==='orders.php'?'active':'' ?>">
                🧾 Orders
                <?php if ($pendingOrders > 0): ?><span class="nav-badge"><?= $pendingOrders ?></span><?php endif; ?>
            </a>
            <a href="<?= url('admin/users.php') ?>"    class="<?= $__current==='users.php'?'active':'' ?>">👥 Users</a>
            <a href="<?= url('admin/settings.php') ?>" class="<?= $__current==='settings.php'?'active':'' ?>">⚙️ Settings</a>
            <a href="<?= url('index.php') ?>" target="_blank" rel="noopener">🏠 View Store ↗</a>
        </nav>
        <div class="admin-sidebar-foot">
            <div class="admin-sidebar-user">
                <span class="avatar-mini"><?= e(initials($admin['first_name'], $admin['last_name'])) ?></span>
                <div class="sb-user-meta">
                    <strong><?= e($admin['first_name'] . ' ' . $admin['last_name']) ?></strong>
                    <span><?= e(str_replace('_', ' ', $admin['role'])) ?></span>
                </div>
            </div>
            <a href="<?= url('pages/logout.php') ?>" class="logout-link">🚪 Logout</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="admin-toggle" id="adminToggle" aria-label="Menu">☰</button>
            <h1 class="admin-page-title"><?= isset($admin_title) ? e($admin_title) : 'Dashboard' ?></h1>
            <a class="topbar-bell" href="<?= url('admin/orders.php?status=pending') ?>" title="Pending orders">
                🔔
                <?php if ($pendingOrders > 0): ?><span class="bell-badge"><?= $pendingOrders ?></span><?php endif; ?>
            </a>
            <div class="admin-user">
                <span class="avatar-mini"><?= e(initials($admin['first_name'], $admin['last_name'])) ?></span>
                <span class="admin-user-name"><?= e($admin['first_name']) ?> <em>(<?= e($admin['role']) ?>)</em></span>
            </div>
        </header>
        <div class="admin-body">
