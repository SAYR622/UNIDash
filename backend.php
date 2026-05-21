<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentication - UniDash</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <style>
        body { 
            background-color: #121212; 
            color: #ffffff; 
            font-family: 'Nunito', sans-serif; 
        }
        .auth-card { 
            background-color: #1e1e1e; 
            border: 1px solid #333; 
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
        }
        .text-accent { color: #00FFFF; }
        .btn-accent { 
            background-color: #00FFFF; 
            color: #000000; 
            font-weight: bold; 
            transition: 0.2s; 
        }
        .btn-accent:hover { 
            background-color: #00cccc; 
            color: #000000; 
        }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center vh-100">

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="auth-card p-4 p-md-5 text-center">
                    
                    <?php
                    // --- 1. DATABASE CONNECTION ---
                    $servername = "localhost";
                    $username = "root";
                    $password = ""; 
                    $dbname = "unidash";

                    $conn = new mysqli($servername, $username, $password, $dbname);

                    if ($conn->connect_error) {
                        die("<i class='fas fa-exclamation-triangle text-danger mb-3' style='font-size: 4rem;'></i><h2 class='fw-bold text-danger'>Connection Failed</h2><p class='text-muted'>" . $conn->connect_error . "</p>");
                    }

                    // Ensure we actually got a form submission
                    if (isset($_POST['form_type'])) {
                        
                        $formType = $_POST['form_type'];

                        // ==========================================
                        // LOGIC FOR SIGN UP
                        // ==========================================
                        if ($formType == 'signup') {
                            $fname = $_POST['fname'];
                            $lname = $_POST['lname'];
                            $email = $_POST['uniemail'];
                            $pass  = $_POST['pass'];
                            $memtype = 'customer'; // Defaulting to customer for new signups

                            $sql_insert = "INSERT INTO members (memtype, fname, lname, uniemail, pass) 
                                           VALUES ('$memtype', '$fname', '$lname', '$email', '$pass')";

                            if ($conn->query($sql_insert) === TRUE) {
                                echo "<i class='fas fa-check-circle text-accent mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-white'>Registration Successful!</h2>";
                                echo "<p class='text-muted mb-4'>Welcome to UniDash, <b>$fname</b>. You can now log in.</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Return to Home</a>";
                            } else {
                                echo "<i class='fas fa-times-circle text-danger mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-danger'>Registration Failed</h2>";
                                echo "<p class='text-muted mb-4'>Error: " . $conn->error . "</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Go Back</a>";
                            }
                        }

                        // ==========================================
                        // LOGIC FOR ADMIN ADDING A DISPATCHER
                        // ==========================================
                        elseif ($formType == 'add_dispatcher') {
                            $fname = $_POST['fname'];
                            $lname = $_POST['lname'];
                            $email = $_POST['uniemail'];
                            $pass  = $_POST['pass'];
                            
                            // FORCE the account type to be a dispatcher
                            $memtype = 'dispatcher'; 

                            $sql_insert = "INSERT INTO members (memtype, fname, lname, uniemail, pass) 
                                           VALUES ('$memtype', '$fname', '$lname', '$email', '$pass')";

                            if ($conn->query($sql_insert) === TRUE) {
                                echo "<i class='fas fa-id-badge text-warning mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-white'>Dispatcher Hired!</h2>";
                                echo "<p class='text-muted mb-4'><b>$fname $lname</b> now has official dispatch access to UniDash.</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Return to Store</a>";
                            } else {
                                echo "<i class='fas fa-times-circle text-danger mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-danger'>Account Creation Failed</h2>";
                                echo "<p class='text-muted mb-4'>Error: " . $conn->error . "</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Go Back</a>";
                            }
                        }

                        // ==========================================
                        // LOGIC FOR LOG IN
                        // ==========================================
                        elseif ($formType == 'login') {
                            $login_email = $_POST['login_email'];
                            $login_pass  = $_POST['login_pass'];

                            $sql_select = "SELECT * FROM members WHERE uniemail = '$login_email' AND pass = '$login_pass'";
                            $result = $conn->query($sql_select);

                            if ($result == FALSE) {
                                echo "<i class='fas fa-exclamation-triangle text-danger mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-danger'>System Error</h2>";
                                echo "<p class='text-muted mb-4'>Error executing query: " . $conn->error . "</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Go Back</a>";
                            } 
                            else if ($result->num_rows > 0) {
                                // We found a match!
                                $row = $result->fetch_assoc();
                                
                                // Encode the data so it's safe to put in a URL
                                $f = urlencode($row["fname"]);
                                $l = urlencode($row["lname"]);
                                $e = urlencode($row["uniemail"]);
                                $t = urlencode($row["memtype"]);
                                
                                echo "<i class='fas fa-user-circle text-accent mb-3' style='font-size: 4.5rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-white'>Login Successful!</h2>";
                                echo "<p class='text-muted mb-4 fs-5'>Welcome back, <b class='text-white'>" . $row["fname"] . "</b>!</p>";
                                
                                // The main Dashboard Button
                                echo "<a href='index.php?logged_in=true&fname=$f&lname=$l&email=$e&type=$t' class='btn btn-accent rounded-pill px-4 py-2 w-100 fs-5 mt-2'>Go to Dashboard <i class='fas fa-arrow-right ms-2'></i></a>";
                            } 
                            else {
                                echo "<i class='fas fa-lock text-danger mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-danger'>Login Failed</h2>";
                                echo "<p class='text-muted mb-4'>Invalid email or password. Please check your credentials and try again.</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Go Back</a>";
                            }
                        }

                        // ==========================================
                        // LOGIC FOR CHECKOUT
                        // ==========================================
                        elseif ($formType == 'checkout') {
                            
                            // Grab data from the JavaScript form
                            $cart_json = $_POST['cart_data'];
                            $fname = $conn->real_escape_string($_POST['fname']);
                            $membertype = $conn->real_escape_string($_POST['membertype']);
                            $deliver_to = $conn->real_escape_string($_POST['deliver_to']);
                            
                            // NEW: Grab the payment method radio button value
                            $payment_method = $conn->real_escape_string($_POST['payment_method']);
                            
                            // Decode the JSON to calculate price
                            $cart_array = json_decode($cart_json, true);
                            $total_amount = 0;
                            foreach($cart_array as $item) {
                                $total_amount += ($item['price'] * $item['qty']);
                            }
                            
                            $safe_cart_data = $conn->real_escape_string($cart_json);
                            
                            // Insert the order with the payment method included
                            $sql_insert_order = "INSERT INTO orders (order_data, total_amount, fname, membertype, deliver_to, payment_method) 
                                                 VALUES ('$safe_cart_data', '$total_amount', '$fname', '$membertype', '$deliver_to', '$payment_method')";
                            
                            if ($conn->query($sql_insert_order) === TRUE) {
                                echo "<i class='fas fa-check-circle text-accent mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-white'>Order Placed!</h2>";
                                echo "<p class='text-muted mb-4'>Your cart has been successfully sent to the outlet. <br><br><b>Total Paid: LKR " . number_format($total_amount) . "</b><br><small class='text-muted'>Via " . htmlspecialchars($payment_method) . "</small></p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Return to Store</a>";
                            } else {
                                echo "<i class='fas fa-times-circle text-danger mb-3' style='font-size: 4rem;'></i>";
                                echo "<h2 class='fw-bold mb-3 text-danger'>Checkout Failed</h2>";
                                echo "<p class='text-muted mb-4'>Error saving order: " . $conn->error . "</p>";
                                echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Go Back</a>";
                            }
                        }

                    } else {
                        echo "<i class='fas fa-question-circle text-warning mb-3' style='font-size: 4rem;'></i>";
                        echo "<h2 class='fw-bold mb-3 text-white'>No Data Received</h2>";
                        echo "<p class='text-muted mb-4'>Please submit the form from the main store page.</p>";
                        echo "<a href='index.php' class='btn btn-outline-light rounded-pill px-4 py-2 w-100 fw-semibold'>Return to Home</a>";
                    }

                    $conn->close();
                    ?>

                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>