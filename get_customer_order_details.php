<?php
require_once 'includes/config.php';
require_once 'includes/check_customer_session.php';

if (!isset($_GET['id'])) {
    echo 'Order ID not provided';
    exit;
}

$customer_id = $_SESSION['customer_id'];
$order_id = intval($_GET['id']);

// Fetch order details - ensure it belongs to the customer
$query = "SELECT * FROM orders WHERE id = $order_id AND customer_id = $customer_id";
$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo 'Order not found or access denied';
    exit;
}

// Fetch order items
$items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
?>

<div style="margin-bottom: 2rem; color: var(--deep-brown);">
    <p><strong>Date:</strong>
        <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
    </p>
    <p><strong>Status:</strong>
        <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
            <?php echo $order['status']; ?>
        </span>
    </p>
    <p><strong>Delivery Address:</strong>
        <span style="color: var(--warm-brown);"><?php echo htmlspecialchars($order['delivery_address']); ?></span>
    </p>
</div>

<h3 style="font-family: 'Playfair Display', serif; color: var(--deep-brown); margin-bottom: 1rem;">Order Items</h3>
<div style="overflow-x: auto;">
    <table style="width: 100%; border-collapse: collapse; margin-top: 1rem; color: var(--deep-brown);">
        <thead>
            <tr style="border-bottom: 2px solid var(--butter);">
                <th style="text-align: left; padding: 0.75rem;">Item</th>
                <th style="text-align: left; padding: 0.75rem;">Price</th>
                <th style="text-align: center; padding: 0.75rem;">Qty</th>
                <th style="text-align: right; padding: 0.75rem;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                <tr style="border-bottom: 1px solid var(--butter);">
                    <td style="padding: 0.75rem; font-weight: 500;">
                        <?php echo htmlspecialchars($item['pastry_name']); ?>
                    </td>
                    <td style="padding: 0.75rem;">₱
                        <?php echo number_format($item['price'], 2); ?>
                    </td>
                    <td style="text-align: center; padding: 0.75rem;">
                        <?php echo $item['quantity']; ?>
                    </td>
                    <td style="text-align: right; padding: 0.75rem;">₱
                        <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                    </td>
                </tr>
            <?php endwhile; ?>
            <tr style="border-top: 2px solid var(--butter); font-weight: bold; font-size: 1.1rem;">
                <td colspan="3" style="text-align: right; padding: 1.5rem 0.75rem;">Total Amount:</td>
                <td style="text-align: right; padding: 1.5rem 0.75rem;">₱
                    <?php echo number_format($order['total_amount'], 2); ?>
                </td>
            </tr>
        </tbody>
    </table>
</div>