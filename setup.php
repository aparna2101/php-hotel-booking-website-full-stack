<?php
/**
 * One-Click Admin Account Setup & Reset Utility
 * Visit this script in browser: http://your-domain/setup.php
 */
require_once 'config/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Setup | Hotel Luxury</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0b132b; color: #fff; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: #1c2541; padding: 40px; border-radius: 12px; border: 1px solid #c5a880; box-shadow: 0 10px 30px rgba(0,0,0,0.5); text-align: center; max-width: 480px; width: 90%; }
        h1 { color: #e0b973; margin-top: 0; }
        .details { background: #0b132b; padding: 15px; border-radius: 8px; margin: 20px 0; font-family: monospace; font-size: 1.1rem; border-left: 4px solid #e0b973; text-align: left; }
        .btn { display: inline-block; background: linear-gradient(135deg, #c5a880, #e0b973); color: #0b132b; padding: 12px 24px; border-radius: 6px; text-decoration: none; font-weight: bold; margin-top: 10px; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
<div class="card">
<?php
if (!$db_connected || !$pdo) {
    echo "<h1 style='color:#ff6b6b;'>Database Connection Error</h1>";
    echo "<p>Could not connect to database. Please check config/database.php</p>";
} else {
    try {
        // Ensure table exists
        $pdo->exec("
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

        $admin_email = 'admin@hotel.com';
        $admin_user  = 'admin';
        $admin_pass  = 'admin123';
        $hashed_pw   = password_hash($admin_pass, PASSWORD_BCRYPT);

        // Delete any corrupt/old admin records and insert clean
        $pdo->exec("TRUNCATE TABLE `admins`");
        $stmt = $pdo->prepare("INSERT INTO `admins` (`id`, `name`, `username`, `email`, `password`, `role`) VALUES (1, 'Hotel Manager', ?, ?, ?, 'Super Admin')");
        $stmt->execute([$admin_user, $admin_email, $hashed_pw]);

        echo "<h1>&#10004; Admin Account Ready!</h1>";
        echo "<p style='color:#a0aec0;'>A fresh administrator account has been created successfully.</p>";
        echo "<div class='details'>";
        echo "<strong>Portal:</strong> admin/login.php<br>";
        echo "<strong>Email:</strong> <span style='color:#48bb78;'>" . htmlspecialchars($admin_email) . "</span><br>";
        echo "<strong>Password:</strong> <span style='color:#48bb78;'>" . htmlspecialchars($admin_pass) . "</span>";
        echo "</div>";
        echo "<a class='btn' href='admin/login.php'>Login to Admin Portal &rarr;</a>";
    } catch (Exception $e) {
        echo "<h1 style='color:#ff6b6b;'>Setup Error</h1>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>
</div>
</body>
</html>
