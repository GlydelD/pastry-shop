<?php
echo "Starting test...\n";
require_once 'includes/config.php';
echo "Config included successfully.\n";
if ($conn) {
    echo "Database connection established.\n";
} else {
    echo "Database connection failed.\n";
}
?>