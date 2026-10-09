  <!-- Luxury Footer -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <!-- Brand Info -->
        <div class="footer-brand">
          <div class="brand-logo">
            <img src="assets/images/logo.png" alt="Hotel Logo">
            <div class="brand-text">
              <span class="brand-name">HOTEL</span>
              <span class="brand-sub">LUXURY STAY & ROOMS</span>
            </div>
          </div>
          <p>
            An enchanting sanctuary where timeless architecture meets coastal elegance. Discover tranquil rooms, private plunge pools, and bespoke luxury hospitality.
          </p>
          <div class="footer-socials">
            <a href="#" class="social-link" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
            <a href="#" class="social-link" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
            <a href="https://wa.me/919000000000" target="_blank" class="social-link" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
            <a href="tel:+919000000000" class="social-link" title="Phone"><i class="fa-solid fa-phone"></i></a>
          </div>
        </div>

        <!-- Quick Links -->
        <div class="footer-col footer-col-nav">
          <h4>Navigation</h4>
          <ul class="footer-links">
            <li><a href="index.php"><i class="fa-solid fa-angle-right"></i> Home</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-angle-right"></i> Luxury Rooms</a></li>
            <li><a href="about.php"><i class="fa-solid fa-angle-right"></i> Our Story</a></li>
            <li><a href="contact.php"><i class="fa-solid fa-angle-right"></i> Contact & Location</a></li>
          </ul>
        </div>

        <!-- Accommodations -->
        <div class="footer-col footer-col-rooms">
          <h4>Accommodations</h4>
          <ul class="footer-links">
            <li><a href="rooms.php"><i class="fa-solid fa-angle-right"></i> Oceanfront Rooms</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-angle-right"></i> Presidential Penthouse</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-angle-right"></i> Tropical Garden Villa</a></li>
            <li><a href="rooms.php"><i class="fa-solid fa-angle-right"></i> Sunset Cabanas</a></li>
          </ul>
        </div>

        <!-- Contact & Front Desk -->
        <div class="footer-col footer-contact">
          <h4>24/7 Front Desk</h4>
          <ul>
            <li>
              <i class="fa-solid fa-location-dot"></i>
              <span>Beach Boulevard, Marine Bay, Goa 403001</span>
            </li>
            <li>
              <i class="fa-solid fa-phone"></i>
              <a href="tel:+919000000000">+91 90000 00000</a>
            </li>
            <li>
              <i class="fa-brands fa-whatsapp"></i>
              <a href="https://wa.me/919000000000?text=Hello%2C%20I%20have%20an%20inquiry%20regarding%20hotel%20booking" target="_blank">+91 90000 00000</a>
            </li>
            <li>
              <i class="fa-solid fa-envelope"></i>
              <a href="mailto:info@hotel.com">info@hotel.com</a>
            </li>
          </ul>
        </div>
      </div>

      <!-- Footer Bottom Bar -->
      <div class="footer-bottom">
        <div>
          &copy; <?php echo date('Y'); ?> Luxury Hotel & Rooms. All rights reserved.
        </div>
        <div>
          <!-- Admin Panel Link -->
          <a href="admin/login.php" class="footer-admin-link">
            <i class="fa-solid fa-lock"></i> Staff & Admin Portal
          </a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Floating Sticky Action Buttons (Call & WhatsApp) -->
  <div class="floating-actions" id="floatingActions">
    <a href="https://wa.me/919000000000?text=Hello%2C%20I%20would%20like%20to%20inquire%20about%20a%20hotel%20room" target="_blank" class="floating-btn floating-wa" id="floatWaBtn" aria-label="Chat on WhatsApp">
      <i class="fa-brands fa-whatsapp"></i>
      <span class="floating-tooltip">Chat with us on WhatsApp</span>
    </a>
    <a href="tel:+919000000000" class="floating-btn floating-call" id="floatCallBtn" aria-label="Call Reception">
      <i class="fa-solid fa-phone"></i>
      <span class="floating-tooltip">Call: +91 90000 00000</span>
    </a>
  </div>

  <!-- Interactive Luxury Booking Modal -->
  <div class="modal-overlay" id="bookingModal">
    <div class="modal-card">
      <div class="modal-header">
        <div>
          <span style="font-size: 0.75rem; letter-spacing: 2px; color: var(--color-gold-light); text-transform: uppercase;">Direct Reservation</span>
          <h3>Reserve Your Luxury Stay</h3>
        </div>
        <button type="button" class="modal-close" id="modalClose" aria-label="Close modal">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <div class="modal-body">
        <form id="bookingModalForm">
          <div class="modal-grid">
            <div class="form-group col-full">
              <label><i class="fa-solid fa-bed"></i> Selected Room</label>
              <?php
                $footer_modal_rooms = $rooms ?? $sample_rooms ?? [];
                if (empty($footer_modal_rooms) && isset($pdo) && $db_connected) {
                    try {
                        $footer_modal_rooms = $pdo->query("SELECT * FROM rooms ORDER BY price ASC")->fetchAll();
                    } catch (Exception $e) {}
                }
              ?>
              <select name="room" id="modalRoomSelect" required>
                <?php if (!empty($footer_modal_rooms)): ?>
                  <?php foreach ($footer_modal_rooms as $fmr): ?>
                    <option value="<?php echo htmlspecialchars($fmr['title']); ?>">
                      <?php echo htmlspecialchars($fmr['title']); ?> — ₹<?php echo number_format($fmr['price']); ?> / night
                    </option>
                  <?php endforeach; ?>
                <?php else: ?>
                  <option value="Oceanfront Master Room">Oceanfront Master Room — ₹4,999 / night</option>
                <?php endif; ?>
              </select>
            </div>

            <div class="form-group">
              <label><i class="fa-regular fa-calendar"></i> Check-in Date</label>
              <input type="date" name="checkin" required>
            </div>

            <div class="form-group">
              <label><i class="fa-regular fa-calendar-check"></i> Check-out Date</label>
              <input type="date" name="checkout" required>
            </div>

            <div class="form-group col-full">
              <label><i class="fa-solid fa-user"></i> Full Name</label>
              <input type="text" name="guest_name" placeholder="Enter guest name" required>
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-phone"></i> Contact Number</label>
              <input type="tel" name="phone" placeholder="+91 90000 00000" required>
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-envelope"></i> Email Address</label>
              <input type="email" name="email" placeholder="guest@example.com" required>
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-users"></i> Guests</label>
              <select name="guests">
                <option value="1 Adult">1 Adult</option>
                <option value="2 Adults" selected>2 Adults</option>
                <option value="3 Adults">3 Adults</option>
                <option value="4+ Adults / Family">4+ Adults / Family</option>
              </select>
            </div>

            <div class="form-group">
              <label><i class="fa-solid fa-clipboard-list"></i> Special Requests</label>
              <input type="text" name="notes" placeholder="Airport pickup, high floor, etc.">
            </div>

            <div class="col-full" style="margin-top: 10px;">
              <button type="submit" class="btn btn-gold" style="width: 100%;">
                <i class="fa-solid fa-paper-plane"></i> Confirm Reservation Inquiry
              </button>
            </div>
          </div>
        </form>

        <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #E2E8F0; display: flex; justify-content: space-between; align-items: center; font-size: 0.85rem;">
          <span style="color: var(--color-text-muted);">Prefer instant booking?</span>
          <div style="display: flex; gap: 15px;">
            <a href="tel:+919000000000" class="text-gold" style="font-weight: 600;"><i class="fa-solid fa-phone"></i> Call Direct</a>
            <a href="https://wa.me/919000000000" target="_blank" style="color: #25D366; font-weight: 600;"><i class="fa-brands fa-whatsapp"></i> Chat Now</a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript -->
  <script src="assets/js/main.js"></script>
</body>
</html>
