<?php
session_start();
require_once '../include/config.php';

    $showMessage = isset($_SESSION['show_message'] )? $_SESSION['show_message'] : 'An unknown error occurred.' ;
    unset($_SESSION['show_message']);
?>

<?php require_once '../include/header.php'; ?>

<div class="message-container">
    <div class="msg-info" role="alert">
        <p><?php echo $showMessage; ?></p>
    </div>
</div>

<?php 

// Clear session variables after displaying the message
//unset($_SESSION['show_message']);

require_once '../include/footer.php'; 

?>
