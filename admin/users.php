<?php
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php');
}
$pageTitle   = 'Manage Users';
$currentPage = 'users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uid   = (int)($_POST['user_id'] ?? 0);
    $name  = trim($_POST['name']     ?? '');
    $email = trim($_POST['email']    ?? '');
    $phone = trim($_POST['phone']    ?? '');
    $role  = trim($_POST['role']     ?? 'customer');

    // Whitelist role
    if (!in_array($role, ['admin','staff','customer'])) $role = 'customer';

    if ($uid) {
        $stmt = $conn->prepare("UPDATE users SET name=?,email=?,phone=?,role=? WHERE id=?");
        $stmt->bind_param('ssssi', $name, $email, $phone, $role, $uid);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'User updated successfully.');
    } else {
        $pass   = trim($_POST['password'] ?? '');
        if (strlen($pass) < 6) $pass = 'password123';
        $hashed = password_hash($pass, PASSWORD_DEFAULT);
        $stmt   = $conn->prepare("INSERT INTO users (name,email,phone,password,role) VALUES (?,?,?,?,?)");
        $stmt->bind_param('sssss', $name, $email, $phone, $hashed, $role);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'User created successfully.');
    }
    redirect('users.php');
}

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    if ($did !== (int)$_SESSION['user_id']) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param('i', $did);
        $stmt->execute();
        $stmt->close();
        setFlash('success', 'User deleted.');
    } else {
        setFlash('error', 'You cannot delete your own account.');
    }
    redirect('users.php');
}

$search      = trim($_GET['q']    ?? '');
$filter_role = trim($_GET['role'] ?? '');
if (!in_array($filter_role, ['admin','staff','customer',''])) $filter_role = '';

// Build safe query
if ($search !== '' && $filter_role !== '') {
    $s = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE (name LIKE ? OR email LIKE ?) AND role=? ORDER BY created_at DESC");
    $stmt->bind_param('sss', $s, $s, $filter_role);
} elseif ($search !== '') {
    $s = "%$search%";
    $stmt = $conn->prepare("SELECT * FROM users WHERE name LIKE ? OR email LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param('ss', $s, $s);
} elseif ($filter_role !== '') {
    $stmt = $conn->prepare("SELECT * FROM users WHERE role=? ORDER BY created_at DESC");
    $stmt->bind_param('s', $filter_role);
} else {
    $stmt = $conn->prepare("SELECT * FROM users ORDER BY created_at DESC");
}
$stmt->execute();
$users = $stmt->get_result();
$stmt->close();

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">👥 Manage Users</span>
    </div>
    <div class="header-right">
      <button class="btn-gold btn-sm" data-modal-open="modal-add-user">
        <i class="fas fa-plus"></i> Add User
      </button>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-info-circle"></i><span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <form method="GET" action="" class="filter-bar">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" id="table-search"
               placeholder="Search name or email…"
               value="<?= e($search) ?>">
      </div>
      <select name="role" class="filter-select">
        <option value="">All Roles</option>
        <?php foreach (['admin','staff','customer'] as $r): ?>
          <option value="<?= $r ?>" <?= $filter_role === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-gold btn-sm"><i class="fas fa-filter"></i> Filter</button>
      <a href="users.php" class="btn-ghost btn-sm"><i class="fas fa-times"></i> Reset</a>
    </form>

    <div class="panel">
      <div class="table-wrap">
        <table class="table-catering">
          <thead>
            <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if ($users->num_rows === 0): ?>
              <tr class="table-empty"><td colspan="7">No users found.</td></tr>
            <?php else: while ($u = $users->fetch_assoc()): ?>
              <tr class="searchable-row">
                <td class="text-muted text-sm"><?= (int)$u['id'] ?></td>
                <td>
                  <div class="d-flex align-center gap-sm">
                    <div class="user-avatar-sm"><?= strtoupper(substr($u['name'],0,1)) ?></div>
                    <strong><?= e($u['name']) ?></strong>
                  </div>
                </td>
                <td class="text-sm"><?= e($u['email']) ?></td>
                <td class="text-sm text-muted"><?= e($u['phone'] ?: '—') ?></td>
                <td>
                  <span class="badge-status <?= $u['role']==='admin'?'confirmed':($u['role']==='staff'?'new':'pending') ?>">
                    <?= ucfirst($u['role']) ?>
                  </span>
                </td>
                <td class="text-sm text-muted"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td class="td-actions">
                  <button class="btn-ghost btn-sm" data-modal-open="modal-edit-user-<?= (int)$u['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <?php if ((int)$u['id'] !== (int)$_SESSION['user_id']): ?>
                    <a href="?delete=<?= (int)$u['id'] ?>" class="btn-danger"
                       data-confirm="Delete <?= e($u['name']) ?>? All their bookings will remain.">
                      <i class="fas fa-trash"></i>
                    </a>
                  <?php endif; ?>
                </td>
              </tr>

              <div class="modal-overlay" id="modal-edit-user-<?= (int)$u['id'] ?>">
                <div class="modal-box">
                  <div class="modal-header">
                    <h3>Edit User — <?= e($u['name']) ?></h3>
                    <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
                  </div>
                  <form method="POST" action="">
                    <div class="modal-body">
                      <input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>">
                      <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input" value="<?= e($u['name']) ?>" required>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-input" value="<?= e($u['email']) ?>" required>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="tel" name="phone" class="form-input" value="<?= e($u['phone'] ?? '') ?>">
                      </div>
                      <div class="form-group">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                          <?php foreach (['customer','staff','admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $u['role']===$r ? 'selected' : '' ?>><?= ucfirst($r) ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                      <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Save</button>
                    </div>
                  </form>
                </div>
              </div>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modal-add-user">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Add New User</h3>
      <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" action="">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Full Name *</label>
          <input type="text" name="name" class="form-input" placeholder="Full name" required>
        </div>
        <div class="form-group">
          <label class="form-label">Email Address *</label>
          <input type="email" name="email" class="form-input" placeholder="email@example.com" required>
        </div>
        <div class="form-group">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-input" placeholder="0300-0000000">
        </div>
        <div class="form-group">
          <label class="form-label">Password *</label>
          <input type="password" name="password" class="form-input" placeholder="Min. 6 characters" required>
        </div>
        <div class="form-group">
          <label class="form-label">Role</label>
          <select name="role" class="form-select">
            <option value="customer">Customer</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
        <button type="submit" class="btn-gold"><i class="fas fa-user-plus"></i> Create User</button>
      </div>
    </form>
  </div>
</div>

<?php require_once '../includes/footer.php'; ?>
