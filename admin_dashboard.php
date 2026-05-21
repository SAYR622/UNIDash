<!DOCTYPE html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dispatch Dashboard - UniDash</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: 'Nunito', sans-serif; }
        .text-accent { color: #00FFFF; }
        .table-custom { --bs-table-bg: #1e1e1e; border: 1px solid #333; border-radius: 10px; overflow: hidden; }
        .item-badge { background-color: #2b2b2b; border: 1px solid #444; color: #00FFFF; }
        .row-fulfilled { opacity: 0.6; } /* Dims the row when order is complete */
    </style>
</head>
<body class="p-4 p-md-5">

    <div class="container-fluid max-w-1200">
        <div class="d-flex justify-content-between align-items-center mb-5 pb-3 border-bottom border-secondary">
            <h1 class="fw-bold m-0"><i class="fas fa-motorcycle text-accent me-3"></i>Dispatch Dashboard</h1>
            <a href="index.php" class="btn btn-outline-light rounded-pill px-4 py-2 fw-semibold">
                <i class="fas fa-arrow-left me-2"></i>Back to Store
            </a>
        </div>

        <div class="table-responsive shadow-lg rounded">
            <table class="table table-custom table-hover align-middle mb-0">
                <thead class="table-dark text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4 py-3">Order #</th>
                        <th class="py-3">Customer & Location</th>
                        <th class="py-3 w-40">Items Ordered</th>
                        <th class="py-3">Payment Info</th>
                        <th class="py-3 text-end pe-4">Status & Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $conn = new mysqli("localhost", "root", "", "unidash");
                    if ($conn->connect_error) die("<tr><td colspan='5' class='text-danger text-center py-4'>Database Connection Failed</td></tr>");

                    $sql = "SELECT * FROM orders ORDER BY order_time DESC";
                    $result = $conn->query($sql);

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
                            }

                            $custName = htmlspecialchars($row['fname'] ?? 'Guest');
                            $custType = htmlspecialchars($row['membertype'] ?? 'Customer');
                            $location = htmlspecialchars($row['deliver_to'] ?? 'Not Specified');
                            
                            $payMethod = htmlspecialchars($row['payment_method'] ?? 'Cash on Delivery');
                            $payBadgeClass = ($payMethod === 'Online Payment') ? 'bg-primary text-white' : 'bg-success text-dark';
                            $payIcon = ($payMethod === 'Online Payment') ? 'fa-credit-card' : 'fa-money-bill-wave';
                            
                            // Check Status
                            $status = htmlspecialchars($row['status'] ?? 'Pending');
                            $isFulfilled = ($status === 'Fulfilled');
                            $rowClass = $isFulfilled ? 'row-fulfilled' : '';

                            echo "<tr class='$rowClass' id='order-row-" . $row['id'] . "'>
                                <td class='ps-4 align-top pt-4'>
                                    <span class='fw-bold fs-5 text-accent'>#" . str_pad($row['id'], 4, '0', STR_PAD_LEFT) . "</span><br>
                                    <small class='text-muted'>" . date('h:i A', strtotime($row['order_time'])) . "</small>
                                </td>
                                <td class='align-top pt-4'>
                                    <div class='fw-bold text-white mb-1'>$custName</div>
                                    <div class='text-muted small'><i class='fas fa-map-marker-alt text-accent me-2'></i>$location</div>
                                </td>
                                <td class='pt-4 pb-3'>$itemListHTML</td>
                                <td class='align-top pt-4'>
                                    <div class='fw-bold fs-5 text-white mb-1'>LKR " . number_format($row['total_amount']) . "</div>
                                    <span class='badge $payBadgeClass p-2 fs-7 shadow-sm'><i class='fas $payIcon me-2'></i>$payMethod</span>
                                </td>
                                <td class='text-end pe-4 align-top pt-4'>";
                                
                                // Show button if Pending, show Badge if Fulfilled
                                if (!$isFulfilled) {
                                    echo "<button onclick='markFulfilled(" . $row['id'] . ")' class='btn btn-warning fw-bold text-dark rounded-pill px-4 shadow-sm' id='btn-fulfill-" . $row['id'] . "'><i class='fas fa-check me-2'></i>Mark Fulfilled</button>";
                                } else {
                                    echo "<span class='badge bg-secondary text-white p-2 fs-6 rounded-pill px-4'><i class='fas fa-check-double me-2'></i>Fulfilled</span>";
                                }

                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='5' class='text-center text-muted py-5'><h5>No orders have been placed yet.</h5></td></tr>";
                    }
                    $conn->close();
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        function markFulfilled(orderId) {
            if(confirm("Mark this order as fulfilled and delivered?")) {
                fetch('update_order_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `id=${orderId}`
                }).then(() => {
                    // Visually update the dashboard without refreshing
                    const btn = document.getElementById('btn-fulfill-' + orderId);
                    const row = document.getElementById('order-row-' + orderId);
                    
                    btn.outerHTML = "<span class='badge bg-secondary text-white p-2 fs-6 rounded-pill px-4 fade-enter-end'><i class='fas fa-check-double me-2'></i>Fulfilled</span>";
                    row.classList.add('row-fulfilled');
                });
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>