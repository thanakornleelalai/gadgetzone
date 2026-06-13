<?php
/**
 * admin/settings.php
 * Store settings — active currency + Stripe API keys.
 * POST handled before layout; clears the currency cache on save.
 */
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_currency') {
        $code  = strtoupper(trim($_POST['active_currency'] ?? 'BDT'));
        if (isset(currencyTable()[$code])) {
            setSetting('active_currency', $code);
            clearCurrencyCache();
            $msg = 'Currency updated to ' . $code . '.';
        } else {
            $msg = 'Invalid currency.';
        }
    } elseif ($action === 'save_stripe') {
        $pk = trim($_POST['stripe_publishable_key'] ?? '');
        $sk = trim($_POST['stripe_secret_key'] ?? '');
        setSetting('stripe_publishable_key', $pk);
        setSetting('stripe_secret_key', $sk);
        $msg = 'Stripe keys saved.';
    }
    header('Location: ' . url('admin/settings.php?flash=' . urlencode($msg ?? 'Saved.')));
    exit;
}

$admin_title = 'Settings';
require_once __DIR__ . '/layout.php';

$flash    = isset($_GET['flash']) ? sanitize($_GET['flash']) : '';
$active   = getActiveCurrency()['code'];
$pk       = getSetting('stripe_publishable_key', '');
$sk       = getSetting('stripe_secret_key', '');
$table    = currencyTable();

// Stripe status: not set / sandbox / live
$stripeStatus = 'not_set';
if (strpos($pk, 'pk_test_') === 0 && strpos($sk, 'sk_test_') === 0) {
    $stripeStatus = 'sandbox';
} elseif (strpos($pk, 'pk_live_') === 0 && strpos($sk, 'sk_live_') === 0) {
    $stripeStatus = 'live';
}
$stripeLabel = ['not_set' => 'Not Set', 'sandbox' => 'Sandbox ✓', 'live' => 'LIVE'][$stripeStatus];
$stripeColor = ['not_set' => 'red', 'sandbox' => 'green', 'live' => 'amber'][$stripeStatus];

// Sample BDT prices for live currency preview
$sampleBdt = [1499, 5000, 89999];
?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="admin-card">
    <div class="admin-card-head"><h3>💱 Active Currency</h3></div>
    <p class="muted" style="margin-top:-6px">All product prices are stored in BDT and converted on the fly. Choose the currency shown to shoppers.</p>
    <form method="POST">
        <input type="hidden" name="action" value="save_currency">
        <div class="currency-grid">
            <?php foreach ($table as $code => $info):
                // Compute sample preview values for this currency
                [$symbol, $name, $rate, $decimals, $after] = $info;
                $sampleVal = number_format($sampleBdt[0] * $rate, $decimals);
                $sample = $after ? $sampleVal . ' ' . $symbol : $symbol . $sampleVal;
            ?>
                <label class="currency-opt <?= $active===$code?'selected':'' ?>" data-preview="<?= e($sample) ?>">
                    <input type="radio" name="active_currency" value="<?= $code ?>" <?= $active===$code?'checked':'' ?>>
                    <span class="cur-symbol"><?= e($symbol) ?></span>
                    <span class="cur-code"><?= e($code) ?></span>
                    <span class="cur-name"><?= e($name) ?></span>
                    <span class="cur-preview"><?= e($sample) ?></span>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="currency-preview-box">
            Live preview &mdash; sample prices in <strong id="curPreviewCode"><?= e($active) ?></strong>:
            <div class="currency-preview-row" id="curPreviewRow">
                <?php foreach ($sampleBdt as $bdt): ?>
                    <span><?= e(formatPrice($bdt)) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div style="margin-top:18px"><button class="btn btn-primary">Save Currency</button></div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-head">
        <h3>💳 Stripe Payment</h3>
        <span class="status-badge sb-<?= $stripeColor ?>"><?= e($stripeLabel) ?></span>
    </div>
    <p class="muted" style="margin-top:-6px">Add your Stripe API keys to accept card payments. Find them in your Stripe Dashboard → Developers → API keys. Leave blank to disable card payments (COD / bKash / Nagad still work).</p>
    <form method="POST" class="settings-form">
        <input type="hidden" name="action" value="save_stripe">
        <div class="field full">
            <label>Publishable key (pk_…)</label>
            <input type="text" name="stripe_publishable_key" value="<?= e($pk) ?>" placeholder="pk_test_xxxxxxxxxxxx" autocomplete="off">
        </div>
        <div class="field full">
            <label>Secret key (sk_…)</label>
            <input type="password" name="stripe_secret_key" value="<?= e($sk) ?>" placeholder="sk_test_xxxxxxxxxxxx" autocomplete="off">
        </div>
        <div style="margin-top:6px"><button class="btn btn-primary">Save Stripe Keys</button></div>
    </form>
</div>

<div class="admin-card">
    <div class="admin-card-head"><h3>ℹ️ Store Info</h3></div>
    <table class="data-table">
        <tbody>
            <tr><td>Free shipping threshold</td><td><strong><?= formatPrice(FREE_SHIPPING_THRESHOLD) ?></strong></td></tr>
            <tr><td>Flat shipping fee</td><td><strong><?= formatPrice(SHIPPING_FEE) ?></strong></td></tr>
            <tr><td>Base path</td><td><code><?= e(BASE_URL ?: '/') ?></code></td></tr>
        </tbody>
    </table>
</div>

<script>
  // Highlight selected currency tile + update live preview.
  var bdtSamples = <?= json_encode($sampleBdt) ?>;
  var rates = <?= json_encode(array_map(fn($i) => ['symbol'=>$i[0],'rate'=>$i[2],'dec'=>$i[3],'after'=>$i[4]], $table)) ?>;
  function fmt(val, info) {
    var n = val.toLocaleString(undefined, { minimumFractionDigits: info.dec, maximumFractionDigits: info.dec });
    return info.after ? (n + ' ' + info.symbol) : (info.symbol + n);
  }
  document.querySelectorAll('.currency-opt input').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('.currency-opt').forEach(function (o) { o.classList.remove('selected'); });
      r.closest('.currency-opt').classList.add('selected');
      var code = r.value;
      var info = rates[code];
      if (!info) return;
      var row = document.getElementById('curPreviewRow');
      var lbl = document.getElementById('curPreviewCode');
      lbl.textContent = code;
      row.innerHTML = bdtSamples.map(function (b) { return '<span>' + fmt(b * info.rate, info) + '</span>'; }).join('');
    });
  });
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
