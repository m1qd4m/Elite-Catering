<?php
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    redirect('../auth/login.php');
}
$pageTitle   = 'Staff Dashboard';
$currentPage = 'dashboard';

$new_orders   = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status='New'")->fetch_assoc()['c'];
$confirmed    = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE order_status='Confirmed'")->fetch_assoc()['c'];
$today_events = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE DATE(event_date)=CURDATE()")->fetch_assoc()['c'];

$upcoming = $conn->query("
    SELECT o.id, u.name AS customer, u.phone, o.event_type,
           o.event_date, o.num_guests, o.order_status, o.payment_status, o.event_location
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.event_date >= CURDATE()
      AND o.order_status NOT IN ('Cancelled','Completed')
    ORDER BY o.event_date ASC
    LIMIT 10
");

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_staff.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Staff Dashboard</span>
    </div>
    <div class="header-right">
      <span class="header-date"><?= date('l, F j, Y') ?></span>
    </div>
  </div>

  <div class="dash-content">
    <?php if ($flash): ?>
      <div class="alert-flash <?= e($flash['type']) ?>">
        <i class="fas fa-info-circle"></i><span><?= e($flash['message']) ?></span>
      </div>
    <?php endif; ?>

    <div class="welcome-banner mb-xl">
      <div class="wb-text">
        <h2>Good to see you, <?= e(getFirstName()) ?> 👋</h2>
        <p>Here's a summary of today's bookings and upcoming events.</p>
      </div>
      <div class="wb-icon">📋</div>
    </div>

    <div class="grid-3 mb-xl" style="max-width:820px">
      <div class="stat-card reveal">
        <div class="stat-icon red">🆕</div>
        <div class="stat-info">
          <div class="stat-value"><?= $new_orders ?></div>
          <div class="stat-label">New Orders</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon green">✅</div>
        <div class="stat-info">
          <div class="stat-value"><?= $confirmed ?></div>
          <div class="stat-label">Confirmed</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon gold">📅</div>
        <div class="stat-info">
          <div class="stat-value"><?= $today_events ?></div>
          <div class="stat-label">Events Today</div>
        </div>
      </div>
    </div>

    <div class="panel reveal">
      <div class="panel-header">
        <h3>Upcoming Events</h3>
        <a href="orders.php" class="btn-ghost btn-sm"><i class="fas fa-arrow-right"></i> All Orders</a>
      </div>
      <div class="table-wrap">
        <table class="table-catering">
          <thead>
            <tr><th>#</th><th>Customer</th><th>Event</th><th>Date</th><th>Location</th><th>Guests</th><th>Status</th><th>Action</th></tr>
          </thead>
          <tbody>
            <?php if ($upcoming->num_rows === 0): ?>
              <tr class="table-empty"><td colspan="8">No upcoming events.</td></tr>
            <?php else: while ($o = $upcoming->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?= (int)$o['id'] ?></strong></td>
                <td>
                  <?= e($o['customer']) ?><br>
                  <span class="text-xs text-muted"><?= e($o['phone'] ?: '—') ?></span>
                </td>
                <td><?= e($o['event_type']) ?></td>
                <td><?= date('d M Y', strtotime($o['event_date'])) ?></td>
                <td class="text-sm text-muted"><?= e($o['event_location'] ?: '—') ?></td>
                <td><?= (int)$o['num_guests'] ?></td>
                <td><span class="badge-status <?= strtolower(str_replace(' ','-',$o['order_status'])) ?>"><?= e($o['order_status']) ?></span></td>
                <td><a href="orders.php" class="btn-ghost btn-sm"><i class="fas fa-edit"></i></a></td>
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
