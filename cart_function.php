<?php
include_once 'auth.php';
include_once 'db.php';


// Connect to the database
$db = new Database();
$conn = $db->connect();

// Validate and sanitize input function
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Get product details with parameterized queries to prevent SQL injection
function getProductDetails($product_id) {
    global $conn;
    if (!($conn instanceof PDO)) {
        header("Location: error_page.php?error=Database error.");
        exit;
    }
    
    $query = "SELECT product_id, name, price FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get cart items from session
function getCartItems() {
    return isset($_SESSION['cart_item']) ? $_SESSION['cart_item'] : [];
}

// Calculate total price of items in the cart
function calculateTotalPrice($cart_contents) {
    $total_price = 0;
    foreach ($cart_contents as $item_id => $item) {
        if (isset($item['price']) && is_numeric($item['price'])) {
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 1;
            $total_price += floatval($item['price']) * $quantity;
        } else {
            // Handle potential data issues
        }
    }
    return $total_price;
}

// Add item to the cart after validation
function addItemToCart($product_id, $name, $price, $quantity) {
    global $conn;

    // Validate and sanitize the inputs
    $product_id = intval($product_id);  // Ensure product_id is an integer
    $quantity = max(1, intval($quantity)); // Ensure quantity is at least 1

    if (!isset($_SESSION['cart_item'])) {
        $_SESSION['cart_item'] = array();
    }

    // Determine the available stock quantity from the database
    $available_quantity = determineQuantity($product_id, $conn);

    // Ensure the requested quantity does not exceed available stock
    if ($quantity > $available_quantity) {
        echo "Error: Not enough stock available. Available stock: $available_quantity.";
        return;
    }

    // Add item to the cart or update the quantity
    if (!isset($_SESSION['cart_item'][$product_id])) {
        $_SESSION['cart_item'][$product_id] = array(
            'product_id' => $product_id,
            'name' => sanitizeInput($name),
            'price' => number_format(floatval($price), 2),
            'quantity' => $quantity
        );
    } else {
        $new_quantity = $_SESSION['cart_item'][$product_id]['quantity'] + $quantity;

        // Ensure the updated quantity does not exceed available stock
        if ($new_quantity > $available_quantity) {
            echo "Error: You cannot add more than $available_quantity items.";
            return;
        }

        $_SESSION['cart_item'][$product_id]['quantity'] = $new_quantity;
    }

    // Update total price
    $total_price = calculateTotalPrice($_SESSION['cart_item']);
    $_SESSION['total_price'] = $total_price;

    // Ensure item details are correct
    $product = getProductDetails($product_id);
    if ($product && isset($_SESSION['cart_item'][$product_id])) {
        $_SESSION['cart_item'][$product_id] = array(
            'product_id' => $product_id,
            'name' => sanitizeInput($product['name']),
            'price' => number_format(floatval($product['price']), 2),
            'quantity' => $_SESSION['cart_item'][$product_id]['quantity']
        );
    }
}

// Remove an item from the cart
function removeFromCart($product_id) {

    if (isset($_SESSION['cart_item'][$product_id])) {
        unset($_SESSION['cart_item'][$product_id]);
    }
}

// Determine available stock based on product ID from the database
function determineQuantity($product_id, $conn) {
    $query = "SELECT stock_quantity FROM products WHERE product_id = :product_id";
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    // Return available stock quantity
    return ($product && isset($product['stock_quantity'])) ? $product['stock_quantity'] : 0;
}

// Clear all items from the cart
function emptyCart() {
    // Clear the cart
    unset($_SESSION['cart_item']);
}

?>