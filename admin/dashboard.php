<?php
// admin/dashboard.php
require_once '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php');
}

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';

// Stats
$r  = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(total_amount),0) AS rev FROM orders WHERE order_status != 'Cancelled'");
$row = $r->fetch_assoc();
$stats['orders']    = (int)($row['cnt'] ?? 0);
$stats['revenue']   = (float)($row['rev'] ?? 0);
$stats['customers'] = (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='customer'")->fetch_assoc()['c'];
$stats['pending']   = (int)$conn->query("SELECT COUNT(*) AS c FROM orders WHERE payment_status='Pending'")->fetch_assoc()['c'];

// Recent orders
$recent = $conn->query("
    SELECT o.id, u.name AS customer, o.event_type, o.event_date,
           o.num_guests, o.total_amount, o.payment_status, o.order_status
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    ORDER BY o.created_at DESC
    LIMIT 10
");

// Monthly bookings chart data
$monthly = $conn->query("
    SELECT DATE_FORMAT(event_date,'%b %Y') AS month,
           COUNT(*) AS bookings,
           COALESCE(SUM(total_amount),0) AS revenue
    FROM orders
    WHERE order_status != 'Cancelled'
      AND event_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(event_date,'%Y-%m')
    ORDER BY MIN(event_date) ASC
");
$months_data = [];
while ($m = $monthly->fetch_assoc()) { $months_data[] = $m; }

$flash = getFlash();
require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">Dashboard Overview</span>
    </div>
    <div class="header-right">
      <span class="header-date"><?= date('l, F j, Y') ?></span>
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

    <!-- Welcome Banner -->
    <div class="welcome-banner mb-xl">
      <div class="wb-text">
        <h2>Welcome back, <?= e(getFirstName()) ?> 👋</h2>
        <p>Here's what's happening with your catering business today.</p>
      </div>
      <div class="wb-icon">🍽️</div>
    </div>

    <!-- Stat Cards -->
    <div class="grid-4 mb-xl">
      <div class="stat-card reveal">
        <div class="stat-icon gold">📋</div>
        <div class="stat-info">
          <div class="stat-value" data-count="<?= $stats['orders'] ?>"><?= $stats['orders'] ?></div>
          <div class="stat-label">Total Bookings</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
          <div class="stat-value">Rs.&nbsp;<?= number_format($stats['revenue'], 0) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
          <div class="stat-value" data-count="<?= $stats['customers'] ?>"><?= $stats['customers'] ?></div>
          <div class="stat-label">Total Clients</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon red">⏳</div>
        <div class="stat-info">
          <div class="stat-value" data-count="<?= $stats['pending'] ?>"><?= $stats['pending'] ?></div>
          <div class="stat-label">Pending Payments</div>
        </div>
      </div>
    </div>

    <!-- Monthly Chart -->
    <?php if (!empty($months_data)):
      $maxB = max(1, max(array_column($months_data, 'bookings'))); ?>
    <div class="chart-box reveal mb-xl">
      <div class="chart-title"><span>📈 Monthly Bookings (Last 6 Months)</span></div>
      <div class="chart-bars">
        <?php foreach ($months_data as $md):
          $h = max(4, (int)round(($md['bookings'] / $maxB) * 150)); ?>
          <div class="chart-bar-col">
            <span class="chart-bar-val"><?= (int)$md['bookings'] ?></span>
            <div class="chart-bar" style="height:<?= $h ?>px"></div>
            <span class="chart-bar-label"><?= e($md['month']) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Recent Orders -->
    <div class="panel reveal">
      <div class="panel-header">
        <h3>Recent Bookings</h3>
        <a href="orders.php" class="btn-ghost btn-sm">
          View All <i class="fas fa-arrow-right"></i>
        </a>
      </div>
      <div class="table-wrap">
        <table class="table-catering">
          <thead>
            <tr>
              <th>#</th>
              <th>Customer</th>
              <th>Event</th>
              <th>Date</th>
              <th>Guests</th>
              <th>Amount</th>
              <th>Payment</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($recent->num_rows === 0): ?>
              <tr class="table-empty">
                <td colspan="9">No bookings yet.</td>
              </tr>
            <?php else:
              while ($row = $recent->fetch_assoc()): ?>
              <tr class="searchable-row">
                <td><strong>#<?= (int)$row['id'] ?></strong></td>
                <td><?= e($row['customer']) ?></td>
                <td><?= e($row['event_type']) ?></td>
                <td><?= date('d M Y', strtotime($row['event_date'])) ?></td>
                <td><?= (int)$row['num_guests'] ?></td>
                <td><strong class="text-gold">Rs.&nbsp;<?= number_format($row['total_amount'], 0) ?></strong></td>
                <td>
                  <span class="badge-status <?= strtolower($row['payment_status']) ?>">
                    <?= e($row['payment_status']) ?>
                  </span>
                </td>
                <td>
                  <span class="badge-status <?= strtolower(str_replace(' ', '-', $row['order_status'])) ?>">
                    <?= e($row['order_status']) ?>
                  </span>
                </td>
                <td>
                  <a href="orders.php" class="btn-ghost btn-sm">
                    <i class="fas fa-eye"></i>
                  </a>
                </td>
              </tr>
            <?php endwhile; endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div><!-- /.dash-content -->
</div><!-- /.dash-wrapper -->

<?php require_once '../includes/footer.php'; ?>
