<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: index.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_db";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <h1>Host Dashboard</h1>
            <a href="logout.php" class="logout-button">Logout</a>
        </header>
        <div class="dashboard-content">
            <aside class="dashboard-sidebar">
                <ul>
                    <li><a href="member_list.php" class="sidebar-link">Member List</a></li>
                    <li><a href="modify_data.php" class="sidebar-link">Modify Data</a></li>
                    <li><a href="file_manager.php" class="sidebar-link">File Manager</a></li>
                    <li><a href="message_board.php" class="sidebar-link">Message Board</a></li>
                    <li><a href="subscription.php" class="sidebar-link">Subscription</a></li>
                    <li><a href="member_manage.php" class="sidebar-link">Member Manage</a></li>
                </ul>
            </aside>
            <main class="dashboard-main">
                <h2>Welcome, <?php echo htmlspecialchars($user); ?>!</h2>
                <p>Select an option from the sidebar.</p>
            </main>
        </div>
    </div>
</body>
</html>

<?php
$conn->close();
?>
