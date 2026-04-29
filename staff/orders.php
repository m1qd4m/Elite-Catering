<?php
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    redirect('../auth/login.php');
}
$pageTitle   = 'Manage Orders';
$currentPage = 'orders';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id'] ?? 0);
    $pay = trim($_POST['payment_status'] ?? '');
    $ord = trim($_POST['order_status']   ?? '');

    $allowed_pay = ['Pending','Paid','Cancelled'];
    $allowed_ord = ['New','Confirmed','In Progress','Completed','Cancelled'];

    if ($oid && in_array($pay, $allowed_pay) && in_array($ord, $allowed_ord)) {
        $stmt = $conn->prepare("UPDATE orders SET payment_status=?, order_status=? WHERE id=?");
        $stmt->bind_param('ssi', $pay, $ord, $oid);
        $stmt->execute();
        $stmt->close();
        setFlash('success', "Order #$oid updated successfully.");
    }
    redirect('orders.php');
}

$filter_ord = trim($_GET['status'] ?? '');
$search     = trim($_GET['q']      ?? '');
$allowed_ord_filter = ['New','Confirmed','In Progress','Completed','Cancelled',''];
if (!in_array($filter_ord, $allowed_ord_filter)) $filter_ord = '';

if ($search !== '' && $filter_ord !== '') {
    $s = "%$search%";
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone, p.name AS package_name
        FROM orders o JOIN users u ON o.customer_id=u.id LEFT JOIN packages p ON o.package_id=p.id
        WHERE o.order_status=? AND (u.name LIKE ? OR o.event_type LIKE ?)
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param('sss', $filter_ord, $s, $s);
} elseif ($filter_ord !== '') {
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone, p.name AS package_name
        FROM orders o JOIN users u ON o.customer_id=u.id LEFT JOIN packages p ON o.package_id=p.id
        WHERE o.order_status=? ORDER BY o.created_at DESC
    ");
    $stmt->bind_param('s', $filter_ord);
} elseif ($search !== '') {
    $s = "%$search%";
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone, p.name AS package_name
        FROM orders o JOIN users u ON o.customer_id=u.id LEFT JOIN packages p ON o.package_id=p.id
        WHERE u.name LIKE ? OR o.event_type LIKE ?
        ORDER BY o.created_at DESC
    ");
    $stmt->bind_param('ss', $s, $s);
} else {
    $stmt = $conn->prepare("
        SELECT o.*, u.name AS customer_name, u.phone AS customer_phone, p.name AS package_name
        FROM orders o JOIN users u ON o.customer_id=u.id LEFT JOIN packages p ON o.package_id=p.id
        ORDER BY o.created_at DESC
    ");
}
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_staff.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">📋 Orders</span>
    </div>
    <div class="header-right">
      <span class="header-date"><?= (int)$orders->num_rows ?> total</span>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i><span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <form method="GET" action="" class="filter-bar">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" id="table-search"
               placeholder="Search customer or event…"
               value="<?= e($search) ?>">
      </div>
      <select name="status" class="filter-select">
        <option value="">All Statuses</option>
        <?php foreach (['New','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $filter_ord === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-gold btn-sm"><i class="fas fa-filter"></i> Filter</button>
      <a href="orders.php" class="btn-ghost btn-sm"><i class="fas fa-times"></i> Reset</a>
    </form>

    <div class="panel">
      <div class="table-wrap">
        <table class="table-catering">
          <thead>
            <tr><th>#</th><th>Customer</th><th>Event</th><th>Date</th><th>Guests</th><th>Package</th><th>Amount</th><th>Payment</th><th>Status</th><th>Update</th></tr>
          </thead>
          <tbody>
            <?php if ($orders->num_rows === 0): ?>
              <tr class="table-empty"><td colspan="10">No orders found.</td></tr>
            <?php else: while ($o = $orders->fetch_assoc()): ?>
              <tr class="searchable-row">
                <td><strong>#<?= (int)$o['id'] ?></strong></td>
                <td>
                  <?= e($o['customer_name']) ?><br>
                  <span class="text-xs text-muted"><?= e($o['customer_phone'] ?: '—') ?></span>
                </td>
                <td><?= e($o['event_type']) ?></td>
                <td><?= date('d M Y', strtotime($o['event_date'])) ?></td>
                <td><?= (int)$o['num_guests'] ?></td>
                <td class="text-sm"><?= $o['package_name'] ? e($o['package_name']) : '<span class="text-muted">Custom</span>' ?></td>
                <td class="text-gold fw-600">Rs.&nbsp;<?= number_format($o['total_amount'],0) ?></td>
                <td><span class="badge-status <?= strtolower($o['payment_status']) ?>"><?= e($o['payment_status']) ?></span></td>
                <td><span class="badge-status <?= strtolower(str_replace(' ','-',$o['order_status'])) ?>"><?= e($o['order_status']) ?></span></td>
                <td>
                  <button class="btn-gold btn-sm" data-modal-open="modal-staff-<?= (int)$o['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                </td>
              </tr>

              <div class="modal-overlay" id="modal-staff-<?= (int)$o['id'] ?>">
                <div class="modal-box">
                  <div class="modal-header">
                    <h3>Update Order #<?= (int)$o['id'] ?></h3>
                    <button class="modal-close" data-modal-close><i class="fas fa-times"></i></button>
                  </div>
                  <form method="POST" action="">
                    <div class="modal-body">
                      <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                      <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                          <?php foreach (['Pending','Paid','Cancelled'] as $p): ?>
                            <option value="<?= $p ?>" <?= $o['payment_status']===$p ? 'selected' : '' ?>><?= $p ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Order Status</label>
                        <select name="order_status" class="form-select">
                          <?php foreach (['New','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['order_status']===$s ? 'selected' : '' ?>><?= $s ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="order-info-box">
                        <strong>Customer:</strong> <?= e($o['customer_name']) ?><br>
                        <strong>Event:</strong> <?= e($o['event_type']) ?> — <?= date('d M Y', strtotime($o['event_date'])) ?><br>
                        <strong>Guests:</strong> <?= (int)$o['num_guests'] ?> &nbsp;|&nbsp;
                        <strong>Total:</strong> Rs. <?= number_format($o['total_amount'],0) ?>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                      <button type="submit" class="btn-gold"><i class="fas fa-save"></i> Update</button>
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
<?php require_once '../includes/footer.php'; ?>
