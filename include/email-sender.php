<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Include the Composer autoloader

function sendVerificationEmail($subject, $body, $altBody, $email, $name)
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
        $mail->addAddress($email, $name);
        $mail->Subject = $subject;
        $mail->isHTML(true);
        $mail->Body = $body;
        $mail->AltBody = $altBody;
        $mail->send();
    } 
    catch (Exception $e) 
    {
        throw new Exception('Could not send email: ' . $mail->ErrorInfo);
    }
}

?>