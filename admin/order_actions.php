<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'update_status':
        $order_id = intval($_POST['order_id']);
        $status = mysqli_real_escape_string($conn, $_POST['status']);

        $query = "UPDATE orders SET status = '$status' WHERE id = $order_id";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;

    case 'get_details':
        $order_id = intval($_POST['order_id']);

        // Get order
        $order_query = "SELECT * FROM orders WHERE id = $order_id";
        $order_result = mysqli_query($conn, $order_query);
        $order = mysqli_fetch_assoc($order_result);

        // Get order items
        $items_query = "SELECT * FROM order_items WHERE order_id = $order_id";
        $items_result = mysqli_query($conn, $items_query);
        $items = [];
        while ($item = mysqli_fetch_assoc($items_result)) {
            $items[] = $item;
        }

        echo json_encode([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>