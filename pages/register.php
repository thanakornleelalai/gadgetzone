<?php
require_once __DIR__ . '/../includes/db.php';
$page_title = 'Register';

if (isLoggedIn()) { header('Location: ' . url('pages/myaccount.php')); exit; }

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = sanitize($_POST['first_name'] ?? '');
    $last  = sanitize($_POST['last_name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    $conf  = $_POST['confirm'] ?? '';

    if ($first === '' || $last === '')               { $errors[] = 'First and last name are required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))  { $errors[] = 'A valid email address is required.'; }
    if (strlen($pass) < 6)                           { $errors[] = 'Password must be at least 6 characters.'; }
    if ($pass !== $conf)                             { $errors[] = 'Passwords do not match.'; }

    if (empty($errors)) {
        // Check email not taken
        $stmt = $conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $exists = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($exists) {
            $errors[] = 'An account with this email already exists.';
        } else {
            $hash = password_hash($pass, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, password, role) VALUES (?,?,?,?,'member')");
            $stmt->bind_param('ssss', $first, $last, $email, $hash);
            $stmt->execute();
            $newId = $stmt->insert_id;
            $stmt->close();

            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$newId;
            header('Location: ' . url('pages/myaccount.php'));
            exit;
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
/* Register-specific tweaks — slightly wider card, tighter vertical rhythm
   so the 5 stacked fields don't make the form look stretched. */
.auth-wrap.register-wrap { max-width: 480px; margin: 40px auto; }
.register-wrap .auth-card { padding: 34px 36px 30px; }
.register-wrap .auth-card h1 { margin-bottom: 4px; }
.register-wrap .auth-sub { margin-bottom: 22px; }
.register-wrap .auth-card .field { margin-bottom: 14px; }
.register-wrap .auth-card .btn-block { margin-top: 6px; }
.register-wrap .auth-foot { margin-top: 16px; }
</style>
<div class="container auth-wrap register-wrap">
    <div class="auth-card">
        <h1>Create Account 🚀</h1>
        <p class="auth-sub">Join GadgetZone and start shopping</p>

        <?php if ($errors): ?>
            <div class="alert alert-error"><ul><?php foreach ($errors as $err) { echo '<li>' . e($err) . '</li>'; } ?></ul></div>
        <?php endif; ?>

        <form method="POST" action="<?= url('pages/register.php') ?>">
            <div class="field"><label>First Name</label><input type="text" name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>"></div>
            <div class="field"><label>Last Name</label><input type="text" name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>"></div>
            <div class="field"><label>Email Address</label><input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></div>
            <div class="field"><label>Password</label><input type="password" name="password" required minlength="6"></div>
            <div class="field"><label>Confirm Password</label><input type="password" name="confirm" required minlength="6"></div>
            <button type="submit" class="btn btn-primary btn-lg btn-block">Create Account</button>
        </form>

        <p class="auth-foot">Already have an account? <a href="<?= url('pages/login.php') ?>">Log in →</a></p>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
