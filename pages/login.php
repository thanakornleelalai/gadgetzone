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
        $error = t('auth.err.both');
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
        $error = t('auth.err.invalid');
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container auth-wrap">
    <div class="auth-card">
        <h1><?= e(t('auth.welcome')) ?></h1>
        <p class="auth-sub"><?= e(t('auth.welcome.sub')) ?></p>

        <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>

        <form method="POST" action="<?= url('pages/login.php') ?>">
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <div class="field"><label><?= e(t('auth.email')) ?></label><input type="email" id="loginEmail" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="field"><label><?= e(t('auth.password')) ?></label><input type="password" id="loginPassword" name="password" required></div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">🔒 <?= e(t('auth.login')) ?></button>
        </form>

        <p class="auth-foot"><?= e(t('auth.signup.text')) ?> <a href="<?= url('pages/register.php') ?>"><?= e(t('auth.signup.link')) ?></a></p>

        <div class="demo-picker">
            <label for="demoSelect"><?= e(t('auth.demo.label')) ?></label>
            <select id="demoSelect">
                <option value=""><?= e(t('auth.demo.placeholder')) ?></option>
                <option value="demo@gadgetzone.com|Demo@1234"><?= e(t('auth.demo.member')) ?></option>
                <option value="admin@gadgetzone.com|Admin@1234"><?= e(t('auth.demo.admin')) ?></option>
            </select>
        </div>
    </div>
</div>

<style>
.demo-picker { margin-top: 22px; padding-top: 18px; border-top: 1px dashed var(--border-strong); display: flex; flex-direction: column; gap: 7px; }
.demo-picker label { font-size: .82rem; color: var(--text2); font-weight: 600; }
.demo-picker select { padding: 10px 12px; background: var(--bg); border: 1px solid var(--border); border-radius: var(--radius-sm); color: var(--text); font-family: inherit; font-size: .88rem; cursor: pointer; transition: border-color .15s; }
.demo-picker select:hover, .demo-picker select:focus { border-color: var(--accent); outline: none; }
</style>

<script>
(function () {
    var sel = document.getElementById('demoSelect');
    if (!sel) return;
    sel.addEventListener('change', function () {
        if (!sel.value) return;
        var parts = sel.value.split('|');
        document.getElementById('loginEmail').value    = parts[0];
        document.getElementById('loginPassword').value = parts[1];
    });
})();
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
