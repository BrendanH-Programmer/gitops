ef="register.php">Register</a>
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
