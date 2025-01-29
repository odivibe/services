<?php 

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/input-cleaner.php';

if (!isset($_SESSION['user_id'])) 
{
    header('Location:' . BASE_URL . 'account/login.php');
}

$user_id = $_SESSION['user_id'];



$errors[] = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_submit'])) 
{
    $email = strtolower(sanitizeInput($_POST['change_email']));

    if (empty($email)) 
    {
        $errors['email'] = 'Email is required';
    }

    if (empty($errors)) 
    {
        $query = "SELECT email FROM users WHERE email = :email";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) 
        {
            echo 'email exist';
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>
<main class="body-container">
    <div class="form-container">
        <h1>Change Email</h1>
        <form method="POST" enctype="multipart/form-data">
            <!-- Email -->
            <div class="form-group">
                <label for="change_email">New Email:</label>
                <input type="text" name="change_email" id="change_email" placeholder="Enter new email" required>
            </div>

            <!-- Submit -->
            <div class="form-group">
                <button type="submit" name="change_submit" id="change_submit">Submit</button>
            </div>
        </form>
    </div>
</main>
<?php require_once '../include/footer.php' ?>

