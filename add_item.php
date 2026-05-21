<?php
$conn = new mysqli("localhost", "root", "", "unidash");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Grab the data from the Admin form
    $outlet = $conn->real_escape_string($_POST['outlet_id']);
    $name = $conn->real_escape_string($_POST['item_name']);
    $price = (int)$_POST['price'];
    $img = $conn->real_escape_string($_POST['image_url']);
    
    // Default rating for everything except UniDash store is 0.0
    $rating = 0.0;
    if ($outlet === 'unidash_store') {
        $rating = 5.0; // Give new store items a default 5-star rating
    }

    $sql = "INSERT INTO menu_items (outlet_id, item_name, price, image_url, rating) 
            VALUES ('$outlet', '$name', $price, '$img', $rating)";

    if ($conn->query($sql) === TRUE) {
        // Send them right back to the store so they can see the new item immediately
        header("Location: index.php"); 
        exit();
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}
$conn->close();
?>