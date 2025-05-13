<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

// Database connection
$db = new Database();
$conn = $db->connect();

ensureAuthenticated();
displayAdminLink();

// Check if the cart is empty
if (empty($_SESSION['cart_item'])) {
    echo "Your cart is empty. Please add some items to your cart before proceeding to checkout.";
    header("Location: index.php");
    exit;
}

// Handle form submission
$error_message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $billing_name = $_POST['billing_name'] ?? null;
    $billing_number = $_POST['billing_number'] ?? null;
    $billing_street = $_POST['billing_street'] ?? null;
    $billing_city = $_POST['billing_city'] ?? null;
    $billing_postcode = $_POST['billing_postcode'] ?? null;
    $payment_method = $_POST['payment_method'] ?? null;
    $same_shipping_address = isset($_POST['same_shipping_address']) ? 1 : 0;

    // Initialize shipping address variable
    $shipping_address = null;

    // If shipping address is same as billing, copy the billing address
    if ($same_shipping_address) {
        $shipping_address = $billing_number . ' ' . $billing_street . ', ' . $billing_city . ', ' . $billing_postcode;
    } else {
        // If shipping address is different, retrieve the shipping address fields
        $shipping_number = $_POST['shipping_number'] ?? null;
        $shipping_street = $_POST['shipping_street'] ?? null;
        $shipping_city = $_POST['shipping_city'] ?? null;
        $shipping_postcode = $_POST['shipping_postcode'] ?? null;

        // Validate shipping fields
        if (!$shipping_number || !$shipping_street || !$shipping_city || !$shipping_postcode) {
            $error_message = "Please fill in all shipping address fields.";
        } else {
            // Concatenate shipping address if all fields are filled
            $shipping_address = $shipping_number . ' ' . $shipping_street . ', ' . $shipping_city . ', ' . $shipping_postcode;
        }
    }

    // Validate required billing fields
    if (!$billing_name || !$billing_number || !$billing_street || !$billing_city || !$billing_postcode || !$payment_method || !$shipping_address) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Combine billing address parts into a single string
        $billing_address = $billing_number . ' ' . $billing_street . ', ' . $billing_city . ', ' . $billing_postcode;

        // Get total price from the cart
        $total_price = calculateTotalPrice($_SESSION['cart_item']);

        // Get the user_id from the database using the session username
        try {
            $username = $_SESSION['username'];
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                $user_id = $user['user_id'];
            } else {
                throw new Exception("User not found.");
            }
        } catch (PDOException $e) {
            header("Location: error_page.php?error=Error retrieving user information.");
            exit;
        } catch (Exception $e) {
            header("Location: error_page.php?error=Database connection failed.");
            exit;
        }

        // Insert the order into the database
        try {
            $conn->beginTransaction();

            // Insert order data
            $stmt = $conn->prepare("
            INSERT INTO orders (user_id, billing_name, billing_address, shipping_address, payment_method, total_price, status)
            VALUES (:user_id, :billing_name, :billing_address, :shipping_address, :payment_method, :total_price, 'pending')
        ");
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':billing_name', $billing_name);
            $stmt->bindParam(':billing_address', $billing_address);
            $stmt->bindParam(':shipping_address', $shipping_address);
            $stmt->bindParam(':payment_method', $payment_method);
            $stmt->bindParam(':total_price', $total_price);
            $stmt->execute();

            // Get the generated order ID
            $order_id = $conn->lastInsertId();

            // Prepare the SQL statement to insert items into the 'order_items' table
            $stmt = $conn->prepare("
            INSERT INTO order_items (order_id, product_id, name, quantity, price, total)
            VALUES (:order_id, :product_id, :name, :quantity, :price, :total)
        ");

            foreach ($_SESSION['cart_item'] as $item) {
                // Check if required keys exist and are valid
                if (isset($item['product_id'], $item['name'], $item['quantity'], $item['price']) &&
                    !empty($item['product_id']) && !empty($item['name']) && !empty($item['quantity']) && !empty($item['price'])) {

                    $itemTotal = $item['price'] * $item['quantity'];
                    // Check if the product_id exists in the products table
                    $stmt_check = $conn->prepare("SELECT COUNT(*) FROM products WHERE product_id = :product_id");
                    $stmt_check->bindParam(':product_id', $item['product_id']);
                    $stmt_check->execute();
                    $product_exists = $stmt_check->fetchColumn();

                    if (!$product_exists) {
                        header("Location: error_page.php?error=Product not found.");
                        exit;
                    }

                    // Execute the statement for each valid item
                    $stmt->execute([
                        ':order_id' => $order_id,
                        ':product_id' => $item['product_id'],
                        ':name' => $item['name'],
                        ':quantity' => $item['quantity'],
                        ':price' => $item['price'],
                        ':total' => $itemTotal
                    ]);
                } else {
                    // Handle missing or invalid data for the item
                    header("Location: error_page.php?error=Product not found.");
                    exit;
                }
            }

            $conn->commit();

            // Clear the cart session
            $_SESSION['cart_item'] = [];

            // Redirect to the order confirmation page
            header("Location: order_confirmation.php?order_id=$order_id");
            exit;
        } catch (Exception $e) {
            $conn->rollBack();
            header("Location: error_page.php?error=Error proccessing order.");
            exit;
        }
    }
}

