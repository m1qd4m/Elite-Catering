<?php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    redirect('../auth/login.php');
}

$pageTitle   = 'My Dashboard';
$currentPage = 'dashboard';
$uid         = (int)$_SESSION['user_id'];

$total   = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE customer_id=$uid")->fetch_assoc()['c'];
$pending = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE customer_id=$uid AND payment_status='Pending'")->fetch_assoc()['c'];
$spent   = (float)$conn->query("SELECT COALESCE(SUM(total_amount),0) AS s FROM orders WHERE customer_id=$uid AND payment_status='Paid'")->fetch_assoc()['s'];

$recent = $conn->query("
    SELECT o.*, p.name AS package_name
    FROM orders o
    LEFT JOIN packages p ON o.package_id = p.id
    WHERE o.customer_id = $uid
    ORDER BY o.created_at DESC
    LIMIT 5
");

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_customer.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">My Dashboard</span>
    </div>
    <div class="header-right">
      <a href="booking.php" class="btn-gold btn-sm">
        <i class="fas fa-plus"></i> Book Now
      </a>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-check-circle"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <div class="welcome-banner mb-xl">
      <div class="wb-text">
        <h2>Hello, <?= e(getFirstName()) ?> 👋</h2>
        <p>Manage your bookings and track your upcoming events.</p>
      </div>
      <div class="wb-icon">🎉</div>
    </div>

    <div class="grid-3 mb-xl" style="max-width:820px">
      <div class="stat-card reveal">
        <div class="stat-icon gold">📋</div>
        <div class="stat-info">
          <div class="stat-value"><?= $total ?></div>
          <div class="stat-label">Total Bookings</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon red">⏳</div>
        <div class="stat-info">
          <div class="stat-value"><?= $pending ?></div>
          <div class="stat-label">Pending Payment</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
          <div class="stat-value">Rs.&nbsp;<?= number_format($spent, 0) ?></div>
          <div class="stat-label">Total Spent</div>
        </div>
      </div>
    </div>

    <div class="panel reveal">
      <div class="panel-header">
        <h3>Recent Bookings</h3>
        <a href="my_orders.php" class="btn-ghost btn-sm">View All</a>
      </div>
      <div class="table-wrap">
        <table class="table-catering">
          <thead>
            <tr>
              <th>#</th><th>Event</th><th>Date</th><th>Guests</th>
              <th>Package</th><th>Amount</th><th>Payment</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recent->num_rows === 0): ?>
              <tr class="table-empty">
                <td colspan="8">
                  No bookings yet.
                  <a href="booking.php" class="text-gold"> Book your first event!</a>
                </td>
              </tr>
            <?php else:
              while ($o = $recent->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?= (int)$o['id'] ?></strong></td>
                <td><?= e($o['event_type']) ?></td>
                <td><?= date('d M Y', strtotime($o['event_date'])) ?></td>
                <td><?= (int)$o['num_guests'] ?></td>
                <td><?= $o['package_name'] ? e($o['package_name']) : '—' ?></td>
                <td class="text-gold fw-600">Rs.&nbsp;<?= number_format($o['total_amount'], 0) ?></td>
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
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
