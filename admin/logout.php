<?php
session_start();

// Unset admin session variables
unset($_SESSION['admin_id']);
unset($_SESSION['admin_db_id']);

// Redirect to Home Page
header('Location: ../index.php');
exit;
?>
