<?php
/**
 * admin/users.php
 * User & role management. POST handled before layout.
 * Guards:
 *   - super_admins are protected from demotion and deletion
 *   - you cannot act on your own account
 *   - role changes and deletions require super_admin (PRD rule)
 */
require_once __DIR__ . '/../includes/db.php';
requireAdmin();

$me = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_user') {
        // Any admin can create new users (default role: member).
        $first = sanitize($_POST['first_name'] ?? '');
        $last  = sanitize($_POST['last_name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        $pass  = $_POST['password'] ?? '';
        $role  = $_POST['role'] ?? 'member';

        $allowedRoles = isSuperAdmin() ? ['member','admin'] : ['member'];
        if (!in_array($role, $allowedRoles, true)) { $role = 'member'; }

        if ($first === '' || $last === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) {
            $msg = 'Please fill all fields (password ≥ 6 chars).';
        } else {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
            $stmt->bind_param('s', $email); $stmt->execute();
            if ($stmt->get_result()->fetch_assoc()) {
                $msg = 'That email is already registered.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $stmt2 = $conn->prepare("INSERT INTO users (first_name,last_name,email,password,role) VALUES (?,?,?,?,?)");
                $stmt2->bind_param('sssss', $first, $last, $email, $hash, $role);
                $stmt2->execute(); $stmt2->close();
                $msg = 'User created.';
            }
            $stmt->close();
        }
    } else {
        $id = (int)($_POST['id'] ?? 0);

        // Load target.
        $target = null;
        if ($id > 0) {
            $stmt = $conn->prepare("SELECT id, role FROM users WHERE id=? LIMIT 1");
            $stmt->bind_param('i', $id); $stmt->execute();
            $target = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        }

        if ($target) {
            $isSelf  = ((int)$target['id'] === (int)$me['id']);
            $isSuper = ($target['role'] === 'super_admin');

            if ($action === 'update_role') {
                if (!isSuperAdmin()) {
                    $msg = 'Only a super admin can change roles.';
                } else {
                    $role  = $_POST['role'] ?? '';
                    $valid = ['member', 'admin', 'super_admin'];
                    if (in_array($role, $valid, true) && !$isSelf && !$isSuper) {
                        $stmt = $conn->prepare("UPDATE users SET role=? WHERE id=?");
                        $stmt->bind_param('si', $role, $id);
                        $stmt->execute(); $stmt->close();
                        $msg = 'User role updated.';
                    } else {
                        $msg = 'That account is protected.';
                    }
                }
            } elseif ($action === 'delete') {
                if (!isSuperAdmin()) {
                    $msg = 'Only a super admin can delete users.';
                } else {
                    // Safety: block delete if user has orders.
                    $stmt = $conn->prepare("SELECT COUNT(*) c FROM orders WHERE user_id=?");
                    $stmt->bind_param('i', $id); $stmt->execute();
                    $orderCount = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
                    $stmt->close();
                    if ($isSelf || $isSuper) {
                        $msg = 'That account cannot be deleted.';
                    } elseif ($orderCount > 0) {
                        $msg = "Cannot delete: this user has {$orderCount} order(s). Demote them instead.";
                    } else {
                        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
                        $stmt->bind_param('i', $id);
                        $stmt->execute(); $stmt->close();
                        $msg = 'User deleted.';
                    }
                }
            }
        }
    }
    header('Location: ' . url('admin/users.php?flash=' . urlencode($msg ?? 'Done.')));
    exit;
}

$admin_title = 'Users';
require_once __DIR__ . '/layout.php';

$flash      = isset($_GET['flash']) ? sanitize($_GET['flash']) : '';
$search     = isset($_GET['q']) ? sanitize($_GET['q']) : '';
$roleFilter = isset($_GET['role']) ? sanitize($_GET['role']) : '';
$validRoles = ['member', 'admin', 'super_admin'];

// Role counts for stat strip
$roleCounts = ['member' => 0, 'admin' => 0, 'super_admin' => 0];
if ($res = $conn->query("SELECT role, COUNT(*) c FROM users GROUP BY role")) {
    while ($r = $res->fetch_assoc()) { $roleCounts[$r['role']] = (int)$r['c']; }
    $res->free();
}

