<?php
require_once 'config/database.php';
require_once 'includes/auth.php';

// MANDATORY AUTH GUARD: Must be logged in to view personal reservations
if (!is_user_logged_in()) {
    header("Location: login.php?msg=login_required&redirect=my-bookings.php");
    exit;
}

$cancel_msg = '';
$cancel_error = '';

// Handle Booking Cancellation by User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_booking') {
    $cancel_id = trim($_POST['booking_id'] ?? '');
    $reason_type = trim($_POST['reason_type'] ?? 'Change of travel plans');
    $reason_details = trim($_POST['reason_details'] ?? '');
    $full_reason = $reason_type . (!empty($reason_details) ? " - " . $reason_details : "");

    if (empty($cancel_id)) {
        $cancel_error = "Invalid booking ID.";
    } else {
        $found = false;
        // Verify and update in Database
        if ($db_connected && $pdo && !empty($_SESSION['user_email'])) {
            try {
                $check_stmt = $pdo->prepare("SELECT * FROM `bookings` WHERE `booking_id` = ? AND (`email` = ? OR `user_id` = ?)");
                $check_stmt->execute([$cancel_id, $_SESSION['user_email'], $_SESSION['user_id'] ?? 0]);
                $b_row = $check_stmt->fetch();

                if ($b_row) {
                    if ($b_row['status'] === 'Cancelled') {
                        $cancel_error = "This booking is already cancelled.";
                    } elseif ($b_row['status'] === 'Checked Out') {
                        $cancel_error = "Cannot cancel a stay that is already completed (Checked Out).";
                    } else {
                        $up_stmt = $pdo->prepare("UPDATE `bookings` SET `status` = 'Cancelled', `cancellation_reason` = ?, `cancelled_at` = NOW() WHERE `booking_id` = ?");
                        $up_stmt->execute([$full_reason, $cancel_id]);
                        $found = true;
                        $cancel_msg = "Your reservation <strong>" . htmlspecialchars($cancel_id) . "</strong> has been cancelled successfully.";

                        // Send cancellation email notification to admin & guest if mailer is available
                        if (file_exists('includes/mailer.php')) {
                            @require_once 'includes/mailer.php';
                            if (function_exists('send_email')) {
                                $admin_subject = "Cancellation Notice: Booking " . $cancel_id . " by " . $b_row['guest_name'];
                                $admin_body = "<h3>Reservation Cancelled</h3>"
                                    . "<p><strong>Booking Ref:</strong> {$cancel_id}</p>"
                                    . "<p><strong>Guest:</strong> {$b_row['guest_name']} ({$b_row['email']}, {$b_row['phone']})</p>"
                                    . "<p><strong>Room:</strong> {$b_row['room_title']}</p>"
                                    . "<p><strong>Dates:</strong> {$b_row['checkin']} to {$b_row['checkout']}</p>"
                                    . "<p><strong>Reason:</strong> " . htmlspecialchars($full_reason) . "</p>";
                                @send_email($admin_email, $admin_subject, $admin_body);

                                $guest_subject = "Reservation Cancelled: " . $cancel_id . " | HOTEL Luxury Resort";
                                $guest_body = "<h3>Booking Cancellation Confirmation</h3>"
                                    . "<p>Dear " . htmlspecialchars($b_row['guest_name']) . ",</p>"
                                    . "<p>Your reservation for <strong>" . htmlspecialchars($b_row['room_title']) . "</strong> (Booking ID: <strong>{$cancel_id}</strong>) has been successfully cancelled as per your request.</p>"
                                    . "<p><strong>Cancellation Reason:</strong> " . htmlspecialchars($full_reason) . "</p>"
                                    . "<p>We hope to welcome you to HOTEL in the future.</p>";
                                @send_email($b_row['email'], $guest_subject, $guest_body);
                            }
                        }
                    }
                } else {
                    $cancel_error = "Booking not found or unauthorized.";
                }
            } catch (Exception $e) {
                $cancel_error = "Database error: " . $e->getMessage();
            }
        }
    }
}

$bookings = [];

// Fetch room images lookup
$room_imgs = [];
if ($db_connected && $pdo) {
    try {
        $r_rows = $pdo->query("SELECT title, image FROM rooms")->fetchAll();
        foreach ($r_rows as $rr) {
            $room_imgs[$rr['title']] = $rr['image'];
        }
    } catch(Exception $e) {}
}

