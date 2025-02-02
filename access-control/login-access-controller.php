<?php 

// Redirect if user has already logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['has_logged_in'])) 
{
    header("Location: " . BASE_URL . "index.php");
    exit();
}

?>