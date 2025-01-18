<?php

session_start();
require_once  '../include/config.php';
require_once  '../include/db.php';
require_once '../include/input-cleaner.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['logged_in']) && !isset($_SESSION['service_id'])) 
{
    header("Location: " . BASE_URL . "account/login.php");
    exit();
}

$service_id = $_SESSION['service_id']; // Get the service ID session set from service details page

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_review'])) 
{
    $user_id = $_SESSION['user_id'];   
    $service_id = $_SESSION['service_id'];
    $rating = intval($_POST['rating']);
    $comment = sanitizeInput($_POST['comment']);


    // Insert the review into the database with a 'pending' status
    try 
    {
        $stmt = $pdo->prepare("INSERT INTO reviews (service_id, user_id, title, rating, review) VALUES (':service_id', ':user_id','title', ':rating', ':review'");
        $stmt->execute([$service_id, $user_id, $title, $rating, $review]);

        // Redirect to a success page
        header('Location: review-success.php?service_id=' . $service_id);
        exit;
    } 
    catch (Exception $e) 
    {
        // Handle errors (e.g., database issues)
        error_log($e->getMessage());
        echo 'Error submitting your review. Please try again later.';
    }
}

?>










<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="form-container">
        <h1>Write a Review</h1>
        <form action="<?php htmlspecialchars('reviews/submit-review.php', ENT_QUOTES, 'UTF-8'); ?>" method="POST">
            <input type="hidden" name="service_id" value="<?php echo $service_id; ?>">
            <label for="rating">Rating:</label>
            <select name="rating" required>
                <option value="5">5</option>
                <option value="4">4</option>
                <option value="3">3</option>
                <option value="2">2</option>
                <option value="1">1</option>
            </select>

            <label for="comment">Comment:</label>
            <textarea name="comment" cols="50" rows="10" required></textarea>

            <button type="submit" name="send_review">Submit Review</button>
        </form>
    </div>
</main>

<?php require_once '../include/footer.php'; ?>
