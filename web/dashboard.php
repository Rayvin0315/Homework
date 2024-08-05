<?php
session_start();

// 檢查是否已登入
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

// 獲取用戶名和是否為 admin
$user = $_SESSION['username'];
$is_admin = ($user === 'Admin');

// 設置時區為台北
date_default_timezone_set('Asia/Taipei');

// 記錄用戶操作的函數
function log_user_action($conn, $username, $action_type, $details = '') {
    $action_time = date("Y-m-d H:i:s");
    $sql = "INSERT INTO user_actions (username, action_type, action_time, details) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Failed to prepare statement: " . $conn->error);
        return;
    }
    $stmt->bind_param("ssss", $username, $action_type, $action_time, $details);
    if (!$stmt->execute()) {
        error_log("Failed to execute statement: " . $stmt->error);
    }
    $stmt->close();
}

// 紀錄用戶訪問 Dashboard 的操作
log_user_action($conn, $user, "View Dashboard", "User accessed the dashboard page.");

// 關閉資料庫連接
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="dashboard-container">
        <header class="dashboard-header">
            <button class="menu-toggle" onclick="toggleSidebar()">☰</button>
            <h1>Dashboard</h1>
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
                    <?php if ($is_admin): ?>
                        <li><a href="member_manage.php" class="sidebar-link">Member Manage</a></li>
                    <?php endif; ?>
                </ul>
            </aside>
            <main class="dashboard-main">
                <h2>Welcome, <?php echo htmlspecialchars($user); ?>!</h2>
                <p>Select an option from the sidebar.</p>
            </main>
        </div>
    </div>
    <script>
    function toggleSidebar() {
    document.querySelector('.dashboard-sidebar').classList.toggle('active');
}
</script>
</body>
</html>




