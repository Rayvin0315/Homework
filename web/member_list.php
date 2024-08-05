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

// 獲取用戶名
$current_user = $_SESSION['username'];

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

// 紀錄用戶進入會員列表頁面的操作
log_user_action($conn, $current_user, "View Member List", "Accessed the member list page.");

// 獲取所有會員資料
$sql = "SELECT username, email, gender, favorite_color FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member List</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>Member List</h1>
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </header>
        <main>
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Favorite Color</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['username']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo htmlspecialchars($row['gender']); ?></td>
                                <td style="background-color: <?php echo htmlspecialchars($row['favorite_color']); ?>;">
                                    <?php echo htmlspecialchars($row['favorite_color']); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No members found.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>


