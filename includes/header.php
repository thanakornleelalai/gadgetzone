<?php
/**
 * includes/header.php
 * Shared storefront <head> + navigation bar.
 * Expects optional $page_title before inclusion.
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/db.php';
}
$__cartCount = getCartCount();
$__user      = getCurrentUser();
$__cur       = getActiveCurrency();
$__cats      = [];
if ($res = $conn->query("SELECT name, slug, icon FROM categories ORDER BY id")) {
    while ($r = $res->fetch_assoc()) { $__cats[] = $r; }
    $res->free();
}
$__cssVer = @filemtime(__DIR__ . '/../assets/css/style.css') ?: time();
?>
<?php
$__lang = gz_currentLang();
$__otherLang = $__lang === 'th' ? 'en' : 'th';
?>
<!DOCTYPE html>
<html lang="<?= e($__lang) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' — GadgetZone' : 'GadgetZone — Next-Level Technology' ?></title>
    <meta name="description" content="GadgetZone — premium gadgets, smartphones, laptops, audio and more.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;600;700;800&family=DM+Sans:wght@400;500;700&family=Sarabun:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= url('assets/css/style.css') ?>?v=<?= $__cssVer ?>">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>⚡</text></svg>">
</head>
<body data-base="<?= e(BASE_URL) ?>" data-lang="<?= e($__lang) ?>">
<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= url('index.php') ?>">
            <span class="brand-mark">⚡</span> Gadget<span class="brand-accent">Zone</span>
        </a>

        <button class="nav-toggle" id="navToggle" aria-label="Menu">☰</button>

        <nav class="main-nav" id="mainNav">
            <a href="<?= url('index.php') ?>"><?= e(t('nav.home')) ?></a>
            <a href="<?= url('pages/shop.php') ?>"><?= e(t('nav.shop')) ?></a>
            <div class="nav-dropdown">
                <a href="<?= url('pages/shop.php') ?>"><?= e(t('nav.categories')) ?> ▾</a>
                <div class="nav-dropdown-menu">
                    <?php foreach ($__cats as $c): ?>
                        <a href="<?= url('pages/shop.php?cat=' . urlencode($c['slug'])) ?>"><?= e($c['icon']) ?> <?= e($c['name']) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <a href="<?= url('pages/shop.php?badge=SALE') ?>"><?= e(t('nav.deals')) ?></a>
        </nav>

        <form class="header-search" action="<?= url('pages/shop.php') ?>" method="GET" role="search">
            <input type="search" name="search" placeholder="<?= e(t('nav.search')) ?>"
                   value="<?= isset($_GET['search']) ? e($_GET['search']) : '' ?>" aria-label="Search">
            <button type="submit" aria-label="Search">🔍</button>
        </form>

        <div class="header-actions">
            <div class="lang-dropdown" id="langDropdown">
                <button type="button" class="lang-trigger" id="langTrigger" aria-haspopup="true" aria-expanded="false">
                    <span class="lang-flag"><?= $__lang === 'th' ? '🇹🇭' : '🇬🇧' ?></span>
                    <span class="lang-code"><?= $__lang === 'th' ? 'TH' : 'EN' ?></span>
                    <span class="lang-caret">▾</span>
                </button>
                <div class="lang-menu" role="menu">
                    <a href="<?= e(gz_langSwitchUrl('th')) ?>" class="lang-opt <?= $__lang==='th'?'active':'' ?>" role="menuitem">
                        <span class="lang-flag">🇹🇭</span>
                        <span class="lang-label">ไทย</span>
                        <?php if ($__lang === 'th'): ?><span class="lang-check">✓</span><?php endif; ?>
                    </a>
                    <a href="<?= e(gz_langSwitchUrl('en')) ?>" class="lang-opt <?= $__lang==='en'?'active':'' ?>" role="menuitem">
                        <span class="lang-flag">🇬🇧</span>
                        <span class="lang-label">English</span>
                        <?php if ($__lang === 'en'): ?><span class="lang-check">✓</span><?php endif; ?>
                    </a>
                </div>
            </div>

            <?php if ($__user): ?>
                <a class="icon-btn" href="<?= url('pages/myaccount.php') ?>" title="<?= e(t('nav.account')) ?>">
                    <span class="avatar-mini"><?= e(initials($__user['first_name'], $__user['last_name'])) ?></span>
                </a>
                <?php if (in_array($__user['role'], ['admin','super_admin'], true)): ?>
                    <a class="icon-btn admin-link" href="<?= url('admin/index.php') ?>" title="<?= e(t('nav.admin')) ?>">🛠️</a>
                <?php endif; ?>
            <?php else: ?>
                <a class="icon-btn" href="<?= url('pages/login.php') ?>" title="<?= e(t('nav.login')) ?>">👤</a>
            <?php endif; ?>

            <a class="icon-btn cart-link" href="<?= url('pages/cart.php') ?>" title="<?= e(t('nav.cart')) ?>">
                🛒
                <span class="cart-badge" style="<?= $__cartCount > 0 ? '' : 'display:none;' ?>"><?= (int)$__cartCount ?></span>
            </a>
        </div>
    </div>
</header>
<main class="site-main">
