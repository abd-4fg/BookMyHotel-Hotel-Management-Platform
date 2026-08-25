<?php
require_once 'config/database.php';

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to customer login page with message
header('Location: login.php');
exit;
?>