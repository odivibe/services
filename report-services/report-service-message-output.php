<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';

// Retrieve the report message from the session or set a fallback message
$showMessage = isset($_SESSION['report_message']) ? $_SESSION['report_message'] : 'An unknown error occurred.';

// Clear the session variable
unset($_SESSION['report_message']);
?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="message-container">
        <div class="msg-info" role="alert">
            <?php echo htmlspecialchars($showMessage, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    </div>
</main>

<?php require_once '../include/footer.php'; ?>