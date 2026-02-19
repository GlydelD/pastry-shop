<?php
session_start();
$page_title = 'My Profile';

require_once 'includes/config.php';
require_once 'includes/check_customer_session.php';

$customer_id = $_SESSION['customer_id'];
$error = '';
$success = '';

// Get current customer data
$customer_query = "SELECT * FROM customers WHERE id = ?";
$stmt = mysqli_prepare($conn, $customer_query);
mysqli_stmt_bind_param($stmt, "i", $customer_id);
mysqli_stmt_execute($stmt);
$customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $username = trim($_POST['username']);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Handle password change
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if (empty($username) || empty($full_name) || empty($email)) {
        $error = 'Username, Full Name, and Email are required.';
    } elseif (!empty($current_password) && (empty($new_password) || empty($confirm_password))) {
        $error = 'Please fill all password fields to change password.';
    } elseif (!empty($current_password) && $new_password !== $confirm_password) {
        $error = 'New passwords do not match.';
    } elseif (!empty($current_password) && strlen($new_password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } else {
        // Check if username or email already exists (excluding current user)
        $check_query = "SELECT id FROM customers WHERE (username = ? OR email = ?) AND id != ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ssi", $username, $email, $customer_id);
        mysqli_stmt_execute($check_stmt);
        
        if (mysqli_num_rows(mysqli_stmt_get_result($check_stmt)) > 0) {
            $error = 'Username or Email already exists.';
        } else {
            // Handle profile picture upload
            $profile_picture = $customer['profile_picture']; // Keep existing picture by default
            
            if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_picture'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowed_types)) {
                    $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
                } elseif ($file['size'] > $max_size) {
                    $error = 'File size too large. Maximum size is 5MB.';
                } else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = 'uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $filename = uniqid('profile_', true) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                        // Delete old profile picture if exists
                        if ($customer['profile_picture'] && file_exists($customer['profile_picture'])) {
                            unlink($customer['profile_picture']);
                        }
                        $profile_picture = $upload_path;
                    } else {
                        $error = 'Failed to upload profile picture.';
                    }
                }
            }
            
            // Update customer data if no upload error
            if (empty($error)) {
                // If password change is requested, verify current password first
                if (!empty($current_password)) {
                    // Verify current password
                    $verify_query = "SELECT password FROM customers WHERE id = ?";
                    $verify_stmt = mysqli_prepare($conn, $verify_query);
                    mysqli_stmt_bind_param($verify_stmt, "i", $customer_id);
                    mysqli_stmt_execute($verify_stmt);
                    $stored_password = mysqli_fetch_assoc(mysqli_stmt_get_result($verify_stmt))['password'];
                    
                    if (!password_verify($current_password, $stored_password)) {
                        $error = 'Current password is incorrect.';
                    } else {
                        // Hash new password
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $update_query = "UPDATE customers SET username = ?, full_name = ?, email = ?, phone = ?, address = ?, profile_picture = ?, password = ? WHERE id = ?";
                        $update_stmt = mysqli_prepare($conn, $update_query);
                        mysqli_stmt_bind_param($update_stmt, "ssssssi", $username, $full_name, $email, $phone, $address, $profile_picture, $hashed_password, $customer_id);
                    }
                } else {
                    // Update without password change
                    $update_query = "UPDATE customers SET username = ?, full_name = ?, email = ?, phone = ?, address = ?, profile_picture = ? WHERE id = ?";
                    $update_stmt = mysqli_prepare($conn, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "ssssssi", $username, $full_name, $email, $phone, $address, $profile_picture, $customer_id);
                }
                
                if (mysqli_stmt_execute($update_stmt)) {
                    // Update session variables
                    $_SESSION['customer_username'] = $username;
                    $_SESSION['customer_full_name'] = $full_name;
                    
                    $success = 'Profile updated successfully!';
                    
                    // Refresh customer data
                    $customer_query = "SELECT * FROM customers WHERE id = ?";
                    $stmt = mysqli_prepare($conn, $customer_query);
                    mysqli_stmt_bind_param($stmt, "i", $customer_id);
                    mysqli_stmt_execute($stmt);
                    $customer = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
                } else {
                    $error = 'Failed to update profile. Please try again.';
                }
            }
        }
    }
}

