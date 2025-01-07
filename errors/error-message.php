<?php

    $error = $_SESSION['error'] ? $_SESSION['error'] : "" ;
?>

<?php require_once '../include/header.php'; ?>

<div class="message-container">
    <div class="msg-info" role="alert">
        <h4>Invalid request</h4>
        <p><?php echo $error; ?></p>
    </div>
</div>

// Clear session variables after displaying the message
//unset($_SESSION['error']);

<?php require_once '../include/footer.php'; ?>
