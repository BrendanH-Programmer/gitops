t;
}

// Fetch the order details
$order_stmt = $conn->prepare("SELECT * FROM orders WHERE order_id = :order_id");
$order_stmt->execute(['order_id' => $order_id]);
$order = $order_stmt->fetch(PDO::FETCH_ASSOC);

// Check if the order exists
if (!$order) {
    header("Location: error_page.php?error=Order not found.");
    exit;
}

// Fetch the items in the order
$items_stmt = $conn->prepare("SELECT oi.*, p.name AS product_name, p.stock_quantity FROM order_items oi
                              JOIN products p ON oi.product_id = p.product_id WHERE oi.order_id = :order_id");
$items_stmt->execute(['order_id' => $order_id]);
$items = $items_stmt->fetchAll(PDO::FETCH_ASSOC);


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['mark_completed'])) {
        // Begin a transaction to ensure data consistency
        $conn->beginTransaction();
        try {
            foreach ($items as $item) {
                if ($item['quantity'] > $item['stock_quantity']) {
                    $conn->rollBack(); // Rollback the transaction if started
                    $insufficient_stock = true; // Flag to trigger the popup
                    break;
                }
            }

            if (isset($insufficient_stock) && $insufficient_stock) {
                // Set a session variable to trigger the JavaScript popup
                $_SESSION['insufficient_stock_error'] = 'Insufficient stock for one or more products. Would you like to order more stock?';
                header("Location: admin_orders.php");
                exit;
            }
            foreach ($items as $item) {
                // Insert the order items into the completed_orders table
                $insert_stmt = $conn->prepare("INSERT INTO completed_orders (order_id, product_id, name, quantity, price, total)
                                              VALUES (:order_id, :product_id, :name, :quantity, :price, :total)");
                $insert_stmt->execute([ 
                    'order_id' => $order_id,
                    'product_id' => $item['product_id'],
                    'name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'total' => $item['price'] * $item['quantity']
                ]);

                // Subtract the quantity from the stock_quantity in the products table
                $update_stock_stmt = $conn->prepare("UPDATE products 
                                                     SET stock_quantity = stock_quantity - :quantity 
                                                     WHERE product_id = :product_id");
                $update_stock_stmt->execute([
                    'quantity' => $item['quantity'],
                    'product_id' => $item['product_id']
                ]);
            }

            // Delete the order items from the order_items table after moving them
            $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = :order_id");
            $delete_items_stmt->execute(['order_id' => $order_id]);

            // Update the order status to "completed"
            $update_status_stmt = $conn->prepare("UPDATE orders SET status = 'completed' WHERE order_id = :order_id");
            $update_status_stmt->execute(['order_id' => $order_id]);

            // Commit the transaction
            $conn->commit();

            header("Location: admin_orders.php");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: error_page.php?error=Database error.");
            exit;
        }
    }

    if (isset($_POST['cancel_order'])) {
        try {
            // Delete the order items
            $delete_items_stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = :order_id");
            $delete_items_stmt->execute(['order_id' => $order_id]);

            // Update the order status to "cancelled"
            $update_status_stmt = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE order_id = :order_id");
            $update_status_stmt->execute(['order_id' => $order_id]);

            header("Location: admin_orders.php");
            exit;
        } catch (Exception $e) {
            header("Location: error_page.php?error=Error cancelling the order.");
            exit;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Back to Orders Button -->
<div class="mt-3">
    <a href="admin_orders.php" class="btn btn-secondary">Back to Orders</a>
</div>

</head>
<body>
    <div class="container mt-5">
        <h1 class="mb-4">Order Details (Order ID: <?= htmlspecialchars($order['order_id']) ?>)</h1>

        <div class="card mb-3">
            <div class="card-body">
                <h5><strong>Customer:</strong> <?= htmlspecialchars($order['billing_name']) ?></h5>
                <p><strong>Total Price:</strong> £<?= number_format($order['total_price'], 2) ?></p>
                <p><strong>Status:</strong> <?= htmlspecialchars($order['status']) ?></p>
            </div>
        </div>

        <?php if (empty($items)): ?>
            <!-- No items found -->
            <div class="alert alert-warning">
                <p>Stock no longer available for this order. Do you wish to cancel the order?</p>
                <form method="POST">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <button type="submit" name="cancel_order" class="btn btn-danger">Cancel Order</button>
                </form>
            </div>
        <?php else: ?>
            <!-- Display order items -->
            <h3>Items in this Order</h3>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product Name</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['product_name']) ?></td>
                        <td><?= htmlspecialchars($item['quantity']) ?></td>
                        <td>£<?= number_format($item['price'], 2) ?></td>
                        <td>£<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <form method="POST">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                <button type="submit" name="mark_completed" class="btn btn-success">Mark as Completed</button>
            </form>
        <?php endif; ?>
    </div>

    <?php if (isset($_SESSION['insufficient_stock_error'])): ?>
    <script type="text/javascript">
        // Show the confirmation dialog
        var userChoice = confirm("<?= $_SESSION['insufficient_stock_error']; ?>");
        if (userChoice) {
            // If the user clicks "OK", redirect to order_stock.php
            window.location.href = "order_stock.php";
        } else {
            // If the user clicks "Cancel", stay on the current page (admin_orders.php)
            window.location.href = "admin_orders.php";
        }
    </script>
    <?php
    // Clear the session variable after displaying the message
    unset($_SESSION['insufficient_stock_error']);
    ?>
<?php endif; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

<footer class="main-footer">
    <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
