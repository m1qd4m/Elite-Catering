<?php $currentPage = $currentPage ?? ''; ?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    <div class="logo-name">Élite Catering</div>
    <span class="logo-sub">Staff Panel</span>
  </div>
  <nav class="sidebar-nav">
    <div class="sidebar-section">Orders</div>
    <a href="dashboard.php" class="<?= $currentPage==='dashboard'?'active':'' ?>">
      <i class="fas fa-home"></i> Dashboard
    </a>
    <a href="orders.php" class="<?= $currentPage==='orders'?'active':'' ?>">
      <i class="fas fa-clipboard-list"></i> Manage Orders
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
    <div class="s-avatar"><?= strtoupper(substr($_SESSION['name']??'S',0,1)) ?></div>
    <div>
      <div class="s-name"><?= htmlspecialchars($_SESSION['name']??'Staff') ?></div>
      <div class="s-role">Staff Member</div>
    </div>
  </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>