// Build list query
$where = []; $params = []; $types = '';
if ($search !== '') {
    $like = "%$search%";
    $where[] = '(u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ?)';
    array_push($params, $like, $like, $like); $types .= 'sss';
}
if (in_array($roleFilter, $validRoles, true)) {
    $where[] = 'u.role = ?'; $params[] = $roleFilter; $types .= 's';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "SELECT u.*,
        (SELECT COUNT(*) FROM orders o WHERE o.user_id=u.id) AS order_count
        FROM users u $whereSql ORDER BY u.created_at DESC";
if ($params) {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $users = $conn->query($sql)->fetch_all(MYSQLI_ASSOC);
}

$roleColor = ['member' => 'blue', 'admin' => 'purple', 'super_admin' => 'amber'];
$canManageRoles = isSuperAdmin();
?>
<?php if ($flash): ?><div class="alert alert-success"><?= e($flash) ?></div><?php endif; ?>

<div class="role-stats">
    <div class="role-stat"><span class="status-badge sb-blue">Members</span><strong><?= $roleCounts['member'] ?></strong></div>
    <div class="role-stat"><span class="status-badge sb-purple">Admins</span><strong><?= $roleCounts['admin'] ?></strong></div>
    <div class="role-stat"><span class="status-badge sb-amber">Super Admins</span><strong><?= $roleCounts['super_admin'] ?></strong></div>
</div>

<div class="admin-card-head bare">
    <form method="GET" class="admin-search">
        <input type="search" name="q" placeholder="Search users…" value="<?= e($search) ?>">
        <select name="role" class="mini-select" onchange="this.form.submit()">
            <option value="">All roles</option>
            <option value="member"      <?= $roleFilter==='member'?'selected':'' ?>>Members</option>
            <option value="admin"       <?= $roleFilter==='admin'?'selected':'' ?>>Admins</option>
            <option value="super_admin" <?= $roleFilter==='super_admin'?'selected':'' ?>>Super Admins</option>
        </select>
        <button class="btn btn-ghost sm" type="submit">Search</button>
    </form>
    <button class="btn btn-primary" onclick="UserAdmin.openAdd()">+ Add User</button>
</div>

<div class="admin-card">
    <div class="table-wrap">
    <table class="data-table">
        <thead><tr><th></th><th>Name</th><th>Email</th><th>Phone</th><th>Orders</th><th>Joined</th><th>Role</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $isSelf  = ((int)$u['id'] === (int)$me['id']);
            $isSuper = ($u['role'] === 'super_admin');
            $locked  = $isSelf || $isSuper;
        ?>
            <tr>
                <td><span class="avatar-mini"><?= e(initials($u['first_name'], $u['last_name'])) ?></span></td>
                <td><?= e($u['first_name'] . ' ' . $u['last_name']) ?><?= $isSelf ? ' <small class="muted">(you)</small>' : '' ?></td>
                <td><?= e($u['email']) ?></td>
                <td><?= e($u['phone'] ?: '—') ?></td>
                <td><?= (int)$u['order_count'] ?></td>
                <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                <td><span class="status-badge sb-<?= $roleColor[$u['role']] ?? 'blue' ?>"><?= e(str_replace('_', ' ', $u['role'])) ?></span></td>
                <td class="actions-cell">
                    <?php if ($locked || !$canManageRoles): ?>
                        <span class="muted" title="<?= $locked ? 'Protected account' : 'Super admin only' ?>">🔒</span>
                    <?php else: ?>
                        <form method="POST" class="inline-form">
                            <input type="hidden" name="action" value="update_role">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <select name="role" onchange="this.form.submit()" class="mini-select">
                                <option value="member" <?= $u['role']==='member'?'selected':'' ?>>Member</option>
                                <option value="admin"  <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                            </select>
                        </form>
                        <form method="POST" class="inline-form" onsubmit="return confirm('Delete this user?')">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                            <button class="icon-act danger" title="Delete">🗑️</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($users)): ?><tr><td colspan="8" class="muted center">No users found.</td></tr><?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<script>
  window.GZ_USERS_URL = '<?= url('admin/users.php') ?>';
  window.GZ_CAN_PROMOTE = <?= $canManageRoles ? 'true' : 'false' ?>;
</script>
<?php require_once __DIR__ . '/footer.php'; ?>
