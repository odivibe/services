<?php
// Include necessary files (e.g., config for DB connection)
include_once '../include/config.php';
include_once '../include/db.php';

// Initialize variables
$email = "";
$error_message = "";
$success_message = "";

// Form handling logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['subscribe'])) 
{
    // Clean and validate the email address
    $email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
    {
        $error_message = "Please enter a valid email address.";
    } 
    else 
    {
        // Prepare SQL query with a placeholder for email
        $sql = "INSERT INTO newsletter_subscribers, email = :email";
        $stmt = $pdo->prepare($sql);

        // Bind the email parameter to the placeholder
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);

        // Execute the statement
        if ($stmt->execute()) 
        {
            $success_message = "Thank you for subscribing to our newsletter!";
            $email = "";  // Clear the email field
        } 
        else 
        {
            $error_message = "There was an error subscribing. Please try again.";
        }
    }
}
?>


