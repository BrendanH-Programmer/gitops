im($input);
    $input = htmlspecialchars($input);
    return $input;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($user['username']); ?>'s Profile</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<header>
    <div class="header-container">
        <!-- Left side: Profile/Register link -->
        <div class="header-left">
            <?php if (isLoggedIn()) : ?>
                <a href="profile.php">Profile</a>
            <?php else : ?>
                <a href="register.php">Register</a>
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