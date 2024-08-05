<?php
$servername = "localhost"; // 通常本地伺服器是 localhost
$username = "root"; // XAMPP 的默認 MySQL 用戶名
$password = ""; // 默認密碼通常為空
$dbname = "user_db"; // 你的資料庫名稱

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

// 獲取表單數據
$user = $_POST['username'];
$pass = $_POST['password']; // 使用者輸入的原始密碼
$email = $_POST['email'];
$gender = $_POST['gender'];
$favorite_color = $_POST['favorite_color'];

// 檢查是否已存在相同的使用者
$sql = "SELECT * FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "Username already exists.";
    // 記錄嘗試註冊的操作
    log_user_action($conn, $user, "Registration Attempt", "Username already exists.");
} else {
    // 插入新用戶資料
    $sql = "INSERT INTO users (username, password, email, gender, favorite_color) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $user, $pass, $email, $gender, $favorite_color);
    
    if ($stmt->execute()) {
        echo "Registration successful!";
        // 記錄成功註冊的操作
        log_user_action($conn, $user, "Registration", "User registered successfully.");
    } else {
        echo "Error: " . $stmt->error;
        // 記錄註冊錯誤的操作
        log_user_action($conn, $user, "Registration Error", $stmt->error);
    }
}

$stmt->close();
$conn->close();
?>

