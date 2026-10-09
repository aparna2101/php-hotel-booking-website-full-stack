<?php
require_once 'config/database.php';

// Check if we can fetch rooms from database, otherwise use sample fallback
$rooms = $sample_rooms;
if (isset($pdo) && $db_connected) {
    try {
        $stmt = $pdo->query("SELECT * FROM rooms ORDER BY id ASC LIMIT 3");
        $db_rooms = $stmt->fetchAll();
        if (!empty($db_rooms)) {
            $rooms = $db_rooms;
        }
    } catch (Exception $e) {
        // Fallback to sample rooms
    }
}

$page_title = "HOTEL | Luxury Seaside Rooms & Spa";
$current_page = "home";

include 'includes/header.php';
?>

<!-- ==========================================================================
     HERO SECTION
     ========================================================================== -->
<section class="hero-section">
  <div class="container">
    <div class="hero-content">
      <div class="hero-badge">
        <i class="fa-solid fa-crown"></i> 5-Star Seaside Luxury Experience
      </div>
      <h1 class="hero-title">Experience Royal Elegance by the Azure Coast</h1>
      <p class="hero-subtitle">
        Indulge in an enchanting sanctuary of private infinity pools, sun-kissed cabanas, and bespoke luxury hospitality tailored just for you.
      </p>
      <div class="hero-buttons">
        <a href="rooms.php" class="btn btn-gold btn-sm">
          <i class="fa-solid fa-compass"></i> Explore Rooms
        </a>
        <a href="book.php" class="btn btn-outline-gold btn-sm">
          <i class="fa-regular fa-calendar-check"></i> Book A Stay
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     QUICK BOOKING BAR
     ========================================================================== -->
<div class="container booking-bar-wrapper">
  <form class="booking-bar" method="GET" action="book.php">
    <div class="form-group">
      <label><i class="fa-regular fa-calendar"></i> Check-in</label>
      <input type="date" name="checkin" required>
    </div>
    <div class="form-group">
      <label><i class="fa-regular fa-calendar-check"></i> Check-out</label>
      <input type="date" name="checkout" required>
    </div>
    <div class="form-group">
      <label><i class="fa-solid fa-bed"></i> Room Type</label>
      <select name="room_type">
        <option value="ocean">Oceanfront Master Room</option>
        <option value="penthouse">Presidential Penthouse</option>
        <option value="garden">Tropical Garden Villa</option>
      </select>
    </div>
    <div class="form-group">
      <label><i class="fa-solid fa-user-group"></i> Guests</label>
      <select name="guests">
        <option value="1">1 Adult</option>
        <option value="2" selected>2 Adults</option>
        <option value="3">3 Adults</option>
        <option value="4">4+ Adults / Family</option>
      </select>
    </div>
    <div class="form-group" style="justify-content: flex-end;">
      <label style="visibility: hidden;">Action</label>
      <button type="submit" class="btn btn-gold btn-sm" style="width: 100%;">
        <i class="fa-solid fa-magnifying-glass"></i> Check Rates
      </button>
    </div>
  </form>
</div>

<!-- ==========================================================================
     WELCOME & RESORT HIGHLIGHTS
     ========================================================================== -->
<section class="section-py">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Sanctuary of Refinement</span>
      <h2 class="section-title">A World Beyond Ordinary Hospitality</h2>
      <p class="section-desc">
        Nestled along the golden palm shores, our resort merges contemporary architectural marvels with traditional royal warmth.
      </p>
    </div>

    <div class="features-grid">
      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-water-ladder"></i></div>
        <h3 class="feature-title">Infinity Sea Pool</h3>
        <p class="feature-desc">Perched over the horizon with temperature-controlled crystal waters and sunset cocktail bar.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-spa"></i></div>
        <h3 class="feature-title">Imperial Ayurveda Spa</h3>
        <p class="feature-desc">Rejuvenate your senses with ancient therapeutic rituals, hot stone therapies, and ocean breezes.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-utensils"></i></div>
        <h3 class="feature-title">Gourmet Ocean Dining</h3>
        <p class="feature-desc">Michelin-standard culinary masterpieces curated with freshly caught seafood and organic produce.</p>
      </div>

      <div class="feature-card">
        <div class="feature-icon"><i class="fa-solid fa-bell"></i></div>
        <h3 class="feature-title">24/7 Butler & Chauffeur</h3>
        <p class="feature-desc">Dedicated personal butler service and complimentary luxury airport transfers for every guest.</p>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     FEATURED ROOMS & VILLAS
     ========================================================================== -->
