<?php
$page_title = 'Order Management';
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';
require_once 'header.php';

// Handle Search and Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$query = "SELECT o.*, c.first_name, c.last_name FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE 1=1";

if ($search) {
    $query .= " AND (c.first_name LIKE '%$search%' OR c.last_name LIKE '%$search%' OR o.id LIKE '%$search%')";
}

if ($status_filter) {
    $query .= " AND o.status = '$status_filter'";
}

switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY o.order_date ASC";
        break;
    case 'amount_high':
        $query .= " ORDER BY o.total_amount DESC";
        break;
    case 'amount_low':
        $query .= " ORDER BY o.total_amount ASC";
        break;
    default: // newest
        $query .= " ORDER BY o.order_date DESC";
        break;
}

$result = mysqli_query($conn, $query);
?>

<div class="container" style="padding-top: 2rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
        <h1>Order Management</h1>
        
        <!-- Search and Filter Form -->
        <form method="GET" style="display: flex; gap: 0.5rem; flex-wrap: wrap; align-items: center;">
            <div style="position: relative;">
                <input type="text" name="search" placeholder="Search orders..." value="<?php echo htmlspecialchars($search); ?>" 
                       style="padding: 0.5rem 1rem; border: 1px solid #ddd; border-radius: 8px; width: 250px;">
                <?php if($search): ?>
                    <a href="orders.php" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); text-decoration: none; color: #999;">&times;</a>
                <?php endif; ?>
            </div>
            
            <select name="status" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 8px; cursor: pointer;">
                <option value="">All Statuses</option>
                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            </select>

            <select name="sort" onchange="this.form.submit()" style="padding: 0.5rem; border: 1px solid #ddd; border-radius: 8px; cursor: pointer;">
                <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                <option value="amount_high" <?php echo $sort == 'amount_high' ? 'selected' : ''; ?>>Amount: High to Low</option>
                <option value="amount_low" <?php echo $sort == 'amount_low' ? 'selected' : ''; ?>>Amount: Low to High</option>
            </select>
        </form>
    </div>

    <div
        style="background: white; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: var(--cream); border-bottom: 2px solid var(--honey);">
                    <th style="padding: 1rem; text-align: left;">Date</th>
                    <th style="padding: 1rem; text-align: left;">Customer</th>
                    <th style="padding: 1rem; text-align: left;">Total</th>
                    <th style="padding: 1rem; text-align: left;">Status</th>
                    <th style="padding: 1rem; text-align: left;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = mysqli_fetch_assoc($result)): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 1rem;">
                            <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?>
                        </td>
                        <td style="padding: 1rem;">
                            <?php
                                if ($order['first_name']) {
                                    echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']);
                                } else {
                                    echo 'Unknown Customer';
                                }
                            ?>
                        </td>
                        <td style="padding: 1rem;">₱
                            <?php echo number_format($order['total_amount'], 2); ?>
                        </td>
                        <td style="padding: 1rem;">
                            <select onchange="updateStatus(<?php echo $order['id']; ?>, this.value)" style="padding: 0.25rem; border-radius: 4px; border: 1px solid #ddd;
                                    background: <?php
                                    echo match ($order['status']) {
                                        'pending' => '#fff3cd',
                                        'processing' => '#cce5ff',
                                        'completed' => '#d4edda',
                                        'cancelled' => '#f8d7da',
                                        default => '#fff'
                                    };
                                    ?>">
                                <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending
                                </option>
                                <option value="processing" <?php echo $order['status'] == 'processing' ? 'selected' : ''; ?>>
                                    Processing</option>
                                <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>
                                    Completed</option>
                                <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>
                                    Cancelled</option>
                            </select>
                        </td>
                        <td style="padding: 1rem;">
                            <button class="btn btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">View
                                Details</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if(mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="5" style="padding: 2rem; text-align: center; color: #666;">No orders found matching your criteria.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000;">
    <div
        style="background: white; width: 90%; max-width: 600px; margin: 2rem auto; padding: 2rem; border-radius: 8px; position: relative;">
        <h2>Order Details #<span id="modalOrderId"></span></h2>
        <span onclick="closeModal('orderModal')"
            style="position: absolute; top: 1rem; right: 1rem; cursor: pointer; font-size: 1.5rem;">&times;</span>

        <div id="orderDetails" style="margin-top: 1rem;">
            <!-- Details will be loaded here -->
        </div>
    </div>
</div>

<script>
    function updateStatus(orderId, status) {
        if(confirm('Are you sure you want to update the status to ' + status + '?')) {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', status);
            
            fetch('update_order_status.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    location.reload();
                } else {
                    alert('Error updating status: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while updating the status');
            });
        } else {
            location.reload(); // Reset select if cancelled
        }
    }

    function viewOrder(orderId) {
        const formData = new FormData();
        formData.append('action', 'get_details');
        formData.append('order_id', orderId);

        fetch('order_actions.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = `
                <div style="margin-bottom: 1rem;">
                    <strong>Status:</strong> ${data.order.status}<br>
                    <strong>Total Amount:</strong> ₱${parseFloat(data.order.total_amount).toFixed(2)}<br>
                    <strong>Date:</strong> ${data.order.created_at}
                </div>
                <h3>Items:</h3>
                <ul style="list-style: none; padding: 0;">
            `;

                    data.items.forEach(item => {
                        html += `
                    <li style="border-bottom: 1px solid #eee; padding: 0.5rem 0; display: flex; justify-content: space-between;">
                        <span>${item.quantity}x Pastry #${item.pastry_id}</span>
                        <span>₱${parseFloat(item.price_at_time).toFixed(2)}</span>
                    </li>
                `;
                    });

                    html += '</ul>';

                    document.getElementById('modalOrderId').innerText = orderId;
                    document.getElementById('orderDetails').innerHTML = html;
                    document.getElementById('orderModal').style.display = 'block';
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once '../includes/footer.php'; ?>