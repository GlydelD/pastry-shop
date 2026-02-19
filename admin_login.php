<?php
$page_title = 'Admin Login';

require_once 'includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// Check if admin is already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/admin_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'All fields are required.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password']; // Plain text - no hashing

        // Query shop_admin table
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, username, password 
             FROM shop_admin 
             WHERE username = ?
             LIMIT 1"
        );

        if (!$stmt) {
            die('Database error. Please try again later.');
        }

        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($admin = mysqli_fetch_assoc($result)) {
            // Plain text password comparison (as requested by user)
            if ($password === $admin['password']) {
                // Set admin session variables
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];

                // Prevent session fixation
                session_regenerate_id(true);

                // Redirect to admin dashboard
                header('Location: admin/admin_dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'Invalid username or password.';
        }

        mysqli_stmt_close($stmt);
    }
}

// Include header AFTER processing
require_once 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 style="text-align:center; font-family: 'Playfair Display', serif; margin-bottom:2rem;">Admin Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required
                    value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-input-wrapper">
                    <input type="password" id="password" name="password" required>
                    <span class="toggle-password" onclick="togglePassword('password')">
                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor">
                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                    </span>
                </div>
            </div>

            <button type="submit" class="btn">
                Login
            </button>
        </form>

        <p style="text-align:center; margin-top:1.5rem;">
            <a href="index.php" class="back-link">← Back to Home</a>
        </p>
    </div>
</section>

<script>
    function togglePassword(inputId) {
        const input = document.getElementById(inputId);
        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
        input.setAttribute('type', type);
    }
</script>

<?php require_once 'includes/footer.php'; ?>