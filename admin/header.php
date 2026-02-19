<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Artisan Pastry Shop</title>
    <link rel="icon"
        href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>🥐</text></svg>">
    <link rel="stylesheet" href="../css/style.css">
    <script src="../js/theme-toggle.js"></script>
    <style>
        /* Admin-specific refinements */
        .admin-controls {
            display: flex;
            gap: 1rem;
            align-items: flex-start;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .admin-search-group {
            flex: 1;
            min-width: 300px;
        }

        .admin-search-input-wrapper {
            position: relative;
            margin-bottom: 0.5rem;
        }

        .admin-search-input {
            width: 100%;
            padding: 0.75rem 1.5rem;
            border: 1.5px solid var(--warm-brown);
            border-radius: 50px;
            background: var(--card-bg);
            color: var(--deep-brown);
            font-family: inherit;
            transition: all 0.3s ease;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
        }

        .admin-search-input:focus {
            outline: none;
            border-color: var(--honey);
            box-shadow: 0 4px 15px rgba(232, 164, 70, 0.15);
            transform: translateY(-1px);
        }

        .admin-search-hint {
            font-size: 0.8rem;
            color: var(--warm-brown);
            margin-left: 1rem;
            opacity: 0.8;
        }

        .admin-filter-select {
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 12px;
            background: var(--butter);
            color: var(--deep-brown);
            cursor: pointer;
            font-family: inherit;
            min-width: 160px;
            transition: all 0.3s ease;
        }

        .admin-filter-select:hover {
            background: var(--cream);
        }

        .admin-btn-clear {
            padding: 0.75rem 1.2rem;
            border: 1.5px solid var(--warm-brown);
            border-radius: 12px;
            color: var(--warm-brown);
            background: transparent;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .admin-btn-clear:hover {
            background: var(--warm-brown);
            color: white;
        }

        .admin-table-wrapper {
            background: var(--card-bg);
            border-radius: 18px;
            box-shadow: 0 10px 30px var(--shadow);
            overflow: hidden;
            border: none;
        }

        .admin-table {
            width: 100%;
            border-collapse: collapse;
            font-family: inherit;
        }

        .admin-table thead {
            background: var(--warm-brown);
            color: white;
        }

        .admin-table th {
            padding: 1.2rem 1rem;
            text-align: left;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .admin-table td {
            padding: 1.2rem 1rem;
            border-bottom: 1px solid var(--butter);
        }

        .admin-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            padding: 0.4rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
            display: inline-block;
            text-align: center;
            min-width: 100px;
        }

        .status-pending {
            background-color: #FEF3C7;
            color: #92400E;
        }

        .status-processing {
            background-color: #DBEAFE;
            color: #1E40AF;
        }

        .status-completed {
            background-color: #D1FAE5;
            color: #065F46;
        }

        .status-cancelled {
            background-color: #FEE2E2;
            color: #991B1B;
        }

        h1 {
            font-family: 'Playfair Display', serif;
            font-weight: 900;
            color: var(--deep-brown);
            margin-bottom: 2rem;
        }
    </style>
</head>

<body>
    <nav>
        <div class="container">
            <a href="admin_dashboard.php" class="logo">Pâtisserie Admin</a>
            <div class="nav-wrapper">
                <ul class="nav-links">
                    <li><a href="admin_dashboard.php">Dashboard</a></li>
                    <li><a href="inventory.php">Inventory</a></li>
                    <li><a href="orders.php">Orders</a></li>
                    <li><a href="../logout.php">Logout</a></li>
                </ul>
                <button class="theme-toggle" aria-label="Toggle dark mode">
                    <span class="icon">🌙</span>
                </button>
            </div>
        </div>
    </nav>
    <main>