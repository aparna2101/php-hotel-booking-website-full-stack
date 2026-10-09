# 🏨 HOTEL - Luxury Resort & Room Booking Web Application

A full-stack, production-grade luxury Hotel & Resort management system built using **PHP & MySQL** with real-time room availability, OTP email verification, complete booking lifecycle management, printable tax invoices, and a dedicated admin portal.

---

## ✨ Features

### 🌟 Guest & Customer Portal
* **5-Star Luxury Frontend:** Designed with a navy & gold aesthetic, responsive layout, Google Fonts, and micro-interactions.
* **User Authentication & OTP:** Register with email verification via Gmail SMTP, password reset with 6-digit OTP, and bcrypt password hashing.
* **Strict Check-in Reservation:** Collects full stay information: Check-in/out dates, guest occupancy (adults & children), Govt Photo ID proof & number, origin city/state, expected arrival time, and stay preferences.
* **Instant Booking Voucher:** Automatic generation of HTML confirmation voucher sent via email.
* **My Bookings Dashboard:** View past & upcoming stays, download printable 5-star Tax Invoices, or cancel stays with structured reasons.
* **Official Tax Invoice Generator:** Printable PDF-ready A4 receipt with GST breakdown (CGST 6% + SGST 6%), guest details, hotel seal, and WhatsApp share.
* **Customer Profile:** Manage profile details, view membership status, and update security credentials.

### 🛡️ Admin & Front Desk Management Portal
* **Dashboard Analytics:** Live statistics of total bookings, confirmed reservations, checked-in guests, in-house guests, and total active revenue.
* **All Bookings Management:** Live multi-field search bar (by booking ref, guest name, phone, Aadhaar/ID, city, reason) and filter tabs (`All`, `Confirmed`, `Checked In`, `Checked Out`, `Cancelled`).
* **Front Desk Lifecycle:** 1-click status actions: **Check-In**, **Check-Out**, **Approve**, **Re-confirm**, and **Cancel**.
* **Room Inventory Control:** Add, edit, or delete rooms with photos, pricing, amenities, and toggle **Available** vs **Under Maintenance** vs **Fully Booked**.
* **Direct Concierge WhatsApp:** 1-click button to chat with guests on WhatsApp with pre-filled booking details.

---

## 🛠️ Tech Stack
* **Backend:** Pure PHP (PDO with prepared statements)
* **Database:** MySQL (`hotel_db` schema auto-migrated)
* **Security:** `password_hash()` (Bcrypt), input sanitization, CSRF & auth guards
* **Mailer:** Pure PHP SSL/TLS Socket SMTP Client for Gmail
* **Frontend:** Modern Vanilla CSS3, HTML5, Font Awesome 6.5, Google Fonts

---

## 🚀 Local Installation & Setup

1. **Clone the repository:**
   ```bash
   git clone https://github.com/aparna2101/php-hotel-booking-website-full-stack.git
   ```
2. **Move to XAMPP htdocs:**
   Place the project folder inside `C:\xampp\htdocs\`.
3. **Start Apache & MySQL:**
   Open XAMPP Control Panel and start Apache & MySQL.
4. **Database Setup:**
   * Open `http://localhost/phpmyadmin/`.
   * The database `hotel_db` and tables auto-initialize on first visit, or you can import `database.sql`.
5. **Open in Browser:**
   * Guest Website: `http://localhost/php-hotel-booking-website-full-stack/`
   * Admin Portal: `http://localhost/php-hotel-booking-website-full-stack/admin/login.php`
     * **Email:** `admin@hotel.com`
     * **Password:** `admin123`
