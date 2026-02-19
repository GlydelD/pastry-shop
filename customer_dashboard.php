<?php
$page_title = 'Customer Dashboard';

require_once 'includes/config.php';
require_once 'includes/check_customer_session.php';
require_once 'includes/header.php';

$customer_id = $_SESSION['customer_id'];

// Get customer data
$customer_query = "SELECT * FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Check for order success message
$order_success = isset($_GET['order_success']) ? true : false;

// Get cart count
$cart_count_query = "SELECT SUM(quantity) as total FROM cart WHERE customer_id = $customer_id";
$cart_count_result = mysqli_query($conn, $cart_count_query);
$cart_count = mysqli_fetch_assoc($cart_count_result)['total'] ?? 0;

// Get search and filter parameters
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$category = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Pagination setup
$items_per_page = 12;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

// Migration logic - ensure consistent categories
mysqli_query($conn, "UPDATE pastries SET category = 'Savory Pastries' WHERE category = 'Savory'");
mysqli_query($conn, "UPDATE pastries SET category = 'Sweet Pastries' WHERE category NOT IN ('Savory Pastries', 'Sweet Pastries')");

// Build query for pastries
$pastries_query = "SELECT * FROM pastries WHERE 1=1";
if ($search) {
    $pastries_query .= " AND (name LIKE '%$search%' OR description LIKE '%$search%')";
}
if ($category) {
    $pastries_query .= " AND category = '$category'";
}

// Apply sorting
if ($sort === 'price_asc') {
    $pastries_query .= " ORDER BY price ASC";
} elseif ($sort === 'price_desc') {
    $pastries_query .= " ORDER BY price DESC";
} else {
    $pastries_query .= " ORDER BY name ASC";
}

// Get total count for pagination
$count_query = str_replace("SELECT *", "SELECT COUNT(*) as total", $pastries_query);
$count_result = mysqli_query($conn, $count_query);
$total_pastries = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_pastries / $items_per_page);

// Add pagination to main query
$pastries_query .= " LIMIT $offset, $items_per_page";
$pastries_result = mysqli_query($conn, $pastries_query);

// Get categories (Hardcoded to 2 as requested)
$categories = ['Savory Pastries', 'Sweet Pastries'];
?>

<style>
    .order-item-clickable {
        cursor: pointer;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }
    .order-item-clickable:hover {
        background-color: var(--butter) !important;
        border-left-color: var(--honey);
        transform: translateX(5px);
    }
</style>

