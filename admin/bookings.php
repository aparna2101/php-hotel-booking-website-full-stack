<?php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../config/database.php';

if (!$db_connected || !$pdo) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;"><h2 style="color:#991B1B;">Database Connection Error</h2><p>MySQL is not running. Please start XAMPP and refresh.</p><a href="bookings.php">Retry</a></div>');
}

// Handle Booking Actions (Confirm, Check-In, Check-Out, Cancel, Delete)
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
    
    // Preserve search and filter query string
    $redirect_url = "bookings.php?msg=updated";
    if (!empty($_GET['search'])) $redirect_url .= "&search=" . urlencode($_GET['search']);
    if (!empty($_GET['status'])) $redirect_url .= "&status=" . urlencode($_GET['status']);
    
    header("Location: " . $redirect_url);
    exit;
}

// Search and Filter Params
$search = trim($_GET['search'] ?? '');
$status_filter = trim($_GET['status'] ?? 'all');

// Build SQL Query
$sql = "SELECT * FROM `bookings` WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (`booking_id` LIKE ? OR `guest_name` LIKE ? OR `phone` LIKE ? OR `email` LIKE ? OR `room_title` LIKE ? OR `id_number` LIKE ? OR `city` LIKE ? OR `cancellation_reason` LIKE ?)";
    $like = "%{$search}%";
    $params = array_merge($params, [$like, $like, $like, $like, $like, $like, $like, $like]);
}

if ($status_filter !== 'all') {
    $sql .= " AND `status` = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY `id` DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Statistics Counters
$total_count       = (int)$pdo->query("SELECT COUNT(*) FROM `bookings`")->fetchColumn();
$confirmed_count   = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Confirmed'")->fetchColumn();
$checked_in_count  = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Checked In'")->fetchColumn();
$checked_out_count = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Checked Out'")->fetchColumn();
$pending_count     = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Pending'")->fetchColumn();
$cancelled_count   = (int)$pdo->query("SELECT COUNT(*) FROM `bookings` WHERE `status` = 'Cancelled'")->fetchColumn();
$active_revenue    = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM `bookings` WHERE `status` IN ('Confirmed', 'Checked In', 'Checked Out')")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Manage Bookings & Check-ins | HOTEL Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
  <link rel="stylesheet" href="../assets/css/style.css">
  <style>
    body { margin: 0; }
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
    .admin-nav { list-style: none; padding: 0; margin: 0; }
    .admin-nav li { margin-bottom: 8px; }
    .admin-nav a {
      display: flex;
      align-items: center;
      gap: 12px;
      padding: 12px 16px;
      border-radius: var(--radius-sm);
      color: #94A3B8;
      font-weight: 500;
      transition: all 0.2s ease;
      text-decoration: none;
    }
    .admin-nav a.active, .admin-nav a:hover {
      background: rgba(197, 155, 39, 0.15);
      color: var(--color-gold-light);
    }
    .admin-main {
      padding: 30px 36px;
      background: #F8FAFC;
    }
    .stat-pill {
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      border-radius: var(--radius-md);
      padding: 16px 18px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.05);
      display: flex;
      align-items: center;
      justify-content: space-between;
    }
    .admin-table {
      width: 100%;
      min-width: 820px;
      border-collapse: collapse;
      text-align: left;
      font-size: 0.86rem;
    }
    .admin-table th {
      padding: 12px 14px;
      border-bottom: 2px solid #E2E8F0;
      color: var(--color-text-muted);
      font-weight: 600;
      background: #F8FAFC;
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.8px;
    }
    .admin-table td {
      padding: 14px;
      border-bottom: 1px solid #EDF2F7;
      vertical-align: middle;
    }
    .admin-table tr:hover td {
      background-color: #FAFAFA;
    }
    .filter-tab-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 7px 14px;
      border-radius: 20px;
      font-size: 0.82rem;
      font-weight: 600;
      text-decoration: none;
      color: #64748B;
      background: #FFFFFF;
      border: 1px solid #E2E8F0;
      transition: all 0.2s ease;
    }
    .filter-tab-btn:hover {
      border-color: var(--color-gold);
      color: var(--color-primary);
    }
    .filter-tab-btn.active {
      background: var(--color-primary);
      color: #FFFFFF;
      border-color: var(--color-primary);
    }
    .action-btn-group {
      display: flex;
      align-items: center;
      gap: 5px;
      justify-content: flex-end;
    }
  </style>
