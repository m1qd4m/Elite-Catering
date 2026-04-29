<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php');
}

$pageTitle   = 'Manage Orders';
$currentPage = 'orders';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oid = (int)($_POST['order_id']       ?? 0);
    $pay = trim($_POST['payment_status']  ?? '');
    $ord = trim($_POST['order_status']    ?? '');

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

// Handle delete
if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM orders WHERE id=?");
    $stmt->bind_param('i', $did);
    $stmt->execute();
    $stmt->close();
    setFlash('success', "Order #$did deleted.");
    redirect('orders.php');
}

// Filters
$filter_pay = trim($_GET['payment'] ?? '');
$filter_ord = trim($_GET['status']  ?? '');
$search     = trim($_GET['q']       ?? '');

$allowed_pay_filter = ['Pending','Paid','Cancelled',''];
$allowed_ord_filter = ['New','Confirmed','In Progress','Completed','Cancelled',''];
if (!in_array($filter_pay, $allowed_pay_filter)) $filter_pay = '';
if (!in_array($filter_ord, $allowed_ord_filter)) $filter_ord = '';

// Build query with prepared statement for search
$where_parts = ['1=1'];
$bind_types  = '';
$bind_vals   = [];

if ($filter_pay !== '') {
    $where_parts[] = "o.payment_status = ?";
    $bind_types   .= 's';
    $bind_vals[]   = $filter_pay;
}
if ($filter_ord !== '') {
    $where_parts[] = "o.order_status = ?";
    $bind_types   .= 's';
    $bind_vals[]   = $filter_ord;
}
if ($search !== '') {
    $where_parts[] = "(u.name LIKE ? OR o.event_type LIKE ? OR o.id LIKE ?)";
    $bind_types   .= 'sss';
    $s = "%$search%";
    $bind_vals[]   = $s;
    $bind_vals[]   = $s;
    $bind_vals[]   = $s;
}

$where = implode(' AND ', $where_parts);
$sql   = "
    SELECT o.*, u.name AS customer_name, u.email AS customer_email,
           u.phone AS customer_phone, p.name AS package_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    LEFT JOIN packages p ON o.package_id = p.id
    WHERE $where
    ORDER BY o.created_at DESC
";

if (empty($bind_vals)) {
    $orders = $conn->query($sql);
} else {
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($bind_types, ...$bind_vals);
    $stmt->execute();
    $orders = $stmt->get_result();
    $stmt->close();
}

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">📋 All Bookings</span>
    </div>
    <div class="header-right">
      <span class="header-date"><?= (int)$orders->num_rows ?> orders found</span>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <form method="GET" action="" class="filter-bar">
      <div class="search-bar">
        <i class="fas fa-search"></i>
        <input type="text" name="q" id="table-search"
               placeholder="Search customer, event, #ID…"
               value="<?= e($search) ?>">
      </div>
      <select name="payment" class="filter-select">
        <option value="">All Payments</option>
        <?php foreach (['Pending','Paid','Cancelled'] as $p): ?>
          <option value="<?= $p ?>" <?= $filter_pay === $p ? 'selected' : '' ?>><?= $p ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="filter-select">
        <option value="">All Statuses</option>
        <?php foreach (['New','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
          <option value="<?= $s ?>" <?= $filter_ord === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn-gold btn-sm">
        <i class="fas fa-filter"></i> Filter
      </button>
      <a href="orders.php" class="btn-ghost btn-sm">
        <i class="fas fa-times"></i> Reset
      </a>
    </form>

    <div class="panel">
      <div class="table-wrap">
        <table class="table-catering">
          <thead>
            <tr>
              <th>#</th>
              <th>Customer</th>
              <th>Event</th>
              <th>Date</th>
              <th>Guests</th>
              <th>Package</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($orders->num_rows === 0): ?>
              <tr class="table-empty">
                <td colspan="10">No orders match your filters.</td>
              </tr>
            <?php else:
              while ($o = $orders->fetch_assoc()): ?>
              <tr class="searchable-row">
                <td><strong>#<?= (int)$o['id'] ?></strong></td>
                <td>
                  <strong><?= e($o['customer_name']) ?></strong><br>
                  <span class="text-xs text-muted"><?= e($o['customer_email']) ?></span>
                </td>
                <td><?= e($o['event_type']) ?></td>
                <td><?= date('d M Y', strtotime($o['event_date'])) ?></td>
                <td><?= (int)$o['num_guests'] ?></td>
                <td><?= $o['package_name'] ? e($o['package_name']) : '<span class="text-muted">Custom</span>' ?></td>
                <td><strong class="text-gold">Rs.&nbsp;<?= number_format($o['total_amount'], 0) ?></strong></td>
                <td>
                  <span class="badge-status <?= strtolower($o['payment_status']) ?>">
                    <?= e($o['payment_status']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge-status <?= strtolower(str_replace(' ', '-', $o['order_status'])) ?>">
                    <?= e($o['order_status']) ?>
                  </span>
                </td>
                <td class="td-actions">
                  <button class="btn-gold btn-sm"
                          data-modal-open="modal-order-<?= (int)$o['id'] ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <a href="?delete=<?= (int)$o['id'] ?>"
                     class="btn-danger"
                     data-confirm="Delete order #<?= (int)$o['id'] ?>? This cannot be undone.">
                    <i class="fas fa-trash"></i>
                  </a>
                </td>
              </tr>

              <!-- Edit Modal -->
              <div class="modal-overlay" id="modal-order-<?= (int)$o['id'] ?>">
                <div class="modal-box">
                  <div class="modal-header">
                    <h3>Update Booking #<?= (int)$o['id'] ?></h3>
                    <button class="modal-close" data-modal-close aria-label="Close">
                      <i class="fas fa-times"></i>
                    </button>
                  </div>
                  <form method="POST" action="">
                    <div class="modal-body">
                      <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                      <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-select">
                          <?php foreach (['Pending','Paid','Cancelled'] as $p): ?>
                            <option value="<?= $p ?>" <?= $o['payment_status'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-group">
                        <label class="form-label">Order Status</label>
                        <select name="order_status" class="form-select">
                          <?php foreach (['New','Confirmed','In Progress','Completed','Cancelled'] as $s): ?>
                            <option value="<?= $s ?>" <?= $o['order_status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                          <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="order-info-box">
                        <strong>Customer:</strong> <?= e($o['customer_name']) ?>
                        <?php if ($o['customer_phone']): ?> · <?= e($o['customer_phone']) ?><?php endif; ?><br>
                        <strong>Event:</strong> <?= e($o['event_type']) ?> — <?= date('d M Y', strtotime($o['event_date'])) ?><br>
                        <strong>Guests:</strong> <?= (int)$o['num_guests'] ?> &nbsp;|&nbsp;
                        <strong>Total:</strong> Rs. <?= number_format($o['total_amount'], 0) ?>
                        <?php if ($o['special_notes']): ?>
                          <br><strong>Notes:</strong> <?= e($o['special_notes']) ?>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="modal-footer">
                      <button type="button" class="btn-ghost" data-modal-close>Cancel</button>
                      <button type="submit" class="btn-gold">
                        <i class="fas fa-save"></i> Save Changes
                      </button>
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