<section class="section-py bg-navy" id="roomsSection">
  <div class="container">
    <div class="section-header">
      <span class="section-tag text-gold">Handcrafted Luxury</span>
      <h2 class="section-title">Signature Accommodations</h2>
      <p class="section-desc">
        Each room is designed as a peaceful oasis, blending panoramic ocean panoramas, Italian marble, and plush king bedding.
      </p>
    </div>

    <div class="rooms-grid">
      <?php foreach ($rooms as $room): ?>
      <div class="room-card">
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
                  $amenities = ['Ocean View', 'Wi-Fi', 'Breakfast'];
              }
              foreach (array_slice($amenities, 0, 3) as $am):
            ?>
              <span class="room-tag"><i class="fa-solid fa-check text-gold"></i> <?php echo htmlspecialchars($am); ?></span>
            <?php endforeach; ?>
          </div>

          <div class="room-footer">
            <?php if (($room['status'] ?? 'Available') === 'Maintenance'): ?>
              <button class="btn btn-sm" disabled style="flex: 1; justify-content: center; background: #334155; color: #94A3B8; border: 1px solid #475569; cursor: not-allowed; opacity: 0.9;">
                <i class="fa-solid fa-screwdriver-wrench"></i> Under Maintenance
              </button>
            <?php elseif (($room['status'] ?? 'Available') === 'Booked'): ?>
              <button class="btn btn-sm" disabled style="flex: 1; justify-content: center; background: rgba(225, 29, 72, 0.2); color: #FDA4AF; border: 1px solid #E11D48; cursor: not-allowed; opacity: 0.9;">
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

    <div style="text-align: center; margin-top: 40px;">
      <a href="rooms.php" class="btn btn-outline-gold btn-sm" style="max-width: 100%;">
        <i class="fa-solid fa-arrow-right"></i> View All Rooms & Villas
      </a>
    </div>
  </div>
</section>

<!-- ==========================================================================
     DIRECT RESERVATION BENEFITS BANNER
     ========================================================================== -->
<section class="section-py" style="padding-top: 40px; padding-bottom: 40px;">
  <div class="container">
    <div class="cta-banner">
      <div class="cta-info">
        <span class="section-tag">Direct Booking Benefits</span>
        <h3>Plan Your Perfect Escape with Us</h3>
        <p>Book directly with us for best price guarantee, complimentary room upgrades, and free sunset mocktails.</p>
      </div>
      <div class="cta-actions">
        <a href="book.php" class="btn btn-gold btn-sm">
          <i class="fa-regular fa-calendar-check"></i> Reserve Online
        </a>
        <a href="rooms.php" class="btn btn-outline-gold btn-sm">
          <i class="fa-solid fa-compass"></i> View All Rooms
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ==========================================================================
     GUEST EXPERIENCES & TESTIMONIALS
     ========================================================================== -->
<section class="section-py" style="background: #FFFFFF;">
  <div class="container">
    <div class="section-header">
      <span class="section-tag">Guest Experiences</span>
      <h2 class="section-title">Stories of Pure Serenity</h2>
      <p class="section-desc">See what our esteemed travelers have to say about their unforgettable stays at our Hotel.</p>
    </div>

    <div class="testimonials-grid">
      <div class="testimonial-card">
        <div class="testimonial-stars">
          <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
        </div>
        <p class="testimonial-text">
          "The Oceanfront Master Room was magical. Waking up to the sea waves and enjoying breakfast on the private balcony felt like heaven. Hotel guest service was 10/10!"
        </p>
        <div class="testimonial-author">
          <div class="author-avatar"><i class="fa-solid fa-user"></i></div>
          <div>
            <div class="author-name">Verified Suite Guest</div>
            <div class="author-location">Oceanfront Retreat</div>
          </div>
        </div>
      </div>

      <div class="testimonial-card">
        <div class="testimonial-stars">
          <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
        </div>
        <p class="testimonial-text">
          "We stayed in the Presidential Penthouse for our 5th anniversary. The architecture, the private butler, and the sunset pool view exceeded every expectation."
        </p>
        <div class="testimonial-author">
          <div class="author-avatar"><i class="fa-solid fa-user"></i></div>
          <div>
            <div class="author-name">Verified Penthouse Guest</div>
            <div class="author-location">Executive Stay</div>
          </div>
        </div>
      </div>

      <div class="testimonial-card">
        <div class="testimonial-stars">
          <i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i>
        </div>
        <p class="testimonial-text">
          "Booking via WhatsApp was seamless! Within 5 minutes my reservation was confirmed and a driver was waiting at Goa airport. Truly a world-class luxury hotel experience."
        </p>
        <div class="testimonial-author">
          <div class="author-avatar"><i class="fa-solid fa-user"></i></div>
          <div>
            <div class="author-name">International Traveler</div>
            <div class="author-location">Holiday Vacation</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
