<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
ensureAdmin();
displayAdminLink();

// Initialize the database connection
$db = new Database();
$conn = $db->connect();

// Fetch all products with pagination
$limit = 10; // Number of products per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Message initialization
$message = "";

// Retrieve message from session if available
if (!empty($_SESSION['message'])) {
    $message = $_SESSION['message']; // Retrieve the message
    unset($_SESSION['message']); // Clear the message from the session
}


try {
    // Fetch products for the current page
    $stmt = $conn->prepare("SELECT * FROM products LIMIT :limit OFFSET :offset");
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get total number of products for pagination
    $countStmt = $conn->prepare("SELECT COUNT(*) FROM products");
    $countStmt->execute();
    $totalProducts = $countStmt->fetchColumn();
    $totalPages = ceil($totalProducts / $limit);
} catch (PDOException $e) {
    header("Location: error_page.php?error=Databse connection failed.");
    exit;
}

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product_id'])) {

    $product_id = $_POST['delete_product_id'];

    try {
        // Check if the product exists before deletion
        $stmt = $conn->prepare("SELECT * FROM products WHERE product_id = :product_id");
        $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            // Product exists, proceed with deletion
            $stmt = $conn->prepare("DELETE FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();

            // Set a success message and refresh the page
            $_SESSION['message'] = "Product deleted successfully!";
            header("Location: " . $_SERVER['PHP_SELF'] . "?page=" . $page);
            exit;
        } else {
            $message = "Product not found.";
        }
    } catch (PDOException $e) {
        header("Location: error_page.php?error=Error deleting product.");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Products</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <header class="mb-4">
        <h1>Admin Panel - Manage Products</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
    </header>

    <!-- Display success or error message -->
    <?php if (!empty($message)): ?>
        <div class="alert <?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
            <?= htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <main>
        <h2>Product List</h2>

        <!-- Pagination Controls -->
        <nav aria-label="Page navigation">
            <ul class="pagination">
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?= $i; ?>"><?= $i; ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>

        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Product ID</th>
                    <th>Name</th>
                    <th>Description</th>
                    <th>Price (£)</th>
                    <th>Stock</th>
                    <th>Category ID</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($products)): ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td><?= htmlspecialchars($product['product_id']) ?></td>
                            <td><?= htmlspecialchars($product['name']) ?></td>
                            <td><?= htmlspecialchars($product['description']) ?></td>
                            <td>£<?= number_format($product['price'], 2) ?></td>
                            <td><?= htmlspecialchars($product['stock_quantity']) ?></td>
                            <td><?= htmlspecialchars($product['category_id'] ?? 'N/A') ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                                    <input type="hidden" name="delete_product_id" value="<?= htmlspecialchars($product['product_id']) ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this product?')">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No products found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>