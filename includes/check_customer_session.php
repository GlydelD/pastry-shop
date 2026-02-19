<?php
// Customer Session Check
// Include this file at the top of customer-only pages

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}
?>