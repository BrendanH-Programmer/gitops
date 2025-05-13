<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
displayAdminLink();

// Initialize the database connection
$db = new Database();
$conn = $db->connect();

// Retrieve the username from the session
$username = $_SESSION['username'];

// Check if the database connection is established
if ($conn === null) {
    header("Location: error_page.php?error=Database error.");
    exit;
}

// Function to display success messages
function showMessage($message) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// Function to display error messages
function showError($error) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get user inputs
    $new_email = trim($_POST['new_email']);
    $confirm_new_email = trim($_POST['confirm_new_email']);
    $re_enter_current_password = $_POST['re_enter_current_password'];

    // Check if the current password is correct
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $stored_password = $stmt->fetchColumn();

    if (!password_verify($re_enter_current_password, $stored_password)) {
        showError("Incorrect current password.");
    } elseif (empty($new_email) || empty($confirm_new_email) || empty($re_enter_current_password)) {
        showError("All fields are required.");
    } elseif ($new_email !== $confirm_new_email) {
        showError("Email addresses don't match.");
    } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        showError("Invalid email format.");
    } else {
        // Check if the new email is already in use
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$new_email]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            showError("Email already exists.");
        } else {
            // Update the user's email in the database
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE username = ?");
            if ($stmt->execute([$new_email, $username])) {
                // Update session and set success message
                $_SESSION['email'] = $new_email;
                $_SESSION['success_message'] = "Email updated successfully!";
                header("Location: profile.php"); // Redirect to profile
                exit();
            } else {
                showError("Failed to update email. Please try again.");
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Email</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to external CSS -->
    <script>
        // Live validation for email matching
        document.addEventListener("DOMContentLoaded", () => {
            const newEmailInput = document.getElementById('new_email');
            const confirmEmailInput = document.getElementById('confirm_new_email');
            confirmEmailInput.addEventListener('input', function () {
                if (newEmailInput.value !== confirmEmailInput.value) {
                    confirmEmailInput.setCustomValidity('Email addresses must match.');
                } else {
                    confirmEmailInput.setCustomValidity('');
                }
            });
        });
    </script>
</head>
<header>
    <div class="header-container">
        <!-- Left side: Profile/Register link -->
        <div class="header-left">
            <?php if (isLoggedIn()) : ?>
                <a href="profile.php">Profile</a>
            <?php else : ?>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>

        <!-- Centered: Tyne Brew Coffee and Welcome Message -->
        <div class="header-center">
            <h1>Tyne Brew Coffee</h1>
            <?php if (isLoggedIn()) : ?>
                <span>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <?php endif; ?>
        </div>

        <!-- Right side: Shopping Cart and Login/Logout -->
        <div class="header-right">
            <a href="index.php">Store</a>
            <a href="shopping_cart.php">Shopping Cart</a>
            <?php if (isLoggedIn()) : ?>
                <a href="logout.php">Logout</a>
            <?php else : ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>
<body>

    <h2>Update Email</h2>
    <?php
    // Display session messages
    if (isset($_SESSION['success_message'])) {
        showMessage($_SESSION['success_message']);
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        showError($_SESSION['error_message']);
        unset($_SESSION['error_message']);
    }
    ?>
<div class="form-container">
    <form action="" method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <div class="form-group">
            <label for="new_email">New Email:</label>
            <input type="email" id="new_email" name="new_email" placeholder="New Email" required>
        </div>

        <div class="form-group">
            <label for="confirm_new_email">Confirm New Email:</label>
            <input type="email" id="confirm_new_email" name="confirm_new_email" placeholder="Confirm New Email" required>
        </div>

        <div class="form-group">
            <label for="current_password">Password:</label>
            <input type="password" id="current_password" name="re_enter_current_password" placeholder="Current Password" required>
        </div>
        
        <button type="submit">Update Email</button>
    </form>
</div>
</body>

<footer class="main-footer">
    <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
