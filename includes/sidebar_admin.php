<?php $currentPage = $currentPage ?? ''; ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-name">Élite Catering</div>
    <span class="logo-sub">Admin Panel</span>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Main</div>
    <a href="dashboard.php" class="<?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fas fa-chart-pie"></i> Dashboard
    </a>
    <a href="orders.php" class="<?= $currentPage==='orders'?'active':'' ?>">
      <i class="fas fa-clipboard-list"></i> All Bookings
    </a>
    <a href="booking.php" class="<?= $currentPage==='booking'?'active':'' ?>">
      <i class="fas fa-calendar-plus"></i> Create Booking
    </a>
    <a href="reports.php" class="<?= $currentPage==='reports'?'active':'' ?>">
      <i class="fas fa-chart-bar"></i> Reports
    </a>
    <div class="sidebar-section">Manage</div>
    <a href="users.php" class="<?= $currentPage==='users'?'active':'' ?>">
      <i class="fas fa-users"></i> Users
    </a>
    <a href="menu.php" class="<?= $currentPage==='menu'?'active':'' ?>">
      <i class="fas fa-utensils"></i> Menu Items
    </a>
    <a href="packages.php" class="<?= $currentPage==='packages'?'active':'' ?>">
      <i class="fas fa-box-open"></i> Packages
    </a>
    <div class="sidebar-section">System</div>
    <a href="../index.php" target="_blank">
      <i class="fas fa-globe"></i> View Website
    </a>
    <a href="../auth/logout.php">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="s-avatar"><?= strtoupper(substr($_SESSION['name']??'A',0,1)) ?></div>
    <div>
      <div class="s-name"><?= e($_SESSION['name']??'Admin') ?></div>
      <div class="s-role">Administrator</div>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
