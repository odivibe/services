<main class="body-container">
    <div class="form-container">
        <h1>Change Password</h1>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div id="csrf_token_error" class="error">
                    <?php echo $errorMessages['csrf_token']; ?>
                </div>
            </div>
            
            <!-- Old Password -->
            <div class="form-group">
                <label for="change_password">Old Password:</label>
                <input type="password" name="change_password" id="change_password" placeholder="Enter old password" required>
            </div>

            <!-- New Password-->
            <div class="form-group">
                <label for="change_new_password">New Password:</label>
                <input type="password" name="change_new_password" id="change_new_password" placeholder="Enter new password" required>
            </div>

            <!-- Confirm Password -->
            <div class="form-group">
                <label for="change_confirm_password">Confirm Password:</label>
                <input type="password" name="change_confirm_password" id="change_confirm_password" placeholder="Confirm password" required>
            </div>

            <!-- Submit -->
            <div class="form-group">
                <button type="submit" name="change_password_submit" id="change_password_submit">Submit</button>
            </div>
        </form>
    </div>
</main>


