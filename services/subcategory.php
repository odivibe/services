<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';

// Get category and subcategory slugs from URL
$categorySlug = isset($_GET['cat_slug']) ? $_GET['cat_slug'] : '';
$subcategorySlug = isset($_GET['subcat_slug']) ? $_GET['subcat_slug'] : '';

// Validate slugs
if (!$categorySlug || !$subcategorySlug) 
{
    error_log("Missing or invalid category/subcategory slug.");
    http_response_code(404);
    require_once '../include/header.php';
    require_once '../errors/404.php';
    require_once '../include/footer.php';
    exit();
}

try 
{
    // Fetch category and subcategory details
    $query = "SELECT c.id AS category_id, c.name AS category_name, c.slug AS cat_slug, s.slug AS subcat_slug,
                     s.id AS subcategory_id, s.name AS subcategory_name
              FROM categories c
              INNER JOIN subcategories s ON c.id = s.category_id
              WHERE c.slug = :categorySlug AND s.slug = :subcategorySlug";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':categorySlug' => $categorySlug, ':subcategorySlug' => $subcategorySlug]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) 
    {
        error_log("Category or subcategory not found: $categorySlug / $subcategorySlug");
        http_response_code(404);
        require_once '../include/header.php';
        require_once '../errors/404.php';
        require_once '../include/footer.php';
        exit();
    }

    // Fetch ads related to the subcategory
    $query = "SELECT s.*, i.image_path
              FROM services s
              LEFT JOIN service_images i ON s.id = i.service_id
              WHERE s.category_id = :category_id AND s.subcategory_id = :subcategory_id
              GROUP BY s.id";

    $stmt = $pdo->prepare($query);
    $stmt->execute([':category_id' => $result['category_id'], ':subcategory_id' => $result['subcategory_id']]);
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Dynamic Meta Tag Variables
    /*$metaTitle = $result['subcategory_name'] . " - Find Trusted " . $result['subcategory_name'] . " Providers";
    $metaDescription = "Browse service ads in the " . $result['subcategory_name'] . " subcategory under " . $result['category_name'] . ". Find trusted providers offering " . $result['subcategory_name'] . " services near you.";
    $metaKeywords = $result['subcategory_name'] . ", " . $result['category_name'] . ", service ads, trusted providers, skilled professionals, local services";
    $siteName = SITE_NAME;
    $siteUrl = BASE_URL . $result['cat_slug'] . '/' . $result['subcat_slug'];
    $searchTarget = BASE_URL . "search.php?query={search_term_string}";*/

} 
catch (PDOException $e) 
{
    error_log("Database Error: " . $e->getMessage());
    http_response_code(500);
    require_once '../include/header.php';
    require_once '../errors/404.php';
    require_once '../include/footer.php';
    exit();
}
?>

<?php include '../include/header.php'; ?>

<!-- Ads Section -->
<section class="featured-services">
    <h2><?php echo $result['subcategory_name']; ?> - Ads</h2>
    
    <?php if ($services): ?>
        <div class="services-container">
            <?php foreach ($services as $service): ?>
                <?php
                // Build dynamic slug for service details page
                $serviceSlug = BASE_URL . $categorySlug . '/' . $subcategorySlug . '/' . $service['slug'] . '-' . $service['id'] . '.html';
                ?>
                <div class="service-card">
                    <a href="<?php echo $serviceSlug; ?>">
                        <div class="service-img">
                            <img src="<?php echo BASE_URL . 'uploads/services-images/' . $service['image_path']; ?>" alt="<?php echo $service['title']; ?>">
                        </div>
                        <div class="service-text">
                            <h3><?php echo $service['title']; ?></h3>
                            <p class="price">
                                <?php echo $service['price'] ? CURRENCY_TYPE_SYMBOLE . number_format($service['price'], 2) : 'Price on Request'; ?>
                            </p>
                            <p><?php echo substr($service['description'], 0, 35) . '...'; ?></p>
                            <p class="negotiable <?php echo $service['is_negotiable'] ? 'yes' : 'no'; ?>">
                                <?php echo $service['is_negotiable'] ? 'Negotiable' : 'Not Negotiable'; ?>
                            </p>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p class="no-ads-available">No ads available in this subcategory.</p>
    <?php endif; ?>
</section>

<?php include '../include/footer.php'; ?>
