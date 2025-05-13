<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
ensureAdmin();
displayAdminLink();

$db = new Database();
$conn = $db->connect();

// Function to fetch available products
function getAvailableProducts($conn) {
    $stmt = $conn->prepare("SELECT product_id, name, stock_quantity FROM products");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Initialize error message
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming you are passing product_id and quantities via POST
    $orderDetails = $_POST['order'];  // Expected format: {product_id: quantity}
    
    // Check if at least one item has a quantity greater than 0
    $validOrder = false;
    foreach ($orderDetails as $quantity) {
        if ($quantity > 0) {
            $validOrder = true;
            break;
        }
    }

    if (!$validOrder) {
        $errorMessage = "Can't order nothing, you need at least 1 product to place an order.";
    } else {
        try {
            $invoiceNumber = uniqid('INV-', true);  // Generate unique invoice number
            $totalCost = 0;

            // Begin database transaction
            $conn->beginTransaction();

            // Insert order details into the stock orders table and update stock
            foreach ($orderDetails as $productId => $quantity) {
                if ($quantity > 0) {
                    // Fetch product details
                    $stmt = $conn->prepare("SELECT price, stock_quantity FROM products WHERE product_id = ?");
                    $stmt->execute([$productId]);
                    $product = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Calculate total cost
                    $totalCost += $product['price'] * $quantity;

                    // Update stock quantity in products table
                    $newStockQuantity = $product['stock_quantity'] + $quantity;
                    $updateStmt = $conn->prepare("UPDATE products SET stock_quantity = ? WHERE product_id = ?");
                    $updateStmt->execute([$newStockQuantity, $productId]);

                    // Insert stock order into orders table (you may want to create a `stock_orders` table)
                    $insertStmt = $conn->prepare("INSERT INTO stock_orders (invoice_number, product_id, quantity, order_date) 
                                                  VALUES (?, ?, ?, NOW())");
                    $insertStmt->execute([$invoiceNumber, $productId, $quantity]);
                }
            }

            // Commit transaction
            $conn->commit();
?>
<div class="container mt-4">
    <div class="confirmation-box shadow-lg p-4 rounded">
        <h4 class="confirmation-heading text-center mb-4"><i class="bi bi-check-circle"></i> Order Successfully Placed!</h4>
        <p class="confirmation-text">Your stock order has been successfully processed. Below are the details for your reference:</p>
        <div class="confirmation-details mb-4">
            <ul>
                <li><strong>Invoice Number:</strong> <?= $invoiceNumber; ?></li>
                <li><strong>Total Cost:</strong> £<?= number_format($totalCost, 2); ?></li>
            </ul>
        </div>
        <hr>
        <div class="confirmation-actions text-center">
            <a href="order_stock.php" class="btn btn-primary mb-3">Place Another Order</a>
        </div>
    </div>
</div>
<?php
            exit;  // End script execution after displaying confirmation and buttons
        } catch (Exception $e) {
            // Rollback if there is an error during the transaction
            $conn->rollBack();
            $errorMessage = "An error occurred while processing the order. Please try again later.";
        }
    }
}


$products = getAvailableProducts($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Order Stock</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css">
</head>
<body>
<div class="container mt-4">
    <h1 class="mb-4">Order More Stock</h1>

    <!-- Display error message if no items are selected -->
    <?php if ($errorMessage) { ?>
        <div class="alert alert-danger"><?= htmlspecialchars($errorMessage); ?></div>
    <?php } ?>

    <form method="post" action="order_stock.php">
        <!-- Include the CSRF token as a hidden field -->
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Product Name</th>
                    <th>Available Stock</th>
                    <th>Quantity to Order</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product) { ?>
                    <tr>
                        <td><?= htmlspecialchars($product['name']); ?></td>
                        <td><?= $product['stock_quantity']; ?></td>
                        <td>
                            <input type="number" name="order[<?= $product['product_id']; ?>]" min="0" value="0" class="form-control" />
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <button type="submit" class="btn btn-success">Place Order</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
