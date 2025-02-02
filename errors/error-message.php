<?php
session_start();
require_once '../include/config.php';

    $showMessage = isset($_SESSION['show_message'] )? $_SESSION['show_message'] : "Unexpected issue encountered. Try again." ;
    unset($_SESSION['show_message']);
?>

<?php require_once '../include/header.php'; ?>
<main class="body-container">
    <div class="message-container">
        <div class="msg-info" role="alert">
            <?php echo $showMessage; ?>
        </div>
    </div>
</main>

<?php require_once '../include/footer.php'; ?>
