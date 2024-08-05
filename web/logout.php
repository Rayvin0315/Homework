<?php
// 啟用 session
session_start();

// 連接到資料庫
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

// 檢查用戶是否登入
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username'];
    
    // 記錄登出操作
    log_user_action($conn, $username, "Logout", "User logged out.");

    // 清除 session
    session_unset();
    session_destroy();
}

// 重定向到登入頁面
header("Location: index.html");
exit();
?>

