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
$upload_dir = 'uploads/';

if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

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

// 處理檔案上傳
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_FILES['fileToUpload'])) {
        $target_file = $upload_dir . basename($_FILES["fileToUpload"]["name"]);
        $file_size = $_FILES["fileToUpload"]["size"];
        $file_name = basename($_FILES["fileToUpload"]["name"]);
        $upload_time = date("Y-m-d H:i:s");

        if (file_exists($target_file)) {
            $option = $_POST['overwrite'] ?? 'cancel';
            if ($option == 'overwrite') {
                unlink($target_file);
            } else {
                echo "<p class='error-message'>File already exists. Upload canceled.</p>";
                exit();
            }
        }

        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"], $target_file)) {
            $sql = "INSERT INTO files (username, file_name, file_size, upload_time) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssis", $user, $file_name, $file_size, $upload_time);
            $stmt->execute();
            $stmt->close();
            echo "<p class='success-message'>File uploaded successfully!</p>";

            log_user_action($conn, $user, 'Upload File', "File Name: $file_name, File Size: $file_size KB");
        } else {
            echo "<p class='error-message'>File upload failed!</p>";
        }
    }

    if (isset($_POST['action'])) {
        $file_id = $_POST['file_id'];

        if ($_POST['action'] == 'delete') {
            $sql = "SELECT file_name FROM files WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $file_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $file_name = $row['file_name'];
            $file_path = $upload_dir . $file_name;

            if (file_exists($file_path)) {
                unlink($file_path);
            }

            $sql = "DELETE FROM files WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $file_id);
            $stmt->execute();
            $stmt->close();

            echo "<p class='success-message'>File deleted successfully!</p>";

            log_user_action($conn, $user, 'Delete File', "File ID: $file_id, File Name: $file_name");
        }

        if ($_POST['action'] == 'rename') {
            $new_name = $_POST['new_name'];
            $sql = "SELECT file_name FROM files WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $file_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $old_name = $row['file_name'];
            $old_file_path = $upload_dir . $old_name;
            $new_file_path = $upload_dir . $new_name;

            if (rename($old_file_path, $new_file_path)) {
                $sql = "UPDATE files SET file_name = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("si", $new_name, $file_id);
                $stmt->execute();
                $stmt->close();
                echo "<p class='success-message'>File renamed successfully!</p>";

                log_user_action($conn, $user, 'Rename File', "File ID: $file_id, Old Name: $old_name, New Name: $new_name");
            } else {
                echo "<p class='error-message'>File rename failed!</p>";
            }
        }
    }
}

// 獲取用戶上傳的檔案
$sql = "SELECT id, file_name, file_size, upload_time FROM files WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container file-manager-container">
        <header>
            <h1>File Manager</h1>
            <a href="dashboard.php" class="button">Back to Dashboard</a>
        </header>
        <main>
            <form action="file_manager.php" method="post" enctype="multipart/form-data">
                <label for="fileToUpload">Select file to upload:</label>
                <input type="file" id="fileToUpload" name="fileToUpload" required>
                <input type="submit" value="Upload File">
                <p>Options if file exists:</p>
                <label><input type="radio" name="overwrite" value="cancel" checked> Cancel</label>
                <label><input type="radio" name="overwrite" value="overwrite"> Overwrite</label>
            </form>

            <h2>Uploaded Files</h2>
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>File Size</th>
                            <th>Upload Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['file_name']); ?></td>
                                <td><?php echo number_format($row['file_size'] / 1024, 2); ?> KB</td>
                                <td><?php echo htmlspecialchars($row['upload_time']); ?></td>
                                <td>
                                    <form action="file_manager.php" method="post" style="display:inline;">
                                        <input type="hidden" name="file_id" value="<?php echo $row['id']; ?>">
                                        <input type="submit" name="action" value="delete" class="button">
                                    </form>
                                    <form action="file_manager.php" method="post" style="display:inline;">
                                        <input type="hidden" name="file_id" value="<?php echo $row['id']; ?>">
                                        <input type="text" name="new_name" placeholder="New name" required>
                                        <input type="submit" name="action" value="rename" class="button">
                                    </form>
                                    <a href="<?php echo $upload_dir . htmlspecialchars($row['file_name']); ?>" download class="button">Download</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No files uploaded.</p>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>

<?php
$conn->close();
?>




