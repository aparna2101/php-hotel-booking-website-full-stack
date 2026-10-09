<?php
require_once 'includes/auth.php';
require_once 'config/database.php';

$error = '';
$success = '';
$redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? 'my-bookings.php';

if (isset($_GET['registered'])) {
    $success = "Registration successful! Please sign in with your credentials.";
}
if (isset($_GET['verified'])) {
    $success = "Your email has been successfully verified! You may now sign in.";
}
if (isset($_GET['reset']) && $_GET['reset'] === 'success') {
    $success = "Your password has been successfully reset! Please sign in with your new password.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = trim($_POST['password'] ?? '');

    if (empty($email) || empty($password)) {
        $error = "Please enter both email address and password.";
    } elseif ($db_connected && $pdo) {
        $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $error = "Invalid email address or password.";
        } elseif ($user['is_verified'] == 0) {
            $_SESSION['pending_verify_email'] = $user['email'];
            $_SESSION['pending_verify_name']  = $user['name'];
            if (!empty($redirect) && $redirect !== 'my-bookings.php') {
                $_SESSION['booking_redirect'] = $redirect;
            }
            header("Location: verify-otp.php");
            exit;
        } else {
            $_SESSION['user_logged_in'] = true;
            $_SESSION['user_id']        = $user['id'];
            $_SESSION['user_name']      = $user['name'];
            $_SESSION['user_email']     = $user['email'];
            $_SESSION['user_phone']     = $user['phone'];

            header("Location: " . (!empty($redirect) ? $redirect : "my-bookings.php"));
            exit;
        }
    } else {
        // Fallback login
        $_SESSION['user_logged_in'] = true;
        $_SESSION['user_email']     = $email;
        $_SESSION['user_name']      = 'Valued Guest';
        $_SESSION['user_phone']     = '+91 90000 00000';
        header("Location: " . (!empty($redirect) ? $redirect : "my-bookings.php"));
        exit;
    }
}

$page_title = "Sign In | HOTEL";
$current_page = "login";

include 'includes/header.php';
?>

<div class="auth-page-wrapper">
  <div class="auth-card auth-card-dark">
    
    <div style="text-align: center; margin-bottom: 24px;">
      <img src="assets/images/logo.png" alt="Hotel Logo" style="width: 52px; height: 52px; border-radius: 50%; border: 1.5px solid var(--color-gold); margin: 0 auto 12px; object-fit: cover;">
      <h2 style="font-family: var(--font-serif); font-size: 1.5rem; color: #FFFFFF; margin-bottom: 4px;">Guest Sign In</h2>
      <p style="color: #94A3B8; font-size: 0.82rem;">Access your personalized hotel reservations & benefits</p>
    </div>

    <?php if (isset($_GET['msg']) && $_GET['msg'] === 'login_required'): ?>
      <div style="background: rgba(197, 155, 39, 0.15); border: 1.5px solid var(--color-gold); color: #FDE047; padding: 13px 16px; border-radius: var(--radius-xs); font-size: 0.84rem; margin-bottom: 20px; text-align: left; line-height: 1.5;">
        <div style="font-weight: 700; display: flex; align-items: center; gap: 8px; margin-bottom: 4px; color: var(--color-gold-light);">
          <i class="fa-solid fa-user-shield"></i> Guest Access Notice:
        </div>
        <div style="color: #E2E8F0; font-size: 0.82rem;">
          <?php if (strpos($redirect, 'my-bookings') !== false): ?>
            To view your personal reservations and download official vouchers, please <strong>Sign In</strong> with your registered credentials.
          <?php else: ?>
            To complete your room reservation with Govt ID verification and booking voucher generation, please <strong>Sign In</strong> or <a href="register.php?redirect=<?php echo urlencode($redirect); ?>" style="color: var(--color-gold-light); font-weight: 700; text-decoration: underline;">Create a Guest Account</a>.
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

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

    <form method="POST" action="login.php">
      <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($redirect); ?>">

      <div class="form-group" style="margin-bottom: 16px;">
        <label><i class="fa-solid fa-envelope"></i> Email Address</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" placeholder="name@domain.com" required>
      </div>

      <div class="form-group" style="margin-bottom: 18px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <label><i class="fa-solid fa-lock"></i> Password</label>
          <a href="forgot-password.php" style="font-size: 0.74rem; color: var(--color-gold-light); text-decoration: underline;">Forgot?</a>
        </div>
        <div style="position: relative; display: flex; align-items: center;">
          <input type="password" id="userPassword" name="password" placeholder="••••••••" style="width: 100%; padding-right: 42px;" required>
          <button type="button" onclick="togglePasswordVisibility('userPassword', 'eyeIconUser')" style="position: absolute; right: 12px; background: none; border: none; color: #94A3B8; cursor: pointer; padding: 6px; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; transition: color 0.2s ease;" title="Show / Hide Password">
            <i class="fa-solid fa-eye" id="eyeIconUser"></i>
          </button>
        </div>
      </div>

      <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 22px; font-size: 0.8rem; color: #CBD5E1;">
        <input type="checkbox" id="rememberMe" checked style="accent-color: var(--color-gold); cursor: pointer;">
        <label for="rememberMe" style="cursor: pointer;">Remember this device</label>
      </div>

      <button type="submit" class="btn btn-gold" style="width: 100%; padding: 10px;">
        <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Account
      </button>

      <div style="text-align: center; margin-top: 22px; font-size: 0.82rem; color: #94A3B8; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 16px;">
        Don't have an account yet? 
        <a href="register.php<?php echo !empty($redirect) && $redirect !== 'my-bookings.php' ? '?redirect=' . urlencode($redirect) : ''; ?>" style="color: var(--color-gold-light); font-weight: 600; text-decoration: underline;">
          Create Guest Account
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
