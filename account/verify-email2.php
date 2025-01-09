<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';

if (isset($_GET['token'])) 
{
    $token = $_GET['token'];

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
            $_SESSION['message_type'] = 'invalid-token';
            header("Location: " . BASE_URL . "account/message-output-manager.php");
            exit();
        } 

        // Token is valid, update email_verified status
        $user_id = $user['id'];
        $tokenExpiration = $user['token_expiration'];
        $currentTimestamp = time();

        // Check if token is expired
        if ($currentTimestamp > $tokenExpiration) 
        {
            // Token has expired
            $_SESSION['message_type'] = 'expired-token';
            $_SESSION['user_id'] = $user_id;
            header("Location: " . BASE_URL . "account/message-output-manager.php");
            exit();
        }

        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL, token_expiration = NULL WHERE id = :id");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Set session message
        $_SESSION['message_type'] = 'email-verified';
        $_SESSION['user_id'] = $user_id;
        header("Location: " . BASE_URL . "account/message-output-manager.php");
        exit();
    } 
    catch (PDOException $e) 
    {
        $message = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . PHP_EOL;

        error_log($message, 3, '../errors/custom-error.log');

        // emailAdmin($message);  // Notify admin

        http_response_code(500);

        require_once '../include/header.php';
        require_once '../include/500.php';
        require_once '../include/footer.php';

        exit();
    }
} 
else 
{
    $_SESSION['error'] = "Invalid token.";
    header("Location: " . BASE_URL . "errors/error-message.php");
    exit();
}

?>
