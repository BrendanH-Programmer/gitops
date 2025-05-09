

// Ensure the user is not already logged in (if they are, redirect them)
if (isLoggedIn()) {
    header("Location: index.php");
    exit;
}

$errors = array();

try {
    // Create an instance of DB and connect
    $db = new Database();
    $conn = $db->connect();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        // Retrieve and sanitize form data
        $username = htmlspecialchars(trim($_POST['username']));
        $email = htmlspecialchars(filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL));
        $password = htmlspecialchars(trim($_POST['password']));
        $confpass = htmlspecialchars(trim($_POST['confpass']));

        // Email Validation
        if (strlen($email) < 5 || strlen($email) > 50) {
            $errors['emailError'] = 'Email does not meet required length!';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['emailError'] = 'Email address not valid!';
        }

        // Check if username or email is already in use
        $emailQuery = $conn->prepare("SELECT * FROM users WHERE email = :email OR username = :username");
        $emailQuery->bindParam(':email', $email, PDO::PARAM_STR);
        $emailQuery->bindParam(':username', $username, PDO::PARAM_STR);
        $emailQuery->execute();

        if ($emailQuery->rowCount() > 0) {
            $errors['usernameError'] = 'Username/Email is already in use!';
        }

        // Username Validation
        if (strlen($username) < 3 || strlen($username) > 15) {
            $errors['usernameError'] = 'Username must be between 3 and 15 characters long!';
        } elseif (preg_match('/[\W]/', $username)) {
            $errors['usernameError'] = 'Username must not contain special characters!';
        }

        // Password validation
        if (strlen($password) < 8 || strlen($password) > 50) {
            $errors['passwordError'] = 'Password must be between 8 and 50 characters long!';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors['passwordError'] = 'Password must contain at least one uppercase letter!';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors['passwordError'] = 'Password must contain at least one number!';
        } elseif (!preg_match('/[\W]/', $password)) {
            $errors['passwordError'] = 'Password must contain at least one special character!';
        }

        // Check if passwords match
        if ($password !== $confpass) {
            $errors['passwordError'] = "Passwords do not match. Please try again.";
        }

        // If no validation errors, register the user
        if (count($errors) == 0) {
            // Hash the password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insertQuery = $conn->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
            $insertQuery->bindParam(":username", $username, PDO::PARAM_STR);
            $insertQuery->bindParam(":password", $hashedPassword, PDO::PARAM_STR);
            $insertQuery->bindParam(":email", $email, PDO::PARAM_STR);
            $insertQuery->execute();

            // Redirect to the login page upon successful registration
            header("Location: login.php");
            exit;
        }
    }
} catch (PDOException $e) {
    header("Location: error_page.php?error=Database error.");
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
                <a href="login.php">Login</a>
                <a href="index.php">Shop</a>
        </div>
    </div>
</header>
</head>
<body>
<form class="form-container" method="POST">
    <h1 style="color: #3e2723;">Register</h1>
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token']; ?>">

    <!-- Username Field -->
    <input type="text" placeholder="Username..." name="username" required>
    <?php if (isset($errors['usernameError'])): ?>
        <p style="color: red;"><?= htmlspecialchars($errors['usernameError'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- Email Field -->
    <input type="email" placeholder="Email..." name="email" required>
    <?php if (isset($errors['emailError'])): ?>
        <p style="color: red;"><?= htmlspecialchars($errors['emailError'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- Password Field -->
    <input type="password" placeholder="Password..." name="password" required>
    <?php if (isset($errors['passwordError'])): ?>
        <p style="color: red;"><?= htmlspecialchars($errors['passwordError'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <!-- Confirm Password Field -->
    <input type="password" placeholder="Confirm Password..." name="confpass" required>
    <?php if (isset($errors['passwordError']) && $password !== $confpass): ?>
        <p style="color: red;"><?= htmlspecialchars($errors['passwordError'], ENT_QUOTES, 'UTF-8'); ?></p>
    <?php endif; ?>

    <button type="submit" name="register">Register</button>
</form>