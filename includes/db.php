<?php
/**
 * includes/db.php
 * Database connection, session bootstrap, and global config.
 * Every page should require this file first.
 */

// ── Configuration ─────────────────────────────────────────
// BASE_URL is the single source of truth for absolute paths.
//   • XAMPP / localhost (project in htdocs/gadget/) -> '/gadget'
//   • VPS deployed to a domain root                 -> ''
// Change this one line when deploying to root.
if (!defined('BASE_URL')) {
    define('BASE_URL', '/gadgetzone');
}

// Database credentials — adjust for your environment.
define('DB_HOST', getenv('GZ_DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('GZ_DB_PORT') ?: 3307);
define('DB_USER', getenv('GZ_DB_USER') ?: 'root');
define('DB_PASS', getenv('GZ_DB_PASS') ?: '123456');
define('DB_NAME', getenv('GZ_DB_NAME') ?: 'gadgetzone');

// Free-shipping threshold (in base currency: BDT).
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
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if ($conn->connect_errno) {
    http_response_code(500);
    die('Database connection failed: ' . htmlspecialchars($conn->connect_error)
        . '<br>Please import <code>database_setup.sql</code> and check credentials in <code>includes/db.php</code>.');
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
