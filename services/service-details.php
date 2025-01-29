<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';

// Check if the user is logged in
$userLoggedIn = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // current logged in user

// Get URL parameters
$service_id = isset($_GET['id']) ? $_GET['id'] : '';
$title_slug = isset($_GET['slug']) ? $_GET['slug'] : '';
$category_slug = isset($_GET['category']) ? $_GET['category'] : '';
$subcategory_slug = isset($_GET['subcategory']) ? $_GET['subcategory'] : '';

// Check if the service is already in favourites on page load
/*$stmt = $pdo->prepare("SELECT id FROM user_favourites WHERE user_id = :user_id AND service_id = :service_id");
$stmt->execute([':user_id' => $userLoggedIn, ':service_id' => $service_id]);
$is_favourite = $stmt->rowCount() > 0;*/


// Get the owner id(advertiser)
$stmt = $pdo->prepare("SELECT user_id FROM services WHERE id = :service_id");
$stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
$stmt->execute();
$service_owner = $stmt->fetch();

// Check if the logged-in user is the owner of the service
$isOwner = ($userLoggedIn && $_SESSION['user_id'] == $service_owner['user_id']);

// Capture the full URL and extract the service ID using the slug pattern
$request_url = $_SERVER['REQUEST_URI'];

// Validate service ID is numeric and greater than 0
if (is_numeric($service_id) && $service_id > 0) 
{
    $hash = hash_hmac('sha256', $service_id, SECRET_KEY);

    try 
    {
        // Query to fetch the service details using the ID
        $query = "
        SELECT s.id, s.slug, s.title, s.price, s.user_id, s.is_negotiable, s.description, s.category_id, 
            s.subcategory_id, c.slug AS category_slug, sc.slug AS subcategory_slug,u.first_name, u.last_name,
            u.phone,u.profile_image, st.name AS state,lg.name AS lga
        FROM services s
        JOIN categories c ON s.category_id = c.id
        JOIN subcategories sc ON s.subcategory_id = sc.id
        JOIN users u ON s.user_id = u.id
        JOIN states st ON s.state_id = st.id
        JOIN lgas lg ON s.lga_id = lg.id
        WHERE s.id = :id";

        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':id', $service_id, PDO::PARAM_INT);
        $stmt->execute();
        $service = $stmt->fetch(PDO::FETCH_ASSOC);

        // If the service exists, load its details
        if ($service) 
        {
            // Use the category and subcategory slugs to ensure correct URL format
            $expected_slug = '/classified/' . $service['category_slug'] . '/' . $service['subcategory_slug'] . '/' . $service['slug'] . '-' . $service['id'] . '.html';//Remove the classified folder online

            $_SESSION['expected_slug'] = $expected_slug;

            // Check if the URL format has changed and redirect to the correct URL (optional)
            if ($request_url !== $expected_slug) 
            {
                header("Location: " . $expected_slug, true, 301);
                exit();
            }

            // Fetch images for the service
            $query = "SELECT * FROM service_images WHERE service_id = :service_id";
            $stmt = $pdo->prepare($query);
            $stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
            $stmt->execute();
            $images = $stmt->fetchAll(PDO::FETCH_ASSOC);


            // Fetch reviews and calculate average rating
            $query = "
                SELECT r.rating, r.comment, r.title, r.created_at, u.first_name, u.last_name
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

            // Check if the user has written a review
            $user_has_reviewed = false; // Default to false
            $review_check_query = "SELECT COUNT(*) AS review_count FROM reviews WHERE service_id = :service_id AND user_id = :user_id";
            $check_stmt = $pdo->prepare($review_check_query);
            $check_stmt->bindParam(':service_id', $service_id, PDO::PARAM_INT);
            $check_stmt->bindParam(':user_id', $userLoggedIn, PDO::PARAM_INT);
            $check_stmt->execute();
            $review_check = $check_stmt->fetch(PDO::FETCH_ASSOC);

            if ($review_check['review_count'] > 0) 
            {
                $user_has_reviewed = true; // User has written a review
            } 
            else 
            {
                $user_has_reviewed = false; // User has not written a review
            }


            // Fetch similar services based on subcategory. First, get the total number of matching rows 
            $total_rows_query = "SELECT COUNT(*) as total FROM services WHERE id != :current_service_id AND subcategory_id = :subcategory_id";
            $stmt = $pdo->prepare($total_rows_query);
            $stmt->execute(
                [':current_service_id' => $service_id, ':subcategory_id' => $service['subcategory_id']]
            );
            $total_rows = $stmt->fetchColumn();

            // Calculate a random offset
            $random_offset = max(0, $total_rows - 10); // Ensure offset doesn't exceed available rows

            // If $total_rows is smaller than 10, we don't need an offset, just use it directly
            $random_start = ($random_offset > 0) ? rand(0, $random_offset) : 0;

            // Fetch similar ads with random offset
            $similar_ads_query = "
                SELECT s.id, s.title, s.slug, s.price, s.created_at, s.is_negotiable AS negotiable, si.image_path 
                FROM services s
                LEFT JOIN service_images si ON s.id = si.service_id AND si.is_featured = 1
                WHERE s.id != :current_service_id AND s.subcategory_id = :subcategory_id
                LIMIT :start, 10";
            $stmt = $pdo->prepare($similar_ads_query);
            $stmt->bindValue(':current_service_id', $service_id, PDO::PARAM_INT);
            $stmt->bindValue(':subcategory_id', $service['subcategory_id'], PDO::PARAM_INT);
            $stmt->bindValue(':start', $random_start, PDO::PARAM_INT);
            $stmt->execute();
            $similar_ads = $stmt->fetchAll(PDO::FETCH_ASSOC);


            // Fetch more ads by the same advertiser excluding the current ad
            $currentSlug = isset($_GET['slug']) ? trim($_GET['slug']) : ''; //Title slug for the current ad 

            // Fetch current ad details using slug
            $query = "SELECT id, user_id, category_id FROM services WHERE slug = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute([$currentSlug]);
            $currentAd = $stmt->fetch();

            if (!$currentAd) 
            {
                header("Location: " . BASE_URL . "index.php");
                exit();
            }

            $userId = $currentAd['user_id'];
            $currentAdId = $currentAd['id'];

            
            $sql = "SELECT s.is_negotiable AS negotiable, s.id, s.title,s.description, s.slug, s.price, 
            s.created_at, si.image_path, c.slug AS category_slug, sc.slug AS subcategory_slug
            FROM services s
            LEFT JOIN service_images si ON s.id = si.service_id AND si.is_featured = 1
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN subcategories sc ON s.subcategory_id = sc.id
            WHERE s.user_id = ? AND s.id != ? 
            ORDER BY s.created_at DESC 
            LIMIT 6";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([$userId, $currentAdId]);
            $moreAds = $stmt->fetchAll();


           // Checking the number of ad views for the current ad
            function getUserIP() 
            {
                if (!empty($_SERVER['HTTP_CLIENT_IP'])) 
                {
                    return $_SERVER['HTTP_CLIENT_IP']; // IP from shared internet
                } 
                elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) 
                {
                    return $_SERVER['HTTP_X_FORWARDED_FOR']; // IP from a proxy
                } 
                else 
                {
                    return $_SERVER['REMOTE_ADDR']; // IP from the remote address
                }
            }

            $ip_address = getUserIP();

            $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null; // Current logged-in user

            // Check if the view is unique within the last hour
            $checkQuery = "SELECT id, last_viewed FROM ad_views 
                           WHERE service_id = :service_id 
                           AND (user_id = :user_id OR ip_address = :ip_address) 
                           AND last_viewed > DATE_SUB(NOW(), INTERVAL 1 HOUR)";

            $stmt = $pdo->prepare($checkQuery);
            $stmt->execute([':service_id' => $service_id,':user_id' => $user_id,':ip_address' => $ip_address
            ]);

            // If no recent view exists, insert a new view
            if ($stmt->rowCount() == 0) 
            {
                $insertQuery = "INSERT INTO ad_views (service_id, user_id, ip_address) 
                                VALUES (:service_id, :user_id, :ip_address)";
                $stmt = $pdo->prepare($insertQuery);
                $stmt->execute([':service_id' => $service_id,':user_id' => $user_id,':ip_address' => 
                    $ip_address]);
            } 
            else 
            {
                // Update the last viewed timestamp if the interval is greater than 1 hour
                $viewData = $stmt->fetch(PDO::FETCH_ASSOC);
                if (strtotime($viewData['last_viewed']) <= strtotime('-1 hour')) 
                {
                    $updateQuery = "UPDATE ad_views 
                                    SET view_count = view_count + 1, 
                                        last_viewed = NOW() 
                                    WHERE service_id = :service_id 
                                    AND (user_id = :user_id OR ip_address = :ip_address)";
                    $stmt = $pdo->prepare($updateQuery);
                    $stmt->execute([':service_id' => $service_id,':user_id' => $user_id,':ip_address' => 
                        $ip_address]);
                }
            }

            // Fetch total views to display on UI
            $totalViewsQuery = "SELECT SUM(view_count) AS total_views 
                                FROM ad_views 
                                WHERE service_id = :service_id";

            $stmt = $pdo->prepare($totalViewsQuery);
            $stmt->execute([':service_id' => $service_id]);

            // Fetch the total views
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalViews = $result['total_views'] ?? 0; // Default to 0 if no views found


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

<main class="body-container">

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

    <section>
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

                

                <div class="s-details service-title">
                    <h1><?php echo $service['title']; ?></h1>
                </div>

                <div class="s-details service-description">
                    <h2>Description</h2>
                    <p><?php echo $service['description']; ?></p>
                </div>

                    <!--<div class="favourite-icon" id="favourite-icon-container" >
                        <form action="" enctype="multipart/form-data" method="POST" id="favourite-form">
                            <input type="hidden" name="service_id" value="<?php //echo $service_id; ?>">
                            <button type="submit" id="toggle-favourite-btn" style="background: none; border: none; cursor: pointer;">
                                <?php //if ($is_favourite): ?>
                                    <i class="fas fa-heart" style="color: red;"></i>
                                <?php //else: ?>
                                    <i class="far fa-heart" style="color: gray;"></i>
                                <?php //endif; ?>
                            </button>
                        </form>
                    </div>-->

                <div class="s-details price-negotiable">
                    <h2>
                        <?php echo CURRENCY_TYPE_SYMBOLE . ' ' . number_format($service['price'], 2); ?>
                    </h2>
                    <?php if ($service['is_negotiable'] == 'yes'): ?>
                            <p class="negotiable yes"><i class="fas fa-tag"></i> Negotiable</p>
                    <?php else: ?>
                            <p class="negotiable no"><i class="fas fa-tag"></i> Not Negotiable</p>
                    <?php endif; ?>
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

                            <div class="rating-all-comments">
                                <a href="<?php echo BASE_URL; ?>reviews/review-comments.php?service_id=<?php echo 
                                $service_id .'&review_token=' . $hash; ?>">
                                    View All
                                </a>
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
                        <img src="<?php echo BASE_URL; ?>images/default-profile.jpg" alt="Advertiser profile Picture">
                    <?php endif; ?>

                    <p>
                        <!-- Show Contact Button -->
                        <button class="phone-contact" id="show-contact-button" onclick="showPhoneNumber()">
                            <i class="fas fa-phone-alt"></i> Show Contact
                        </button>

                        <!-- Phone Number Button (Initially Hidden) -->
                        <button class="phone-contact" id="phone-contact-button" style="display: none;">
                            <a href="tel:<?php echo $service['phone']; ?>" class="phone-contact" id="phone-contact-anchor">
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
                    <form method="POST" id="chat-form">
                        <p id="service-chat-msg" style="display: none;">Message sent</p>
                        <textarea placeholder="Message the advertiser..." id="chat-message"></textarea>
                        <button type="submit" name="chat" id="chat">Send Chat</button>
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
                        <a href="<?php echo BASE_URL; ?>report-services/report-service.php?id=<?php echo 
                                $service_id .'&token=' . $hash; ?>">
                            <i class="fas fa-flag"></i>Report Abuse
                        </a>
                        <a href="<?php echo BASE_URL; ?>services/post-service.php">
                            <i class="fas fa-plus-circle"></i>Post Your Ads
                        </a>
                    </p>
                    <p class="stat-counter view-counter">
                        <i class="fas fa-eye" title="Total Views"></i><?php echo $totalViews; ?>
                    </p>
                </div>

                <!-- Show "Write a Review" link for other users that are logged in, excluding the ads owner-->
                <?php if ($userLoggedIn && !$isOwner && !$user_has_reviewed): ?>
                    <div class="s-details">
                        <p class="write-review-link">
                            <a href="<?php echo BASE_URL; ?>reviews/write-review.php?id=<?php echo 
                                $service_id .'&token=' . $hash; ?>">Write a Review
                            </a>
                        </p>
                    </div>
                <?php elseif (!$userLoggedIn): ?>
                    <!-- If the user is not logged in, show the "Write a Review" link -->
                    <div class="s-details">
                        <p class="write-review-link">
                            <a href="<?php echo BASE_URL; ?>reviews/write-review.php?id=<?php 
                                echo $service_id .'&token=' . $hash; ?>">Write a Review
                            </a>
                        </p>
                    </div>
                <?php endif; ?>

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
    </section>

    
    <!-- Similar Adverts Section -->
    <?php if (!empty($similar_ads)): ?>
    <section class="featured-section">
        <h2>Similar Services</h2>
        <div class="services-container">
            <?php foreach ($similar_ads as $similar_ad): ?>
                <div class="service-card">
                    <a href="<?php echo BASE_URL . $service['category_slug'] . '/' . $service['subcategory_slug'] . '/' . $similar_ad['slug'] . '-' . $similar_ad['id'] . '.html'; ?>">
                        <!-- Featured Image -->
                        <div class="service-img">
                            <img src="<?php echo BASE_URL . 'uploads/services-images/' . ($similar_ad['image_path'] ?: 'default-image.jpg'); ?>" 
                                 alt="<?php echo $similar_ad['title']; ?>">
                        </div>
                        
                        <div class="service-text">
                            <h3><?php echo substr($similar_ad['title'], 0, 24); ?>...</h3>
                            <p class="price">
                                <?php echo $similar_ad['price'] ? CURRENCY_TYPE_SYMBOLE . number_format(
                                    $similar_ad['price'], 2) : 'Price on Request'; ?>
                            </p>
                            <?php if ($similar_ad['negotiable'] == 'yes'): ?>
                                <p class="negotiable yes">Negotiable</p>
                            <?php else: ?>
                                <p class="negotiable no">Not Negotiable</p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Ads from the same advertiser -->
    <?php if (!empty($moreAds)): ?>
    <section>
        <h2>More Ads from This Advertiser</h2>
        <div class="ad-list">
            <?php foreach ($moreAds as $ad): ?>
                <div class="ad-item">
                    <a href="<?php echo BASE_URL . $ad['category_slug'] . '/' . $ad['subcategory_slug'] . '/' . $ad['slug'] . '-' . $ad['id'] . '.html'; ?>">
                        <div class="ad-list-img">
                            <img src="<?= BASE_URL . 'uploads/services-images/' . $ad['image_path']; ?>" 
                                 alt="<?= $ad['title']; ?>">
                        </div>
                        <div class="ad-details">
                            <h3><?php echo substr($ad['title'], 0, 25); ?>...</h3>

                            <p class="price">
                                <?php echo $ad['price'] ? CURRENCY_TYPE_SYMBOLE . number_format($ad['price'], 2) : 'Price on Request'; ?>
                            </p>

                            <p><?php echo substr($ad['description'], 0, 40); ?>...</p>

                            <?php if ($ad['negotiable'] == 'yes'): ?>
                                <p class="negotiable yes">Negotiable</p>
                            <?php else: ?>
                                <p class="negotiable no">Not Negotiable</p>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

</main>

<script>
    // Add to favourite ajax
    /*document.addEventListener("DOMContentLoaded", function () {

        const toggleButtons = document.getElementById("toggle-favourite-btn");
        favouriteForm.addEventListener('submit', function(e){
            e.preventDefault();
            //alert(789);
        });
        const toggleButtons = document.getElementById("toggle-favourite-btn");
        const favouriteForm = document.getElementById("favourite-form");
        toggleButtons.addEventListener('click', function (e){
            e.preventDefault();
            favouriteForm.submit();
        });
    });*/





document.getElementById('chat-form').addEventListener('submit', function(event) {
    event.preventDefault();

    const message = document.getElementById('chat-message').value.trim();

    const formData = new FormData();
    //formData.append('chat', true);
    formData.append('message', message);
    
    // Send the request using fetch
    fetch('process-chat.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Handle success
        if (data.success) {
            alert(data.message);
        } 
        else 
        {
            alert('data.message');
        }
    })
    .catch(error => {
        alert(error);
    });
});











    function changeImage(src)
    {
        document.getElementById('featured-image').src = src;
    }

    // Update star rating dynamically
    window.onload = function () {
        const averageRating = <?php echo $average_rating; ?>;
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


    // JavaScript function to toggle visibility of phone number
    function showPhoneNumber() 
    {
        // Hide the "Show Contact" button
        document.getElementById('show-contact-button').style.display = 'none';

        // Show the button with the phone number
        document.getElementById('phone-contact-button').style.display = 'inline-block';
    }

</script>

<?php include '../include/footer.php'; ?>
