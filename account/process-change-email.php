<?php

session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/input-cleaner.php';
require_once '../include/email-sender.php';

header('Content-Type: application/json'); // Send JSON response

// redirect if not logged in
if (!isset($_SESSION['user_id'])) 
{
    header('Location:' . BASE_URL . 'account/login.php');
    exit();
}

$errors = [];

if ($_SERVER["REQUEST_METHOD"] === "POST")  
{
    
    //$_SESSION['form_submitted'] = true; // Mark form as submitted

    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $_POST['csrf_token']) 
    {
        $technicalDetails = "CSRF token mismatch for IP: " . $_SERVER['REMOTE_ADDR'];
        handleError("Invalid form submission. Please try again.", $technicalDetails);

        $errors['csrf_token'] = "We can't validate you, try again";
    }

    $new_email = strtolower(sanitizeInput($_POST['new_email']));
    $password = sanitizeInput($_POST['password']);

    if (empty($new_email)) 
    {
        $errors['new_email'] = 'New email is required';
    }
    else
    {
        // Validate Email Format
        if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) 
        {
            $errors['new_email'] = "Invalid email format.";
        }
    }

    

    try 
    {
        // Check if email already Exists in users table
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $new_email]);

        if ($stmt->fetch()) 
        {
            $errors['new_email'] = "This email is already in use.";
        }
    }
    catch (Exception $e) 
    {
        handleError('An error occurred. Please try again later.', $e);
        exit();
    }

    /*if (empty($password)) 
    {
        $errors['password'] = 'Password is required';
    }

    try 
    {
        // fetch user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
        $stmt->execute([':id' => $user_id]);
        $user = $stmt->fetch();

        // Verify User Password
        if (!$user || !password_verify($password, $user['password'])) 
        {
            $errors['password'] = "Incorrect password.";
        }
        else
        {
            $old_email = $user['email'];
            $fname = $user['first_name'];
            $lname = $user['last_name'];
        }

    } 
    catch (Exception $e) 
    {
        handleError('An error occurred. Please try again later.', $e);
        exit();
    }*/
    
    if (empty($errors)) 
    {
        // Generate a unique verification token and expiration time
        /*$new_email_token = bin2hex(random_bytes(64));
        $expiration = time() + 60 * 5; // (5 minutes)

        $query = "INSERT INTO email_change_requests (user_id, old_email, new_email, verification_token, token_expiration) VALUES (:user_id, :old_email, :new_email, :verification_token, :token_expiration)";
            $stmt = $pdo->prepare($query);

            $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
            $stmt->bindParam(':old_email', $old_email, PDO::PARAM_STR);
            $stmt->bindParam(':new_email', $new_email, PDO::PARAM_STR);
            $stmt->bindParam(':verification_token', $verificationToken, PDO::PARAM_STR);
            $stmt->bindParam(':token_expiration', $expiration, PDO::PARAM_STR);
            $stmt->execute();

        //Email verification to new email
        $verificationLink = BASE_URL . "account/change-email-verification.php?token=" . 
            $new_email_token;

        $subject = "Email verification";

        $name = $fname . " " . $lname;

        $body = "
            <p>Dear $name,</p>
            <p>Please click on the link below to verify your email.</p>
            <p><a href='$verificationLink'>$verificationLink</a></p>
            <p>The link will expire in 1 hour time.</p>
            <p>Thanks.</p>";

        $altBody = "Dear $name,\n Please click on the link below to verify your email.\n $verificationLink. The link will expire in 1 hour time. \n Thanks.";

        sendVerificationEmail($subject, $body, $altBody, $email, $name);

        
        //Security email to old email
        $emailLink = "mailto:" . SUPPORT_EMAIL; // support

        $subject = "Security Alert: Email Change Attempt";

        $body = "
            <p>Dear $name,</p>
            <p>Your email has been changed to $old_email.</p>
            <p>
                If this wasn't you, click here to secure your account, 
                <a href='$revert_link'>$revert_link</a>
            </p>
            <p>Thanks.</p>";

        $altBody = "
            Dear $name, \n Your email has been changed to $old_email. \n If this wasn't you, click here to secure your account. \n $emailLink.";

        sendVerificationEmail($subject, $body, $altBody, $email, $name);

        unset($_SESSION['csrf_token']);*/

        echo json_encode(['success' => true, 'message' => "We sent verification emails to your old and new addresses. Follow the instructions there"]);
    }
    else
    {
        echo json_encode(['success' => false, 'message' => 'Errors in form fields.', 'errors' => $errors]);
        exit();
    }
}


?>
