<?php

session_start();
require_once '../include/config.php';
require_once '../include/db.php';

// Verify service ID and hash
if (isset($_GET['service_id'], $_GET['review_token'], $_SESSION['expected_slug'])) 
{
    $service_id = intval($_GET['service_id']);
    $hash = $_GET['review_token'];

    // Verify the hash
    $expectedHash = hash_hmac('sha256', $service_id, SECRET_KEY);

    if ($hash !== $expectedHash) 
    {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}

$service_id = $_GET['service_id'];
$expected_slug = $_SESSION['expected_slug'];

// Define pagination variables
$reviews_per_page = 10; // Number of reviews per page
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($current_page - 1) * $reviews_per_page;

try 
{
    // Fetch service title
    $stmt = $pdo->prepare("SELECT title FROM services WHERE id = ?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetch();

    if (!$service) 
    {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }

    // Fetch total number of reviews
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM reviews WHERE service_id = :service_id");
    $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
    $stmt->execute();
    $total_reviews = $stmt->fetchColumn();

    // Calculate total pages
    $total_pages = ceil($total_reviews / $reviews_per_page);

    // Fetch reviews for the current page
    $query = "
        SELECT r.rating, r.comment, r.title, r.created_at, u.first_name, u.last_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.service_id = :service_id
        LIMIT :limit OFFSET :offset";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
    $stmt->bindParam(':limit', $reviews_per_page, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate average rating
    $average_rating = 0;
    if ($total_reviews > 0) 
    {
        $ratings_sum_query = "
            SELECT SUM(rating) AS total_rating FROM reviews WHERE service_id = :service_id";
        $stmt = $pdo->prepare($ratings_sum_query);
        $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
        $stmt->execute();
        $total_rating = $stmt->fetchColumn();
        $average_rating = $total_rating / $total_reviews;
    }

    // Initialize rating count and percentage arrays
    $ratings_count = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
    $ratings_percentage = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

    // Count ratings based on star level
    $rating_count_query = "
        SELECT rating, COUNT(*) AS count 
        FROM reviews 
        WHERE service_id = :service_id 
        GROUP BY rating";
    $stmt = $pdo->prepare($rating_count_query);
    $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
    $stmt->execute();
    $rating_counts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($rating_counts as $rating_count) 
    {
        $ratings_count[$rating_count['rating']] = $rating_count['count'];
    }

    // Calculate percentages
    foreach ($ratings_count as $star => $count) 
    {
        $ratings_percentage[$star] = ($total_reviews > 0) ? ($count / $total_reviews) * 100 : 0;
    }
} 
catch (PDOException $e) 
{
    error_log("Database error: " . $e->getMessage());
    $_SESSION['error'] = 'An error occurred while fetching reviews.';
    header("Location: /500.php");
    exit();
}
?>


<?php require_once '../include/header.php'; ?>

<main class="body-container">
	<section>
		<div class="breadcrumb">
			<h1>Reviews for <?php echo $service['title']; ?></h1>
		    <a href="<?php echo $expected_slug; ?>" style="color: #0000EE;">
		        Back to Service Details
		    </a>
		</div>

	    <!-- Reviews Section -->
	    <div class="s-details reviews-section-container">
	        <!-- Dynamic Star Rating -->
	        <div class="star-container">
	            <h3>Star Total Rating</h3>
	            <div class="star-rating">
	                <p><?php echo round($average_rating, 1); ?> / 5</p>
	                <div class="stars-outer">
	                    <div class="stars-inner"></div>
	                </div>
	                <p class="number-of-review"><?php echo $total_reviews. ' verified ratings'; ?></p>
	            </div>

	            <div class="star-progress-container">
	                <div class="star-progress-row">
	                    <span>5 <i class="fas fa-star"></i></span>
	                    <span>(<?php echo $ratings_count[5] ?? 0; ?>)</span>
	                    <div class="progress-bar">
	                        <div class="progress-fill" style="width: <?php echo $ratings_percentage[5] ?? 0; ?>%;"></div>
	                    </div>
	                </div>
	                <div class="star-progress-row">
	                    <span>4 <i class="fas fa-star"></i></span>
	                    <span>(<?php echo $ratings_count[4] ?? 0; ?>)</span>
	                    <div class="progress-bar">
	                        <div class="progress-fill" style="width: <?php echo $ratings_percentage[4] ?? 0; ?>%;"></div>
	                    </div>
	                </div>
	                <div class="star-progress-row">
	                    <span>3 <i class="fas fa-star"></i></span>
	                    <span>(<?php echo $ratings_count[3] ?? 0; ?>)</span>
	                    <div class="progress-bar">
	                        <div class="progress-fill" style="width: <?php echo $ratings_percentage[3] ?? 0; ?>%;"></div>
	                    </div>
	                </div>
	                <div class="star-progress-row">
	                    <span>2 <i class="fas fa-star"></i></span>
	                    <span>(<?php echo $ratings_count[2] ?? 0; ?>)</span>
	                    <div class="progress-bar">
	                        <div class="progress-fill" style="width: <?php echo $ratings_percentage[2] ?? 0; ?>%;"></div>
	                    </div>
	                </div>
	                <div class="star-progress-row">
	                    <span>1 <i class="fas fa-star"></i></span>
	                    <span>(<?php echo $ratings_count[1] ?? 0; ?>)</span>
	                    <div class="progress-bar">
	                        <div class="progress-fill" style="width: <?php echo $ratings_percentage[1] ?? 0; ?>%;"></div>
	                    </div>
	                </div>
	            </div>
	        </div>

	        <div class="reviews-section">
	            <h3>Review Comments</h3>
	            <?php if ($total_reviews > 0): ?>
	                <?php foreach ($reviews as $review): ?>
	                    <div class="review">
	                        <p class="review-title"><?php echo $review['title']; ?></p>
	                        <p class="review-body"><?php echo $review['comment']; ?></p>
	                        <p> 
	                            <span class="reviewer-star-rating-number">
	                                <?php echo $review['rating']; ?>/5
	                            </span>

	                            <span class="stars">
	                                <?php
	                                // Total number of stars (e.g., 5 stars)
	                                $totalStars = 5;

	                                // Display filled and unfilled stars based on rating
	                                for ($i = 1; $i <= $totalStars; $i++) 
	                                {
	                                    if ($i <= $review['rating']) 
	                                    {
	                                        echo '<i class="fas fa-star filled-star"></i>'; // Filled star
	                                    } 
	                                    else 
	                                    {
	                                        echo '<i class="fas fa-star unfilled-star"></i>'; // Unfilled star with gray gradient fill
	                                    }
	                                }
	                                ?>
	                            </span>
	                        </p>
	                        <p>
	                            <span class="time-rating-posted">
	                                <strong>Posted on:</strong> <?php echo date('F j, Y', strtotime($review['created_at']) ) . ','; ?>
	                            
	                                <?php echo 'by ' . $review['first_name'] . ' ' . $review['last_name']; ?>
	                            </span>
	                        </p>
	                    </div>
	                <?php endforeach; ?>

	            <?php else: ?>
	                <p class="review-body">No reviews yet.</p>
	            <?php endif; ?>
	        </div>

	    </div>

		<!-- Add Pagination Links -->
		<div class="pagination">
		    <?php if ($current_page > 1): ?>
		        <a href="?service_id=<?php echo $service_id; ?>&page=<?php echo $current_page - 1; ?>&review_token=<?php echo $hash; ?>">Previous</a>
		    <?php endif; ?>

		    <?php for ($page = 1; $page <= $total_pages; $page++): ?>
		        <a href="?service_id=<?php echo $service_id; ?>&page=<?php echo $page; ?>&review_token=<?php echo $hash; ?>" 
		           class="<?php echo $page === $current_page ? 'active' : ''; ?>">
		           <?php echo $page; ?>
		        </a>
		    <?php endfor; ?>

		    <?php if ($current_page < $total_pages): ?>
		        <a href="?service_id=<?php echo $service_id; ?>&page=<?php echo $current_page + 1; ?>&review_token=<?php echo $hash; ?>">Next</a>
		    <?php endif; ?>
		</div>
	</section>
</main>

<script>

    // Update star rating dynamically
    window.onload = function () {
        const averageRating = <?php echo $average_rating; ?>; // PHP value
        const starsInner = document.querySelector('.stars-inner');
        
        // Calculate percentage for filled stars
        const ratingPercentage = (averageRating / 5) * 100;
        
        // Apply width to display stars based on the rating
        starsInner.style.width = ratingPercentage + '%';
    };

</script>

<?php require_once '../include/footer.php'; ?>