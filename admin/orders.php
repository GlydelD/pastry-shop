<?php
$page_title = 'Order Management';
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';
require_once 'header.php';

// Handle Search and Filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

$query = "SELECT o.*, c.full_name, c.profile_picture FROM orders o LEFT JOIN customers c ON o.customer_id = c.id WHERE 1=1";

if ($search) {
    $query .= " AND (c.full_name LIKE '%$search%' OR o.id LIKE '%$search%')";
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
if (!$result) {
    die("Query Failed: " . mysqli_error($conn));
}
?>

<div class="container" style="padding-top: 2rem;">
    <h1>Order Management</h1>

    <!-- Search and Filter Section -->
    <form method="GET" class="admin-controls">
        <div class="admin-search-group">
            <div class="admin-search-input-wrapper">
                <input type="text" name="search" placeholder="Search orders..." class="admin-search-input"
                    value="<?php echo htmlspecialchars($search); ?>">
                <?php if ($search): ?>
                    <a href="orders.php"
                        style="position: absolute; right: 15px; top: 50%; transform: translateY(-50%); text-decoration: none; color: #999; font-size: 1.2rem;">&times;</a>
                <?php endif; ?>
            </div>
            <div class="admin-search-hint">Press Enter to search</div>
        </div>

        <select name="status" onchange="this.form.submit()" class="admin-filter-select">
            <option value="">All Statuses</option>
            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <select name="sort" onchange="this.form.submit()" class="admin-filter-select">
            <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="oldest" <?php echo $sort == 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
            <option value="amount_high" <?php echo $sort == 'amount_high' ? 'selected' : ''; ?>>Amount: High to Low
            </option>
            <option value="amount_low" <?php echo $sort == 'amount_low' ? 'selected' : ''; ?>>Amount: Low to High</option>
        </select>

        <?php if ($search || $status_filter || $sort != 'newest'): ?>
            <a href="orders.php" class="admin-btn-clear">
                <span>&times;</span> Clear Filters
            </a>
        <?php endif; ?>
    </form>

    <div class="admin-table-wrapper">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($order = mysqli_fetch_assoc($result)): ?>
                    <tr>
                        <td>
                            <?php echo date('M d, Y h:i A', strtotime($order['order_date'])); ?>
                        </td>
                        <td>
                            <div style="display: flex; align-items: center; gap: 0.8rem;">
                                <div
                                    style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: var(--butter); border: 1.5px solid var(--butter); flex-shrink: 0; color: var(--deep-brown);">
                                    <?php if (!empty($order['profile_picture'])): ?>
                                        <img src="../<?php echo htmlspecialchars($order['profile_picture']); ?>" alt=""
                                            style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <div
                                            style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                            👤</div>
                                    <?php endif; ?>
                                </div>
                                <span>
                                    <?php
                                    if ($order['full_name']) {
                                        echo htmlspecialchars($order['full_name']);
                                    } else {
                                        echo 'Unknown Customer';
                                    }
                                    ?>
                                </span>
                            </div>
                        </td>
                        <td style="font-weight: 700;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                        <td>
                            <select onchange="updateStatus(<?php echo $order['id']; ?>, this.value)"
                                class="status-badge status-<?php echo strtolower($order['status']); ?>"
                                style="border: none; cursor: pointer; outline: none; font-family: inherit;">
                                <option value="Pending" <?php echo $order['status'] == 'Pending' ? 'selected' : ''; ?>>Pending
                                </option>
                                <option value="Processing" <?php echo $order['status'] == 'Processing' ? 'selected' : ''; ?>>
                                    Processing</option>
                                <option value="Completed" <?php echo $order['status'] == 'Completed' ? 'selected' : ''; ?>>
                                    Completed</option>
                                <option value="Cancelled" <?php echo $order['status'] == 'Cancelled' ? 'selected' : ''; ?>>
                                    Cancelled</option>
                            </select>
                        </td>
                        <td>
                            <button class="btn btn-sm" onclick="viewOrder(<?php echo $order['id']; ?>)">View
                                Details</button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if (mysqli_num_rows($result) == 0): ?>
                    <tr>
                        <td colspan="5" style="padding: 3rem; text-align: center; color: #999; font-style: italic;">
                            No orders found matching your criteria.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderModal" class="modal"
    style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 1000; backdrop-filter: blur(4px);">
    <div
        style="background: var(--card-bg); color: var(--deep-brown); width: 90%; max-width: 600px; margin: 2rem auto; padding: 2.5rem; border-radius: 20px; position: relative; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 1.5rem;">Order Details #<span
                id="modalOrderId"></span></h2>
        <span onclick="closeModal('orderModal')"
            style="position: absolute; top: 1.25rem; right: 1.5rem; cursor: pointer; font-size: 1.5rem; color: var(--warm-brown);">&times;</span>

        <div id="orderDetails" style="margin-top: 1rem;">
            <!-- Details will be loaded here -->
        </div>
    </div>
</div>

<script>
    function updateStatus(orderId, status) {
        if (confirm('Are you sure you want to update the status to ' + status + '?')) {
            const formData = new FormData();
            formData.append('order_id', orderId);
            formData.append('status', status);

            fetch('update_order_status.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
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
        fetch('get_order_details.php?id=' + orderId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalOrderId').innerText = orderId;
                document.getElementById('orderDetails').innerHTML = html;
                document.getElementById('orderModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while fetching order details');
            });
    }

    function closeModal(id) {
        document.getElementById(id).style.display = 'none';
    }
</script>

<?php require_once '../includes/footer.php'; ?>