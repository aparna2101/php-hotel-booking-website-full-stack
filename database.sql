-- Hotel Luxury Resort & Rooms Database Schema
-- Database Name: hotel_db

CREATE DATABASE IF NOT EXISTS `hotel_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hotel_db`;

-- 1. Users Table (Registration with Email OTP verification)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(120) NOT NULL,
  `email` VARCHAR(160) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `otp` VARCHAR(10) DEFAULT NULL,
  `otp_expiry` DATETIME DEFAULT NULL,
  `is_verified` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Rooms Table
CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title` VARCHAR(150) NOT NULL,
  `category` VARCHAR(80) NOT NULL,
  `price` INT NOT NULL,
  `badge` VARCHAR(60) DEFAULT NULL,
  `image` VARCHAR(255) NOT NULL,
  `capacity` VARCHAR(60) NOT NULL,
  `bed` VARCHAR(60) NOT NULL,
  `size` VARCHAR(60) NOT NULL,
  `description` TEXT NOT NULL,
  `amenities` TEXT NOT NULL,
  `status` ENUM('Available', 'Booked', 'Maintenance') DEFAULT 'Available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Bookings Table
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `booking_id` VARCHAR(40) NOT NULL UNIQUE,
  `user_id` INT DEFAULT NULL,
  `guest_name` VARCHAR(120) NOT NULL,
  `phone` VARCHAR(30) NOT NULL,
  `email` VARCHAR(160) NOT NULL,
  `room_title` VARCHAR(150) NOT NULL,
  `checkin` DATE NOT NULL,
  `checkout` DATE NOT NULL,
  `guests` VARCHAR(60) NOT NULL,
  `adults` INT DEFAULT 2,
  `children` INT DEFAULT 0,
  `id_type` VARCHAR(50) DEFAULT 'Aadhaar Card',
  `id_number` VARCHAR(80) DEFAULT NULL,
  `city` VARCHAR(80) DEFAULT NULL,
  `state` VARCHAR(80) DEFAULT NULL,
  `country` VARCHAR(80) DEFAULT 'India',
  `arrival_time` VARCHAR(50) DEFAULT '12:00 PM - 02:00 PM',
  `visit_purpose` VARCHAR(80) DEFAULT 'Vacation / Leisure',
  `meal_pref` VARCHAR(80) DEFAULT 'No Preference',
  `nights` INT NOT NULL DEFAULT 1,
  `total_amount` DECIMAL(10,2) NOT NULL,
  `status` VARCHAR(40) DEFAULT 'Confirmed',
  `cancellation_reason` TEXT DEFAULT NULL,
  `cancelled_at` DATETIME DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX (`email`),
  INDEX (`booking_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Admins Table
CREATE TABLE IF NOT EXISTS `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `username` VARCHAR(80) NOT NULL UNIQUE,
  `email` VARCHAR(160) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(40) DEFAULT 'admin',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Rooms Seed
INSERT INTO `rooms` (`id`, `title`, `category`, `price`, `badge`, `image`, `capacity`, `bed`, `size`, `description`, `amenities`, `status`) 
VALUES
(1, 'Oceanfront Master Room', 'Ocean View Room', 4999, 'Most Popular', 'assets/images/room1.jpg', '2 Adults, 1 Child', '1 King Bed', '650 sq.ft', 'Breathtaking sunset ocean views, private balcony, marble luxury bathroom, and 24/7 dedicated room service.', '["Ocean View","Free High-Speed Wi-Fi","Breakfast Included","Private Balcony","Jacuzzi"]', 'Available'),
(2, 'Royal Presidential Penthouse', 'Penthouse', 12999, 'Ultra Luxury', 'assets/images/room2.jpg', '4 Adults', '2 King Beds', '1250 sq.ft', 'Panaromic horizon sea vistas, crystal chandelier lounge, grand master bedroom, private bar, and personal butler service.', '["Panoramic Sea View","Private Butler","Private Bar & Lounge","Airport Chauffeur","Complimentary Spa"]', 'Available'),
(3, 'Tropical Garden Pool Villa', 'Garden Villa', 7499, 'Private Pool', 'assets/images/room3.jpg', '3 Adults', '1 King Bed + 1 Daybed', '850 sq.ft', 'Private temperature-controlled plunge pool, enclosed tropical botanical courtyard, and open-air rain shower.', '["Private Plunge Pool","Tropical Garden View","Espresso Bar","24/7 In-Villa Dining","Complimentary Yoga"]', 'Available')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- Initial Admin Seed (login: admin@hotel.com | password: admin123)
-- NOTE: This SQL file is optional. config/database.php auto-creates all tables
-- and the admin account on first run with password_hash('admin123', PASSWORD_BCRYPT).
-- If you import this SQL, the admin password hash below works with password: admin123
INSERT INTO `admins` (`id`, `name`, `username`, `email`, `password`, `role`)
VALUES
(1, 'Hotel Manager', 'admin', 'admin@hotel.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Super Admin')
ON DUPLICATE KEY UPDATE `username` = VALUES(`username`);
-- Default password hash above = "password" (Laravel default)
-- RECOMMENDED: Login at /admin and change password from My Profile page after first login.
