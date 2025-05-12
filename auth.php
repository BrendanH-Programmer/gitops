<?php
include_once 'db.php';

// Function to check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION['username']);
}

// Function to ensure user is logged in
function ensureAuthenticated() {
    if (!isLoggedIn()) {
        $redirectUrl = htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');
        header("Location: login.php?redirect=" . urlencode($redirectUrl));
        exit;
    }
}

// Function to check if the logged-in user is an admin
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Function to ensure the user is an admin
function ensureAdmin() {
    if (!isAdmin()) {
        http_response_code(403);
        header("Location: error_page.php?error=Access Denied"); // Redirect to error page
        exit;
    }
}

// Generate a single CSRF token if it doesn't already exist
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// CSRF Protection: Validate CSRF token on POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        http_response_code(403);
        header("Location: error_page.php?error=Invalid CSRF Token"); // Redirect to error page with an error message
        exit;
    }
}

// Function to display the admin link if the user is an admin
function displayAdminLink() {
    if (isAdmin()) {
        echo '<a href="admin_dashboard.php" class="admin-link">Go to Admin Dashboard</a>';
    }
}