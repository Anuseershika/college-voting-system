<?php
/**
 * Secure Vote Submission Handler
 * Uses transactions to insert vote and update student status.
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$path_prefix = "../";
include $path_prefix . 'config/db.php';

// 1. Access Control: Verify student session
if (!isset($_SESSION['student_id']) || !isset($_SESSION['student_db_id'])) {
    header('Location: login.php');
    exit;
}

$student_id = $_SESSION['student_id'];

// 2. Validate Request Type
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: dashboard.php');
    exit;
}

$candidate_id = intval($_POST['candidate_id'] ?? 0);

if ($candidate_id <= 0) {
    header('Location: dashboard.php?error=Invalid candidate selected.');
    exit;
}

try {
    // 3. Verify Election Status
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'election_status'");
    $stmt->execute();
    $setting = $stmt->fetch();
    $election_status = $setting ? $setting['setting_value'] : '';

    if ($election_status !== 'voting') {
        header('Location: dashboard.php?error=Voting is currently closed.');
        exit;
    }

    // 4. Double Check Student Eligibility (has_voted status)
    $stmt = $pdo->prepare("SELECT has_voted FROM students WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student || $student['has_voted'] == 1) {
        header('Location: dashboard.php?error=You have already cast your vote.');
        exit;
    }

    // 5. Verify Candidate is Approved
    $stmt = $pdo->prepare("SELECT id FROM candidate_applications WHERE id = ? AND status = 'Approved'");
    $stmt->execute([$candidate_id]);
    $cand = $stmt->fetch();

    if (!$cand) {
        header('Location: dashboard.php?error=The selected candidate is not valid or approved.');
        exit;
    }

    // 6. Execute Atomic Vote Transaction
    $pdo->beginTransaction();

    // Insert vote record (database unique constraint on student_id will block duplicates as well)
    $vote_stmt = $pdo->prepare("INSERT INTO votes (student_id, candidate_id) VALUES (?, ?)");
    $vote_stmt->execute([$student_id, $candidate_id]);

    // Update has_voted status in student table
    $update_stmt = $pdo->prepare("UPDATE students SET has_voted = 1 WHERE student_id = ?");
    $update_stmt->execute([$student_id]);

    // Commit Transaction
    $pdo->commit();

    header('Location: dashboard.php?success=Your vote was securely cast!');
    exit;

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Check if error is due to double voting constraint violation
    if ($e->getCode() == 23000) {
        header('Location: dashboard.php?error=Duplicate vote detected. You cannot vote twice.');
    } else {
        header('Location: dashboard.php?error=Vote failed: ' . $e->getMessage());
    }
    exit;
}
?>
