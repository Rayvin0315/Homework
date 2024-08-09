<?php
header('Content-Type: application/json');
include('database.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    $height = $_POST['height'];
    $gender = $_POST['gender'];
    $bmr = $_POST['bmr']; // BMR field

    // Check if ID and other parameters are valid
    if (isset($id) && is_numeric($id) && isset($name) && isset($age) && isset($weight) && isset($height) && isset($gender) && isset($bmr)) {
        $sql = "UPDATE users SET name = ?, age = ?, weight = ?, height = ?, gender = ?, bmr = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sddssdi', $name, $age, $weight, $height, $gender, $bmr, $id);

        if ($stmt->execute()) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => $stmt->error]);
        }

        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    }

    $conn->close();
}
?>








