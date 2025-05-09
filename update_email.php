// Initialize the database connection
$db = new Database();
$conn = $db->connect();

// Retrieve the username from the session
$username = $_SESSION['username'];

// Check if the database connection is established
if ($conn === null) {
    header("Location: error_page.php?error=Database error.");
    exit;
}

// Function to display success messages
function showMessage($message) {
    echo "<div class='alert alert-success'>" . htmlspecialchars($message) . "</div>";
}

// Function to display error messages
function showError($error) {
    echo "<div class='alert alert-danger'>" . htmlspecialchars($error) . "</div>";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
