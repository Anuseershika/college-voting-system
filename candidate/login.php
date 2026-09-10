<?php
$page_title = "Candidate Login";
$active_page = "candidate_login";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as a candidate
if (isset($_SESSION['candidate_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = "";

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Fetch candidate from database
            $stmt = $pdo->prepare("SELECT * FROM candidate_applications WHERE email = ?");
            $stmt->execute([$email]);
            $candidate = $stmt->fetch();

            if ($candidate && password_verify($password, $candidate['password'])) {
                // Set session variables
                $_SESSION['candidate_id'] = $candidate['student_id'];
                $_SESSION['candidate_db_id'] = $candidate['id'];
                $_SESSION['candidate_name'] = $candidate['candidate_name'];
                $_SESSION['candidate_email'] = $candidate['email'];

                // Redirect to candidate dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid Email or Password.";
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
        <h2>Candidate Login</h2>
        <p>Log in to access the candidate portal and check status</p>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="email" class="form-label">College Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="e.g. michael@college.edu" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary">Log In</button>

        <div class="form-footer">
            Don't have an account? <a href="register.php">Register as Candidate</a>
        </div>
    </form>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
