<?php
session_start();

// Unset all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Delete the "Remember Me" cookie
setcookie('remember_user', '', time() - 3600, "/"); // Expire the cookie

// Redirect to login page
header("Location: ../login.php");
exit();
?>