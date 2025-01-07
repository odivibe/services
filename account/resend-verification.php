<?php
// This script resend email confirmation code after registration

session_start();
require_once '../include/config.php';
require_once '../include/db.php';

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Autoload PHPMailer

if(isset($_SESSION['user_id'])) 
{
    $user_id =  $_SESSION['user_id'];

    // Fetch user details (firstname and email) from the database
    try 
    {
        $stmt = $pdo->prepare("SELECT first_name, email, email_verified FROM users WHERE id = :id LIMIT 1");
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) 
        {
            $firstname = $user['first_name'];
            $email = $user['email'];
            $email_verified = $user['email_verified'];

            if ($email_verified == 1) 
            {
                // Redirect to login
                header("Location:" . BASE_URL . "account/login.php");
                exit();
            }
        } 
        else 
        {
            $_SESSION['error'] = "User not found.";
            header("Location: " . BASE_URL . "errors/error-message.php");
            exit();
        }
    } 
    catch (PDOException $e) 
    {
        $message = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . PHP_EOL;
        error_log($message, 3, '../errors/custom-error.log');
        $_SESSION['error'] = "An error occurred. Please try again later.";
        header("Location: " . BASE_URL . "errors/error-message.php");
        exit();
    }

    // Generate new token and expiry date
    $verificationToken = bin2hex(random_bytes(32)); // Secure token
    $expiry = time() + 60 * 5; // (5 minutes)

    try
    {
        // Update users table with the new verification token and expiration
        $stmt = $pdo->prepare("UPDATE users SET verification_token = :token, token_expiration = :expiry WHERE id = :id");
        $stmt->bindParam(':token', $verificationToken, PDO::PARAM_STR);
        $stmt->bindParam(':expiry', $expiry, PDO::PARAM_STR);
        $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
        $stmt->execute();

        // Prepare the verification link
        $verificationLink = BASE_URL . "account/verify-email.php?token=" . $verificationToken;

        $subject = "Verify Your Email Address";
        $body = "
            <p>Hi $firstname,</p>
            <p>We received a request to resend your email verification link.</p>
            <p>Please click the link below to verify your email:</p>
            <p><a href='" . $verificationLink . "'>" . $verificationLink . "</a></p>
            <p>If you didn't make this request, please ignore this email.</p>
            <p>Thanks.</p>";

        $mail = new PHPMailer(true);

        try 
        {
            // Server settings
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = '670c70f26f6810';
            $mail->Password = 'd4b68560895d65'; 
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            // Recipients
            $mail->setFrom('no-reply@yourdomain.com', 'Your Website');
            $mail->addAddress($email); // Send to the user's email
            $mail->Subject = $subject;
            $mail->isHTML(true);
            $mail->Body = $body;
            $mail->AltBody = 'We received a request to resend your email verification link. \n\n Please click the link below to verify your email address:\n' . $verificationLink . '\n\nBest regards,\nYour Website Team';
            $mail->send();

            $_SESSION['message_type'] = 'email-resent-link';

            // Redirect to success message
            header("Location:" . BASE_URL . "account/message-output-manager.php");
            exit();
        } 
        catch (Exception $e) 
        {
            $message = "[" . date('Y-m-d H:i:s') . "] Mailer Error: " . $mail->ErrorInfo . PHP_EOL;
            error_log($message, 3, '../errors/custom-error.log');
            $_SESSION['error'] = "Failed to send the email. Please try again later.";
            header("Location: " . BASE_URL . "errors/error-message.php");
            exit();
        }
    }
    catch (PDOException $e) 
    {
        $message = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . PHP_EOL;
        error_log($message, 3, '../errors/custom-error.log');
        $_SESSION['error'] = "An error occurred. Please try again later.";
        header("Location: " . BASE_URL . "errors/error-message.php");
        exit();
    }
} 
else 
{
    $_SESSION['error'] = "An error occurred. Please try again later.";
    header("Location: " . BASE_URL . "errors/error-message.php");
    exit();
}

?>
