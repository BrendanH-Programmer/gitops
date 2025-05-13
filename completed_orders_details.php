<?php
include_once 'db.php';
include_once 'auth.php';  // Ensure the user is logged in and authorized
include_once 'session_manager.php';

// Initialize database connection
$db = new Database();
$conn = $db->connect();

ensureAuthenticated();
ensureAdmin();
displayAdminLink();

// Check if `order_id` is provided in the URL
if (!isset($_GET['order_id']) || empty($_GET['order_id'])) {
    header("Location: error_page.php?error=Order not found");
    exit;
}

// Sanitize input to prevent SQL injection
$order_id = intval($_GET['order_id']);  // Sanitize the order ID

// Fetch general order details from the orders table, ensuring it's marked as 'completed'
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id AND status = 'completed'");
$orderStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
$orderStmt->execute();
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

// Ensure the order exists
if (!$order) {
    header("Location: error_page.php?error=Order not found.");
    exit;
}

// Fetch order items from the completed_orders table
$itemsStmt = $conn->prepare("SELECT * FROM completed_orders WHERE order_id = :order_id");
$itemsStmt->bindParam(':order_id', $order_id, PDO::PARAM_INT);
$itemsStmt->execute();
$orderItems = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// Ensure the order has items (safety check)
if (empty($orderItems)) {
    header("Location: error_page.php?error=Error with your products.");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?= htmlspecialchars($order['order_id']) ?></title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <!-- Back to Orders Button -->
<div class="mt-3">
    <a href="admin_completed_orders.php" class="btn btn-secondary">Back to Orders</a>
</div>

</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Order Details</h1>

        <!-- General Order Information -->
        <div class="card mb-4">
            <div class="card-header">
                <h5>General Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Order ID:</strong> <?= htmlspecialchars($order['order_id']) ?></p>
                <p><strong>Customer Name:</strong> <?= htmlspecialchars($order['billing_name']) ?></p>
                <p><strong>Billing Address:</strong> <?= htmlspecialchars($order['billing_address']) ?></p>
                <p><strong>Shipping Address:</strong> <?= htmlspecialchars($order['shipping_address']) ?></p>
                <p><strong>Payment Method:</strong> <?= htmlspecialchars($order['payment_method']) ?></p>
                <p><strong>Order Status:</strong> <?= ucfirst(htmlspecialchars($order['status'])) ?></p>
                <p><strong>Order Date:</strong> <?= date('d, F Y', strtotime($order['order_date'])) ?></p>
                <p><strong>Total Price:</strong> £<?= number_format($order['total_price'], 2) ?></p>
            </div>
        </div>

        <!-- Order Items -->
        <div class="card">
            <div class="card-header">
                <h5>Order Items</h5>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Product Name</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orderItems as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td><?= htmlspecialchars($item['quantity']) ?></td>
                            <td>£<?= number_format($item['price'], 2) ?></td>
                            <td>£<?= number_format($item['total'], 2) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Back to Completed Orders -->
        <a href="admin_completed_orders.php" class="btn btn-secondary mt-3">Back to Completed Orders</a>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
