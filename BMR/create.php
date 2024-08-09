<?php
$conn = new mysqli('localhost', 'root', '', 'bmr_calculator_db');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$name = $_POST['name'];
$age = $_POST['age'];
$weight = $_POST['weight'];
$height = $_POST['height'];
$gender = $_POST['gender'];
$bmr = $_POST['bmr']; // New field for BMR

$sql = "INSERT INTO users (name, age, weight, height, gender, bmr) VALUES ('$name', '$age', '$weight', '$height', '$gender', '$bmr')";

if ($conn->query($sql) === TRUE) {
    echo "New record created successfully";
} else {
    echo "Error: " . $sql . "<br>" . $conn->error;
}

$conn->close();
?>