</head>
<body>

  <div class="admin-layout">
    <?php
      $admin_active_page = 'bookings';
      require_once 'nav.php';
    ?>

    <!-- Main Content -->
    <main class="admin-main">

      <!-- Header Bar -->
      <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
        <div>
          <span style="font-size: 0.78rem; text-transform: uppercase; letter-spacing: 2px; color: var(--color-gold); font-weight: 700;">Front Desk Management</span>
          <h1 style="font-family: var(--font-serif); font-size: 1.85rem; color: var(--color-primary); margin: 4px 0 0;">All Guest Reservations</h1>
        </div>
        <div style="display: flex; gap: 10px;">
          <a href="dashboard.php" class="btn btn-outline-dark btn-sm">
            <i class="fa-solid fa-arrow-left"></i> Dashboard
          </a>
          <a href="../book.php" target="_blank" class="btn btn-gold btn-sm">
            <i class="fa-solid fa-plus"></i> Walk-in Reservation
          </a>
        </div>
      </div>

      <!-- Live Flash Messages -->
      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
        <div style="background: #F0FDF4; border: 1.5px solid #22C55E; color: #166534; padding: 12px 18px; border-radius: var(--radius-sm); font-size: 0.9rem; margin-bottom: 22px; display: flex; align-items: center; gap: 10px;">
          <i class="fa-solid fa-circle-check" style="color: #22C55E; font-size: 1.2rem;"></i>
          <span>Reservation status updated successfully!</span>
        </div>
      <?php endif; ?>

      <!-- 5 Summary Metric Cards -->
      <div class="admin-stats-grid">
        <div class="stat-pill">
          <div>
            <span style="font-size: 0.75rem; color: var(--color-text-muted); text-transform: uppercase; font-weight: 600;">Total Bookings</span>
            <h3 style="margin: 4px 0 0; font-size: 1.6rem; color: var(--color-primary);"><?php echo $total_count; ?></h3>
          </div>
          <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(10, 19, 41, 0.08); display: flex; align-items: center; justify-content: center; color: var(--color-primary);">
            <i class="fa-solid fa-clipboard-list"></i>
          </div>
        </div>

        <div class="stat-pill">
          <div>
            <span style="font-size: 0.75rem; color: #166534; text-transform: uppercase; font-weight: 600;">Confirmed</span>
            <h3 style="margin: 4px 0 0; font-size: 1.6rem; color: #166534;"><?php echo $confirmed_count; ?></h3>
          </div>
          <div style="width: 40px; height: 40px; border-radius: 8px; background: #DCFCE7; display: flex; align-items: center; justify-content: center; color: #166534;">
            <i class="fa-solid fa-circle-check"></i>
          </div>
        </div>

        <div class="stat-pill">
          <div>
            <span style="font-size: 0.75rem; color: #1E40AF; text-transform: uppercase; font-weight: 600;">In House</span>
            <h3 style="margin: 4px 0 0; font-size: 1.6rem; color: #1E40AF;"><?php echo $checked_in_count; ?></h3>
          </div>
          <div style="width: 40px; height: 40px; border-radius: 8px; background: #DBEAFE; display: flex; align-items: center; justify-content: center; color: #1E40AF;">
            <i class="fa-solid fa-key"></i>
          </div>
        </div>

        <div class="stat-pill">
          <div>
            <span style="font-size: 0.75rem; color: #9F1239; text-transform: uppercase; font-weight: 600;">Cancelled</span>
            <h3 style="margin: 4px 0 0; font-size: 1.6rem; color: #9F1239;"><?php echo $cancelled_count; ?></h3>
          </div>
          <div style="width: 40px; height: 40px; border-radius: 8px; background: #FFE4E6; display: flex; align-items: center; justify-content: center; color: #9F1239;">
            <i class="fa-solid fa-ban"></i>
          </div>
        </div>

        <div class="stat-pill">
          <div>
            <span style="font-size: 0.75rem; color: var(--color-gold-dark); text-transform: uppercase; font-weight: 600;">Active Revenue</span>
            <h3 style="margin: 4px 0 0; font-size: 1.4rem; color: var(--color-primary); font-weight: 800;">₹<?php echo number_format($active_revenue); ?></h3>
          </div>
          <div style="width: 40px; height: 40px; border-radius: 8px; background: rgba(197, 155, 39, 0.15); display: flex; align-items: center; justify-content: center; color: var(--color-gold);">
            <i class="fa-solid fa-indian-rupee-sign"></i>
          </div>
        </div>
      </div>

      <!-- Search & Status Filter Tool Bar -->
      <div class="card" style="padding: 18px 22px; margin-bottom: 22px; background: #FFFFFF; border-radius: var(--radius-md); border: 1px solid #E2E8F0;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
          
          <!-- Search Form -->
          <form method="GET" action="bookings.php" style="display: flex; gap: 10px; flex: 1; max-width: 480px;">
            <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
            <div style="position: relative; flex: 1;">
              <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94A3B8; font-size: 0.85rem;"></i>
              <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search ID, Guest, Phone, Aadhaar, City, Reason..." style="width: 100%; padding: 9px 12px 9px 36px; border: 1.5px solid #CBD5E1; border-radius: var(--radius-xs); font-size: 0.86rem; box-sizing: border-box;">
            </div>
            <button type="submit" class="btn btn-outline-dark btn-sm" style="flex-shrink: 0;">Search</button>
            <?php if (!empty($search) || $status_filter !== 'all'): ?>
              <a href="bookings.php" class="btn btn-subtle btn-sm" style="color: var(--color-primary); flex-shrink: 0;">Reset</a>
            <?php endif; ?>
          </form>

          <!-- Status Filter Tabs -->
          <div style="display: flex; gap: 6px; flex-wrap: wrap;">
            <a href="bookings.php?status=all<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab-btn <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
              All (<?php echo $total_count; ?>)
            </a>
            <a href="bookings.php?status=Confirmed<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab-btn <?php echo $status_filter === 'Confirmed' ? 'active' : ''; ?>">
              <i class="fa-solid fa-check" style="color: #22C55E;"></i> Confirmed (<?php echo $confirmed_count; ?>)
            </a>
            <a href="bookings.php?status=Checked In<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab-btn <?php echo $status_filter === 'Checked In' ? 'active' : ''; ?>">
              <i class="fa-solid fa-key" style="color: #3B82F6;"></i> Checked In (<?php echo $checked_in_count; ?>)
            </a>
            <a href="bookings.php?status=Checked Out<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab-btn <?php echo $status_filter === 'Checked Out' ? 'active' : ''; ?>">
              <i class="fa-solid fa-check-double" style="color: #64748B;"></i> Checked Out (<?php echo $checked_out_count; ?>)
            </a>
            <a href="bookings.php?status=Cancelled<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?>" class="filter-tab-btn <?php echo $status_filter === 'Cancelled' ? 'active' : ''; ?>">
              <i class="fa-solid fa-ban" style="color: #EF4444;"></i> Cancelled (<?php echo $cancelled_count; ?>)
            </a>
          </div>

        </div>
      </div>

      <!-- Bookings Table Card -->
      <div class="card" style="overflow: hidden; background: #FFFFFF; border-radius: var(--radius-md); border: 1px solid #E2E8F0;">
        <div style="padding: 16px 22px; border-bottom: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center;">
          <h3 style="font-family: var(--font-serif); font-size: 1.15rem; color: var(--color-primary); margin: 0;">
            Bookings Roster <span style="color: var(--color-gold); font-size: 0.95rem;">(<?php echo count($bookings); ?> Found)</span>
          </h3>
          <span style="font-size: 0.78rem; color: var(--color-text-muted);">
            Real-time Database Sync &bull; Click Invoice icon to print slip
          </span>
        </div>

        <div class="table-swipe-hint">
          <i class="fa-solid fa-arrows-left-right"></i> Swipe table horizontally to view full guest ID, room info & front desk actions
        </div>

        <div class="admin-table-responsive">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Booking Ref</th>
                <th>Guest & Govt ID</th>
                <th>Room & Occupancy</th>
                <th>Dates & Arrival</th>
                <th>Total Tariff</th>
                <th>Status</th>
                <th style="text-align: right;">Front Desk Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($bookings)): ?>
                <tr>
                  <td colspan="7" style="text-align: center; padding: 60px 20px; color: var(--color-text-muted);">
                    <i class="fa-regular fa-calendar-xmark" style="font-size: 2.4rem; color: var(--color-gold); display: block; margin-bottom: 12px;"></i>
                    <h4 style="margin: 0 0 6px; color: var(--color-primary); font-size: 1.1rem;">No Reservations Found</h4>
                    <p style="margin: 0; font-size: 0.85rem;">No bookings match your current search or status filter.</p>
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                  <tr>
                    <!-- Ref ID & Time -->
                    <td style="white-space: nowrap;">
                      <a href="../invoice.php?id=<?php echo urlencode($b['booking_id']); ?>" target="_blank" style="font-family: monospace; font-weight: 800; color: var(--color-gold); font-size: 0.92rem; text-decoration: none;">
                        <?php echo htmlspecialchars($b['booking_id'] ?? ('BK-' . $b['id'])); ?>
                      </a>
                      <div style="font-size: 0.72rem; color: var(--color-text-muted); margin-top: 2px;">
                        <?php echo date('d M, h:i A', strtotime($b['created_at'])); ?>
                      </div>
                    </td>

                    <!-- Guest Details & Govt ID -->
                    <td>
                      <strong style="color: var(--color-primary); font-size: 0.94rem;"><?php echo htmlspecialchars($b['guest_name']); ?></strong><br>
                      <span style="font-size: 0.76rem; color: var(--color-text-muted); line-height: 1.5; display: inline-block; margin-top: 2px;">
                        <i class="fa-solid fa-phone"></i> <?php echo htmlspecialchars($b['phone']); ?> &bull; 
                        <i class="fa-solid fa-envelope"></i> <?php echo htmlspecialchars($b['email']); ?>
                      </span>
                      <?php if (!empty($b['id_number'])): ?>
                        <div style="font-size: 0.75rem; color: #0284C7; margin-top: 2px; font-weight: 600;">
                          <i class="fa-solid fa-id-card"></i> <?php echo htmlspecialchars($b['id_type'] ?? 'ID'); ?>: <span><?php echo htmlspecialchars($b['id_number']); ?></span>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($b['city'])): ?>
                        <div style="font-size: 0.72rem; color: #64748B; margin-top: 1px;">
                          <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($b['city'] . (!empty($b['state']) ? ', ' . $b['state'] : '')); ?>
                        </div>
                      <?php endif; ?>

                      <!-- Show Cancellation Reason if cancelled -->
                      <?php if (($b['status'] ?? '') === 'Cancelled' && !empty($b['cancellation_reason'])): ?>
                        <div style="background: #FFF1F2; border-left: 2px solid #F43F5E; padding: 4px 8px; font-size: 0.74rem; color: #9F1239; margin-top: 4px; border-radius: 2px;">
                          <strong>Reason:</strong> <?php echo htmlspecialchars($b['cancellation_reason']); ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Room & Stay Details -->
                    <td>
                      <div style="font-weight: 600; color: var(--color-primary); font-size: 0.88rem;"><?php echo htmlspecialchars($b['room_title']); ?></div>
                      <div style="font-size: 0.75rem; color: var(--color-gold); font-weight: 600; margin-top: 2px;">
                        <i class="fa-solid fa-user-group"></i> <?php echo htmlspecialchars($b['guests']); ?>
                        <?php if (!empty($b['visit_purpose'])): ?>
                          &bull; <span style="color: #64748B; font-weight: 400;"><?php echo htmlspecialchars($b['visit_purpose']); ?></span>
                        <?php endif; ?>
                      </div>
                      <?php if (!empty($b['meal_pref']) && $b['meal_pref'] !== 'No Preference'): ?>
                        <div style="font-size: 0.72rem; color: #059669; margin-top: 2px;">
                          <i class="fa-solid fa-utensils"></i> <?php echo htmlspecialchars($b['meal_pref']); ?>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($b['notes'])): ?>
                        <div style="font-size: 0.72rem; color: #475569; font-style: italic; margin-top: 2px;">
                          "<?php echo htmlspecialchars($b['notes']); ?>"
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Dates & Arrival Window -->
                    <td style="font-size: 0.82rem; color: var(--color-text-muted); line-height: 1.5; white-space: nowrap;">
                      <strong><?php echo htmlspecialchars($b['checkin']); ?></strong> &rarr; <strong><?php echo htmlspecialchars($b['checkout']); ?></strong>
                      <div style="font-size: 0.74rem; color: #64748B; margin-top: 2px;">
                        <?php echo $b['nights'] ?? 1; ?> Night<?php echo ($b['nights'] ?? 1) > 1 ? 's' : ''; ?>
                        <?php if (!empty($b['arrival_time'])): ?>
                          &bull; In: <?php echo htmlspecialchars($b['arrival_time']); ?>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Total Amount -->
                    <td style="font-weight: 800; color: var(--color-primary); font-size: 0.98rem; white-space: nowrap;">
                      ₹<?php echo number_format($b['total_amount'] ?? 0); ?>
                    </td>

                    <!-- Status Badge -->
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

                    <!-- Actions Buttons Group -->
                    <td style="text-align: right; white-space: nowrap;">
                      <div class="action-btn-group">
                        
                        <!-- Print / View Invoice Slip -->
                        <a href="../invoice.php?id=<?php echo urlencode($b['booking_id']); ?>" target="_blank" class="btn btn-outline-dark btn-sm" style="padding: 5px 8px; font-size: 0.76rem;" title="Official Printable Tax Invoice">
                          <i class="fa-solid fa-file-invoice text-gold"></i>
                        </a>

                        <!-- Check-In Action (When Confirmed) -->
                        <?php if ($st === 'Confirmed'): ?>
                          <a href="bookings.php?action=check_in&id=<?php echo $b['id']; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-sm" style="background: #2563EB; color: #FFF; padding: 5px 9px; font-size: 0.76rem; border: none;" title="Mark Guest Checked In">
                            <i class="fa-solid fa-key"></i> Check In
                          </a>
                        <?php endif; ?>

                        <!-- Check-Out Action (When Checked In) -->
                        <?php if ($st === 'Checked In'): ?>
                          <a href="bookings.php?action=check_out&id=<?php echo $b['id']; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-sm" style="background: #059669; color: #FFF; padding: 5px 9px; font-size: 0.76rem; border: none;" title="Mark Guest Checked Out">
                            <i class="fa-solid fa-check-double"></i> Check Out
                          </a>
                        <?php endif; ?>

                        <!-- Re-Confirm Action (When Cancelled or Pending) -->
                        <?php if (in_array($st, ['Pending', 'Cancelled'])): ?>
                          <a href="bookings.php?action=confirm&id=<?php echo $b['id']; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline-dark btn-sm" style="padding: 5px 8px; font-size: 0.76rem;" title="Confirm / Re-activate">
                            <i class="fa-solid fa-rotate-left text-gold"></i>
                          </a>
                        <?php endif; ?>

                        <!-- WhatsApp Direct Guest -->
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $b['phone']); ?>?text=Hello%20<?php echo urlencode($b['guest_name']); ?>%2C%20regarding%20your%20HOTEL%20booking%20<?php echo $b['booking_id']; ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="padding: 5px 8px; font-size: 0.76rem;" title="WhatsApp Concierge">
                          <i class="fa-brands fa-whatsapp"></i>
                        </a>

                        <!-- Cancel Action (When Confirmed or Checked In) -->
                        <?php if ($st !== 'Cancelled' && $st !== 'Checked Out'): ?>
                          <a href="bookings.php?action=cancel&id=<?php echo $b['id']; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline-dark btn-sm" style="padding: 5px 8px; font-size: 0.76rem; color: #EF4444; border-color: #EF4444;" title="Cancel Reservation" onclick="return confirm('Cancel reservation \'<?php echo $b['booking_id']; ?>\'?')">
                            <i class="fa-solid fa-ban"></i>
                          </a>
                        <?php endif; ?>

                        <!-- Delete Record -->
                        <a href="bookings.php?action=delete_booking&id=<?php echo $b['id']; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status_filter); ?>" class="btn btn-outline-dark btn-sm" style="padding: 5px 8px; font-size: 0.76rem; color: #94A3B8; border-color: #CBD5E1;" title="Delete Record Permanently" onclick="return confirm('Delete booking \'<?php echo $b['booking_id']; ?>\' permanently from database?')">
                          <i class="fa-solid fa-trash"></i>
                        </a>

                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>
  </div>

</body>
</html>
