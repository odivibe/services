<?php

session_start();
require_once '../include/config.php';

// Destroy all session data
session_unset(); // Remove all session variables
session_destroy(); // Destroy the session

// Redirect to the login page or homepage
header("Location: " . BASE_URL . "account/login.php"); 
exit();

?>
