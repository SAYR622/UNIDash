<?php
$conn = new mysqli("localhost", "root", "", "unidash");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $id = $conn->real_escape_string($_POST['id']);
    
    // Change the status from Pending to Fulfilled
    $sql = "UPDATE orders SET status = 'Fulfilled' WHERE id = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Order marked as fulfilled!";
    } else {
        echo "Error: " . $conn->error;
    }
}
$conn->close();
?>