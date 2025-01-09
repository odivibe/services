<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Ensure PHPMailer is installed via Composer

function sendEmail($recipientEmail, $recipientName, $subject, $htmlBody, $altBody) 
{
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
        $mail->Port = 465;

        // Recipients
        $mail->setFrom('no-reply@yourdomain.com', 'Your Website');
        $mail->addAddress($recipientEmail, $recipientName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $htmlBody;
        $mail->AltBody = $altBody;

        $mail->send();
        $success = true;
        return $success;
    } 
    catch (Exception $e) 
    {
        handleError("We couldn't send the email. Please try again later.", $e);
        exit();
    }
}

?>
