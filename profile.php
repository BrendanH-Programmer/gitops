<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
displayAdminLink();

// Instantiate DB class and get connection
$db = new Database();
$conn = $db->connect();

// Get the logged-in username
$username = $_SESSION['username'];

try {
    // Fetch user data from the database
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: error_page.php?error=User not found.");
        exit();
    }
} catch (PDOException $e) {
    header("Location: error_page.php?error=Database error.");
    exit;
}

// Input validation function
function validateInput($input) {
    $input = trim($input);
    $input = htmlspecialchars($input);
    return $input;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
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
    
    <main class="profile-container">
        <h2><?= htmlspecialchars($user['username']); ?>'s Profile</h2>

        <!-- Display success message if set -->
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success_message'] . '</div>';
            unset($_SESSION['success_message']);
        }
        ?>
        <div class="user-details">
            <table>
                <tr><th>Username:</th><td><?= htmlspecialchars($user['username']); ?></td></tr>
                <tr><th>Email:</th><td><?= htmlspecialchars($user['email']); ?></td></tr>
                <tr><th>Password:</th><td>********</td></tr>
            </table>
        </div>

        <h3>User Settings</h3>
        <div class="settings-buttons">
            <a href="order_history.php" class="button">View Order History</a>
            <a href="change_username.php" class="button">Change Username</a>
            <a href="update_email.php" class="button">Update Email</a>
            <a href="change_password.php" class="button">Change Password</a>
        </div>
    </main>

</body>
<footer class="main-footer">
        <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
    </footer>
</html>
