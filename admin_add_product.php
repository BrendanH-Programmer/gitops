<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'auth.php';

ensureAuthenticated();
ensureAdmin();
displayAdminLink();

// Connect to the database
$db = new Database();
$conn = $db->connect();

if ($conn === null) {
    // Log the error and redirect to error page
    header("Location: error_page.php?error=Database connection failed.");
    exit;
}

// Fetch categories for the dropdown
try {
    $category_stmt = $conn->prepare("SELECT * FROM categories");
    $category_stmt->execute();
    $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Log the error and redirect to error page
    header("Location: error_page.php?error=Failed to load categories. Please try again later.");
    exit;
}

// Variable to hold error or success messages
$message = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get and sanitize form data
    $name = trim(htmlspecialchars($_POST['name'] ?? ''));
    $description = trim(htmlspecialchars($_POST['description'] ?? ''));
    $price = floatval($_POST['price'] ?? 0);
    $stock_quantity = intval($_POST['stock_quantity'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? null);    
    $image_url = ''; // Will be set after image upload

    // Validate input
    if (empty($name) || empty($description) || empty($price) || empty($stock_quantity) || empty($category_id)) {
        $message = "All fields are required. Please fill in all fields.";
    } elseif (!is_numeric($price) || $price <= 0 || !is_numeric($stock_quantity) || $stock_quantity < 0) {
        $message = "Invalid price or stock quantity. Please enter valid values.";
    } else {
        // Handle image upload
        if (!empty($_FILES['image']['name'])) {
            $target_dir = "img/"; // Directory where images will be uploaded
            $image_name = uniqid() . '-' . basename($_FILES['image']['name']);
            $target_file = $target_dir . $image_name;

            // Validate file type and size
            $valid_mime_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_mime_type = mime_content_type($_FILES['image']['tmp_name']);
            $max_file_size = 2 * 1024 * 1024; // 2 MB

            if (!in_array($file_mime_type, $valid_mime_types)) {
                $message = "Invalid file type. Only JPG, PNG, and GIF are allowed.";
            } elseif ($_FILES['image']['size'] > $max_file_size) {
                $message = "File size exceeds the 2 MB limit.";
            } elseif (!move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $message = "Error uploading the image. Please try again.";
            } else {
                $image_url = $target_file; // Set the image URL after successful upload
            }
        }

        // If image upload was successful or no image provided, insert the product into the database
        if (empty($message)) {
            try {
                // Insert product into database
                $stmt = $conn->prepare("
                    INSERT INTO products (category_id, name, description, price, image_url, stock_quantity)
                    VALUES (:category_id, :name, :description, :price, :image_url, :stock_quantity)
                ");
                $stmt->bindParam(':category_id', $category_id, PDO::PARAM_INT);
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price, PDO::PARAM_STR);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->bindParam(':stock_quantity', $stock_quantity, PDO::PARAM_INT);
                $stmt->execute();

                $message = "Product added successfully!";
            } catch (PDOException $e) {
                // Log the error and redirect to error page
                header("Location: error_page.php?error=Failed to add product. Please try again later.");
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Add Product</title>
    <link rel="stylesheet" href="admin_styles.css">
</head>
<body>
    <header>
        <h1>Admin Panel - Add Product</h1>
        <a href="admin_dashboard.php">Back to Dashboard</a>
    </header>

    <main>
        <h2>Add a New Product</h2>

        <?php if (!empty($message)): ?>
            <p style="color: <?= strpos($message, 'successfully') !== false ? 'green' : 'red'; ?>;">
                <?= htmlspecialchars($message); ?>
            </p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">

            <label for="name">Product Name:</label><br>
            <input type="text" id="name" name="name" required><br><br>

            <label for="description">Description:</label><br>
            <textarea id="description" name="description" required></textarea><br><br>

            <label for="price">Price (£):</label><br>
            <input type="number" id="price" name="price" step="0.01" required><br><br>

            <label for="stock_quantity">Stock Quantity:</label><br>
            <input type="number" id="stock_quantity" name="stock_quantity" required><br><br>

            <label for="category_id">Category:</label><br>
            <select id="category_id" name="category_id" required>
                <option value="">Select a category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= htmlspecialchars($category['category_id']) ?>">
                        <?= htmlspecialchars($category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select><br><br>

            <label for="image">Image:</label><br>
            <input type="file" id="image" name="image" accept="image/*" required><br><br>

            <button type="submit">Add Product</button>
        </form>
    </main>
</body>
<footer class="main-footer">
    <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
