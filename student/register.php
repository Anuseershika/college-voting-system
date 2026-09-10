<?php
$page_title = "Student Registration";
$active_page = "student_login";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

$error = "";
$success = "";

// Handle student registration POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Server-side validation
    if (empty($student_id) || empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if student_id or email already exists in students table
            $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
            $stmt->execute([$student_id, $email]);
            if ($stmt->fetch()) {
                $error = "A student with this Student ID or Email already exists.";
            } else {
                // Securely hash the password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert student record
                $insert_stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, password, has_voted) VALUES (?, ?, ?, ?, 0)");
                if ($insert_stmt->execute([$student_id, $name, $email, $hashed_password])) {
                    $success = "Registration successful! You can now log in.";
                } else {
                    $error = "Registration failed. Please try again.";
                }
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
        <h2>Student Registration</h2>
        <p>Register to participate in the college election polls</p>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST" data-validate>
        <div class="form-group">
            <label for="student_id" class="form-label">Student ID / Roll Number</label>
            <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. S101" required value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="name" class="form-label">Full Name</label>
            <input type="text" id="name" name="name" class="form-control" placeholder="e.g. John Doe" required value="<?php echo htmlspecialchars($name ?? ''); ?>">
        </div>

        <div class="form-group">
            <label for="email" class="form-label">College Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="e.g. john@college.edu" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="password" class="form-label">Password</label>
                <input type="password" id="password" name="password" class="form-control" placeholder="At least 6 characters" required>
            </div>
            <div class="form-group">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" class="form-control" placeholder="Retype password" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Register Account</button>

        <div class="form-footer">
            Already registered? <a href="login.php">Login here</a>
        </div>
    </form>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
