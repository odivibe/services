<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';

if (isset($_GET['token'])) 
{
    try 
    {
        // Fetch user id and token expiration
        $stmt = $pdo->prepare("SELECT id, token_expiration FROM users WHERE verification_token = :token");
        $stmt->bindParam(':token', $token, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) 
        {
            // Token is invalid or not found
            $message_type = 
                '<h3>Invalid token</h3>
                <p>The verification token is invalid. Please check your link.</p>';
            $_SESSION['show_resend_button'] = false;
            $_SESSION['message_type'] = $message_type;
            header("Location: " . BASE_URL . "account/account-message-output.php");
            exit();
        } 

        $user_id = $user['id'];
        $tokenExpiration = $user['token_expiration'];
        $currentTimestamp = time();

        // Check if token is expired
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

        // update if token has not expired
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiration = NULL WHERE id = :id");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Set session message
        $message_type = 
            '<h3>Email Verified</h3>
            <p>
                Your email has been verified successfully. <a href="'. BASE_URL .'account/login.php" >
                Login Here</a>
            </p>';
        $_SESSION['show_resend_button'] = false;
        $_SESSION['message_type'] = $message_type;
        $_SESSION['user_id'] = $user_id;
        header("Location: " . BASE_URL . "account/account-message-output.php");
        exit();
    } 
    catch (PDOException $e) 
    {
        handleError('An error occurred. Please try again later.', $e);
        exit();
    }
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
