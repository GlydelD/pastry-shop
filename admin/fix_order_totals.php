<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';

// Fetch all orders
$orders_query = "SELECT id, total_amount FROM orders";
$orders_result = mysqli_query($conn, $orders_query);

$updated_count = 0;
$errors = [];

echo "<h1>Fixing Order Totals...</h1>";
echo "<ul>";

while ($order = mysqli_fetch_assoc($orders_result)) {
    $order_id = $order['id'];
    $current_total = floatval($order['total_amount']);

    // Calculate real total from items
    $items_query = "SELECT SUM(subtotal) as real_total FROM order_items WHERE order_id = $order_id";
    $items_result = mysqli_query($conn, $items_query);
    $items_data = mysqli_fetch_assoc($items_result);
    $real_total = floatval($items_data['real_total'] ?? 0);

    if (abs($current_total - $real_total) > 0.01) {
        // Mismatch found, update order
        $update_query = "UPDATE orders SET total_amount = $real_total WHERE id = $order_id";
        if (mysqli_query($conn, $update_query)) {
            echo "<li>Order #$order_id: Updated from ₱$current_total to ₱$real_total</li>";
            $updated_count++;
        } else {
            $errors[] = "Failed to update Order #$order_id: " . mysqli_error($conn);
            echo "<li><strong style='color:red;'>Error updating Order #$order_id</strong></li>";
        }
    } else {
        echo "<li style='color:green;'>Order #$order_id is correct (₱$current_total)</li>";
    }
}

echo "</ul>";
echo "<h3>Finished. Updated $updated_count orders.</h3>";
echo "<a href='orders.php'>Back to Order Management</a>";
?>