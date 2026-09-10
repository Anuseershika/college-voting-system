<?php
$page_title = "Admin Login";
$active_page = "admin_login";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as admin
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = "";

// Handle admin login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Fetch admin from database
            $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
            $stmt->execute([$username]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                // Set session variables
                $_SESSION['admin_id'] = $admin['username'];
                $_SESSION['admin_db_id'] = $admin['id'];

                // Redirect to admin dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid Username or Password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include $path_prefix . 'includes/header.php';
?>

<div class="form-card">
    <div class="form-header">
        <h2>Admin Portal</h2>
        <p>Log in to access administrative tools and manage elections</p>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="username" class="form-label">Admin Username</label>
            <input type="text" id="username" name="username" class="form-control" placeholder="Enter admin username" required value="<?php echo htmlspecialchars($username ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter admin password" required>
        </div>

        <button type="submit" class="btn btn-primary">Log In</button>

        <div class="form-footer">
            Return to <a href="../index.php">Public Home</a>
        </div>
    </form>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
