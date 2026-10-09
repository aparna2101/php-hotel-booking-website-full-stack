<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// Must be logged in
if (!is_user_logged_in()) {
    header("Location: login.php?msg=login_required");
    exit;
}

$user_id = $_SESSION['user_id'] ?? 0;
$user_email = $_SESSION['user_email'] ?? '';

$db_user = null;
if ($db_connected && $pdo && $user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `id` = ?");
        $stmt->execute([$user_id]);
        $db_user = $stmt->fetch();
    } catch (Exception $e) {}
}

if (!$db_user && $db_connected && $pdo && $user_email) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmt->execute([$user_email]);
        $db_user = $stmt->fetch();
    } catch (Exception $e) {}
}

$user_name = $db_user['name'] ?? ($_SESSION['user_name'] ?? 'Valued Guest');
$user_phone = $db_user['phone'] ?? ($_SESSION['user_phone'] ?? '');
$user_email = $db_user['email'] ?? ($_SESSION['user_email'] ?? '');
$created_at = !empty($db_user['created_at']) ? date('d M Y', strtotime($db_user['created_at'])) : 'Recent Member';
$is_verified = !empty($db_user['is_verified']) && $db_user['is_verified'] == 1;

// Fetch booking count
$booking_count = 0;
if ($db_connected && $pdo) {
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM `bookings` WHERE `email` = ? OR `user_id` = ?");
        $s->execute([$user_email, $user_id]);
        $booking_count = (int)$s->fetchColumn();
    } catch (Exception $e) {}
}

$alert = '';
$alert_type = 'success';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 1. Update Personal Info
    if ($action === 'update_info') {
        $new_name  = trim($_POST['name'] ?? '');
        $new_phone = trim($_POST['phone'] ?? '');

        if (empty($new_name) || empty($new_phone)) {
            $alert = 'Please fill in both your name and phone number.';
            $alert_type = 'error';
        } elseif ($db_connected && $pdo && $db_user) {
            try {
                $stmt = $pdo->prepare("UPDATE `users` SET `name` = ?, `phone` = ? WHERE `id` = ?");
                $stmt->execute([$new_name, $new_phone, $db_user['id']]);

                $_SESSION['user_name']  = $new_name;
                $_SESSION['user_phone'] = $new_phone;
                $user_name = $new_name;
                $user_phone = $new_phone;

                $alert = 'Your profile details have been successfully updated!';
            } catch (Exception $e) {
                $alert = 'Database error updating profile: ' . $e->getMessage();
                $alert_type = 'error';
            }
        } else {
            $_SESSION['user_name']  = $new_name;
            $_SESSION['user_phone'] = $new_phone;
            $user_name = $new_name;
            $user_phone = $new_phone;
            $alert = 'Profile details updated.';
        }
    }

    // 2. Change Password
    if ($action === 'change_password') {
        $current_pass = trim($_POST['current_password'] ?? '');
        $new_pass     = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (empty($current_pass) || empty($new_pass) || empty($confirm_pass)) {
            $alert = 'Please fill in all password fields.';
            $alert_type = 'error';
        } elseif (strlen($new_pass) < 6) {
            $alert = 'New password must be at least 6 characters long.';
            $alert_type = 'error';
        } elseif ($new_pass !== $confirm_pass) {
            $alert = 'New passwords do not match. Please verify.';
            $alert_type = 'error';
        } elseif ($current_pass === $new_pass) {
            $alert = 'New password cannot be the same as your old password. Please choose a different password.';
            $alert_type = 'error';
        } elseif ($db_connected && $pdo && $db_user) {
            if (!password_verify($current_pass, $db_user['password'])) {
                $alert = 'Current password is incorrect. Please try again.';
                $alert_type = 'error';
            } elseif (password_verify($new_pass, $db_user['password'])) {
                $alert = 'New password cannot be the same as your old password. Please choose a different password.';
                $alert_type = 'error';
            } else {
                try {
                    $new_hash = password_hash($new_pass, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `id` = ?");
                    $stmt->execute([$new_hash, $db_user['id']]);
                    $db_user['password'] = $new_hash;
                    $alert = 'Password changed successfully! Keep it safe.';
                } catch (Exception $e) {
                    $alert = 'Error updating password: ' . $e->getMessage();
                    $alert_type = 'error';
                }
            }
        } else {
            $alert = 'Password updated.';
        }
    }
}

$page_title = "My Guest Profile | HOTEL";
$current_page = "profile";

include 'includes/header.php';
?>

