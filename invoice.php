<?php
session_start();
require_once 'config/database.php';

// Authentication Check: User or Admin
$is_admin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$is_user  = !empty($_SESSION['user_email']);

if (!$is_admin && !$is_user) {
    header("Location: login.php?msg=login_required&redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit;
}

$booking_ref = trim($_GET['id'] ?? '');
if (empty($booking_ref)) {
    die("Invalid Booking Reference. <a href='my-bookings.php'>Return to Bookings</a>");
}

$booking = null;

// Fetch from Database
if ($db_connected && $pdo) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM `bookings` WHERE `booking_id` = ? OR `id` = ? LIMIT 1");
        $stmt->execute([$booking_ref, is_numeric($booking_ref) ? intval($booking_ref) : 0]);
        $booking = $stmt->fetch();
    } catch (Exception $e) {}
}

// Fallback to session bookings if DB is unavailable
if (!$booking && !empty($_SESSION['my_bookings'])) {
    foreach ($_SESSION['my_bookings'] as $sb) {
        if ($sb['booking_id'] === $booking_ref) {
            $booking = [
                'booking_id'          => $sb['booking_id'],
                'guest_name'          => $sb['guest_name'],
                'phone'               => $sb['phone'],
                'email'               => $sb['email'],
                'room_title'          => $sb['room_title'],
                'checkin'             => $sb['checkin'],
                'checkout'            => $sb['checkout'],
                'nights'              => $sb['nights'],
                'guests'              => $sb['guests'],
                'adults'              => $sb['adults'] ?? 2,
                'children'            => $sb['children'] ?? 0,
                'id_type'             => $sb['id_type'] ?? 'Aadhaar Card',
                'id_number'           => $sb['id_number'] ?? 'Verified at Check-in',
                'city'                => $sb['city'] ?? 'India',
                'state'               => $sb['state'] ?? '',
                'country'             => 'India',
                'arrival_time'        => $sb['arrival_time'] ?? '12:00 PM - 02:00 PM',
                'visit_purpose'       => $sb['visit_purpose'] ?? 'Vacation / Leisure',
                'meal_pref'           => $sb['meal_pref'] ?? 'No Preference',
                'total_amount'        => $sb['total'],
                'status'              => $sb['status'],
                'cancellation_reason' => $sb['cancellation_reason'] ?? null,
                'notes'               => '',
                'created_at'          => date('Y-m-d H:i:s')
            ];
            break;
        }
    }
}

if (!$booking) {
    die("Reservation not found. <a href='my-bookings.php'>Return to Bookings</a>");
}

// Security: If not admin, ensure the booking belongs to this user
if (!$is_admin && strtolower($booking['email']) !== strtolower($_SESSION['user_email'])) {
    die("Unauthorized access to this booking voucher. <a href='my-bookings.php'>Return</a>");
}

// Calculations
$total_amount = floatval($booking['total_amount']);
$tax_rate     = 0.12; // 12% GST
$base_amount  = round($total_amount / (1 + $tax_rate), 2);
$cgst         = round(($total_amount - $base_amount) / 2, 2);
$sgst         = round(($total_amount - $base_amount) / 2, 2);
$nights       = max(1, intval($booking['nights']));
$rate_per_night = round($base_amount / $nights, 2);

