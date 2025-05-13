<?php
include_once 'session_manager.php';
include_once 'auth.php';
include_once 'db.php';

// Check if the user is already logged in, if so, redirect to index.php
if (isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

try {
    $db = new Database();
    $conn = $db->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Process login credentials
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        $query = "SELECT * FROM users WHERE email = :email";
        $stmt = $conn->prepare($query);
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID
            session_regenerate_id(true);

            // Set session variables
            $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
            $_SESSION['email'] = htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8');
            $_SESSION['role'] = $user['role'];

            // Redirect to index.php
            header("Location: index.php");
            exit;
        } else {
            $error = "Invalid email or password.";
        }
    }
} catch (PDOException $e) {
    header("Location: error_page.php?error=Database Error.");
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
        <div class="header-center">
            <h1>Tyne Brew Coffee</h1>
                <a href="register.php">Register</a>
                <a href="index.php">Shop</a>
        </div>
    </div>
</header>
</head>
<body>
        <form class="form-container" method="POST">
            <h1 style="color: #3e2723;">Login</h1>
             <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
            <?php if (!empty($error)): ?>
                <p style="color: red;"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </form>
</body>
<footer class="main-footer">
        <p>&copy; <?= date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
    </footer>
</html>