<?php
session_start();

// Set guest session
$_SESSION['user'] = 'Guest';
$_SESSION['email'] = 'guest@cafe4.com';
$_SESSION['is_guest'] = true;

// Redirect to home page
header("Location: home.php");
exit();
?>
