<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Ensure path_prefix is defined
if (!isset($path_prefix)) {
    $path_prefix = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " - College Voting" : "College Online Voting System"; ?></title>
    <!-- Google Fonts & Icons -->
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.css">
</head>
<body>
    <header>
        <div class="container nav-container">
            <div class="logo-section">
                <a href="<?php echo $path_prefix; ?>index.php">
                    <div class="logo-icon">V</div>
                    <span>College Vote</span>
                </a>
            </div>
            
            <button class="menu-toggle" aria-label="Toggle navigation">☰</button>
            
            <nav class="nav-menu">
                <a href="<?php echo $path_prefix; ?>index.php" class="nav-link <?php echo ($active_page ?? '') === 'home' ? 'active' : ''; ?>">Home</a>
                
                <?php if (isset($_SESSION['student_id'])): ?>
                    <!-- Student Nav Links -->
                    <a href="<?php echo $path_prefix; ?>student/dashboard.php" class="nav-link <?php echo ($active_page ?? '') === 'student_dashboard' ? 'active' : ''; ?>">Voting Panel</a>
                    <a href="<?php echo $path_prefix; ?>student/logout.php" class="nav-link nav-btn-logout">Logout (<?php echo htmlspecialchars($_SESSION['student_name']); ?>)</a>
                
                <?php elseif (isset($_SESSION['candidate_id'])): ?>
                    <!-- Candidate Nav Links -->
                    <a href="<?php echo $path_prefix; ?>candidate/dashboard.php" class="nav-link <?php echo ($active_page ?? '') === 'candidate_dashboard' ? 'active' : ''; ?>">Dashboard</a>
                    <a href="<?php echo $path_prefix; ?>candidate/logout.php" class="nav-link nav-btn-logout">Logout (<?php echo htmlspecialchars($_SESSION['candidate_name']); ?>)</a>
                
                <?php elseif (isset($_SESSION['admin_id'])): ?>
                    <!-- Admin Nav Links -->
                    <a href="<?php echo $path_prefix; ?>admin/dashboard.php" class="nav-link <?php echo ($active_page ?? '') === 'admin_dashboard' ? 'active' : ''; ?>">Admin Panel</a>
                    <a href="<?php echo $path_prefix; ?>admin/manage_students.php" class="nav-link <?php echo ($active_page ?? '') === 'manage_students' ? 'active' : ''; ?>">Voters</a>
                    <a href="<?php echo $path_prefix; ?>admin/manage_candidates.php" class="nav-link <?php echo ($active_page ?? '') === 'manage_candidates' ? 'active' : ''; ?>">Candidates</a>
                    <a href="<?php echo $path_prefix; ?>admin/logout.php" class="nav-link nav-btn-logout">Logout</a>
                
                <?php else: ?>
                    <!-- Guest Nav Links -->
                    <a href="<?php echo $path_prefix; ?>student/login.php" class="nav-link <?php echo ($active_page ?? '') === 'student_login' ? 'active' : ''; ?>">Student Login</a>
                    <a href="<?php echo $path_prefix; ?>candidate/login.php" class="nav-link <?php echo ($active_page ?? '') === 'candidate_login' ? 'active' : ''; ?>">Candidate Login</a>
                    <a href="<?php echo $path_prefix; ?>admin/login.php" class="nav-link <?php echo ($active_page ?? '') === 'admin_login' ? 'active' : ''; ?>">Admin</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>
    <main class="container">
