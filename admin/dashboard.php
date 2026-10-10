<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

// Must have DB connected
if (!$db_connected || !$pdo) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;"><h2 style="color:#991B1B;">Database Connection Error</h2><p>MySQL is not running. Please start XAMPP and refresh.</p><a href="dashboard.php">Retry</a></div>');
}

// Handle Booking Status Update
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $b_id   = intval($_GET['id']);
    if ($action === 'confirm') {
        $stmt = $pdo->prepare("UPDATE `bookings` SET `status` = 'Confirmed' WHERE `id` = ?");
        $stmt->execute([$b_id]);
    } elseif ($action === 'check_in') {
        $stmt = $pdo->prepare("UPDATE `bookings` SET `status` = 'Checked In' WHERE `id` = ?");
        $stmt->execute([$b_id]);
    } elseif ($action === 'check_out') {
        $stmt = $pdo->prepare("UPDATE `bookings` SET `status` = 'Checked Out' WHERE `id` = ?");
        $stmt->execute([$b_id]);
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE `bookings` SET `status` = 'Cancelled', `cancellation_reason` = COALESCE(`cancellation_reason`, 'Cancelled by Hotel Front Desk Administrator'), `cancelled_at` = NOW() WHERE `id` = ?");
        $stmt->execute([$b_id]);
    } elseif ($action === 'delete_booking') {
        $stmt = $pdo->prepare("DELETE FROM `bookings` WHERE `id` = ?");
        $stmt->execute([$b_id]);
    }
    header("Location: dashboard.php?msg=updated");
    exit;
}

// Fetch Live Statistics from DB only
$total_bookings     = (int)$pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();
$confirmed_bookings = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Confirmed'")->fetchColumn();
$checked_in_count   = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Checked In'")->fetchColumn();
$total_revenue      = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM `bookings` WHERE `status` IN ('Confirmed', 'Checked In', 'Checked Out')")->fetchColumn();
$total_rooms        = (int)$pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
$pending_bookings   = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Pending'")->fetchColumn();

$stmt = $pdo->query("SELECT * FROM `bookings` ORDER BY `id` DESC LIMIT 30");
$recent_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard | HOTEL</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    .admin-layout {
      display: grid;
      grid-template-columns: 260px 1fr;
      min-height: 100vh;
    }
    .admin-sidebar {
      background: var(--color-primary-dark);
      color: #FFFFFF;
      padding: 30px 20px;
      border-right: 1px solid var(--color-border-gold);
    }
    .admin-nav li {
      margin-bottom: 8px;
    }
    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      color: #94A3B8;
      font-weight: 500;
      transition: all 0.2s ease;
    }
    .admin-nav a.active, .admin-nav a:hover {
      background: rgba(197, 155, 39, 0.15);
      color: var(--color-gold-light);
    }
    .admin-main {
      padding: 36px 40px;
      background: #F8FAFC;
    }
    .stat-card {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: var(--radius-md);
      padding: 24px;
      box-shadow: var(--shadow-sm);
    }
    .table-container {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: var(--radius-md);
      padding: 24px;
      box-shadow: var(--shadow-sm);
      overflow: hidden;
    }
    @media (max-width: 768px) {
      .table-container {
        padding: 16px 12px;
      }
    }
    .admin-table {
      width: 100%;
      min-width: 650px;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.88rem;
    }
    .admin-table th {
      padding: 12px 14px;
      border-bottom: 2px solid #E2E8F0;
      color: var(--color-text-muted);
      font-weight: 600;
      background: #F8FAFC;
    }
    .admin-table td {
      padding: 14px;
      border-bottom: 1px solid #EDF2F7;
      vertical-align: middle;
    }
  </style>
