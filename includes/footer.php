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
            <p><?= e(t('footer.tagline')) ?></p>
            <div class="social-row">
                <a href="#" aria-label="Facebook">f</a>
                <a href="#" aria-label="Twitter">𝕏</a>
                <a href="#" aria-label="Instagram">◎</a>
                <a href="#" aria-label="YouTube">▶</a>
            </div>
        </div>
        <div class="footer-col">
            <h4><?= e(t('footer.shop.title')) ?></h4>
            <a href="<?= url('pages/shop.php') ?>"><?= e(t('footer.shop.all')) ?></a>
            <a href="<?= url('pages/shop.php?badge=NEW') ?>"><?= e(t('footer.shop.new')) ?></a>
            <a href="<?= url('pages/shop.php?badge=SALE') ?>"><?= e(t('footer.shop.sale')) ?></a>
            <a href="<?= url('pages/shop.php?badge=HOT') ?>"><?= e(t('footer.shop.hot')) ?></a>
        </div>
        <div class="footer-col">
            <h4><?= e(t('footer.acc.title')) ?></h4>
            <a href="<?= url('pages/myaccount.php') ?>"><?= e(t('footer.acc.my')) ?></a>
            <a href="<?= url('pages/cart.php') ?>"><?= e(t('footer.acc.cart')) ?></a>
            <a href="<?= url('pages/login.php') ?>"><?= e(t('footer.acc.login')) ?></a>
            <a href="<?= url('pages/register.php') ?>"><?= e(t('footer.acc.register')) ?></a>
        </div>
        <div class="footer-col">
            <h4><?= e(t('footer.support.title')) ?></h4>
            <a href="mailto:support@gadgetzone.com">support@gadgetzone.com</a>
            <a href="#"><?= e(t('footer.support.shipping')) ?></a>
            <a href="#"><?= e(t('footer.support.warranty')) ?></a>
            <a href="#"><?= e(t('footer.support.privacy')) ?></a>
        </div>
    </div>
    <div class="container footer-bottom">
        <span>© <?= date('Y') ?> GadgetZone. <?= e(t('footer.rights')) ?></span>
        <span class="pay-icons">VISA · Mastercard · bKash · Nagad · Stripe</span>
    </div>
</footer>

<div class="toast" id="toast"></div>

<script src="<?= url('assets/js/main.js') ?>?v=<?= $__jsVer ?>"></script>
</body>
</html>
