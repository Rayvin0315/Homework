<?php
header('Content-Type: application/json');
include('database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    // Check if ID is provided and is an integer
    if (isset($id) && is_numeric($id)) {
        $sql = "SELECT * FROM users WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode($result->fetch_assoc());
        } else {
            echo json_encode(['error' => 'No record found']);
        }

        $stmt->close();
    } else {
        echo json_encode(['error' => 'Invalid ID']);
    }

    $conn->close();
}
?>