</head>
<body>

  <div class="admin-layout">
    <?php 
      $admin_active_page = 'dashboard';
      require_once 'nav.php'; 
    ?>

    <!-- Main Content -->
    <main class="admin-main">
      <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 24px; flex-wrap: wrap; gap: 12px;">
        <div style="min-width: 200px;">
          <h1 style="font-family: var(--font-serif); font-size: 1.6rem; color: var(--color-primary); margin-bottom: 3px; word-break: break-word;">Management Overview</h1>
          <p style="color: var(--color-text-muted); font-size: 0.82rem; margin: 0; word-break: break-all;">
            Logged in as <strong><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></strong> (<?php echo htmlspecialchars($_SESSION['admin_email'] ?? 'admin@hotel.com'); ?>)
          </p>
        </div>
        <div>
          <a href="../index.php" target="_blank" class="btn btn-outline-dark btn-sm" style="white-space: nowrap;">
            <i class="fa-solid fa-arrow-up-right-from-square"></i> Open Website
          </a>
        </div>
      </div>

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div style="background: rgba(34, 197, 94, 0.15); border: 1px solid #22C55E; color: #166534; padding: 10px 14px; border-radius: var(--radius-xs); font-size: 0.85rem; margin-bottom: 20px;">
          <i class="fa-solid fa-circle-check"></i> Reservation status successfully updated.
        </div>
      <?php endif; ?>

      <!-- Stats Grid (Responsive 4-col desktop, 2-col mobile, 0 overflow) -->
      <div class="admin-stats-grid">
        <a href="rooms.php" class="stat-card" style="text-decoration:none; display:block; cursor:pointer;">
          <div style="color: var(--color-gold); font-size: 1.3rem; margin-bottom: 6px;"><i class="fa-solid fa-bed"></i></div>
          <div class="stat-num" style="font-size: 1.7rem; font-weight: 700; color: var(--color-primary); line-height: 1.1;"><?php echo $total_rooms; ?></div>
          <div class="stat-label" style="color: var(--color-text-muted); font-size: 0.8rem; margin-top: 4px;">Total Rooms &rarr;</div>
        </a>

        <div class="stat-card">
          <div style="color: #25D366; font-size: 1.3rem; margin-bottom: 6px;"><i class="fa-solid fa-calendar-check"></i></div>
          <div class="stat-num" style="font-size: 1.7rem; font-weight: 700; color: var(--color-primary); line-height: 1.1;"><?php echo $confirmed_bookings; ?></div>
          <div class="stat-label" style="color: var(--color-text-muted); font-size: 0.8rem; margin-top: 4px;">Confirmed</div>
        </div>

        <div class="stat-card">
          <div style="color: #F59E0B; font-size: 1.3rem; margin-bottom: 6px;"><i class="fa-solid fa-clock"></i></div>
          <div class="stat-num" style="font-size: 1.7rem; font-weight: 700; color: var(--color-primary); line-height: 1.1;"><?php echo $pending_bookings; ?></div>
          <div class="stat-label" style="color: var(--color-text-muted); font-size: 0.8rem; margin-top: 4px;">Pending</div>
        </div>

        <div class="stat-card">
          <div style="color: #0E7490; font-size: 1.3rem; margin-bottom: 6px;"><i class="fa-solid fa-indian-rupee-sign"></i></div>
          <div class="stat-num" style="font-size: 1.7rem; font-weight: 700; color: var(--color-primary); line-height: 1.1;">&#8377;<?php echo number_format($total_revenue); ?></div>
          <div class="stat-label" style="color: var(--color-text-muted); font-size: 0.8rem; margin-top: 4px;">Revenue</div>
        </div>
      </div>

      <!-- Guest Reservations Table -->
      <div class="table-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 10px;">
          <div>
            <h3 style="font-family: var(--font-serif); font-size: 1.2rem; color: var(--color-primary); margin-bottom: 2px;">Recent Guest Reservations</h3>
            <p style="color: var(--color-text-muted); font-size: 0.8rem; margin: 0;">Live reservations synced from the database</p>
          </div>
          <a href="bookings.php" class="btn btn-gold btn-sm">
            <i class="fa-solid fa-magnifying-glass"></i> All Bookings & Search
          </a>
        </div>

        <div class="table-swipe-hint">
          <i class="fa-solid fa-arrows-left-right"></i> Swipe table horizontally to view full booking details & actions
        </div>

        <div class="admin-table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Ref #</th>
                <th>Guest Details</th>
                <th>Room</th>
                <th>Dates</th>
                <th>Amount</th>
                <th>Status</th>
                <th style="text-align: right;">Action</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($recent_bookings)): ?>
              <tr>
                <td colspan="7" style="text-align:center; padding: 50px 20px; color: var(--color-text-muted);">
                  <i class="fa-regular fa-calendar-xmark" style="font-size:2rem; display:block; margin-bottom:10px; color:var(--color-gold);"></i>
                  No bookings received yet. They will appear here when guests make reservations.
                </td>
              </tr>
            <?php else: ?>
            <?php foreach ($recent_bookings as $b): ?>
              <tr>
                <td style="font-family: monospace; font-weight: 700; color: var(--color-gold);">
                  <?php echo htmlspecialchars($b['booking_id'] ?? ('BK-' . $b['id'])); ?>
                </td>
                <td>
                  <strong style="color: var(--color-primary); font-size: 0.95rem;"><?php echo htmlspecialchars($b['guest_name']); ?></strong><br>
                  <span style="font-size: 0.76rem; color: var(--color-text-muted); line-height: 1.5; display: inline-block; margin-top: 2px;">
                    <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($b['phone']); ?> &bull; 
                    <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($b['email']); ?>
                  </span>
                  <?php if (!empty($b['id_number'])): ?>
                    <div style="font-size: 0.74rem; color: #0284C7; margin-top: 2px; font-weight: 500;">
                      <i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($b['id_type'] ?? 'ID'); ?>: <strong><?php echo htmlspecialchars($b['id_number']); ?></strong>
                    </div>
                  <?php endif; ?>
                  <?php if (!empty($b['city'])): ?>
                    <div style="font-size: 0.72rem; color: #64748B;">
                      <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($b['city'] . (!empty($b['state']) ? ', ' . $b['state'] : '')); ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>
                  <div style="font-weight: 600; color: var(--color-primary);"><?php echo htmlspecialchars($b['room_title']); ?></div>
                  <div style="font-size: 0.74rem; color: var(--color-gold); font-weight: 600; margin-top: 2px;">
                    <i class="fa-solid fa-user-group"></i> <?php echo htmlspecialchars($b['guests']); ?>
                    <?php if (!empty($b['visit_purpose'])): ?>
                      &bull; <span style="color: #64748B; font-weight: 400;"><?php echo htmlspecialchars($b['visit_purpose']); ?></span>
                    <?php endif; ?>
                  </div>
                  <?php if (!empty($b['notes'])): ?>
                    <div style="font-size: 0.72rem; color: #475569; font-style: italic; margin-top: 2px;">
                      "<?php echo htmlspecialchars($b['notes']); ?>"
                    </div>
                  <?php endif; ?>
                </td>
                <td style="font-size: 0.8rem; color: var(--color-text-muted); line-height: 1.5;">
                  <strong><?php echo htmlspecialchars($b['checkin']); ?></strong> &rarr; <strong><?php echo htmlspecialchars($b['checkout']); ?></strong>
                  <div style="font-size: 0.73rem; color: #64748B; margin-top: 2px;">
                    <?php echo $b['nights'] ?? 1; ?> Night<?php echo ($b['nights'] ?? 1) > 1 ? 's' : ''; ?>
                    <?php if (!empty($b['arrival_time'])): ?>
                      &bull; In: <?php echo htmlspecialchars($b['arrival_time']); ?>
                    <?php endif; ?>
                  </div>
                </td>
                <td style="font-weight: 700; color: var(--color-primary); font-size: 0.95rem;">
                  ₹<?php echo number_format($b['total_amount'] ?? 0); ?>
                </td>
                <td>
                  <?php 
                    $st = $b['status'] ?? 'Confirmed';
                    if ($st === 'Confirmed') {
                        echo '<span class="status-badge" style="background:#DCFCE7;color:#166534;border:1px solid #86EFAC;"><i class="fa-solid fa-circle-check"></i> Confirmed</span>';
                    } elseif ($st === 'Checked In') {
                        echo '<span class="status-badge" style="background:#DBEAFE;color:#1E40AF;border:1px solid #93C5FD;"><i class="fa-solid fa-key"></i> Checked In</span>';
                    } elseif ($st === 'Checked Out') {
                        echo '<span class="status-badge" style="background:#F1F5F9;color:#475569;border:1px solid #CBD5E1;"><i class="fa-solid fa-check-double"></i> Checked Out</span>';
                    } elseif ($st === 'Cancelled') {
                        echo '<span class="status-badge" style="background:#FFE4E6;color:#9F1239;border:1px solid #FDA4AF;"><i class="fa-solid fa-ban"></i> Cancelled</span>';
                    } else {
                        echo '<span class="status-badge status-pending"><i class="fa-solid fa-clock"></i> ' . htmlspecialchars($st) . '</span>';
                    }
                  ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                  <!-- Invoice View -->
                  <a href="../invoice.php?id=<?php echo urlencode($b['booking_id']); ?>" target="_blank" class="btn btn-outline-dark btn-sm" style="padding: 4px 7px; font-size: 0.75rem;" title="View & Print Official Invoice">
                    <i class="fa-solid fa-file-invoice text-gold"></i>
                  </a>

                  <!-- Check In Action -->
                  <?php if ($st === 'Confirmed'): ?>
                    <a href="dashboard.php?action=check_in&id=<?php echo $b['id']; ?>" class="btn btn-sm" style="background: #2563EB; color: #FFF; padding: 4px 8px; font-size: 0.75rem; border: none;" title="Mark Checked In">
                      <i class="fa-solid fa-key"></i>
                    </a>
                  <?php endif; ?>

                  <!-- Check Out Action -->
                  <?php if ($st === 'Checked In'): ?>
                    <a href="dashboard.php?action=check_out&id=<?php echo $b['id']; ?>" class="btn btn-sm" style="background: #059669; color: #FFF; padding: 4px 8px; font-size: 0.75rem; border: none;" title="Mark Checked Out">
                      <i class="fa-solid fa-check-double"></i>
                    </a>
                  <?php endif; ?>

                  <!-- Re-confirm if pending or cancelled -->
                  <?php if (in_array($st, ['Pending', 'Cancelled'])): ?>
                    <a href="dashboard.php?action=confirm&id=<?php echo $b['id']; ?>" class="btn btn-outline-dark btn-sm" style="padding: 4px 7px; font-size: 0.75rem;" title="Approve / Re-activate Reservation">
                      <i class="fa-solid fa-rotate-left text-gold"></i>
                    </a>
                  <?php endif; ?>

                  <!-- WhatsApp -->
                  <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $b['phone']); ?>?text=Hello%20<?php echo urlencode($b['guest_name']); ?>%2C%20regarding%20your%20HOTEL%20booking%20<?php echo $b['booking_id']; ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding: 4px 7px; font-size: 0.75rem;" title="WhatsApp Guest">
                    <i class="fa-brands fa-whatsapp"></i>
                  </a>

                  <!-- Cancel -->
                  <?php if ($st !== 'Cancelled' && $st !== 'Checked Out'): ?>
                    <a href="dashboard.php?action=cancel&id=<?php echo $b['id']; ?>" class="btn btn-outline-dark btn-sm" style="padding: 4px 7px; font-size: 0.75rem; color: #EF4444; border-color: #EF4444;" title="Cancel Reservation" onclick="return confirm('Cancel reservation \'<?php echo $b['booking_id']; ?>\'?')">
                      <i class="fa-solid fa-ban"></i>
                    </a>
                  <?php endif; ?>

                  <!-- Delete -->
                  <a href="dashboard.php?action=delete_booking&id=<?php echo $b['id']; ?>" class="btn btn-outline-dark btn-sm" style="padding: 4px 7px; font-size: 0.75rem; color: #94A3B8; border-color: #CBD5E1;" title="Delete Record" onclick="return confirm('Delete this booking record permanently?')">
                    <i class="fa-solid fa-trash"></i>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        </div> <!-- end admin-table-responsive -->
      </div> <!-- end table-container -->
    </main>
  </div>

</body>
</html>
