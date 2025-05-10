<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

// Initialize database connection
$db = new Database();
$conn = $db->connect();

ensureAuthenticated();
ensureAdmin();
displayAdminLink();

// Function to get order history
function getOrderHistory($conn) {
    try {
        $stmt = $conn->prepare("SELECT o.orderID, oi.productID, p.name AS productName, oi.quantity, oi.price, oi.orderDate 
                                FROM orders o 
                                JOIN order_items oi ON o.orderID = oi.orderID 
                                JOIN products p ON oi.productID = p.id 
                                ORDER BY o.orderID DESC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location: error_page.php?error=Databse connection failed.");  // Redirect to the error page
        exit;
    }
}

// Function to get product count
function getProductCount($conn) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as totalProducts FROM products");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    } catch (PDOException $e) {        
        header("Location: error_page.php?error=Database connection failed.");  // Redirect to the error page
        exit;
    }
}

// Function to get order counts by status
function getOrderCounts($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM orders
            GROUP BY status
        ");
        $stmt->execute();
        $orderCounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare counts for different statuses
        $counts = [
            'pending' => 0,
            'completed' => 0,
            'cancelled' => 0,
            'total' => 0,
        ];

        foreach ($orderCounts as $row) {
            $counts[strtolower($row['status'])] = (int) $row['count'];
            $counts['total'] += (int) $row['count'];
        }

        return $counts;
    } catch (PDOException $e) {
        header("Location: error_page.php?error=Database connection failed.");  // Redirect to the error page
        exit;
    }
}

// Function to get revenue by order status
function getRevenueBreakdown($conn) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                status,
                SUM(total_price) as revenue
            FROM orders
            WHERE status IN ('pending', 'completed')
            GROUP BY status
        ");
        $stmt->execute();
        $revenueData = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Prepare revenue breakdown
        $revenueBreakdown = [
            'pending' => 0.00,
            'completed' => 0.00,
            'total' => 0.00,
        ];

        foreach ($revenueData as $row) {
            $revenueBreakdown[strtolower($row['status'])] = (float) ($row['revenue'] ?? 0.00);
            $revenueBreakdown['total'] += (float) ($row['revenue'] ?? 0.00);
        }

        return $revenueBreakdown;
    } catch (PDOException $e) {
        header("Location: error_page.php?error=Databse connection failed.");  // Redirect to the error page
        exit;
    }
}

// Function to get top-selling products
function getTopSellingProducts($conn, $limit = 5) {
    try {
        $stmt = $conn->prepare("
            SELECT 
                p.product_id AS product_id, 
                p.name AS name, 
                SUM(co.quantity) AS total_quantity
            FROM completed_orders co
            JOIN products p ON co.product_id = p.product_id
            GROUP BY p.product_id
            ORDER BY total_quantity DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT); // Ensure the limit is bound as an integer
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        header("Location: error_page.php?error=Database connection failed.");  // Redirect to the error page
        exit;
    }
}

// Handle form submission (placeholder for future actions)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'viewHistory':
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <div class="mt-3">
    <a href="index.php" class="btn btn-secondary">Back to Store</a>
</div>
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">Admin Dashboard</h1>

    <!-- Order History -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>View Orders</h5>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between">
                <a href="admin_orders.php" class="btn btn-primary">View Active Orders</a>
                <a href="admin_completed_orders.php" class="btn btn-success">View Completed Orders</a>
            </div>
        </div>
    </div>

    <!-- Product Count -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Products</h5>
        </div>
        <div class="card-body">
            <p>Total Products: <?= htmlspecialchars(getProductCount($conn)); ?></p>
            <div class="d-flex justify-content-between mt-3">
                <a href="admin_add_product.php" class="btn btn-success">Add Product</a>
                <a href="admin_delete_product.php" class="btn btn-danger">Delete Product</a>
                <a href="admin_edit_product.php" class="btn btn-warning">Edit Product</a>
                <a href="order_stock.php" class="btn btn-primary">Order More Stock</a>
            </div>
        </div>
    </div>

    <!-- Order Count -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Order Count</h5>
        </div>
        <div class="card-body">
            <?php $orderCounts = getOrderCounts($conn); ?>
            <p><strong>Pending Orders:</strong> <?= htmlspecialchars($orderCounts['pending']); ?></p>
            <p><strong>Completed Orders:</strong> <?= htmlspecialchars($orderCounts['completed']); ?></p>
            <p><strong>Canceled Orders:</strong> <?= htmlspecialchars($orderCounts['cancelled']); ?></p>
            <p><strong>Total Orders:</strong> <?= htmlspecialchars($orderCounts['total']); ?></p>
        </div>
    </div>

    <!-- Revenue Breakdown -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Revenue Breakdown</h5>
        </div>
        <div class="card-body">
            <?php $revenueBreakdown = getRevenueBreakdown($conn); ?>
            <p><strong>Pending Revenue:</strong> £<?= number_format($revenueBreakdown['pending'], 2); ?></p>
            <p><strong>Completed Revenue:</strong> £<?= number_format($revenueBreakdown['completed'], 2); ?></p>
            <p><strong>Total Revenue:</strong> £<?= number_format($revenueBreakdown['total'], 2); ?></p>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="card mb-3">
        <div class="card-header">
            <h5>Top-Selling Products</h5>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Product Name</th>
                    <th>Total Quantity Sold</th>
                </tr>
                </thead>
                <tbody>
                <?php
                $topProducts = getTopSellingProducts($conn);
                if (!empty($topProducts)) {
                    foreach ($topProducts as $key => $product) { ?>
                        <tr>
                            <td><?= htmlspecialchars($key + 1); ?></td>
                            <td><?= htmlspecialchars($product['name']); ?></td>
                            <td><?= htmlspecialchars($product['total_quantity']); ?></td>
                        </tr>
                    <?php }
                } else { ?>
                    <tr>
                        <td colspan="3">No sales data available.</td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>