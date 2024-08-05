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

// 創建連接
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 設置時區為台北
date_default_timezone_set('Asia/Taipei');

// 獲取用戶資料
$user = $_SESSION['username'];
$sql = "SELECT email, gender, favorite_color FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 取得表單數據
    $email = $_POST['email'];
    $gender = $_POST['gender'];
    $favorite_color = $_POST['favorite_color'];
    $new_username = $_POST['new_username'];
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];

    // 更新用戶名
    if ($new_username && $new_username !== $user) {
        $update_sql = "UPDATE users SET username = ? WHERE username = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ss", $new_username, $user);
        $stmt->execute();
        $_SESSION['username'] = $new_username; // 更新會話中的用戶名
        $user = $new_username;

        log_user_action($conn, $user, "Username Change", "Changed username to $new_username");
    }

    // 更新密碼
    if ($current_password && $new_password) {
        // 確認舊密碼
        $check_password_sql = "SELECT password FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_password_sql);
        $stmt->bind_param("s", $user);
        $stmt->execute();
        $result = $stmt->get_result();
        $row_password = $result->fetch_assoc();

        // 直接比較明文密碼
        if ($current_password === $row_password['password']) {
            $update_password_sql = "UPDATE users SET password = ? WHERE username = ?";
            $stmt = $conn->prepare($update_password_sql);
            $stmt->bind_param("ss", $new_password, $user);
            $stmt->execute();

            log_user_action($conn, $user, "Password Change", "Changed password");
        } else {
            echo "<p class='error-message'>Current password is incorrect!</p>";
        }
    }

    // 更新其他資料
    $update_sql = "UPDATE users SET email = ?, gender = ?, favorite_color = ? WHERE username = ?";
    $stmt = $conn->prepare($update_sql);
    $stmt->bind_param("ssss", $email, $gender, $favorite_color, $user);
    $stmt->execute();
    $stmt->close();

    log_user_action($conn, $user, "Profile Update", "Updated email, gender, or favorite color");

    echo "<p class='success-message'>Profile updated successfully!</p>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modify Data</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container modify-data-container">
        <header>
            <h1>Modify Your Data</h1>
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </header>
        <main>
            <form action="modify_data.php" method="post">
                <label for="new_username">New Username:</label>
                <input type="text" id="new_username" name="new_username">

                <label for="current_password">Current Password:</label>
                <input type="password" id="current_password" name="current_password">

                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password">

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>" required>

                <label for="gender">Gender:</label>
                <select id="gender" name="gender" required>
                    <option value="Male" <?php if ($row['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                    <option value="Female" <?php if ($row['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                    <option value="Other" <?php if ($row['gender'] == 'Other') echo 'selected'; ?>>Other</option>
                </select>

                <label for="favorite_color">Favorite Color:</label>
                <input type="color" id="favorite_color" name="favorite_color" value="<?php echo htmlspecialchars($row['favorite_color']); ?>" required>

                <input type="submit" value="Update">
            </form>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>




