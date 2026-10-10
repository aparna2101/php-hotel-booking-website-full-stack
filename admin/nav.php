<?php
/**
 * Shared Admin Navigation & Mobile Responsive Header
 * Included by dashboard.php, bookings.php, rooms.php, profile.php
 */
if (!isset($admin_active_page)) {
    $admin_active_page = 'dashboard';
}

$admin_user_name  = $_SESSION['admin_name']  ?? 'Admin';
$admin_user_email = $_SESSION['admin_email'] ?? 'admin@hotel.com';
?>

<!-- Desktop Sidebar -->
<aside class="admin-sidebar">
  <div class="admin-brand-box">
    <img src="../assets/images/logo.png" alt="Hotel Logo" class="admin-brand-logo">
    <div>
      <span class="admin-brand-title">HOTEL</span>
      <span class="admin-brand-subtitle">ADMIN SUITE</span>
    </div>
  </div>

  <ul class="admin-nav">
    <li>
      <a href="dashboard.php" class="<?php echo $admin_active_page === 'dashboard' ? 'active' : ''; ?>">
        <i class="fa-solid fa-chart-pie"></i>
        <span>Dashboard</span>
      </a>
    </li>
    <li>
      <a href="bookings.php" class="<?php echo $admin_active_page === 'bookings' ? 'active' : ''; ?>">
        <i class="fa-solid fa-calendar-check"></i>
        <span>All Bookings</span>
      </a>
    </li>
    <li>
      <a href="rooms.php" class="<?php echo $admin_active_page === 'rooms' ? 'active' : ''; ?>">
        <i class="fa-solid fa-bed"></i>
        <span>Manage Rooms</span>
      </a>
    </li>
    <li>
      <a href="profile.php" class="<?php echo $admin_active_page === 'profile' ? 'active' : ''; ?>">
        <i class="fa-solid fa-user-shield"></i>
        <span>Admin Profile</span>
      </a>
    </li>
    <li class="nav-divider"></li>
    <li>
      <a href="../index.php" target="_blank" class="nav-link-website">
        <i class="fa-solid fa-arrow-up-right-from-square"></i>
        <span>View Website</span>
      </a>
    </li>
    <li>
      <a href="logout.php" class="nav-link-logout">
        <i class="fa-solid fa-power-off"></i>
        <span>Logout</span>
      </a>
    </li>
  </ul>

  <div class="admin-user-badge">
    <div class="user-badge-avatar">
      <i class="fa-solid fa-user"></i>
    </div>
    <div class="user-badge-info">
      <div class="user-badge-name"><?php echo htmlspecialchars($admin_user_name); ?></div>
      <div class="user-badge-email"><?php echo htmlspecialchars($admin_user_email); ?></div>
    </div>
  </div>
</aside>

<!-- Mobile Top Header (Visible on screens < 900px) -->
<header class="admin-mobile-header">
  <div class="mobile-header-inner">
    <div class="mobile-brand">
      <img src="../assets/images/logo.png" alt="Hotel Logo">
      <div class="mobile-brand-meta">
        <span class="brand-t">HOTEL</span>
        <span class="brand-s">ADMIN PORTAL</span>
      </div>
    </div>

    <div class="mobile-header-actions">
      <a href="../index.php" target="_blank" class="btn-mobile-site" title="View Guest Website">
        <i class="fa-solid fa-globe"></i>
      </a>
      <button type="button" class="btn-mobile-toggle" id="adminMenuToggle" aria-label="Toggle Admin Navigation">
        <i class="fa-solid fa-bars" id="adminMenuIcon"></i>
      </button>
    </div>
  </div>

  <!-- Mobile Quick Action Horizontal Tabs -->
  <nav class="admin-mobile-quicktabs">
    <a href="dashboard.php" class="quicktab-item <?php echo $admin_active_page === 'dashboard' ? 'active' : ''; ?>">
      <i class="fa-solid fa-chart-pie"></i>
      <span>Overview</span>
    </a>
    <a href="bookings.php" class="quicktab-item <?php echo $admin_active_page === 'bookings' ? 'active' : ''; ?>">
      <i class="fa-solid fa-receipt"></i>
      <span>Bookings</span>
    </a>
    <a href="rooms.php" class="quicktab-item <?php echo $admin_active_page === 'rooms' ? 'active' : ''; ?>">
      <i class="fa-solid fa-bed"></i>
      <span>Rooms</span>
    </a>
    <a href="profile.php" class="quicktab-item <?php echo $admin_active_page === 'profile' ? 'active' : ''; ?>">
      <i class="fa-solid fa-user-shield"></i>
      <span>Profile</span>
    </a>
    <a href="logout.php" class="quicktab-item quicktab-logout">
      <i class="fa-solid fa-power-off"></i>
      <span>Exit</span>
    </a>
  </nav>

  <!-- Mobile Dropdown Drawer -->
  <div class="admin-mobile-drawer" id="adminMobileDrawer">
    <div class="drawer-user-card">
      <div class="drawer-avatar"><i class="fa-solid fa-user-tie"></i></div>
      <div>
        <div class="drawer-uname"><?php echo htmlspecialchars($admin_user_name); ?></div>
        <div class="drawer-uemail"><?php echo htmlspecialchars($admin_user_email); ?></div>
      </div>
    </div>
    <ul class="drawer-nav">
      <li><a href="dashboard.php" class="<?php echo $admin_active_page === 'dashboard' ? 'active' : ''; ?>"><i class="fa-solid fa-chart-pie"></i> Dashboard Overview</a></li>
      <li><a href="bookings.php" class="<?php echo $admin_active_page === 'bookings' ? 'active' : ''; ?>"><i class="fa-solid fa-calendar-check"></i> Manage Bookings & Check-ins</a></li>
      <li><a href="rooms.php" class="<?php echo $admin_active_page === 'rooms' ? 'active' : ''; ?>"><i class="fa-solid fa-bed"></i> Manage Rooms & Rates</a></li>
      <li><a href="profile.php" class="<?php echo $admin_active_page === 'profile' ? 'active' : ''; ?>"><i class="fa-solid fa-user-shield"></i> Admin Security & Profile</a></li>
      <li style="margin-top: 10px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 10px;">
        <a href="../index.php" target="_blank" style="color: var(--color-gold-light);"><i class="fa-solid fa-arrow-up-right-from-square"></i> Open Live Guest Website</a>
      </li>
      <li><a href="logout.php" style="color: #EF4444;"><i class="fa-solid fa-power-off"></i> Logout from System</a></li>
    </ul>
  </div>
</header>

<script>
(function() {
  var toggle = document.getElementById('adminMenuToggle');
  var drawer = document.getElementById('adminMobileDrawer');
  var icon   = document.getElementById('adminMenuIcon');
  if (toggle && drawer && icon) {
    toggle.addEventListener('click', function(e) {
      e.stopPropagation();
      var isOpen = drawer.classList.contains('open');
      if (isOpen) {
        drawer.classList.remove('open');
        icon.className = 'fa-solid fa-bars';
      } else {
        drawer.classList.add('open');
        icon.className = 'fa-solid fa-xmark';
      }
    });

    document.addEventListener('click', function(e) {
      if (drawer.classList.contains('open') && !drawer.contains(e.target) && !toggle.contains(e.target)) {
        drawer.classList.remove('open');
        icon.className = 'fa-solid fa-bars';
      }
    });
  }
})();
</script>
