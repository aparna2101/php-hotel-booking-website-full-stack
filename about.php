<?php
$page_title = "About Us | HOTEL";
$current_page = "about";

include 'includes/header.php';
?>

<!-- About Hero Header -->
<section style="background: linear-gradient(rgba(10, 19, 41, 0.85), rgba(10, 19, 41, 0.9)), url('assets/images/hero.jpg') center/cover; padding: 60px 0; text-align: center; color: #FFFFFF;">
  <div class="container">
    <span class="hero-badge"><i class="fa-solid fa-crown"></i> Our Royal Heritage</span>
    <h1 class="hero-title" style="margin-top: 15px;">Welcome to Our Hotel</h1>
    <p class="hero-subtitle" style="margin-bottom: 0;">A sanctuary crafted for tranquil escapes, royal indulgence, and unforgettable memories.</p>
  </div>
</section>

<!-- About Story Section -->
<section class="section-py">
  <div class="container">
    <div class="about-story-grid">
      <div>
        <span class="section-tag">Since 2015</span>
        <h2 class="section-title" style="text-align: left;">Timeless Elegance & Coastal Splendor</h2>
        <p style="color: var(--color-text-muted); margin-bottom: 20px; line-height: 1.8;">
          Founded on the philosophy of personalized royal hospitality, our Hotel stands as an architectural tribute to the harmony between nature and luxury. Every corner has been meticulously designed with warm imperial gold finishes, deep sapphire accents, and soothing coastal breezes.
        </p>
        <p style="color: var(--color-text-muted); margin-bottom: 30px; line-height: 1.8;">
          Whether you are savoring breakfast on a private ocean balcony, soaking in an infinity plunge pool, or strolling along our private sun-drenched sands, our dedicated team of hospitality staff and butlers ensure your experience is nothing short of perfection.
        </p>
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
          <a href="rooms.php" class="btn btn-gold btn-sm">Explore Accommodations</a>
          <a href="book.php" class="btn btn-outline-dark btn-sm">Book A Stay</a>
        </div>
      </div>

      <div>
        <div style="border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-lg); border: 2px solid var(--color-border-gold);">
          <img src="assets/images/hero.jpg" alt="Hotel View" style="width: 100%; height: 380px; object-fit: cover;">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Stats Section -->
<section class="section-py bg-navy">
  <div class="container">
    <div class="stats-grid">
      <div>
        <div class="text-gradient-gold" style="font-family: var(--font-serif); font-size: 3.2rem; font-weight: 700;">45+</div>
        <div style="color: #CBD5E1; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;">Luxury Rooms</div>
      </div>
      <div>
        <div class="text-gradient-gold" style="font-family: var(--font-serif); font-size: 3.2rem; font-weight: 700;">4.9★</div>
        <div style="color: #CBD5E1; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;">Guest Satisfaction</div>
      </div>
      <div>
        <div class="text-gradient-gold" style="font-family: var(--font-serif); font-size: 3.2rem; font-weight: 700;">12,000+</div>
        <div style="color: #CBD5E1; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;">Delighted Guests</div>
      </div>
      <div>
        <div class="text-gradient-gold" style="font-family: var(--font-serif); font-size: 3.2rem; font-weight: 700;">24/7</div>
        <div style="color: #CBD5E1; font-weight: 600; text-transform: uppercase; letter-spacing: 1px; margin-top: 6px;">24/7 Room Service</div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
