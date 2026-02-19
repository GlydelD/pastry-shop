<?php
session_start();
$page_title = 'Admin Dashboard';

require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';
require_once 'header.php';

$admin_username = $_SESSION['admin_username'];

// Get statistics
$total_orders_query = "SELECT COUNT(*) as total FROM orders";
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, $total_orders_query))['total'];

$total_revenue_query = "SELECT SUM(total_amount) as revenue FROM orders WHERE status != 'Cancelled'";
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, $total_revenue_query))['revenue'] ?? 0;

$total_products_query = "SELECT COUNT(*) as total FROM pastries";
$total_products = mysqli_fetch_assoc(mysqli_query($conn, $total_products_query))['total'];

$pending_orders_query = "SELECT COUNT(*) as total FROM orders WHERE status = 'Pending'";
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, $pending_orders_query))['total'];

// New dashboard statistics
$orders_today_query = "SELECT COUNT(*) as total FROM orders WHERE DATE(order_date) = CURDATE()";
$orders_today_result = mysqli_query($conn, $orders_today_query);
$orders_today = $orders_today_result ? mysqli_fetch_assoc($orders_today_result)['total'] : 0;

$low_stock_query = "SELECT COUNT(*) as total FROM pastries WHERE stock_quantity < 5";
$low_stock_result = mysqli_query($conn, $low_stock_query);
$low_stock_items = $low_stock_result ? mysqli_fetch_assoc($low_stock_result)['total'] : 0;

// Top treat (most sold pastry)
$top_treat_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                    FROM order_items oi 
                    JOIN pastries p ON oi.pastry_id = p.id 
                    GROUP BY p.id, p.name 
                    ORDER BY total_sold DESC 
                    LIMIT 1";
$top_treat_result = mysqli_query($conn, $top_treat_query);
if ($top_treat_result) {
    $top_treat = mysqli_fetch_assoc($top_treat_result);
} else {
    $top_treat = ['name' => 'No data', 'total_sold' => 0];
}

// Top selling pastries
$top_selling_query = "SELECT p.name, SUM(oi.quantity) as total_sold 
                     FROM order_items oi 
                     JOIN pastries p ON oi.pastry_id = p.id 
                     GROUP BY p.id, p.name 
                     ORDER BY total_sold DESC 
                     LIMIT 5";
$top_selling_result = mysqli_query($conn, $top_selling_query);
$top_selling = $top_selling_result ? $top_selling_result : null;

// Revenue by category
$category_revenue_query = "SELECT p.category, SUM(oi.quantity * oi.price) as revenue 
                         FROM order_items oi 
                         JOIN pastries p ON oi.pastry_id = p.id 
                         GROUP BY p.category 
                         ORDER BY revenue DESC";
$category_revenue_result = mysqli_query($conn, $category_revenue_query);
$category_revenue = $category_revenue_result ? $category_revenue_result : null;

// Monthly sales trends (last 6 months)
$monthly_sales_query = "SELECT DATE_FORMAT(order_date, '%Y-%m') as month, 
                              SUM(total_amount) as revenue 
                      FROM orders 
                      WHERE order_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                      GROUP BY DATE_FORMAT(order_date, '%Y-%m') 
                      ORDER BY month";
$monthly_sales_result = mysqli_query($conn, $monthly_sales_query);
$monthly_sales = $monthly_sales_result ? $monthly_sales_result : null;

// Hourly order trends (today)
$hourly_orders_query = "SELECT HOUR(order_date) as hour, COUNT(*) as order_count 
                       FROM orders 
                       WHERE DATE(order_date) = CURDATE() 
                       GROUP BY HOUR(order_date) 
                       ORDER BY hour";
$hourly_orders_result = mysqli_query($conn, $hourly_orders_query);
$hourly_orders = $hourly_orders_result ? $hourly_orders_result : null;

// Monthly customer growth (last 6 months)
$monthly_customers_query = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                                   COUNT(*) as new_customers 
                           FROM customers 
                           WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH) 
                           GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
                           ORDER BY month";
