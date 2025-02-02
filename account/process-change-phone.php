<?php

session_start();
header('Content-Type: application/json'); // Set JSON response header

require_once '../include/config.php';
require_once '../include/db.php';



if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    /*$new_email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);

    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) 
    {
        $response["message"] = "Invalid email format.";
    } 
    else 
    {
        // Assume email update logic here...
        $response["success"] = true;
        $response["message"] = "Email updated successfully.";
    }*/

    $response = ["success" => true, "message" => "new phone"];
    echo json_encode($response);
}

exit;
?>