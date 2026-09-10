<?php
$page_title = "Candidate Dashboard";
$active_page = "candidate_dashboard";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Verify candidate is logged in
if (!isset($_SESSION['candidate_id']) || !isset($_SESSION['candidate_db_id'])) {
    header('Location: login.php');
    exit;
}

$candidate_db_id = $_SESSION['candidate_db_id'];
$error = "";

try {
    // 1. Fetch current candidate details from DB (real-time check)
    $stmt = $pdo->prepare("SELECT * FROM candidate_applications WHERE id = ?");
    $stmt->execute([$candidate_db_id]);
    $candidate = $stmt->fetch();

    if (!$candidate) {
        // Session exists but candidate deleted
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // 2. Fetch Election Status
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'election_status'");
    $stmt->execute();
    $setting = $stmt->fetch();
    $election_status = $setting ? $setting['setting_value'] : 'voting';

    // 3. Fetch Votes Count (always calculated, but display logic relies on election status)
    $stmt = $pdo->prepare("SELECT COUNT(*) as vote_count FROM votes WHERE candidate_id = ?");
    $stmt->execute([$candidate_db_id]);
    $votes_fetched = $stmt->fetch();
    $vote_count = $votes_fetched ? $votes_fetched['vote_count'] : 0;

    // Calculate total votes cast in the system to compute candidate percentage
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_votes FROM votes");
    $stmt->execute();
    $total_votes_fetched = $stmt->fetch();
    $total_votes_cast = $total_votes_fetched ? $total_votes_fetched['total_votes'] : 0;

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include $path_prefix . 'includes/header.php';
?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Sidebar -->
    <div class="dashboard-sidebar">
        <h3 class="sidebar-title">Candidate Menu</h3>
        <ul class="sidebar-menu">
            <li><a href="#" class="sidebar-link active">Profile Info</a></li>
            <li><a href="../index.php" class="sidebar-link">Public Home</a></li>
        </ul>
        
        <div style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px;">
            <a href="logout.php" class="btn btn-outline btn-sm">Log Out</a>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="dashboard-content">
        <!-- Dashboard Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>Candidate Dashboard</h1>
                <p>Monitor your election status and profile details.</p>
            </div>
            <div>
                <?php if ($candidate['status'] === 'Pending'): ?>
                    <span class="badge badge-pending" style="font-size: 0.9rem; padding: 8px 16px;">Application Pending</span>
                <?php elseif ($candidate['status'] === 'Approved'): ?>
                    <span class="badge badge-approved" style="font-size: 0.9rem; padding: 8px 16px;">Approved Candidate</span>
                <?php elseif ($candidate['status'] === 'Rejected'): ?>
                    <span class="badge badge-rejected" style="font-size: 0.9rem; padding: 8px 16px;">Application Rejected</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- System Alerts depending on candidate approval status -->
        <?php if ($candidate['status'] === 'Pending'): ?>
            <div class="alert alert-warning" style="margin-bottom: 0;">
                <div>
                    <strong>Application Under Review!</strong> Your nomination is currently pending approval by the admin. Once approved, your profile will become visible to student voters in the voting center.
                </div>
            </div>
        <?php elseif ($candidate['status'] === 'Rejected'): ?>
            <div class="alert alert-danger" style="margin-bottom: 0;">
                <div>
                    <strong>Application Rejected!</strong> Unfortunately, your candidate application has been declined by the system administrators. Please contact the student affairs coordinator for clarification.
                </div>
            </div>
        <?php endif; ?>

        <!-- Votes Count Display Block -->
        <div class="stats-row">
            <!-- Election Phase status card -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--success-light); color: var(--success);">⏱</div>
                <div class="stat-info">
                    <span class="stat-val" style="text-transform: capitalize; font-size: 1.4rem;"><?php echo htmlspecialchars($election_status); ?></span>
                    <span class="stat-lbl">Election Phase</span>
                </div>
            </div>

            <!-- Votes count card (hidden until election concluded) -->
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--warning-light); color: var(--warning);">🏆</div>
                <div class="stat-info">
                    <?php if ($candidate['status'] !== 'Approved'): ?>
                        <span class="stat-val" style="font-size: 1.1rem; color: var(--text-muted);">N/A</span>
                        <span class="stat-lbl">Not Approved</span>
                    <?php elseif ($election_status !== 'ended'): ?>
                        <span class="stat-val" style="font-size: 1.1rem; color: var(--text-muted);">Locked 🔒</span>
                        <span class="stat-lbl">Awaiting Results</span>
                    <?php else: ?>
                        <span class="stat-val"><?php echo $vote_count; ?></span>
                        <span class="stat-lbl">Votes (<?php echo $total_votes_cast > 0 ? round(($vote_count / $total_votes_cast) * 100, 1) : 0; ?>%)</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Detailed Profile Display Card -->
        <div class="form-card" style="max-width: 100%; margin: 0; padding: 30px;">
            <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 700; margin-bottom: 24px; border-bottom: 2px solid var(--bg-alt); padding-bottom: 10px;">Submitted Profile Details</h2>
            
            <div style="display: flex; gap: 30px; flex-wrap: wrap; align-items: flex-start;">
                <!-- Profile Image Display -->
                <div style="width: 150px; text-align: center;">
                    <?php 
                        $photo_path = $path_prefix . "assets/images/uploads/" . $candidate['photo'];
                        // If file doesn't exist in uploads, check parent/fallback
                        if (!file_exists($photo_path) || empty($candidate['photo'])) {
                            $photo_path = $path_prefix . "assets/images/" . $candidate['photo'];
                        }
                        if (!file_exists($photo_path) || empty($candidate['photo'])) {
                            $photo_path = $path_prefix . "assets/images/default_candidate.svg";
                        }
                    ?>
                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($candidate['candidate_name']); ?>" style="width: 130px; height: 130px; object-fit: cover; border-radius: 50%; border: 4px solid var(--border); box-shadow: var(--shadow-md); margin-bottom: 12px;">
                    <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 550;">NOMINATION IMAGE</p>
                </div>
                
                <!-- Grid of details -->
                <div style="flex-grow: 1; display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px;">
                    <div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">FULL NAME</p>
                        <p style="font-weight: 600; font-size: 1.1rem; color: var(--text-main);"><?php echo htmlspecialchars($candidate['candidate_name']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">STUDENT ID / ROLL NO.</p>
                        <p style="font-weight: 600; font-size: 1.1rem; color: var(--text-main);"><?php echo htmlspecialchars($candidate['student_id']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">ACADEMIC DEPARTMENT</p>
                        <p style="font-weight: 600; font-size: 1.1rem; color: var(--text-main);"><?php echo htmlspecialchars($candidate['department']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">POSITION APPLIED FOR</p>
                        <p style="font-weight: 600; font-size: 1.1rem; color: var(--primary);"><?php echo htmlspecialchars($candidate['position']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">EMAIL ADDRESS</p>
                        <p style="font-weight: 600; font-size: 1.05rem; color: var(--text-main);"><?php echo htmlspecialchars($candidate['email']); ?></p>
                    </div>
                    <div>
                        <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">APPLICATION STATUS</p>
                        <p style="font-weight: 700; font-size: 1.1rem;"><?php echo htmlspecialchars($candidate['status']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Manifesto Box -->
            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid var(--border);">
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600; margin-bottom: 8px;">SUBMITTED MANIFESTO</p>
                <div style="background-color: var(--bg-alt); padding: 20px; border-radius: var(--radius-sm); border-left: 4px solid var(--secondary); font-size: 0.95rem; white-space: pre-wrap;"><?php echo htmlspecialchars($candidate['manifesto']); ?></div>
            </div>
        </div>
    </div>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
