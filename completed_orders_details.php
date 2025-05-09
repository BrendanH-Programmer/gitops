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