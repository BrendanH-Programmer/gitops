header("Location: admin_edit_product.php?message=Product updated successfully!");
            exit;
        } catch (PDOException $e) {
            $message = "Error updating product: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="admin_styles.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-4">
    <header class="mb-4">
        <h1>Edit Product</h1>
        <a href="admin_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        <a href="admin_edit_product.php" class="btn btn-secondary">Back to Products</a>
    </header>

    <main>
        <?php if (!empty($message)): ?>
            <div class="alert <?= strpos($message, 'successfully') !== false ? 'alert-success' : 'alert-danger'; ?>">
                <?= htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">

            <div class="mb-3">
                <label for="name" class="form-label">Product Name</label>
                <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($product['name']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4" required><?= htmlspecialchars($product['description']) ?></textarea>
            </div>

            <div class="mb-3">
                <label for="price" class="form-label">Price (£)</label>
                <input type="number" step="0.01" id="price" name="price" class="form-control" value="<?= htmlspecialchars($product['price']) ?>" required>
            </div>

            <div class="mb-3">
                <label for="category_id" class="form-label">Category</label>
                <select id="category_id" name="category_id" class="form-select" required>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= htmlspecialchars($category['category_id']) ?>" <?= $product['category_id'] == $category['category_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($category['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-3">
                <label for="current_image" class="form-label">Current Image</label><br>
                <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Current Product Image" class="img-thumbnail" width="150">
            </div>

            <div class="mb-3">
                <label for="new_image" class="form-label">Change Image</label>
                <input type="file" id="new_image" name="new_image" class="form-control" accept="image/*">
            </div>

            <button type="submit" class="btn btn-primary">Save Changes</button>
        </form>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
