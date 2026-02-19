<?php
/**
 * Automatic Database Setup for Pastry Shop
 * Creates database, tables, and sample data if not present
 */

// Only define if not already defined (avoids errors when included)
if (!defined('DB_HOST'))
    define('DB_HOST', 'localhost');
if (!defined('DB_USER'))
    define('DB_USER', 'root');
if (!defined('DB_PASS'))
    define('DB_PASS', '');
if (!defined('DB_NAME'))
    define('DB_NAME', 'pastry_shop');

// Connect to MySQL server (without selecting a database)
$setup_conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS);
if (!$setup_conn) {
    if (basename($_SERVER['PHP_SELF']) == 'setup.php') {
        die("Connection failed: " . mysqli_connect_error());
    }
    return false;
}

// Step 1: Create the database if it doesn't exist
if (!mysqli_query($setup_conn, "CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci")) {
    if (basename($_SERVER['PHP_SELF']) == 'setup.php')
        die("Step 1 Failed: " . mysqli_error($setup_conn));
    return false;
}

// Step 2: Select the database
if (!mysqli_select_db($setup_conn, DB_NAME)) {
    if (basename($_SERVER['PHP_SELF']) == 'setup.php')
        die("Step 2 Failed: " . mysqli_error($setup_conn));
    return false;
}

// Step 3: Load SQL file
$sqlFile = __DIR__ . '/complete_setup.sql';
if (!file_exists($sqlFile)) {
    if (basename($_SERVER['PHP_SELF']) == 'setup.php')
        die("Step 3 Failed: SQL file not found");
    return false;
}

$sql = file_get_contents($sqlFile);

// Step 4: Remove 'CREATE DATABASE' and 'USE' statements from SQL file
// We use ^ to ensure it only matches at the start of the line
$sql = preg_replace('/^CREATE DATABASE.*?;/im', '', $sql);
$sql = preg_replace('/^USE.*?;/im', '', $sql);

// Step 5: Execute SQL statements using multi_query
if (mysqli_multi_query($setup_conn, $sql)) {
    do {
        // flush multi_query results
        if ($result = mysqli_store_result($setup_conn)) {
            mysqli_free_result($result);
        }
    } while (mysqli_more_results($setup_conn) && mysqli_next_result($setup_conn));

    // FINAL CHECK: Does the pastries table exist now?
    $check = mysqli_query($setup_conn, "SHOW TABLES LIKE 'pastries'");
    if (!$check || mysqli_num_rows($check) == 0) {
        if (basename($_SERVER['PHP_SELF']) == 'setup.php')
            die("Step 6 Failed: 'pastries' table not found.");
        mysqli_close($setup_conn);
        return false;
    }

    if (basename($_SERVER['PHP_SELF']) == 'setup.php') {
        echo "Database and tables created successfully!";
    }
    mysqli_close($setup_conn);
    return true;
} else {
    $error = mysqli_error($setup_conn);
    mysqli_close($setup_conn);
    if (basename($_SERVER['PHP_SELF']) == 'setup.php')
        die("Step 5 Failed: " . $error);
    return false;
}
?>