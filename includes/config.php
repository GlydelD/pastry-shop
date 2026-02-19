<?php
/**
 * Database Configuration with Auto-Setup
 * Automatically creates the database and tables if they don't exist
 */

// Error reporting - Enable early to catch setup errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pastry_shop');

// Auto-setup database if needed
require_once __DIR__ . '/../database/setup.php';

// Create connection
$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset
mysqli_set_charset($conn, "utf8mb4");

// Base URL
define('BASE_URL', 'http://localhost/pastry-shop/');
define('ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/pastry-shop/');



?>