<?php
$conn = new mysqli("localhost", "root", "", "unidash");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    
    // Secure the ID to prevent SQL injection
    $id = $conn->real_escape_string($_POST['id']);
    
    // Delete the item from MariaDB
    $sql = "DELETE FROM menu_items WHERE id = '$id'";
    
    if ($conn->query($sql) === TRUE) {
        echo "Item deleted successfully";
    } else {
        echo "Error deleting record: " . $conn->error;
    }
}

$conn->close();
?>