<?php
$page_title = "Manage Student Voters";
$active_page = "manage_students";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Verify admin session
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = "";
$success = "";
$action = $_GET['action'] ?? 'list';
$edit_id = intval($_GET['id'] ?? 0);
$edit_student = null;

// --- 1. Handle Delete Student Action ---
if ($action === 'delete' && $edit_id > 0) {
    try {
        // Fetch student details to get student_id
        $stmt = $pdo->prepare("SELECT student_id FROM students WHERE id = ?");
        $stmt->execute([$edit_id]);
        $student = $stmt->fetch();

        if ($student) {
            $s_id = $student['student_id'];
            
            $pdo->beginTransaction();
            
            // Delete student's vote if they have cast one
            $del_vote = $pdo->prepare("DELETE FROM votes WHERE student_id = ?");
            $del_vote->execute([$s_id]);

            // Delete student record
            $del_stud = $pdo->prepare("DELETE FROM students WHERE id = ?");
            $del_stud->execute([$edit_id]);
            
            $pdo->commit();
            $success = "Student voter deleted successfully and associated vote (if any) was removed.";
        } else {
            $error = "Student not found.";
        }
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = "Database error: " . $e->getMessage();
    }
    $action = 'list'; // Switch back to listing
}

// --- 2. Handle Edit Form Loading ---
if ($action === 'edit' && $edit_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_student = $stmt->fetch();
        if (!$edit_student) {
            $error = "Student voter not found.";
            $action = 'list';
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        $action = 'list';
    }
}

