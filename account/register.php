<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/input-cleaner.php';

// CSRF Token
if (empty($_SESSION['csrf_token'])) 
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new token
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php'; // Include the Composer autoloader

// Initialize an array to hold errors
$errorMessages = [
    'signup_fname' => '',
    'signup_lname' => '',
    'signup_phone' => '',
    'signup_email' => '',
    'signup_password' => '',
    'signup_confirm_password' => '',
    'signup_terms' => ''
];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['signup_submit'])) 
{
    // Check CSRF token validity
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) 
    {
        $message = "[" . date('Y-m-d H:i:s') . "] CSRF token mismatch for IP: " . $_SERVER['REMOTE_ADDR'] . PHP_EOL;
        error_log($message, 3, '../errors/custom-error.log');

        // emailAdmin($message); //Notify admin 

        // Set session error message
        $_SESSION['error'] = "Invalid form submission. Please try again.";

        // Redirect to error page
        header("Location: " . BASE_URL . "errors/error-message.php");
        exit();
    }


    $errors = [];

    // Clean inputs using your input-cleaner function
    $fname = sanitizeInput($_POST['signup_fname']);
    $lname = sanitizeInput($_POST['signup_lname']);
    $phone = sanitizeInput($_POST['signup_phone']);
    $email = strtolower(sanitizeInput($_POST['signup_email']));
    $password = sanitizeInput($_POST['signup_password']);
    $confirmPassword = sanitizeInput($_POST['signup_confirm_password']);

    if (empty($fname)) 
    {
        $errors['signup_fname'] = "First name is required.";
    }

    if (empty($lname)) 
    {
        $errors['signup_lname'] = "Last name is required.";
    }

    if (!preg_match('/^[0-9]{11}$/', $phone)) 
    {
        $errors['signup_phone'] = "Phone number must be exactly 11 digits.";
    }

    $email = filter_var($_POST['signup_email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        $errors['signup_email'] = "Please enter a valid email address.";
    }

    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $password)) 
    {
        $errors['signup_password'] = "Password must meet the required conditions below.";
    }

    if ($password !== $confirmPassword) 
    {
        $errors['signup_confirm_password'] = "Passwords do not match.";
    }

    if (empty($_POST['signup_terms'])) 
    {
        $errors['signup_terms'] = "You must agree to the terms and conditions.";
    }

    try 
    {
        // Check if the phone number already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindParam(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        $emailExists = $stmt->fetchColumn();

        // If email or phone exists, add error
        if ($emailExists > 0) 
        {
            $errors['signup_email'] = "Email is already registered.";
        }

        // Check if the email already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE phone = :phone");
        $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
        $stmt->execute();
        $phoneExists = $stmt->fetchColumn();

        // If email or phone exists, add error
        if ($phoneExists > 0) 
        {
            $errors['signup_phone'] = "Phone number is already registered.";
        }
    } 
    catch (PDOException $e) 
    {
        $message = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . PHP_EOL;

        error_log($message, 3, '../errors/custom-error.log');

        // emailAdmin($message);  // Notify admin

        http_response_code(500);

        require_once '../include/header.php';
        require_once '../include/500.php';
        require_once '../include/footer.php';

        exit();
    }


    // Check for errors
    foreach ($errors as $field => $message) 
    {
        $errorMessages[$field] = $message;
    }

    // If no errors, proceed with further registration logic
    if (empty($errors)) 
    {
        try 
        {
            // Generate a unique verification token and expiration time
            $verificationToken = bin2hex(random_bytes(32));
            $expiration = time() + 60 * 5; // (5 minutes)

            $query = "INSERT INTO users (first_name, last_name, phone, email, password, verification_token, token_expiration) VALUES (:fname, :lname, :phone, :email, :password, :token, :expiration)";
            $stmt = $pdo->prepare($query);

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt->bindParam(':fname', $fname, PDO::PARAM_STR);
            $stmt->bindParam(':lname', $lname, PDO::PARAM_STR);
            $stmt->bindParam(':phone', $phone, PDO::PARAM_STR);
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashedPassword, PDO::PARAM_STR);
            $stmt->bindParam(':token', $verificationToken, PDO::PARAM_STR);
            $stmt->bindParam(':expiration', $expiration, PDO::PARAM_STR);
            $stmt->execute();

            $user_id = $pdo->lastInsertId(); // Last inserted user ID

            // Send the verification email
            $verificationLink = BASE_URL . "account/verify-email.php?token=" . $verificationToken;
            //$verificationLink = BASE_URL . "account/verify-email.php?id=" . $user_id . "&token=" . $verificationToken;

            $mail = new PHPMailer(true); 
            try 
            {
                //Server settings
                $mail->isSMTP();
                $mail->Host = 'sandbox.smtp.mailtrap.io';
                $mail->SMTPAuth = true;
                $mail->Username = '670c70f26f6810';
                $mail->Password = 'd4b68560895d65'; 
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;

                //Recipients
                $mail->setFrom('no-reply@yourdomain.com', 'Your Website');
                $mail->addAddress($email, $fname . ' ' . $lname);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Email Confirmation';
                $mail->Body =  "
                            <p>Hi $fname,</p>
                            <p>We have sent an email verification link.</p>
                            <p>Please click the link below to verify your email:</p>
                            <p><a href='" . $verificationLink . "'>" . $verificationLink . "</a></p>
                            <p>Thanks.</p>";

                $mail->AltBody = 'Dear ' . $fname . ',\n\nThank you for registering on our website. Please click the link below to verify your email address:\n' . $verificationLink . '\n\nBest regards,\nYour Website Team';

                $mail->send();
            } 
            catch (Exception $e) 
            {
                $message = "[" . date('Y-m-d H:i:s') . "] Mailer Error: " . $mail->ErrorInfo . PHP_EOL;

                error_log($message, 3, '../errors/custom-error.log');

                // emailAdmin($message); //Notify admin 

                $_SESSION['error'] = "Failed to send the email. Please try again later.";

                header("Location: " . BASE_URL . "errors/error-message.php");

                exit();
            }

            // Set session variables after inserting data into the database
            $_SESSION['user_id'] = $user_id; 
            $_SESSION['user_email'] = $email;
            $_SESSION['logged_in'] = false;
            $_SESSION['message_type'] = 'email-sent';

            // Redirect to success message
            header("Location:" . BASE_URL . "account/message-output-manager.php");
            exit();
        } 
        catch (PDOException $e) 
        {
            $message = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage() . " | File: " . $e->getFile() . " | Line: " . $e->getLine() . PHP_EOL;

            error_log($message, 3, '../errors/custom-error.log');

            // emailAdmin($message);  // Notify admin

            http_response_code(500);

            require_once '../include/header.php';
            require_once '../include/500.php';
            require_once '../include/footer.php';

            exit();
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>

<div class="form-container">
    <h1>Sign Up</h1>
    <form method="POST" enctype="multipart/form-data" id="signup_form">

        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <!-- First Name -->
        <div class="form-group">
            <label for="signup_fname">First Name: <span class="required">*</span></label>
            <input type="text" name="signup_fname" id="signup_fname" placeholder="Enter first name" required>
            <div id="signup_fname_error" class="error">
                <?php echo $errorMessages['signup_fname']; ?>
            </div>
        </div>

        <!-- Last Name -->
        <div class="form-group">
            <label for="signup_lname">Last Name: <span class="required">*</span></label>
            <input type="text" name="signup_lname" id="signup_lname" placeholder="Enter last name" required>
            <div id="signup_lname_error" class="error">
                <?php echo $errorMessages['signup_lname']; ?>
            </div>
        </div>

        <!-- Phone -->
        <div class="form-group">
            <label for="signup_phone">Phone: <span class="required">*</span></label>
            <input type="text" name="signup_phone" id="signup_phone" placeholder="Eg, 08062xxxxxx" required>
            <div id="signup_phone_error" class="error">
                <?php echo $errorMessages['signup_phone']; ?>
            </div>
        </div>

        <!-- Email -->
        <div class="form-group">
            <label for="signup_email">Email: <span class="required">*</span></label>
            <input type="email" name="signup_email" id="signup_email" placeholder="Enter email" required>
            <div id="signup_email_error" class="error">
                <?php echo $errorMessages['signup_email']; ?>
            </div>
        </div>

        <!-- Password -->
        <div class="form-group">
            <label for="signup_password">Password: <span class="required">*</span></label>
            <input type="password" name="signup_password" id="signup_password" placeholder="Enter password" required>
            <div id="signup_password_error" class="error">
                <?php echo $errorMessages['signup_password']; ?>
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
            <label for="signup_confirm_password">Confirm Password: <span class="required">*</span></label>
            <input type="password" name="signup_confirm_password" id="signup_confirm_password" placeholder="Confirm password" required>
            <div id="signup_confirm_password_error" class="error">
                <?php echo $errorMessages['signup_confirm_password']; ?>
            </div>
        </div>

        <!-- Terms and Conditions -->
        <div class="form-group terms-group">
            <input type="checkbox" name="signup_terms" id="signup_terms" required>
            <label for="signup_terms">
                <span class="required">*</span> I agree to the 
                <a href="<?php echo BASE_URL; ?>terms-of-service.php" target="_blank">Terms of Service</a> and 
                <a href="<?php echo BASE_URL; ?>privacy-policy.php" target="_blank">Privacy Policy</a>.
            </label>
            <div id="signup_terms_error" class="error"><?php echo $errorMessages['signup_terms']; ?></div>
        </div>

        <!-- Submit -->
        <div class="form-group">
            <button type="submit" name="signup_submit" id="login_submit">Submit</button>
        </div>

        <!-- Links -->
        <div class="form-links">
            <p>Already have an account? <a href="<?php echo BASE_URL; ?>account/login.php">Login here</a>.</p>
            <p><a href="<?php echo BASE_URL; ?>account/forgot-password.php">Forgot your password?</a></p>
        </div>
    </form>
</div>

<script>

// Form submission
document.addEventListener("DOMContentLoaded", function () 
{
    const form = document.getElementById("signup_form");
    const errors = {}; // To track errors dynamically

    form.addEventListener("submit", function (e) 
    {
        let isValid = true;

        // Clear previous error messages
        clearErrors();

        // Validate First Name
        const fname = document.getElementById("signup_fname");
        if (fname.value.trim() === "") 
        {
            isValid = false;
            showError("signup_fname_error", "First name is required.");
        }

        // Validate Last Name
        const lname = document.getElementById("signup_lname");
        if (lname.value.trim() === "") 
        {
            isValid = false;
            showError("signup_lname_error", "Last name is required.");
        }

        // Validate Phone Number
        const phone = document.getElementById("signup_phone");
        const phoneRegex = /^[0-9]{11}$/;
        if (!phoneRegex.test(phone.value.trim())) 
        {
            isValid = false;
            showError("signup_phone_error", "Enter a valid phone number (11 digits).");
        }

        // Validate Email
        const email = document.getElementById("signup_email");
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(email.value.trim())) 
        {
            isValid = false;
            showError("signup_email_error", "Enter a valid email address.");
        }

        // Validate Password
        const password = document.getElementById("signup_password");
        const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/;
        if (!passwordRegex.test(password.value)) 
        {
            isValid = false;
            showError("signup_password_error", "Password must meet the required conditions below.");
        }

        // Validate Confirm Password
        const confirmPassword = document.getElementById("signup_confirm_password");
        if (confirmPassword.value !== password.value) 
        {
            isValid = false;
            showError("signup_confirm_password_error", "Passwords do not match.");
        }

        // Validate Terms and Conditions
        const terms = document.getElementById("signup_terms");
        if (!terms.checked) 
        {
            isValid = false;
            showError("signup_terms_error", "You must accept the terms and conditions.");
        }

        // Prevent form submission if validation fails
        if (!isValid) 
        {
            e.preventDefault();
        }
    });

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
});


//Password input typing check
document.getElementById("signup_password").addEventListener("input", function(event) 
{
    const password = event.target.value;
    const uppercaseRegex = /[A-Z]/;
    const lowercaseRegex = /[a-z]/;
    const numberRegex = /\d/;
    const specialCharRegex = /[^\w\s]/; // Allows any special character

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
        showError("signup_password_error", "Password must be at least 8 characters long.");
    } 
    else 
    {
        clearError();
    }

});

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


/*function togglePassword() 
{
    const passwordField = document.getElementById('signup_password');
    const confirmPasswordField = document.getElementById('signup_confirm_password');
    const passwordCheckbox = document.getElementById('show_password');
    const confirmPasswordCheckbox = document.getElementById('show_confirm_password');
    
    // Toggle the type attribute for both password fields
    if (passwordCheckbox.checked) 
    {
        passwordField.type = 'text';
        confirmPasswordField.type = 'text';  // Optionally show the confirm password as well
    } 
    else 
    {
        passwordField.type = 'password';
        confirmPasswordField.type = 'password';
    }
}*/

</script>

<?php require_once '../include/footer.php'; ?>
