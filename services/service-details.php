<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';

$userLoggedIn = isset($_SESSION['user_id']); // current logged in user

// Get URL parameters
$service_id = isset($_GET['id']) ? $_GET['id'] : '';
$title_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';
$subcategory_slug = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';

// Get the owner id(advertiser)
$stmt = $pdo->prepare("SELECT user_id FROM services WHERE id = :service_id");
$stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
$stmt->execute();
$service_owner = $stmt->fetch();

// Check if the logged-in user is the owner of the service
$isOwner = ($userLoggedIn && $_SESSION['user_id'] == $service_owner['user_id']);

$_SESSION['service_id'] = $service_id;

// Capture the full URL and extract the service ID using the slug pattern
$request_url = $_SERVER['REQUEST_URI'];

// Validate service ID is numeric and greater than 0
if (is_numeric($service_id) && $service_id > 0) 
{
    try 
    {
        // Query to fetch the service details using the ID
        $query = "
        SELECT 
            s.id, 
            s.slug, 
            s.title, 
            s.price, 
            s.user_id, 
            s.is_negotiable, 
            s.description, 
            c.slug AS category_slug, 
            sc.slug AS subcategory_slug,
            u.first_name, 
            u.last_name, 
            u.phone,
            u.profile_image,
            st.name AS state,
            lg.name AS lga
        FROM 
            services s
        JOIN 
            categories c ON s.category_id = c.id
        JOIN 
            subcategories sc ON s.subcategory_id = sc.id
        JOIN 
            users u ON s.user_id = u.id
        JOIN 
            states st ON s.state_id = st.id
        JOIN 
            lgas lg ON s.lga_id = lg.id
        WHERE s.id = :id";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $service_id, PDO::PARAM_INT);
        //$stmt->bindParam(':title_slug', $title_slug, PDO::PARAM_INT);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the service exists, load its details
        if ($service) 
        {
            // Use the category and subcategory slugs to ensure correct URL format
            $expected_slug = $service['category_slug'] . '/' . $service['subcategory_slug'] . '/' . $service['slug'] . '-' . $service['id'] . '.html';


            // Check if the URL format has changed and redirect to the correct URL (optional)
            /*if ($request_url !== BASE_URL . $expected_slug) 
            {
                header("Location: " . BASE_URL . $expected_slug, true, 301);
                exit();
            }


            if ($service['id'] !== $service_id) 
            {
                header("Location: " . BASE_URL . $expected_slug, true, 301);
                exit();
            }*/



            // Fetch images for the service
            $query = "SELECT * FROM service_images WHERE service_id = :service_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);



            // Fetch reviews and calculate average rating
            $query = "
                SELECT r.rating, r.review, r.title, r.created_at, u.first_name, u.last_name
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.service_id = :service_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
            $stmt->execute();
            $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Calculate average rating
            $total_reviews = count($reviews);
            $average_rating = 0;
            if ($total_reviews > 0) 
            {
                $total_rating = array_column($reviews, 'rating'); // get a column called rating from database result
                $total_rating = array_sum($total_rating); // sum it
                $average_rating = $total_rating / $total_reviews;
            }

            // Initialize rating count and percentage arrays
            $ratings_count = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];
            $ratings_percentage = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0];

            // Count ratings based on star level
            foreach ($reviews as $review) 
            {
                $ratings_count[$review['rating']]++;
            }

            // Calculate percentages
            foreach ($ratings_count as $star => $count) 
            {
                $ratings_percentage[$star] = ($total_reviews > 0) ? ($count / $total_reviews) * 100 : 0;
            }



            // Fetch similar adverts based on the same subcategory for similar services
            try 
            {
                $query = "
                    SELECT id, title, slug, price, is_negotiable
                    FROM services
                    WHERE subcategory_id = :subcategory_id
                      AND id != :current_service_id
                    ORDER BY RAND() -- Random order for variety
                    LIMIT 10"; // Show up to 10 similar adverts

                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':subcategory_id', $service['subcategory_slug'], PDO::PARAM_STR);
                $stmt->bindParam(':current_service_id', $service_id, PDO::PARAM_INT);
                $stmt->execute();
                $similar_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } 
            catch (PDOException $e) 
            {
                error_log("Database error: " . $e->getMessage());
                $similar_ads = [];
            }




            //Fetch other ads from the same advertiser
            // Get the current slug of the ads
            $currentSlug = isset($_GET['slug']) ? trim($_GET['slug']) : '';

            // Fetch current ad details using slug
            $query = "SELECT id, user_id, category_id FROM services WHERE slug = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$currentSlug]);
            $currentAd = $stmt->fetch();

            if (!$currentAd) 
            {
                // Redirect or show 404 if ad not found
                header("Location: /404.html");
                exit;
            }

            $userId = $currentAd['user_id'];
            $currentAdId = $currentAd['id'];

            // Fetch more ads by the same advertiser excluding the current ad
            $sql = "SELECT id, title, slug, price, created_at 
                    FROM services 
                    WHERE user_id = ? AND id != ? 
                    ORDER BY created_at DESC LIMIT 5";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $currentAdId]);
            $moreAds = $stmt->fetchAll();

        } 
        else 
        {
            // If no service is found with this ID, show 404 page
            http_response_code(404);
            require_once '../include/header.php';
            require_once '../errors/404.php';
            require_once '../include/footer.php';
            exit();
        }
    } 
    catch (PDOException $e) 
    {
        error_log("Database error: " . $e->getMessage());
        http_response_code(500);
        require_once '../include/header.php';
        require_once '../include/500.php';
        require_once '../include/footer.php';
        exit();
    }
} 
else 
{
    // Invalid ID in the URL
    http_response_code(404);
    require_once '../include/header.php';
    require_once '../errors/404.php';
    require_once '../include/footer.php';
    exit();
}

