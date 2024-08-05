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

// 設置時區為台北
date_default_timezone_set('Asia/Taipei');

// 記錄用戶操作
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

// 訂閱和取消訂閱操作
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        $subscribe_to = 'Host';  // 訂閱對象固定為 'Host'
        
        if ($_POST['action'] == 'subscribe') {
            $sql = "INSERT INTO subscriptions (username, subscribed_to) VALUES (?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $user, $subscribe_to);
            $stmt->execute();
            $stmt->close();

            log_user_action($conn, $user, "Subscribe", "Subscribed to Host");
        }

        if ($_POST['action'] == 'unsubscribe') {
            $sql = "DELETE FROM subscriptions WHERE username = ? AND subscribed_to = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ss", $user, $subscribe_to);
            $stmt->execute();
            $stmt->close();

            log_user_action($conn, $user, "Unsubscribe", "Unsubscribed from Host");
        }
    }
}

// 獲取所有訂閱
$sql = "SELECT DISTINCT subscribed_to FROM subscriptions WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$subscriptions_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container subscription-container">
        <header>
            <h1>Subscription</h1>
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </header>
        <main>
            <section>
                <h2>Subscribe to Host</h2>
                <form action="subscription.php" method="post">
                    <input type="hidden" name="action" value="subscribe">
                    <input type="hidden" name="subscribe_to" value="Host">
                    <input type="submit" value="Subscribe">
                </form>
            </section>

            <section>
                <h2>Your Subscriptions</h2>
                <?php if ($subscriptions_result->num_rows > 0): ?>
                    <?php while ($subscription = $subscriptions_result->fetch_assoc()): ?>
                        <?php if ($subscription['subscribed_to'] === 'Host'): ?>
                            <div class="subscription">
                                <h3>Host</h3>
                                <p>Additional content for subscribers:</p>
                                <img src="https://www.csie.ncu.edu.tw/images/banners/banner.png" alt="Banner" class="subscription-banner">
                                <form action="subscription.php" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="unsubscribe">
                                    <input type="hidden" name="subscribe_to" value="Host">
                                    <input type="submit" value="Unsubscribe" class="button">
                                </form>
                            </div>
                        <?php endif; ?>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You are not subscribed to Host.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>















