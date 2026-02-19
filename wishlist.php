<?php
session_start();
$page_title = 'My Wishlist';
require_once 'includes/config.php';
require_once 'includes/check_customer_session.php';

$customer_id = $_SESSION['customer_id'];
$success = '';
$error = '';

// Add item to wishlist
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_wishlist'])) {
    $pastry_id = (int)$_POST['pastry_id'];

    // Check if pastry exists
    $check_query = "SELECT id FROM pastries WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "i", $pastry_id);
    mysqli_stmt_execute($check_stmt);

    if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0) {
        // Check if already in wishlist
        $wishlist_check = "SELECT id FROM wishlist WHERE customer_id = ? AND pastry_id = ?";
        $wishlist_stmt = mysqli_prepare($conn, $wishlist_check);
        mysqli_stmt_bind_param($wishlist_stmt, "ii", $customer_id, $pastry_id);
        mysqli_stmt_execute($wishlist_stmt);

        if (mysqli_num_rows(mysqli_stmt_get_result($wishlist_stmt)) === 0) {
            // Add to wishlist
            $insert_query = "INSERT INTO wishlist (customer_id, pastry_id) VALUES (?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, "ii", $customer_id, $pastry_id);

            if (mysqli_stmt_execute($insert_stmt)) {
                $success = 'Item added to wishlist!';
            } else {
                $error = 'Failed to add item to wishlist.';
            }
        } else {
            $error = 'Item already in wishlist!';
        }
    } else {
        $error = 'Pastry not found!';
    }
}

// Remove from wishlist (for Add to Cart functionality)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_from_wishlist'])) {
    $pastry_id = (int)$_POST['pastry_id'];
    
    $delete_query = "DELETE FROM wishlist WHERE customer_id = ? AND pastry_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $customer_id, $pastry_id);
    mysqli_stmt_execute($delete_stmt);
    
    // Return JSON response for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode(['success' => true]);
        exit;
    }
}

// Remove from wishlist
if (isset($_GET['remove']) && is_numeric($_GET['remove'])) {
    $remove_id = (int)$_GET['remove'];
    $delete_query = "DELETE FROM wishlist WHERE id = ? AND customer_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "ii", $remove_id, $customer_id);

    if (mysqli_stmt_execute($delete_stmt)) {
        $success = 'Item removed from wishlist!';
    }
}

// Fetch wishlist items
$wishlist_query = "SELECT w.id as wishlist_id, w.created_at, p.*
                  FROM wishlist w
                  JOIN pastries p ON w.pastry_id = p.id
                  WHERE w.customer_id = ?
                  ORDER BY w.created_at DESC";
$wishlist_stmt = mysqli_prepare($conn, $wishlist_query);
mysqli_stmt_bind_param($wishlist_stmt, "i", $customer_id);
mysqli_stmt_execute($wishlist_stmt);
$wishlist_result = mysqli_stmt_get_result($wishlist_stmt);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Pastry Shop</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-toggle.js"></script>
</head>
<body>
    <?php require_once 'includes/header.php'; ?>

    <section>
        <div class="form-container">
            <h2 style="text-align:center; font-family: 'Playfair Display', serif; margin-bottom:2rem;">My Wishlist</h2>

            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (mysqli_num_rows($wishlist_result) > 0): ?>
                <div class="pastry-grid">
                    <?php while ($item = mysqli_fetch_assoc($wishlist_result)): ?>
                        <div class="pastry-card fade-in-up">
                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>"
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" class="pastry-image">
                            <div class="pastry-info">
                                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                                <p><?php echo htmlspecialchars($item['description']); ?></p>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <span class="pastry-price">₱<?php echo number_format($item['price'], 2); ?></span>
                                    <div style="display: flex; gap: 0.5rem;">
                                        <!-- ✅ Fixed: use $item['id'] instead of $item['pastry_id'] -->
                                        <button class="btn" style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                                onclick="addToCart(<?php echo $item['id']; ?>)">Add to Cart</button>
                                        <button class="btn btn-danger" style="padding: 0.5rem 1rem; font-size: 0.85rem;"
                                                onclick="if(confirm('Remove from wishlist?')) window.location.href='wishlist.php?remove=<?php echo $item['wishlist_id']; ?>'">Remove</button>
                                    </div>
                                </div>
                            </div>
                        </div> <!-- ✅ Fixed: closing </div> for pastry-card was missing -->
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div style="text-align: center; padding: 3rem;">
                    <div style="font-size: 4rem; margin-bottom: 1rem; color: var(--warm-brown);">💝</div>
                    <h3 style="color: var(--warm-brown); margin-bottom: 0.5rem;">Your wishlist is empty</h3>
                    <p style="color: var(--warm-brown);">Start adding pastries you'd like to try later!</p>
                    <a href="index.php" class="btn" style="margin-top: 1rem;">Browse Pastries</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php require_once 'includes/footer.php'; ?>

    <script>
        // Auto-hide success/error messages after 5 seconds
        setTimeout(() => {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Add to Cart function
        function addToCart(pastryId) {
            fetch('cart_actions.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=add&pastry_id=' + pastryId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove from wishlist after adding to cart
                    fetch('wishlist.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'remove_from_wishlist=true&pastry_id=' + pastryId
                    })
                    .then(() => {
                        alert('Added to cart and removed from wishlist!');
                        location.reload();
                    })
                    .catch(() => {
                        alert('Added to cart! Please refresh the page.');
                        location.reload();
                    });
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
        }
    </script>
</body>
</html>