<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
displayAdminLink();

// Get the order ID from the URL and validate it
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    header("Location: error_page.php?error=Invalid order.");
    exit;
}

$order_id = (int)$_GET['order_id']; // Ensure the order ID is an integer

// Connect to the database
$db = new Database();
$conn = $db->connect();

// Fetch order details securely using prepared statements
$stmt = $conn->prepare("
    SELECT * FROM orders
    WHERE order_id = :order_id AND user_id = (SELECT user_id FROM users WHERE username = :username)
");
$stmt->execute([
    ':order_id' => $order_id,
    ':username' => $_SESSION['username']
]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header("Location: error_page.php?error=Order not found or you don't have permission to view this order.");
    exit;
}

// Fetch cart items associated with the order securely
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
$stmt->execute([':order_id' => $order_id]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Confirmation</title>
    <link rel="stylesheet" href="styles.css">
    <!-- Security Headers -->
    <meta http-equiv="Content-Security-Policy" content="default-src 'self'; script-src 'self'; style-src 'self';">
</head>
<body>
    <header>
        <div>
            <h1>Tyne Brew Coffee</h1>
            <?php if (isset($_SESSION['username'])): ?>
                <span>Welcome, <?= htmlspecialchars($_SESSION['username']) ?></span>
                <a href="logout.php">Logout</a>
                <a href="profile.php">Profile</a>
            <?php else: ?>
                <a href="login.php">Login</a>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
        <a href="index.php">Shop</a>
    </header>

    <main class="order-history-container">
    <h1 style="color: #3e2723;">Order Confirmation</h1>

        <h2 style="color: #3e2723;">Order Details</h2>
        <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
        <p><strong>Name:</strong> <?= htmlspecialchars($order['billing_name']) ?></p>
        <p><strong>Billing Address:</strong> <?= htmlspecialchars($order['billing_address']) ?></p>
        <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
        <p><strong>Total Price:</strong> £<?= number_format($order['total_price'], 2) ?></p>
        <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']); ?></p>
        <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
