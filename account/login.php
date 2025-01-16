<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/input-cleaner.php';
require_once '../include/email-sender.php';

$error = '';

// Generate CSRF Token for the form
if (empty($_SESSION['csrf_token'])) 
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) 
{
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) 
    {
        $error = 'Invalid form submission. Please try again.';
    } 
    else 
    {
        $email = strtolower(sanitizeInput($_POST['login_email']));
        $password = sanitizeInput($_POST['login_password']);

        // Fetch user from database
        $stmt = $pdo->prepare("SELECT id, email, password, email_verified, first_name, verification_token, token_expiration FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $userResult = $stmt->fetch();

        if (!$userResult) 
        {
            $error = 'Invalid email or password.';
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
                    $verificationToken = bin2hex(random_bytes(32));
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

                        sendVerificationEmail($email, $fname, $verificationLink);

                        $message_type = 
                            '<h4>Email resent</h4>
                            <p>
                                Verification token has been resend to your email, <strong>' . 
                                $email . '</strong>. Please check your inbox or spam folder to verify your email.
                            </p>';

                        $_SESSION['message_type'] = $message_type;
                        header("Location: " . BASE_URL . "account/message-output-manager.php");
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
                    header("Location: " . BASE_URL . "account/message-output-manager.php");
                    exit();
                }
            }

            // Verify password
            if (!password_verify($password, $userResult['password'])) 
            {
                $error = 'Invalid email or password.';
            }

            // Successful login
            if (empty($error)) 
            {
                session_regenerate_id(true); // Regenerate session ID
                $_SESSION['user_id'] = $userResult['id'];
                $_SESSION['logged_in'] = true;

                // Redirect to dashboard or home page
                header("Location: " . BASE_URL . "index.php");
                exit();
            }
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>

<div class="form-container">
    <h1>Login</h1>
    <form method="POST" enctype="multipart/form-data">
        <!-- Error Message -->
        <?php if (!empty($error)): ?>
            <div class="form-group error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

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

        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

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

<?php require_once '../include/footer.php'; ?>