require_once 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 style="text-align:center; font-family: 'Playfair Display', serif; margin-bottom:2rem;">My Profile</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div style="text-align: center; margin-bottom: 2rem;">
            <?php if ($customer['profile_picture']): ?>
                <img src="<?php echo htmlspecialchars($customer['profile_picture']); ?>" 
                     alt="Profile Picture" 
                     style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover; border: 4px solid var(--warm-brown); box-shadow: 0 4px 15px var(--shadow);">
            <?php else: ?>
                <div style="width: 150px; height: 150px; border-radius: 50%; background: var(--butter); display: flex; align-items: center; justify-content: center; margin: 0 auto; border: 4px solid var(--warm-brown); box-shadow: 0 4px 15px var(--shadow);">
                    <span style="font-size: 3rem; color: var(--warm-brown);">👤</span>
                </div>
            <?php endif; ?>
        </div>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                       value="<?php echo htmlspecialchars($customer['username']); ?>">
            </div>

            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input type="text" id="full_name" name="full_name" required
                       value="<?php echo htmlspecialchars($customer['full_name']); ?>">
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required
                       value="<?php echo htmlspecialchars($customer['email']); ?>">
            </div>

            <div class="form-group">
                <label for="phone">Phone Number</label>
                <input type="tel" id="phone" name="phone"
                       value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>"
                       placeholder="+63 XXX XXX XXXX">
            </div>

            <div class="form-group">
                <label for="address">Address</label>
                <textarea id="address" name="address" rows="3"
                          placeholder="Enter your complete address"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label for="current_password">Current Password</label>
                <div style="position: relative;">
                    <input type="password" id="current_password" name="current_password"
                           placeholder="Enter current password to change">
                </div>
            </div>

            <div class="form-group">
                <label for="new_password">New Password</label>
                <div style="position: relative;">
                    <input type="password" id="new_password" name="new_password"
                           placeholder="Leave blank to keep current password">
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm New Password</label>
                <div style="position: relative;">
                    <input type="password" id="confirm_password" name="confirm_password"
                           placeholder="Leave blank to keep current password">
                </div>
            </div>

            <div class="form-group">
                <label for="profile_picture">Profile Picture</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                       onchange="previewProfilePicture(event)">
                <div id="imagePreview" style="margin-top: 10px; display: none;">
                    <img id="previewImg" src="" alt="Preview"
                         style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid var(--crust);">
                </div>
                <p style="font-size: 0.8rem; color: var(--warm-brown); margin-top: 5px;">
                    Allowed formats: JPG, PNG, GIF, WebP. Maximum size: 5MB
                </p>
            </div>

            <div style="display: flex; gap: 0.5rem;">
                                <button type="submit" class="btn" style="padding: 0.4rem 1rem; font-size: 0.85rem;">Update Profile</button>
                            </div>
        </form>

        <div style="text-align: center; margin-top: 2rem;">
            <a href="customer_dashboard.php" class="back-link">← Back to Dashboard</a>
        </div>
    </div>
</section>

<script>
    function previewProfilePicture(event) {
        const file = event.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('imagePreview').style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    }

    function togglePasswordVisibility(inputId) {
        const input = document.getElementById(inputId);
        const type = input.type === 'password' ? 'text' : 'password';
        input.type = type;
        
        // Toggle the eye icon
        const parent = input.parentElement;
        const eyeIcon = parent.querySelector('.eye-icon');
        if (eyeIcon) {
            eyeIcon.textContent = type === 'password' ? '👁' : '👁';
        }
    }

    // Add eye icons to password fields
    document.addEventListener('DOMContentLoaded', function() {
        const passwordFields = ['current_password', 'new_password', 'confirm_password'];
        passwordFields.forEach(fieldId => {
            const field = document.getElementById(fieldId);
            if (field) {
                const eyeIcon = document.createElement('span');
                eyeIcon.className = 'eye-icon';
                eyeIcon.textContent = '👁';
                eyeIcon.style.cssText = `
                    position: absolute;
                    right: 10px;
                    top: 50%;
                    transform: translateY(-50%);
                    cursor: pointer;
                    user-select: none;
                    font-size: 0.9rem;
                `;
                eyeIcon.onclick = () => togglePasswordVisibility(fieldId);
                
                field.parentElement.style.position = 'relative';
                field.parentElement.appendChild(eyeIcon);
            }
        });
    });
</script>

<?php require_once 'includes/footer.php'; ?>