// Fetch from Database strictly for authenticated logged-in user
if ($db_connected && $pdo && !empty($_SESSION['user_email'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `bookings` WHERE `email` = ? OR `user_id` = ? ORDER BY `id` DESC");
        $stmt->execute([$_SESSION['user_email'], $_SESSION['user_id'] ?? 0]);
        $db_bookings = $stmt->fetchAll();
        foreach ($db_bookings as $row) {
            $img = $room_imgs[$row['room_title']] ?? 'assets/images/room1.jpg';

            $bookings[] = [
                'booking_id'          => $row['booking_id'],
                'room_title'          => $row['room_title'],
                'room_image'          => $img,
                'checkin'             => $row['checkin'],
                'checkout'            => $row['checkout'],
                'nights'              => $row['nights'],
                'guests'              => $row['guests'],
                'adults'              => $row['adults'] ?? 2,
                'children'            => $row['children'] ?? 0,
                'guest_name'          => $row['guest_name'],
                'phone'               => $row['phone'],
                'email'               => $row['email'],
                'id_type'             => $row['id_type'] ?? 'ID Proof',
                'id_number'           => $row['id_number'] ?? '',
                'city'                => $row['city'] ?? '',
                'state'               => $row['state'] ?? '',
                'arrival_time'        => $row['arrival_time'] ?? 'Standard Check-in',
                'visit_purpose'       => $row['visit_purpose'] ?? '',
                'meal_pref'           => $row['meal_pref'] ?? '',
                'total'               => $row['total_amount'],
                'status'              => $row['status'],
                'cancellation_reason' => $row['cancellation_reason'] ?? '',
                'cancelled_at'        => $row['cancelled_at'] ?? '',
                'created_at'          => date('d M Y, h:i A', strtotime($row['created_at']))
            ];
        }
    } catch (Exception $e) {}
}

// Clear any residual session booking array
unset($_SESSION['my_bookings']);

// Search by Booking ID, room, or phone
$search_query = trim($_GET['search'] ?? '');
if (!empty($search_query)) {
    $filtered = [];
    foreach ($bookings as $b) {
        if (stripos($b['booking_id'], $search_query) !== false || 
            stripos($b['phone'], $search_query) !== false ||
            stripos($b['room_title'], $search_query) !== false) {
            $filtered[] = $b;
        }
    }
    $bookings = $filtered;
}

$page_title = "My Reservations & Bookings | HOTEL";
$current_page = "my-bookings";

include 'includes/header.php';
?>

<!-- Page Hero Header -->
<section style="background: linear-gradient(rgba(10, 19, 41, 0.85), rgba(10, 19, 41, 0.9)), url('assets/images/hero.jpg') center/cover; padding: 50px 0 45px; text-align: center; color: #FFFFFF;">
  <div class="container">
    <span class="hero-badge"><i class="fa-solid fa-receipt"></i> Guest Portal</span>
    <h1 class="hero-title" style="margin-top: 15px; color: #FFFFFF; font-size: clamp(1.8rem, 4vw, 2.8rem);">My Luxury Reservations</h1>
    <p class="hero-subtitle" style="margin-bottom: 0; color: #E2E8F0; font-size: clamp(0.88rem, 2vw, 1.05rem);">Track your upcoming stays, download tax invoices, or manage reservations.</p>
  </div>
</section>

<section class="section-py" style="padding: 22px 0 35px;">
  <div class="container">

    <!-- Cancellation Alerts -->
    <?php if (!empty($cancel_msg)): ?>
      <div style="background: #F0FDF4; border: 1.5px solid #22C55E; color: #166534; padding: 14px 18px; border-radius: var(--radius-sm); font-size: 0.92rem; margin-bottom: 22px; display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-circle-check" style="color: #22C55E; font-size: 1.3rem;"></i>
        <div><?php echo $cancel_msg; ?></div>
      </div>
    <?php endif; ?>

    <?php if (!empty($cancel_error)): ?>
      <div style="background: #FEF2F2; border: 1.5px solid #EF4444; color: #991B1B; padding: 14px 18px; border-radius: var(--radius-sm); font-size: 0.92rem; margin-bottom: 22px; display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-circle-exclamation" style="color: #EF4444; font-size: 1.3rem;"></i>
        <div><?php echo htmlspecialchars($cancel_error); ?></div>
      </div>
    <?php endif; ?>

    <?php if (isset($_GET['verified'])): ?>
      <div style="background: rgba(34, 197, 94, 0.12); border: 1.5px solid #22C55E; color: #166534; padding: 14px 18px; border-radius: var(--radius-sm); font-size: 0.92rem; margin-bottom: 22px; display: flex; align-items: center; gap: 12px;">
        <i class="fa-solid fa-circle-check" style="color: #22C55E; font-size: 1.25rem;"></i>
        <div>
          <strong>Account Activated!</strong> Your guest email has been verified. Welcome to the HOTEL Guest Circle.
        </div>
      </div>
    <?php endif; ?>

    <!-- Search & Action Bar -->
    <div class="my-bookings-bar">
      <form method="GET" action="my-bookings.php" class="search-bookings-form">
        <div class="search-input-wrapper">
          <i class="fa-solid fa-magnifying-glass"></i>
          <input type="text" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search Booking ID, room...">
        </div>
        <button type="submit" class="btn btn-outline-dark btn-sm">Search</button>
        <?php if (!empty($search_query)): ?>
          <a href="my-bookings.php" class="btn btn-subtle btn-sm" style="color: var(--color-primary);">Reset</a>
        <?php endif; ?>
      </form>

      <div style="display: flex; gap: 8px; flex-wrap: wrap;">
        <a href="profile.php" class="btn btn-outline-dark btn-sm">
          <i class="fa-solid fa-user text-gold"></i> My Profile
        </a>
        <a href="book.php" class="btn btn-gold btn-sm my-bookings-new-btn">
          <i class="fa-solid fa-plus"></i> Reserve Another Stay
        </a>
      </div>
    </div>

    <!-- Bookings Listing -->
    <?php if (empty($bookings)): ?>
      <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: var(--radius-md); padding: 60px 20px; text-align: center;">
        <div style="font-size: 2.5rem; color: var(--color-gold); margin-bottom: 12px;"><i class="fa-regular fa-calendar-xmark"></i></div>
        <h3 style="font-family: var(--font-serif); color: var(--color-primary); font-size: 1.4rem; margin-bottom: 8px;">No Bookings Found</h3>
        <p style="color: var(--color-text-muted); font-size: 0.92rem; max-width: 440px; margin: 0 auto 20px;">
          No reservation matching your query was found. Plan your peaceful retreat today with our best rate guarantee.
        </p>
        <a href="book.php" class="btn btn-gold">
          <i class="fa-solid fa-calendar-check"></i> Book A Room Now
        </a>
      </div>
    <?php else: ?>
      <div style="display: flex; flex-direction: column; gap: 18px;">
        <?php foreach ($bookings as $b): ?>
          <div class="booking-item-card" style="position: relative; border-radius: 12px; border: 1px solid #E2E8F0; box-shadow: 0 4px 16px rgba(0,0,0,0.04); background: #FFFFFF; padding: 22px;">
            
            <!-- Thumbnail -->
            <div class="booking-item-thumb">
              <img src="<?php echo htmlspecialchars($b['room_image']); ?>" alt="<?php echo htmlspecialchars($b['room_title']); ?>" style="width: 140px; height: 110px; object-fit: cover; border-radius: 8px;">
            </div>

            <!-- Details -->
            <div style="flex: 1;">
              <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
                <?php 
                $st = $b['status'];
                if ($st === 'Confirmed'): ?>
                  <span class="status-badge" style="background: #DCFCE7; color: #166534; border: 1px solid #86EFAC;">
                    <i class="fa-solid fa-circle-check"></i> Confirmed
                  </span>
                <?php elseif ($st === 'Checked In'): ?>
                  <span class="status-badge" style="background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD;">
                    <i class="fa-solid fa-key"></i> Checked In
                  </span>
                <?php elseif ($st === 'Checked Out'): ?>
                  <span class="status-badge" style="background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1;">
                    <i class="fa-solid fa-check-double"></i> Checked Out
                  </span>
                <?php elseif ($st === 'Cancelled'): ?>
                  <span class="status-badge" style="background: #FFE4E6; color: #9F1239; border: 1px solid #FDA4AF;">
                    <i class="fa-solid fa-ban"></i> Cancelled
                  </span>
                <?php else: ?>
                  <span class="status-badge status-pending">
                    <i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($st); ?>
                  </span>
                <?php endif; ?>

                <span style="font-family: monospace; font-size: 0.82rem; color: var(--color-gold); font-weight: 700; background: rgba(197, 155, 39, 0.1); padding: 3px 8px; border-radius: 4px;">
                  Ref: <?php echo htmlspecialchars($b['booking_id']); ?>
                </span>
              </div>

              <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--color-primary); margin-bottom: 8px;">
                <?php echo htmlspecialchars($b['room_title']); ?>
              </h3>

              <div style="display: flex; flex-wrap: wrap; gap: 16px; font-size: 0.84rem; color: var(--color-text-muted); margin-bottom: 10px;">
                <span><i class="fa-regular fa-calendar text-gold"></i> <strong>Check-in:</strong> <?php echo htmlspecialchars($b['checkin']); ?></span>
                <span><i class="fa-regular fa-calendar-check text-gold"></i> <strong>Check-out:</strong> <?php echo htmlspecialchars($b['checkout']); ?></span>
                <span><i class="fa-solid fa-moon text-gold"></i> <?php echo $b['nights'] ?? 1; ?> Night<?php echo ($b['nights'] ?? 1) > 1 ? 's' : ''; ?></span>
                <span><i class="fa-solid fa-users text-gold"></i> <?php echo htmlspecialchars($b['adults'] ?? 2); ?> Adults<?php echo !empty($b['children']) ? ', ' . htmlspecialchars($b['children']) . ' Child' : ''; ?></span>
              </div>

              <!-- Cancellation Note if Cancelled -->
              <?php if ($st === 'Cancelled' && !empty($b['cancellation_reason'])): ?>
                <div style="background: #FFF1F2; border-left: 3px solid #F43F5E; padding: 6px 12px; font-size: 0.82rem; color: #9F1239; margin-top: 6px; border-radius: 4px;">
                  <strong>Reason:</strong> <?php echo htmlspecialchars($b['cancellation_reason']); ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Price & Actions -->
            <div style="text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; min-width: 200px;">
              <div>
                <span style="font-size: 0.75rem; color: var(--color-text-muted); display: block;">Total Paid / Tariff</span>
                <span style="font-size: 1.3rem; font-weight: 800; color: var(--color-primary);">₹<?php echo number_format($b['total']); ?></span>
              </div>

              <!-- Action Buttons Row -->
              <div style="display: flex; gap: 8px; flex-wrap: wrap; justify-content: flex-end;">
                <!-- Printable Invoice Button -->
                <a href="invoice.php?id=<?php echo urlencode($b['booking_id']); ?>" target="_blank" class="btn btn-outline-dark btn-sm" title="View & Print Official Invoice">
                  <i class="fa-solid fa-file-invoice text-gold"></i> Invoice
                </a>

                <!-- WhatsApp Support -->
                <a href="https://wa.me/919000000000?text=Hello%2C%20inquiry%20regarding%20booking%20<?php echo $b['booking_id']; ?>" target="_blank" class="btn btn-whatsapp btn-sm" title="WhatsApp Concierge">
                  <i class="fa-brands fa-whatsapp"></i> Chat
                </a>

                <!-- Cancellation Button (Active bookings only) -->
                <?php if (in_array($b['status'], ['Confirmed', 'Pending'])): ?>
                  <button type="button" class="btn btn-sm" style="background: #FFF1F2; color: #BE123C; border: 1px solid #FECDD3;" onclick="openCancelModal('<?php echo htmlspecialchars($b['booking_id']); ?>', '<?php echo htmlspecialchars(addslashes($b['room_title'])); ?>')">
                    <i class="fa-solid fa-xmark"></i> Cancel Stay
                  </button>
                <?php endif; ?>
              </div>
            </div>

          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

  </div>
