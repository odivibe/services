<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/input-cleaner.php';
require_once '../include/email-sender.php'; 

//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;

// Redirect if user has already logged in
require_once '../access-control/login-access-controller.php'; 

$errors = [];

// Generate CSRF Token for the form
if (empty($_SESSION['csrf_token'])) 
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot_password_submit'])) 
{
        // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) 
    {
        $technicalDetails = "Invalid form submission: " . $_SERVER['REMOTE_ADDR'];
        handleError("Invalid form submission. Please try again.", $technicalDetails);
    }
    else 
    {
        $email = sanitizeInput($_POST['forgot_password_email']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) 
        {
            $errors['forgot_password_email'] = "Invalid email format.";
        } 
        else 
        {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) 
            {
                $errors['forgot_password_email'] = "No account found with this email.";
            } 
            else 
            {
                // Generate reset token
                $verificationToken = bin2hex(random_bytes(64));
                $expiration = time() + 60 * 30; // (5 minutes)

                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET verification_token = :token, token_expiration = :expiry WHERE email = :email");
                    $stmt->bindParam(':token', $verificationToken, PDO::PARAM_STR);
                    $stmt->bindParam(':expiry', $expiration, PDO::PARAM_STR);
                    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                    $stmt->execute();


                $name = $user['first_name'] . ' ' . $user['last_name'];

                // Send password reset email
                $verificationLink = BASE_URL . "account/reset-password.php?token=" . $verificationToken;

                $subject = "Password Reset Request";

                $body = "
                    <p><p>Dear $name,</p>
                    <p>Click the link below to reset your password:</p>
                    <p><a href='$verificationLink'>$verificationLink</a></p>
                    <p>This link will expire in next 15 minutes.</p>";

                $altBody = "Dear $name, click the link below to reset your password: $verificationLink. The link will expire in next 15 minutes.";

                sendVerificationEmail($subject, $body, $altBody, $email, $name);

                $message_type = 
                   '<h3>An email has been sent.</h3>
                    <p>
                        We have sent an email to <strong>' . $email . '</strong>. You will receive instructions on how to reset your password shortly, Please check your inbox or spam folder.
                    </p>';

                $_SESSION['message_type'] = $message_type;
                unset($_SESSION['csrf_token']); // Remove CSRF token after success
                header("Location:" . BASE_URL . "account/account-message-output.php");
                exit(); 
            }
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="form-container">
        <h1>Forgot Password</h1>

        <form method="POST">
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div id="csrf_token_error" class="error">
                    <?php echo $errorMessages['csrf_token']; ?>
                </div>
            </div>

            <!-- Email -->
            <div class="form-group">
                <label for="forgot_password_email">Email:</label>
                <input type="text" name="forgot_password_email" id="forgot_password_email" placeholder="Enter email" required>
                <div class="error"><?php echo $errors['forgot_password_email'] ?? ''; ?></div>
            </div>

            <!-- Submit -->
            <div class="form-group">
                <button type="submit" name="forgot_password_submit" id="forgot_password_submit">Submit</button>
            </div>
        </form>

        <!-- Login Link -->
        <div class="form-links">
            <a href="<?php echo BASE_URL; ?>account/login.php">Back to Login</a>
        </div>
    </div>
</main>

<?php require_once '../include/footer.php'; ?>
