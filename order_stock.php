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
            $validOrder =