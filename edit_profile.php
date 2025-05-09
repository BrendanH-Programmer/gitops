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
    <link rel="stylesheet" href="st