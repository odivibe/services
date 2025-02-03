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

    if ($current_time < $next_allowed_change) //user can change email because current time is greater 
    {
        $can_change_email = false; // Can change email
        $remaining_days = ceil(($next_allowed_change - $current_time) / 86400); // Compute remaining days left
        
    }
}

$_SESSION['csrf_token'] = bin2hex(random_bytes(64));

?>

<main class="body-container">
    
    <div class="msg-info" id="message-output"></div>
    
    <!-- Show the form if 30 days have passed -->
    <div class="form-container">
        <h1>Change Your Email</h1>
        <?php if ($can_change_email): ?>
            <form method="POST" action="<?php echo htmlspecialchars('process-change-email.php'); ?>" enctype="multipart/form-data">
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

                <!-- Password -->
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
                <p>You can change your email in <b><?php echo $remaining_days; ?> days</b>.</p>
                <p id="countdown"></p>
            </div>
        <?php endif; ?>
    </div>

</main>


<?php if (!$can_change_email): ?>
<script>
    //document.getElementById("countdown").textContent = "chima";
    /*let remainingSeconds = <?php echo intval($remaining_days) * 86400; ?>; // Convert days to seconds

    function updateCountdown() 
    {
        if (remainingSeconds <= 0) 
        {
            document.getElementById("countdown").innerText = "You can now change your email!";
            document.getElementById("change-email-btn").disabled = false; // Enable button
            return;
        }

        let days = Math.floor(remainingSeconds / 86400);
        let hours = Math.floor((remainingSeconds % 86400) / 3600);
        let minutes = Math.floor((remainingSeconds % 3600) / 60);
        let seconds = remainingSeconds % 60;

        document.getElementById("countdown").innerText = 
            `You can change your email in ${days} days, ${hours} hours, ${minutes} minutes, and ${seconds} seconds.`;

        remainingSeconds--;
        setTimeout(updateCountdown, 1000);
    }

    // Disable the email change button initially
    document.addEventListener("DOMContentLoaded", function () {
        document.getElementById("change-email-btn").disabled = true; // Disable button initially
        updateCountdown();
    });

    // Periodically check from the server if the countdown is over
    setInterval(function() {
        fetch("check-email-change.php") // A server-side script to check if the time has expired
            .then(response => response.json())
            .then(data => {
                if (data.can_change) {
                    document.getElementById("countdown").innerText = "You can now change your email!";
                    document.getElementById("change-email-btn").disabled = false;
                }
            });
    }, 30000); // Check every 30 seconds*/
</script>
<?php endif; ?>

