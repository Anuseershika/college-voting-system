<?php
$page_title = "Manage Candidates";
$active_page = "manage_candidates";
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
$edit_candidate = null;

// --- 1. Handle Delete Candidate Action ---
if ($action === 'delete' && $edit_id > 0) {
    try {
        // Fetch candidate to delete photo file
        $stmt = $pdo->prepare("SELECT photo FROM candidate_applications WHERE id = ?");
        $stmt->execute([$edit_id]);
        $cand = $stmt->fetch();
        
        // Delete database record
        $delete_stmt = $pdo->prepare("DELETE FROM candidate_applications WHERE id = ?");
        if ($delete_stmt->execute([$edit_id])) {
            // Unlink photo if not default svg
            if ($cand && $cand['photo'] !== 'default_candidate.svg') {
                $file_path = $path_prefix . "assets/images/uploads/" . $cand['photo'];
                if (file_exists($file_path)) {
                    unlink($file_path);
                }
            }
            $success = "Candidate deleted successfully.";
        } else {
            $error = "Failed to delete candidate.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
    $action = 'list'; // Reset action to list
}

// --- 2. Handle Edit Form Loading ---
if ($action === 'edit' && $edit_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM candidate_applications WHERE id = ?");
        $stmt->execute([$edit_id]);
        $edit_candidate = $stmt->fetch();
        if (!$edit_candidate) {
            $error = "Candidate record not found.";
            $action = 'list';
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
        $action = 'list';
    }
}

// --- 3. Handle Add / Edit POST Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Create New Candidate Manually
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'add') {
        $candidate_name = trim($_POST['candidate_name'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $manifesto = trim($_POST['manifesto'] ?? '');
        $status = $_POST['status'] ?? 'Approved';

        if (empty($candidate_name) || empty($student_id) || empty($department) || empty($position) || empty($email) || empty($password) || empty($manifesto)) {
            $error = "All fields are required to add a candidate.";
        } else {
            try {
                // Check unique constraint
                $stmt = $pdo->prepare("SELECT id FROM candidate_applications WHERE student_id = ? OR email = ?");
                $stmt->execute([$student_id, $email]);
                if ($stmt->fetch()) {
                    $error = "Candidate with this Student ID or Email already exists.";
                } else {
                    // Handle file upload
                    $photo_name = 'default_candidate.svg';
                    $upload_dir = $path_prefix . 'assets/images/uploads/';
                    
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['photo']['tmp_name'];
                        $file_name = $_FILES['photo']['name'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            $photo_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                            if (!move_uploaded_file($file_tmp, $upload_dir . $photo_name)) {
                                $photo_name = 'default_candidate.svg';
                            }
                        }
                    }
                    
                    // Hash Password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    $insert_stmt = $pdo->prepare("
                        INSERT INTO candidate_applications 
                        (student_id, candidate_name, department, position, email, password, photo, manifesto, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    if ($insert_stmt->execute([$student_id, $candidate_name, $department, $position, $email, $hashed_password, $photo_name, $manifesto, $status])) {
                        $success = "Candidate manual entry added successfully.";
                    } else {
                        $error = "Failed to create candidate entry.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }

    // Save Edited Candidate
    if (isset($_POST['action_type']) && $_POST['action_type'] === 'edit' && $edit_id > 0) {
        $candidate_name = trim($_POST['candidate_name'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $manifesto = trim($_POST['manifesto'] ?? '');
        $status = $_POST['status'] ?? 'Approved';

        if (empty($candidate_name) || empty($student_id) || empty($department) || empty($position) || empty($email) || empty($manifesto)) {
            $error = "All fields are required to update candidate.";
        } else {
            try {
                // Check if another candidate has the same ID or Email
                $stmt = $pdo->prepare("SELECT id FROM candidate_applications WHERE (student_id = ? OR email = ?) AND id != ?");
                $stmt->execute([$student_id, $email, $edit_id]);
                if ($stmt->fetch()) {
                    $error = "Another candidate with this Student ID or Email already exists.";
                } else {
                    // Update photo if uploaded
                    $photo_update_sql = "";
                    $photo_update_params = [];
                    
                    if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $file_tmp = $_FILES['photo']['tmp_name'];
                        $file_name = $_FILES['photo']['name'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp'];
                        
                        if (in_array($file_ext, $allowed_exts)) {
                            $upload_dir = $path_prefix . 'assets/images/uploads/';
                            $photo_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", $file_name);
                            if (move_uploaded_file($file_tmp, $upload_dir . $photo_name)) {
                                $photo_update_sql = ", photo = ?";
                                $photo_update_params = [$photo_name];
                                
                                // Delete old photo if it wasn't default
                                if ($edit_candidate && $edit_candidate['photo'] !== 'default_candidate.svg') {
                                    $old_path = $upload_dir . $edit_candidate['photo'];
                                    if (file_exists($old_path)) {
                                        unlink($old_path);
                                    }
                                }
                            }
                        }
                    }

                    // Update password if typed
                    $pass_update_sql = "";
                    $pass_update_params = [];
                    if (!empty($password)) {
                        $pass_update_sql = ", password = ?";
                        $pass_update_params = [password_hash($password, PASSWORD_DEFAULT)];
                    }

                    // Prepare main SQL
                    $sql = "UPDATE candidate_applications SET student_id = ?, candidate_name = ?, department = ?, position = ?, email = ?, manifesto = ?, status = ? $photo_update_sql $pass_update_sql WHERE id = ?";
                    $params = array_merge([$student_id, $candidate_name, $department, $position, $email, $manifesto, $status], $photo_update_params, $pass_update_params, [$edit_id]);
                    
                    $update_stmt = $pdo->prepare($sql);
                    if ($update_stmt->execute($params)) {
                        $success = "Candidate profile updated successfully.";
                        $action = 'list'; // Switch back to view list
                    } else {
                        $error = "Failed to update candidate record.";
                    }
                }
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Fetch all candidates for the summary list table
$candidates_list = [];
try {
    $stmt = $pdo->query("SELECT * FROM candidate_applications ORDER BY id DESC");
    $candidates_list = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database fetch failure: " . $e->getMessage();
}

include $path_prefix . 'includes/header.php';
?>

<!-- Action Feedbacks -->
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
            <li><a href="manage_students.php" class="sidebar-link">Manage Student Voters</a></li>
            <li><a href="manage_candidates.php" class="sidebar-link active">Manage Candidates</a></li>
            <li><a href="../index.php" class="sidebar-link">Public Home</a></li>
        </ul>
    </div>

    <!-- Main Panel -->
    <div class="dashboard-content">
        
        <?php if ($action === 'edit' && $edit_candidate): ?>
            <!-- ==========================================================================
                 EDIT CANDIDATE FORM VIEW
                 ========================================================================== -->
            <div class="page-header">
                <div class="page-title">
                    <h1>Edit Candidate</h1>
                    <p>Modify nomination data and profiles for: <?php echo htmlspecialchars($edit_candidate['candidate_name']); ?></p>
                </div>
                <div>
                    <a href="manage_candidates.php" class="btn btn-outline btn-sm btn-inline">Back to List</a>
                </div>
            </div>

            <div class="form-card form-card-wide" style="margin: 0;">
                <form action="manage_candidates.php?action=edit&id=<?php echo $edit_id; ?>" method="POST" enctype="multipart/form-data" data-validate>
                    <input type="hidden" name="action_type" value="edit">

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="candidate_name" class="form-label">Full Name</label>
                            <input type="text" id="candidate_name" name="candidate_name" class="form-control" required value="<?php echo htmlspecialchars($edit_candidate['candidate_name']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" id="student_id" name="student_id" class="form-control" required value="<?php echo htmlspecialchars($edit_candidate['student_id']); ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="department" class="form-label">Department</label>
                            <select id="department" name="department" class="form-control" required>
                                <option value="Computer Science" <?php echo $edit_candidate['department'] === 'Computer Science' ? 'selected' : ''; ?>>Computer Science</option>
                                <option value="Mechanical Engineering" <?php echo $edit_candidate['department'] === 'Mechanical Engineering' ? 'selected' : ''; ?>>Mechanical Engineering</option>
                                <option value="Electrical Engineering" <?php echo $edit_candidate['department'] === 'Electrical Engineering' ? 'selected' : ''; ?>>Electrical Engineering</option>
                                <option value="Business Administration" <?php echo $edit_candidate['department'] === 'Business Administration' ? 'selected' : ''; ?>>Business Administration</option>
                                <option value="Physics" <?php echo $edit_candidate['department'] === 'Physics' ? 'selected' : ''; ?>>Physics</option>
                                <option value="Chemistry" <?php echo $edit_candidate['department'] === 'Chemistry' ? 'selected' : ''; ?>>Chemistry</option>
                                <option value="Humanities" <?php echo $edit_candidate['department'] === 'Humanities' ? 'selected' : ''; ?>>Humanities</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="position" class="form-label">Position Applying For</label>
                            <select id="position" name="position" class="form-control" required>
                                <option value="President" <?php echo $edit_candidate['position'] === 'President' ? 'selected' : ''; ?>>President</option>
                                <option value="Vice President" <?php echo $edit_candidate['position'] === 'Vice President' ? 'selected' : ''; ?>>Vice President</option>
                                <option value="Secretary" <?php echo $edit_candidate['position'] === 'Secretary' ? 'selected' : ''; ?>>Secretary</option>
                                <option value="Treasurer" <?php echo $edit_candidate['position'] === 'Treasurer' ? 'selected' : ''; ?>>Treasurer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($edit_candidate['email']); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">nomination Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="Pending" <?php echo $edit_candidate['status'] === 'Pending' ? 'selected' : ''; ?>>Pending Evaluation</option>
                                <option value="Approved" <?php echo $edit_candidate['status'] === 'Approved' ? 'selected' : ''; ?>>Approved Candidate</option>
                                <option value="Rejected" <?php echo $edit_candidate['status'] === 'Rejected' ? 'selected' : ''; ?>>Rejected nomination</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password" class="form-label">Password (Leave blank to keep current password)</label>
                        <input type="password" id="password" name="password" class="form-control" placeholder="Update password (optional)">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Upload New Photo (Optional, replaces existing)</label>
                        <div class="photo-upload-container">
                            <?php 
                                $preview_path = $path_prefix . "assets/images/uploads/" . $edit_candidate['photo'];
                                if (!file_exists($preview_path) || empty($edit_candidate['photo'])) {
                                    $preview_path = $path_prefix . "assets/images/" . $edit_candidate['photo'];
                                }
                                if (!file_exists($preview_path) || empty($edit_candidate['photo'])) {
                                    $preview_path = $path_prefix . "assets/images/default_candidate.svg";
                                }
                            ?>
                            <div id="photo-preview" class="photo-preview" style="background-image: url('<?php echo htmlspecialchars($preview_path); ?>'); background-size: cover; background-position: center;"></div>
                            <div style="flex-grow: 1;">
                                <input type="file" id="photo-input" name="photo" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="manifesto" class="form-label">Manifesto / About Me</label>
                        <textarea id="manifesto" name="manifesto" class="form-control" required><?php echo htmlspecialchars($edit_candidate['manifesto']); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-secondary">Save Updates</button>
                </form>
            </div>

        <?php else: ?>
            <!-- ==========================================================================
                 CANDIDATE LISTING & ADD NEW FORM
                 ========================================================================== -->
            <div class="page-header">
                <div class="page-title">
                    <h1>Nomination Databases</h1>
                    <p>Add new nominations or manage active database profiles below.</p>
                </div>
            </div>

            <!-- Add New Candidate Accordion -->
            <details class="form-card" style="max-width: 100%; margin: 0; padding: 20px;" <?php echo (!empty($error) && isset($_POST['action_type']) && $_POST['action_type'] === 'add') ? 'open' : ''; ?>>
                <summary style="font-weight: 600; color: var(--primary); cursor: pointer; font-size: 1.1rem; outline: none; display: flex; align-items: center; gap: 8px;">
                    ➕ Create Candidate Profile (Click to expand)
                </summary>
                
                <form action="manage_candidates.php" method="POST" enctype="multipart/form-data" style="margin-top: 20px;" data-validate>
                    <input type="hidden" name="action_type" value="add">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="candidate_name" class="form-label">Full Name</label>
                            <input type="text" id="candidate_name" name="candidate_name" class="form-control" required value="<?php echo htmlspecialchars($candidate_name ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="student_id" class="form-label">Student ID</label>
                            <input type="text" id="student_id" name="student_id" class="form-control" required value="<?php echo htmlspecialchars($student_id ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="department" class="form-label">Academic Department</label>
                            <select id="department" name="department" class="form-control" required>
                                <option value="" disabled selected>Select Department</option>
                                <option value="Computer Science">Computer Science</option>
                                <option value="Mechanical Engineering">Mechanical Engineering</option>
                                <option value="Electrical Engineering">Electrical Engineering</option>
                                <option value="Business Administration">Business Administration</option>
                                <option value="Physics">Physics</option>
                                <option value="Chemistry">Chemistry</option>
                                <option value="Humanities">Humanities</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="position" class="form-label">Position Applying For</label>
                            <select id="position" name="position" class="form-control" required>
                                <option value="" disabled selected>Select Position</option>
                                <option value="President">President</option>
                                <option value="Vice President">Vice President</option>
                                <option value="Secretary">Secretary</option>
                                <option value="Treasurer">Treasurer</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">Default Application Status</label>
                            <select id="status" name="status" class="form-control" required>
                                <option value="Approved" selected>Approved (Instant Runner)</option>
                                <option value="Pending">Pending Evaluation</option>
                                <option value="Rejected">Rejected</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" id="password" name="password" class="form-control" required placeholder="Minimum 6 characters">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="Retype password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Profile Image (Optional)</label>
                        <div class="photo-upload-container">
                            <div id="photo-preview" class="photo-preview">No Image</div>
                            <div style="flex-grow: 1;">
                                <input type="file" id="photo-input" name="photo" class="form-control" accept="image/*">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="manifesto" class="form-label">Candidate Manifesto</label>
                        <textarea id="manifesto" name="manifesto" class="form-control" required placeholder="Write profile bio and election promises here..."><?php echo htmlspecialchars($manifesto ?? ''); ?></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-inline" style="width: auto; padding: 12px 30px;">Add Candidate</button>
                </form>
            </details>

            <!-- Candidates Summary List Table -->
            <div class="table-responsive" style="margin-top: 30px;">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Candidate Photo & Name</th>
                            <th>Student ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Administration Options</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($candidates_list) === 0): ?>
                            <tr>
                                <td colspan="6" style="text-align: center; color: var(--text-muted);">No candidates exist in the system registry.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($candidates_list as $cand): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php 
                                                $path = $path_prefix . "assets/images/uploads/" . $cand['photo'];
                                                if (!file_exists($path) || empty($cand['photo'])) {
                                                    $path = $path_prefix . "assets/images/" . $cand['photo'];
                                                }
                                                if (!file_exists($path) || empty($cand['photo'])) {
                                                    $path = $path_prefix . "assets/images/default_candidate.svg";
                                                }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($path); ?>" alt="" style="width: 42px; height: 42px; object-fit: cover; border-radius: 50%; border: 1px solid var(--border);">
                                            <div>
                                                <strong style="display: block;"><?php echo htmlspecialchars($cand['candidate_name']); ?></strong>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($cand['email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($cand['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($cand['department']); ?></td>
                                    <td><span class="badge badge-approved" style="background-color: var(--primary); color: white; border: none; font-size: 0.75rem;"><?php echo htmlspecialchars($cand['position']); ?></span></td>
                                    <td>
                                        <?php if ($cand['status'] === 'Approved'): ?>
                                            <span class="badge badge-approved">Approved</span>
                                        <?php elseif ($cand['status'] === 'Pending'): ?>
                                            <span class="badge badge-pending">Pending</span>
                                        <?php else: ?>
                                            <span class="badge badge-rejected">Rejected</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <a href="manage_candidates.php?action=edit&id=<?php echo $cand['id']; ?>" class="btn btn-outline btn-sm btn-inline" style="padding: 6px 12px;">Edit</a>
                                            <a href="manage_candidates.php?action=delete&id=<?php echo $cand['id']; ?>" class="btn btn-danger btn-sm btn-inline" style="padding: 6px 12px; border: none;" onclick="return confirm('Are you sure you want to delete this candidate application?\n\nThis will also remove all associated votes cast for them.');">Delete</a>
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
