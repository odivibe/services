<?php
session_start();

require_once '../include/config.php';

// Default values
$title = 'Notice';
$message = 'An unexpected error occurred.';

$messageType = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : '';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '';
$email = isset($_SESSION['user_email']) ? $_SESSION['user_email'] : '';

// Handle messages using switch-case
switch ($messageType) 
{
    case 'email-sent': 
        $title = 'Email Sent';
        $message = 'We have sent a verification link to <strong>' . 
            $email . '</strong>. Please check your inbox or spam folder to verify your email.';
        break;

    case 'email-verified':
        $title = 'Email Verified';
        $message = 'Your email has been verified successfully. <a href="'. BASE_URL .'account/login.php" >Login Here</a>';
        break;

    case 'expired-token':
        $title = 'Verification Failed';
        $message = 'The verification link has expired.';
        break;

    case 'invalid-token':
        $title = 'Invalid Token';
        $message = 'The verification token is invalid. Please check your link.';
        break;

    case 'email-resent-link':
        $title = 'Email link resend';
        $message = 'Verification token has been resend to your email, <strong>' . 
            $email . '</strong>. Please check your inbox or spam folder to verify your email.';
        break;

    default:
        $title = 'Notice';
        $message = 'An unexpected error occurred.';
        break;
}

?>

<?php require_once '../include/header.php'; ?>
<div class="message-container">
    <div class="msg-info" role="alert">
        <h4><?= $title ?></h4>
        <p><?= $message ?></p>
        <hr>
        
        <!-- For resending verification link -->
        <?php if ($messageType === 'expired-token' || $messageType === 'email-sent' || $messageType === 'email-resent-link'): ?>
            <p>
                Resend Verification Below.
            </p>
            <form method="POST" action="resend-verification.php">
                <button type="submit" class="resend-link" name="resend-verification-code">
                    Resend Verification Email
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php

// Clear session variables after displaying the message
//unset($_SESSION['message_type']);
//unset($_SESSION['user_email']);

require_once '../include/footer.php';
?>
