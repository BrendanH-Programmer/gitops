<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
displayAdminLink();

// Instantiate DB class and get connection
$db = new Database();
$conn = $db->connect();

// Get user data from the database
$username = $_SESSION['username'];

try {
    // Fetch user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        header("Location: error_page.php?error=User not found.");
        exit();
    }

    // Get the user_id for the logged-in user
    $user_id = $user['user_id'];

    // Fetch the order history for the logged-in user, ordered by most recent
    $stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Check if no orders are found
    if (empty($orders)) {
        echo "<p>No orders yet.</p>";
    }

} catch (PDOException $e) {
    header("Location: error_page.php?error=Database error.");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History</title>
    <link rel="stylesheet" href="styles.css"> <!-- External CSS file -->
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self';">
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
    
    <main class="order-history-container">
        <h2 style="color: #3e2723;">Order History</h2>
        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-box">
                    <!-- Order ID and Status -->
                    <div class="order-detail">
                        <strong>Order ID:</strong> 
                        <span class="order-id"><?= htmlspecialchars($order['order_id']); ?></span>
                    </div>

                    <div class="order-detail">
                        <strong>Status:</strong>
                        <span class="order-status <?= strtolower($order['status']); ?>">
                            <?= ucfirst($order['status']); ?>
                        </span>
                    </div>

                    <div class="order-detail">
                        <strong>Date/Time:</strong> <?= htmlspecialchars($order['order_date']); ?>
                    </div>

                    <!-- Ordered Items -->
                    <div class="order-detail">
                        <strong>Items Ordered:</strong>
                        <?php
                        // Fetch items for this order
                        $order_id = $order['order_id'];
                        $stmt = $conn->prepare("SELECT oi.*, p.name, p.price FROM order_items oi 
                                                JOIN products p ON oi.product_id = p.product_id 
                                                WHERE oi.order_id = ?");
                        $stmt->execute([$order_id]);
                        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        // Display each item in this order
                        foreach ($items as $item):
                        ?>
                            <p>Product: <?= htmlspecialchars($item['name']); ?> | 
                               Quantity: <?= $item['quantity']; ?> | 
                               Price: £<?= number_format($item['price'], 2); ?></p>
                        <?php endforeach; ?>
                    </div>

                    <!-- Additional Order Information -->
                    <div class="order-detail">
                        <strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']); ?>
                    </div>

                    <div class="order-detail">
                        <strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?>
                    </div>

                    <div class="order-detail">
                        <strong>Total Price:</strong> £<?= number_format($order['total_price'], 2); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>You have not placed any orders yet.</p>
        <?php endif; ?>
    </main>

    <footer class="main-footer">
        <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
    </footer>
</body>
</html>
