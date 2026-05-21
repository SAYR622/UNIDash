<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - UniDash</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background-color: #121212; 
            color: #ffffff; 
            font-family: 'Nunito', sans-serif; 
        }
        .text-accent { color: #00FFFF; }
        .table-custom {
            --bs-table-bg: #1e1e1e;
            border: 1px solid #333;
            border-radius: 10px;
            overflow: hidden;
        }
        .item-badge {
            background-color: #2b2b2b;
            border: 1px solid #444;
            color: #00FFFF;
        }
    </style>
</head>
<body class="p-4 p-md-5">

    <div class="container-fluid max-w-1200">
        
        <?php 
        // Grab the user's name from the URL
        $currentUser = isset($_GET['user']) ? $_GET['user'] : 'Guest'; 
        ?>

        <div class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom border-secondary">
            <h1 class="fw-bold m-0"><i class="fas fa-shopping-bag text-primary me-3"></i>My Past Orders</h1>
            <a href="index.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                <i class="fas fa-arrow-left me-2"></i>Back to Store
            </a>
        </div>

        <div class="table-responsive shadow-lg rounded">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead class="table-dark text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4 py-3">Order #</th>
                        <th class="py-3">Delivery Location</th>
                        <th class="py-3 w-40">Items Ordered</th>
                        <th class="py-3">Payment Info</th>
                        <th class="py-3 text-end pe-4">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = new mysqli("localhost", "root", "", "unidash");
                    
                    if ($conn->connect_error) {
                        die("<tr><td colspan='5' class='text-danger text-center py-4'>Database Connection Failed</td></tr>");
                    }

                    // Secure the variable to prevent SQL Injection
                    $safeUser = $conn->real_escape_string($currentUser);

                    // Only fetch orders that belong to THIS user!
                    $sql = "SELECT * FROM orders WHERE fname = '$safeUser' ORDER BY order_time DESC";
                    $result = $conn->query($sql);

                    // FIXED: Removed the duplicate IF statement here!
                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            
                            $items = json_decode($row['order_data'], true);
                            $itemListHTML = "";
                            if ($items) {
                                foreach($items as $item) {
                                    $outletName = htmlspecialchars($item['outlet'] ?? 'Unknown');
                                    $itemName = htmlspecialchars($item['name']);
                                    $itemListHTML .= "<span class='badge item-badge me-2 mb-1 fs-6 fw-normal px-3 py-2'>" . $item['qty'] . "x " . $itemName . " <small class='text-muted ms-1'>(" . $outletName . ")</small></span> ";
                                }
                            } else {
                                $itemListHTML = "<span class='text-muted fst-italic'>Data unreadable</span>";
                            }

                            $location = htmlspecialchars($row['deliver_to'] ?? 'Not Specified');
                            
                            $payMethod = htmlspecialchars($row['payment_method'] ?? 'Cash on Delivery');
                            $payBadgeClass = ($payMethod === 'Online Payment') ? 'bg-primary text-white' : 'bg-success text-dark';
                            $payIcon = ($payMethod === 'Online Payment') ? 'fa-credit-card' : 'fa-money-bill-wave';

                            // --- STATUS BADGE LOGIC ---
                            $status = htmlspecialchars($row['status'] ?? 'Pending');
                            
                            // Green badge for Fulfilled, Orange blinking badge for Pending
                            if ($status === 'Fulfilled') {
                                $statusBadge = "<span class='badge bg-secondary text-white p-2 fs-6 rounded-pill px-4'><i class='fas fa-check-double me-2'></i>Fulfilled</span>";
                            } else {
                                $statusBadge = "<span class='badge bg-warning text-dark p-2 fs-6 rounded-pill px-4'><i class='fas fa-spinner fa-spin me-2'></i>Pending</span>";
                            }

                            echo "<tr>
                                <td class='ps-4 align-top pt-4'>
                                    <span class='fw-bold fs-5 text-accent'>#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</span><br>
                                    <small class='text-muted'>" . date('d M Y, h:i A', strtotime($row['order_time'])) . "</small>
                                </td>
                                <td class='align-top pt-4'>
                                    <div class='text-white'><i class='fas fa-map-marker-alt text-accent me-2'></i>$location</div>
                                </td>
                                <td class='pt-4 pb-3'>$itemListHTML</td>
                                <td class='align-top pt-4'>
                                    <span class='badge $payBadgeClass p-2 fs-7 shadow-sm'><i class='fas $payIcon me-2'></i>$payMethod</span>
                                </td>
                                <td class='text-end pe-4 align-top pt-4'>$statusBadge</td>
                            </tr>";
                        }
                    } else {
                        echo "<tr>
                            <td colspan='5' class='text-center text-muted py-5'>
                                <i class='fas fa-receipt fs-1 mb-3 opacity-50'></i>
                                <h5>You haven't placed any orders yet, " . htmlspecialchars($currentUser) . ".</h5>
                                <a href='index.php' class='btn btn-accent mt-3 fw-bold'>Start Shopping</a>
                            </td>
                        </tr>";
                    }

                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>