<?php
/**
 * includes/db.php
 * Database connection, session bootstrap, and global config.
 * Every page should require this file first.
 *
 * Credentials are loaded from `.env` at the project root (gitignored).
 * Falls back to local XAMPP defaults so the site still works without .env.
 */

// ── Tiny .env loader ──────────────────────────────────────
(function () {
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile)) { return; }
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') { continue; }
        if (!str_contains($line, '=')) { continue; }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v, " \t\"'");
        if ($k !== '' && getenv($k) === false) {
            putenv("$k=$v");
            $_ENV[$k]    = $v;
            $_SERVER[$k] = $v;
        }
    }
})();

// ── Configuration ─────────────────────────────────────────
if (!defined('BASE_URL')) {
    define('BASE_URL', getenv('GZ_BASE_URL') !== false ? getenv('GZ_BASE_URL') : '/gadgetzone');
}

define('DB_HOST', getenv('GZ_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', (int)(getenv('GZ_DB_PORT') ?: 3307));
define('DB_USER', getenv('GZ_DB_USER') ?: 'root');
define('DB_PASS', getenv('GZ_DB_PASS') ?: '123456');
define('DB_NAME', getenv('GZ_DB_NAME') ?: 'gadgetzone');
define('DB_SSL',  (bool)getenv('GZ_DB_SSL'));

define('FREE_SHIPPING_THRESHOLD', 5000);
define('SHIPPING_FEE', 150);

// ── Error reporting (turn display off in production) ──────
error_reporting(E_ALL);
ini_set('display_errors', '1');

// ── Session ───────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Database connection (mysqli, OO style) ────────────────
mysqli_report(MYSQLI_REPORT_OFF); // we handle errors ourselves

$conn = mysqli_init();
if (DB_SSL) {
    // TLS for TiDB Cloud / PlanetScale / managed MySQL.
    // We don't ship a CA bundle, so skip cert verification (still encrypted on the wire).
    $conn->ssl_set(null, null, null, null, null);
    @$conn->real_connect(
        DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT, null,
        MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT
    );
} else {
    @$conn->real_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
}

if ($conn->connect_errno) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error)
        . '<br>Please import <code>database_setup.sql</code> and check credentials in <code>.env</code>.');
}
$conn->set_charset('utf8mb4');

// ── Helper: build an absolute URL from BASE_URL ───────────
function url($path = '')
{
    return BASE_URL . '/' . ltrim($path, '/');
}

// ── Bootstrap currency + functions ────────────────────────
require_once __DIR__ . '/currency.php';
require_once __DIR__ . '/functions.php';
