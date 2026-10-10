<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php"); exit;
}
require_once '../config/database.php';
if (!$db_connected || !$pdo) {
    die('<p style="padding:40px;font-family:sans-serif;color:red;">Database not connected. Start MySQL in XAMPP.</p>');
}

$alert = ''; $alert_type = 'success';
$admin_id = $_SESSION['admin_id'] ?? null;

// Fetch current admin
$admin = null;
if ($admin_id) {
    $s = $pdo->prepare("SELECT * FROM `admins` WHERE `id`=?");
    $s->execute([$admin_id]);
    $admin = $s->fetch();
}

// Handle UPDATE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form = $_POST['form'] ?? '';

    // UPDATE email/name
    if ($form === 'update_info') {
        $new_name  = trim($_POST['name'] ?? '');
        $new_email = strtolower(trim($_POST['email'] ?? ''));
        if (!$new_name || !$new_email) {
            $alert = 'Name and email are required.'; $alert_type = 'error';
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $alert = 'Invalid email format.'; $alert_type = 'error';
        } else {
            // Check if email already taken by someone else
            $ck = $pdo->prepare("SELECT `id` FROM `admins` WHERE `email`=? AND `id`!=?");
            $ck->execute([$new_email, $admin_id]);
            if ($ck->fetch()) {
                $alert = 'That email is already in use by another admin.'; $alert_type = 'error';
            } else {
                $pdo->prepare("UPDATE `admins` SET `name`=?, `email`=?, `username`=? WHERE `id`=?")
                    ->execute([$new_name, $new_email, strtolower(explode('@', $new_email)[0]), $admin_id]);
                $_SESSION['admin_name']  = $new_name;
                $_SESSION['admin_email'] = $new_email;
                $admin['name'] = $new_name;
                $admin['email'] = $new_email;
                $alert = 'Profile updated successfully!';
            }
        }
    }

    // CHANGE password
    if ($form === 'change_password') {
        $cur_pass  = trim($_POST['current_password'] ?? '');
        $new_pass  = trim($_POST['new_password'] ?? '');
        $conf_pass = trim($_POST['confirm_password'] ?? '');
        if (!$cur_pass || !$new_pass || !$conf_pass) {
            $alert = 'All password fields are required.'; $alert_type = 'error';
        } elseif (!password_verify($cur_pass, $admin['password'])) {
            $alert = 'Current password is incorrect.'; $alert_type = 'error';
        } elseif ($cur_pass === $new_pass || password_verify($new_pass, $admin['password'])) {
            $alert = 'New password cannot be the same as your old password. Please choose a different password.'; $alert_type = 'error';
        } elseif (strlen($new_pass) < 6) {
            $alert = 'New password must be at least 6 characters.'; $alert_type = 'error';
        } elseif ($new_pass !== $conf_pass) {
            $alert = 'New passwords do not match.'; $alert_type = 'error';
        } else {
            $hashed = password_hash($new_pass, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE `admins` SET `password`=? WHERE `id`=?")->execute([$hashed, $admin_id]);
            $admin['password'] = $hashed;
            $alert = 'Password changed successfully!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Profile | HOTEL Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link rel="stylesheet" href="../assets/css/style.css">
<style>
*, *::before, *::after {
  box-sizing: border-box;
}
html, body {
  margin: 0;
  padding: 0;
  width: 100%;
  max-width: 100%;
  overflow-x: hidden;
  background: #F8FAFC;
}
.admin-layout {
  display: grid;
  grid-template-columns: 240px minmax(0, 1fr);
  min-height: 100vh;
  width: 100%;
  max-width: 100%;
}
.admin-sidebar {
  background: var(--color-primary-dark);
  color: #FFF;
  padding: 24px 16px;
  border-right: 1px solid var(--color-border-gold);
}
.admin-nav { list-style: none; padding: 0; margin: 0; }
.admin-nav li { margin-bottom: 4px; }
.admin-nav a {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border-radius: var(--radius-sm);
  color: #94A3B8;
  font-weight: 500;
  font-size: 0.87rem;
  text-decoration: none;
  transition: all 0.2s;
}
.admin-nav a.active, .admin-nav a:hover {
  background: rgba(197, 155, 39, 0.15);
  color: var(--color-gold-light);
}
.admin-main {
  padding: 28px 36px;
  background: #F8FAFC;
  width: 100%;
  max-width: 100%;
  min-width: 0;
  overflow-x: hidden;
  box-sizing: border-box;
}
.card {
  background: #FFF;
  border: 1px solid #E2E8F0;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 26px 30px;
  margin-bottom: 22px;
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}
.fg2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}
.fl label {
  display: block;
  font-size: 0.81rem;
  font-weight: 600;
  color: var(--color-primary);
  margin-bottom: 5px;
}
.fl input {
  width: 100%;
  padding: 9px 12px;
  border: 1.5px solid #CBD5E1;
  border-radius: var(--radius-xs);
  font-size: 0.87rem;
  font-family: inherit;
  color: var(--color-primary);
  transition: border-color 0.2s;
  box-sizing: border-box;
}
.fl input:focus {
  outline: none;
  border-color: var(--color-gold);
}
@media(max-width: 900px) {
  .admin-layout {
    display: block !important;
    width: 100% !important;
    max-width: 100% !important;
  }
  .admin-mobile-topbar {
    display: flex !important;
    justify-content: space-between;
    align-items: center;
    background: var(--color-primary-dark);
    padding: 12px 16px;
    border-bottom: 1px solid var(--color-border-gold);
    position: sticky;
    top: 0;
    z-index: 100;
    width: 100% !important;
    box-sizing: border-box;
  }
  .admin-sidebar {
    display: none;
    position: fixed;
    top: 57px;
    left: 0;
    width: 260px;
    height: calc(100vh - 57px);
    z-index: 99;
    box-shadow: 5px 0 25px rgba(0,0,0,0.5);
    overflow-y: auto;
  }
  .admin-sidebar.sidebar-open {
    display: block !important;
  }
  .admin-main {
    padding: 18px 12px !important;
    width: 100% !important;
    max-width: 100% !important;
    min-width: 0 !important;
    overflow-x: hidden !important;
  }
  .card {
    padding: 18px 14px !important;
  }
  .fg2 {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
  }
}
@media(max-width: 480px) {
  .admin-main h1 {
    font-size: 1.35rem !important;
  }
}
</style>
</head>
<body>

<!-- Mobile Top Bar (< 900px) -->
<div class="admin-mobile-topbar" style="display:none;">
  <div style="display:flex;align-items:center;gap:10px;">
    <img src="../assets/images/logo.png" alt="Logo" style="width:34px;height:34px;border-radius:50%;border:1.5px solid var(--color-gold);object-fit:cover;">
    <span style="font-family:var(--font-serif);font-size:1.05rem;color:#FFF;letter-spacing:1.5px;">HOTEL ADMIN</span>
  </div>
  <div style="display:flex;gap:8px;align-items:center;">
    <a href="../index.php" target="_blank" style="color:#38BDF8;font-size:0.82rem;padding:6px 10px;background:rgba(255,255,255,0.08);border-radius:4px;text-decoration:none;">
      <i class="fa-solid fa-globe"></i>
    </a>
    <button type="button" onclick="document.getElementById('adminProfileSidebar').classList.toggle('sidebar-open')" style="background:rgba(197,155,39,0.2);border:1px solid var(--color-gold);color:var(--color-gold-light);padding:6px 12px;border-radius:4px;font-size:0.88rem;cursor:pointer;display:flex;align-items:center;gap:6px;">
      <i class="fa-solid fa-bars"></i> Menu
    </button>
  </div>
</div>

<div class="admin-layout">
<aside class="admin-sidebar" id="adminProfileSidebar">
  <div class="brand-logo" style="margin-bottom:30px;">
    <img src="../assets/images/logo.png" alt="Logo" style="width:36px;height:36px;border-radius:50%;border:2px solid var(--color-gold);object-fit:cover;">
    <div><span class="brand-name" style="font-size:0.95rem;letter-spacing:2px;">HOTEL</span><span class="brand-sub" style="font-size:0.48rem;letter-spacing:2px;display:block;">Admin</span></div>
  </div>
  <ul class="admin-nav">
    <li><a href="dashboard.php"><i class="fa-solid fa-chart-pie"></i> Dashboard</a></li>
    <li><a href="bookings.php"><i class="fa-solid fa-receipt"></i> All Bookings</a></li>
    <li><a href="rooms.php"><i class="fa-solid fa-bed"></i> Manage Rooms</a></li>
    <li><a href="profile.php" class="active"><i class="fa-solid fa-user-shield"></i> My Profile</a></li>
    <li><a href="../index.php" target="_blank"><i class="fa-solid fa-globe"></i> View Website</a></li>
    <li style="margin-top:28px;"><a href="logout.php" style="color:#EF4444;"><i class="fa-solid fa-power-off"></i> Logout</a></li>
  </ul>
</aside>

<main class="admin-main">
  <div style="margin-bottom:20px;">
    <h1 style="font-family:var(--font-serif);font-size:1.55rem;color:var(--color-primary);margin-bottom:3px;"><i class="fa-solid fa-user-shield text-gold"></i> My Profile</h1>
    <p style="color:var(--color-text-muted);font-size:0.8rem;margin:0;">Update your admin email, name, and password securely.</p>
  </div>

  <?php if($alert): ?>
    <div style="padding:10px 15px;border-radius:var(--radius-xs);margin-bottom:16px;font-size:0.86rem;<?php echo $alert_type==='error'?'background:#FEE2E2;border:1px solid #EF4444;color:#991B1B':'background:rgba(34,197,94,0.12);border:1px solid #22C55E;color:#166534'; ?>">
      <i class="fa-solid <?php echo $alert_type==='error'?'fa-circle-exclamation':'fa-circle-check'; ?>"></i> <?php echo htmlspecialchars($alert); ?>
    </div>
  <?php endif; ?>

  <!-- PROFILE INFO -->
  <div class="card">
    <h2 style="font-family:var(--font-serif);font-size:1.1rem;color:var(--color-primary);margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #E2E8F0;">
      <i class="fa-solid fa-pen-to-square text-gold"></i> Admin Information
    </h2>
    <form method="POST" action="profile.php">
      <input type="hidden" name="form" value="update_info">
      <div class="fg2">
        <div class="fl"><label><i class="fa-solid fa-user text-gold"></i> Full Name *</label>
          <input type="text" name="name" value="<?php echo htmlspecialchars($admin['name'] ?? $_SESSION['admin_name'] ?? 'Hotel Manager'); ?>" required></div>
        <div class="fl"><label><i class="fa-solid fa-envelope text-gold"></i> Admin Email *</label>
          <input type="email" name="email" value="<?php echo htmlspecialchars($admin['email'] ?? $_SESSION['admin_email'] ?? ''); ?>" required></div>
      </div>
      <div style="background:#F8FAFC;border:1px solid #E2E8F0;border-radius:var(--radius-xs);padding:12px 14px;font-size:0.8rem;color:var(--color-text-muted);margin-bottom:16px;">
        <i class="fa-solid fa-circle-info text-gold"></i>
        <strong>Note:</strong> This email is used to log in to the admin panel. Changing it will require you to use the new email on your next login.
      </div>
      <button type="submit" class="btn btn-gold btn-sm"><i class="fa-solid fa-floppy-disk"></i> Save Profile</button>
    </form>
  </div>

  <!-- CHANGE PASSWORD -->
  <div class="card">
    <h2 style="font-family:var(--font-serif);font-size:1.1rem;color:var(--color-primary);margin-bottom:18px;padding-bottom:12px;border-bottom:1px solid #E2E8F0;">
      <i class="fa-solid fa-lock text-gold"></i> Change Password
    </h2>
    <form method="POST" action="profile.php">
      <input type="hidden" name="form" value="change_password">
      <div class="fg2">
        <div class="fl"><label><i class="fa-solid fa-lock-open text-gold"></i> Current Password *</label>
          <input type="password" name="current_password" placeholder="Enter current password" required></div>
        <div></div>
      </div>
      <div class="fg2">
        <div class="fl"><label><i class="fa-solid fa-lock text-gold"></i> New Password * <small style="color:var(--color-text-muted);font-weight:400;">(min 6 chars)</small></label>
          <input type="password" name="new_password" placeholder="Enter new password" required></div>
        <div class="fl"><label><i class="fa-solid fa-lock text-gold"></i> Confirm New Password *</label>
          <input type="password" name="confirm_password" placeholder="Repeat new password" required></div>
      </div>
      <button type="submit" class="btn btn-outline-dark btn-sm"><i class="fa-solid fa-key"></i> Update Password</button>
    </form>
  </div>

  <!-- CURRENT CREDENTIALS INFO BOX -->
  <div style="background:rgba(10,19,41,0.04);border:1px solid var(--color-border-gold);border-radius:var(--radius-sm);padding:16px 20px;">
    <div style="font-size:0.82rem;color:var(--color-primary);font-weight:700;margin-bottom:8px;"><i class="fa-solid fa-shield-halved text-gold"></i> Current Login Credentials (for reference)</div>
    <div style="font-size:0.82rem;color:var(--color-text-muted);line-height:1.8;">
      <strong>Name:</strong> <?php echo htmlspecialchars($admin['name'] ?? $_SESSION['admin_name'] ?? 'Hotel Manager'); ?><br>
      <strong>Email:</strong> <?php echo htmlspecialchars($admin['email'] ?? $_SESSION['admin_email'] ?? 'admin@hotel.com'); ?><br>
      <strong>Role:</strong> <?php echo htmlspecialchars($admin['role'] ?? $_SESSION['admin_role'] ?? 'Super Admin'); ?><br>
      <strong>Login URL:</strong> <a href="login.php" style="color:var(--color-gold);">hotel/admin/login.php</a>
    </div>
  </div>

</main>
</div>
</body>
</html>
