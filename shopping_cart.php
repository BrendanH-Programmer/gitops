emptyCart(); // Clear the cart using the CSRF token
    
    // Return the response (empty cart)
    echo json_encode([
        'items' => [], // Cart is now empty
        'total' => 0    // Total price is 0
    ]);
    exit;


case 'update':
if (isset($_POST['product_id'], $_POST['quantity'])) {
    $product_id = $_POST['product_id'];
    $quantity = intval($_POST['quantity']);

    // Get the available stock from the database
    $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $stock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];

    if ($quantity > $stock) {
        echo json_encode(['error' => 'Quantity exceeds available stock']);
        exit;
    }

    if ($quantity > 0 && isset($_SESSION['cart_item'][$product_id])) {
        $_SESSION['cart_item'][$product_id]['quantity'] = $quantity;
    }

    echo json_encode([
        'items' => $_SESSION['cart_item'],
        'total' => calculateTotalPrice($_SESSION['cart_item']),
    ]);
    exit;
}
break;

default:
echo "Invalid action";
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shopping Cart</title>
<link rel="stylesheet" href="styles.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
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
<?php if (isLoggedIn()) : ?>
    <a href="logout.php">Logout</a>
<?php else : ?>
    <a href="login.php">Login</a>
<?php endif; ?>
</div>
</div>
</header>


<body>
<h1 style="color: #3e2723;">Shopping Cart</h1>
<script>
function emptyCart() {
$.post('shopping_cart.php', {
action: 'empty',
csrf_token: '<?= $_SESSION['csrf_token']; ?>'
}, function(response) {
const data = JSON.parse(response);

if (data.items.length === 0) {
$('#cart-table').remove();
$('#empty-cart-message').text('Your cart is empty.').show();
}
});
}

</script>   
<?php if (!empty($_SESSION['cart_item'])): ?>
<table id="cart-table">
<thead>
    <tr>
        <th style="width: 25%;">Name</th>
        <th style="width: 15%;">Price</th>
        <th style="width: 20%;">Quantity</th>
        <th style="width: 20%;">Total</th>
        <th style="width: 20%;">Action</th>
    </tr>
</thead>

<tbody id="cart-body">
    <?php foreach ($_SESSION['cart_item'] as $product_id => $item): ?>
        <?php
            // Fetch the stock quantity for this product from the database
            $stmt = $conn->prepare("SELECT stock_quantity FROM products WHERE product_id = :product_id");
            $stmt->bindParam(':product_id', $product_id, PDO::PARAM_INT);
            $stmt->execute();
            $productStock = $stmt->fetch(PDO::FETCH_ASSOC)['stock_quantity'];
        ?>
        <tr id="cart-row-<?= $product_id; ?>">
            <td>
                <a href="product_details.php?id=<?= $product_id; ?>">
                    <?= htmlspecialchars($item['name']); ?>
                </a>
            </td>
            <td>£<?= number_format(floatval($item['price']), 2); ?></td>
            <td>
                <input 
                    type="number" 
                    name="quantity" 
                    value="<?= htmlspecialchars($item['quantity']); ?>" 
                    min="1" 
                    max="<?= $productStock; ?>" 
                    data-max-quantity="<?= $productStock; ?>" 
                    onchange="updateQuantity('<?= $product_id; ?>', this.value)"
                >
            </td>
            <td>£<span id="item-total-<?= $product_id; ?>">
                <?= number_format(floatval($item['price']) * $item['quantity'], 2); ?>
            </span></td>
            <td>
                <form method="post" action="">
                    <input type="hidden" name="action" value="remove">
                    <input type="hidden" name="product_id" value="<?= $product_id; ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
                    <button type="submit">Remove</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>

<tfoot>
    <tr>
        <td colspan="4">Total:</td>
        <td id="total-price">£<?= number_format(calculateTotalPrice($_SESSION['cart_item']), 2); ?></td>
    </tr>
</tfoot>

<!-- Checkout section -->
<tr>
    <td colspan="5" class="checkout-section">
        <?php if (isLoggedIn()): ?>
            <?php if (!empty($_SESSION['cart_item'])): ?>
                <form method="post" action="checkout.php">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                    <button type="submit" class="checkout-btn">Proceed to Checkout</button>
                </form>
            <?php else: ?>
                <p>Your cart is empty.</p>
            <?php endif; ?>
        <?php else: ?>
            <div class="login-prompt">
                <p>You must be logged in to proceed to checkout.</p>
                <a href="login.php">Login</a> or <a href="register.php">Register</a> to continue.
            </div>
        <?php endif; ?>
    </td>
</tr>
</table>
<?php else: ?>
<p id="empty-cart-message"></p>
<?php endif; ?>

<form method="post" action="" id="empty-cart-form">
<input type="hidden" name="action" value="empty">
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
<!-- Change type to button and trigger JavaScript function directly -->
<button type="button" onclick="emptyCart()">Empty Cart</button>
</form>



