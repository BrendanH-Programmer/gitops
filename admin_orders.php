<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
ensureAdmin();
displayAdminLink();

// Connect to the database
$db = new Database();
$conn = $db->connect();


try {
    // Fetch all pending orders securely
    $stmt = $conn->prepare("SELECT order_id, billing_name, total_price FROM orders WHERE status = :status");
    $stmt->bindValue(':status', 'pending', PDO::PARAM_STR);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header("Location: error_page.php?error=Error fetching orders.");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Orders</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Admin: Manage Orders</h1>

        <!-- Display Feedback Message -->
        <?php if (isset($_GET['message'])): ?>
            <div class="alert <?= strpos($_GET['message'], 'success') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?= htmlspecialchars($_GET['message']) ?>
            </div>
        <?php endif; ?>

        <!-- Orders Table -->
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Order ID</th>
                    <th>Customer</th>
                    <th>Total Price (£)</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="4" class="text-center">No pending orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                            <td><?= htmlspecialchars($order['billing_name']) ?></td>
                            <td>£<?= number_format($order['total_price'], 2) ?></td>
                            <td>
                                <!-- View Order Button -->
                                <a href="update_order_status.php?order_id=<?= urlencode($order['order_id']) ?>" class="btn btn-primary btn-sm">
                                    View Order
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>