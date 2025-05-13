<?php

$errorMessage = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : "An unexpected error occurred. Please try again later.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Something Went Wrong</title>
    <link rel="stylesheet" href="styles.css"> 
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-center">
            <h1>Something Went Wrong</h1>
        </div>
    </div>
</header>

<main>
    <div class="error-message">
        <h2>Oops!</h2>
        <p><?= $errorMessage ?></p>
        <p>If the problem persists, please contact support.</p>
    </div>

    <div class="suggestions">
        <p>You can:</p>
        <ul>
            <li><a href="index.php">Go back to the homepage</a></li>
        </ul>
    </div>
</main>

<footer class="main-footer">
    <p>&copy; <?php date('Y'); ?> Tyne Brew Coffee. All rights reserved.</p>
</footer>

</body>
</html>
