<?php
session_start();

// 檢查是否為 Admin
if (!isset($_SESSION['username']) || $_SESSION['username'] !== 'Admin') {
    header("Location: index.html");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "user_db";

// 創建連接
$conn = new mysqli($servername, $username, $password, $dbname);

// 檢查連接
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// 紀錄管理員進入會員管理頁面的操作
$admin_user = $_SESSION['username'];
log_user_action($conn, $admin_user, "Access Member Manage Page", "Admin accessed the member manage page.");

// 處理搜尋功能
$search_query = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['search'])) {
    $search_query = $_POST['search_query'];
}

$search_query_escaped = $conn->real_escape_string($search_query);

$sql = "SELECT * FROM user_actions WHERE username LIKE '%$search_query_escaped%' ORDER BY action_time DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Manage</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Member Manage</h1>
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </header>
        <main>
            <section class="search-section">
                <form action="member_manage.php" method="post">
                    <input type="text" name="search_query" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search by username">
                    <input type="submit" name="search" value="Search" class="button">
                </form>
            </section>

            <section class="actions-list">
                <?php if ($result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Action Type</th>
                                <th>Action Time</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td><?php echo htmlspecialchars($row['action_type']); ?></td>
                                    <td><?php echo htmlspecialchars($row['action_time']); ?></td>
                                    <td><?php echo htmlspecialchars($row['details']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No records found.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>





