<?php
include_once 'session_manager.php';
include_once 'db.php';
include 'auth.php';

ensureAuthenticated();
displayAdminLink();

// Get user data from database
$user_id = $_SESSION['user_id'];

// Secure the SQL query by using prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE id = :user_id");
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Ensure the user exists
if (!$user) {
    header("Location: error_page.php?error=User not found.");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="container mt-4">
    <h1>Edit Profile</h1>

    <form action="profile_update.php" method="post" enctype="multipart/form-data">
        <div class="form-group">
            <label for="username">Username:</label>
            <input type="text" class="form-control" id="username" name="username" value="<? htmlspecialchars($user['username']); ?>" required>
        </div>

        <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user['email']); ?>" required>
        </div>

        <div class="form-group">
            <label for="first_name">First Name:</label>
            <input type="text" class="form-control" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name">Last Name:</label>
            <input type="text" class="form-control" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']); ?>" required>
        </div>

        <div class="form-group">
            <label for="profile_picture">Profile Picture:</label>
            <input type="file" class="form-control-file" id="profile_picture" name="profile_picture">
        </div>

        <button type="submit" class="btn btn-primary">Update Profile</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>
</html>