<!-- Page Hero Header -->
<section style="background: linear-gradient(rgba(10, 19, 41, 0.88), rgba(10, 19, 41, 0.93)), url('assets/images/hero.jpg') center/cover; padding: 50px 0 45px; text-align: center; color: #FFFFFF;">
  <div class="container">
    <span class="hero-badge"><i class="fa-solid fa-user-shield"></i> Guest Circle Member</span>
    <h1 class="hero-title" style="margin-top: 15px; color: #FFFFFF; font-size: clamp(1.8rem, 4vw, 2.8rem);">My Profile & Security</h1>
    <p class="hero-subtitle" style="margin-bottom: 0; color: #E2E8F0; font-size: clamp(0.88rem, 2vw, 1.05rem);">
      Manage your personal contact info, view reservation statistics, and update your security credentials.
    </p>
  </div>
</section>

<section class="section-py" style="background: #F8FAFC;">
  <div class="container" style="max-width: 1040px;">

    <!-- Alerts -->
    <?php if (!empty($alert)): ?>
      <div style="padding: 14px 18px; border-radius: var(--radius-xs); margin-bottom: 24px; font-size: 0.9rem; display: flex; align-items: center; gap: 10px; <?php echo $alert_type === 'error' ? 'background: #FEE2E2; border: 1.5px solid #EF4444; color: #991B1B;' : 'background: rgba(34, 197, 94, 0.12); border: 1.5px solid #22C55E; color: #166534;'; ?>">
        <i class="fa-solid <?php echo $alert_type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check'; ?>" style="font-size: 1.1rem;"></i>
        <span><?php echo htmlspecialchars($alert); ?></span>
      </div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 320px 1fr; gap: 28px; align-items: flex-start;" class="profile-layout-grid">
      
      <!-- Left Column: Member Card -->
      <div>
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: var(--radius-md); padding: 30px 24px; text-align: center; box-shadow: var(--shadow-sm); margin-bottom: 20px;">
          <div style="width: 82px; height: 82px; border-radius: 50%; background: var(--color-primary-dark); color: var(--color-gold); display: flex; align-items: center; justify-content: center; font-size: 2.2rem; margin: 0 auto 16px; border: 2.5px solid var(--color-gold); font-family: var(--font-serif); font-weight: 700;">
            <?php echo strtoupper(substr($user_name, 0, 1)); ?>
          </div>
          
          <h2 style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--color-primary); margin-bottom: 4px;">
            <?php echo htmlspecialchars($user_name); ?>
          </h2>
          <p style="color: var(--color-text-muted); font-size: 0.84rem; margin-bottom: 14px; word-break: break-all;">
            <?php echo htmlspecialchars($user_email); ?>
          </p>

          <div style="display: inline-flex; align-items: center; gap: 6px; padding: 4px 12px; border-radius: 20px; font-size: 0.76rem; font-weight: 700; background: rgba(34, 197, 94, 0.12); color: #166534; border: 1px solid #86EFAC;">
            <i class="fa-solid fa-circle-check"></i> Verified Member
          </div>

          <div style="margin-top: 24px; padding-top: 20px; border-top: 1px solid #E2E8F0; text-align: left; font-size: 0.84rem; line-height: 1.8;">
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--color-text-muted);"><i class="fa-solid fa-phone text-gold"></i> Phone:</span>
              <strong><?php echo htmlspecialchars($user_phone ?: 'Not provided'); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--color-text-muted);"><i class="fa-solid fa-calendar text-gold"></i> Member Since:</span>
              <strong><?php echo htmlspecialchars($created_at); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between;">
              <span style="color: var(--color-text-muted);"><i class="fa-solid fa-receipt text-gold"></i> Reservations:</span>
              <strong style="color: var(--color-gold);"><?php echo $booking_count; ?> Stays</strong>
            </div>
          </div>
        </div>

        <!-- Quick Actions Card -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: var(--radius-md); padding: 20px; box-shadow: var(--shadow-sm);">
          <span class="section-tag" style="font-size: 0.72rem;">Quick Navigation</span>
          <div style="display: flex; flex-direction: column; gap: 8px; margin-top: 10px;">
            <a href="my-bookings.php" class="btn btn-outline-dark btn-sm" style="justify-content: flex-start;">
              <i class="fa-solid fa-receipt text-gold"></i> View My Bookings (<?php echo $booking_count; ?>)
            </a>
            <a href="rooms.php" class="btn btn-gold btn-sm" style="justify-content: flex-start;">
              <i class="fa-solid fa-bed"></i> Book Another Stay
            </a>
            <a href="logout.php" class="btn btn-outline-dark btn-sm" style="justify-content: flex-start; color: #EF4444; border-color: #EF4444;">
              <i class="fa-solid fa-power-off"></i> Sign Out
            </a>
          </div>
        </div>
      </div>

      <!-- Right Column: Edit Forms -->
      <div style="display: flex; flex-direction: column; gap: 24px;">

        <!-- 1. Personal Information Form -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-sm);">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(197, 155, 39, 0.12); color: var(--color-gold); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
              <i class="fa-solid fa-user-pen"></i>
            </div>
            <div>
              <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--color-primary); margin: 0;">Personal Information</h3>
              <p style="color: var(--color-text-muted); font-size: 0.8rem; margin: 0;">Update your display name and contact phone number.</p>
            </div>
          </div>

          <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="update_info">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;" class="prof-row">
              <div class="form-group">
                <label><i class="fa-solid fa-user text-gold"></i> Full Name *</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($user_name); ?>" placeholder="Your Full Name" required>
              </div>
              <div class="form-group">
                <label><i class="fa-solid fa-phone text-gold"></i> Mobile Number *</label>
                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_phone); ?>" placeholder="+91 90000 00000" required>
              </div>
            </div>

            <div class="form-group" style="margin-bottom: 22px;">
              <label><i class="fa-solid fa-envelope text-gold"></i> Registered Email (Account ID)</label>
              <input type="email" value="<?php echo htmlspecialchars($user_email); ?>" disabled style="background: #F1F5F9; cursor: not-allowed; color: #64748B;">
              <small style="color: var(--color-text-muted); font-size: 0.74rem; margin-top: 4px;">
                <i class="fa-solid fa-shield-halved text-gold"></i> Email address is verified and permanently linked to your bookings.
              </small>
            </div>

            <button type="submit" class="btn btn-gold btn-sm">
              <i class="fa-solid fa-floppy-disk"></i> Save Profile Details
            </button>
          </form>
        </div>

        <!-- 2. Change Password Form -->
        <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: var(--radius-md); padding: 30px; box-shadow: var(--shadow-sm);">
          <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #E2E8F0; padding-bottom: 14px;">
            <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(197, 155, 39, 0.12); color: var(--color-gold); display: flex; align-items: center; justify-content: center; font-size: 1.1rem;">
              <i class="fa-solid fa-key"></i>
            </div>
            <div>
              <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--color-primary); margin: 0;">Change Account Password</h3>
              <p style="color: var(--color-text-muted); font-size: 0.8rem; margin: 0;">Keep your reservation account safe with a strong password.</p>
            </div>
          </div>

          <form method="POST" action="profile.php">
            <input type="hidden" name="action" value="change_password">

            <div class="form-group" style="margin-bottom: 16px;">
              <label><i class="fa-solid fa-lock text-gold"></i> Current Password *</label>
              <div style="position: relative; display: flex; align-items: center;">
                <input type="password" id="profCurrentPw" name="current_password" placeholder="Enter current password" style="width: 100%; padding-right: 38px;" required>
                <button type="button" onclick="togglePasswordVisibility('profCurrentPw', 'eyeProf1')" style="position: absolute; right: 10px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;" title="Show/Hide">
                  <i class="fa-solid fa-eye" id="eyeProf1"></i>
                </button>
              </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 22px;" class="prof-row">
              <div class="form-group">
                <label><i class="fa-solid fa-shield-halved text-gold"></i> New Password (min 6 chars) *</label>
                <div style="position: relative; display: flex; align-items: center;">
                  <input type="password" id="profNewPw" name="new_password" placeholder="Enter new password" style="width: 100%; padding-right: 38px;" required>
                  <button type="button" onclick="togglePasswordVisibility('profNewPw', 'eyeProf2')" style="position: absolute; right: 10px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;" title="Show/Hide">
                    <i class="fa-solid fa-eye" id="eyeProf2"></i>
                  </button>
                </div>
              </div>
              <div class="form-group">
                <label><i class="fa-solid fa-shield-check text-gold"></i> Confirm New Password *</label>
                <div style="position: relative; display: flex; align-items: center;">
                  <input type="password" id="profConfirmPw" name="confirm_password" placeholder="Re-enter new password" style="width: 100%; padding-right: 38px;" required>
                  <button type="button" onclick="togglePasswordVisibility('profConfirmPw', 'eyeProf3')" style="position: absolute; right: 10px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px;" title="Show/Hide">
                    <i class="fa-solid fa-eye" id="eyeProf3"></i>
                  </button>
                </div>
              </div>
            </div>

            <button type="submit" class="btn btn-outline-dark btn-sm">
              <i class="fa-solid fa-lock"></i> Update Password
            </button>
          </form>
        </div>

      </div>

    </div>

  </div>
</section>

<script>
function togglePasswordVisibility(inputId, iconId) {
  var input = document.getElementById(inputId);
  var icon = document.getElementById(iconId);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
    icon.style.color = "var(--color-gold)";
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
    icon.style.color = "#94A3B8";
  }
}
</script>

<style>
@media (max-width: 820px) {
  .profile-layout-grid {
    grid-template-columns: 1fr !important;
  }
  .prof-row {
    grid-template-columns: 1fr !important;
  }
}
</style>

<?php include 'includes/footer.php'; ?>
