<?php
$page_title = 'Customer Login';

require_once 'includes/config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';

// Check if customer is already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: customer_dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    if (empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'All fields are required.';
    } else {
        $username = trim($_POST['username']);
        $password = $_POST['password']; // Plain text - no hashing

        // Query customers table
        $stmt = mysqli_prepare(
            $conn,
            "SELECT id, username, full_name, password 
             FROM customers 
             WHERE username = ? OR email = ?
             LIMIT 1"
        );

        if (!$stmt) {
            die('Database error. Please try again later.');
        }

        mysqli_stmt_bind_param($stmt, "ss", $username, $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) === 1) {
            $customer = mysqli_fetch_assoc($result);

            // Plain text password comparison (NO ENCRYPTION)
            if ($password === $customer['password']) {
                // Set customer session variables
                $_SESSION['customer_id'] = $customer['id'];
                $_SESSION['customer_username'] = $customer['username'];
                $_SESSION['customer_name'] = $customer['full_name'];

                // Prevent session fixation
                session_regenerate_id(true);

                // Redirect to customer dashboard
                header('Location: customer_dashboard.php');
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

// Include header AFTER processing to avoid output before redirect
require_once 'includes/header.php';
?>

<section>
    <div class="form-container">
        <h2 style="text-align:center; font-family: 'Playfair Display', serif; margin-bottom:2rem;">Customer Login</h2>

        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username or Email</label>
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

        <p style="text-align:center;margin-top:1.5rem;">
            Don't have an account?
            <a href="signup.php" style="color: var(--raspberry); font-weight: 600;">Sign Up</a>
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