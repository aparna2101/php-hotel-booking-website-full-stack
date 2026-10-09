<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/mailer.php';

// MANDATORY AUTH CHECK: User must be registered & logged in before booking
if (!is_user_logged_in()) {
    $current_url = 'book.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $current_url .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: login.php?msg=login_required&redirect=" . urlencode($current_url));
    exit;
}

$current_user = get_logged_user();

$rooms = $sample_rooms;
if (isset($pdo) && $db_connected) {
    try {
        $stmt = $pdo->query("SELECT * FROM rooms WHERE status != 'Maintenance' ORDER BY price ASC");
        $db_rooms = $stmt->fetchAll();
        if (!empty($db_rooms)) {
            $rooms = $db_rooms;
        }
    } catch (Exception $e) {}
}

// Preselected room from query param
$selected_room_id = isset($_GET['room_id']) ? intval($_GET['room_id']) : ($rooms[0]['id'] ?? 1);
$selected_room_name = isset($_GET['room']) ? trim($_GET['room']) : '';

if (!empty($selected_room_name)) {
    foreach ($rooms as $r) {
        if (strcasecmp($r['title'], $selected_room_name) === 0) {
            $selected_room_id = $r['id'];
            break;
        }
    }
}

$success_booking = null;

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id       = intval($_POST['room_id'] ?? 1);
    $checkin       = trim($_POST['checkin'] ?? '');
    $checkout      = trim($_POST['checkout'] ?? '');
    $adults        = max(1, intval($_POST['adults'] ?? 2));
    $children      = max(0, intval($_POST['children'] ?? 0));
    $guests        = "{$adults} Adult" . ($adults > 1 ? "s" : "") . ($children > 0 ? ", {$children} Child" . ($children > 1 ? "ren" : "") : "");
    $arrival_time  = trim($_POST['arrival_time'] ?? '12:00 PM - 02:00 PM');

    $guest_name    = trim($_POST['guest_name'] ?? ($current_user['name'] ?? ''));
    $phone         = trim($_POST['phone'] ?? ($current_user['phone'] ?? ''));
    $email         = trim($_POST['email'] ?? ($current_user['email'] ?? ''));
    $id_type       = trim($_POST['id_type'] ?? 'Aadhaar Card');
    $id_number     = trim($_POST['id_number'] ?? '');
    $city          = trim($_POST['city'] ?? '');
    $state         = trim($_POST['state'] ?? '');
    $country       = trim($_POST['country'] ?? 'India');

    $visit_purpose = trim($_POST['visit_purpose'] ?? 'Vacation / Leisure');
    $meal_pref     = trim($_POST['meal_pref'] ?? 'No Preference');
    $notes         = trim($_POST['notes'] ?? '');

    // Find room details
    $booked_room = $rooms[0];
    foreach ($rooms as $r) {
        if ($r['id'] === $room_id) {
            $booked_room = $r;
            break;
        }
    }

    // Calculate nights
    $cin = new DateTime($checkin);
    $cout = new DateTime($checkout);
    $nights = max(1, $cout->diff($cin)->days);
    $subtotal = $nights * $booked_room['price'];
    $taxes = round($subtotal * 0.12);
    $total_amount = $subtotal + $taxes;

    $booking_id = "BK-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
    $user_id = $_SESSION['user_id'] ?? null;

    // 1. Insert into Real Database
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO `bookings` 
                (`booking_id`, `user_id`, `guest_name`, `phone`, `email`, `room_title`, `checkin`, `checkout`, `guests`, `adults`, `children`, `id_type`, `id_number`, `city`, `state`, `country`, `arrival_time`, `visit_purpose`, `meal_pref`, `nights`, `total_amount`, `status`, `notes`) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?)
            ");
            $stmt->execute([
                $booking_id, $user_id, $guest_name, $phone, $email, 
                $booked_room['title'], $checkin, $checkout, $guests, $adults, $children, 
                $id_type, $id_number, $city, $state, $country, $arrival_time, $visit_purpose, $meal_pref, 
                $nights, $total_amount, $notes
            ]);
        } catch (Exception $e) {
            error_log("Database Booking Error: " . $e->getMessage());
        }
    }

    $booking_record = [
        'booking_id'    => $booking_id,
        'room_title'    => $booked_room['title'],
        'room_image'    => $booked_room['image'],
        'checkin'       => $checkin,
        'checkout'      => $checkout,
        'nights'        => $nights,
        'guests'        => $guests,
        'adults'        => $adults,
        'children'      => $children,
        'guest_name'    => $guest_name,
        'phone'         => $phone,
        'email'         => $email,
        'id_type'       => $id_type,
        'id_number'     => $id_number,
        'city'          => $city,
        'state'         => $state,
        'country'       => $country,
        'arrival_time'  => $arrival_time,
        'visit_purpose' => $visit_purpose,
        'meal_pref'     => $meal_pref,
        'total'         => $total_amount,
        'status'        => 'Confirmed',
        'created_at'    => date('d M Y, h:i A')
    ];

    // Store in session bookings array for instant viewing in my-bookings.php
    if (!isset($_SESSION['my_bookings'])) {
        $_SESSION['my_bookings'] = [];
    }
    array_unshift($_SESSION['my_bookings'], $booking_record);

    // 2. Send Luxury Booking Confirmation Email to Guest
    $guest_voucher_html = '
      <p>Dear <strong>' . htmlspecialchars($guest_name) . '</strong>,</p>
      <p>Your luxury reservation has been confirmed with best rate guarantee. Below is your official hotel booking voucher:</p>
      
      <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background: #F8FAFC; border-radius: 8px; overflow: hidden; border: 1px solid #E2E8F0;">
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Booking Reference:</td>
          <td style="padding: 12px 16px; font-weight: 700; color: #C59B27; font-family: monospace; font-size: 15px;">' . $booking_id . '</td>
        </tr>
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Accommodations:</td>
          <td style="padding: 12px 16px; font-weight: 600; color: #0A1329;">' . htmlspecialchars($booked_room['title']) . '</td>
        </tr>
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Dates of Stay:</td>
          <td style="padding: 12px 16px; font-weight: 600; color: #0A1329;">' . htmlspecialchars($checkin) . ' &rarr; ' . htmlspecialchars($checkout) . ' (' . $nights . ' Night' . ($nights > 1 ? 's' : '') . ')</td>
        </tr>
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Occupancy:</td>
          <td style="padding: 12px 16px; color: #0A1329;">' . htmlspecialchars($guests) . '</td>
        </tr>
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Expected Arrival:</td>
          <td style="padding: 12px 16px; color: #0A1329;">' . htmlspecialchars($arrival_time) . '</td>
        </tr>
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Govt ID on Record:</td>
          <td style="padding: 12px 16px; color: #0A1329;">' . htmlspecialchars($id_type) . ' (' . htmlspecialchars($id_number) . ')</td>
        </tr>
        <tr style="border-bottom: 1px solid #E2E8F0;">
          <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Stay Purpose & Meal:</td>
          <td style="padding: 12px 16px; color: #0A1329;">' . htmlspecialchars($visit_purpose) . ' | ' . htmlspecialchars($meal_pref) . '</td>
        </tr>
        <tr>
          <td style="padding: 14px 16px; color: #64748B; font-size: 13px; font-weight: 700;">Total Amount:</td>
          <td style="padding: 14px 16px; font-weight: 700; color: #0A1329; font-size: 18px;">₹' . number_format($total_amount) . '</td>
        </tr>
      </table>

      <p style="font-size: 13px; color: #64748B;">Please carry the original <strong>' . htmlspecialchars($id_type) . '</strong> during physical check-in at the front desk reception. For any immediate assistance, call <strong>+91 90000 00000</strong>.</p>
    ';
    $guest_html = get_luxury_email_template("Reservation Voucher: {$booking_id}", "Booking Confirmation", $guest_voucher_html);
    send_hotel_smtp_mail($email, $guest_name, "Reservation Confirmed: [{$booking_id}] - {$booked_room['title']}", $guest_html);

    // 3. Send New Reservation Alert Email to Admin
    if (!empty($admin_email)) {
        $admin_alert_html = '
          <p><strong>A new luxury booking inquiry has been confirmed on the hotel portal:</strong></p>
          <ul style="color: #334155; line-height: 1.8;">
            <li><strong>Booking ID:</strong> <span style="font-family: monospace; color: #C59B27; font-weight: 700;">' . $booking_id . '</span></li>
            <li><strong>Guest Name:</strong> ' . htmlspecialchars($guest_name) . '</li>
            <li><strong>Guest Phone:</strong> ' . htmlspecialchars($phone) . '</li>
            <li><strong>Guest Email:</strong> ' . htmlspecialchars($email) . '</li>
            <li><strong>Govt ID:</strong> ' . htmlspecialchars($id_type) . ' (' . htmlspecialchars($id_number) . ')</li>
            <li><strong>City / State:</strong> ' . htmlspecialchars($city . ', ' . $state . ', ' . $country) . '</li>
            <li><strong>Room:</strong> ' . htmlspecialchars($booked_room['title']) . '</li>
            <li><strong>Dates:</strong> ' . htmlspecialchars($checkin) . ' to ' . htmlspecialchars($checkout) . ' (' . $nights . ' Nights)</li>
            <li><strong>Occupancy:</strong> ' . htmlspecialchars($guests) . ' | Arrival: ' . htmlspecialchars($arrival_time) . '</li>
            <li><strong>Purpose:</strong> ' . htmlspecialchars($visit_purpose) . ' | Diet: ' . htmlspecialchars($meal_pref) . '</li>
            <li><strong>Total Amount:</strong> ₹' . number_format($total_amount) . '</li>
            <li><strong>Special Requests:</strong> ' . htmlspecialchars(!empty($notes) ? $notes : 'None') . '</li>
          </ul>
        ';
        $admin_email_template = get_luxury_email_template("New Reservation: {$booking_id}", "Admin Alert", $admin_alert_html);
        send_hotel_smtp_mail($admin_email, "Hotel Management", "[NEW RESERVATION] {$booking_id} - {$guest_name}", $admin_email_template);
    }

    $success_booking = $booking_record;
}

