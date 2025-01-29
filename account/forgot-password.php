<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/header.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists in database
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Generate a unique token
        $token = bin2hex(random_bytes(50));
        $expires_at = date('Y-m-d H:i:s', strtotime('+1 hour')); // Token valid for 1 hour

        // Insert or update reset token in database
        $stmt = $pdo->prepare("REPLACE INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->execute([$email, $token, $expires_at]);

        // Send reset email
        $resetLink = BASE_URL . "/reset-password.php?token=$token";
        $subject = "Password Reset Request";
        $message = "Click the link below to reset your password:\n$resetLink";
        $headers = "From: no-reply@example.com";

        if (mail($email, $subject, $message, $headers)) {
            $success = "Password reset link has been sent to your email.";
        } else {
            $error = "Failed to send email. Please try again later.";
        }
    } else {
        $error = "Email address not found.";
    }
}
?>

<main class="body-container">
    <div class="form-container">
        <h1>Forgot Password</h1>
        <?php if ($error): ?>
            <p class="error"> <?= $error; ?> </p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p class="success"> <?= $success; ?> </p>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <!-- Email -->
            <div class="form-group">
                <label for="forgot_password_email">Email:</label>
                <input type="text" name="forgot_password_email" id="forgot_password_email" placeholder="Enter email" required>
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