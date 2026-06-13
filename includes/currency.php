<?php
/**
 * includes/currency.php
 * Multi-currency support. Prices are stored in BDT and converted on the fly.
 */

// Supported currencies: code => [symbol, name, rate-from-BDT, decimals, symbol_after]
function currencyTable()
{
    return [
        'BDT' => ['৳',   'Bangladeshi Taka',  1.00,    0, false],
        'USD' => ['$',   'US Dollar',         0.0091,  2, false],
        'EUR' => ['€',   'Euro',              0.0084,  2, false],
        'GBP' => ['£',   'British Pound',     0.0072,  2, false],
        'CAD' => ['C$',  'Canadian Dollar',   0.0124,  2, false],
        'AUD' => ['A$',  'Australian Dollar', 0.0140,  2, false],
        'INR' => ['₹',   'Indian Rupee',      0.76,    2, false],
        'SGD' => ['S$',  'Singapore Dollar',  0.0122,  2, false],
        'SAR' => ['﷼',   'Saudi Riyal',       0.034,   2, false],
        'AED' => ['د.إ', 'UAE Dirham',        0.033,   2, true ],
        'JPY' => ['¥',   'Japanese Yen',      1.39,    0, false],
        'MYR' => ['RM',  'Malaysian Ringgit', 0.042,   2, false],
    ];
}

/**
 * Returns the active currency as:
 *   ['code'=>'BDT','symbol'=>'৳','name'=>..,'rate'=>1.0,'decimals'=>0,'after'=>false]
 * Cached in the session; cache is invalidated when admin saves settings.
 */
function getActiveCurrency()
{
    global $conn;

    if (isset($_SESSION['_cur_cache']) && is_array($_SESSION['_cur_cache'])) {
        return $_SESSION['_cur_cache'];
    }

    $code = 'BDT';
    if ($conn instanceof mysqli) {
        if ($res = $conn->query("SELECT setting_value FROM settings WHERE setting_key='active_currency' LIMIT 1")) {
            if ($row = $res->fetch_assoc()) {
                $code = strtoupper(trim($row['setting_value']));
            }
            $res->free();
        }
    }

    $table = currencyTable();
    if (!isset($table[$code])) {
        $code = 'BDT';
    }
    [$symbol, $name, $rate, $decimals, $after] = $table[$code];

    $cur = compact('code', 'symbol', 'name', 'rate', 'decimals', 'after');
    $_SESSION['_cur_cache'] = $cur;
    return $cur;
}

/** Invalidate the cached currency (call after changing the setting). */
function clearCurrencyCache()
{
    unset($_SESSION['_cur_cache']);
}

/** Convert a BDT amount to the active currency's numeric value. */
function convertAmount($bdt)
{
    $cur = getActiveCurrency();
    return (float)$bdt * $cur['rate'];
}

/** Format a BDT amount as a string in the active currency (e.g. "৳1,49,999" or "$1,365"). */
function formatPrice($bdt)
{
    $cur     = getActiveCurrency();
    $value   = convertAmount($bdt);
    $number  = number_format($value, $cur['decimals']);
    return $cur['after']
        ? $number . ' ' . $cur['symbol']
        : $cur['symbol'] . $number;
}

/** Stripe smallest unit (e.g. cents). Zero-decimal currencies stay whole. */
function getStripeAmount($bdt)
{
    $cur   = getActiveCurrency();
    $value = convertAmount($bdt);
    return $cur['decimals'] === 0 ? (int)round($value) : (int)round($value * 100);
}

/** Lowercase Stripe currency code (e.g. 'usd'). */
function getStripeCurrencyCode()
{
    return strtolower(getActiveCurrency()['code']);
}