$page_title = "Reserve Your Stay | HOTEL";
$current_page = "book";

include 'includes/header.php';
?>

<style>
.hotel-form-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
  margin-bottom: 14px;
}
.hotel-form-row-3 {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 14px;
  margin-bottom: 14px;
}
@media (max-width: 768px) {
  .hotel-form-row,
  .hotel-form-row-3 {
    grid-template-columns: 1fr !important;
    gap: 12px !important;
  }
}
</style>

<!-- Page Hero Header -->
<section style="background: linear-gradient(rgba(10, 19, 41, 0.88), rgba(10, 19, 41, 0.93)), url('assets/images/hero.jpg') center/cover; padding: 50px 0 45px; text-align: center; color: #FFFFFF;">
  <div class="container">
    <span class="hero-badge"><i class="fa-solid fa-crown"></i> Verified Guest Reservation</span>
    <h1 class="hero-title" style="margin-top: 15px; color: #FFFFFF; font-size: clamp(1.8rem, 4vw, 2.8rem);">Hotel Room Check-in & Booking</h1>
    <p class="hero-subtitle" style="margin-bottom: 0; color: #E2E8F0; font-size: clamp(0.88rem, 2vw, 1.05rem);">
      Welcome <strong><?php echo htmlspecialchars($current_user['name']); ?></strong>! Complete your official hotel check-in details with Govt ID verification.
    </p>
  </div>
