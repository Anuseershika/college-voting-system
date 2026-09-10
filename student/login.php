<?php
$page_title = "Student Login";
$active_page = "student_login";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in as a student
if (isset($_SESSION['student_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = "";

// Handle login POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($student_id) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Fetch student from database
            $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();

            if ($student && password_verify($password, $student['password'])) {
                // Set session variables
                $_SESSION['student_id'] = $student['student_id'];
                $_SESSION['student_db_id'] = $student['id'];
                $_SESSION['student_name'] = $student['name'];
                $_SESSION['student_email'] = $student['email'];

                // Redirect to student dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid Student ID or Password.";
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
        <h2>Student Login</h2>
        <p>Log in to access the election ballot and vote</p>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form action="login.php" method="POST">
        <div class="form-group">
            <label for="student_id" class="form-label">Student ID / Roll Number</label>
            <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. S101" required value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="password" class="form-label">Password</label>
            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
        </div>

        <button type="submit" class="btn btn-primary">Log In</button>

        <div class="form-footer">
            Don't have an account? <a href="register.php">Register here</a>
        </div>
    </form>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
