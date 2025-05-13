<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'cart_function.php';
include_once 'auth.php';

isLoggedIn();
displayAdminLink();

$db = new Database();
$conn = $db->connect();

// Check if product ID is passed in the query string
if (isset($_GET['id'])) {
    $product_id = $_GET['id'];

    // Ensure that product ID is an integer
    $product_id = intval($product_id); 

    // Fetch product details from the database using a prepared statement
    $query = "SELECT * FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Check if product exists
    if (!$product) {
        header("Location: error_page.php?error=Product not found.");
        exit;
    }
} else {
    header("Location: error_page.php?error=Product not found.");
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($product['name']) ?></title>
    <link rel="stylesheet" href="styles.css">
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

    <div class="product-details">
        <!-- Product Image -->
        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">

        <!-- Product Title -->
        <h3><?= htmlspecialchars($product['name']) ?></h3>

        <!-- Product Description -->
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <!-- Product Price -->
        <p>Price: £<?= number_format($product['price'], 2) ?></p>

        <?php
        // Fetch the available stock for the product
        $max_quantity = determineQuantity($product['product_id'], $conn);
        ?>

        <!-- Add to Cart Form -->
        <form action="shopping_cart.php" method="POST">
            <!-- CSRF token -->
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">

            <label for="quantity">Quantity:</label>
            <input 
                type="number" 
                id="quantity" 
                name="quantity" 
                value="1" 
                min="1" 
                max="<?= $product['stock_quantity'] ?>" 
                required>

            <p>Available stock: <?= $product['stock_quantity'] ?> units</p>

            <button type="submit">Add to Cart</button>
        </form>
    </div>


</body>
    <footer class="main-footer">
        <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
    </footer>
</html>
