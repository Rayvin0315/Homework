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
$is_admin = ($user === 'Admin'); // 檢查是否為 Admin

// 設置時區為台北
date_default_timezone_set('Asia/Taipei');

// 記錄用戶操作
function log_user_action($conn, $username, $action_type, $details = '') {
    $action_time = date("Y-m-d H:i:s");
    $sql = "INSERT INTO user_actions (username, action_type, action_time, details) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $username, $action_type, $action_time, $details);
    $stmt->execute();
    $stmt->close();
}

// 處理訊息發布
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'post_message') {
            $title = $_POST['title'];
            $content = $_POST['content'];
            $post_time = date("Y-m-d H:i:s");

            $sql = "INSERT INTO messages (username, title, content, post_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssss", $user, $title, $content, $post_time);
            $stmt->execute();
            $stmt->close();

            log_user_action($conn, $user, 'Post Message', "Title: $title, Content: $content");
        }

        if ($_POST['action'] == 'reply_message') {
            $message_id = $_POST['message_id'];
            $content = $_POST['reply_content'];
            $reply_time = date("Y-m-d H:i:s");

            $sql = "INSERT INTO replies (message_id, username, content, reply_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isss", $message_id, $user, $content, $reply_time);
            $stmt->execute();
            $stmt->close();

            log_user_action($conn, $user, 'Reply Message', "Message ID: $message_id, Content: $content");
        }

        if ($_POST['action'] == 'edit_message') {
            $message_id = $_POST['message_id'];
            $title = $_POST['title'];
            $content = $_POST['content'];

            $sql = "UPDATE messages SET title = ?, content = ? WHERE id = ? AND username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssis", $title, $content, $message_id, $user);
            $stmt->execute();
            $stmt->close();

            log_user_action($conn, $user, 'Edit Message', "Message ID: $message_id, New Title: $title, New Content: $content");
        }

        if ($_POST['action'] == 'delete_message') {
            $message_id = $_POST['message_id'];

            $sql = "DELETE FROM messages WHERE id = ? AND username = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $message_id, $user);
            $stmt->execute();
            $stmt->close();

            log_user_action($conn, $user, 'Delete Message', "Message ID: $message_id");
        }
    }
}

// 獲取所有訊息
$sql = "SELECT id, username, title, content, post_time FROM messages ORDER BY post_time DESC";
$messages_result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Board</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container message-board-container">
        <header>
            <h1>Message Board</h1>
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </header>
        <main>
            <section>
                <h2>Post a New Message</h2>
                <form action="message_board.php" method="post">
                    <input type="hidden" name="action" value="post_message">
                    <label for="title">Title:</label>
                    <input type="text" id="title" name="title" required>
                    <label for="content">Content:</label>
                    <textarea id="content" name="content" rows="4" required></textarea>
                    <input type="submit" value="Post Message">
                </form>
            </section>

            <section>
                <h2>Messages</h2>
                <?php if ($messages_result->num_rows > 0): ?>
                    <?php while ($message = $messages_result->fetch_assoc()): ?>
                        <div class="message">
                            <h3><?php echo htmlspecialchars($message['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                            <small>Posted by <?php echo htmlspecialchars($message['username']); ?> on <?php echo htmlspecialchars($message['post_time']); ?> (Taipei Time)</small>

                            <?php if ($message['username'] == $user): ?>
                                <form action="message_board.php" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="edit_message">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <input type="text" name="title" placeholder="New title" value="<?php echo htmlspecialchars($message['title']); ?>" required>
                                    <textarea name="content" rows="2" required><?php echo htmlspecialchars($message['content']); ?></textarea>
                                    <input type="submit" value="Edit">
                                </form>
                                <form action="message_board.php" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete_message">
                                    <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                    <input type="submit" value="Delete" class="button">
                                </form>
                            <?php endif; ?>

                            <h4>Replies:</h4>
                            <?php
                            // 獲取回復
                            $message_id = $message['id'];
                            $reply_sql = "SELECT username, content, reply_time FROM replies WHERE message_id = ? ORDER BY reply_time ASC";
                            $reply_stmt = $conn->prepare($reply_sql);
                            $reply_stmt->bind_param("i", $message_id);
                            $reply_stmt->execute();
                            $replies_result = $reply_stmt->get_result();
                            ?>
                            <?php if ($replies_result->num_rows > 0): ?>
                                <?php while ($reply = $replies_result->fetch_assoc()): ?>
                                    <div class="reply">
                                        <p><?php echo nl2br(htmlspecialchars($reply['content'])); ?></p>
                                        <small>Replied by <?php echo htmlspecialchars($reply['username']); ?> on <?php echo htmlspecialchars($reply['reply_time']); ?> (Taipei Time)</small>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <p>No replies yet.</p>
                            <?php endif; ?>
                            <form action="message_board.php" method="post">
                                <input type="hidden" name="action" value="reply_message">
                                <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                <label for="reply_content">Reply:</label>
                                <textarea id="reply_content" name="reply_content" rows="2" required></textarea>
                                <input type="submit" value="Reply">
                            </form>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No messages available.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>




