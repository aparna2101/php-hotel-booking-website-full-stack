<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

$error = '';

$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
if (!empty($redirect)) {
    $_SESSION['booking_redirect'] = $redirect;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm_password'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please provide a valid email address.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match. Please verify.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $otp = strval(rand(100000, 999999));
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        if ($db_connected && $pdo) {
            // Check if email exists
            $stmt = $pdo->prepare("SELECT id, is_verified FROM `users` WHERE `email` = ?");
            $stmt->execute([$email]);
            $existing_user = $stmt->fetch();

            if ($existing_user && $existing_user['is_verified'] == 1) {
                $error = "An account with this email already exists. Please login.";
            } else {
                if ($existing_user) {
                    // Update unverified user with new password & OTP
                    $stmt = $pdo->prepare("UPDATE `users` SET `name` = ?, `phone` = ?, `password` = ?, `otp` = ?, `otp_expiry` = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE `email` = ?");
                    $stmt->execute([$name, $phone, $hashed_password, $otp, $email]);
                } else {
                    // Insert new user
                    $stmt = $pdo->prepare("INSERT INTO `users` (`name`, `email`, `phone`, `password`, `otp`, `otp_expiry`, `is_verified`) VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE), 0)");
                    $stmt->execute([$name, $email, $phone, $hashed_password, $otp]);
                }

                // Send Luxury HTML OTP Email
                $email_content = '
                  <p>Welcome to <strong>HOTEL Luxury Stay & Rooms</strong>,</p>
                  <p>Dear <strong>' . htmlspecialchars($name) . '</strong>, thank you for joining our exclusive Guest Circle. To complete your registration, please verify your email address with the code below:</p>
                  <div style="background: rgba(197, 155, 39, 0.1); border: 2px dashed #C59B27; border-radius: 8px; padding: 18px; text-align: center; margin: 24px 0;">
                    <span style="font-family: monospace; font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #0A1329;">' . $otp . '</span>
                  </div>
                  <p style="font-size: 13px; color: #64748B;">This OTP is valid for 15 minutes. If you did not request this, please ignore this email.</p>
                ';
                $html = get_luxury_email_template("Your Verification Code", "Guest Circle Registration", $email_content);
                send_hotel_smtp_mail($email, $name, "Your Verification Code: {$otp}", $html);

                $_SESSION['pending_verify_email'] = $email;
                $_SESSION['pending_verify_name']  = $name;

                header("Location: verify-otp.php");
                exit;
            }
        } else {
            // Offline fallback
            $_SESSION['pending_verify_email'] = $email;
            $_SESSION['pending_verify_name']  = $name;
            header("Location: verify-otp.php");
            exit;
        }
    }
}

$page_title = "Create Guest Account | HOTEL";
$current_page = "register";

include 'includes/header.php';
?>

<style>
.auth-grid-2col {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-bottom: 14px;
}
@media (max-width: 640px) {
  .auth-grid-2col {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
    margin-bottom: 12px !important;
  }
}
@media (max-width: 480px) {
  .auth-page-wrapper {
    padding: 20px 12px !important;
  }
  .auth-card {
    padding: 24px 16px !important;
    max-width: 100% !important;
  }
}
</style>

<div class="auth-page-wrapper">
  <div class="auth-card auth-card-dark" style="max-width: 500px;">
    
    <div style="text-align: center; margin-bottom: 22px;">
      <img src="assets/images/logo.png" alt="Hotel Logo" style="width: 52px; height: 52px; border-radius: 50%; border: 1.5px solid var(--color-gold); margin: 0 auto 12px; object-fit: cover;">
      <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: #FFFFFF; margin-bottom: 4px;">Join Our Guest Circle</h2>
      <p style="color: #94A3B8; font-size: 0.82rem;">Enjoy exclusive member rates, early check-in, and personalized perks</p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #EF4444; color: #FCA5A5; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.82rem; margin-bottom: 18px;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="register.php">
      <?php if (!empty($redirect)): ?>
        <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">
      <?php endif; ?>

      <div class="form-group" style="margin-bottom: 14px;">
        <label><i class="fa-solid fa-user"></i> Full Name</label>
        <input type="text" name="name" placeholder="Your Full Name" required>
      </div>

      <div class="auth-grid-2col">
        <div class="form-group">
          <label><i class="fa-solid fa-envelope"></i> Email Address</label>
          <input type="email" name="email" placeholder="name@domain.com" required>
        </div>
        <div class="form-group">
          <label><i class="fa-solid fa-phone"></i> Mobile Number</label>
          <input type="tel" name="phone" placeholder="+91 90000 00000" required>
        </div>
      </div>

      <div class="auth-grid-2col">
        <div class="form-group">
          <label><i class="fa-solid fa-lock"></i> Password</label>
          <div style="position: relative; display: flex; align-items: center;">
            <input type="password" id="regPassword" name="password" placeholder="••••••••" style="width: 100%; padding-right: 38px;" required>
            <button type="button" onclick="togglePasswordVisibility('regPassword', 'eyeIconReg1')" style="position: absolute; right: 10px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;" title="Show / Hide Password">
              <i class="fa-solid fa-eye" id="eyeIconReg1"></i>
            </button>
          </div>
        </div>
        <div class="form-group">
          <label><i class="fa-solid fa-lock"></i> Confirm Password</label>
          <div style="position: relative; display: flex; align-items: center;">
            <input type="password" id="regConfirmPassword" name="confirm_password" placeholder="••••••••" style="width: 100%; padding-right: 38px;" required>
            <button type="button" onclick="togglePasswordVisibility('regConfirmPassword', 'eyeIconReg2')" style="position: absolute; right: 10px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 4px; font-size: 0.9rem; display: flex; align-items: center; justify-content: center;" title="Show / Hide Password">
              <i class="fa-solid fa-eye" id="eyeIconReg2"></i>
            </button>
          </div>
        </div>
      </div>

      <div style="display: flex; align-items: flex-start; gap: 8px; margin-bottom: 22px; font-size: 0.78rem; color: #CBD5E1;">
        <input type="checkbox" id="terms" required style="accent-color: var(--color-gold); margin-top: 3px; cursor: pointer;">
        <label for="terms" style="cursor: pointer; line-height: 1.4;">
          I agree to the hotel membership terms, privacy policy, and booking conditions.
        </label>
      </div>

      <button type="submit" class="btn btn-gold" style="width: 100%; padding: 10px;">
        <i class="fa-solid fa-crown"></i> Complete Registration
      </button>

      <div style="text-align: center; margin-top: 20px; font-size: 0.82rem; color: #94A3B8; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 16px;">
        Already registered? 
        <a href="login.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--color-gold-light); font-weight: 600; text-decoration: underline;">
          Sign In Here
        </a>
      </div>
    </form>

  </div>
</div>

<script>
function togglePasswordVisibility(inputId, iconId) {
  var input = document.getElementById(inputId);
  var icon = document.getElementById(iconId);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
    icon.style.color = "var(--color-gold-light)";
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
    icon.style.color = "#94A3B8";
  }
}
</script>

<?php include 'includes/footer.php'; ?>
