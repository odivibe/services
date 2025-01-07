<?php
// This script displays all the subcategories under a particular category

require_once '../include/config.php';
require_once '../include/db.php';

// Get category slug
$categorySlug = isset($_GET['slug']) ? $_GET['slug'] : '';

try 
{
    // Fetch category details
    $query = "SELECT * FROM categories WHERE slug = :slug";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':slug', $categorySlug, PDO::PARAM_STR);
    $stmt->execute();
    $category = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$category) 
    {
        // If the category is not found, show 404 ERROR
        error_log("Invalid Category slug: " . $categorySlug);
        http_response_code(404);
        require_once '../include/header.php';
        require_once '../include/404.php';
        require_once '../include/footer.php';
        exit();
    }

    // Fetch subcategories for the current category
    $query = "SELECT * FROM subcategories WHERE category_id = :category_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':category_id', $category['id'], PDO::PARAM_INT);
    $stmt->execute();
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //Dynamic meta tag variables
    $metaTitle = $category['name'] . " - Find Trusted " . $category['name'] . " Providers";
    $metaDescription = "Browse service ads in the " . $category['name'] . " category. Find trusted providers offering " . $category['name'] . " services near you.";
    $metaKeywords = $category['name'] . ", service ads, trusted providers, skilled professionals, local services";
    $siteName = SITE_NAME;
    $siteUrl = BASE_URL . $category['slug'];
    $searchTarget = BASE_URL . "search.php?query={search_term_string}";

} 
catch (PDOException $e) 
{
    // Error handling for database issues
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    require_once '../include/header.php';
    require_once '../include/500.php';
    require_once '../include/footer.php';
    exit();
}

?>

<?php include '../include/header.php'; ?>

    <!-- List out Subcategories for a particular category-->
    <section class="category-section">
        <h2><?php echo $category['name']; ?> - Subcategories</h2>
        <?php if ($subcategories && count($subcategories) > 0): ?>
            <div class="category-cards-container">
                <?php foreach ($subcategories as $subcategory): ?>
                    <div class="category-card">
                        <a href="<?php echo $category['slug']; ?>/<?php echo $subcategory['slug']; ?>">
                            <div class="card-content card-content-h3">
                                <h3><?php echo $subcategory['name']; ?></h3>
                            </div>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="no-ads-available">No subcategories available.</p>
        <?php endif; ?>
    </section>

<?php include '../include/footer.php'; ?>




