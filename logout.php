<?php
session_start();

// Determine user type and redirect accordingly
if (isset($_SESSION['customer_id'])) {
    // Customer logout
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
} elseif (isset($_SESSION['admin_id'])) {
    // Admin logout
    session_unset();
    session_destroy();
    header('Location: admin_login.php');
    exit;
} else {
    // No session, redirect to home
    header('Location: index.php');
    exit;
}
?>