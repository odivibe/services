<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Include the Composer autoloader

   $mail = new PHPMailer(true); 
       //Server settings
       $mail->isSMTP();
       $mail->Host = 'sandbox.smtp.mailtrap.io';
       $mail->SMTPAuth = true;
       $mail->Username = '670c70f26f6810';
       $mail->Password = 'd4b68560895d65'; 
       $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
       $mail->Port = 465;
       $email = 'elvisway4u@gmail.com';
       //Recipients
       $mail->setFrom('no-reply@yourdomain.com', 'Your Website');
       $mail->addAddress($email);

       // Content
       $mail->isHTML(true);
       $mail->Subject = 'Email Confirmation';
       $mail->Body =  
          "<p>We have sent an email verification link.</p>
           <p>Please click the link below to verify your email:</p>
           <p>Thanks.</p>";

       $mail->AltBody = 'Thank you for registering on our website. Please click the link below to verify your email';

       if ($mail->send()) 
       {
         echo 'email sent';
       }
?>