?>

<?php include '../include/header.php'; ?>


<section class="service-details">




    <div class="breadcrumb">
        <div class="breadcrumb-container">
            <!-- Home Link -->
            <a href="<?php echo BASE_URL; ?>index.php">Home</a> &gt;

            <!-- Category Link -->
            <a href="<?php echo BASE_URL . $service['category_slug']; ?>">
                <?php echo ucwords(str_replace('-', ' ', $service['category_slug'])); ?>
            </a> &gt;

            <!-- Subcategory Link -->
            <a href="<?php echo BASE_URL . $service['category_slug'] . '/' . $service['subcategory_slug']; ?>">
                <?php echo ucwords(str_replace('-', ' ', $service['subcategory_slug'])); ?>
            </a> &gt;

            <!-- Current Service Title -->
            <span><?php echo $service['title']; ?></span>
        </div>
    </div>




    <div class="service-details-container">
        <!-- Left Container -->
        <div class="left-container">

            <div class="main-image">
                <?php if (!empty($images)): ?>
                    <img id="featured-image" src="<?php echo BASE_URL . 'uploads/services-images/' . $images[0]['image_path']; ?>" alt="Main image">
                <?php endif; ?>
            </div>

            <div class="thumbnail-gallery">
                
                <?php foreach ($images as $image): ?>
                    <div class="thumbnail-item">
                        <img src="<?php echo BASE_URL . 'uploads/services-images/' . $image['image_path']; ?>" alt="Thumbnail" onclick="changeImage(this.src)">
                    </div>
                <?php endforeach; ?>
                
            </div>
            <div class="next-prev">
                <button class="prev-btn" onclick="changeImageByIndex('prev')"> < </button>
                <button class="next-btn" onclick="changeImageByIndex('next')"> > </button>
            </div>

            <div class="s-details service-details-info">
                <div class="title-description">
                    <h1><?php echo $service['title']; ?></h1>
                    <hr>
                    <h2>Description</h2>
                    <p><?php echo $service['description']; ?></p>
                    <hr>
                </div>
                
                <div class="price-negotiable">
                    <h2>
                        <?php echo CURRENCY_TYPE_SYMBOLE . ' ' . number_format($service['price'], 2); ?>
                    </h2>
                    <p> 
                        <?php $service['is_negotiable'] ? 'yes' : 'no'; ?>
                        <i class="fas fa-handshake"></i>
                        <?php echo $service['is_negotiable'] ? 'Negotiable' : 'Not Negotiable'; ?>
                    </p>
                </div>
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
                                <p class="review-body"><?php echo $review['review']; ?></p>
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
                        <div class="rating-all-comments">
                            <a href="<?php echo BASE_URL; ?>services/view-comments.php">
                                View All
                            </a>

                            <!-- write review link -->
                            <?php if ($userLoggedIn && !$isOwner): ?>
                                <a href="<?php echo BASE_URL; ?>reviews/write-review.php?service_id=<?php echo 
                                $service_id; ?>">
                                    Write a Review
                                </a>
                            <?php else: ?>
                                <?php if (!$userLoggedIn): ?>
                                    <a href="<?php echo BASE_URL; ?>reviews/write-review.php?service_id=<?php echo 
                                    $service_id; ?>">
                                        Write a Review
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>

                        </div>
                    <?php else: ?>
                        <p class="review-body">No reviews yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>


        <!-- Right Container -->
        <div class="right-container">

            <div class="s-details advertiser-info">
                <h3>Advertiser Info</h3>

                <?php if (isset($_SESSION['logged_in']) && isset($_SESSION['user_id'])): ?>
                    <img src="<?php echo BASE_URL . 'uploads/profile-images/' . $service['profile_image']; ?>" alt="Advertiser profile Picture">
                <?php else: ?>
                    <img src="<?php echo BASE_URL; ?>images/profile-image.jpg" alt="Advertiser profile Picture">
                <?php endif; ?>









                <p>
                    <button class="phone-contact" id="service-phone-contact-button">
                        <i class="fas fa-phone-alt"></i>Show Contact
                    </button>

                    <button class="phone-contact" id="service-phone-contact-button">
                        <a tel="<?php echo $service['phone']; ?>" class="phone-contact" 
                        id="service-phone-contact-anchor">
                            <?php echo $service['phone']; ?>
                        </a>
                    </button>
                </p>
                <p>
                    <i class="fas fa-user" title="Advertiser Name"></i>
                    <?php echo $service['first_name'] . ' ' . $service['last_name']; ?>
                    
                </p>
                <p>
                    <i class="fas fa-map-marker-alt" title="Location"></i> 
                    <?php echo $service['state']  . ', ' . $service['lga']; ?>
                </p>
            </div>

            <div class="s-details one-to-one-chat">
                <form>
                    <p id="service-chat-msg">Message sent</p>
                    <textarea placeholder="Message the advertiser..."></textarea>
                    <button type="submit">Send Chat</button>
                </form>
            </div>
            
            <div class="s-details safty-tips">
                <h3>Safety Tips</h3>
                <ul>
                    <li>Meet in public places</li>
                    <li>Avoid advance payments</li>
                    <li>Verify the service before payment</li>
                    <li>When you are indoubt consult professionals in the field</li>
                </ul>
            </div>
            
            <div class="s-details ads-statistics">
                <p>
                    <a href="#"><i class="fas fa-flag"></i>Report Abuse</a>
                    <a href="#"><i class="fas fa-plus-circle"></i>Post Your Ads</a>
                </p>
                <p class="stat-counter view-counter"><i class="fas fa-eye" title="Views"></i>1234</p>
                <p class="stat-counter like-counter"><i class="fas fa-thumbs-up" title="Likes"></i>567</p>
            </div>

            <div class="s-details">
                <h3 class="socials-header">Share on socials</h3>
                <div class="share-on-socials">
                    <a href="#">Facebook</a>
                    <a href="#">Instagram</a>
                    <a href="#">Twitter</a>
                    <a href="#">Whatsapp</a>
                    <a href="#">Email</a>
                </div>
            </div>
        </div>
    </div>








    <!-- Similar Adverts Section -->
    <div class="similar-adverts">
        <h2>Similar Adverts</h2>
        <div class="similar-adverts-container">

            <?php if (!empty($similar_ads)): ?>
                <?php foreach ($similar_ads as $ad): ?>
                    <div class="similar-ad-item">
                        <a href="<?php echo BASE_URL . $service['category_slug'] . '/' . $service['subcategory_slug'] . '/' . $ad['slug'] . '-' . $ad['id'] . '.html'; ?>">
                            <div class="similar-ad-info">
                                <h3><?php echo $ad['title']; ?></h3>
                                <p>
                                    <?php echo CURRENCY_TYPE_SYMBOLE . ' ' . number_format($ad['price'], 2); ?>
                                    <?php echo $ad['is_negotiable'] ? 'Negotiable' : 'Not Negotiable'; ?>
                                </p>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No similar adverts available.</p>
            <?php endif; ?>

        </div>
    </div>






