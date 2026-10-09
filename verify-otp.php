<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

$pending_email = $_SESSION['pending_verify_email'] ?? '';
if (empty($pending_email)) {
    header("Location: register.php");
    exit;
}

$error = '';
$success = '';

// Handle Resend OTP
if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    $new_otp = strval(rand(100000, 999999));
    $user_name = $_SESSION['pending_verify_name'] ?? 'Guest';

    if ($db_connected && $pdo) {
        $stmt = $pdo->prepare("UPDATE `users` SET `otp` = ?, `otp_expiry` = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE `email` = ?");
        $stmt->execute([$new_otp, $pending_email]);
    }

    $email_content = '
      <p>Dear <strong>' . htmlspecialchars($user_name) . '</strong>,</p>
      <p>Here is your new one-time verification code to activate your luxury guest account:</p>
      <div style="background: rgba(197, 155, 39, 0.1); border: 2px dashed #C59B27; border-radius: 8px; padding: 18px; text-align: center; margin: 24px 0;">
        <span style="font-family: monospace; font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #0A1329;">' . $new_otp . '</span>
      </div>
      <p style="font-size: 13px; color: #64748B;">This OTP is valid for 15 minutes. For your security, please do not share this code with anyone.</p>
    ';
    $html = get_luxury_email_template("Your Verification Code", "Account Security", $email_content);
    send_hotel_smtp_mail($pending_email, $user_name, "Your Verification Code: {$new_otp}", $html);

    $success = "A fresh OTP has been dispatched to your email.";
}

// Handle OTP Verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $entered_otp = trim($_POST['otp'] ?? '');

    if (empty($entered_otp)) {
        $error = "Please enter the 6-digit OTP sent to your email.";
    } elseif ($db_connected && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmt->execute([$pending_email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = "Account not found. Please register again.";
        } elseif ($user['otp'] !== $entered_otp) {
            $error = "Invalid OTP code. Please check your email and try again.";
        } else {
            // Mark verified
            $update = $pdo->prepare("UPDATE `users` SET `is_verified` = 1, `otp` = NULL WHERE `id` = ?");
            $update->execute([$user['id']]);

            // Log in user
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_name']      = $user['name'];
            $_SESSION['user_email']     = $user['email'];
            $_SESSION['user_phone']     = $user['phone'];

            unset($_SESSION['pending_verify_email']);
            unset($_SESSION['pending_verify_name']);

            $dest = $_SESSION['booking_redirect'] ?? 'my-bookings.php?verified=1';
            unset($_SESSION['booking_redirect']);

            header("Location: " . $dest);
            exit;
        }
    } else {
        // Offline fallback
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_name']      = $_SESSION['pending_verify_name'] ?? 'Guest';
        $_SESSION['user_email']     = $pending_email;
        $_SESSION['user_phone']     = '+91 90000 00000';

        $dest = $_SESSION['booking_redirect'] ?? 'my-bookings.php?verified=1';
        unset($_SESSION['booking_redirect']);

        header("Location: " . $dest);
        exit;
    }
}

$page_title = "Verify Your Account | HOTEL";
$current_page = "register";

include 'includes/header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card auth-card-dark" style="max-width: 480px;">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="width: 58px; height: 58px; background: rgba(197, 155, 39, 0.15); border: 1.5px solid var(--color-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: var(--color-gold-light); font-size: 1.5rem;">
        <i class="fa-solid fa-envelope-circle-check"></i>
      </div>
      <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: #FFFFFF; margin-bottom: 6px;">Verify Your Email</h2>
      <p style="color: #94A3B8; font-size: 0.86rem; line-height: 1.5;">
        We have sent a 6-digit confirmation code to<br>
        <strong style="color: var(--color-gold-light);"><?php echo htmlspecialchars($pending_email); ?></strong>
      </p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #EF4444; color: #FCA5A5; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.82rem; margin-bottom: 18px;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($success)): ?>
      <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22C55E; color: #86EFAC; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.82rem; margin-bottom: 18px;">
        <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="verify-otp.php">
      <div class="form-group" style="margin-bottom: 20px;">
        <label style="text-align: center; display: block;"><i class="fa-solid fa-key"></i> Enter 6-Digit OTP</label>
        <input type="text" name="otp" maxlength="6" pattern="[0-9]{6}" placeholder="• • • • • •" required autofocus style="text-align: center; font-size: 1.4rem; letter-spacing: 8px; font-weight: 700; background: #0A1329; border: 1.5px solid var(--color-border-gold); color: #FFFFFF;">
      </div>

      <button type="submit" class="btn btn-gold" style="width: 100%; justify-content: center; padding: 12px; margin-bottom: 16px;">
        <i class="fa-solid fa-shield-halved"></i> Verify & Activate Account
      </button>
    </form>

    <div style="text-align: center; font-size: 0.84rem; color: #94A3B8; padding-top: 14px; border-top: 1px solid rgba(255, 255, 255, 0.08);">
      Didn't receive the email? 
      <a href="verify-otp.php?action=resend" style="color: var(--color-gold-light); font-weight: 600; text-decoration: underline;">Resend OTP</a>
    </div>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
