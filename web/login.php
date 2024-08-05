<?php
session_start();

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

// 取得用戶輸入
$user = $_POST['username'];
$pass = $_POST['password']; // 使用者輸入的原始密碼

// 查詢用戶
$sql = "SELECT * FROM users WHERE username = ? AND password = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $user, $pass);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // 登入成功，設置 session 變量
    $_SESSION['username'] = $user;
    
    // 檢查用戶是否為 Admin
    $row = $result->fetch_assoc();
    if ($row['username'] === 'Admin') {
        // Admin 登入，導向至會員管理頁面
        log_user_action($conn, $user, "Login", "Admin logged in.");
        header("Location: HostDashboard.php");
    } else {
        // 普通用戶登入，導向至儀表板頁面
        log_user_action($conn, $user, "Login", "User logged in.");
        header("Location: dashboard.php");
    }
    exit();
} else {
    // 登入失敗，記錄失敗操作
    log_user_action($conn, $user, "Failed Login", "Invalid username or password.");
    echo "Invalid username or password.";
}

$stmt->close();
$conn->close();
?>







