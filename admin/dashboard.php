<?php
$page_title = "Admin Dashboard";
$active_page = "admin_dashboard";
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

// 1. Handle Administrative Actions (POST requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Toggle Election Phase
    if (isset($_POST['action']) && $_POST['action'] === 'toggle_phase') {
        $new_phase = $_POST['election_phase'] ?? '';
        $valid_phases = ['setup', 'voting', 'ended'];
        
        if (in_array($new_phase, $valid_phases)) {
            try {
                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = 'election_status'");
                $stmt->execute([$new_phase]);
                $success = "Election phase updated to: " . strtoupper($new_phase);
            } catch (PDOException $e) {
                $error = "Failed to update phase: " . $e->getMessage();
            }
        } else {
            $error = "Invalid phase selected.";
        }
    }
    
    // Approve Candidate Nomination
    if (isset($_POST['action']) && $_POST['action'] === 'approve_candidate') {
        $cand_id = intval($_POST['candidate_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE candidate_applications SET status = 'Approved' WHERE id = ?");
            if ($stmt->execute([$cand_id])) {
                $success = "Candidate nomination approved successfully.";
            } else {
                $error = "Failed to approve candidate nomination.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }

    // Reject Candidate Nomination
    if (isset($_POST['action']) && $_POST['action'] === 'reject_candidate') {
        $cand_id = intval($_POST['candidate_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("UPDATE candidate_applications SET status = 'Rejected' WHERE id = ?");
            if ($stmt->execute([$cand_id])) {
                $success = "Candidate nomination rejected successfully.";
            } else {
                $error = "Failed to reject candidate nomination.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

try {
    // 2. Fetch Dashboard Statistics
    // Total Students
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM students");
    $total_students = $stmt->fetch()['cnt'];

    // Total Votes Cast
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM votes");
    $total_votes = $stmt->fetch()['cnt'];

    // Total Candidate Applications
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM candidate_applications");
    $total_candidates = $stmt->fetch()['cnt'];

    // Approved Candidates
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM candidate_applications WHERE status = 'Approved'");
    $approved_candidates = $stmt->fetch()['cnt'];

    // 3. Fetch Pending Candidate Applications
    $stmt = $pdo->query("SELECT * FROM candidate_applications WHERE status = 'Pending' ORDER BY id DESC");
    $pending_candidates = $stmt->fetchAll();

    // 4. Fetch Current Election Status
    $stmt = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'election_status'");
    $setting = $stmt->fetch();
    $election_status = $setting ? $setting['setting_value'] : 'setup';

    // 5. Fetch Results for ending display
    $results = [];
    $winner = null;
    if ($election_status === 'ended') {
        $stmt = $pdo->query("
            SELECT c.id, c.candidate_name, c.department, c.position, COUNT(v.id) as vote_count 
            FROM candidate_applications c 
            LEFT JOIN votes v ON c.id = v.candidate_id 
            WHERE c.status = 'Approved' 
            GROUP BY c.id 
            ORDER BY vote_count DESC
        ");
        $results = $stmt->fetchAll();
        
        if (count($results) > 0) {
            $max_votes = $results[0]['vote_count'];
            $winner_list = [];
            foreach ($results as $r) {
                if ($r['vote_count'] == $max_votes && $max_votes > 0) {
                    $winner_list[] = $r;
                }
            }
            $winner = $winner_list;
        }
    }

} catch (PDOException $e) {
    $error = "Database fetch failure: " . $e->getMessage();
}

include $path_prefix . 'includes/header.php';
?>

<!-- Info Messages -->
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
            <li><a href="dashboard.php" class="sidebar-link active">Admin Dashboard</a></li>
            <li><a href="manage_students.php" class="sidebar-link">Manage Student Voters</a></li>
            <li><a href="manage_candidates.php" class="sidebar-link">Manage Candidates</a></li>
            <li><a href="../index.php" class="sidebar-link">Public Home</a></li>
        </ul>
        
        <div style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px;">
            <a href="logout.php" class="btn btn-outline btn-sm">Log Out</a>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="dashboard-content">
        <!-- Header -->
        <div class="page-header">
            <div class="page-title">
                <h1>Admin Command Center</h1>
                <p>Monitor participation, handle candidate applications, and govern the election cycle.</p>
            </div>
            <div>
                <?php if ($election_status === 'setup'): ?>
                    <span class="status-badge setup"><span class="pulse-dot"></span> Registration Phase</span>
                <?php elseif ($election_status === 'voting'): ?>
                    <span class="status-badge voting"><span class="pulse-dot"></span> Voting Ongoing</span>
                <?php elseif ($election_status === 'ended'): ?>
                    <span class="status-badge ended">Election Concluded</span>
                <?php endif; ?>
            </div>
        </div>

        <!-- Statistics Badges Panel -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">🎓</div>
                <div class="stat-info">
                    <span class="stat-val"><?php echo $total_students; ?></span>
                    <span class="stat-lbl">Registered Voters</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--success-light); color: var(--success);">🗳</div>
                <div class="stat-info">
                    <span class="stat-val"><?php echo $total_votes; ?></span>
                    <span class="stat-lbl">Votes Cast (<?php echo $total_students > 0 ? round(($total_votes / $total_students) * 100, 1) : 0; ?>%)</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--warning-light); color: var(--warning);">🏆</div>
                <div class="stat-info">
                    <span class="stat-val"><?php echo $total_candidates; ?></span>
                    <span class="stat-lbl">Total Applicants</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background-color: var(--primary-light); color: white;">✓</div>
                <div class="stat-info">
                    <span class="stat-val"><?php echo $approved_candidates; ?></span>
                    <span class="stat-lbl">Running Candidates</span>
                </div>
            </div>
        </div>

        <!-- System Governance & Phase Controls Card -->
        <div class="form-card" style="max-width: 100%; margin: 0; padding: 30px;">
            <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid var(--bg-alt); padding-bottom: 10px;">Election Phase Governance</h2>
            <p style="font-size: 0.95rem; color: var(--text-muted); margin-bottom: 24px;">Alter the system status to open voting, restrict candidate submissions, or display final charts & results publicly.</p>
            
            <form action="dashboard.php" method="POST" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
                <input type="hidden" name="action" value="toggle_phase">
                <div style="flex-grow: 1; min-width: 250px;">
                    <label for="election_phase" class="form-label">Select Active Phase</label>
                    <select id="election_phase" name="election_phase" class="form-control" style="background-color: white;">
                        <option value="setup" <?php echo $election_status === 'setup' ? 'selected' : ''; ?>>Setup & Registration Phase (Registration open, Polls closed)</option>
                        <option value="voting" <?php echo $election_status === 'voting' ? 'selected' : ''; ?>>Voting Phase (Registration open, Polls open, Results hidden)</option>
                        <option value="ended" <?php echo $election_status === 'ended' ? 'selected' : ''; ?>>Election Concluded (Polls closed, Results unlocked & Winner declared)</option>
                    </select>
                </div>
                <div style="width: auto;">
                    <button type="submit" class="btn btn-secondary btn-inline" style="padding: 12px 30px;">Update System Phase</button>
                </div>
            </form>
        </div>

        <!-- Pending Candidate Applications Section -->
        <div class="form-card" style="max-width: 100%; margin: 0; padding: 30px;">
            <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid var(--bg-alt); padding-bottom: 10px;">Pending Nomination Applications (<?php echo count($pending_candidates); ?>)</h2>
            
            <?php if (count($pending_candidates) === 0): ?>
                <p style="color: var(--text-muted); font-size: 0.95rem;">No candidate applications require evaluation at this moment.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-premium">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Student ID</th>
                                <th>Department</th>
                                <th>Position</th>
                                <th>Manifesto</th>
                                <th>Evaluation</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_candidates as $cand): ?>
                                <tr>
                                    <td>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <?php 
                                                $p_path = $path_prefix . "assets/images/uploads/" . $cand['photo'];
                                                if (!file_exists($p_path) || empty($cand['photo'])) {
                                                    $p_path = $path_prefix . "assets/images/" . $cand['photo'];
                                                }
                                                if (!file_exists($p_path) || empty($cand['photo'])) {
                                                    $p_path = $path_prefix . "assets/images/default_candidate.svg";
                                                }
                                            ?>
                                            <img src="<?php echo htmlspecialchars($p_path); ?>" alt="" style="width: 38px; height: 38px; object-fit: cover; border-radius: 50%; border: 1px solid var(--border);">
                                            <div>
                                                <strong style="display: block;"><?php echo htmlspecialchars($cand['candidate_name']); ?></strong>
                                                <span style="font-size: 0.8rem; color: var(--text-muted);"><?php echo htmlspecialchars($cand['email']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code><?php echo htmlspecialchars($cand['student_id']); ?></code></td>
                                    <td><?php echo htmlspecialchars($cand['department']); ?></td>
                                    <td><span class="badge badge-pending" style="background-color: var(--primary); color: white; border: none; font-size: 0.75rem;"><?php echo htmlspecialchars($cand['position']); ?></span></td>
                                    <td>
                                        <div style="max-width: 200px; font-size: 0.85rem; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo htmlspecialchars($cand['manifesto']); ?>">
                                            <?php echo htmlspecialchars($cand['manifesto']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="table-actions">
                                            <!-- Approve Form -->
                                            <form action="dashboard.php" method="POST" style="margin: 0;">
                                                <input type="hidden" name="action" value="approve_candidate">
                                                <input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
                                                <button type="submit" class="btn btn-primary btn-sm btn-inline" style="background-color: var(--success); border: none;">Approve</button>
                                            </form>
                                            <!-- Reject Form -->
                                            <form action="dashboard.php" method="POST" style="margin: 0;">
                                                <input type="hidden" name="action" value="reject_candidate">
                                                <input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm btn-inline" style="border: none;">Reject</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <!-- Concluded Election Results Card (if ended) -->
        <?php if ($election_status === 'ended'): ?>
            <div class="form-card" style="max-width: 100%; margin: 0; padding: 30px;">
                <h2 style="font-size: 1.35rem; color: var(--primary); font-weight: 700; margin-bottom: 20px; border-bottom: 2px solid var(--bg-alt); padding-bottom: 10px;">Election Concluded - Results</h2>
                
                <?php if ($winner && count($winner) > 0): ?>
                    <div class="winner-card" style="margin-bottom: 30px;">
                        <h2>🏆 System Declared Winner</h2>
                        <?php foreach ($winner as $w): ?>
                            <h3 style="font-size: 1.6rem; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($w['candidate_name']); ?></h3>
                            <p style="font-size: 1rem; margin-bottom: 8px;">Position: <?php echo htmlspecialchars($w['position']); ?> | Department: <?php echo htmlspecialchars($w['department']); ?></p>
                        <?php endforeach; ?>
                        <div style="font-size: 1.25rem; font-weight: 700; background-color: rgba(255,255,255,0.25); display: inline-block; padding: 4px 16px; border-radius: 6px;">
                            Winner Count: <?php echo $winner[0]['vote_count']; ?> Votes
                        </div>
                    </div>

                    <?php if ($total_votes > 0): ?>
                        <div class="charts-card" style="margin-bottom: 30px; border: 1px solid var(--border);">
                            <h3 style="font-size: 1.1rem; font-weight: 700; color: var(--primary); margin-bottom: 16px; text-align: center;">Voter Share Graphs</h3>
                            <div class="chart-container">
                                <canvas id="adminResultsChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Chart scripts -->
                        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const ctx = document.getElementById('adminResultsChart').getContext('2d');
                                const candidateNames = <?php echo json_encode(array_column($results, 'candidate_name')); ?>;
                                const voteCounts = <?php echo json_encode(array_column($results, 'vote_count')); ?>;
                                
                                new Chart(ctx, {
                                    type: 'doughnut',
                                    data: {
                                        labels: candidateNames,
                                        datasets: [{
                                            data: voteCounts,
                                            backgroundColor: [
                                                '#1e3a8a', '#d97706', '#10b981', '#ef4444', 
                                                '#8b5cf6', '#ec4899', '#3b82f6', '#f59e0b'
                                            ],
                                            borderWidth: 2,
                                            borderColor: '#ffffff'
                                        }]
                                    },
                                    options: {
                                        responsive: true,
                                        maintainAspectRatio: false,
                                        plugins: {
                                            legend: {
                                                position: 'right',
                                                labels: { font: { family: 'Outfit', size: 12 } }
                                            }
                                        }
                                    }
                                });
                            });
                        </script>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table-premium">
                            <thead>
                                <tr>
                                    <th>Candidate</th>
                                    <th>Department</th>
                                    <th>Position</th>
                                    <th>Vote Count</th>
                                    <th>Percentage Share</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $row): ?>
                                    <tr>
                                        <td style="font-weight: 600;"><?php echo htmlspecialchars($row['candidate_name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['department']); ?></td>
                                        <td><span class="badge badge-approved" style="background-color: var(--primary-hover); color: white;"><?php echo htmlspecialchars($row['position']); ?></span></td>
                                        <td style="font-weight: 700; font-size: 1.1rem; color: var(--primary);"><?php echo $row['vote_count']; ?></td>
                                        <td>
                                            <?php 
                                                $pct = $total_votes > 0 ? round(($row['vote_count'] / $total_votes) * 100, 1) : 0;
                                                echo $pct . '%';
                                            ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p style="color: var(--text-muted); font-size: 0.95rem;">No candidate entries are available to construct results.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
