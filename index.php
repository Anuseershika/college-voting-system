<?php
$page_title = "Home";
$active_page = "home";
$path_prefix = "";

// Include database connection
if (file_exists('config/db.php')) {
    include 'config/db.php';
}

// Fetch election status from settings
$election_status = 'voting'; // Default fallback
$db_configured = false;

if (isset($pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'election_status'");
        $stmt->execute();
        $setting = $stmt->fetch();
        if ($setting) {
            $election_status = $setting['setting_value'];
        }
        $db_configured = true;
    } catch (PDOException $e) {
        // Database table might not be imported yet
        $db_configured = false;
    }
}

include 'includes/header.php';
?>

<!-- Database Setup Notice if not configured -->
<?php if (!$db_configured): ?>
    <div class="alert alert-warning">
        <div>
            <strong>Database Not Initialized!</strong> To start using the system, please configure your MySQL database, import the SQL file located in <code>database/schema.sql</code>, or review the setup documentation.
        </div>
    </div>
<?php endif; ?>

<!-- Hero Section -->
<div class="hero-section">
    <div class="status-badge-container">
        <?php if ($election_status === 'setup'): ?>
            <span class="status-badge setup">
                <span class="pulse-dot"></span> Setup & Registration Phase
            </span>
        <?php elseif ($election_status === 'voting'): ?>
            <span class="status-badge voting">
                <span class="pulse-dot"></span> Voting Open - Cast Your Vote
            </span>
        <?php elseif ($election_status === 'ended'): ?>
            <span class="status-badge ended">
                Election Concluded - Results Declared
            </span>
        <?php endif; ?>
    </div>
    
    <h1 class="hero-title">College Online Voting System</h1>
    <p class="hero-subtitle">A secure, transparent, and user-friendly digital platform for student representation elections.</p>
</div>

<!-- Main Role Selection Section -->
<div class="roles-grid">
    <!-- Student (Voter) Role -->
    <div class="role-card">
        <div class="role-icon-wrapper">🎓</div>
        <h2 class="role-title">Student Module</h2>
        <p class="role-desc">Register as a student voter, check the approved candidates list, and cast your single vote during the polling phase.</p>
        <div class="role-actions">
            <?php if (isset($_SESSION['student_id'])): ?>
                <a href="student/dashboard.php" class="btn btn-primary">Go to Voting Panel</a>
            <?php else: ?>
                <a href="student/login.php" class="btn btn-primary">Student Login</a>
                <a href="student/register.php" class="btn btn-outline">Student Registration</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Candidate Role -->
    <div class="role-card">
        <div class="role-icon-wrapper">🏆</div>
        <h2 class="role-title">Candidate Module</h2>
        <p class="role-desc">Apply as an election candidate, upload your profile picture, submit your election manifesto, and track live application review.</p>
        <div class="role-actions">
            <?php if (isset($_SESSION['candidate_id'])): ?>
                <a href="candidate/dashboard.php" class="btn btn-primary">Go to Dashboard</a>
            <?php else: ?>
                <a href="candidate/login.php" class="btn btn-primary">Candidate Login</a>
                <a href="candidate/register.php" class="btn btn-outline">Candidate Registration</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Admin Role -->
    <div class="role-card">
        <div class="role-icon-wrapper">⚙️</div>
        <h2 class="role-title">Admin Module</h2>
        <p class="role-desc">Manage system databases, review/approve candidate requests, add or remove voter records, toggle voting periods, and declare winners.</p>
        <div class="role-actions">
            <?php if (isset($_SESSION['admin_id'])): ?>
                <a href="admin/dashboard.php" class="btn btn-primary">Go to Admin Panel</a>
            <?php else: ?>
                <a href="admin/login.php" class="btn btn-primary">Admin Login</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
