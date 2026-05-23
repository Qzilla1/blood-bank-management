<?php
/**
 * Admin Logout Page
 * Terminates session and redirects to the login screen safely.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie if it exists
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the actual session
session_destroy();

// Start a brief new session to pass the success logout notification message
session_start();
$_SESSION['error_message'] = "You have been logged out successfully.";

header("Location: login.php");
exit();
?>