</section>

<section class="section-py">
  <div class="container">

    <?php if ($success_booking): ?>
      <!-- Booking Confirmation Screen -->
      <div style="max-width: 720px; margin: 0 auto; background: #FFFFFF; border: 1.5px solid var(--color-gold); border-radius: var(--radius-md); padding: 40px; text-align: center; box-shadow: var(--shadow-lg);">
        <div style="width: 64px; height: 64px; background: rgba(37, 211, 102, 0.15); color: #25D366; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.8rem; margin: 0 auto 16px;">
          <i class="fa-solid fa-check"></i>
        </div>
        <span class="section-tag" style="color: #03543F; font-size: 0.76rem;">Reservation Confirmed</span>
        <h2 style="font-family: var(--font-serif); font-size: 2rem; color: var(--color-primary); margin-bottom: 8px;">Thank You, <?php echo htmlspecialchars($success_booking['guest_name']); ?>!</h2>
        <p style="color: var(--color-text-muted); font-size: 0.92rem; margin-bottom: 24px;">
          Your luxury reservation for <strong><?php echo htmlspecialchars($success_booking['room_title']); ?></strong> has been confirmed. A voucher copy has been dispatched to <strong><?php echo htmlspecialchars($success_booking['email']); ?></strong>.
        </p>

        <div style="background: var(--color-surface-muted); border-radius: var(--radius-sm); padding: 22px; text-align: left; margin-bottom: 28px;">
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.88rem;">
            <span style="color: var(--color-text-muted);">Booking Reference ID:</span>
            <strong style="color: var(--color-gold); font-family: monospace; font-size: 1.05rem;"><?php echo htmlspecialchars($success_booking['booking_id']); ?></strong>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.88rem;">
            <span style="color: var(--color-text-muted);">Dates of Stay:</span>
            <span><?php echo htmlspecialchars($success_booking['checkin']); ?> to <?php echo htmlspecialchars($success_booking['checkout']); ?> (<?php echo $success_booking['nights']; ?> Night<?php echo $success_booking['nights'] > 1 ? 's' : ''; ?>)</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.88rem;">
            <span style="color: var(--color-text-muted);">Occupancy & Arrival:</span>
            <span><?php echo htmlspecialchars($success_booking['guests']); ?> &bull; <?php echo htmlspecialchars($success_booking['arrival_time']); ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.88rem;">
            <span style="color: var(--color-text-muted);">Govt ID on Record:</span>
            <span><?php echo htmlspecialchars($success_booking['id_type']); ?> (<?php echo htmlspecialchars($success_booking['id_number']); ?>)</span>
          </div>
          <div style="display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 0.88rem;">
            <span style="color: var(--color-text-muted);">Stay Purpose / Meal:</span>
            <span><?php echo htmlspecialchars($success_booking['visit_purpose']); ?> / <?php echo htmlspecialchars($success_booking['meal_pref']); ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 1.05rem; font-weight: 700; border-top: 1px dashed #CBD5E1; padding-top: 12px; margin-top: 12px;">
            <span>Total Payable Amount:</span>
            <span class="text-gold">₹<?php echo number_format($success_booking['total']); ?></span>
          </div>
        </div>

        <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
          <a href="invoice.php?id=<?php echo urlencode($success_booking['booking_id']); ?>" target="_blank" class="btn btn-outline-dark">
            <i class="fa-solid fa-file-invoice text-gold"></i> Download Invoice
          </a>
          <a href="my-bookings.php" class="btn btn-gold">
            <i class="fa-solid fa-receipt"></i> View in My Bookings
          </a>
          <a href="https://wa.me/919000000000?text=Hi%2C%20I%20have%20completed%20booking%20reference%20<?php echo $success_booking['booking_id']; ?>" target="_blank" class="btn btn-whatsapp">
            <i class="fa-brands fa-whatsapp"></i> Chat with Front Desk
          </a>
        </div>
      </div>

    <?php else: ?>
      <!-- User Account Verification Banner -->
      <div style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: var(--radius-sm); padding: 12px 18px; margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; box-shadow: var(--shadow-xs);">
        <div style="display: flex; align-items: center; gap: 10px; font-size: 0.86rem; color: var(--color-primary);">
          <span style="width: 32px; height: 32px; border-radius: 50%; background: rgba(197, 155, 39, 0.15); color: var(--color-gold); display: flex; align-items: center; justify-content: center; font-weight: 700;">
            <i class="fa-solid fa-user-check"></i>
          </span>
          <div>
            Verified Guest: <strong><?php echo htmlspecialchars($current_user['name']); ?></strong> (<?php echo htmlspecialchars($current_user['email']); ?>)
          </div>
        </div>
        <a href="logout.php" style="font-size: 0.78rem; color: #EF4444; text-decoration: none;">
          <i class="fa-solid fa-arrow-right-from-bracket"></i> Sign out / Switch account
        </a>
      </div>

      <!-- Booking Form Layout -->
      <form method="POST" action="book.php" id="bookingPageForm">
        <div class="booking-layout">
          
          <!-- Left Column: Form Fields -->
          <div class="booking-form-card">
            
            <!-- Step 1: Room Selection -->
            <div style="margin-bottom: 30px;">
              <span class="section-tag">Step 1 of 4</span>
              <h3 style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--color-primary); margin-bottom: 14px;">Select Accommodation</h3>
              
              <div class="room-selector-grid" id="roomSelector">
                <?php foreach ($rooms as $r): ?>
                  <label style="cursor: pointer; position: relative;">
                    <input type="radio" name="room_id" value="<?php echo $r['id']; ?>" <?php echo $r['id'] === $selected_room_id ? 'checked' : ''; ?> style="position: absolute; opacity: 0;" class="room-radio-input" data-price="<?php echo $r['price']; ?>" data-title="<?php echo htmlspecialchars($r['title']); ?>" data-image="<?php echo htmlspecialchars($r['image']); ?>">
                    <div class="room-radio-box" style="border: 1.5px solid <?php echo $r['id'] === $selected_room_id ? 'var(--color-gold)' : '#CBD5E1'; ?>; border-radius: var(--radius-sm); overflow: hidden; background: #FFFFFF; transition: var(--transition);">
                      <img src="<?php echo htmlspecialchars($r['image']); ?>" alt="<?php echo htmlspecialchars($r['title']); ?>" onerror="this.src='assets/images/room1.jpg'" style="height: 110px; width: 100%; object-fit: cover;">
                      <div style="padding: 10px;">
                        <h4 style="font-size: 0.88rem; color: var(--color-primary); font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($r['title']); ?></h4>
                        <div style="font-size: 0.8rem; color: var(--color-gold); font-weight: 700;">₹<?php echo number_format($r['price']); ?> / night</div>
                      </div>
                    </div>
                  </label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Step 2: Dates, Occupancy & Expected Arrival Time -->
            <div style="margin-bottom: 30px; border-top: 1px solid #E2E8F0; padding-top: 24px;">
              <span class="section-tag">Step 2 of 4</span>
              <h3 style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--color-primary); margin-bottom: 14px;">Stay Dates & Timing</h3>
              
              <div class="hotel-form-row">
                <div class="form-group">
                  <label><i class="fa-regular fa-calendar text-gold"></i> Check-in Date *</label>
                  <input type="date" name="checkin" id="bookCheckin" required>
                </div>
                <div class="form-group">
                  <label><i class="fa-regular fa-calendar-check text-gold"></i> Check-out Date *</label>
                  <input type="date" name="checkout" id="bookCheckout" required>
                </div>
              </div>

              <div class="hotel-form-row-3">
                <div class="form-group">
                  <label><i class="fa-solid fa-user-group text-gold"></i> Adults *</label>
                  <select name="adults" id="bookAdults" required>
                    <option value="1">1 Adult</option>
                    <option value="2" selected>2 Adults</option>
                    <option value="3">3 Adults</option>
                    <option value="4">4 Adults</option>
                  </select>
                </div>
                <div class="form-group">
                  <label><i class="fa-solid fa-child text-gold"></i> Children</label>
                  <select name="children" id="bookChildren">
                    <option value="0" selected>0 Children</option>
                    <option value="1">1 Child</option>
                    <option value="2">2 Children</option>
                    <option value="3">3 Children</option>
                  </select>
                </div>
                <div class="form-group">
                  <label><i class="fa-regular fa-clock text-gold"></i> Estimated Arrival *</label>
                  <select name="arrival_time" required>
                    <option value="10:00 AM - 12:00 PM">10:00 AM - 12:00 PM (Early Arrival)</option>
                    <option value="12:00 PM - 02:00 PM" selected>12:00 PM - 02:00 PM (Standard Check-in)</option>
                    <option value="02:00 PM - 04:00 PM">02:00 PM - 04:00 PM</option>
                    <option value="04:00 PM - 08:00 PM">04:00 PM - 08:00 PM</option>
                    <option value="Late Arrival (After 08:00 PM)">Late Arrival (After 08:00 PM)</option>
                  </select>
                </div>
              </div>
            </div>

            <!-- Step 3: Government ID Proof & Check-in Verification (Mandatory for Hotels) -->
            <div style="margin-bottom: 30px; border-top: 1px solid #E2E8F0; padding-top: 24px;">
              <span class="section-tag">Step 3 of 4</span>
              <h3 style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--color-primary); margin-bottom: 6px;">Guest Identification (Govt ID Proof)</h3>
              <p style="color: var(--color-text-muted); font-size: 0.8rem; margin-bottom: 16px;">
                As per standard luxury hotel hospitality regulations, a valid government photo identity is required for guest registration.
              </p>
              
              <div class="hotel-form-row">
                <div class="form-group">
                  <label><i class="fa-solid fa-user text-gold"></i> Full Name (as on Govt ID) *</label>
                  <input type="text" name="guest_name" value="<?php echo htmlspecialchars($current_user['name'] ?? ''); ?>" placeholder="Full Name as per ID" required>
                </div>
                <div class="form-group">
                  <label><i class="fa-solid fa-phone text-gold"></i> Contact Phone *</label>
                  <input type="tel" name="phone" value="<?php echo htmlspecialchars($current_user['phone'] ?? ''); ?>" placeholder="+91 90000 00000" required>
                </div>
              </div>

              <div class="hotel-form-row">
                <div class="form-group">
                  <label><i class="fa-solid fa-id-card text-gold"></i> Government ID Type *</label>
                  <select name="id_type" required>
                    <option value="Aadhaar Card" selected>Aadhaar Card</option>
                    <option value="Passport">Passport</option>
                    <option value="Driving License">Driving License</option>
                    <option value="Voter ID Card">Voter ID Card</option>
                    <option value="PAN Card">PAN Card</option>
                  </select>
                </div>
                <div class="form-group">
                  <label><i class="fa-solid fa-barcode text-gold"></i> ID Document Number *</label>
                  <input type="text" name="id_number" placeholder="e.g. 12-digit Aadhaar / Passport No." required>
                </div>
              </div>

              <div class="hotel-form-row-3">
                <div class="form-group">
                  <label><i class="fa-solid fa-city text-gold"></i> City / Town *</label>
                  <input type="text" name="city" placeholder="e.g. Mumbai" required>
                </div>
                <div class="form-group">
                  <label><i class="fa-solid fa-map-location-dot text-gold"></i> State *</label>
                  <input type="text" name="state" placeholder="e.g. Maharashtra" required>
                </div>
                <div class="form-group">
                  <label><i class="fa-solid fa-globe text-gold"></i> Country *</label>
                  <input type="text" name="country" value="India" required>
                </div>
              </div>
            </div>

            <!-- Step 4: Stay Preferences & Special Requests -->
            <div style="border-top: 1px solid #E2E8F0; padding-top: 24px;">
              <span class="section-tag">Step 4 of 4</span>
              <h3 style="font-family: var(--font-serif); font-size: 1.35rem; color: var(--color-primary); margin-bottom: 14px;">Stay Preferences & Special Requests</h3>
              
              <div class="hotel-form-row">
                <div class="form-group">
                  <label><i class="fa-solid fa-compass text-gold"></i> Purpose of Visit</label>
                  <select name="visit_purpose">
                    <option value="Vacation / Leisure" selected>Vacation / Leisure</option>
                    <option value="Honeymoon / Couple Getaway">Honeymoon / Couple Getaway</option>
                    <option value="Family Holiday">Family Holiday</option>
                    <option value="Business / Corporate">Business / Corporate</option>
                    <option value="Wedding / Celebration">Wedding / Celebration</option>
                    <option value="Wellness & Spa Retreat">Wellness & Spa Retreat</option>
                  </select>
                </div>
                <div class="form-group">
                  <label><i class="fa-solid fa-utensils text-gold"></i> Meal / Dietary Preference</label>
                  <select name="meal_pref">
                    <option value="No Preference" selected>No Preference</option>
                    <option value="Pure Vegetarian">Pure Vegetarian</option>
                    <option value="Non-Vegetarian">Non-Vegetarian</option>
                    <option value="Jain Meal">Jain Food</option>
                    <option value="Vegan">Vegan</option>
                  </select>
                </div>
              </div>

              <div class="form-group">
                <label><i class="fa-solid fa-clipboard-list text-gold"></i> Special Requests (Optional)</label>
                <textarea name="notes" placeholder="e.g. Airport pickup required, high floor with ocean sunset view, quiet corner room, extra pillows, anniversary welcome cake..." style="width: 100%; min-height: 80px; padding: 10px 14px; border: 1.5px solid #CBD5E1; border-radius: var(--radius-xs); font-family: inherit; font-size: 0.88rem; box-sizing: border-box;"></textarea>
              </div>
            </div>

          </div>

          <!-- Right Column: Sticky Summary Box -->
          <div class="booking-summary-card">
            <div class="summary-header">
              <span class="section-tag">Summary</span>
              <h3 style="font-family: var(--font-serif); font-size: 1.25rem; color: var(--color-primary);">Reservation Details</h3>
            </div>

            <div class="summary-thumb">
              <img id="summaryRoomImg" src="assets/images/room1.jpg" alt="Selected Room" onerror="this.src='assets/images/room1.jpg'">
            </div>

            <h4 id="summaryRoomTitle" style="font-family: var(--font-serif); font-size: 1.1rem; color: var(--color-primary); margin-bottom: 12px;">Oceanfront Master Room</h4>

            <div class="price-row">
              <span>Rate per Night:</span>
              <span id="summaryNightRate" style="font-weight: 600;">₹4,999</span>
            </div>

            <div class="price-row">
              <span>Duration of Stay:</span>
              <span id="summaryNights">1 Night</span>
            </div>

            <div class="price-row">
              <span>Room Subtotal:</span>
              <span id="summarySubtotal">₹4,999</span>
            </div>

            <div class="price-row">
              <span>Luxury Taxes & Fees (12%):</span>
              <span id="summaryTaxes">₹600</span>
            </div>

            <div class="price-row total">
              <span>Total Estimated:</span>
              <span id="summaryGrandTotal" class="text-gold">₹5,599</span>
            </div>

            <button type="submit" class="btn btn-gold" style="width: 100%; margin-top: 18px; padding: 11px;">
              <i class="fa-solid fa-lock"></i> Confirm Luxury Booking
            </button>

            <div style="margin-top: 16px; padding-top: 12px; border-top: 1px solid #E2E8F0; font-size: 0.78rem; color: var(--color-text-muted); line-height: 1.5;">
              <div><i class="fa-solid fa-shield-halved text-gold"></i> Free cancellation up to 48 hours prior.</div>
              <div style="margin-top: 4px;"><i class="fa-solid fa-sparkles text-gold"></i> Complimentary welcome mocktail & Wi-Fi.</div>
              <div style="margin-top: 4px;"><i class="fa-solid fa-envelope-circle-check text-gold"></i> Instant voucher emailed to <?php echo htmlspecialchars($current_user['email']); ?>.</div>
            </div>

            <div style="margin-top: 14px; text-align: center;">
              <span style="font-size: 0.75rem; color: var(--color-text-muted);">Need help? </span>
              <a href="https://wa.me/919000000000?text=Hi%2C%20I%20need%20help%20with%20booking" target="_blank" style="color: #25D366; font-size: 0.8rem; font-weight: 600;">
                <i class="fa-brands fa-whatsapp"></i> Chat on WhatsApp
              </a>
            </div>
          </div>

        </div>
      </form>
    <?php endif; ?>

  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const radioInputs = document.querySelectorAll('.room-radio-input');
  const summaryImg = document.getElementById('summaryRoomImg');
  const summaryTitle = document.getElementById('summaryRoomTitle');
  const summaryNightRate = document.getElementById('summaryNightRate');
  const summaryNights = document.getElementById('summaryNights');
  const summarySubtotal = document.getElementById('summarySubtotal');
  const summaryTaxes = document.getElementById('summaryTaxes');
  const summaryGrandTotal = document.getElementById('summaryGrandTotal');
  const checkinInput = document.getElementById('bookCheckin');
  const checkoutInput = document.getElementById('bookCheckout');

  let currentPrice = 4999;

  // Set default dates (Today and Tomorrow)
  const today = new Date();
  const tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);

  if (checkinInput && checkoutInput) {
    checkinInput.min = today.toISOString().split('T')[0];
    checkinInput.value = today.toISOString().split('T')[0];

    checkoutInput.min = tomorrow.toISOString().split('T')[0];
    checkoutInput.value = tomorrow.toISOString().split('T')[0];

    checkinInput.addEventListener('change', () => {
      const nextDay = new Date(checkinInput.value);
      nextDay.setDate(nextDay.getDate() + 1);
      checkoutInput.min = nextDay.toISOString().split('T')[0];
      if (checkoutInput.value <= checkinInput.value) {
        checkoutInput.value = checkoutInput.min;
      }
      recalc();
    });

    checkoutInput.addEventListener('change', recalc);
  }

  function recalc() {
    if (!checkinInput || !checkoutInput) return;
    const cin = new Date(checkinInput.value);
    const cout = new Date(checkoutInput.value);
    const diffTime = Math.abs(cout - cin);
    const nights = Math.max(1, Math.ceil(diffTime / (1000 * 60 * 60 * 24)));

    const sub = nights * currentPrice;
    const tax = Math.round(sub * 0.12);
    const total = sub + tax;

    if (summaryNights) summaryNights.textContent = `${nights} Night${nights > 1 ? 's' : ''}`;
    if (summarySubtotal) summarySubtotal.textContent = `₹${sub.toLocaleString()}`;
    if (summaryTaxes) summaryTaxes.textContent = `₹${tax.toLocaleString()}`;
    if (summaryGrandTotal) summaryGrandTotal.textContent = `₹${total.toLocaleString()}`;
  }

  radioInputs.forEach(input => {
    input.addEventListener('change', () => {
      document.querySelectorAll('.room-radio-box').forEach(b => b.style.borderColor = '#CBD5E1');
      const box = input.parentElement.querySelector('.room-radio-box');
      if (box) box.style.borderColor = 'var(--color-gold)';

      currentPrice = parseInt(input.getAttribute('data-price')) || 4999;
      if (summaryImg) summaryImg.src = input.getAttribute('data-image');
      if (summaryTitle) summaryTitle.textContent = input.getAttribute('data-title');
      if (summaryNightRate) summaryNightRate.textContent = `₹${currentPrice.toLocaleString()}`;
      recalc();
    });

    if (input.checked) {
      input.dispatchEvent(new Event('change'));
    }
  });

  recalc();
});
</script>

<?php include 'includes/footer.php'; ?>
