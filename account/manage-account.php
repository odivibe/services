<?php
require_once  '../include/config.php';
require_once  '../include/db.php';
require_once '../include/header.php';

$isPremiumUser = 1;
 ?>

    <div id="manage-account">
        <div class="sidebar">
            <a href="#" data-page="change-email">Change Email</a>
            <a href="#" data-page="change-password">Change Password</a>
            <a href="#" data-page="change-profile-image">Change Profile Image</a>
            <a href="#" data-page="update-phone">Update Phone</a>
            <?php if ( isset($isPremiumUser) && $isPremiumUser == 1) : ?>
                <a href="#" data-page="add-socials">Add socials</a>
            <?php endif; ?>
        </div>
        <div id="sidebar-display-area">
            <h2>Welcome to Your Account</h2>
            <p>Manage your account here.</p>
        </div>
    </div>

<?php require_once '../include/footer.php'; ?>
