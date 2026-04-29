<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    redirect('../auth/login.php');
}

$pageTitle   = 'My Orders';
$currentPage = 'my_orders';
$uid         = (int)$_SESSION['user_id'];

// Cancel own order (only if still New)
if (isset($_GET['cancel'])) {
    $oid = (int)$_GET['cancel'];
    $stmt = $conn->prepare("UPDATE orders SET order_status='Cancelled', payment_status='Cancelled' WHERE id=? AND customer_id=? AND order_status='New'");
    $stmt->bind_param('ii', $oid, $uid);
    $stmt->execute();
    $stmt->close();
    setFlash('info', "Booking #$oid has been cancelled.");
    redirect('my_orders.php');
}

$orders = $conn->query("
    SELECT o.*, p.name AS package_name
    FROM orders o
    LEFT JOIN packages p ON o.package_id = p.id
    WHERE o.customer_id = $uid
    ORDER BY o.created_at DESC
");

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_customer.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">📋 My Orders</span>
    </div>
    <div class="header-right">
      <a href="booking.php" class="btn-gold btn-sm">
        <i class="fas fa-plus"></i> New Booking
      </a>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-info-circle"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <?php if ($orders->num_rows === 0): ?>
      <div class="empty-state">
        <div class="empty-icon">📭</div>
        <h3>No Bookings Yet</h3>
        <p>You haven't made any bookings. Let's change that!</p>
        <a href="booking.php" class="btn-gold">
          <i class="fas fa-calendar-plus"></i> Book an Event
        </a>
      </div>
    <?php else: ?>
      <div class="d-flex flex-col gap-md">
        <?php while ($o = $orders->fetch_assoc()): ?>
          <div class="order-card">
            <div class="order-card-header">
              <div class="order-card-id">Booking #<?= (int)$o['id'] ?></div>
              <div class="order-card-badges">
                <span class="badge-status <?= strtolower($o['payment_status']) ?>">
                  <?= e($o['payment_status']) ?>
                </span>
                <span class="badge-status <?= strtolower(str_replace(' ', '-', $o['order_status'])) ?>">
                  <?= e($o['order_status']) ?>
                </span>
              </div>
            </div>

            <div class="order-card-body">
              <div class="order-meta-grid">
                <div class="order-meta-item">
                  <span>🎉</span>
                  <span><?= e($o['event_type']) ?></span>
                </div>
                <div class="order-meta-item">
                  <span>📅</span>
                  <span><?= date('d M Y', strtotime($o['event_date'])) ?></span>
                </div>
                <div class="order-meta-item">
                  <span>👥</span>
                  <span><?= (int)$o['num_guests'] ?> guests</span>
                </div>
                <?php if (!empty($o['event_location'])): ?>
                  <div class="order-meta-item">
                    <span>📍</span>
                    <span><?= e($o['event_location']) ?></span>
                  </div>
                <?php endif; ?>
                <?php if (!empty($o['package_name'])): ?>
                  <div class="order-meta-item">
                    <span>📦</span>
                    <span><?= e($o['package_name']) ?></span>
                  </div>
                <?php endif; ?>
              </div>
              <?php if (!empty($o['special_notes'])): ?>
                <div class="order-note">
                  📝 <?= e($o['special_notes']) ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="order-card-footer">
              <div>
                <div class="order-total">Rs.&nbsp;<?= number_format($o['total_amount'], 0) ?></div>
                <div class="order-date">Booked on <?= date('d M Y', strtotime($o['created_at'])) ?></div>
              </div>
              <?php if ($o['order_status'] === 'New'): ?>
                <a href="?cancel=<?= (int)$o['id'] ?>"
                   class="btn-danger"
                   data-confirm="Cancel booking #<?= (int)$o['id'] ?>? This cannot be undone.">
                  <i class="fas fa-times"></i> Cancel
                </a>
              <?php endif; ?>
            </div>
          </div>
        <?php endwhile; ?>
      </div>
    <?php endif; ?>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
