<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/input-cleaner.php';
require_once '../include/email-sender.php';

// Redirect if user has already logged in
require_once '../access-control/login-access-controller.php';

$errors = [];

// Generate CSRF Token for the form
if (empty($_SESSION['csrf_token'])) 
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) 
    {
        $technicalDetails = "Invalid form submission: " . $_SERVER['REMOTE_ADDR'];
        handleError("Invalid form submission. Please try again.", $technicalDetails);

        $errors["login-errors"] = "Invalid form submission. Please try again.";
    } 
    else 
    {
        $email = strtolower(sanitizeInput($_POST['login_email']));
        $password = sanitizeInput($_POST['login_password']);

        // Fetch user from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $userResult = $stmt->fetch();

        if (!$userResult) 
        {
            $errors["login-errors"] = 'Invalid email or password.';
        } 
        else 
        {
            // Check email verification status
            if ($userResult['email_verified'] == 0) 
            {
                // Check if token is still valid
                if (time() > $userResult['token_expiration']) 
                {
                    // Generate new token and expiry date
                    $verificationToken = bin2hex(random_bytes(64));
                    $expiry = time() + (60 * 5); // 5 minutes
                    $fname = $userResult['first_name'];

                    try 
                    {
                        // Update token and expiry in the database
                        $stmt = $pdo->prepare("UPDATE users SET verification_token = :token, token_expiration = :expiry WHERE email = :email");
                        $stmt->bindParam(':token', $verificationToken, PDO::PARAM_STR);
                        $stmt->bindParam(':expiry', $expiry, PDO::PARAM_INT);
                        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
                        $stmt->execute();

                        // Prepare and send verification email
                        $verificationLink = BASE_URL . "account/verify-email.php?token=" . $verificationToken;

                        $subject = "Email verification";

                        $body = "
                            <p>Dear $fname,</p>
                            <p>Please click on the link below to verify your email:</p>
                            <p><a href='$verificationLink'>$verificationLink</a></p>
                            <p>The link will expire in next 15 minutes.</p>
                            <p>Thanks,</p>
                            <p>Your Website Team</p>";

                        $altBody = "Please click on the link below to verify your email: $verificationLink. The link will expire in next 30 minutes.";

                        sendVerificationEmail($subject, $body, $altBody, $email, $fname);

                        $message_type = 
                            '<h3>Email resent</h3>
                            <p>
                                Verification token has been resend to your email, <strong>' . 
                                $email . '</strong>. Please check your inbox or spam folder to verify your email.
                            </p>';

                        $_SESSION['message_type'] = $message_type;
                        header("Location: " . BASE_URL . "account/account-message-output.php");
                        exit();
                    } 
                    catch (Exception $e) 
                    {
                        handleError('Failed to resend verification email. Please try again later.', $e);
                    }
                } 
                else 
                {
                    $_SESSION['message_type'] = 'A verification email was recently sent. Please check your inbox.';
                    header("Location: " . BASE_URL . "account/account-message-output.php");
                    exit();
                }
            }

            // Verify password
            if (!password_verify($password, $userResult['password'])) 
            {
                $errors["login-errors"] = 'Invalid email or password.';
            }

            // Successful login
            if (empty($errors)) 
            {
                session_regenerate_id(true); // Regenerate session ID
                $_SESSION['user_id'] = $userResult['id'];
                $_SESSION['has_logged_in'] = true;
                unset($_SESSION['csrf_token']); // Remove CSRF token after success

                // Redirect to home page
                header("Location: " . BASE_URL . "index.php");
                exit();
            }
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="form-container">
        <h1>Login</h1>
        <form method="POST" enctype="multipart/form-data">
            
            <!-- Error Message -->
            <div class="form-group error">
                <?php echo $errors["login-errors"] ?? ''; ?>
            </div>

            <!-- CSRF Token -->
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            </div>

            <!-- Email Input -->
            <div class="form-group">
                <label for="login_email">Email: <span class="required">*</span></label>
                <input type="email" name="login_email" id="login_email" placeholder="Enter email" required>
            </div>

            <!-- Password Input -->
            <div class="form-group">
                <label for="login_password">Password: <span class="required">*</span></label>
                <input type="password" name="login_password" id="login_password" placeholder="Enter password" required>
            </div>

            <!-- Remember Me -->
            <div class="form-group remember-me">
                <input type="checkbox" name="remember_me" id="remember_me">
                <label for="remember_me">Remember Me</label>
            </div>

            <!-- Submit Button -->
            <div class="form-group">
                <button type="submit" name="login_submit" id="login_submit">Submit</button>
            </div>

            <!-- Links -->
            <div class="form-links">
                <a href="<?php echo BASE_URL; ?>account/forgot-password.php">Forgot Password?</a>
                <span> | </span>
                <a href="<?php echo BASE_URL; ?>account/register.php">Register</a>
            </div>
        </form>
    </div>
</main>

<?php require_once '../include/footer.php'; ?>
