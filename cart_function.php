

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