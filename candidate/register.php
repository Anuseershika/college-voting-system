<?php
$page_title = "Candidate Registration";
$active_page = "candidate_login";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

$error = "";
$success = "";

// Handle candidate registration POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $candidate_name = trim($_POST['candidate_name'] ?? '');
    $student_id = trim($_POST['student_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $position = trim($_POST['position'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $manifesto = trim($_POST['manifesto'] ?? '');

    // Server-side validation
    if (empty($candidate_name) || empty($student_id) || empty($department) || empty($position) || empty($email) || empty($password) || empty($confirm_password) || empty($manifesto)) {
        $error = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if student_id or email already exists in candidate_applications
            $stmt = $pdo->prepare("SELECT id FROM candidate_applications WHERE student_id = ? OR email = ?");
            $stmt->execute([$student_id, $email]);
            if ($stmt->fetch()) {
                $error = "A candidate application with this Student ID or Email already exists.";
            } else {
                
                // Handle Photo Upload
                $photo_name = 'default_candidate.svg'; // Fallback default
                
                $upload_dir = $path_prefix . 'assets/images/uploads/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $file_tmp = $_FILES['photo']['tmp_name'];
                    $file_name = $_FILES['photo']['name'];
                    $file_size = $_FILES['photo']['size'];
                    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                    
                    if (!in_array($file_ext, $allowed_exts)) {
                        $error = "Invalid file type. Only JPG, JPEG, PNG, and WEBP images are allowed.";
                    } elseif ($file_size > 2 * 1024 * 1024) {
                        $error = "File size exceeds 2MB limit.";
                    } else {
                        // Generate a unique filename to prevent collisions
                        $photo_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                        $target_path = $upload_dir . $photo_name;
                        
                        if (!move_uploaded_file($file_tmp, $target_path)) {
                            $error = "Failed to upload photo. Using default image instead.";
                            $photo_name = 'default_candidate.svg';
                        }
                    }
                }
                
                if (empty($error)) {
                    // Hash Password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert candidate application
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO candidate_applications 
                        (student_id, candidate_name, department, position, email, password, photo, manifesto, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Pending')
                    ");
                    
                    if ($insert_stmt->execute([$student_id, $candidate_name, $department, $position, $email, $hashed_password, $photo_name, $manifesto])) {
                        $success = "Application submitted successfully! Your application status is currently 'Pending' admin approval.";
                    } else {
                        $error = "Failed to submit application. Please try again.";
                    }
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

include $path_prefix . 'includes/header.php';
?>

<div class="form-card form-card-wide">
    <div class="form-header">
        <h2>Candidate Registration</h2>
        <p>Apply to stand as an official candidate in the upcoming election</p>
    </div>

    <!-- Alert Messages -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form action="register.php" method="POST" enctype="multipart/form-data" data-validate>
        <div class="form-grid">
            <div class="form-group">
                <label for="candidate_name" class="form-label">Full Name</label>
                <input type="text" id="candidate_name" name="candidate_name" class="form-control" placeholder="e.g. Michael Green" required value="<?php echo htmlspecialchars($candidate_name ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="student_id" class="form-label">Student ID / Roll Number</label>
                <input type="text" id="student_id" name="student_id" class="form-control" placeholder="e.g. C201" required value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label for="department" class="form-label">Academic Department</label>
                <select id="department" name="department" class="form-control" required>
                    <option value="" disabled selected>Select Department</option>
                    <option value="Computer Science" <?php echo ($department ?? '') === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Mechanical Engineering" <?php echo ($department ?? '') === 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engineering</option>
                    <option value="Electrical Engineering" <?php echo ($department ?? '') === 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                    <option value="Business Administration" <?php echo ($department ?? '') === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                    <option value="Physics" <?php echo ($department ?? '') === 'Physics' ? 'selected' : ''; ?>>Physics</option>
                    <option value="Chemistry" <?php echo ($department ?? '') === 'Chemistry' ? 'selected' : ''; ?>>Chemistry</option>
                    <option value="Humanities" <?php echo ($department ?? '') === 'Humanities' ? 'selected' : ''; ?>>Humanities</option>
                </select>
            </div>

            <div class="form-group">
                <label for="position" class="form-label">Position Applying For</label>
                <select id="position" name="position" class="form-control" required>
                    <option value="" disabled selected>Select Position</option>
                    <option value="President" <?php echo ($position ?? '') === 'President' ? 'selected' : ''; ?>>President</option>
                    <option value="Vice President" <?php echo ($position ?? '') === 'Vice President' ? 'selected' : ''; ?>>Vice President</option>
                    <option value="Secretary" <?php echo ($position ?? '') === 'Secretary' ? 'selected' : ''; ?>>Secretary</option>
                    <option value="Treasurer" <?php echo ($position ?? '') === 'Treasurer' ? 'selected' : ''; ?>>Treasurer</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="email" class="form-label">College Email Address</label>
            <input type="email" id="email" name="email" class="form-control" placeholder="e.g. michael@college.edu" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
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

        <!-- Custom Photo Upload layout with Preview -->
        <div class="form-group">
            <label class="form-label">Profile Photo (Max 2MB, JPG/PNG/WEBP)</label>
            <div class="photo-upload-container">
                <div id="photo-preview" class="photo-preview">No Image</div>
                <div style="flex-grow: 1;">
                    <input type="file" id="photo-input" name="photo" class="form-control" accept="image/*">
                </div>
            </div>
        </div>

        <div class="form-group">
            <label for="manifesto" class="form-label">Manifesto / About Me (Why should students vote for you?)</label>
            <textarea id="manifesto" name="manifesto" class="form-control" placeholder="Write your election manifesto here..." required><?php echo htmlspecialchars($manifesto ?? ''); ?></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Submit Application</button>

        <div class="form-footer">
            Already applied? <a href="login.php">Login to candidate portal</a>
        </div>
    </form>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
