<?php
session_start();
require_once 'includes/config.php';

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$customer_id = $_SESSION['customer_id'];

// Handle GET request for cart items
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_cart') {
    $cart_query = "SELECT c.id as cart_id, c.quantity, p.id as pastry_id, p.name, p.price 
                   FROM cart c 
                   JOIN pastries p ON c.pastry_id = p.id 
                   WHERE c.customer_id = $customer_id";
    $cart_result = mysqli_query($conn, $cart_query);

    $items = [];
    while ($item = mysqli_fetch_assoc($cart_result)) {
        $items[] = $item;
    }

    echo json_encode(['success' => true, 'items' => $items]);
    exit;
}

// Handle POST actions
$action = $_POST['action'] ?? '';

switch ($action) {
    case 'add':
        $pastry_id = intval($_POST['pastry_id']);

        // Check if item already in cart
        $check_query = "SELECT id, quantity FROM cart WHERE customer_id = $customer_id AND pastry_id = $pastry_id";
        $check_result = mysqli_query($conn, $check_query);

        if (mysqli_num_rows($check_result) > 0) {
            // Update quantity
            $row = mysqli_fetch_assoc($check_result);
            $new_qty = $row['quantity'] + 1;
            $update_query = "UPDATE cart SET quantity = $new_qty WHERE id = {$row['id']}";
            mysqli_query($conn, $update_query);
        } else {
            // Insert new item
            $insert_query = "INSERT INTO cart (customer_id, pastry_id, quantity) VALUES ($customer_id, $pastry_id, 1)";
            mysqli_query($conn, $insert_query);
        }

        echo json_encode(['success' => true, 'message' => 'Added to cart']);
        break;

    case 'update':
        $cart_id = intval($_POST['cart_id']);
        $quantity = intval($_POST['quantity']);

        if ($quantity < 1) {
            echo json_encode(['success' => false, 'message' => 'Invalid quantity']);
            exit;
        }

        $update_query = "UPDATE cart SET quantity = $quantity WHERE id = $cart_id AND customer_id = $customer_id";
        mysqli_query($conn, $update_query);

        echo json_encode(['success' => true, 'message' => 'Cart updated']);
        break;

    case 'remove':
        $cart_id = intval($_POST['cart_id']);

        $delete_query = "DELETE FROM cart WHERE id = $cart_id AND customer_id = $customer_id";
        mysqli_query($conn, $delete_query);

        echo json_encode(['success' => true, 'message' => 'Item removed']);
        break;

    case 'clear':
        $delete_query = "DELETE FROM cart WHERE customer_id = $customer_id";
        mysqli_query($conn, $delete_query);

        echo json_encode(['success' => true, 'message' => 'Cart cleared']);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>