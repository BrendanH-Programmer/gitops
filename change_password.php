);
        $stored_password = $stmt->fetchColumn();

        if ($stored_password && password_verify($current_password, $stored_password)) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
            if ($stmt->execute([$hashed_password, $username])) {
                // Set a success message
                $_SESSION['success_message'] = "Password changed successfully!";

                // Redirect to profile page
                header("Location: profile.php");
                exit();
            } else {
                echo '<div class="alert alert-danger">Failed to update the password. Please try again.</div>';
            }
        } else {
            echo '<div class="alert alert-danger">Incorrect current password.</div>';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <link rel="stylesheet" href="styles.css"> <!-- Link to your external CSS file -->
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
 

    <h2>Change Password</h2>

    <?php
    // Display success messages if available
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success_message']) . '</div>';
        unset($_SESSION['success_message']); // Clear message after displaying
    }
    ?>
<div class="form-container">
    <form action="" method="POST">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <div class="form-group">
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" placeholder="Enter your current password" required>
        </div>

        <div class="form-group">
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter your new password" required>
        </div>

        <div class="form-group">
            <label for="confirm_new_password">Confirm New Password:</label>
            <input type="password" id="confirm_new_password" name="confirm_new_password" placeholder="Confirm your new password" required>
        </div>

        <button type="submit" class="btn btn-primary">Change Password</button>
    </form>
</div>
</body>
<footer class="main-footer">
    <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
