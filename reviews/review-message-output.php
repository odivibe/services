<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';

// Retrieve the review message from the session or set a fallback message
$showMessage = isset($_SESSION['review_message']) ? $_SESSION['review_message'] : 'An unknown error occurred.';

// Clear the session variables after displaying the message
if (isset($_SESSION['review_submitted'])) 
{
    unset($_SESSION['review_submitted']);
}

unset($_SESSION['review_message']);
?>

<?php require_once '../include/header.php'; ?>

<div class="message-container">
    <div class="msg-info" role="alert">
        <?php echo htmlspecialchars($showMessage, ENT_QUOTES, 'UTF-8'); ?>
    </div>
</div>

<?php require_once '../include/footer.php'; ?>