// --- 3. Handle Add / Edit POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Create New Student manually
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'add') {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $has_voted = intval($_POST['has_voted'] ?? 0);

        if (empty($student_id) || empty($name) || empty($email) || empty($password)) {
            $error = "Please fill in all fields.";
        } else {
            try {
                // Check if student_id or email already exists in students table
                $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ? OR email = ?");
                $stmt->execute([$student_id, $email]);
                if ($stmt->fetch()) {
                    $error = "A student with this Student ID or Email already exists.";
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $insert_stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, password, has_voted) VALUES (?, ?, ?, ?, ?)");
                    if ($insert_stmt->execute([$student_id, $name, $email, $hashed_password, $has_voted])) {
                        $success = "Student voter record created successfully.";
                    } else {
                        $error = "Failed to create student record.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    // Save Edited Student
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'edit' && $edit_id > 0) {
        $student_id = trim($_POST['student_id'] ?? '');
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $has_voted = intval($_POST['has_voted'] ?? 0);

        if (empty($student_id) || empty($name) || empty($email)) {
            $error = "Please fill in all required fields.";
        } else {
            try {
                // Verify uniqueness of ID and Email
                $stmt = $pdo->prepare("SELECT id FROM students WHERE (student_id = ? OR email = ?) AND id != ?");
                $stmt->execute([$student_id, $email, $edit_id]);
                if ($stmt->fetch()) {
                    $error = "Another student is registered with this Student ID or Email.";
                } else {
                    $pdo->beginTransaction();
                    
                    // Retrieve old student_id
                    $stmt_old = $pdo->prepare("SELECT student_id, has_voted FROM students WHERE id = ?");
                    $stmt_old->execute([$edit_id]);
                    $old_data = $stmt_old->fetch();
                    $old_student_id = $old_data['student_id'];
                    $old_has_voted = $old_data['has_voted'];

                    // Update student ID inside votes table if it changed to keep integrity
                    if ($old_student_id !== $student_id) {
                        $up_votes = $pdo->prepare("UPDATE votes SET student_id = ? WHERE student_id = ?");
                        $up_votes->execute([$student_id, $old_student_id]);
                    }

                    // If admin marks student as 'Not Voted', remove their cast ballot records
                    if ($old_has_voted == 1 && $has_voted == 0) {
                        $del_vote = $pdo->prepare("DELETE FROM votes WHERE student_id = ?");
                        $del_vote->execute([$student_id]);
                    }

                    // Handle optional password update
                    $pass_sql = "";
                    $pass_params = [];
                    if (!empty($password)) {
                        $pass_sql = ", password = ?";
                        $pass_params = [password_hash($password, PASSWORD_DEFAULT)];
                    }

                    // Perform main update
                    $sql = "UPDATE students SET student_id = ?, name = ?, email = ?, has_voted = ? $pass_sql WHERE id = ?";
                    $params = array_merge([$student_id, $name, $email, $has_voted], $pass_params, [$edit_id]);
                    
                    $up_stmt = $pdo->prepare($sql);
                    $up_stmt->execute($params);
                    
                    $pdo->commit();
                    $success = "Student voter registry updated successfully.";
                    $action = 'list'; // Reset action to listing table
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch list of registered students
$students_list = [];
try {
    $stmt = $pdo->query("SELECT * FROM students ORDER BY student_id ASC");
    $students_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database fetch failure: " . $e->getMessage();
}

include $path_prefix . 'includes/header.php';
?>

<!-- Alerts -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
        <h3 class="sidebar-title">Admin Controls</h3>
        <ul class="sidebar-menu">
            <li><a href="dashboard.php" class="sidebar-link">Admin Dashboard</a></li>
            <li><a href="manage_students.php" class="sidebar-link active">Manage Student Voters</a></li>
            <li><a href="manage_candidates.php" class="sidebar-link">Manage Candidates</a></li>
            <li><a href="../index.php" class="sidebar-link">Public Home</a></li>
        </ul>
    </div>

    <!-- Main Panel -->
    <div class="dashboard-content">
        
        <?php if ($action === 'edit' && $edit_student): ?>
            <!-- ==========================================================================
                 EDIT STUDENT FORM VIEW
                 ========================================================================== -->
            <div class="page-header">
                <div class="page-title">
                    <h1>Edit Voter Registry</h1>
                    <p>Modify profile details for student: <?php echo htmlspecialchars($edit_student['name']); ?></p>
                </div>
                <div>
                    <a href="manage_students.php" class="btn btn-outline btn-sm btn-inline">Back to List</a>
                </div>
            </div>

            <div class="form-card" style="margin: 0; max-width: 100%;">
                <form action="manage_students.php?action=edit&id=<?php echo $edit_id; ?>" method="POST" data-validate>
                    <input type="hidden" name="action_type" value="edit">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required value="<?php echo htmlspecialchars($edit_student['name']); ?>">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID / Roll No.</label>
                            <input type="text" id="student_id" name="student_id" class="form-control" required value="<?php echo htmlspecialchars($edit_student['student_id']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($edit_student['email']); ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password" class="form-label">Password (Leave empty to keep current)</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Update password (optional)">
                        </div>
                        <div class="form-group">
                            <label for="has_voted" class="form-label">Voting Status</label>
                            <select id="has_voted" name="has_voted" class="form-control" required>
                                <option value="0" <?php echo $edit_student['has_voted'] == 0 ? 'selected' : ''; ?>>Not Voted</option>
                                <option value="1" <?php echo $edit_student['has_voted'] == 1 ? 'selected' : ''; ?>>Voted</option>
                            </select>
                            <p style="font-size: 0.8rem; color: var(--text-muted); margin-top: 4px;">* Changing status from Voted to Not Voted deletes their submitted vote from the database.</p>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-secondary">Save Changes</button>
                </form>
            </div>

        <?php else: ?>
            <!-- ==========================================================================
                 STUDENT LISTING & ADD FORM VIEW
                 ========================================================================== -->
            <div class="page-header">
                <div class="page-title">
                    <h1>Student Voter Database</h1>
                    <p>Manually insert students or update registry entries.</p>
                </div>
            </div>

            <!-- Add New Student Voter Section -->
            <details class="form-card" style="max-width: 100%; margin: 0; padding: 20px;" <?php echo (!empty($error) && isset($_POST['action_type']) && $_POST['action_type'] === 'add') ? 'open' : ''; ?>>
                <summary style="font-weight: 600; color: var(--primary); cursor: pointer; font-size: 1.1rem; outline: none;">
                    ➕ Register New Student Voter (Click to expand)
                </summary>
                
                <form action="manage_students.php" method="POST" style="margin-top: 20px;" data-validate>
                    <input type="hidden" name="action_type" value="add">
                    
                    <div class="form-group">
                        <label for="name" class="form-label">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required placeholder="e.g. Charlie Brown" value="<?php echo htmlspecialchars($name ?? ''); ?>">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID / Roll No.</label>
                            <input type="text" id="student_id" name="student_id" class="form-control" required placeholder="e.g. S106" value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="email" class="form-label">College Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required placeholder="e.g. charlie@college.edu" value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="At least 6 characters">
                        </div>
                        <div class="form-group">
                            <label for="has_voted" class="form-label">Voted Status</label>
                            <select id="has_voted" name="has_voted" class="form-control" required>
                                <option value="0" selected>Not Voted</option>
                                <option value="1">Voted (Place-holder without casting active vote)</option>
                            </select>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-inline" style="width: auto; padding: 12px 30px;">Add Student Voter</button>
                </form>
            </details>

            <!-- Student Registry List Table -->
            <div class="table-responsive" style="margin-top: 30px;">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Voter Name</th>
                            <th>Student ID</th>
                            <th>Email Address</th>
                            <th>Status</th>
                            <th>Administration Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($students_list) === 0): ?>
                            <tr>
                                <td colspan="5" style="text-align: center; color: var(--text-muted);">No student records found in the database.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students_list as $stud): ?>
                                <tr>
                                    <td style="font-weight: 600;"><?php echo htmlspecialchars($stud['name']); ?></td>
                                    <td><code><?php echo htmlspecialchars($stud['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($stud['email']); ?></td>
                                    <td>
                                        <?php if ($stud['has_voted']): ?>
                                            <span class="badge badge-approved">Voted</span>
                                        <?php else: ?>
                                            <span class="badge badge-pending">Not Voted</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="manage_students.php?action=edit&id=<?php echo $stud['id']; ?>" class="btn btn-outline btn-sm btn-inline" style="padding: 6px 12px;">Edit</a>
                                            <a href="manage_students.php?action=delete&id=<?php echo $stud['id']; ?>" class="btn btn-danger btn-sm btn-inline" style="padding: 6px 12px; border: none;" onclick="return confirm('Are you sure you want to delete this student voter registry?\n\nThis will permanently delete the account and associated votes.');">Delete</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
