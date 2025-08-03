<?php
// Start session
session_start();

// Destroy all session variables
session_unset();

// Destroy the session
session_destroy();

// Redirect to login page or homepage
header("Location: login.php");
exit;
?>
