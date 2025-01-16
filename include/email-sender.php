<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Include the Composer autoloader

function sendVerificationEmail($email, $fname, $verificationLink) 
{
    $mail = new PHPMailer(true);

    try 
    {
        // SMTP server configuration
        $mail->isSMTP();
        $mail->Host = 'sandbox.smtp.mailtrap.io';
        $mail->SMTPAuth = true;
        $mail->Username = '670c70f26f6810';
        $mail->Password = 'd4b68560895d65'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 465;

        // Email settings
        $mail->setFrom('no-reply@yourdomain.com', 'Your Website');
        $mail->addAddress($email, $fname);
        $mail->Subject = 'Verify Your Email Address';
        $mail->isHTML(true);
        $mail->Body = "
            <p>Dear $fname,</p>
            <p>Please click the link below to verify your email address:</p>
            <p><a href='$verificationLink'>$verificationLink</a></p>
            <p>The link will expire in 15 minutes.</p>
            <p>Thanks,</p>
            <p>Your Website Team</p>";
        $mail->AltBody = "Please click the link below to verify your email address: $verificationLink. The link will expire in 15 minutes.";

        $mail->send();
    } 
    catch (Exception $e) 
    {
        throw new Exception('Could not send email: ' . $mail->ErrorInfo);
    }
}

?>