$monthly_customers_result = mysqli_query($conn, $monthly_customers_query);
$monthly_customers = $monthly_customers_result ? $monthly_customers_result : null;

// Get recent orders
$recent_orders_query = "SELECT o.*, c.profile_picture FROM orders o LEFT JOIN customers c ON o.customer_id = c.id ORDER BY o.order_date DESC LIMIT 5";
$recent_orders = mysqli_query($conn, $recent_orders_query);

// Get all users with pagination and search
$page = isset($_GET['user_page']) ? (int) $_GET['user_page'] : 1;
$users_per_page = 10;
$offset = ($page - 1) * $users_per_page;
$search = isset($_GET['user_search']) ? mysqli_real_escape_string($conn, $_GET['user_search']) : '';

// Build users query
$users_query = "SELECT id, username, full_name, email, profile_picture, created_at FROM customers";
$count_query = "SELECT COUNT(*) as total FROM customers";

if ($search) {
    $users_query .= " WHERE username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%'";
    $count_query .= " WHERE username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%'";
}

// Sort logic
$user_sort = isset($_GET['user_sort']) ? $_GET['user_sort'] : 'newest';
$sort_sql = "ORDER BY created_at DESC";

switch ($user_sort) {
    case 'oldest':
        $sort_sql = "ORDER BY created_at ASC";
        break;
    case 'az':
        $sort_sql = "ORDER BY username ASC";
        break;
    case 'za':
        $sort_sql = "ORDER BY username DESC";
        break;
    default:
        $sort_sql = "ORDER BY created_at DESC";
        break;
}

$users_query .= " $sort_sql LIMIT $users_per_page OFFSET $offset";

