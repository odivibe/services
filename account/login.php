<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login_submit'])) {
    $email = $_POST['login_email'];
    $password = $_POST['login_password'];
    
    // Fetch user from database
    $stmt = $pdo->prepare("SELECT email, id, password, email_verified FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $userResult = $stmt->fetch();

    if (!$userResult) 
    {
        $error = 'Email does not exist';
    } 
    else 
    {
        if ($userResult['email_verified'] !== 1) 
        {
            $_SESSION['user_id'] = $userResult['id'];
            header("Location: " . BASE_URL . "account/resend-verification-email.php");
            exit();
        }

        if (!password_verify($password, $userResult['password'])) 
        {
            $error = 'Email/Password not correct';
        }

        if ($error == '') 
        {
            // Session management
            session_regenerate_id(true);  // Regenerate session ID after successful login
            $_SESSION['user_id'] = $userResult['id'];

            // Handle Remember Me functionality
            /*if (isset($_POST['remember_me']) && $_POST['remember_me'] == 'on') 
            {
                setcookie('user_id', $userResult['id'], time() + (30 * 24 * 60 * 60), '/');  // 30 days cookie
                setcookie('user_email', $userResult['email'], time() + (30 * 24 * 60 * 60), '/');
            }*/

            header("Location: " . BASE_URL . "index.php");
            exit();
        }
    }
}
?>

<?php require_once '../include/header.php'; ?>

<div class="form-container">
    <h1>Login</h1>
    <form method="POST" enctype="multipart/form-data">

        <div class="form-group error">
            <?php echo $error; ?>
        </div>

        <div class="form-group">
            <label for="login_email">Email: <span class="required">*</span></label>
            <input type="text" name="login_email" id="login_email" placeholder="Enter email" required>
        </div>

        <div class="form-group">
            <label for="login_password">Password: <span class="required">*</span></label>
            <input type="password" name="login_password" id="login_password" placeholder="Enter password" required>
        </div>

        <div class="form-group remember-me">
            <input type="checkbox" name="remember_me" id="remember_me">
            <label for="remember_me">Remember Me</label>
        </div>

        <div class="form-group">
            <button type="submit" name="login_submit" id="login_submit">Submit</button>
        </div>

        <div class="form-links">
            <a href="<?php echo BASE_URL; ?>account/forgot-password.php">Forgot Password?</a>
            <span> | </span>
            <a href="<?php echo BASE_URL; ?>account/register.php">Register</a>
        </div>

    </form>
</div>

<?php require_once '../include/footer.php'; ?>
