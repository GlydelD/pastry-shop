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
    <link rel="stylesheet" href="css/style.css">
    <script src="js/theme-toggle.js"></script>
</head>

<body>
    <nav>
        <div class="container">
            <a href="index.php" class="logo">Pâtisserie</a>
            <div class="nav-wrapper">
                <ul class="nav-links">
                    <?php if (!isset($_SESSION['customer_id'])): ?>
                        <li><a href="index.php">Home</a></li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['customer_id'])): ?>
                        <li><a href="customer_dashboard.php">Shop</a></li>
                        <li><a href="wishlist.php">
                                Wishlist
                            </a></li>
                        <li><a href="customer_profile.php">Profile</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php elseif (isset($_SESSION['admin_id'])): ?>
                        <li><a href="admin/admin_dashboard.php">Dashboard</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php">Login</a></li>
                        <li><a href="signup.php">Sign Up</a></li>
                    <?php endif; ?>
                </ul>
                <button class="theme-toggle" aria-label="Toggle dark mode">
                    <span class="icon">🌙</span>
                </button>
            </div>
        </div>
    </nav>
    <main>