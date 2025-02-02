<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/input-cleaner.php';
require_once '../include/email-sender.php'; 

// Redirect if user has already logged in
require_once '../access-control/login-access-controller.php'; 

// Generate CSRF Token for the form
if (empty($_SESSION['csrf_token'])) 
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

$errors = [];

$token = $_GET['token'] ?? '';

// Check if token is provided
if (empty($token)) 
{
    $message_type = 
        '<h3>Missing token</h3>
        <p>Missing reset token. Please check your link.</p>';

    $_SESSION['message_type'] = $message_type;

    header("Location: " . BASE_URL . "account/account-message-output.php");

    exit();
} 

// Verify token in the database
$stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = :token");
$stmt->execute([':token' => $token]);
$resetData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resetData) 
{
    // Token is invalid or not found
    $message_type = 
        '<h3>Invalid token</h3>
        <p>The verification token is invalid. Please check your link.</p>';

    $_SESSION['message_type'] = $message_type;

    header("Location: " . BASE_URL . "account/account-message-output.php");

    exit();
}

$user_id = $resetData['id'];
$email = $resetData['email'];
$fname = $resetData['first_name'];
$lname = $resetData['last_name'];
$tokenExpiration = $resetData['token_expiration'];
$currentTimestamp = time();

if ($currentTimestamp > $tokenExpiration) 
{
    $link = BASE_URL . "account/forgot-password.php";

    // Token has expired
    $message_type = 
        "<h3>Token has expired</h3>
        <p>The verification link has expired. <a href='" . $link . "'>Go back here</a></p>";

    $_SESSION['message_type'] = $message_type;

    header("Location: " . BASE_URL . "account/account-message-output.php");

    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) 
    {
        $technicalDetails = "Invalid CSRF token: " . $_SERVER['REMOTE_ADDR'];
        handleError("Invalid form submission. Please try again.", $technicalDetails);
    } 

    $new_password = sanitizeInput($_POST['new_password']);
    $confirm_password = sanitizeInput($_POST['confirm_password']);

    if (empty($new_password)) 
    {
        $errors['new_password'] = "Password is required.";
    }

    if (empty($confirm_password)) 
    {
        $errors['confirm_password'] = "Confirm password is required.";
    }

    // Validate password format
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $new_password)) 
    {
        $errors['new_password'] = "Password must meet the requirements below.";
    }

    if ($new_password !== $confirm_password) 
    {
        $errors['confirm_password'] = "Passwords do not match.";
    }
    

    if (empty($errors)) 
    {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("UPDATE users SET password = :password, verification_token = NULL, token_expiration = NULL WHERE id = :id");
        $updated = $stmt->execute([':password' => $hashed_password, ':id' => $user_id]);

        if ($updated) 
        {
            $name = $fname . ' ' . $lname;

           // Send notification email to user on password change
            $emailLink = "mailto:" . SUPPORT_EMAIL;

            $subject = "Password Changed";

            $body = "
                <p><p>Dear $name,</p>
                <p>Your account's password was successfully changed!</p>
                <p>
                    If this was not you, please contact us at 
                    <a href='$emailLink'>$emailLink</a>
                </p>";

            $altBody = "
                Your account's password was successfully changed! If this
                was not you, please contact us at $emailLink";

            sendVerificationEmail($subject, $body, $altBody, $email, $name);

            unset($_SESSION['csrf_token']); // Remove CSRF token after success

            $link = BASE_URL . "account/login.php";

            $message_type = "Password successfully reset. <a href='$link'>Login here</a>";

            $_SESSION['message_type'] = $message_type;

            header("Location: " . BASE_URL . "account/account-message-output.php");

            exit();
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="form-container">
        <h1>Reset Password</h1>

        <form method="POST" id="reset_password_form">
                        
            <!-- CSRF Token -->
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div id="csrf_token_error" class="error">
                    <?php echo $errorMessages['csrf_token']; ?>
                </div>
            </div>

            <!-- New Password -->
            <div class="form-group">
                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password">
                <div id="new_password_error" class="error">
                    <?php echo $errors['new_password'] ?? ''; ?>
                </div>
                <ul id="password-requirements">
                    <li id="uppercase" class="requirement">At least one uppercase letter</li>
                    <li id="lowercase" class="requirement">At least one lowercase letter</li>
                    <li id="number" class="requirement">At least one number</li>
                    <li id="special" class="requirement">At least one special character</li>
                </ul>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password">
                <div id="confirm_password_error" class="error">
                    <?php echo $errors['confirm_password'] ?? ''; ?>
                </div>
            </div>

            <!-- Submit -->
            <div class="form-group">
                <button type="submit" name="reset_password_submit" id="reset_password_submit">
                    Reset Password
                </button>
            </div>
        </form>
    </div>
</main>

<script>

document.addEventListener("DOMContentLoaded", function () 
{
    const form = document.getElementById("reset_password_form");
    const submitButton = document.getElementById("reset_password_submit");

    // Function to show error
    function showError(id, message) 
    {
        const errorDiv = document.getElementById(id);
        errorDiv.textContent = message;
        errorDiv.style.display = "block";
    }

    // Function to clear errors
    function clearErrors() 
    {
        const errorDivs = document.querySelectorAll(".error");
        errorDivs.forEach(div => {
            div.textContent = ""; // Clear error message
            div.style.display = "none"; // Hide error div
        });
    }

    function validateForm() 
    {
        let isValid = true;

        // Clear previous error messages
        clearErrors();

        const newPassword = document.getElementById("new_password");
        const confirmPassword = document.getElementById("confirm_password");

        // Validate Password
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/; // Regex patterns

        if (newPassword.value === "") 
        {
            showError("new_password_error", "Password is required.");
            isValid = false;
        }
        else if (!passwordRegex.test(newPassword.value)) 
        {
            showError("new_password_error", "Password must meet the requirements below.");
            isValid = false;
        }

        if (confirmPassword.value === "") 
        {
            showError("confirm_password_error", "Confirm password is required.");
            isValid = false;
        }
        else if (confirmPassword.value !== newPassword.value) 
        {
            showError("confirm_password_error", "Passwords do not match.");
            isValid = false;
        }

        return isValid;
    }

    // Password requirement text color change
    function updateRequirement(id, isValid) 
    {
        const element = document.getElementById(id);
        if (isValid) 
        {
            element.classList.add("valid");
            element.classList.remove("invalid");
        } 
        else 
        {
            element.classList.add("invalid");
            element.classList.remove("valid");
        }
    }

    // Password input typing check
    document.getElementById("new_password").addEventListener("input", function(event) 
    {
        const password = event.target.value;

        // Regex patterns for password condition
        const uppercaseRegex = /[A-Z]/;
        const lowercaseRegex = /[a-z]/;
        const numberRegex = /\d/;
        const specialCharRegex = /[^\w\s]/; // Don't match words and space. Only special characters

        // Check conditions
        const hasUppercase = uppercaseRegex.test(password);
        const hasLowercase = lowercaseRegex.test(password);
        const hasNumber = numberRegex.test(password);
        const hasSpecialChar = specialCharRegex.test(password);

        // Show/hide green color for each requirement based on password input
        updateRequirement("uppercase", hasUppercase);
        updateRequirement("lowercase", hasLowercase);
        updateRequirement("number", hasNumber);
        updateRequirement("special", hasSpecialChar);

        // Password validation pattern
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/;

        if (!passwordRegex.test(password)) 
        {
            showError("new_password_error", "Password must be at least 8 characters long.");
        } 
        else 
        {
            clearErrors();  // FIXED: Previously used `clearError()`, which was undefined
        }
    });

    form.addEventListener("submit", function (event) 
    {
        submitButton.disabled = true; // Disable button to prevent multiple clicks

        let isValid = validateForm(); // Validate form

        if (!isValid) 
        {
            event.preventDefault(); // Prevent submission if validation fails
            submitButton.disabled = false; // Re-enable button so user can try again
        }
        else 
        {
            form.submit();
        }
    });
});

</script>

<?php require_once '../include/footer.php'; ?>