$users_result = mysqli_query($conn, $users_query);
$count_result = mysqli_query($conn, $count_query);
$total_users = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_users / $users_per_page);

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title; ?> - Artisan Pastry Shop
    </title>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🥐</text></svg>">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/theme-toggle.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* ============================================
           DASHBOARD LAYOUT - Embedded for reliability
           ============================================ */

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--cream);
            font-family: 'Quattrocento', Georgia, serif;
            margin: 0;
            padding: 0;
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem 2rem 4rem;
            width: 100%;
        }

        .dashboard-header {
            margin-bottom: 1.5rem;
        }

        /* ===== SUMMARY CARDS ===== */
        .dashboard-summary {
            display: grid !important;
            grid-template-columns: repeat(4, 1fr) !important;
            gap: 1.25rem !important;
            margin-bottom: 1.5rem !important;
            width: 100%;
        }

        .summary-card {
            background: var(--card-bg) !important;
            border-radius: 18px !important;
            padding: 1.4rem 1.5rem !important;
            box-shadow: 0 2px 14px rgba(139, 111, 71, 0.09) !important;
            display: flex !important;
            align-items: center !important;
            gap: 1rem !important;
            transition: transform 0.25s ease, box-shadow 0.25s ease !important;
            position: relative !important;
            border: none !important;
            min-width: 0;
        }

        .summary-card:hover {
            transform: translateY(-5px) !important;
            box-shadow: 0 12px 30px rgba(139, 111, 71, 0.18) !important;
            border-color: var(--honey) !important;
        }

        .summary-card-link {
            text-decoration: none !important;
            color: inherit !important;
            display: flex !important;
            min-width: 0;
        }

        .summary-card::after {
            content: '···' !important;
            position: absolute !important;
            top: 0.85rem !important;
            right: 1rem !important;
            font-size: 1.1rem !important;
            color: #ccc !important;
            letter-spacing: 2px !important;
            cursor: pointer !important;
            line-height: 1 !important;
        }

        .summary-icon {
            font-size: 1.7rem !important;
            width: 54px !important;
            height: 54px !important;
            min-width: 54px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            border-radius: 50% !important;
            flex-shrink: 0 !important;
        }

        .summary-content {
            flex: 1;
            min-width: 0;
        }

        .summary-content h3 {
            font-size: 1.65rem !important;
            margin: 0 0 0.15rem 0 !important;
            font-weight: 700 !important;
            line-height: 1.1 !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .summary-content p {
            font-size: 0.78rem !important;
            margin: 0 !important;
            font-weight: 500 !important;
            text-transform: uppercase !important;
            letter-spacing: 0.5px !important;
            color: var(--warm-brown) !important;
            opacity: 0.7 !important;
        }

        /* Card color themes */
        .revenue-card .summary-icon {
            background: #FFF4E5;
        }

        .revenue-card .summary-content h3 {
            color: #E8943A;
        }

        .orders-card .summary-icon {
            background: #FFF0F5;
        }

        .orders-card .summary-content h3 {
            color: #E85D8A;
        }

        .top-treat-card .summary-icon {
            background: #FFF4E5;
        }

        .top-treat-card .summary-content h3 {
            color: #8B6F47;
            font-size: 1.1rem !important;
        }

        .low-stock-card .summary-icon {
            background: #FFF8E1;
        }

        .low-stock-card .summary-content h3 {
            color: #E8943A;
        }

        /* ===== CHARTS SECTION ===== */
        .dashboard-charts {
            margin-bottom: 1.5rem;
            width: 100%;
        }

        .charts-row {
            display: grid !important;
            gap: 1.25rem !important;
            margin-bottom: 1.25rem !important;
            width: 100%;
        }

        /* Top row: big bar | donut | top list */
        .charts-row.top-row {
            grid-template-columns: 2.2fr 1.3fr 1.2fr !important;
        }

        /* Bottom row: hourly | purple growth */
        .charts-row.bottom-row {
            grid-template-columns: 1fr 1fr !important;
        }

        .chart-container {
            background: var(--card-bg) !important;
            border-radius: 18px !important;
            padding: 1.5rem !important;
            box-shadow: 0 2px 14px rgba(139, 111, 71, 0.09) !important;
            position: relative !important;
            border: none !important;
            min-width: 0;
            overflow: hidden;
        }

        .chart-container::after {
            content: '···' !important;
            position: absolute !important;
            top: 1.1rem !important;
            right: 1.2rem !important;
            font-size: 1.1rem !important;
            color: #ccc !important;
            cursor: pointer !important;
            letter-spacing: 2px !important;
            line-height: 1 !important;
        }

        .chart-container h3 {
            font-family: 'Quattrocento', Georgia, serif !important;
            color: var(--deep-brown) !important;
            margin: 0 !important;
            font-size: 1rem !important;
            font-weight: 700 !important;
        }

        .chart-header {
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            margin-bottom: 1rem !important;
        }

        .filter-select {
            background: #f5f5f5 !important;
            border: 1px solid #eee !important;
            border-radius: 8px !important;
            padding: 0.3rem 0.75rem !important;
            font-size: 0.8rem !important;
            color: #666 !important;
            cursor: pointer !important;
            outline: none !important;
            font-family: inherit !important;
        }

        .chart-wrapper {
            height: 220px !important;
            position: relative !important;
            width: 100% !important;
        }

        .chart-wrapper canvas {
            max-height: 100% !important;
        }

        /* ===== PURPLE CUSTOMER GROWTH CHART ===== */
        .chart-container.purple-chart {
            background: linear-gradient(135deg, #7B4FC4 0%, #4A1F8A 100%) !important;
        }

        .chart-container.purple-chart h3 {
            color: #ffffff !important;
        }

        .chart-container.purple-chart::after {
            color: rgba(255, 255, 255, 0.35) !important;
        }

        .chart-container.purple-chart .filter-select {
            background: rgba(255, 255, 255, 0.15) !important;
            border-color: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
        }

        p.chart-subtitle {
            color: rgba(255, 255, 255, 0.65) !important;
            font-size: 0.78rem !important;
            margin: 0.15rem 0 0 0 !important;
        }

        .purple-star {
            position: absolute !important;
            bottom: 1.2rem !important;
            right: 1.5rem !important;
            font-size: 2rem !important;
            opacity: 0.35 !important;
            line-height: 1 !important;
        }

        /* ===== TOP SELLING LIST ===== */
        .top-selling-header {
            display: flex !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding-bottom: 0.5rem !important;
            margin-bottom: 0.4rem !important;
            border-bottom: 1px solid #f0f0f0 !important;
            color: #bbb !important;
            font-size: 0.7rem !important;
            font-weight: 700 !important;
            letter-spacing: 0.6px !important;
            text-transform: uppercase !important;
        }

        .top-selling-list {
            max-height: 250px !important;
            overflow-y: auto !important;
        }

        .selling-item {
            display: flex !important;
            align-items: center !important;
            gap: 0.8rem !important;
            padding: 0.6rem 0 !important;
            border-bottom: 1px solid #f8f8f8 !important;
        }

        .selling-item:last-child {
            border-bottom: none !important;
        }

        .selling-icon {
            width: 32px !important;
            height: 32px !important;
            border-radius: 8px !important;
            background: #fff5f8 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            font-size: 1.1rem !important;
            flex-shrink: 0 !important;
        }

        .selling-info {
            flex: 1 !important;
            min-width: 0 !important;
        }

        .selling-name {
            font-weight: 600 !important;
            color: #2c3e50 !important;
            font-size: 0.85rem !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .selling-quantity {
            font-weight: 700 !important;
            color: #2c3e50 !important;
            font-size: 0.88rem !important;
            flex-shrink: 0 !important;
        }

        /* ===== HOURLY BADGE ===== */
        .hourly-badge-wrap {
            position: relative;
            height: 0;
        }

        .hourly-badge {
            position: absolute !important;
            top: 0.5rem !important;
            left: 2rem !important;
            background: #1a1a1a !important;
            color: white !important;
            font-size: 0.78rem !important;
            font-weight: 600 !important;
            padding: 0.3rem 0.7rem !important;
            border-radius: 8px !important;
            display: inline-flex !important;
            align-items: center !important;
            gap: 0.4rem !important;
            z-index: 5 !important;
            white-space: nowrap !important;
        }

        .hourly-badge .badge-dot {
            width: 8px !important;
            height: 8px !important;
            border-radius: 50% !important;
            background: #E85D8A !important;
            flex-shrink: 0 !important;
        }

        /* ===== RESPONSIVE ===== */
        @media (max-width: 1100px) {
            .dashboard-summary {
                grid-template-columns: repeat(2, 1fr) !important;
            }

            .charts-row.top-row {
                grid-template-columns: 1fr 1fr !important;
            }

            .charts-row.bottom-row {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 700px) {
            .dashboard-summary {
                grid-template-columns: 1fr 1fr !important;
            }

            .charts-row.top-row {
                grid-template-columns: 1fr !important;
            }
        }

        @media (max-width: 480px) {
            .dashboard-summary {
                grid-template-columns: 1fr !important;
            }

            .dashboard-container {
                padding: 1rem 1rem 3rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation is handled by header.php -->

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 style="font-family: 'Playfair Display', serif; color: var(--deep-brown);">
                Admin Dashboard
            </h1>
            <p style="color: var(--warm-brown);">Welcome back,
                <?php echo htmlspecialchars($admin_username); ?>!
            </p>
        </div>

        <!-- Dashboard Summary Cards -->
        <div class="dashboard-summary">
            <a href="orders.php" class="summary-card-link">
                <div class="summary-card revenue-card" style="width: 100%;">
                    <div class="summary-icon">🍯</div>
                    <div class="summary-content">
                        <h3>₱<?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
            </a>

            <a href="orders.php" class="summary-card-link">
                <div class="summary-card orders-card" style="width: 100%;">
                    <div class="summary-icon">🍓</div>
                    <div class="summary-content">
                        <h3><?php echo $orders_today; ?></h3>
                        <p>Orders Today</p>
                    </div>
                </div>
            </a>

            <a href="inventory.php" class="summary-card-link">
                <div class="summary-card top-treat-card" style="width: 100%;">
                    <div class="summary-icon">🥐</div>
                    <div class="summary-content">
                        <h3><?php echo htmlspecialchars($top_treat['name'] ?? 'N/A'); ?></h3>
                        <p>Top Treat</p>
                    </div>
                </div>
            </a>

            <a href="inventory.php?sort=stock_low" class="summary-card-link">
                <div class="summary-card low-stock-card" style="width: 100%;">
                    <div class="summary-icon">🔔</div>
                    <div class="summary-content">
                        <h3><?php echo $low_stock_items; ?></h3>
                        <p>Low Stock Items</p>
                    </div>
                </div>
            </a>
        </div>

        <!-- Dashboard Charts -->
        <div class="dashboard-charts">
            <!-- Top Row: Bar | Donut | Top Selling -->
            <div class="charts-row top-row">
                <!-- Monthly Pastry Sales Trends -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Monthly Pastry Sales Trends</h3>
                        <select class="filter-select">
                            <option>Show by months</option>
                        </select>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>

                <!-- Revenue by Category -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Revenue by Category</h3>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="categoryRevenueChart"></canvas>
                    </div>
                </div>

                <!-- Top Selling Pastries -->
                <div class="chart-container">
                    <h3>Top Selling Pastries</h3>
                    <div class="top-selling-header">
                        <span>Division</span>
                        <span>Sold</span>
                    </div>
                    <div class="top-selling-list">
                        <?php
                        $pastry_icons = ['🥐', '🧁', '🍩', '🍪', '🥧'];
                        if ($top_selling && mysqli_num_rows($top_selling) > 0): ?>
                            <?php $rank = 0;
                            while ($pastry = mysqli_fetch_assoc($top_selling)): ?>
                                <div class="selling-item">
                                    <div class="selling-icon"><?php echo $pastry_icons[$rank] ?? '🍰'; ?></div>
                                    <div class="selling-info">
                                        <div class="selling-name"><?php echo htmlspecialchars($pastry['name']); ?></div>
                                    </div>
                                    <div class="selling-quantity"><?php echo $pastry['total_sold']; ?></div>
                                </div>
                                <?php $rank++; endwhile; ?>
                        <?php else: ?>
                            <p style="text-align: center; padding: 2rem; color:#aaa;">No sales data available</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Bottom Row: Hourly line | Purple customer growth -->
            <div class="charts-row bottom-row">
                <!-- Hourly Order Trend -->
                <div class="chart-container">
                    <div class="chart-header">
                        <h3>Hourly Order Trend</h3>
                        <select class="filter-select">
                            <option>Today</option>
                        </select>
                    </div>
                    <div class="hourly-badge-wrap">
                        <div class="hourly-badge">
                            <span class="badge-dot"></span>
                            <?php echo $orders_today; ?> orders today
                        </div>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="hourlyOrdersChart"></canvas>
                    </div>
                </div>

                <!-- Monthly Customer Growth - Purple -->
                <div class="chart-container purple-chart">
                    <div class="chart-header">
                        <div>
                            <h3>Monthly Customer Growth</h3>
                            <p class="chart-subtitle">New Customers this month</p>
                        </div>
                        <select class="filter-select">
                            <option>Show by months</option>
                        </select>
                    </div>
                    <div class="chart-wrapper">
                        <canvas id="customerGrowthChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Orders -->
        <h2 style="font-family: 'Playfair Display', serif; margin-bottom: 1.5rem;">Recent Orders</h2>
        <div class="admin-table-wrapper" style="margin-bottom: 2rem;">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Delivery Address</th>
                        <th>Date</th>
                        <th>Total</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($recent_orders) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($recent_orders)): ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <div
                                            style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #f0f0f0; border: 1.5px solid var(--butter); flex-shrink: 0;">
                                            <?php if (!empty($order['profile_picture'])): ?>
                                                <img src="../<?php echo htmlspecialchars($order['profile_picture']); ?>" alt=""
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div
                                                    style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                                    👤</div>
                                            <?php endif; ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($order['customer_name']); ?></span>
                                    </div>
                                </td>
                                <td style="color: #666; font-size: 0.9rem;">
                                    <?php
                                    $address = $order['delivery_address'] ?? 'Not provided';
                                    echo htmlspecialchars(strlen($address) > 50 ? substr($address, 0, 50) . '...' : $address);
                                    ?>
                                </td>
                                <td>
                                    <?php echo date('M d, Y', strtotime($order['order_date'])); ?>
                                </td>
                                <td style="font-weight: 700;">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo strtolower($order['status']); ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 3rem; color: #999; font-style: italic;">No
                                orders yet</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-bottom: 3rem;">
            <a href="orders.php" class="btn" style="border-radius: 12px; padding: 0.8rem 2rem;">View All Orders</a>
        </div>

        <!-- Users Table -->
        <div
            style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <h2 style="font-family: 'Playfair Display', serif; margin: 0;">Registered Users</h2>

            <div class="admin-controls" style="margin-bottom: 0; display: flex; gap: 1rem; align-items: center;">
                <div class="admin-search-group" style="min-width: 250px; margin-bottom: 0; flex: none;">
                    <div class="admin-search-input-wrapper" style="margin-bottom: 0;">
                        <input type="text" id="userSearchInput" placeholder="Search users..." class="admin-search-input"
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>

                <select id="userSortSelect" class="admin-filter-select" style="min-width: 120px;">
                    <option value="newest" <?php echo $user_sort == 'newest' ? 'selected' : ''; ?>>Newest</option>
                    <option value="oldest" <?php echo $user_sort == 'oldest' ? 'selected' : ''; ?>>Oldest</option>
                    <option value="az" <?php echo $user_sort == 'az' ? 'selected' : ''; ?>>A-Z</option>
                    <option value="za" <?php echo $user_sort == 'za' ? 'selected' : ''; ?>>Z-A</option>
                </select>

                <a href="admin_dashboard.php" class="admin-btn-clear" id="userClearBtn"
                    style="display: <?php echo ($search || $user_sort != 'newest') ? 'inline-flex' : 'none'; ?>;">
                    Clear
                </a>
            </div>
        </div>

        <div class="admin-table-wrapper">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Joined Date</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <?php if (mysqli_num_rows($users_result) > 0): ?>
                        <?php while ($user = mysqli_fetch_assoc($users_result)): ?>
                            <tr>
                                <td style="font-weight: 600;">
                                    <div style="display: flex; align-items: center; gap: 0.8rem;">
                                        <div
                                            style="width: 32px; height: 32px; border-radius: 50%; overflow: hidden; background: #f0f0f0; border: 1.5px solid var(--butter); flex-shrink: 0;">
                                            <?php if (!empty($user['profile_picture'])): ?>
                                                <img src="../<?php echo htmlspecialchars($user['profile_picture']); ?>" alt=""
                                                    style="width: 100%; height: 100%; object-fit: cover;">
                                            <?php else: ?>
                                                <div
                                                    style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 0.9rem;">
                                                    👤</div>
                                            <?php endif; ?>
                                        </div>
                                        <span><?php echo htmlspecialchars($user['username']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td style="color: #666;"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 2rem;">
                                <?php if ($search): ?>
                                    No users found matching "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    No users registered yet
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="pagination"
                style="display: flex; justify-content: center; align-items: center; gap: 1rem; margin-top: 2rem;">
                <?php if ($page > 1): ?>
                    <a href="?user_page=<?php echo $page - 1; ?>&user_search=<?php echo urlencode($search); ?>"
                        class="btn btn-secondary" style="padding: 0.5rem 1rem;">&lt; Previous</a>
                <?php endif; ?>

                <span style="color: var(--warm-brown); font-weight: 600;">
                    Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                </span>

                <?php if ($page < $total_pages): ?>
                    <a href="?user_page=<?php echo $page + 1; ?>&user_search=<?php echo urlencode($search); ?>"
                        class="btn btn-secondary" style="padding: 0.5rem 1rem;">Next &gt;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    </div>

    <script>
        // Chart.js initialization
        document.addEventListener('DOMContentLoaded', function () {
            // Chart colors matching reference image
            const brownColor = '#8b7355';
            const raspberryColor = '#e85d8a';
            const honeyColor = '#E8943A';
            const lightBrown = '#c4a47c';

            // Monthly Sales Trends - Grouped Bar Chart (brown + raspberry like reference)
            const monthlySalesCtx = document.getElementById('monthlySalesChart').getContext('2d');
            <?php
            if ($monthly_sales && mysqli_num_rows($monthly_sales) > 0) {
                $months = [];
                $revenues = [];
                mysqli_data_seek($monthly_sales, 0);
                while ($row = mysqli_fetch_assoc($monthly_sales)) {
                    $months[] = '"' . date('M Y', strtotime($row['month'] . '-01')) . '"';
                    $revenues[] = $row['revenue'];
                }
                echo 'const monthlySalesLabels = [' . implode(',', $months) . '];';
                echo 'const monthlySalesData = [' . implode(',', $revenues) . '];';
            } else {
                echo 'const monthlySalesLabels = ["No Data"];';
                echo 'const monthlySalesData = [0];';
            }
            ?>

            new Chart(monthlySalesCtx, {
                type: 'bar',
                data: {
                    labels: monthlySalesLabels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: monthlySalesData,
                            backgroundColor: brownColor,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.5,
                        },
                        {
                            label: 'Orders',
                            data: monthlySalesData.map(v => v * 0.6),
                            backgroundColor: raspberryColor,
                            borderRadius: 6,
                            borderSkipped: false,
                            barPercentage: 0.5,
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: {
                                callback: function (value) {
                                    return value >= 1000 ? (value / 1000).toFixed(0) + 'k' : value;
                                },
                                font: { size: 11 }
                            }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });

            // Revenue by Category - Donut Chart (raspberry + honey like reference)
            const categoryRevenueCtx = document.getElementById('categoryRevenueChart').getContext('2d');
            <?php
            if ($category_revenue && mysqli_num_rows($category_revenue) > 0) {
                $categories = [];
                $revenues = [];
                mysqli_data_seek($category_revenue, 0);
                while ($row = mysqli_fetch_assoc($category_revenue)) {
                    $categories[] = '"' . htmlspecialchars($row['category']) . '"';
                    $revenues[] = $row['revenue'];
                }
                echo 'const categoryLabels = [' . implode(',', $categories) . '];';
                echo 'const categoryData = [' . implode(',', $revenues) . '];';
            } else {
                echo 'const categoryLabels = ["Sweet Pastries", "Savory Pastries"];';
                echo 'const categoryData = [60, 40];';
            }
            ?>

            new Chart(categoryRevenueCtx, {
                type: 'doughnut',
                data: {
                    labels: categoryLabels,
                    datasets: [{
                        data: categoryData,
                        backgroundColor: [raspberryColor, honeyColor, '#c4a47c', '#8b7355', '#e8b4c8'],
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '65%',
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                padding: 10,
                                font: { size: 11 }
                            }
                        }
                    }
                }
            });

            // Hourly Order Trend - Orange line like reference
            const hourlyOrdersCtx = document.getElementById('hourlyOrdersChart').getContext('2d');
            <?php
            if ($hourly_orders && mysqli_num_rows($hourly_orders) > 0) {
                $hours = [];
                $counts = [];
                mysqli_data_seek($hourly_orders, 0);
                while ($row = mysqli_fetch_assoc($hourly_orders)) {
                    $hours[] = '"' . $row['hour'] . ' am"';
                    $counts[] = $row['order_count'];
                }
                echo 'const hourlyLabels = [' . implode(',', $hours) . '];';
                echo 'const hourlyData = [' . implode(',', $counts) . '];';
            } else {
                echo 'const hourlyLabels = ["7 am","8 am","9 am","10 am","11 pm","12 pm"];';
                echo 'const hourlyData = [40, 55, 130, 130, 135, 145];';
            }
            ?>

            new Chart(hourlyOrdersCtx, {
                type: 'line',
                data: {
                    labels: hourlyLabels,
                    datasets: [{
                        label: 'Orders',
                        data: hourlyData,
                        borderColor: honeyColor,
                        backgroundColor: honeyColor + '25',
                        tension: 0.4,
                        fill: true,
                        pointRadius: 4,
                        pointBackgroundColor: honeyColor,
                        borderWidth: 2.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { font: { size: 11 } }
                        }
                    }
                }
            });

            // Monthly Customer Growth - Pink/purple gradient area like reference
            const customerGrowthCtx = document.getElementById('customerGrowthChart').getContext('2d');
            <?php
            if ($monthly_customers && mysqli_num_rows($monthly_customers) > 0) {
                $growth_months = [];
                $customers = [];
                mysqli_data_seek($monthly_customers, 0);
                while ($row = mysqli_fetch_assoc($monthly_customers)) {
                    $growth_months[] = '"' . date('M Y', strtotime($row['month'] . '-01')) . '"';
                    $customers[] = $row['new_customers'];
                }
                echo 'const growthLabels = [' . implode(',', $growth_months) . '];';
                echo 'const growthData = [' . implode(',', $customers) . '];';
            } else {
                echo 'const growthLabels = ["0m","8am","9am","36","120","36","137","120","13"];';
                echo 'const growthData = [0, 10, 25, 45, 70, 90, 120, 140, 150];';
            }
            ?>

            const growthCtx2 = customerGrowthCtx;
            const growthGradient = growthCtx2.createLinearGradient(0, 0, 0, 200);
            growthGradient.addColorStop(0, 'rgba(232, 93, 138, 0.55)');
            growthGradient.addColorStop(1, 'rgba(232, 93, 138, 0.0)');

            new Chart(customerGrowthCtx, {
                type: 'line',
                data: {
                    labels: growthLabels,
                    datasets: [{
                        label: 'New Customers',
                        data: growthData,
                        borderColor: '#f4a0c0',
                        backgroundColor: growthGradient,
                        tension: 0.45,
                        fill: true,
                        pointRadius: 3,
                        pointBackgroundColor: '#f4a0c0',
                        borderWidth: 2.5
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: { color: 'rgba(255,255,255,0.1)' },
                            ticks: { color: 'rgba(255,255,255,0.6)', font: { size: 11 } }
                        },
                        x: {
                            grid: { display: false },
                            ticks: { color: 'rgba(255,255,255,0.6)', font: { size: 11 } }
                        }
                    }
                }
            });
        });

        // === Registered Users AJAX Search ===
        const userSearchInput = document.getElementById('userSearchInput');
        const userSortSelect = document.getElementById('userSortSelect');
        const usersTableBody = document.getElementById('usersTableBody');
        const userClearBtn = document.getElementById('userClearBtn');
        let searchTimeout;

        function fetchUsers() {
            const search = userSearchInput.value;
            const sort = userSortSelect.value;

            // Show/hide clear button
            if (search || sort !== 'newest') {
                userClearBtn.style.display = 'inline-flex';
            } else {
                userClearBtn.style.display = 'none';
            }

            fetch(`get_users.php?user_search=${encodeURIComponent(search)}&user_sort=${sort}`)
                .then(response => response.json())
                .then(data => {
                    usersTableBody.innerHTML = data.table_html;
                })
                .catch(error => console.error('Error fetching users:', error));
        }

        userSearchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(fetchUsers, 300); // 300ms debounce
        });

        userSortSelect.addEventListener('change', fetchUsers);

        // Prevent Enter key from submitting
        userSearchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    </script>
</body>

</html>