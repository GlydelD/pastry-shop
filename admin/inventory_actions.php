<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/check_admin_session.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// Function to handle image upload
function handleImageUpload($file)
{
    $upload_dir = '../media/pastries/';

    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    // Validate file
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.'];
    }

    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File size exceeds 5MB limit.'];
    }

    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('pastry_') . '.' . $extension;
    $filepath = $upload_dir . $filename;

    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'path' => 'media/pastries/' . $filename];
    } else {
        return ['success' => false, 'message' => 'Failed to upload file.'];
    }
}

switch ($action) {
    case 'add':
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $stock_quantity = intval($_POST['stock_quantity']);

        // Handle image upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image']);
            if (!$upload_result['success']) {
                echo json_encode($upload_result);
                exit;
            }
            $image_url = $upload_result['path'];
        } else {
            echo json_encode(['success' => false, 'message' => 'Image is required.']);
            exit;
        }

        $query = "INSERT INTO pastries (name, description, price, category, image_url, stock_quantity) 
                  VALUES ('$name', '$description', $price, '$category', '$image_url', $stock_quantity)";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Pastry added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;

    case 'update':
        $id = intval($_POST['id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $description = mysqli_real_escape_string($conn, $_POST['description']);
        $price = floatval($_POST['price']);
        $category = mysqli_real_escape_string($conn, $_POST['category']);
        $stock_quantity = intval($_POST['stock_quantity']);

        // Handle image upload (optional for update)
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_result = handleImageUpload($_FILES['image']);
            if (!$upload_result['success']) {
                echo json_encode($upload_result);
                exit;
            }
            $image_url = $upload_result['path'];

            // Update with new image
            $query = "UPDATE pastries SET 
                      name = '$name',
                      description = '$description',
                      price = $price,
                      category = '$category',
                      image_url = '$image_url',
                      stock_quantity = $stock_quantity
                      WHERE id = $id";
        } else {
            // Keep existing image
            $query = "UPDATE pastries SET 
                      name = '$name',
                      description = '$description',
                      price = $price,
                      category = '$category',
                      stock_quantity = $stock_quantity
                      WHERE id = $id";
        }

        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Pastry updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;

    case 'delete':
        $id = intval($_POST['id']);

        $query = "DELETE FROM pastries WHERE id = $id";

        if (mysqli_query($conn, $query)) {
            echo json_encode(['success' => true, 'message' => 'Pastry deleted successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => mysqli_error($conn)]);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}
?>