$back_url = $is_admin ? "admin/bookings.php" : "my-bookings.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #<?php echo htmlspecialchars($booking['booking_id']); ?> | HOTEL Luxury Resort</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <style>
    :root {
      --primary: #0A1329;
      --gold: #C59B27;
      --gold-dark: #A37E1A;
      --text-dark: #1E293B;
      --text-muted: #64748B;
      --border: #E2E8F0;
      --bg-light: #F8FAFC;
    }
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background: #F1F5F9;
      color: var(--text-dark);
      padding: 30px 15px;
      line-height: 1.5;
    }
    .no-print-bar {
      max-width: 860px;
      margin: 0 auto 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    .btn {
      display: inline-flex;
      align-items: center;
      gap: 8px;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.9rem;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s ease;
      border: none;
    }
    .btn-gold {
      background: linear-gradient(135deg, #D4AF37 0%, #AA820A 100%);
      color: #0A1329;
      box-shadow: 0 4px 12px rgba(197, 155, 39, 0.25);
    }
    .btn-gold:hover { transform: translateY(-1px); }
    .btn-outline {
      background: #FFFFFF;
      color: var(--primary);
      border: 1px solid #CBD5E1;
    }
    .btn-outline:hover { background: #F8FAFC; }

    /* Invoice Paper Container */
    .invoice-card {
      max-width: 860px;
      margin: 0 auto;
      background: #FFFFFF;
      border-radius: 12px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
      border: 1px solid var(--border);
      padding: 45px 50px;
      position: relative;
    }

    /* Header */
    .inv-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      border-bottom: 2px solid #F1F5F9;
      padding-bottom: 25px;
      margin-bottom: 30px;
    }
    .brand-logo {
      font-family: 'Cinzel', serif;
      font-size: 1.8rem;
      font-weight: 800;
      color: var(--primary);
      letter-spacing: 2px;
      display: flex;
      align-items: center;
      gap: 8px;
    }
    .brand-logo i { color: var(--gold); }
    .hotel-sub {
      font-size: 0.8rem;
      color: var(--gold-dark);
      letter-spacing: 3px;
      text-transform: uppercase;
      font-weight: 700;
      margin-top: 3px;
    }
    .hotel-meta {
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-top: 8px;
      line-height: 1.5;
    }
    .inv-title-box {
      text-align: right;
    }
    .inv-badge {
      display: inline-block;
      background: rgba(10, 19, 41, 0.06);
      color: var(--primary);
      padding: 4px 14px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 8px;
    }
    .inv-number {
      font-family: monospace;
      font-size: 1.3rem;
      font-weight: 800;
      color: var(--gold-dark);
    }
    .inv-date {
      font-size: 0.82rem;
      color: var(--text-muted);
      margin-top: 4px;
    }

    /* Status Stamp */
    .stamp {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 1px;
      padding: 4px 12px;
      border-radius: 6px;
      font-size: 0.82rem;
      margin-top: 6px;
    }
    .stamp-confirmed { background: #DCFCE7; color: #166534; border: 1px solid #86EFAC; }
    .stamp-checked-in { background: #DBEAFE; color: #1E40AF; border: 1px solid #93C5FD; }
    .stamp-checked-out { background: #F1F5F9; color: #475569; border: 1px solid #CBD5E1; }
    .stamp-cancelled { background: #FFE4E6; color: #9F1239; border: 1px solid #FDA4AF; }

    /* Grid for details */
    .details-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 30px;
      margin-bottom: 30px;
      background: var(--bg-light);
      padding: 24px;
      border-radius: 10px;
      border: 1px solid #E2E8F0;
    }
    .detail-col h4 {
      font-size: 0.78rem;
      color: var(--gold-dark);
      text-transform: uppercase;
      letter-spacing: 1.5px;
      margin-bottom: 12px;
      display: flex;
      align-items: center;
      gap: 6px;
    }
    .detail-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 6px;
      font-size: 0.88rem;
    }
    .detail-label { color: var(--text-muted); }
    .detail-val { font-weight: 600; color: var(--primary); text-align: right; }

    /* Table */
    .inv-table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 25px;
    }
    .inv-table th {
      background: var(--primary);
      color: #FFFFFF;
      text-align: left;
      padding: 12px 16px;
      font-size: 0.82rem;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .inv-table th:last-child { text-align: right; }
    .inv-table td {
      padding: 14px 16px;
      border-bottom: 1px solid #F1F5F9;
      font-size: 0.88rem;
    }
    .inv-table td:last-child { text-align: right; font-weight: 600; }

    /* Calculation Totals */
    .totals-box {
      margin-left: auto;
      max-width: 320px;
      margin-bottom: 30px;
    }
    .totals-row {
      display: flex;
      justify-content: space-between;
      padding: 6px 0;
      font-size: 0.88rem;
      color: var(--text-muted);
    }
    .totals-grand {
      border-top: 2px solid var(--primary);
      padding-top: 10px;
      margin-top: 6px;
      font-size: 1.15rem;
      font-weight: 800;
      color: var(--primary);
    }

    /* Policy & Footer */
    .policy-box {
      border-top: 1px dashed #CBD5E1;
      padding-top: 20px;
      margin-top: 20px;
      display: grid;
      grid-template-columns: 2fr 1fr;
      gap: 20px;
      align-items: flex-end;
    }
    .policy-notes h5 {
      font-size: 0.8rem;
      color: var(--primary);
      margin-bottom: 6px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .policy-notes ul {
      list-style: square;
      padding-left: 18px;
      font-size: 0.78rem;
      color: var(--text-muted);
      line-height: 1.6;
    }
    .sign-box {
      text-align: center;
      border-top: 1px solid #94A3B8;
      padding-top: 8px;
      font-size: 0.8rem;
      color: var(--text-muted);
    }

    /* Print media styling */
    @media print {
      body { background: #FFFFFF; padding: 0; }
      .no-print-bar { display: none !important; }
      .invoice-card { box-shadow: none; border: none; padding: 20px 0; max-width: 100%; }
    }
    @media (max-width: 768px) {
      .invoice-card { padding: 25px 20px; }
      .inv-header { flex-direction: column; gap: 20px; }
      .inv-title-box { text-align: left; }
      .details-grid { grid-template-columns: 1fr; gap: 20px; }
      .policy-box { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>

  <!-- Top Action Bar (Screen Only) -->
  <div class="no-print-bar">
    <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-outline">
      <i class="fa-solid fa-arrow-left"></i> Back to <?php echo $is_admin ? "Admin Console" : "My Bookings"; ?>
    </a>
    <div style="display: flex; gap: 10px;">
      <a href="https://wa.me/91<?php echo preg_replace('/\D/', '', $booking['phone']); ?>?text=Hello%20<?php echo urlencode($booking['guest_name']); ?>%2C%20here%20is%20your%20HOTEL%20booking%20voucher%20ref%20<?php echo $booking['booking_id']; ?>" target="_blank" class="btn btn-outline" style="color: #16A34A; border-color: #86EFAC;">
        <i class="fa-brands fa-whatsapp"></i> Share on WhatsApp
      </a>
      <button onclick="window.print()" class="btn btn-gold">
        <i class="fa-solid fa-print"></i> Print / Save PDF
      </button>
    </div>
  </div>

  <!-- Printable Invoice Document -->
  <div class="invoice-card">
    
    <!-- Header -->
    <div class="inv-header">
      <div>
        <div class="brand-logo"><i class="fa-solid fa-hotel"></i> HOTEL</div>
        <div class="hotel-sub">Luxury Oceanfront Resort & Spa</div>
        <div class="hotel-meta">
          Beachfront Promenade, Sector 4, Marine Bay<br>
          GSTIN: 07AAACH0123M1Z9 | Phone: +91 90000 00000<br>
          Email: contact@hotel.com | Web: www.hotel.com
        </div>
      </div>
      
      <div class="inv-title-box">
        <span class="inv-badge">Tax Invoice & Voucher</span>
        <div class="inv-number"><?php echo htmlspecialchars($booking['booking_id']); ?></div>
        <div class="inv-date">Issued: <?php echo date('d M Y, h:i A', strtotime($booking['created_at'])); ?></div>
        
        <div>
          <?php 
          $st = $booking['status'];
          if ($st === 'Confirmed'): ?>
            <span class="stamp stamp-confirmed"><i class="fa-solid fa-circle-check"></i> Confirmed & Guaranteed</span>
          <?php elseif ($st === 'Checked In'): ?>
            <span class="stamp stamp-checked-in"><i class="fa-solid fa-key"></i> Guest Checked In</span>
          <?php elseif ($st === 'Checked Out'): ?>
            <span class="stamp stamp-checked-out"><i class="fa-solid fa-check-double"></i> Checked Out</span>
          <?php elseif ($st === 'Cancelled'): ?>
            <span class="stamp stamp-cancelled"><i class="fa-solid fa-ban"></i> Booking Cancelled</span>
          <?php else: ?>
            <span class="stamp stamp-confirmed"><i class="fa-solid fa-clock"></i> <?php echo htmlspecialchars($st); ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Cancellation Banner if Cancelled -->
    <?php if ($st === 'Cancelled'): ?>
      <div style="background: #FFF1F2; border: 1.5px solid #FDA4AF; border-radius: 8px; padding: 14px 18px; margin-bottom: 25px; color: #9F1239; font-size: 0.9rem;">
        <i class="fa-solid fa-circle-exclamation" style="margin-right: 6px;"></i>
        <strong>Cancellation Notice:</strong> This reservation was cancelled on <?php echo !empty($booking['cancelled_at']) ? date('d M Y, h:i A', strtotime($booking['cancelled_at'])) : 'request'; ?>.
        <?php if (!empty($booking['cancellation_reason'])): ?>
          <br><strong>Reason Provided:</strong> <em>"<?php echo htmlspecialchars($booking['cancellation_reason']); ?>"</em>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Two-column Guest & Stay Details -->
    <div class="details-grid">
      <!-- Guest Info -->
      <div class="detail-col">
        <h4><i class="fa-solid fa-user-check"></i> Guest Information</h4>
        <div class="detail-row">
          <span class="detail-label">Primary Guest:</span>
          <span class="detail-val"><?php echo htmlspecialchars($booking['guest_name']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Mobile Number:</span>
          <span class="detail-val"><?php echo htmlspecialchars($booking['phone']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Email Address:</span>
          <span class="detail-val"><?php echo htmlspecialchars($booking['email']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Govt ID Proof:</span>
          <span class="detail-val"><?php echo htmlspecialchars($booking['id_type'] ?? 'ID Card'); ?> (<?php echo htmlspecialchars($booking['id_number'] ?? 'On Arrival'); ?>)</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Origin City / State:</span>
          <span class="detail-val"><?php echo htmlspecialchars(($booking['city'] ?? '') . ($booking['state'] ? ', ' . $booking['state'] : '')); ?></span>
        </div>
      </div>

      <!-- Stay Info -->
      <div class="detail-col">
        <h4><i class="fa-solid fa-calendar-days"></i> Stay & Room Itinerary</h4>
        <div class="detail-row">
          <span class="detail-label">Selected Suite:</span>
          <span class="detail-val"><?php echo htmlspecialchars($booking['room_title']); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Check-in Date:</span>
          <span class="detail-val"><?php echo date('d M Y (D)', strtotime($booking['checkin'])); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Check-out Date:</span>
          <span class="detail-val"><?php echo date('d M Y (D)', strtotime($booking['checkout'])); ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Duration of Stay:</span>
          <span class="detail-val"><?php echo $nights; ?> Night<?php echo $nights > 1 ? 's' : ''; ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Total Occupancy:</span>
          <span class="detail-val"><?php echo $booking['adults'] ?? 2; ?> Adults<?php echo !empty($booking['children']) ? ', ' . $booking['children'] . ' Child' : ''; ?></span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Arrival Window:</span>
          <span class="detail-val"><?php echo htmlspecialchars($booking['arrival_time'] ?? '12:00 PM - 02:00 PM'); ?></span>
        </div>
      </div>
    </div>

    <!-- Charges Breakdown Table -->
    <table class="inv-table">
      <thead>
        <tr>
          <th>Description & Inclusions</th>
          <th style="text-align: center;">Nights</th>
          <th style="text-align: center;">Rate / Night</th>
          <th>Amount (INR)</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>
            <strong><?php echo htmlspecialchars($booking['room_title']); ?></strong><br>
            <span style="font-size: 0.78rem; color: var(--text-muted);">
              Includes: Complimentary Artisan Breakfast, High-Speed Wi-Fi, Beach Cabana & Pool Access
            </span>
          </td>
          <td style="text-align: center;"><?php echo $nights; ?></td>
          <td style="text-align: center;">₹<?php echo number_format($rate_per_night, 2); ?></td>
          <td>₹<?php echo number_format($base_amount, 2); ?></td>
        </tr>
      </tbody>
    </table>

    <!-- Totals Calculation -->
    <div class="totals-box">
      <div class="totals-row">
        <span>Base Tariff:</span>
        <span style="font-weight: 600; color: var(--primary);">₹<?php echo number_format($base_amount, 2); ?></span>
      </div>
      <div class="totals-row">
        <span>CGST (6.0%):</span>
        <span>₹<?php echo number_format($cgst, 2); ?></span>
      </div>
      <div class="totals-row">
        <span>SGST (6.0%):</span>
        <span>₹<?php echo number_format($sgst, 2); ?></span>
      </div>
      <div class="totals-row totals-grand">
        <span>Net Total:</span>
        <span style="color: var(--gold-dark);">₹<?php echo number_format($total_amount, 2); ?></span>
      </div>
      <div style="font-size: 0.72rem; color: var(--text-muted); text-align: right; margin-top: 4px;">
        (All applicable taxes and service charges included)
      </div>
    </div>

    <!-- Stay Preferences & Special Requests -->
    <?php if (!empty($booking['visit_purpose']) || !empty($booking['meal_pref']) || !empty($booking['notes'])): ?>
      <div style="background: #F8FAFC; border-left: 3px solid var(--gold); padding: 12px 16px; margin-bottom: 25px; font-size: 0.84rem; border-radius: 4px;">
        <strong>Guest Preferences:</strong>
        Stay Purpose: <em><?php echo htmlspecialchars($booking['visit_purpose'] ?? 'Leisure'); ?></em> | 
        Dining: <em><?php echo htmlspecialchars($booking['meal_pref'] ?? 'Standard'); ?></em>
        <?php if (!empty($booking['notes'])): ?>
          <br><strong>Notes:</strong> <em><?php echo htmlspecialchars($booking['notes']); ?></em>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <!-- Hotel Policy & Sign-off -->
    <div class="policy-box">
      <div class="policy-notes">
        <h5>Important Check-In Guidelines</h5>
        <ul>
          <li><strong>Government Photo ID:</strong> Physical ID proof (Aadhaar / Passport / Voter ID) mandatory for all adult guests at the reception.</li>
          <li><strong>Timings:</strong> Standard Check-in begins at 12:00 PM. Standard Check-out is by 11:00 AM.</li>
          <li><strong>Payment & Keycard:</strong> Payment settlement and keycard issuance are conducted at the Front Desk counter.</li>
          <li><strong>Support:</strong> For airport shuttle, room service, or itinerary extensions, dial +91 90000 00000 or contact reception.</li>
        </ul>
      </div>

      <div>
        <div style="height: 45px;"></div>
        <div class="sign-box">
          <strong>Authorized Front Desk Officer</strong><br>
          HOTEL Luxury Resort
        </div>
      </div>
    </div>

  </div>

</body>
</html>
