<?php

session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/input-cleaner.php';

$service_id = $review_token = '';
$errors = [];

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in'])) 
{
    header("Location: " . BASE_URL . "account/login.php");
    exit();
}

// Verify service ID and hash
if (isset($_GET['id'], $_GET['token'])) 
{
    $service_id = intval($_GET['id']);
    $hash = $_GET['token'];
    $user_id = $_SESSION['user_id'];

    // Verify the hash
    $expectedHash = hash_hmac('sha256', $service_id, SECRET_KEY);

    if ($hash !== $expectedHash) 
    {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }

    // Fetch service details and check ownership
    $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();

    if (!$service || $service['user_id'] == $user_id) 
    {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }


    // Check if the user has already written a review for this service
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE service_id = ? AND user_id = ?");
    $stmt->execute([$service_id, $user_id]);
    $existingReviewCount = $stmt->fetchColumn();

    if ($existingReviewCount > 0) 
    {
        $_SESSION['review_message'] = 'You have written review on this service before.';
            header("Location: " . BASE_URL . "reviews/review-message-output.php");
            exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_review'])) 
{
    // Check if the form has already been submitted
    if (isset($_SESSION['review_submitted']) && $_SESSION['review_submitted'] === true) 
    {
        exit();
    }

    // Clean inputs
    $title = sanitizeInput($_POST["title"]);
    $rating = sanitizeInput(intval($_POST['rating']));
    $comment = sanitizeInput($_POST['comment']);

    // Validate inputs
    $errors = validateReview($title, $rating, $comment);

    if (empty($errors)) 
    {
        try 
        {
            // Insert review into the database
            $stmt = $pdo->prepare("INSERT INTO reviews (service_id, user_id, title, rating, comment) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$service_id, $user_id, $title, $rating, $comment]);

            // Set session flag to prevent further submissions
            $_SESSION['review_submitted'] = true;

            $_SESSION['review_message'] = 'Your review has been sent. It is under moderation, and you will receive an email once it is reviewed.';
            header("Location: " . BASE_URL . "reviews/review-message-output.php");
            exit();
        } 
        catch (Exception $e) 
        {
            error_log($e->getMessage());
            echo 'Error submitting your review. Please try again later.';
        }
    }
}

// Function to validate the review
function validateReview($title, $rating, $comment) 
{
    $errors = [];

    if (empty($title)) 
    {
        $errors['title'] = "Title is required.";
    }

    if (empty($rating)) 
    {
        $errors['rating'] = "Rating is required.";
    }

    if (empty($comment)) 
    {
        $errors['comment'] = "Comment is required.";
    }

    return $errors;
}

?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="form-container">
        <h1>Write a Review</h1>
        <form action="" method="POST">
            <!-- Error Message -->
            <div class="form-group error">
                <?php echo isset($errors['review']) ? $errors['review'] : ''; ?>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label for="title">Title:<span class="required">*</span></label>
                <input type="text" name="title" id="title" maxlength="50">
                <div id="title-error" class="error">
                    <?php echo isset($errors['title']) ? $errors['title'] : ''; ?>
                </div>
            </div>

            <!-- Comment -->
            <div class="form-group">
                <label for="comment">Comment:</label>
                <textarea name="comment" cols="50" rows="10"></textarea>
                <div id="comment-error" class="error">
                    <?php echo isset($errors['comment']) ? $errors['comment'] : ''; ?>
                </div>
            </div>

            <!-- Rating -->
            <div class="form-group">
                <label for="rating">Rating:</label>
                <div class="star-rating" id="star-rating">
                    <i class="far fa-star" data-value="1"></i>
                    <i class="far fa-star" data-value="2"></i>
                    <i class="far fa-star" data-value="3"></i>
                    <i class="far fa-star" data-value="4"></i>
                    <i class="far fa-star" data-value="5"></i>
                </div>
                <input type="hidden" name="rating" id="rating-value" value="">
                <div id="rating-error" class="error">
                    <?php echo isset($errors['rating']) ? $errors['rating'] : ''; ?>
                </div>
            </div>

            <button type="submit" name="send_review" class="btn">Submit Review</button>
        </form>
    </div>
</main>

<script>
// JavaScript to handle star rating selection
const stars = document.querySelectorAll('.star-rating i');
const ratingInput = document.getElementById('rating-value');

stars.forEach(star => {
    star.addEventListener('mouseover', () => {
        const value = star.getAttribute('data-value');
        updateStars(value);
    });

    star.addEventListener('click', () => {
        const value = star.getAttribute('data-value');
        ratingInput.value = value; // Store the rating value in the hidden input
    });

    star.addEventListener('mouseout', () => {
        const selectedRating = ratingInput.value;
        updateStars(selectedRating);
    });
});

function updateStars(value) 
{
    stars.forEach(star => {
        if (star.getAttribute('data-value') <= value) {
            star.classList.remove('far');
            star.classList.add('fas');
        } else {
            star.classList.remove('fas');
            star.classList.add('far');
        }
    });
}

</script>

<?php require_once '../include/footer.php'; ?>


