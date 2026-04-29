<?php
require_once '../config/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    redirect('../auth/login.php');
}
$pageTitle   = 'Reports';
$currentPage = 'reports';

$totals = $conn->query("
    SELECT COUNT(*) AS total_orders,
           COALESCE(SUM(total_amount),0) AS total_revenue,
           COALESCE(SUM(num_guests),0)   AS total_guests,
           COALESCE(AVG(total_amount),0) AS avg_order
    FROM orders WHERE order_status != 'Cancelled'
")->fetch_assoc();

$monthly = $conn->query("
    SELECT DATE_FORMAT(event_date,'%b %Y') AS month,
           DATE_FORMAT(event_date,'%Y-%m') AS sort_key,
           COUNT(*) AS bookings,
           SUM(total_amount) AS revenue
    FROM orders
    WHERE order_status != 'Cancelled'
      AND event_date >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(event_date,'%Y-%m')
    ORDER BY sort_key ASC
");
$monthly_data = [];
while ($r = $monthly->fetch_assoc()) { $monthly_data[] = $r; }

$by_event = [];
$er = $conn->query("
    SELECT event_type, COUNT(*) AS cnt, SUM(total_amount) AS rev
    FROM orders WHERE order_status != 'Cancelled'
    GROUP BY event_type ORDER BY cnt DESC
");
while ($row = $er->fetch_assoc()) { $by_event[] = $row; }

$by_pkg = $conn->query("
    SELECT p.name, COUNT(o.id) AS cnt, SUM(o.total_amount) AS rev
    FROM orders o
    JOIN packages p ON o.package_id = p.id
    WHERE o.order_status != 'Cancelled'
    GROUP BY p.id ORDER BY cnt DESC
");

$top_cust = $conn->query("
    SELECT u.name, u.email, COUNT(o.id) AS bookings, SUM(o.total_amount) AS spent
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.order_status != 'Cancelled'
    GROUP BY o.customer_id ORDER BY spent DESC LIMIT 5
");

require_once '../includes/header.php';
require_once '../includes/sidebar_admin.php';
?>
<div class="dash-wrapper">
  <div class="dash-header">
    <div class="header-left">
      <button class="sidebar-toggle"><i class="fas fa-bars"></i></button>
      <span class="page-title">📊 Reports &amp; Analytics</span>
    </div>
    <div class="header-right">
      <span class="header-date">Generated: <?= date('d M Y, H:i') ?></span>
    </div>
  </div>

  <div class="dash-content">

    <div class="grid-4 mb-xl">
      <div class="stat-card reveal">
        <div class="stat-icon gold">📋</div>
        <div class="stat-info">
          <div class="stat-value"><?= number_format($totals['total_orders']) ?></div>
          <div class="stat-label">Total Bookings</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon green">💰</div>
        <div class="stat-info">
          <div class="stat-value">Rs.&nbsp;<?= number_format($totals['total_revenue'],0) ?></div>
          <div class="stat-label">Total Revenue</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon blue">👥</div>
        <div class="stat-info">
          <div class="stat-value"><?= number_format($totals['total_guests']) ?></div>
          <div class="stat-label">Guests Served</div>
        </div>
      </div>
      <div class="stat-card reveal">
        <div class="stat-icon red">📈</div>
        <div class="stat-info">
          <div class="stat-value">Rs.&nbsp;<?= number_format($totals['avg_order'],0) ?></div>
          <div class="stat-label">Avg Order Value</div>
        </div>
      </div>
    </div>

    <div class="grid-2 mb-xl">
      <div class="chart-box reveal">
        <div class="chart-title"><span>📅 Monthly Revenue (Last 12 Months)</span></div>
        <?php if (empty($monthly_data)): ?>
          <p style="color:var(--text-muted);text-align:center;padding:2rem">No data yet.</p>
        <?php else:
          $maxRev = max(1, max(array_column($monthly_data,'revenue'))); ?>
          <div class="chart-bars">
            <?php foreach ($monthly_data as $m):
              $h = max(4, (int)round(($m['revenue']/$maxRev)*150)); ?>
              <div class="chart-bar-col">
                <span class="chart-bar-val"><?= number_format($m['revenue']/1000,0) ?>K</span>
                <div class="chart-bar" style="height:<?= $h ?>px"></div>
                <span class="chart-bar-label"><?= e($m['month']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <div class="chart-box reveal">
        <div class="chart-title"><span>🎉 Bookings by Event Type</span></div>
        <?php if (empty($by_event)): ?>
          <p style="color:var(--text-muted);text-align:center;padding:2rem">No data yet.</p>
        <?php else:
          $maxCnt = max(1, max(array_column($by_event,'cnt')));
          foreach ($by_event as $ev):
            $pct = (int)round(($ev['cnt']/$maxCnt)*100); ?>
            <div class="progress-row">
              <div class="progress-meta">
                <span class="prog-label"><?= e($ev['event_type']) ?></span>
                <span class="prog-val"><?= (int)$ev['cnt'] ?> bookings</span>
              </div>
              <div class="progress-track">
                <div class="progress-fill" style="width:<?= $pct ?>%"></div>
              </div>
              <div class="progress-sub">Rs. <?= number_format($ev['rev'],0) ?> revenue</div>
            </div>
          <?php endforeach; endif; ?>
      </div>
    </div>

    <div class="grid-2">
      <div class="panel reveal">
        <div class="panel-header"><h3>📦 Package Performance</h3></div>
        <div class="table-wrap">
          <table class="table-catering">
            <thead><tr><th>Package</th><th>Bookings</th><th>Revenue</th></tr></thead>
            <tbody>
              <?php if ($by_pkg->num_rows === 0): ?>
                <tr class="table-empty"><td colspan="3">No data yet.</td></tr>
              <?php else: while ($pk = $by_pkg->fetch_assoc()): ?>
                <tr>
                  <td><strong><?= e($pk['name']) ?></strong></td>
                  <td><?= (int)$pk['cnt'] ?></td>
                  <td class="text-gold fw-600">Rs. <?= number_format($pk['rev'],0) ?></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel reveal">
        <div class="panel-header"><h3>🏆 Top Customers</h3></div>
        <div class="table-wrap">
          <table class="table-catering">
            <thead><tr><th>Customer</th><th>Bookings</th><th>Total Spent</th></tr></thead>
            <tbody>
              <?php if ($top_cust->num_rows === 0): ?>
                <tr class="table-empty"><td colspan="3">No data yet.</td></tr>
              <?php else: while ($tc = $top_cust->fetch_assoc()): ?>
                <tr>
                  <td>
                    <strong><?= e($tc['name']) ?></strong><br>
                    <span class="text-xs text-muted"><?= e($tc['email']) ?></span>
                  </td>
                  <td><?= (int)$tc['bookings'] ?></td>
                  <td class="text-success fw-600">Rs. <?= number_format($tc['spent'],0) ?></td>
                </tr>
              <?php endwhile; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require_once '../includes/footer.php'; ?>
