<?php
session_start();

// Unset candidate session variables
unset($_SESSION['candidate_id']);
unset($_SESSION['candidate_db_id']);
unset($_SESSION['candidate_name']);
unset($_SESSION['candidate_email']);

// Redirect to Home Page
header('Location: ../index.php');
exit;
?>
