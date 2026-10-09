<?php
/**
 * Auth Helper File
 * ----------------
 * Guest sessions and authentication management
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_user_logged_in() {
    return isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true;
}

function get_logged_user() {
    if (is_user_logged_in()) {
        return [
            'name'  => $_SESSION['user_name'] ?? 'Guest',
            'email' => $_SESSION['user_email'] ?? '',
            'phone' => $_SESSION['user_phone'] ?? ''
        ];
    }
    return null;
}
?>
