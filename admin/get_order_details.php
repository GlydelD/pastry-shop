<?php
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';

if (!isset($_GET['id'])) {
    echo 'Order ID not provided';
    exit;
}

$order_id = intval($_GET['id']);

// Fetch order details including items
$query = "SELECT o.*, c.first_name, c.last_name, c.email, c.phone, c.address as customer_address
          FROM orders o 
          LEFT JOIN customers c ON o.customer_id = c.id 
          WHERE o.id = $order_id";
$result = mysqli_query($conn, $query);
$order = mysqli_fetch_assoc($result);

if (!$order) {
    echo 'Order not found';
    exit;
}

// Fetch order items
$items_query = "SELECT oi.*, p.name, p.price 
                FROM order_items oi 
                JOIN pastries p ON oi.pastry_id = p.id 
                WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
?>

<div style="margin-bottom: 2rem;">
    <p><strong>Date:</strong>
        <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?>
    </p>
    <p><strong>Status:</strong> <span style="text-transform: capitalize; font-weight: bold;">
            <?php echo $order['status']; ?>
        </span></p>
    <p><strong>Customer:</strong>
        <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?>
    </p>
    <p><strong>Email:</strong>
        <?php echo htmlspecialchars($order['email']); ?>
    </p>
    <p><strong>Contact:</strong>
        <?php echo htmlspecialchars($order['phone']); ?>
    </p>
    <p><strong>Delivery Address:</strong>
        <?php echo htmlspecialchars($order['delivery_address']); ?>
    </p>
    <?php if ($order['notes']): ?>
        <p><strong>Notes:</strong>
            <?php echo nl2br(htmlspecialchars($order['notes'])); ?>
        </p>
    <?php endif; ?>
</div>

<h3>Order Items</h3>
<table style="width: 100%; border-collapse: collapse; margin-top: 1rem;">
    <thead>
        <tr style="border-bottom: 2px solid #eee;">
            <th style="text-align: left; padding: 0.5rem;">Item</th>
            <th style="text-align: left; padding: 0.5rem;">Price</th>
            <th style="text-align: center; padding: 0.5rem;">Qty</th>
            <th style="text-align: right; padding: 0.5rem;">Subtotal</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
            <tr style="border-bottom: 1px solid #f9f9f9;">
                <td style="padding: 0.5rem;">
                    <?php echo htmlspecialchars($item['name']); ?>
                </td>
                <td style="padding: 0.5rem;">₱
                    <?php echo number_format($item['price'], 2); ?>
                </td>
                <td style="text-align: center; padding: 0.5rem;">
                    <?php echo $item['quantity']; ?>
                </td>
                <td style="text-align: right; padding: 0.5rem;">₱
                    <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </td>
            </tr>
        <?php endwhile; ?>
        <tr style="border-top: 2px solid #eee; font-weight: bold;">
            <td colspan="3" style="text-align: right; padding: 1rem 0.5rem;">Total Amount:</td>
            <td style="text-align: right; padding: 1rem 0.5rem;">₱
                <?php echo number_format($order['total_amount'], 2); ?>
            </td>
        </tr>
    </tbody>
</table>