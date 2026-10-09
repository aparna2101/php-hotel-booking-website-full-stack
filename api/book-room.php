<?php
/**
 * API: Room Booking Handler
 * -------------------------
 * Handles modal quick-book submissions, saves to MySQL database,
 * dispatches luxury guest email voucher & admin notification, and returns JSON.
 */

header('Content-Type: application/json');
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../config/database.php';
require_once '../includes/mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Use POST.']);
    exit;
}

// Form Data sanitize
$room_title = trim($_POST['room'] ?? 'Oceanfront Master Room');
$checkin    = trim($_POST['checkin'] ?? date('Y-m-d'));
$checkout   = trim($_POST['checkout'] ?? date('Y-m-d', strtotime('+1 day')));
$guest_name = trim($_POST['guest_name'] ?? '');
$phone      = trim($_POST['phone'] ?? '');
$email      = trim($_POST['email'] ?? '');
$guests     = trim($_POST['guests'] ?? '2 Adults');
$notes      = trim($_POST['notes'] ?? '');

// Validation
if (empty($guest_name) || empty($phone) || empty($email) || empty($checkin) || empty($checkout)) {
    echo json_encode(['success' => false, 'message' => 'Please fill all required fields (Name, Phone, Email, Dates).']);
    exit;
}

// Calculate nights and price
$cin = new DateTime($checkin);
$cout = new DateTime($checkout);
$nights = max(1, $cout->diff($cin)->days);

// Default price lookups
$room_price = 4999;
if (stripos($room_title, 'Penthouse') !== false) {
    $room_price = 12999;
} elseif (stripos($room_title, 'Villa') !== false) {
    $room_price = 7499;
}

$subtotal = $nights * $room_price;
$taxes = round($subtotal * 0.12);
$total_amount = $subtotal + $taxes;

$booking_id = "BK-" . strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
$user_id = $_SESSION['user_id'] ?? null;

// Insert into Database
if (isset($pdo) && $db_connected) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO `bookings` 
            (`booking_id`, `user_id`, `guest_name`, `phone`, `email`, `room_title`, `checkin`, `checkout`, `guests`, `nights`, `total_amount`, `status`, `notes`) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Confirmed', ?)
        ");
        $stmt->execute([
            $booking_id, $user_id, $guest_name, $phone, $email, 
            $room_title, $checkin, $checkout, $guests, $nights, $total_amount, $notes
        ]);
    } catch (Exception $e) {
        error_log("API Booking Error: " . $e->getMessage());
    }
}

// Store in session bookings for instant display on my-bookings.php
$room_image = 'assets/images/room1.jpg';
if (stripos($room_title, 'Penthouse') !== false) $room_image = 'assets/images/room2.jpg';
if (stripos($room_title, 'Villa') !== false) $room_image = 'assets/images/room3.jpg';

$booking_record = [
    'booking_id' => $booking_id,
    'room_title' => $room_title,
    'room_image' => $room_image,
    'checkin'    => $checkin,
    'checkout'   => $checkout,
    'nights'     => $nights,
    'guests'     => $guests,
    'guest_name' => $guest_name,
    'phone'      => $phone,
    'email'      => $email,
    'total'      => $total_amount,
    'status'     => 'Confirmed',
    'created_at' => date('d M Y, h:i A')
];

if (!isset($_SESSION['my_bookings'])) {
    $_SESSION['my_bookings'] = [];
}
array_unshift($_SESSION['my_bookings'], $booking_record);

if (!isset($_SESSION['user_logged_in'])) {
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_name'] = $guest_name;
    $_SESSION['user_email'] = $email;
    $_SESSION['user_phone'] = $phone;
}

// Send Guest Voucher Email
$guest_voucher_html = '
  <p>Dear <strong>' . htmlspecialchars($guest_name) . '</strong>,</p>
  <p>Your reservation has been confirmed. Here are your booking details:</p>
  
  <table style="width: 100%; border-collapse: collapse; margin: 20px 0; background: #F8FAFC; border-radius: 8px; border: 1px solid #E2E8F0;">
    <tr style="border-bottom: 1px solid #E2E8F0;">
      <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Booking ID:</td>
      <td style="padding: 12px 16px; font-weight: 700; color: #C59B27; font-family: monospace; font-size: 15px;">' . $booking_id . '</td>
    </tr>
    <tr style="border-bottom: 1px solid #E2E8F0;">
      <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Accommodation:</td>
      <td style="padding: 12px 16px; font-weight: 600; color: #0A1329;">' . htmlspecialchars($room_title) . '</td>
    </tr>
    <tr style="border-bottom: 1px solid #E2E8F0;">
      <td style="padding: 12px 16px; color: #64748B; font-size: 13px;">Stay Dates:</td>
      <td style="padding: 12px 16px; font-weight: 600; color: #0A1329;">' . htmlspecialchars($checkin) . ' &rarr; ' . htmlspecialchars($checkout) . ' (' . $nights . ' Night' . ($nights > 1 ? 's' : '') . ')</td>
    </tr>
    <tr>
      <td style="padding: 14px 16px; color: #64748B; font-size: 13px; font-weight: 700;">Total Amount:</td>
      <td style="padding: 14px 16px; font-weight: 700; color: #0A1329; font-size: 18px;">₹' . number_format($total_amount) . '</td>
    </tr>
  </table>
';
$guest_html = get_luxury_email_template("Reservation Voucher: {$booking_id}", "Booking Confirmation", $guest_voucher_html);
send_hotel_smtp_mail($email, $guest_name, "Reservation Confirmed: [{$booking_id}] - {$room_title}", $guest_html);

// Send Admin Alert Email
if (!empty($admin_email)) {
    $admin_alert_html = '
      <p><strong>A new booking has been confirmed via the quick modal:</strong></p>
      <ul style="color: #334155; line-height: 1.8;">
        <li><strong>Booking ID:</strong> <span style="font-family: monospace; color: #C59B27; font-weight: 700;">' . $booking_id . '</span></li>
        <li><strong>Guest:</strong> ' . htmlspecialchars($guest_name) . ' (' . htmlspecialchars($phone) . ', ' . htmlspecialchars($email) . ')</li>
        <li><strong>Room:</strong> ' . htmlspecialchars($room_title) . '</li>
        <li><strong>Dates:</strong> ' . htmlspecialchars($checkin) . ' to ' . htmlspecialchars($checkout) . ' (' . $nights . ' Nights)</li>
        <li><strong>Total:</strong> ₹' . number_format($total_amount) . '</li>
      </ul>
    ';
    $admin_template = get_luxury_email_template("New Booking: {$booking_id}", "Admin Alert", $admin_alert_html);
    send_hotel_smtp_mail($admin_email, "Hotel Management", "[NEW RESERVATION] {$booking_id} - {$guest_name}", $admin_template);
}

echo json_encode([
    'success' => true,
    'booking_id' => $booking_id,
    'message' => "Thank you, {$guest_name}! Your booking ({$booking_id}) has been confirmed and sent to your email."
]);
exit;
?>
