
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