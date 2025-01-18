<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/input-cleaner.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['logged_in'])) 
{
    header("Location:" . BASE_URL . "account/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

try 
{
    $query = "
        SELECT s.id, s.title, s.slug, s.status, si.image_path, c.slug AS category_slug, sc.slug AS subcategory_slug
        FROM services AS s
        LEFT JOIN service_images AS si 
            ON s.id = si.service_id AND si.is_featured = 1
        LEFT JOIN categories AS c
            ON s.category_id = c.id
        LEFT JOIN subcategories AS sc
            ON s.subcategory_id = sc.id
        WHERE s.user_id = :user_id
        ORDER BY s.created_at DESC";

    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} 
catch (PDOException $e) 
{
    error_log($e->getMessage(), 3, '../logs/custom-error.log');
    die('Error fetching ads. Please try again later.');
}

?>

<?php require_once '../include/header.php'; ?>

<div class="container">
        <h1>My Ads</h1>
        <table class="ads-table">
            <thead>
                <tr>
                    <th>Featured Image</th>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($ads)): ?>
                <?php foreach ($ads as $ad): ?>
                    <tr>
                        <td>
                            <img src="<?= BASE_URL . 'uploads/services-images/' . $ad['image_path']; ?>" alt="Featured Image" class="featured-img">
                        </td>
                        <td><?= $ad['title'] ?></td>
                        <td>
                            <span class="status <?= $ad['status'] ?>">
                                <?= ucfirst($ad['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL . $ad['category_slug'] . '/' . $ad['subcategory_slug'] . '/' . $ad['slug'] . '-' . $ad['id'] . '.html'; ?>" 
                               class="btn btn-view" target="_blank">View</a>

                            <a href="edit-ad.php?id=<?= $ad['id'] ?>" class="btn btn-edit">Edit</a>
                            
                            <a href="delete-ad.php?id=<?= $ad['id'] ?>" class="btn btn-delete" 
                               onclick="return confirm('Are you sure you want to delete this ad?');">
                                Delete
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No ads found.</td>
                </tr>
            <?php endif; ?>
        </tbody>
        </table>
    </div>

<?php require_once '../include/footer.php'; ?>