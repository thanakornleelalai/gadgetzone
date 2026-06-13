<?php
/**
 * includes/footer.php
 * Shared storefront footer + cache-busted main.js.
 */
$__jsVer = @filemtime(__DIR__ . '/../assets/js/main.js') ?: time();
?>
</main>
<footer class="site-footer">
    <div class="container footer-grid">
        <div class="footer-col footer-brand">
            <a class="brand" href="<?= url('index.php') ?>"><span class="brand-mark">⚡</span> Gadget<span class="brand-accent">Zone</span></a>
            <p>Your world. Next-level technology. Premium gadgets delivered to your door.</p>
            <div class="social-row">
                <a href="#" aria-label="Facebook">f</a>
                <a href="#" aria-label="Twitter">𝕏</a>
                <a href="#" aria-label="Instagram">◎</a>
                <a href="#" aria-label="YouTube">▶</a>
            </div>
        </div>
        <div class="footer-col">
            <h4>Shop</h4>
            <a href="<?= url('pages/shop.php') ?>">All Products</a>
            <a href="<?= url('pages/shop.php?badge=NEW') ?>">New Arrivals</a>
            <a href="<?= url('pages/shop.php?badge=SALE') ?>">On Sale</a>
            <a href="<?= url('pages/shop.php?badge=HOT') ?>">Hot Picks</a>
        </div>
        <div class="footer-col">
            <h4>Account</h4>
            <a href="<?= url('pages/myaccount.php') ?>">My Account</a>
            <a href="<?= url('pages/cart.php') ?>">Cart</a>
            <a href="<?= url('pages/login.php') ?>">Login</a>
            <a href="<?= url('pages/register.php') ?>">Register</a>
        </div>
        <div class="footer-col">
            <h4>Support</h4>
            <a href="mailto:support@gadgetzone.com">support@gadgetzone.com</a>
            <a href="#">Shipping &amp; Returns</a>
            <a href="#">Warranty</a>
            <a href="#">Privacy Policy</a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> GadgetZone. All rights reserved.</span>
        <span class="pay-icons">VISA · Mastercard · bKash · Nagad · Stripe</span>
    </div>
</footer>

<div class="toast" id="toast"></div>

<script src="<?= url('assets/js/main.js') ?>?v=<?= $__jsVer ?>"></script>
</body>
</html>
