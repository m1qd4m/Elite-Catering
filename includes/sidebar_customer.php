<?php $currentPage = $currentPage ?? ''; ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-name">Élite Catering</div>
    <span class="logo-sub">My Account</span>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Bookings</div>
    <a href="dashboard.php" class="<?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fas fa-home"></i> My Dashboard
    </a>
    <a href="booking.php" class="<?= $currentPage==='booking'?'active':'' ?>">
      <i class="fas fa-calendar-plus"></i> New Booking
    </a>
    <a href="my_orders.php" class="<?= $currentPage==='my_orders'?'active':'' ?>">
      <i class="fas fa-list-alt"></i> My Orders
    </a>
    <div class="sidebar-section">Browse</div>
    <a href="../index.php" target="_blank">
      <i class="fas fa-globe"></i> View Menu
    </a>
    <div class="sidebar-section">Account</div>
    <a href="../auth/logout.php">
      <i class="fas fa-sign-out-alt"></i> Logout
    </a>
  </nav>
  <div class="sidebar-user">
    <div class="s-avatar"><?= strtoupper(substr($_SESSION['name']??'C',0,1)) ?></div>
    <div>
      <div class="s-name"><?= htmlspecialchars($_SESSION['name']??'Customer') ?></div>
      <div class="s-role">Customer</div>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