<!-- Ads from the same advertiser -->
<div class="more-ads-section">
    <h3>More Ads from This Advertiser</h3>
    <ul class="more-ads-list">
        <?php foreach ($moreAds as $ad): ?>
            <li>
                <!-- Slug-based URL -->
                <a href="<?php echo BASE_URL; ?>services/service-details/<?= $ad['slug']; ?>.html">
                    <div class="ad-item">
                        <h4><?= htmlspecialchars($ad['title']); ?></h4>
                        <p>Price: $<?= number_format($ad['price'], 2); ?></p>
                        <small>Posted on: <?= date('d M Y', strtotime($ad['created_at'])); ?></small>
                    </div>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>









</section>

<script>
    function changeImage(src)
    {
        document.getElementById('featured-image').src = src;

    }


    // Update star rating dynamically
    window.onload = function () {
        const averageRating = <?php echo $average_rating; ?>; // PHP value
        const starsInner = document.querySelector('.stars-inner');
        
        // Calculate percentage for filled stars
        const ratingPercentage = (averageRating / 5) * 100;
        
        // Apply width to display stars based on the rating
        starsInner.style.width = ratingPercentage + '%';
    };


    // Function to scroll the gallery horizontally by a set amount
    function changeImageByIndex(direction) 
    {
        const gallery = document.querySelector('.thumbnail-gallery');
        const itemWidth = document.querySelector('.thumbnail-item').offsetWidth + 10; // Item width + margin
        const scrollAmount = itemWidth * 5; // Scroll by the width of 5 items (visible at once)

        if (direction === 'prev') 
        {
            gallery.scrollLeft -= scrollAmount; // Scroll left by 5 items
            //alert(1)
        } 
        else if (direction === 'next') 
        {
            gallery.scrollLeft += scrollAmount; // Scroll right by 5 items
            //alert(2)
        }
    }



</script>

<?php include '../include/footer.php'; ?>
