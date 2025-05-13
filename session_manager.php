<?php
// Set strict and secure session configurations BEFORE session_start()
$lifetime = 1800; // Session lifetime: 30 minutes

session_set_cookie_params([
    'lifetime' => $lifetime,
    'domain' => 'sencldigitech.co.uk', // Adjust domain to your site's root
    'path' => '/bhenderson', // Ensure correct path
    'secure' => isset($_SERVER['HTTPS']), // Ensure cookies are only sent over HTTPS
    'samesite' => 'Strict', // Prevent CSRF attacks by restricting cookie sharing
    'httponly' => true // Prevent access to cookies via JavaScript
]);

// Start the session (only once)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check session initialization timestamp and manage session regeneration
if (!isset($_SESSION['createdAt'])) {
    // Initialize session timestamp if not already set
    $_SESSION['createdAt'] = time();
} else {
    // Regenerate session if it has exceeded its lifetime
    if (time() - $_SESSION['createdAt'] > $lifetime) {
        session_regenerate_id(true); // Regenerate session ID and delete old session
        $_SESSION['createdAt'] = time(); // Reset the timestamp
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Regenerate CSRF token to match new session
    }
    }

