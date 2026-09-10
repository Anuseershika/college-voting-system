<?php
session_start();

// Unset student session values
unset($_SESSION['student_id']);
unset($_SESSION['student_db_id']);
unset($_SESSION['student_name']);
unset($_SESSION['student_email']);

// Redirect to Home Page
header('Location: ../index.php');
exit;
?>
