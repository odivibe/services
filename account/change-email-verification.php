<?php

session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';

if (isset($_GET['token'])) 
{
    $token = $_GET['token'];
    $pending = 'pending';

    // Check if token exists and is still valid
    $stmt = $pdo->prepare("SELECT * FROM email_change_requests WHERE verification_token = :verification_token");
    $stmt->bindParam(':verification_token', $token, PDO::PARAM_STR);
    $stmt->execute();
    $request = $stmt->fetch();

    if (!$request) 
    {
        // Token is invalid or not found
        $message_type = 
            '<h3>Invalid token</h3>
            <p>The verification token is invalid. Please check your link.</p>';
        $_SESSION['message_type'] = $message_type;
        header("Location: " . BASE_URL . "account/account-message-output.php");
        exit();
    }

    $user_id = $request['user_id'];
    $new_email = $request['new_email'];
    $tokenExpiration = $request['token_expiration'];
    $currentTimestamp = time();

    // Check if token has expired
    if ($currentTimestamp > $tokenExpiration) 
    {
        // Token has expired
        $message_type = 
            '<h3>Token has expired</h3>
            <p>The verification link has expired.</p>';

        $_SESSION['show_resend_button'] = true;

        $_SESSION['message_type'] = $message_type;

        $_SESSION['user_id'] = $user_id;

        header("Location: " . BASE_URL . "account/account-message-output.php");

        exit();
    }

    // Change email to new email in user table
    $stmt = $pdo->prepare("UPDATE users SET email = :email WHERE id = :user_id");
    $stmt->bindParam(':email', $new_email, PDO::PARAM_STR);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Remove token and expiration time from email_change_requests table
    $query = "UPDATE email_change_requests SET verification_token = NULL, token_expiration = NULL WHERE verification_token = :verification_token";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':verification_token', $token, PDO::PARAM_STR);
    $stmt->execute();

    $message_type = "Your email has been changed!";

    //header("Location: " . BASE_URL . "account/account-message-output.php");
    //exit();
}
else 
{
    // Token is invalid or not found
    $message_type = 
        '<h3>Invalid token</h3>
        <p>The verification token is invalid. Please check your link.</p>';

    $_SESSION['message_type'] = $message_type;
    
    header("Location: " . BASE_URL . "account/account-message-output.php");
    exit();
}


?>