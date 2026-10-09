<?php
/**
 * Global Configuration & Database Handler (MERN-style Single Config)
 * -------------------------------------------------------------------
 * All database and email settings live in this single file.
 */

// Environment Auto-Switcher (Localhost XAMPP vs InfinityFree Live Server)
$is_local = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost:') !== false;

if ($is_local) {
    // 1. LOCAL XAMPP ENVIRONMENT
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "hotel_db";
} else {
    // 2. INFINITYFREE LIVE PRODUCTION ENVIRONMENT
    $db_host = "sql308.infinityfree.com";
    $db_user = "if0_43133421";
    $db_pass = "J6Gw7Hm2Edu";
    $db_name = "if0_43133421_hotel_db";
}

// Gmail SMTP & OTP Settings
$mail_host      = "smtp.gmail.com";
$mail_port      = 465;
$mail_user      = "aparnachaurasia210103@gmail.com";
$mail_pass      = "mxhhelreupkenjuj";
$mail_from_name = "HOTEL Luxury Stay & Rooms";
$admin_email    = "aparnachaurasia210103@gmail.com";

// ============================================================================
// 2. DATABASE AUTO-INITIALIZER & CONNECTION
// ============================================================================
$pdo = null;
$db_connected = false;
$db_error = null;

try {
    // Step 1: Attempt direct database connection first (Works on InfinityFree, cPanel & live servers)
    try {
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $db_connected = true;
    } catch (PDOException $ex) {
        // Step 2: Fallback for local XAMPP to auto-create database if not exists
        $server_pdo = new PDO("mysql:host={$db_host};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $server_pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo = new PDO("mysql:host={$db_host};dbname={$db_name};charset=utf8mb4", $db_user, $db_pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
        $db_connected = true;
    }

    // Step 4: Ensure Tables Exist (Auto Migrations)
    $pdo->exec("
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
          `status` ENUM('Confirmed', 'Pending', 'Cancelled') DEFAULT 'Confirmed',
          `notes` TEXT DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          INDEX (`email`),
          INDEX (`booking_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

        CREATE TABLE IF NOT EXISTS `admins` (
          `id` INT AUTO_INCREMENT PRIMARY KEY,
          `name` VARCHAR(100) NOT NULL,
          `username` VARCHAR(80) NOT NULL UNIQUE,
          `email` VARCHAR(160) NOT NULL UNIQUE,
          `password` VARCHAR(255) NOT NULL,
          `role` VARCHAR(40) DEFAULT 'admin',
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    // Auto-migrate new hotel booking columns if table was created previously
    try {
        $existing_cols = $pdo->query("SHOW COLUMNS FROM `bookings`")->fetchAll(PDO::FETCH_COLUMN);
        $new_hotel_cols = [
            'adults'              => "INT DEFAULT 2",
            'children'            => "INT DEFAULT 0",
            'id_type'             => "VARCHAR(50) DEFAULT 'Aadhaar Card'",
            'id_number'           => "VARCHAR(80) DEFAULT NULL",
            'city'                => "VARCHAR(80) DEFAULT NULL",
            'state'               => "VARCHAR(80) DEFAULT NULL",
            'country'             => "VARCHAR(80) DEFAULT 'India'",
            'arrival_time'        => "VARCHAR(50) DEFAULT '12:00 PM - 02:00 PM'",
            'visit_purpose'       => "VARCHAR(80) DEFAULT 'Vacation / Leisure'",
            'meal_pref'           => "VARCHAR(80) DEFAULT 'No Preference'",
            'cancellation_reason' => "TEXT DEFAULT NULL",
            'cancelled_at'        => "DATETIME DEFAULT NULL"
        ];
        foreach ($new_hotel_cols as $col_name => $col_type) {
            if (!in_array($col_name, $existing_cols)) {
                $pdo->exec("ALTER TABLE `bookings` ADD COLUMN `{$col_name}` {$col_type}");
            }
        }
        // Ensure status column can store Checked In, Checked Out, etc.
        $pdo->exec("ALTER TABLE `bookings` MODIFY COLUMN `status` VARCHAR(40) DEFAULT 'Confirmed'");
    } catch (Exception $e) {}

    // Auto-seed rooms if empty
    $check_rooms = $pdo->query("SELECT COUNT(*) FROM `rooms`")->fetchColumn();
    if ($check_rooms == 0) {
        $stmt = $pdo->prepare("INSERT INTO `rooms` (`id`, `title`, `category`, `price`, `badge`, `image`, `capacity`, `bed`, `size`, `description`, `amenities`, `status`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([1, 'Oceanfront Master Room', 'Ocean View Room', 4999, 'Most Popular', 'assets/images/room1.jpg', '2 Adults, 1 Child', '1 King Bed', '650 sq.ft', 'Breathtaking sunset ocean views, private balcony, marble luxury bathroom, and 24/7 dedicated room service.', '["Ocean View","Free High-Speed Wi-Fi","Breakfast Included","Private Balcony","Jacuzzi"]', 'Available']);
        $stmt->execute([2, 'Royal Presidential Penthouse', 'Penthouse', 12999, 'Ultra Luxury', 'assets/images/room2.jpg', '4 Adults', '2 King Beds', '1250 sq.ft', 'Panaromic horizon sea vistas, crystal chandelier lounge, grand master bedroom, private bar, and personal butler service.', '["Panoramic Sea View","Private Butler","Private Bar & Lounge","Airport Chauffeur","Complimentary Spa"]', 'Available']);
        $stmt->execute([3, 'Tropical Garden Pool Villa', 'Garden Villa', 7499, 'Private Pool', 'assets/images/room3.jpg', '3 Adults', '1 King Bed + 1 Daybed', '850 sq.ft', 'Private temperature-controlled plunge pool, enclosed tropical botanical courtyard, and open-air rain shower.', '["Private Plunge Pool","Tropical Garden View","Espresso Bar","24/7 In-Villa Dining","Complimentary Yoga"]', 'Available']);
    }

    // Auto-seed default admin if empty (admin / admin123)
    $check_admin = $pdo->query("SELECT COUNT(*) FROM `admins`")->fetchColumn();
    if ($check_admin == 0) {
        $stmt = $pdo->prepare("INSERT INTO `admins` (`name`, `username`, `email`, `password`, `role`) VALUES (?, ?, ?, ?, ?)");
        $admin_pw = password_hash('admin123', PASSWORD_BCRYPT);
        $stmt->execute(['Hotel Manager', 'admin', 'admin@hotel.com', $admin_pw, 'Super Admin']);
    }

} catch (PDOException $e) {
    $db_connected = false;
    $db_error = $e->getMessage();
}

// Fallback data if MySQL is temporarily not running in XAMPP
$sample_rooms = [
    [
        'id'          => 1,
        'title'       => 'Oceanfront Master Room',
        'category'    => 'Ocean View Room',
        'price'       => 4999,
        'badge'       => 'Most Popular',
        'image'       => 'assets/images/room1.jpg',
        'capacity'    => '2 Adults, 1 Child',
        'bed'         => '1 King Bed',
        'size'        => '650 sq.ft',
        'description' => 'Breathtaking sunset ocean views, private balcony, marble luxury bathroom, and 24/7 dedicated room service.',
        'amenities'   => ['Ocean View', 'Free High-Speed Wi-Fi', 'Breakfast Included', 'Private Balcony', 'Jacuzzi']
    ],
    [
        'id'          => 2,
        'title'       => 'Royal Presidential Penthouse',
        'category'    => 'Penthouse',
        'price'       => 12999,
        'badge'       => 'Ultra Luxury',
        'image'       => 'assets/images/room2.jpg',
        'capacity'    => '4 Adults',
        'bed'         => '2 King Beds',
        'size'        => '1250 sq.ft',
        'description' => 'Panaromic horizon sea vistas, crystal chandelier lounge, grand master bedroom, private bar, and personal butler service.',
        'amenities'   => ['Panoramic Sea View', 'Private Butler', 'Private Bar & Lounge', 'Airport Chauffeur', 'Complimentary Spa']
    ],
    [
        'id'          => 3,
        'title'       => 'Tropical Garden Pool Villa',
        'category'    => 'Garden Villa',
        'price'       => 7499,
        'badge'       => 'Private Pool',
        'image'       => 'assets/images/room3.jpg',
        'capacity'    => '3 Adults',
        'bed'         => '1 King Bed + 1 Daybed',
        'size'        => '850 sq.ft',
        'description' => 'Private temperature-controlled plunge pool, enclosed tropical botanical courtyard, and open-air rain shower.',
        'amenities'   => ['Private Plunge Pool', 'Tropical Garden View', 'Espresso Bar', '24/7 In-Villa Dining', 'Complimentary Yoga']
    ]
];

function get_all_rooms() {
    global $pdo, $db_connected, $sample_rooms;
    if ($db_connected && $pdo) {
        try {
            $stmt = $pdo->query("SELECT * FROM `rooms` ORDER BY id ASC");
            $rows = $stmt->fetchAll();
            if (!empty($rows)) {
                foreach ($rows as &$r) {
                    if (is_string($r['amenities'])) {
                        $decoded = json_decode($r['amenities'], true);
                        $r['amenities'] = is_array($decoded) ? $decoded : explode(',', $r['amenities']);
                    }
                }
                return $rows;
            }
        } catch (Exception $e) {}
    }
    return $sample_rooms;
}