// Calculate total price from cart items
function calculateTotalPrice($cartItems) {
    $total = 0;
    foreach ($cartItems as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="styles.css">
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

    <script>
    // Toggle visibility of shipping address fields
    function toggleShippingAddress() {
        const checkbox = document.getElementById('same_shipping_address');
        const shippingFields = document.getElementById('shipping_fields');
        shippingFields.style.display = checkbox.checked ? 'none' : 'block';
        
        // If the shipping address is same, copy the billing address to the shipping address fields
        if (checkbox.checked) {
            document.getElementById('shipping_number').value = document.getElementById('billing_number').value;
            document.getElementById('shipping_street').value = document.getElementById('billing_street').value;
            document.getElementById('shipping_city').value = document.getElementById('billing_city').value;
            document.getElementById('shipping_postcode').value = document.getElementById('billing_postcode').value;
        } else {
            document.getElementById('shipping_number').value = '';
            document.getElementById('shipping_street').value = '';
            document.getElementById('shipping_city').value = '';
            document.getElementById('shipping_postcode').value = '';
        }
    }
    </script>
                <div>
                <a href="shopping_cart.php" class="go-back-button">Cancel</a>
                </div>
    <main>
        <form action="checkout.php" method="POST">
            <h3>Billing Address</h3>
            <label for="billing_name">Name:</label>
            <input type="text" name="billing_name" required>
            <label for="billing_number">Street Number:</label>
            <input type="text" name="billing_number" required>
            <label for="billing_street">Street:</label>
            <input type="text" name="billing_street" required>
            <label for="billing_city">City:</label>
            <input type="text" name="billing_city" required>
            <label for="billing_postcode">Postcode:</label>
            <input type="text" name="billing_postcode" required>

            <h3>Shipping Address</h3>
            <label for="same_shipping_address">Same as billing address?</label>
            <input type="checkbox" id="same_shipping_address" name="same_shipping_address" onclick="toggleShippingAddress()" checked>

            <div id="shipping_fields" style="display: none;">
                <label for="shipping_number">Street Number:</label>
                <input type="text" name="shipping_number" id="shipping_number">
                <label for="shipping_street">Street:</label>
                <input type="text" name="shipping_street" id="shipping_street">
                <label for="shipping_city">City:</label>
                <input type="text" name="shipping_city" id="shipping_city">
                <label for="shipping_postcode">Postcode:</label>
                <input type="text" name="shipping_postcode" id="shipping_postcode">
            </div>

            <h3>Payment Method</h3>
            <label for="payment_method">Choose Payment Method:</label>
            <select name="payment_method" required>
                <option value="Credit Card">Credit Card</option>
                <option value="paypal">PayPal</option>
            </select>
            <form action="checkout.php" method="POST">

                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit">Place Order</button>
            </form>
        </form>

        <?php if ($error_message) : ?>
            <p style="color: red;"><?= $error_message; ?></p>
        <?php endif; ?>
    </main>
    <footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
