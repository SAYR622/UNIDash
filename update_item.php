<?php
$conn = new mysqli("localhost", "root", "", "unidash");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];

    // Update the database with the new values typed by the Admin
    $sql = "UPDATE menu_items SET item_name = '$name', price = '$price' WHERE id = '$id'";
    $conn->query($sql);
}
$conn->close();
?>