<?php
require_once '../include/config.php';
require_once '../include/db.php';
/** 
 * This script manages message output for registration and email verification 
 */

session_start();

require_once '../include/config.php';

$messageType = $_SESSION['message_type'] ?? null;
$showResendButton = $_SESSION['show_resend_button'] ?? false;

// Clear session messages after displaying
unset($_SESSION['message_type'], $_SESSION['show_resend_button']);

?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="message-container">

        <div class="msg-info" id="message-output">
            <?php if ($messageType): ?>
                <?php echo $messageType; ?>
            <?php endif; ?>
        </div>

        <!-- Resend Verification Button -->
        <?php if ($showResendButton): ?>
            <button id="resend-button">Resend Verification Email</button>
        <?php endif; ?>

    </div>
</main>

<?php require_once '../include/footer.php'; ?>
