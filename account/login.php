<?php
require_once '../include/config.php';
require_once '../include/db.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user from database
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->execute(['username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['is_premium'] = $user['is_premium'];
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid credentials']);
    }
    exit;
}


?>

<?php require_once '../include/header.php'; ?>

<div class="form-container">
    <h1>Login</h1>
    <form method="POST" enctype="multipart/form-data">
        <!-- Email Input -->
        <div class="form-group">
            <label for="login_email">Email: <span class="required">*</span></label>
            <input type="text" name="login_email" id="login_email" placeholder="Enter email" required>
        </div>

        <!-- Password Input -->
        <div class="form-group">
            <label for="login_password">Password: <span class="required">*</span></label>
            <input type="password" name="login_password" id="login_password" placeholder="Enter password" required>
        </div>

        <!-- Remember Me Checkbox -->
        <div class="form-group remember-me">
            <input type="checkbox" name="remember_me" id="remember_me">
            <label for="remember_me">Remember Me</label>
        </div>

        <!-- Submit Button -->
        <div class="form-group">
            <button type="submit" name="login_submit" id="login_submit">Submit</button>
        </div>

        <!-- Additional Links -->
        <div class="form-links">
            <a href="<?php echo BASE_URL; ?>account/forgot-password.php">Forgot Password?</a>
            <span> | </span>
            <a href="<?php echo BASE_URL; ?>account/register.php">Register</a>
        </div>
    </form>
</div>

<?php require_once '../include/footer.php'; ?>
