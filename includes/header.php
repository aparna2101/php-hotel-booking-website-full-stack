<?php
require_once __DIR__ . '/auth.php';

if (!isset($page_title)) {
    $page_title = "HOTEL | Luxury Stay & Rooms";
}
if (!isset($current_page)) {
    $current_page = "home";
}

$current_user = get_logged_user();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Welcome to our Luxury Hotel. Experience seaside elegance, private beachfront rooms, fine dining, and personalized hospitality.">
  <title><?php echo htmlspecialchars($page_title); ?></title>

  <!-- Google Fonts (Regal Serif & Modern Sans) -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Playfair+Display:ital,wght@0,500;0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 Free CDN for Luxury Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

  <!-- Main Luxury Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Top Announcement Bar -->
  <div class="top-bar">
    <div class="container">
      <div class="top-bar-left">
        <i class="fa-solid fa-crown text-gold"></i> 
        <span>5-Star Luxury Beachfront Hotel & Resort</span>
      </div>
      <div class="top-bar-links">
        <a href="tel:+919000000000"><i class="fa-solid fa-phone"></i> +91 90000 00000</a>
        <a href="mailto:info@hotel.com"><i class="fa-solid fa-envelope"></i> info@hotel.com</a>
        <a href="https://wa.me/919000000000?text=Hello%2C%20I%20would%20like%20to%20inquire%20about%20a%20hotel%20room%20booking" target="_blank" class="text-gold">
          <i class="fa-brands fa-whatsapp"></i> WhatsApp Us
        </a>
      </div>
    </div>
  </div>

  <!-- Main Luxury Header -->
  <header class="site-header" id="siteHeader">
    <div class="container">
      <nav class="navbar">
        <!-- Brand Logo & Name -->
        <a href="index.php" class="brand-logo" id="headerLogo">
          <img src="assets/images/logo.png" alt="Hotel Logo">
          <div class="brand-text">
            <span class="brand-name">HOTEL</span>
            <span class="brand-sub">LUXURY STAY & ROOMS</span>
          </div>
        </a>

        <!-- Navigation Links (Desktop + Mobile Dropdown) -->
        <ul class="nav-links" id="navLinks">
          <li><a href="index.php" class="<?php echo $current_page === 'home' ? 'active' : ''; ?>">Home</a></li>
          <li><a href="rooms.php" class="<?php echo $current_page === 'rooms' ? 'active' : ''; ?>">Rooms</a></li>
          <li><a href="about.php" class="<?php echo $current_page === 'about' ? 'active' : ''; ?>">About</a></li>
          <li><a href="contact.php" class="<?php echo $current_page === 'contact' ? 'active' : ''; ?>">Contact</a></li>
          
          <!-- Mobile Drawer Actions (Shows only inside mobile menu drawer) -->
          <li class="mobile-drawer-auth">
            <?php if ($current_user): ?>
              <a href="profile.php" class="btn btn-outline-gold btn-sm" style="width: 100%; justify-content: center;">
                <i class="fa-solid fa-user"></i> <span>My Profile</span>
              </a>
              <a href="my-bookings.php" class="btn btn-gold btn-sm" style="width: 100%; justify-content: center; margin-top: 8px;">
                <i class="fa-solid fa-receipt"></i> <span>My Bookings</span>
              </a>
              <a href="logout.php" class="btn btn-outline-gold btn-sm" style="width: 100%; justify-content: center; margin-top: 8px; color: #EF4444; border-color: #EF4444;">
                <i class="fa-solid fa-arrow-right-from-bracket"></i> <span>Logout</span>
              </a>
            <?php else: ?>
              <a href="login.php" class="btn btn-outline-gold btn-sm" style="width: 100%; justify-content: center;">
                <i class="fa-regular fa-user"></i> <span>Login / Register</span>
              </a>
              <a href="my-bookings.php" class="btn btn-gold btn-sm" style="width: 100%; justify-content: center; margin-top: 8px;">
                <i class="fa-solid fa-receipt"></i> <span>My Bookings</span>
              </a>
            <?php endif; ?>
          </li>
        </ul>

        <!-- Right Side Actions: Login/Register & My Bookings -->
        <div class="nav-actions">
          <?php if ($current_user): ?>
            <a href="profile.php" class="btn btn-outline-gold btn-sm nav-btn-desktop" id="headerProfileBtn" title="My Profile">
              <i class="fa-solid fa-user"></i> <span>My Profile</span>
            </a>
            <a href="my-bookings.php" class="btn btn-gold btn-sm nav-btn-desktop" id="headerMyBookingsBtn">
              <i class="fa-solid fa-receipt"></i> <span>My Bookings</span>
            </a>
            <a href="logout.php" class="btn btn-outline-gold btn-sm nav-btn-desktop" title="Sign Out" style="padding: 7px 11px;">
              <i class="fa-solid fa-arrow-right-from-bracket"></i>
            </a>
          <?php else: ?>
            <a href="login.php" class="btn btn-outline-gold btn-sm nav-btn-desktop" id="headerAuthBtn">
              <i class="fa-regular fa-user"></i> <span>Login / Register</span>
            </a>
            <a href="my-bookings.php" class="btn btn-gold btn-sm nav-btn-desktop" id="headerMyBookingsBtn">
              <i class="fa-solid fa-receipt"></i> <span>My Bookings</span>
            </a>
          <?php endif; ?>
          
          <!-- Mobile Quick Bookings Button (Visible on phone/tablet header) -->
          <a href="my-bookings.php" class="btn btn-gold btn-sm mobile-quick-btn" title="My Bookings">
            <i class="fa-solid fa-receipt"></i> <span class="mobile-quick-text">Bookings</span>
          </a>

          <!-- Mobile Menu Hamburger Button -->
          <button type="button" class="mobile-toggle" id="mobileToggle" aria-label="Toggle Navigation">
            <i class="fa-solid fa-bars"></i>
          </button>
        </div>
      </nav>
    </div>
  </header>
