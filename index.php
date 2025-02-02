<?php

session_start();

require_once  'include/config.php';
require_once  'include/db.php';

// Check if the user is logged in
//$isLoggedIn = isset($_SESSION['user_id']);

// fetch categories
$query = "SELECT * FROM categories";
$stmt = $pdo->prepare($query);
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// fetch services
$sql = "SELECT s.id, s.title, s.description, s.slug, s.category_id, s.subcategory_id, s.price, s.is_negotiable, s.status, i.image_path 
        FROM services s
        LEFT JOIN service_images i ON s.id = i.service_id AND i.is_featured = 1
        WHERE s.status = 'approved'
        ORDER BY s.created_at DESC
        LIMIT 8";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

//Dynamic meta tag variables
$metaTitle = "Find Services Near You - Explore Categories of Service Ads | Trusted Providers";
$metaDescription = "Discover trusted service providers through categorized service ads. Browse home repairs, cleaning, legal, and business services.";
$metaKeywords = "service ads, classified ads, local services, business services, home repairs, cleaning services, legal services, trusted service providers";
$metaImage = "https://www.example.com/images/home-thumbnail.jpg";
$siteName = SITE_NAME;
$siteUrl = BASE_URL;
$searchTarget = BASE_URL . "search.php?query={search_term_string}";

?>

<?php require_once 'include/header.php'; ?>


    <div class="hero-section">
        <div class="hero-image">
            <img src="<?php echo BASE_URL; ?>images/skill-worker.jpg" alt="People Collaborating on Services">
        </div>
        
        <!-- Content Wrapper -->
        <div class="hero-overlay">
            <!-- Centered Search Bar -->
            <div class="search-bar">
                <form action="<?php htmlspecialchars('services/search.php', ENT_QUOTES, 'UTF-8'); ?>" method="get">
                    <input type="text" placeholder="Search for services..." />
                    <button>Search</button>
                </form>
            </div>

            <!-- Hero Content Below Search Bar -->
            <div class="hero-content">
                <h1>
                    <span class="highlight">Find</span> and <span class="highlight">Post</span> Services with Ease
                </h1>
                <p>
                    Discover a wide range of services from skilled professionals in your area or share your expertise by posting the services you offer. Connecting with the right clients has never been easier.
                </p>
                <a href="<?php echo $isLoggedIn ? BASE_URL . 'services/post-service.php' : BASE_URL . 'account/login.php'; ?>" class="btn">
                    Get Started
                </a>

            </div>
        </div>
    </div>

<main class="body-container">

    <!-- Categories Section -->
    <section class="category-section">
        <h2>Browse Categories</h2>
        <?php if ($categories): ?>
            <div class="category-cards-container">
                <?php foreach ($categories as $category): ?>
                    <div class="category-card">
                        <a href="<?php echo $category['slug']; ?>"> 
                            <div class="card-content">
                                <i style="color: #FFCC00" class="fas <?php echo $category['fontawesome_icon']; ?> category-icon"></i>
                                <h3><?php echo $category['name']; ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-ads-available">No categories available.</p>
        <?php endif; ?>
    </section>


    <!-- Featured Services Section -->
    <section class="featured-section">
        <h2>Featured Services</h2>
        <div class="services-container">
            <?php foreach ($services as $service): ?>
                <?php 
                    // Fetch category slugs from the database
                    $query = "SELECT slug FROM categories WHERE id = :id";
                    $categoryName = $pdo->prepare($query);
                    $categoryName->bindParam(':id', $service['category_id'], PDO::PARAM_INT);
                    $categoryName->execute();
                    $categorySlug = $categoryName->fetchColumn();

                    // Fetch subcategory slugs from the database
                    $subcategoryName = $pdo->prepare("SELECT slug FROM subcategories WHERE id = :id");
                    $subcategoryName->bindParam(':id', $service['subcategory_id'], PDO::PARAM_INT);
                    $subcategoryName->execute();
                    $subcategorySlug = $subcategoryName->fetchColumn();

                    // Create the slug for service URL
                    $slug = BASE_URL . $categorySlug . '/' . $subcategorySlug . '/' . $service['slug'] . '-' . $service['id'] . '.html';
                ?>
                <div class="service-card">
                    <a href="<?php echo $slug; ?>">
                        <div class="service-img">
                            <img src="<?php echo BASE_URL; ?>uploads/services-images/<?php echo $service['image_path']; ?>" alt="<?php echo $service['title']; ?>">
                        </div>
                        <div class="service-text">
                            <h3><?php echo substr($service['title'], 0, 24); ?>...</h3>
                            <p class="price">
                                <?php echo $service['price'] ? CURRENCY_TYPE_SYMBOLE . number_format($service['price'], 2) : 'Price on Request'; ?>
                            </p>
                            
                            <?php if ($service['is_negotiable'] == 'yes'): ?>
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

    <!-- How It Works Section -->
    <section class="how-it-works-section">
        <img src="images/how-it-works.jpg" alt="How it works">
        <h2>How It Works</h2>
        <div class="steps">
            <div class="step">
                <i class="fas fa-search"></i>
                <h3>Search for Services</h3>
                <p>Find the services you need by browsing categories or searching for specific keywords.</p>
            </div>
            <div class="step">
                <i class="fas fa-upload"></i>
                <h3>Post a Service</h3>
                <p>
                    Share your expertise by posting the services you offer with a detailed description and pricing.
                </p>
            </div>
            <div class="step">
                <i class="fas fa-comments"></i>
                <h3>Contact Providers</h3>
                <p>
                    Once you find a service you like, easily contact the provider to discuss details and hire them.
                </p>
            </div>
        </div>
    </section>
</main>

<?php require_once 'include/footer.php'; ?>
