<?php
require_once 'includes/auth.php';
require_once 'config/database.php';
require_once 'includes/mailer.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (empty($email)) {
        $error = "Please enter your registered email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $error = "No guest account found with that email. Please check your spelling or register.";
            } else {
                // Generate 6-digit OTP
                $otp = strval(rand(100000, 999999));

                $up = $pdo->prepare("UPDATE `users` SET `otp` = ?, `otp_expiry` = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE `id` = ?");
                $up->execute([$otp, $user['id']]);

                // Prepare Email Content
                $email_content = '
                  <p>Dear <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>
                  <p>We received a request to reset the password for your HOTEL guest account. Use the one-time verification code below to set a new password:</p>
                  <div style="background: rgba(197, 155, 39, 0.1); border: 2px dashed #C59B27; border-radius: 8px; padding: 18px; text-align: center; margin: 24px 0;">
                    <span style="font-family: monospace; font-size: 32px; font-weight: 700; letter-spacing: 8px; color: #0A1329;">' . $otp . '</span>
                  </div>
                  <p style="font-size: 13px; color: #64748B;">This code is valid for 15 minutes. If you did not request a password reset, please ignore this email or contact front desk support immediately.</p>
                ';
                $email_html = get_luxury_email_template("Password Reset Request", "Account Security", $email_content);
                send_hotel_smtp_mail($user['email'], $user['name'], "Your Password Reset OTP: {$otp}", $email_html);

                $_SESSION['reset_email'] = $user['email'];
                $_SESSION['reset_user_name'] = $user['name'];

                header("Location: reset-password.php?sent=1");
                exit;
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        // Fallback demo
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_name'] = 'Valued Guest';
        header("Location: reset-password.php?sent=1");
        exit;
    }
}

$page_title = "Forgot Password | HOTEL";
$current_page = "login";

include 'includes/header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card auth-card-dark" style="max-width: 460px;">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <div style="width: 58px; height: 58px; background: rgba(197, 155, 39, 0.15); border: 1.5px solid var(--color-gold); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; color: var(--color-gold-light); font-size: 1.5rem;">
        <i class="fa-solid fa-key"></i>
      </div>
      <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: #FFFFFF; margin-bottom: 4px;">Forgot Password?</h2>
      <p style="color: #94A3B8; font-size: 0.82rem; line-height: 1.5;">
        Enter your registered email address and we'll send a 6-digit OTP code to reset your password.
      </p>
    </div>

    <?php if (!empty($error)): ?>
      <div style="background: rgba(239, 68, 68, 0.15); border: 1px solid #EF4444; color: #FCA5A5; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.82rem; margin-bottom: 18px;">
        <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="forgot-password.php">
      <div class="form-group" style="margin-bottom: 20px;">
        <label><i class="fa-solid fa-envelope"></i> Registered Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="name@domain.com" required autofocus>
      </div>

      <button type="submit" class="btn btn-gold" style="width: 100%; padding: 11px;">
        <i class="fa-solid fa-paper-plane"></i> Send Verification OTP
      </button>

      <div style="text-align: center; margin-top: 22px; font-size: 0.82rem; color: #94A3B8; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 16px;">
        Remembered your password? 
        <a href="login.php" style="color: var(--color-gold-light); font-weight: 600; text-decoration: underline;">
          Return to Sign In
        </a>
      </div>
    </form>

  </div>
</div>

<?php include 'includes/footer.php'; ?>