<h3>Available Products</h3>
<ul id="products-list">
<?php foreach ($products as $product): ?>
<?php
    // Fetch the product image URL from the database
    $stmt = $conn->prepare("SELECT image_url FROM products WHERE product_id = :product_id");
    $stmt->bindParam(':product_id', $product['product_id'], PDO::PARAM_INT);
    $stmt->execute();
    $productStock = $productData['stock_quantity'];
    $productImage = $stmt->fetch(PDO::FETCH_ASSOC)['image_url']; 
?>
<li>
    <a href="product_details.php?id=<?= $product['product_id']; ?>">
        <img src="<?= htmlspecialchars($productImage); ?>" alt="Product Image" style="width: 100px; height: 100px;">
        <span><?= htmlspecialchars($product['name']); ?></span>
    </a>
    <span>£<?= number_format(floatval($product['price']), 2); ?></span>
    <form method="post" action="">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="product_id" value="<?= $product['product_id']; ?>">
        <input type="number" name="quantity" min="1" max="<?= $productStock; ?>" value="1" required>
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
        <button type="submit">Add to Cart</button>
    </form>
</li>
<?php endforeach; ?>
</ul>
</body>

<script>
function updateCart(product_id, quantity) {
$.ajax({
type: 'POST',
url: 'shopping_cart.php',
data: { action: 'update', product_id: product_id, quantity: quantity },
success: function(response) {
const cart = JSON.parse(response);

// Check if the cart is empty
if (Object.keys(cart.items).length === 0) {
    $('#cart-table').remove(); // Remove the table
    $('#empty-cart-message').show(); // Show the empty message
} else {
    // Update the item total for the modified item
    const itemTotal = (parseFloat(cart.items[product_id].price) * parseInt(cart.items[product_id].quantity)).toFixed(2);
    $(`#item-total-${product_id}`).text(itemTotal);

    // Recalculate the overall total price for the entire cart
    let overallTotal = 0;
    $.each(cart.items, function(key, item) {
        overallTotal += parseFloat(item.price) * parseInt(item.quantity);
    });

    // Update the total price in the DOM
    $('#total-price').text('£' + overallTotal.toFixed(2));
}
},
error: function(xhr, status, error) {
console.error("Error updating cart:", error);
}
});
}


// Ensure quantity is valid
function updateQuantity(product_id, quantity) {
// Ensure the quantity does not exceed the available stock
const maxQuantity = parseInt($(`#cart-row-${product_id} input[name="quantity"]`).data('max-quantity'));

if (quantity > maxQuantity) {
alert('Quantity exceeds available stock.');
return;
}

if (quantity <= 0) {
alert('Quantity must be greater than zero');
return;
}

// Send AJAX request to update the quantity on the server
$.ajax({
type: 'POST',
url: 'shopping_cart.php',
data: {
action: 'update',
product_id: product_id,
quantity: quantity,
csrf_token: '<?= $_SESSION['csrf_token']; ?>' // Include CSRF token here
},
success: function(response) {
const cart = JSON.parse(response);

// If the cart is empty, hide the table and show the empty message
if (Object.keys(cart.items).length === 0) {
$('#cart-table').remove(); // Remove the table
$('#empty-cart-message').show(); // Show the empty cart message
} else {
// Update the item total for the specific product
const itemTotal = (parseFloat(cart.items[product_id].price) * parseInt(cart.items[product_id].quantity)).toFixed(2);
$(`#item-total-${product_id}`).text(itemTotal);

// Recalculate the overall total price and update the DOM
$('#total-price').text('£' + cart.total.toFixed(2));
}
},
error: function(xhr, status, error) {
console.error("Error updating cart:", error);
}
});

}

// Ensure quantity is valid and within stock limits
function validateAndUpdateQuantity(product_id, quantity) {
// Get the stock quantity from the server (you can make an AJAX call here if needed)
var maxQuantity = parseInt($(`#product-${product_id}`).data('max-quantity')); // Assuming max-quantity is stored as a data attribute

if (quantity > maxQuantity) {
alert('Quantity exceeds available stock.');
return;
}
updateQuantity(product_id, quantity);

// Update quantity and total price dynamically
function updateQuantity(productId, quantity) {
const maxQuantity = document.querySelector(`#cart-row-${productId} input[name="quantity"]`).dataset.maxQuantity;

if (parseInt(quantity) > parseInt(maxQuantity)) {
alert('Quantity exceeds available stock');
return;
}

$.post('shopping_cart.php', {
action: 'update',
product_id: productId,
quantity: quantity,
csrf_token: '<?= $_SESSION['csrf_token']; ?>'
}, function(response) {
const data = JSON.parse(response);
if (data.error) {
alert(data.error);
} else {
// Update the item total and overall total
document.querySelector(`#item-total-${productId}`).textContent = data.items[productId].quantity * data.items[productId].price;
document.getElementById('total-price').textContent = '£' + data.total.toFixed(2);
}
});
}