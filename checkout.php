<?php
session_start();
require_once 'includes/config.php';

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

$customer_id = $_SESSION['customer_id'];
$customer_name = $_SESSION['customer_name'];

// Get customer address if available
$address_query = "SELECT address FROM customers WHERE id = $customer_id";
$address_result = mysqli_query($conn, $address_query);
$saved_address = '';
if ($address_result && mysqli_num_rows($address_result) > 0) {
    $saved_address = mysqli_fetch_assoc($address_result)['address'];
}

// Get cart items
$cart_query = "SELECT c.*, p.name, p.price 
               FROM cart c 
               JOIN pastries p ON c.pastry_id = p.id 
               WHERE c.customer_id = $customer_id";

// Filter by selected items if provided
$selected_items = isset($_GET['items']) ? explode(',', $_GET['items']) : [];
$selected_ids_str = ''; // For use in SQL later

if (!empty($selected_items)) {
    // Sanitize and validate IDs
    $valid_ids = [];
    foreach ($selected_items as $id) {
        if (is_numeric($id)) {
            $valid_ids[] = intval($id);
        }
    }

    if (!empty($valid_ids)) {
        $selected_ids_str = implode(',', $valid_ids);
        $cart_query .= " AND c.id IN ($selected_ids_str)";
    }
}

$cart_result = mysqli_query($conn, $cart_query);

if (mysqli_num_rows($cart_result) === 0) {
    header('Location: customer_dashboard.php');
    exit;
}

// Calculate total
$total = 0;
$order_items = [];
while ($item = mysqli_fetch_assoc($cart_result)) {
    $subtotal = $item['price'] * $item['quantity'];
    $total += $subtotal;
    $order_items[] = $item;
}

// Process checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate delivery address
    if (empty($_POST['delivery_address'])) {
        $error = 'Delivery address is required.';
    } else {
        $delivery_address = mysqli_real_escape_string($conn, trim($_POST['delivery_address']));

        // Start transaction
        mysqli_begin_transaction($conn);

        try {
            // Create order with delivery address
            $insert_order = "INSERT INTO orders (customer_id, customer_name, delivery_address, total_amount, status) 
                            VALUES ($customer_id, '$customer_name', '$delivery_address', $total, 'Pending')";
            mysqli_query($conn, $insert_order);
            $order_id = mysqli_insert_id($conn);

            // Create order items and update stock
            foreach ($order_items as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                $pastry_name_escaped = mysqli_real_escape_string($conn, $item['name']);

                $insert_item = "INSERT INTO order_items (order_id, pastry_id, pastry_name, quantity, price, subtotal) 
                               VALUES ($order_id, {$item['pastry_id']}, '$pastry_name_escaped', {$item['quantity']}, {$item['price']}, $subtotal)";
                mysqli_query($conn, $insert_item);

                // Deduct stock
                $update_stock = "UPDATE pastries SET stock_quantity = stock_quantity - {$item['quantity']} WHERE id = {$item['pastry_id']}";
                mysqli_query($conn, $update_stock);
            }

            // Clear purchased items from cart
            if (!empty($selected_ids_str)) {
                $clear_cart = "DELETE FROM cart WHERE customer_id = $customer_id AND id IN ($selected_ids_str)";
            } else {
                // Should technically rely on the same ID list as retrieval, but safe fallback
                $clear_cart = "DELETE FROM cart WHERE customer_id = $customer_id";
            }
            mysqli_query($conn, $clear_cart);

            // Commit transaction
            mysqli_commit($conn);

            // Redirect to success page
            header('Location: customer_dashboard.php?order_success=1');
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Checkout failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Artisan Pastry Shop</title>
    <link rel="stylesheet" href="css/style.css">
</head>

<body>
    <nav>
        <div class="container">
            <a href="index.php" class="logo">Pâtisserie</a>
            <ul class="nav-links">
                <li><a href="customer_dashboard.php">Back to Shop</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 style="font-family: 'Playfair Display', serif; color: var(--deep-brown);">Checkout</h1>
            <p style="color: var(--warm-brown);">Review your order</p>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <div class="cart-container">
            <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 1.5rem;">Order Summary</h2>

            <form method="POST" action="">
                <div class="form-group" style="margin-bottom: 1.5rem;">
                    <label for="delivery_address"
                        style="display: block; margin-bottom: 0.5rem; font-weight: 600; color: var(--deep-brown);">
                        Delivery Address / Location <span style="color: var(--raspberry);">*</span>
                    </label>
                    <textarea id="delivery_address" name="delivery_address" required
                        placeholder="Enter your complete delivery address (Street, Barangay, City, ZIP)"
                        style="width: 100%; padding: 0.8rem; border: 1px solid var(--crust); border-radius: 8px; font-family: inherit; min-height: 100px; resize: vertical;"><?php echo htmlspecialchars($saved_address); ?></textarea>
                </div>

                <?php foreach ($order_items as $item): ?>
                    <div class="cart-item">
                        <div class="cart-item-details">
                            <h4>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h4>
                            <p>Quantity:
                                <?php echo $item['quantity']; ?> × ₱
                                <?php echo number_format($item['price'], 2); ?>
                            </p>
                        </div>
                        <div class="cart-item-price">
                            ₱
                            <?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="cart-total">
                    Total: ₱
                    <?php echo number_format($total, 2); ?>
                </div>

                <button type="submit" class="btn" style="width: 100%; margin-top: 1rem;">
                    Confirm Order
                </button>
            </form>
        </div>
    </div>
</body>

</html>