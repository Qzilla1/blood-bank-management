<?php
/**
 * Authentication Helper Middleware
 * Manages admin sessions and page access security
 */

// Start session if not already active
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if the administrator is logged in.
 * If not, redirect immediately to login.php.
 */
function check_login() {
    if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
        $_SESSION['error_message'] = "Unauthorized access. Please login first.";
        header("Location: login.php");
        exit();
    }
}

/**
 * Returns true if an administrator is authenticated in the current session.
 */
function is_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Retrieve the active administrator's full name.
 */
function get_admin_fullname() {
    return $_SESSION['admin_fullname'] ?? 'System Administrator';
}

/**
 * Retrieve the active administrator's username.
 */
function get_admin_username() {
    return $_SESSION['admin_username'] ?? 'admin';
}
?>
