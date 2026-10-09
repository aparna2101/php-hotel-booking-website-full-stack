<?php
$page_title = "Contact & Location | HOTEL";
$current_page = "contact";

include 'includes/header.php';
?>

<!-- Contact Hero Header -->
<section style="background: linear-gradient(rgba(10, 19, 41, 0.85), rgba(10, 19, 41, 0.9)), url('assets/images/hero.jpg') center/cover; padding: 60px 0; text-align: center; color: #FFFFFF;">
  <div class="container">
    <span class="hero-badge"><i class="fa-solid fa-headset"></i> 24/7 Front Desk</span>
    <h1 class="hero-title" style="margin-top: 15px;">Connect with Our Hotel</h1>
    <p class="hero-subtitle" style="margin-bottom: 0;">Our dedicated guest experience team is available around the clock to assist with bookings, airport pickups, and special events.</p>
  </div>
</section>

<!-- Contact Section -->
<section class="section-py">
  <div class="container">
    <div class="contact-layout-grid">
      
      <!-- Contact Cards -->
      <div>
        <span class="section-tag">Direct Communication</span>
        <h2 class="section-title" style="text-align: left; margin-bottom: 30px;">How Can We Help You?</h2>

        <div style="display: flex; flex-direction: column; gap: 20px;">
          <!-- Phone Card -->
          <div class="contact-card-box">
            <div class="contact-card-info">
              <div class="contact-icon contact-icon-phone">
                <i class="fa-solid fa-phone"></i>
              </div>
              <div>
                <h4>Call Reception Desk</h4>
                <p>+91 90000 00000</p>
              </div>
            </div>
            <a href="tel:+919000000000" class="btn btn-call btn-sm">Call Now</a>
          </div>

          <!-- WhatsApp Card -->
          <div class="contact-card-box">
            <div class="contact-card-info">
              <div class="contact-icon contact-icon-wa">
                <i class="fa-brands fa-whatsapp"></i>
              </div>
              <div>
                <h4>Chat On WhatsApp</h4>
                <p>+91 90000 00000</p>
              </div>
            </div>
            <a href="https://wa.me/919000000000?text=Hi%2C%20I%20want%20to%20connect%20with%20your%20Hotel" target="_blank" class="btn btn-whatsapp btn-sm">Chat Now</a>
          </div>

          <!-- Email Card -->
          <div class="contact-card-box">
            <div class="contact-card-info">
              <div class="contact-icon contact-icon-email">
                <i class="fa-solid fa-envelope"></i>
              </div>
              <div>
                <h4>Email Support</h4>
                <p>info@hotel.com</p>
              </div>
            </div>
          </div>

          <!-- Address Card -->
          <div class="contact-card-box">
            <div class="contact-card-info">
              <div class="contact-icon contact-icon-geo">
                <i class="fa-solid fa-location-dot"></i>
              </div>
              <div>
                <h4>Resort Location</h4>
                <p>Beach Boulevard, Marine Bay, Goa 403001</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Contact Message Form -->
      <div>
        <div class="contact-form-box">
          <span class="section-tag">Send a Message</span>
          <h3 class="section-title" style="text-align: left; font-size: 1.8rem; margin-bottom: 20px;">Guest Inquiry Form</h3>

          <form onsubmit="event.preventDefault(); alert('Thank you! Your message has been sent to our team. We will get back to you shortly.'); this.reset();">
            <div class="form-group" style="margin-bottom: 16px;">
              <label><i class="fa-solid fa-user"></i> Your Name</label>
              <input type="text" placeholder="Your Full Name" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
              <label><i class="fa-solid fa-envelope"></i> Email Address</label>
              <input type="email" placeholder="e.g. guest@example.com" required>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
              <label><i class="fa-solid fa-phone"></i> Mobile Number</label>
              <input type="tel" placeholder="+91 90000 00000" required>
            </div>

            <div class="form-group" style="margin-bottom: 22px;">
              <label><i class="fa-solid fa-message"></i> Message or Special Inquiry</label>
              <textarea rows="4" style="background: #F8FAFC; border: 1.5px solid #E2E8F0; border-radius: var(--radius-sm); padding: 12px; resize: vertical;" placeholder="Tell us how we can assist your stay..." required></textarea>
            </div>

            <div style="margin-bottom: 20px;">
              <button type="submit" class="btn btn-gold" style="width: 100%; padding: 12px;">
                <i class="fa-solid fa-paper-plane"></i> Send Inquiry Message
              </button>
            </div>
          </form>
        </div>
      </div>

    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
