<?php 

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/error-handler.php';
require_once '../include/input-cleaner.php';
require_once '../include/email-sender.php'; 

if (!isset($_SESSION['user_id'])) 
{
    header('Location:' . BASE_URL . 'account/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$errors = [];
$message_type = "";


// Check email change remaining days
$query = "SELECT new_email, last_email_change FROM email_change_requests 
          WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 1";
$stmt = $pdo->prepare($query);
$stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
$stmt->execute();
$pending_request = $stmt->fetch();

$can_change_email = true; // Default: allow change if no previous request
$remaining_days = 0;

if ($pending_request) 
{
    $last_change_timestamp = strtotime($pending_request['last_email_change']); // Last email change timestamp
    $next_allowed_change = strtotime('+30 days', $last_change_timestamp); // Next allowed change timestamp
    $current_time = time(); // Current timestamp

    if ($current_time < $next_allowed_change) 
    {
        $can_change_email = false; // Cannot change email yet
        $remaining_days = ceil(($next_allowed_change - $current_time) / 86400); // Days left
    }
}


if (empty($_SESSION['csrf_token'])) 
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(64));
}

// Prevent multiple submissions
if (!isset($_SESSION['form_submitted'])) 
{
    $_SESSION['form_submitted'] = false;
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && $_SESSION['form_submitted'] === false)  
{
     $_SESSION['form_submitted'] = true; // Mark form as submitted

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

    // Validate Email Format
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) 
    {
        $errors['new_email'] = "Invalid email format.";
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

    if (empty($password)) 
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
    }
    
    if (empty($errors)) 
    {
        // Generate a unique verification token and expiration time
        $new_email_token = bin2hex(random_bytes(64));
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
        $old_email_token = bin2hex(random_bytes(64));
        $revert_link = BASE_URL . "account/change-email-verification.php?token=" . $old_email_token;

        $subject = "Email changed security Alert";

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

        unset($_SESSION['csrf_token'], $_SESSION['form_submitted']);

        $message_type = "We sent email to your old and new email addresses. Follow the instructions there";
    }
}

?>

<main class="body-container">
    
    <div class="msg-info" id="message-output">
        <?php if (!empty($message_type)): ?>
            <?php echo $message_type; ?>
        <?php endif; ?>
    </div>
    
    
    <!-- Show the form if 30 days have passed -->
    <div class="form-container">
    <h1>Change Your Email</h1>
    <?php if ($can_change_email): ?>
        <form method="POST" action="<?php echo htmlspecialchars('process-change-email.php'); ?>" 
            enctype="multipart/form-data">

            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div id="csrf_token_error" class="error">
                    <?php echo $errors['csrf_token'] ?? ''; ?>
                </div>
            </div>
            
            <!-- New Email -->
            <div class="form-group">
                <label for="new_email">New Email:</label>
                <input type="text" name="new_email" id="new_email" placeholder="Enter new email">
                <div id="new_email_error" class="error">
                    <?php echo $errors['new_email'] ?? ''; ?>  
                </div>
            </div>

            <!--Password -->
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" name="password" id="password" placeholder="Enter password">
                <div id="password_error" class="error">
                    <?php echo $errors['password'] ?? ''; ?>
                </div>
            </div>

            <!-- Submit -->
            <div class="form-group">
                <button type="submit" name="change_email_submit" id="change_email_submit">Submit</button>
            </div>
        </form>
    <?php else: ?>

        <div>
            <p>You can change your email in <b>{$remaining_days} days</b>.</p>
            <p><b><span id="countdown" style="color: red;"></span></b></p>
        </div>
   </div>
    <?php endif; ?>
</main>



<?php if (!$can_change_email): ?>

<script>

    // Counter for number of days left to change email
    let remainingSeconds = <?= $remaining_days * 86400; ?>; // Number of seconds in a day 86400
    
    function updateCountdown() 
    {
        let days = Math.floor(remainingSeconds / 86400); // Number of seconds in a day 3600
        let hours = Math.floor((remainingSeconds % 86400) / 3600); // Number of seconds in an hour 3600
        let minutes = Math.floor((remainingSeconds % 3600) / 60); // Number of seconds in a minute 60
        let seconds = remainingSeconds % 60;

        document.getElementById("countdown").innerText = 
            `You can change your email in ${days} days, ${hours} hours, ${minutes} minutes, and ${seconds} seconds.`;

        if (remainingSeconds > 0) 
        {
            remainingSeconds--;
            setTimeout(updateCountdown, 1000);
        }
    }

    updateCountdown();

</script>

<?php endif; ?>

