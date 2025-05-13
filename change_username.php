<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
displayAdminLink(); 

// Start session and retrieve current username
$username = $_SESSION['username'] ?? ''; // Use null coalescing operator for safety

// Initialize the database connection
$db = new Database();
$conn = $db->connect();
if ($conn === null) {
    header("Location: error_page.php?error=Database error.");
    exit;
}

// Function to display messages
function showMessage($message, $type = 'success') {
    echo "<div class='alert alert-$type'>" . htmlspecialchars($message) . "</div>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Sanitize user input
    $new_username = htmlspecialchars(trim($_POST['new_username']));
    $confirm_new_username = htmlspecialchars(trim($_POST['confirm_new_username']));
    $re_enter_password = htmlspecialchars(trim($_POST['re_enter_password']));

    // Validate input
    if (empty($new_username) || empty($confirm_new_username) || empty($re_enter_password)) {
        showMessage("All fields are required.", 'danger');
    } elseif ($new_username !== $confirm_new_username) {
        showMessage("New usernames don't match.", 'danger');
    } else {
        // Verify current password
        $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $stored_password = $stmt->fetchColumn();

        if (!password_verify($re_enter_password, $stored_password)) {
            showMessage("Incorrect current password.", 'danger');
        } else {
            // Check if the new username is already taken
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$new_username]);
            $count = $stmt->fetchColumn();

            if ($count > 0) {
                showMessage("Username already exists. Please choose another.", 'danger');
            } else {
                // Update the username in the database
                $stmt = $conn->prepare("UPDATE users SET username = ? WHERE username = ?");
                if ($stmt->execute([$new_username, $username])) {
                    // Update session and set success message
                    $_SESSION['username'] = $new_username;
                    $_SESSION['success_message'] = "Username changed successfully!";
                    
                    // Redirect to profile page
                    header("Location: profile.php");
                    exit();
                } else {
                    showMessage("Failed to update username. Please try again.", 'danger');
                }
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
    <title>Change Username</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to external CSS file -->
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
    <h2>Change Username</h2>

    <?php
    // Display success or error messages if available
    if (isset($_SESSION['success_message'])) {
        showMessage($_SESSION['success_message']);
        unset($_SESSION['success_message']);
    }
    ?>
<div class="form-container">
    <form action="" method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <div class="form-group">
            <label for="current_username">Current Username:</label>
            <p><?= htmlspecialchars($username); ?></p>
        </div>

        <div class="form-group">
            <label for="new_username">New Username:</label>
            <input type="text" id="new_username" name="new_username" placeholder="Enter new username" required>
        </div>

        <div class="form-group">
            <label for="confirm_new_username">Confirm New Username:</label>
            <input type="text" id="confirm_new_username" name="confirm_new_username" placeholder="Confirm new username" required>
        </div>

        <div class="form-group">
            <label for="re_enter_password">Current Password:</label>
            <input type="password" id="re_enter_password" name="re_enter_password" placeholder="Enter current password" required>
        </div>

        <button type="submit">Change Username</button>
    </form>
</div>
</body>
<footer class="main-footer">
    <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
