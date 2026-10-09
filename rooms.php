<?php
require_once 'config/database.php';

$rooms = $sample_rooms;
if (isset($pdo) && $db_connected) {
    try {
        $stmt = $pdo->query("SELECT * FROM rooms ORDER BY price ASC");
        $db_rooms = $stmt->fetchAll();
        if (!empty($db_rooms)) {
            $rooms = $db_rooms;
        }
    } catch (Exception $e) {
        // Fallback
    }
}

$page_title = "Luxury Rooms & Villas | HOTEL";
$current_page = "rooms";

include 'includes/header.php';
?>

<!-- Rooms Hero Header -->
<section style="background: linear-gradient(rgba(10, 19, 41, 0.85), rgba(10, 19, 41, 0.9)), url('assets/images/hero.jpg') center/cover; padding: 60px 0; text-align: center; color: #FFFFFF;">
  <div class="container">
    <span class="hero-badge"><i class="fa-solid fa-crown"></i> Exclusive Accommodations</span>
    <h1 class="hero-title" style="margin-top: 15px;">Rooms & Private Villas</h1>
    <p class="hero-subtitle" style="margin-bottom: 0;">Immerse yourself in unparalleled luxury, breathtaking ocean sunsets, and private pool serenity.</p>
  </div>
</section>

<!-- Filter & Rooms Grid Section -->
<section class="section-py">
  <div class="container">
    
<?php
$categories = [];
foreach ($rooms as $r) {
    if (!empty($r['category']) && !in_array($r['category'], $categories)) {
        $categories[] = $r['category'];
    }
}
?>
    <!-- Filter Buttons Bar -->
    <div style="display: flex; justify-content: center; gap: 8px; margin-bottom: 40px; flex-wrap: wrap;">
      <button type="button" class="btn btn-gold btn-sm filter-btn active" data-filter="all">All Accommodations</button>
      <?php foreach ($categories as $cat): ?>
        <button type="button" class="btn btn-outline-gold btn-sm filter-btn" data-filter="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></button>
      <?php endforeach; ?>
    </div>

    <!-- Rooms Listing -->
    <div class="rooms-grid">
      <?php foreach ($rooms as $room): ?>
      <div class="room-card room-filter-card" data-category="<?php echo htmlspecialchars($room['category'] ?? 'Ocean View Room'); ?>">
        <div class="room-thumb">
          <img src="<?php echo htmlspecialchars($room['image']); ?>" alt="<?php echo htmlspecialchars($room['title']); ?>" onerror="this.src='assets/images/room1.jpg'">
          <?php if (!empty($room['badge'])): ?>
            <span class="room-badge"><?php echo htmlspecialchars($room['badge']); ?></span>
          <?php endif; ?>
          <?php if (($room['status'] ?? 'Available') === 'Maintenance'): ?>
            <span class="room-badge" style="left: 16px; right: auto; background: #64748B;"><i class="fa-solid fa-wrench"></i> Maintenance</span>
          <?php elseif (($room['status'] ?? 'Available') === 'Booked'): ?>
            <span class="room-badge" style="left: 16px; right: auto; background: #E11D48;"><i class="fa-solid fa-lock"></i> Fully Booked</span>
          <?php endif; ?>
          <div class="room-price-tag">
            <span class="amount">₹<?php echo number_format($room['price']); ?></span>
            <span class="period">/ night</span>
          </div>
        </div>

        <div class="room-body">
          <span class="room-category"><?php echo htmlspecialchars($room['category'] ?? 'Luxury Room'); ?></span>
          <h3 class="room-name"><?php echo htmlspecialchars($room['title']); ?></h3>
          
          <div class="room-specs">
            <span><i class="fa-solid fa-bed"></i> <?php echo htmlspecialchars($room['bed'] ?? 'King Bed'); ?></span>
            <span><i class="fa-solid fa-users"></i> <?php echo htmlspecialchars($room['capacity'] ?? '2 Guests'); ?></span>
            <span><i class="fa-solid fa-maximize"></i> <?php echo htmlspecialchars($room['size'] ?? '600 sq.ft'); ?></span>
          </div>

          <p class="room-desc">
            <?php echo htmlspecialchars($room['description']); ?>
          </p>

          <div class="room-amenities-tags">
            <?php 
              $amenities = [];
              if (is_array($room['amenities'] ?? null)) {
                  $amenities = $room['amenities'];
              } elseif (!empty($room['amenities'])) {
                  $dec = json_decode($room['amenities'], true);
                  if (is_array($dec)) {
                      $amenities = $dec;
                  } else {
                      $amenities = array_filter(array_map('trim', explode(',', $room['amenities'])));
                  }
              }
              if (empty($amenities)) {
                  $amenities = ['Ocean View', 'Wi-Fi', 'Breakfast', 'Mini Bar', 'Room Service'];
              }
              foreach ($amenities as $am):
            ?>
              <span class="room-tag"><i class="fa-solid fa-check text-gold"></i> <?php echo htmlspecialchars($am); ?></span>
            <?php endforeach; ?>
          </div>

          <div class="room-footer">
            <?php if (($room['status'] ?? 'Available') === 'Maintenance'): ?>
              <button class="btn btn-sm" disabled style="flex: 1; justify-content: center; background: #F1F5F9; color: #64748B; border: 1px solid #CBD5E1; cursor: not-allowed; opacity: 0.9;">
                <i class="fa-solid fa-screwdriver-wrench"></i> Under Maintenance
              </button>
            <?php elseif (($room['status'] ?? 'Available') === 'Booked'): ?>
              <button class="btn btn-sm" disabled style="flex: 1; justify-content: center; background: #FFF1F2; color: #BE123C; border: 1px solid #FDA4AF; cursor: not-allowed; opacity: 0.9;">
                <i class="fa-solid fa-lock"></i> Fully Booked
              </button>
            <?php else: ?>
              <a href="book.php?room=<?php echo urlencode($room['title']); ?>" class="btn btn-gold btn-sm" style="flex: 1; justify-content: center; text-align: center; white-space: nowrap;">
                <i class="fa-regular fa-calendar-check"></i> Book Now
              </a>
            <?php endif; ?>
            <a href="https://wa.me/919000000000?text=Hello%2C%20I%20want%20to%20inquire%20about%20<?php echo urlencode($room['title']); ?>" target="_blank" class="btn btn-whatsapp btn-sm" style="flex: 1; justify-content: center; text-align: center; white-space: nowrap;">
              <i class="fa-brands fa-whatsapp"></i> WhatsApp Now
            </a>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
