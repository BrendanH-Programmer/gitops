<?php
include_once 'session_manager.php';
include_once 'db.php';
include_once 'cart_function.php';
include_once 'auth.php';

isLoggedIn();
displayAdminLink();

// Instantiate DB class and get connection
$db = new Database();
$conn = $db->connect();

// Pagination and sorting settings
$limit = 12;  // Number of products per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) && $_GET['page'] > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $limit;
$currentSort = isset($_GET['sort']) && in_array($_GET['sort'], ['ASC', 'DESC']) ? $_GET['sort'] : 'ASC';

// Fetch categories for filters
$filterOptions = [['label' => 'All Categories', 'value' => 'all']];
try {
    $categoriesQuery = "SELECT * FROM categories";
    $categoriesResult = $conn->query($categoriesQuery);
    while ($category = $categoriesResult->fetch(PDO::FETCH_ASSOC)) {
        $filterOptions[] = [
            'label' => htmlspecialchars($category['name']),
            'value' => intval($category['category_id'])
        ];
    }
} catch (PDOException $e) {
    // Redirect to a friendly error page
    header("Location: error_page.php?error=" .urlencode("Something went wrong while fetching categories."));
    exit;
}

// Handle product filter
$currentFilter = isset($_GET['filter']) && $_GET['filter'] !== 'all' ? intval($_GET['filter']) : 'all';

// Fetch products
$query = "
    SELECT p.*, c.name AS category_name, p.stock_quantity
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE 1=1
";

if ($currentFilter !== 'all') {
    $query .= " AND p.category_id = :category_id";
}
$query .= " ORDER BY p.price $currentSort LIMIT :offset, :limit";

try {
    $stmt = $conn->prepare($query);
    if ($currentFilter !== 'all') {
        $stmt->bindValue(':category_id', $currentFilter, PDO::PARAM_INT);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $totalQuery = "SELECT COUNT(*) as total FROM products";
    if ($currentFilter !== 'all') {
        $totalQuery .= " WHERE category_id = :category_id";
    }
    $totalStmt = $conn->prepare($totalQuery);
    if ($currentFilter !== 'all') {
        $totalStmt->bindValue(':category_id', $currentFilter, PDO::PARAM_INT);
    }
    $totalStmt->execute();
    $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
    $totalPages = ceil($totalResult['total'] / $limit);
} catch (PDOException $e) {
    // Redirect with an error message
    header("Location: error_page.php?error=" .urlencode("Database Error."));
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tyne Brew Coffee</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <?php if (isLoggedIn()) : ?>
                <a href="profile.php">Profile</a>
            <?php else : ?>
                <a href="register.php">Register</a>
            <?php endif; ?>
        </div>
        <div class="header-center">
            <h1>Tyne Brew Coffee</h1>
            <?php if (isLoggedIn()) : ?>
                <span>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?></span>
            <?php endif; ?>
        </div>
        <div class="header-right">
            <a href="shopping_cart.php">Shopping Cart</a>
            <?php if (isLoggedIn()) : ?>
                <a href="logout.php">Logout</a>
            <?php else : ?>
                <a href="login.php">Login</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<div class="filters-nav">
    <form method="GET">
        <div class="filters-container">
            <div class="filter-group">
                <label for="categoryFilter">Category:</label>
                <select id="categoryFilter" name="filter">
                    <?php foreach ($filterOptions as $option): ?>
                        <option value="<?= $option['value'] ?>" <?= ($option['value'] == $currentFilter) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($option['label']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label for="sortBy">Sort by:</label>
                <select id="sortBy" name="sort">
                    <option value="ASC" <?= $currentSort == 'ASC' ? 'selected' : '' ?>>Price Low to High</option>
                    <option value="DESC" <?= $currentSort == 'DESC' ? 'selected' : '' ?>>Price High to Low</option>
                </select>
            </div>
            <div class="filter-group">
                <button type="submit">Apply</button>
            </div>
        </div>
    </form>
</div>

<div class="product-grid">
    <?php if (!empty($products)) : ?>
        <?php foreach ($products as $product) : ?>
            <div class="product-card">
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <h2><?= htmlspecialchars($product['name']) ?></h2>
                <h5>£<?= number_format($product['price'], 2) ?></h5>
                <p>Available stock: <?= $product['stock_quantity'] ?> units</p>
                <a href="product_details.php?id=<?= $product['product_id'] ?>">View Details</a>

                <?php if ($product['stock_quantity'] > 0): ?>
                    <form action="shopping_cart.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <input type="hidden" name="action" value="add">

                        <label for="quantity-<?= $product['product_id'] ?>">Quantity:</label>
                        <input 
                            type="number" 
                            id="quantity-<?= $product['product_id'] ?>" 
                            name="quantity" 
                            value="1" 
                            min="1" 
                            max="<?= $product['stock_quantity'] ?>" 
                            required>

                        <button type="submit">Add to Cart</button>
                    </form>
                <?php else: ?>
                    <span>Out of Stock</span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php else : ?>
        <p>No products found.</p>
    <?php endif; ?>
</div>

<div class="pagination">
    <?php if ($page > 1) : ?>
        <a href="?page=<?= $page - 1 ?>&sort=<?= $currentSort ?>&filter=<?= $currentFilter ?>">Previous</a>
    <?php endif; ?>
    Page <?= $page ?> of <?= $totalPages ?>
    <?php if ($page < $totalPages) : ?>
        <a href="?page=<?= $page + 1 ?>&sort=<?= $currentSort ?>&filter=<?= $currentFilter ?>">Next</a>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadProducts();
    
    document.getElementById('categoryFilter').addEventListener('change', function() {
        loadProducts();
    });
    
    document.getElementById('sortBy').addEventListener('change', function() {
        loadProducts();
    });
});

function loadProducts() {
    const filter = document.getElementById('categoryFilter').value;
    const sortBy = document.getElementById('sortBy').value;
    
    fetch('/api/products?' + 
           'filter=' + encodeURIComponent(filter) +
           '&sort=' + encodeURIComponent(sortBy))
        .then(response => response.json())
        .then(data => {
            const grid = document.querySelector('.product-grid');
            grid.innerHTML = '';
            
            data.forEach(product => {
                const productDiv = createProductDiv(product);
                grid.appendChild(productDiv);
            });
        })
        .catch(() => {

            //window.location.href = '/error_page.php?error=Failed to load products. Please try again later.';
        });
}


function createProductDiv(product) {
    const div = document.createElement('div');
    div.classList.add('product-card');
    div.innerHTML = `
        <img src="${product.image_url}" alt="${product.name}">
        <h2>${product.name}</h2>
        <h5>£${product.price}</h5>
        <p>Available stock: ${product.stock_quantity}</p>
        <a href="product_details.php?id=${product.product_id}&csrf_token=${product.csrf_token}">View Details</a>
    `;
    return div;
}
</script>

</body>
<footer class="main-footer">
        <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
    </footer>
</html>