<div class="dashboard-container">
    <div class="dashboard-header"
        style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 2rem;">
        <div style="display: flex; align-items: center; gap: 2rem;">
            <?php if ($customer['profile_picture']): ?>
                <img src="<?php echo htmlspecialchars($customer['profile_picture']); ?>" alt="Profile Picture"
                    style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid var(--warm-brown); box-shadow: 0 4px 15px var(--shadow);">
            <?php else: ?>
                <div
                    style="width: 80px; height: 80px; border-radius: 50%; background: var(--butter); display: flex; align-items: center; justify-content: center; border: 3px solid var(--warm-brown); box-shadow: 0 4px 15px var(--shadow);">
                    <span style="font-size: 2rem; color: var(--warm-brown);">👤</span>
                </div>
            <?php endif; ?>
            <div>
                <h1 style="font-family: 'Playfair Display', serif; color: var(--deep-brown); margin: 0;">
                    Welcome,
                    <?php echo htmlspecialchars($customer['full_name']); ?>!
                </h1>
                <a href="customer_profile.php" class="btn-clear" style="margin-top: 0.5rem; display: inline-block;">
                    Edit Profile
                </a>
            </div>
        </div>
        <div style="display: flex; gap: 1rem;">
            <button class="btn" onclick="openCart()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <circle cx="9" cy="21" r="1" />
                    <circle cx="20" cy="21" r="1" />
                    <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" />
                </svg>
                Cart (<?php echo $cart_count; ?>)
            </button>
            <button class="btn btn-secondary" onclick="document.getElementById('ordersModal').classList.add('active')">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    style="display: inline-block; vertical-align: middle; margin-right: 0.5rem;">
                    <path
                        d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z" />
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96" />
                    <line x1="12" y1="22.08" x2="12" y2="12" />
                </svg>
                My Orders
            </button>
        </div>
    </div>

    <?php if ($order_success): ?>
        <div class="alert alert-success">Order placed successfully! Check "My Orders" to track your order.</div>
    <?php endif; ?>

    <!-- Search and Filter -->
    <div class="search-filter-bar">
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search pastries..."
                value="<?php echo htmlspecialchars($search); ?>" onkeyup="if(event.key === 'Enter') filterPastries()">
            <p style="font-size: 0.8rem; color: var(--warm-brown); margin-top: 5px;">Press Enter to search</p>
        </div>
        <select class="filter-select" id="categoryFilter" onchange="filterPastries()"
            style="width: 180px; height: 40px;">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat_name): ?>
                <option value="<?php echo htmlspecialchars($cat_name); ?>" <?php echo $category === $cat_name ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($cat_name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select class="filter-select" id="sortFilter" onchange="filterPastries()" style="width: 180px; height: 40px;">
            <option value="">Default (Name)</option>
            <option value="price_asc" <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_desc" <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Price: High to Low</option>
        </select>
        <?php if ($search || $category || $sort): ?>
            <a href="customer_dashboard.php" class="btn-clear">
                <span>&times;</span> Clear Filters
            </a>
        <?php endif; ?>
    </div>

    <!-- Featured Pastries Section -->
    <?php
    // Fetch featured pastries
    $featured_query = "SELECT * FROM pastries WHERE is_featured = 1 LIMIT 3";
    $featured_result = mysqli_query($conn, $featured_query);
    ?>

    <?php if (mysqli_num_rows($featured_result) > 0 && empty($search) && empty($category)): ?>
    <section style="margin-bottom: 3rem;">
        <h2 style="font-family: 'Playfair Display', serif; color: var(--deep-brown); margin-bottom: 1.5rem;">Fresh From the Oven</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2rem;">
            <?php while ($featured = mysqli_fetch_assoc($featured_result)): ?>
                <div class="pastry-card" style="display: flex; flex-direction: column; height: 100%; border: 2px solid var(--honey);">
                    <div style="position: relative; height: 250px; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($featured['image_url']); ?>" 
                             alt="<?php echo htmlspecialchars($featured['name']); ?>" 
                             style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease;">
                        <div style="position: absolute; top: 1rem; right: 1rem; background: var(--raspberry); color: white; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 600; font-size: 0.9rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                            Featured
                        </div>
                    </div>
                    <div class="pastry-info" style="flex: 1; display: flex; flex-direction: column;">
                        <h3 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><?php echo htmlspecialchars($featured['name']); ?></h3>
                        <p style="color: #666; margin-bottom: 1.5rem; flex: 1;"><?php echo htmlspecialchars($featured['description']); ?></p>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: auto;">
                            <div style="display: flex; flex-direction: column;">
                                <span class="pastry-price" style="font-size: 1.4rem;">₱<?php echo number_format($featured['price'], 2); ?></span>
                                <div style="margin-top: 0.2rem;">
                                    <?php if ($featured['stock_quantity'] <= 0): ?>
                                        <span class="badge" style="background: #ccc; color: #555; font-size: 0.8rem; padding: 0.2rem 0.5rem;">Out of Stock</span>
                                    <?php elseif ($featured['stock_quantity'] < 10): ?>
                                        <span class="badge" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba; font-size: 0.8rem; padding: 0.2rem 0.5rem;">Only <?php echo $featured['stock_quantity']; ?> left!</span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb; font-size: 0.8rem; padding: 0.2rem 0.5rem;">In Stock: <?php echo $featured['stock_quantity']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <button class="btn" onclick="addToCart(<?php echo $featured['id']; ?>)"
                                <?php echo $featured['stock_quantity'] <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                Add to Cart
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Pastries Grid -->
    <div class="pastry-grid">
        <?php if (mysqli_num_rows($pastries_result) > 0): ?>
            <?php while ($pastry = mysqli_fetch_assoc($pastries_result)): ?>
                <div class="pastry-card">
                    <img src="<?php echo htmlspecialchars($pastry['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($pastry['name']); ?>" class="pastry-image" loading="lazy">
                    <div class="pastry-info">
                        <h3>
                            <?php echo htmlspecialchars($pastry['name']); ?>
                        </h3>
                        <p>
                            <?php echo htmlspecialchars($pastry['description']); ?>
                        </p>
                        
                        <div style="margin-bottom: 0.5rem;">
                            <?php if ($pastry['stock_quantity'] <= 0): ?>
                                <span class="badge" style="background: #ccc; color: #555;">Out of Stock</span>
                            <?php elseif ($pastry['stock_quantity'] < 10): ?>
                                <span class="badge" style="background: #fff3cd; color: #856404; border: 1px solid #ffeeba;">Only <?php echo $pastry['stock_quantity']; ?> left!</span>
                            <?php else: ?>
                                <span class="badge" style="background: #d4edda; color: #155724; border: 1px solid #c3e6cb;">In Stock: <?php echo $pastry['stock_quantity']; ?></span>
                            <?php endif; ?>
                        </div>

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; flex-wrap: nowrap;">
                            <span class="pastry-price"
                                style="white-space: nowrap;">₱<?php echo number_format($pastry['price'], 2); ?></span>
                            <div style="display: flex; gap: 0.5rem; flex-shrink: 0; align-items: center;">
                                <button class="btn" style="padding: 0.6rem 1.2rem; font-size: 0.9rem;"
                                    onclick="addToCart(<?php echo $pastry['id']; ?>)"
                                    <?php echo $pastry['stock_quantity'] <= 0 ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                                    Add to Cart
                                </button>
                                <form method="POST" action="wishlist.php" style="display: inline; margin: 0;">
                                    <input type="hidden" name="pastry_id" value="<?php echo $pastry['id']; ?>">
                                    <button type="submit" name="add_to_wishlist" class="btn btn-secondary"
                                        style="padding: 0.6rem 0.9rem; font-size: 0.9rem; min-width: auto;">❤️</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div
                style="grid-column: 1 / -1; text-align: center; padding: 4rem; background: white; border-radius: 15px; box-shadow: 0 5px 20px var(--shadow);">
                <span style="font-size: 4rem; display: block; margin-bottom: 1rem;">🥐</span>
                <h3 style="font-family: 'Playfair Display', serif; color: var(--deep-brown);">No pastries found</h3>
                <p style="color: var(--warm-brown);">Try adjusting your search or category filter.</p>
                <button class="btn" style="margin-top: 1.5rem;" onclick="window.location.href='customer_dashboard.php'">View
                    All Pastries</button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Cart Modal -->
<div id="cartModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="font-family: 'Playfair Display', serif;">Shopping Cart</h2>
            <div style="display: flex; gap: 0.5rem; align-items: center;">
                <button class="btn btn-danger" onclick="clearCart()" title="Clear Cart"
                    style="padding: 0.5rem 0.8rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.3rem;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="3,6 5,6 21,6"></polyline>
                        <path d="m19,6v14a2,2 0 0,1 -2,2H7a2,2 0 0,1 -2,-2V6m3,0V4a2,2 0 0,1 2,-2h4a2,2 0 0,1 2,2v2">
                        </path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                    Clear All
                </button>
                <button class="modal-close"
                    onclick="document.getElementById('cartModal').classList.remove('active')">&times;</button>
            </div>
        </div>
        <div id="cartContent">
            <!-- Cart items loaded via AJAX -->
        </div>
    </div>
</div>

<!-- Orders Modal -->
<div id="ordersModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 style="font-family: 'Playfair Display', serif;">My Orders</h2>
            <button class="modal-close"
                onclick="document.getElementById('ordersModal').classList.remove('active')">&times;</button>
        </div>
        <div id="ordersContent">
            <?php
            $orders_query = "SELECT * FROM orders WHERE customer_id = $customer_id ORDER BY order_date DESC";
            $orders_result = mysqli_query($conn, $orders_query);

            if (mysqli_num_rows($orders_result) > 0):
                ?>
                <div class="cart-container">
                    <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                        <div class="cart-item order-item-clickable" onclick="viewOrderDetails(<?php echo $order['id']; ?>)">
                            <div class="cart-item-details">
                                <h4>Order #
                                    <?php echo $order['id']; ?>
                                </h4>
                                <p>Date:
                                    <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                                </p>
                                <p>Total: ₱
                                    <?php echo number_format($order['total_amount'], 2); ?>
                                </p>
                            </div>
                            <div style="display: flex; flex-direction: column; align-items: flex-end; gap: 0.5rem;">
                                <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                    <?php echo $order['status']; ?>
                                </span>
                                <span style="font-size: 0.8rem; color: var(--warm-brown);">Click to view details</span>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <p style="text-align: center; padding: 2rem;">No orders yet. Start shopping!</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Order Details Modal -->
<div id="orderDetailsModal" class="modal">
    <div class="modal-content" style="max-width: 700px;">
        <div class="modal-header">
            <h2 style="font-family: 'Playfair Display', serif;">Order Details #<span id="detailOrderId"></span></h2>
            <button class="modal-close"
                onclick="document.getElementById('orderDetailsModal').classList.remove('active')">&times;</button>
        </div>
        <div id="orderDetailsContent" style="padding: 1.5rem;">
            <!-- Content loaded via AJAX -->
        </div>
    </div>
</div>

<script>
    function addToCart(pastryId) {
        fetch('cart_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=add&pastry_id=' + pastryId
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Added to cart!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            });
    }

    function loadCart() {
        fetch('cart_actions.php?action=get_cart')
            .then(response => response.json())
            .then(data => {
                let html = '<div class="cart-container">';
                let total = 0;

                if (data.items && data.items.length > 0) {
                    html += '<div style="margin-bottom: 1rem;"><label><input type="checkbox" id="selectAll" onchange="toggleAll(this)" checked> Select All</label></div>';
                    
                    data.items.forEach(item => {
                        const subtotal = item.price * item.quantity;
                        // Default to checked
                        total += subtotal;
                        html += `
                    <div class="cart-item">
                        <div style="display: flex; align-items: center; gap: 0.5rem;">
                            <input type="checkbox" class="cart-item-checkbox" value="${item.cart_id}" data-subtotal="${subtotal}" onchange="updateCartTotal()" checked>
                            <div class="cart-item-details">
                                <h4>${item.name}</h4>
                                <p>₱${parseFloat(item.price).toFixed(2)} each</p>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 1rem;">
                            <div class="quantity-controls">
                                <button onclick="updateQuantity(${item.cart_id}, -1)">-</button>
                                <span>${item.quantity}</span>
                                <button onclick="updateQuantity(${item.cart_id}, 1)">+</button>
                            </div>
                            <div class="cart-item-price">₱${subtotal.toFixed(2)}</div>
                            <button class="action-btn delete" onclick="removeFromCart(${item.cart_id})">Remove</button>
                        </div>
                    </div>
                `;
                    });
                    html += `<div class="cart-total" id="cartTotalDisplay">Total: ₱${total.toFixed(2)}</div>`;
                    html += '<button class="btn" style="width: 100%; margin-top: 1rem;" onclick="proceedToCheckout()">Proceed to Checkout</button>';
                } else {
                    html += '<p style="text-align: center; padding: 2rem;">Your cart is empty</p>';
                }
                html += '</div>';
                document.getElementById('cartContent').innerHTML = html;
            });
    }

    function updateCartTotal() {
        let total = 0;
        const checkboxes = document.querySelectorAll('.cart-item-checkbox');
        let allChecked = true;
        
        checkboxes.forEach(cb => {
            if (cb.checked) {
                total += parseFloat(cb.dataset.subtotal);
            } else {
                allChecked = false;
            }
        });
        
        document.getElementById('cartTotalDisplay').textContent = `Total: ₱${total.toFixed(2)}`;
        document.getElementById('selectAll').checked = allChecked;
    }

    function toggleAll(source) {
        const checkboxes = document.querySelectorAll('.cart-item-checkbox');
        checkboxes.forEach(cb => {
            cb.checked = source.checked;
        });
        updateCartTotal();
    }

    function proceedToCheckout() {
        const checkboxes = document.querySelectorAll('.cart-item-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one item to checkout.');
            return;
        }
        
        const selectedIds = Array.from(checkboxes).map(cb => cb.value).join(',');
        window.location.href = `checkout.php?items=${selectedIds}`;
    }

    function updateQuantity(cartId, change) {
        event.stopPropagation(); // Prevent click from bubbling to modal or other listeners
        const btn = event.target;
        const quantitySpan = btn.parentElement.querySelector('span');
        const currentQty = parseInt(quantitySpan.textContent);
        const newQty = currentQty + change;

        if (newQty < 1) return;

        fetch('cart_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update&cart_id=${cartId}&quantity=${newQty}`
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update DOM directly instead of reloading everything
                    quantitySpan.textContent = newQty;
                    
                    // Update subtotal for this item
                    const cartItem = btn.closest('.cart-item');
                    const priceText = cartItem.querySelector('.cart-item-details p').textContent;
                    const priceMethod = parseFloat(priceText.replace('₱', '').replace(' each', ''));
                    const newSubtotal = priceMethod * newQty;
                    
                    cartItem.querySelector('.cart-item-price').textContent = `₱${newSubtotal.toFixed(2)}`;
                    
                    // Update checkbox data
                    const checkbox = cartItem.querySelector('.cart-item-checkbox');
                    checkbox.dataset.subtotal = newSubtotal;
                    
                    // Recalculate total if checked
                    updateCartTotal();
                }
            });
    }

    function removeFromCart(cartId) {
        if (!confirm('Remove this item from cart?')) return;

        fetch('cart_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove&cart_id=' + cartId
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadCart();
                    location.reload();
                }
            });
    }

    function clearCart() {
        if (!confirm('Are you sure you want to remove all items from your cart?')) return;

        fetch('cart_actions.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=clear'
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Cart cleared!');
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(() => alert('Something went wrong. Please try again.'));
    }

    function filterPastries() {
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter').value;
        const sort = document.getElementById('sortFilter').value;
        window.location.href = '?search=' + encodeURIComponent(search) + '&category=' + encodeURIComponent(category) + '&sort=' + encodeURIComponent(sort);
    }

    // Function to open cart and load items immediately
    function openCart() {
        document.getElementById('cartModal').classList.add('active');
        loadCart();
    }

    // Load cart when cart modal is opened
    // Event listener for cart modal clicks removed to prevent unwanted reloads
    // document.getElementById('cartModal').addEventListener('click', function (e) {
    //     if (e.target === this || e.target.classList.contains('btn')) {
    //         loadCart();
    //     }
    // });
    function viewOrderDetails(orderId) {
        document.getElementById('detailOrderId').innerText = orderId;
        document.getElementById('orderDetailsContent').innerHTML = '<div style="text-align: center; padding: 2rem;"><span style="font-size: 2rem;">🥧</span><p>Loading details...</p></div>';
        document.getElementById('orderDetailsModal').classList.add('active');

        fetch('get_customer_order_details.php?id=' + orderId)
            .then(response => response.text())
            .then(html => {
                document.getElementById('orderDetailsContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('orderDetailsContent').innerHTML = '<p style="color: var(--raspberry); text-align: center; padding: 2rem;">Error loading details. Please try again.</p>';
            });
    }
</script>