<?php
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Login';

if (isLoggedIn()) { header('Location: ' . url('pages/myaccount.php')); exit; }

$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $redirect = $_POST['redirect'] ?? '';

    if ($email === '' || $pass === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, password, first_name FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($pass, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $dest = $redirect !== '' ? $redirect : url('pages/myaccount.php');
            // Only allow same-app relative redirects
            if (strpos($dest, '://') !== false) { $dest = url('pages/myaccount.php'); }
            header('Location: ' . $dest);
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container auth-wrap">
    <div class="auth-card">
        <h1>Welcome Back 👋</h1>
        <p class="auth-sub">Log in to your GadgetZone account</p>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" action="<?= url('pages/login.php') ?>">
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <div class="field"><label>Email Address</label><input type="email" id="loginEmail" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="field"><label>Password</label><input type="password" id="loginPassword" name="password" required></div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">🔒 Log In</button>
        </form>

        <p class="auth-foot">Don't have an account? <a href="<?= url('pages/register.php') ?>">Create one →</a></p>

        <div class="demo-accounts">
            <h4 class="demo-accounts-title">🎭 Demo Accounts — คลิกเพื่อกรอกอัตโนมัติ</h4>
            <div class="demo-grid">
                <button type="button" class="demo-card" data-email="demo@gadgetzone.com" data-pass="Demo@1234">
                    <span class="demo-icon">🛍️</span>
                    <div class="demo-info">
                        <strong>Member (ผู้ซื้อ)</strong>
                        <span class="demo-row"><em>Email:</em> <code>demo@gadgetzone.com</code></span>
                        <span class="demo-row"><em>Password:</em> <code>Demo@1234</code></span>
                    </div>
                </button>
                <button type="button" class="demo-card" data-email="admin@gadgetzone.com" data-pass="Admin@1234">
                    <span class="demo-icon">🛠️</span>
                    <div class="demo-info">
                        <strong>Super Admin (ผู้ดูแล)</strong>
                        <span class="demo-row"><em>Email:</em> <code>admin@gadgetzone.com</code></span>
                        <span class="demo-row"><em>Password:</em> <code>Admin@1234</code></span>
                    </div>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.demo-accounts { margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--border); }
.demo-accounts-title { font-size: .82rem; color: var(--text2); margin: 0 0 12px; font-family: var(--font-head); font-weight: 700; letter-spacing: .02em; }
.demo-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.demo-card { display: flex; align-items: flex-start; gap: 10px; padding: 12px; background: var(--bg); border: 1px solid var(--border); border-radius: 10px; text-align: left; cursor: pointer; transition: border-color .15s, background .15s, transform .1s; color: inherit; font-family: inherit; }
.demo-card:hover { border-color: var(--accent); background: rgba(245,158,11,.06); }
.demo-card:active { transform: translateY(1px); }
.demo-icon { font-size: 1.5rem; line-height: 1; padding-top: 2px; }
.demo-info { display: flex; flex-direction: column; gap: 3px; flex: 1; min-width: 0; }
.demo-info strong { font-family: var(--font-head); font-size: .92rem; color: var(--text); margin-bottom: 3px; }
.demo-row { font-size: .75rem; color: var(--text2); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.demo-row em { font-style: normal; color: var(--text2); margin-right: 3px; }
.demo-row code { background: rgba(255,255,255,.04); padding: 1px 6px; border-radius: 4px; font-size: .72rem; color: var(--accent-light); border: 1px solid var(--border); }
@media (max-width: 540px) { .demo-grid { grid-template-columns: 1fr; } }
</style>

<script>
document.querySelectorAll('.demo-card').forEach(function (card) {
    card.addEventListener('click', function () {
        document.getElementById('loginEmail').value    = card.dataset.email;
        document.getElementById('loginPassword').value = card.dataset.pass;
        document.getElementById('loginEmail').focus();
    });
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
