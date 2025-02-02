<?php

/**
 *This script resend email verification code after registration
 */

session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/email-sender.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Autoload PHPMailer

$fname = $email = $user_id = '';

if(isset($_SESSION['user_id']))  
{
    $user_id =  $_SESSION['user_id'];

    try 
    {
        $stmt = $pdo->prepare("SELECT first_name, email, email_verified, id FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) 
        {
            if ($user['email_verified'] == 1) 
            {
                // user has been varified
                $message_type = 
                    '<h3>Email Verified</h3>
                    <p>
                        Your email has been verified successfully. <a href="'. BASE_URL .'account/login.php" >
                        Login Here</a>
                    </p>';
                echo $message_type;
            }
            else
            {
                $fname = $user['first_name'];
                $email = $user['email'];
                $user_id = $user['id'];

                // Generate new token and expiry date
                $verificationToken = bin2hex(random_bytes(64)); // Secure token
                $expiry = time() + 60 * 5; // (5 minutes)

                try
                {
                    // Update users table with the new verification token and expiration
                    $stmt = $pdo->prepare("UPDATE users SET verification_token = :token, token_expiration = :expiry WHERE id = :id");
                    $stmt->bindParam(':token', $verificationToken, PDO::PARAM_STR);
                    $stmt->bindParam(':expiry', $expiry, PDO::PARAM_STR);
                    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
                    $stmt->execute();

                    // Prepare and send verification email
                    $verificationLink = BASE_URL . "account/verify-email.php?token=" . $verificationToken;

                    $subject = "Email verification";

                    $body = "
                        <p>Dear $fname,</p>
                        <p>
                            We received a request to resend your email verification link. Please click the
                            link below to verify your email address:
                        </p>
                        <p><a href='$verificationLink'>$verificationLink</a></p>
                        <p>The link will expire in next 15 minutes.</p>
                        <p>Thanks,</p>
                        <p>Your Website Team</p>";

                    $altBody = 'We received a request to resend your email verification link. Please click the link below to verify your email address:\n' . $verificationLink . '\n\n The link will expire in next 5 minutes. \n\nThanks.';

                    sendVerificationEmail($subject, $body, $altBody, $email, $fname);

                    $message_type = 
                        '<h3>Email resent</h3>
                        <p>
                            Verification token has been resend to your email, <strong>' . 
                            $email . '</strong>. Please check your inbox or spam folder to verify your email.
                        </p>';

                    $_SESSION['show_resend_button'] = true;
                    echo $message_type;
                }
                catch (PDOException $e) 
                {
                    handleError('An error occurred. Please try again later.', $e);
                    exit();
                }
            }
        }
        else 
        {
            handleError('User not found.');
            exit();
        }
    } 
    catch (PDOException $e) 
    {
        handleError('An error occurred. Please try again later.', $e);
        exit();
    }
} 
else 
{
    header("Location: " . BASE_URL . "index.php");
    exit();
}

?>
