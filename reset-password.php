<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

$reset_email = $_SESSION['reset_email'] ?? '';
$user_name   = $_SESSION['reset_user_name'] ?? 'Valued Guest';

if (empty($reset_email)) {
    header("Location: forgot-password.php");
    exit;
}

$error = '';
$success = '';

if (isset($_GET['sent'])) {
    $success = "A 6-digit verification code has been sent to <strong>" . htmlspecialchars($reset_email) . "</strong>.";
}

// Handle Resend OTP
if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    $new_otp = strval(rand(100000, 999999));

    if ($db_connected && $pdo) {
        $stmt = $pdo->prepare("UPDATE `users` SET `otp` = ?, `otp_expiry` = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE `email` = ?");
        $stmt->execute([$new_otp, $reset_email]);
    }

    $email_content = '
      <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
      <p>Here is your new one-time verification code to reset your account password:</p>
      <div style="background: rgba(197, 155, 39, 0.1); border: 2px dashed #C59B27; border-radius: 8px; padding: 18px; text-align: center; margin: 24px 0;">
        <span style="font-family: monospace; font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #0A1329;">' . $new_otp . '</span>
      </div>
      <p style="font-size: 13px; color: #64748B;">This OTP is valid for 15 minutes. For your security, do not share this code with anyone.</p>
    ';
    $html = get_luxury_email_template("New Password Reset Code", "Account Security", $email_content);
    send_hotel_smtp_mail($reset_email, $user_name, "Your New Reset OTP: {$new_otp}", $html);

    $success = "A fresh 6-digit OTP code has been dispatched to your email.";
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp      = trim($_POST['otp'] ?? '');
    $new_password     = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($entered_otp) || empty($new_password) || empty($confirm_password)) {
        $error = "Please fill in all required fields.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters in length.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New password and confirmation password do not match.";
    } elseif ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
            $stmt->execute([$reset_email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "Account not found. Please restart the forgot password process.";
            } elseif ($user['otp'] !== $entered_otp) {
                $error = "Invalid OTP code. Please check your email and enter the correct code.";
            } elseif (password_verify($new_password, $user['password'])) {
                $error = "New password cannot be the same as your old password. Please choose a different password.";
            } else {
                // Update password securely using bcrypt
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $up = $pdo->prepare("UPDATE `users` SET `password` = ?, `otp` = NULL, `otp_expiry` = NULL, `is_verified` = 1 WHERE `id` = ?");
                $up->execute([$hashed, $user['id']]);

                unset($_SESSION['reset_email']);
                unset($_SESSION['reset_user_name']);

                header("Location: login.php?reset=success");
                exit;
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        // Fallback
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_name']);
        header("Location: login.php?reset=success");
        exit;
    }
}

$page_title = "Set New Password | HOTEL";
$current_page = "login";

include 'includes/header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card auth-card-dark" style="max-width: 480px;">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="width: 58px; height: 58px; background: rgba(197, 155, 39, 0.15); border: 1.5px solid var(--color-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: var(--color-gold-light); font-size: 1.5rem;">
        <i class="fa-solid fa-lock-open"></i>
      </div>
      <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: #FFFFFF; margin-bottom: 4px;">Set New Password</h2>
      <p style="color: #94A3B8; font-size: 0.82rem;">Enter the 6-digit OTP code and choose your new password</p>
    </div>

    <!-- Active Email Pill -->
    <div style="background: rgba(10, 19, 41, 0.6); border: 1px solid var(--color-border-gold); padding: 10px 14px; border-radius: var(--radius-xs); margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; font-size: 0.82rem;">
      <span style="color: #CBD5E1;">
        <i class="fa-solid fa-envelope text-gold"></i> <?php echo htmlspecialchars($reset_email); ?>
      </span>
      <a href="forgot-password.php" style="color: var(--color-gold-light); font-size: 0.76rem; text-decoration: underline;">Change</a>
    </div>

    <?php if (!empty($success)): ?>
      <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22C55E; color: #86EFAC; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.82rem; margin-bottom: 18px;">
        <i class="fa-solid fa-circle-check"></i> <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #EF4444; color: #FCA5A5; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.82rem; margin-bottom: 18px;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="reset-password.php">

      <!-- 6-Digit OTP Field -->
      <div class="form-group" style="margin-bottom: 18px;">
        <label><i class="fa-solid fa-shield-halved"></i> 6-Digit Verification Code *</label>
        <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="• • • • • •" required autofocus style="text-align: center; font-size: 1.35rem; letter-spacing: 6px; font-weight: 700; background: #0A1329; border: 1.5px solid var(--color-border-gold); color: #FFFFFF;">
        <div style="text-align: right; margin-top: 6px;">
          <a href="reset-password.php?action=resend" style="color: var(--color-gold-light); font-size: 0.76rem; text-decoration: underline;">
            <i class="fa-solid fa-rotate-right"></i> Resend OTP Code
          </a>
        </div>
      </div>

      <!-- New Password with Eye Icon -->
      <div class="form-group" style="margin-bottom: 16px;">
        <label><i class="fa-solid fa-lock"></i> New Password (min 6 characters) *</label>
        <div style="position: relative; display: flex; align-items: center;">
          <input type="password" id="resetPw1" name="password" placeholder="••••••••" style="width: 100%; padding-right: 42px;" required>
          <button type="button" onclick="togglePasswordVisibility('resetPw1', 'resetEye1')" style="position: absolute; right: 12px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 6px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center;" title="Show / Hide Password">
            <i class="fa-solid fa-eye" id="resetEye1"></i>
          </button>
        </div>
      </div>

      <!-- Confirm New Password with Eye Icon -->
      <div class="form-group" style="margin-bottom: 22px;">
        <label><i class="fa-solid fa-lock"></i> Confirm New Password *</label>
        <div style="position: relative; display: flex; align-items: center;">
          <input type="password" id="resetPw2" name="confirm_password" placeholder="••••••••" style="width: 100%; padding-right: 42px;" required>
          <button type="button" onclick="togglePasswordVisibility('resetPw2', 'resetEye2')" style="position: absolute; right: 12px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 6px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center;" title="Show / Hide Password">
            <i class="fa-solid fa-eye" id="resetEye2"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-gold" style="width: 100%; padding: 11px;">
        <i class="fa-solid fa-check-double"></i> Save Password & Sign In
      </button>

      <div style="text-align: center; margin-top: 22px; font-size: 0.82rem; color: #94A3B8; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 16px;">
        <a href="login.php" style="color: var(--color-gold-light); font-weight: 600; text-decoration: underline;">
          <i class="fa-solid fa-arrow-left"></i> Back to Sign In
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
