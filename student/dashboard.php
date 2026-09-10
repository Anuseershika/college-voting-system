<?php
$page_title = "Student Dashboard";
$active_page = "student_dashboard";
$path_prefix = "../";

include $path_prefix . 'config/db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Access Control: Verify student is logged in
if (!isset($_SESSION['student_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

try {
    // 1. Fetch current student status from DB (real-time check)
    $stmt = $pdo->prepare("SELECT * FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();
    
    if (!$student) {
        // Session exists but student deleted
        session_destroy();
        header('Location: login.php');
        exit;
    }
    
    // 2. Fetch Election Status
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'election_status'");
    $stmt->execute();
    $setting = $stmt->fetch();
    $election_status = $setting ? $setting['setting_value'] : 'voting';

    // 3. Fetch Approved Candidates
    $stmt = $pdo->prepare("SELECT * FROM candidate_applications WHERE status = 'Approved' ORDER BY position, candidate_name");
    $stmt->execute();
    $candidates = $stmt->fetchAll();

    // 4. If election ended, fetch results data
    $results = [];
    $total_votes = 0;
    $winner = null;
    
    if ($election_status === 'ended') {
        // Count votes per candidate
        $stmt = $pdo->prepare("
            SELECT c.id, c.candidate_name, c.department, c.position, c.photo, COUNT(v.id) as vote_count 
            FROM candidate_applications c 
            LEFT JOIN votes v ON c.id = v.candidate_id 
            WHERE c.status = 'Approved' 
            GROUP BY c.id 
            ORDER BY vote_count DESC
        ");
        $stmt->execute();
        $results = $stmt->fetchAll();

        // Calculate total votes
        foreach ($results as $row) {
            $total_votes += $row['vote_count'];
        }

        // Determine winner(s)
        if (count($results) > 0) {
            $max_votes = $results[0]['vote_count'];
            // In case of a tie, list candidates with max votes
            $winner_candidates = [];
            foreach ($results as $row) {
                if ($row['vote_count'] == $max_votes && $max_votes > 0) {
                    $winner_candidates[] = $row;
                }
            }
            $winner = $winner_candidates;
        }
    }

} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

include $path_prefix . 'includes/header.php';
?>

<!-- Info Alerts -->
<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<div class="dashboard-grid">
    <!-- Sidebar / Profile Details -->
    <div class="dashboard-sidebar">
        <h3 class="sidebar-title">Voter Profile</h3>
        <div style="margin-bottom: 20px;">
            <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">FULL NAME</p>
            <p style="font-weight: 600; font-size: 1.05rem;"><?php echo htmlspecialchars($student['name']); ?></p>
        </div>
        <div style="margin-bottom: 20px;">
            <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">STUDENT ID</p>
            <p style="font-weight: 600; font-size: 1.05rem;"><?php echo htmlspecialchars($student['student_id']); ?></p>
        </div>
        <div style="margin-bottom: 20px;">
            <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">EMAIL</p>
            <p style="font-weight: 600; font-size: 0.95rem; overflow-wrap: break-word;"><?php echo htmlspecialchars($student['email']); ?></p>
        </div>
        <div style="margin-bottom: 20px;">
            <p style="font-size: 0.85rem; color: var(--text-muted); font-weight: 500;">VOTING STATUS</p>
            <?php if ($student['has_voted']): ?>
                <span class="badge badge-approved" style="margin-top: 4px;">Voted</span>
            <?php else: ?>
                <span class="badge badge-pending" style="margin-top: 4px;">Not Voted</span>
            <?php endif; ?>
        </div>
        
        <div style="margin-top: 40px; border-top: 1px solid var(--border); padding-top: 20px;">
            <a href="logout.php" class="btn btn-outline btn-sm">Log Out</a>
        </div>
    </div>

    <!-- Main Content Panel -->
    <div class="dashboard-content">
        
        <!-- Header Page Title -->
        <div class="page-header">
            <div class="page-title">
                <h1>Student Voting Center</h1>
                <p>Welcome, <?php echo htmlspecialchars($student['name']); ?>. Manage your vote here.</p>
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

        <!-- Dynamic Display depending on Election Phase -->
        <?php if ($election_status === 'setup'): ?>
            <!-- Setup / Pre-voting Mode -->
            <div class="form-card" style="max-width: 100%; margin: 0; text-align: center;">
                <div class="role-icon-wrapper" style="margin: 0 auto 20px auto; width: 80px; height: 80px; font-size: 2.5rem;">⏳</div>
                <h2 style="color: var(--primary); font-weight: 700; margin-bottom: 12px;">Polls Are Not Open Yet</h2>
                <p style="color: var(--text-muted); max-width: 600px; margin: 0 auto 20px auto; font-size: 1.05rem;">
                    The election is currently in the configuration and candidate application phase. Please check back once the administration officially opens the voting session.
                </p>
            </div>

        <?php elseif ($election_status === 'voting'): ?>
            <!-- Voting Active Mode -->
            <?php if ($student['has_voted']): ?>
                <!-- Already Voted confirmation screen -->
                <div class="form-card" style="max-width: 100%; margin: 0; text-align: center; border: 2px solid var(--success);">
                    <div class="role-icon-wrapper" style="margin: 0 auto 20px auto; width: 80px; height: 80px; font-size: 2.5rem; background-color: var(--success-light); color: var(--success);">✓</div>
                    <h2 style="color: var(--success); font-weight: 700; margin-bottom: 12px;">Vote Confirmed!</h2>
                    <p style="color: var(--text-main); font-weight: 500; font-size: 1.1rem; margin-bottom: 8px;">Your ballot has been successfully and securely submitted.</p>
                    <p style="color: var(--text-muted); max-width: 500px; margin: 0 auto; font-size: 0.95rem;">
                        You have completed your voting requirement. To ensure integrity and fairness, you are restricted from casting multiple votes. Election results will be published when the polls close.
                    </p>
                </div>
            <?php else: ?>
                <!-- Voting ballot card list -->
                <div class="page-title">
                    <h2 style="font-size: 1.4rem; color: var(--primary); font-weight: 700; margin-bottom: 10px;">Select Your Candidate</h2>
                    <p style="font-size: 0.95rem; color: var(--text-muted);">Review candidates below. You can only vote for **one** candidate across all categories.</p>
                </div>
                
                <?php if (count($candidates) === 0): ?>
                    <div class="alert alert-warning">No candidates have been approved to run in this election yet.</div>
                <?php else: ?>
                    <div class="cards-grid">
                        <?php foreach ($candidates as $cand): ?>
                            <div class="candidate-card">
                                <div class="candidate-photo-wrapper">
                                    <span class="candidate-position-badge"><?php echo htmlspecialchars($cand['position']); ?></span>
                                    <?php 
                                        $photo_path = $path_prefix . "assets/images/uploads/" . $cand['photo'];
                                        // If file doesn't exist, check assets/images
                                        if (!file_exists($photo_path) || empty($cand['photo'])) {
                                            $photo_path = $path_prefix . "assets/images/" . $cand['photo'];
                                        }
                                        // Fallback to default svg if still missing
                                        if (!file_exists($photo_path) || empty($cand['photo'])) {
                                            $photo_path = $path_prefix . "assets/images/default_candidate.svg";
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($photo_path); ?>" alt="<?php echo htmlspecialchars($cand['candidate_name']); ?>" class="candidate-photo">
                                </div>
                                <div class="candidate-body">
                                    <h3 class="candidate-name"><?php echo htmlspecialchars($cand['candidate_name']); ?></h3>
                                    <div class="candidate-dept">📁 Department of <?php echo htmlspecialchars($cand['department']); ?></div>
                                    
                                    <div class="candidate-manifesto">
                                        <strong>Manifesto / Profile:</strong>
                                        <?php 
                                            $manifesto_clean = htmlspecialchars($cand['manifesto']);
                                            $short_manifesto = strlen($manifesto_clean) > 120 ? substr($manifesto_clean, 0, 117) . '...' : $manifesto_clean;
                                        ?>
                                        <p class="candidate-manifesto-text" 
                                           data-full-manifesto="<?php echo $manifesto_clean; ?>" 
                                           data-short-manifesto="<?php echo $short_manifesto; ?>">
                                            <?php echo $short_manifesto; ?>
                                        </p>
                                        <?php if (strlen($manifesto_clean) > 120): ?>
                                            <button type="button" class="btn btn-outline btn-sm toggle-manifesto-btn" style="margin-top: 10px; padding: 4px 8px; font-size: 0.8rem; width: auto;" data-expanded="false">Read Manifesto</button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="candidate-actions">
                                        <form action="vote.php" method="POST" class="vote-form" data-candidate="<?php echo htmlspecialchars($cand['candidate_name']); ?>">
                                            <input type="hidden" name="candidate_id" value="<?php echo $cand['id']; ?>">
                                            <button type="submit" class="btn btn-primary">Cast Vote</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        <?php elseif ($election_status === 'ended'): ?>
            <!-- Election Concluded / Results Mode -->
            
            <!-- Winner Highlight Cards -->
            <?php if ($winner && count($winner) > 0): ?>
                <?php foreach ($winner as $w): ?>
                    <div class="winner-card">
                        <h2>🏆 Election Winner</h2>
                        <h3 style="font-size: 1.7rem; font-weight: 700; margin-bottom: 6px;"><?php echo htmlspecialchars($w['candidate_name']); ?></h3>
                        <p style="font-size: 1.05rem;">
                            Position: <?php echo htmlspecialchars($w['position']); ?> | Department: <?php echo htmlspecialchars($w['department']); ?>
                        </p>
                        <div style="font-size: 1.5rem; font-weight: 800; margin-top: 10px; background-color: rgba(255,255,255,0.2); display: inline-block; padding: 6px 20px; border-radius: 8px;">
                            <?php echo $w['vote_count']; ?> Votes (<?php echo $total_votes > 0 ? round(($w['vote_count'] / $total_votes) * 100, 1) : 0; ?>%)
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="winner-card" style="background: linear-gradient(135deg, var(--text-muted), var(--border)); color: white;">
                    <h2>No Votes Recorded</h2>
                    <p>No votes were submitted during the election period.</p>
                </div>
            <?php endif; ?>

            <!-- Interactive Chart Panel -->
            <?php if ($total_votes > 0): ?>
                <div class="charts-card">
                    <h3 style="font-size: 1.25rem; font-weight: 700; color: var(--primary); margin-bottom: 20px; text-align: center;">Graphical Vote Distribution</h3>
                    <div class="chart-container">
                        <canvas id="resultsChart"></canvas>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Table of Results -->
            <div class="page-title" style="margin-top: 20px;">
                <h2 style="font-size: 1.4rem; color: var(--primary); font-weight: 700; margin-bottom: 10px;">Election Poll Breakdown</h2>
            </div>
            
            <div class="table-responsive">
                <table class="table-premium">
                    <thead>
                        <tr>
                            <th>Candidate</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Vote Count</th>
                            <th>Percentage</th>
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

            <!-- Import Chart.js if election ended and votes cast -->
            <?php if ($total_votes > 0): ?>
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        const ctx = document.getElementById('resultsChart').getContext('2d');
                        
                        // Populate labels and data dynamically from PHP
                        const candidateNames = <?php echo json_encode(array_column($results, 'candidate_name')); ?>;
                        const voteCounts = <?php echo json_encode(array_column($results, 'vote_count')); ?>;
                        
                        new Chart(ctx, {
                            type: 'bar',
                            data: {
                                labels: candidateNames,
                                datasets: [{
                                    label: 'Votes Received',
                                    data: voteCounts,
                                    backgroundColor: 'rgba(30, 58, 138, 0.75)', // Primary color opacity
                                    borderColor: 'rgba(30, 58, 138, 1)',
                                    borderWidth: 2,
                                    borderRadius: 6,
                                    barPercentage: 0.6
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: {
                                    legend: {
                                        display: false
                                    },
                                    tooltip: {
                                        padding: 12,
                                        cornerRadius: 8,
                                        titleFont: { size: 14, weight: 'bold', family: 'Outfit' },
                                        bodyFont: { size: 13, family: 'Outfit' }
                                    }
                                },
                                scales: {
                                    y: {
                                        beginAtZero: true,
                                        ticks: {
                                            stepSize: 1,
                                            font: { family: 'Outfit', size: 12 }
                                        },
                                        grid: {
                                            color: '#e2e8f0'
                                        }
                                    },
                                    x: {
                                        ticks: {
                                            font: { family: 'Outfit', size: 12, weight: 'bold' }
                                        },
                                        grid: {
                                            display: false
                                        }
                                    }
                                }
                            }
                        });
                    });
                </script>
            <?php endif; ?>

        <?php endif; ?>

    </div>
</div>

<?php include $path_prefix . 'includes/footer.php'; ?>