</section>

<!-- Cancellation Modal -->
<div id="cancelModal" style="display: none; position: fixed; inset: 0; background: rgba(10, 19, 41, 0.7); z-index: 9999; backdrop-filter: blur(4px); align-items: center; justify-content: center; padding: 20px;">
  <div style="background: #FFFFFF; border-radius: 14px; max-width: 500px; width: 100%; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); position: relative; border: 1px solid #E2E8F0;">
    
    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px;">
      <div style="display: flex; align-items: center; gap: 10px;">
        <div style="width: 40px; height: 40px; border-radius: 50%; background: #FFE4E6; color: #BE123C; display: flex; align-items: center; justify-content: center; font-size: 1.2rem;">
          <i class="fa-solid fa-calendar-xmark"></i>
        </div>
        <div>
          <h3 style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--color-primary); margin: 0;">Cancel Reservation</h3>
          <span id="modalBookingRef" style="font-size: 0.8rem; font-family: monospace; color: var(--color-gold); font-weight: 700;"></span>
        </div>
      </div>
      <button type="button" onclick="closeCancelModal()" style="background: none; border: none; font-size: 1.2rem; color: #94A3B8; cursor: pointer;">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>

    <p style="font-size: 0.88rem; color: var(--color-text-muted); margin-bottom: 18px;">
      Are you sure you want to cancel your stay for <strong id="modalRoomTitle" style="color: var(--color-primary);"></strong>? Please select a reason for cancellation:
    </p>

    <form method="POST" action="my-bookings.php">
      <input type="hidden" name="action" value="cancel_booking">
      <input type="hidden" name="booking_id" id="formBookingId" value="">

      <div style="margin-bottom: 14px;">
        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">Select Reason *</label>
        <select name="reason_type" class="form-control" style="width: 100%; padding: 10px 12px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem; background: #FFF;" required>
          <option value="Change of travel plans / dates">Change of travel plans / dates</option>
          <option value="Personal / family emergency">Personal or family emergency</option>
          <option value="Transportation / flight delay or cancellation">Flight / train cancelled or delayed</option>
          <option value="Found alternative hotel accommodation">Found alternate hotel accommodation</option>
          <option value="Medical / health reasons">Medical / health reasons</option>
          <option value="Work / business trip cancelled">Work or business meeting cancelled</option>
          <option value="Other reason">Other reason</option>
        </select>
      </div>

      <div style="margin-bottom: 18px;">
        <label style="display: block; font-size: 0.82rem; font-weight: 700; color: var(--color-primary); margin-bottom: 6px;">Additional Details (Optional)</label>
        <textarea name="reason_details" rows="2" class="form-control" style="width: 100%; padding: 10px 12px; border: 1.5px solid #CBD5E1; border-radius: 8px; font-size: 0.88rem;" placeholder="Any feedback or rescheduling request..."></textarea>
      </div>

      <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 8px; padding: 10px 14px; font-size: 0.78rem; color: var(--color-text-muted); margin-bottom: 20px;">
        <i class="fa-solid fa-shield-halved text-gold"></i> <strong>Zero Cancellation Fee:</strong> Your cancellation will be processed immediately and a confirmation email will be sent.
      </div>

      <div style="display: flex; gap: 10px; justify-content: flex-end;">
        <button type="button" onclick="closeCancelModal()" class="btn btn-outline-dark btn-sm">Keep Reservation</button>
        <button type="submit" class="btn btn-sm" style="background: #BE123C; color: #FFF; border: none; font-weight: 700; padding: 8px 18px;">
          <i class="fa-solid fa-trash-can"></i> Confirm Cancellation
        </button>
      </div>
    </form>

  </div>
</div>

<script>
function openCancelModal(bookingId, roomTitle) {
  document.getElementById('modalBookingRef').innerText = 'Ref: ' + bookingId;
  document.getElementById('modalRoomTitle').innerText = roomTitle;
  document.getElementById('formBookingId').value = bookingId;
  var modal = document.getElementById('cancelModal');
  modal.style.display = 'flex';
}
function closeCancelModal() {
  document.getElementById('cancelModal').style.display = 'none';
}
window.onclick = function(event) {
  var modal = document.getElementById('cancelModal');
  if (event.target === modal) {
    modal.style.display = 'none';
  }
}
</script>

<?php include 'includes/footer.php'; ?>
