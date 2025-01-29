<?php
session_start();

require_once '../include/config.php';
require_once '../include/db.php'; 

if (!isset($_SESSION['user_id']) && !isset($_GET['id'])) 
{
    header("Location: " . BASE_URL . "account/login.php");
    exit();
}

$ad_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];
$hash = $_GET['token'];

// Verify the hash
$expectedHash = hash_hmac('sha256', $ad_id, SECRET_KEY);

if ($hash !== $expectedHash) 
{
    header("Location: " . BASE_URL . "index.php");
    exit();
}

try 
{
    // Start a transaction to ensure all queries succeed or fail together
    $pdo->beginTransaction();

    // Fetch all image paths for the service
    $stmt = $pdo->prepare("SELECT image_path FROM service_images WHERE service_id = :id");
    $stmt->bindParam(':id', $ad_id, PDO::PARAM_INT);
    $stmt->execute();
    $images = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Delete image files from the server
    foreach ($images as $image) 
    {
        $image_path = '../uploads/services-images/' . $image['image_path'];

        if (file_exists($image_path)) 
        {
            unlink($image_path); // Delete the file
        }
    }

    // Delete images from the service_images table
    $stmt = $pdo->prepare("DELETE FROM service_images WHERE service_id = :id");
    $stmt->bindParam(':id', $ad_id, PDO::PARAM_INT);
    $stmt->execute();

    // Delete the ad from the services table
    $stmt = $pdo->prepare("DELETE FROM services WHERE id = :id AND user_id = :user_id");
    $stmt->bindParam(':id', $ad_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();

    // Commit the transaction
    $pdo->commit();

    $_SESSION['success_message'] = "Ad and its images deleted successfully.";
    header("Location: " . BASE_URL . "services/myads.php");
    exit();

} 
catch (PDOException $e) 
{
    // Rollback the transaction on error
    $pdo->rollBack();
    error_log($e->getMessage(), 3, '../logs/custom-error.log');
    $_SESSION['error_message'] = "Failed to delete the ad and its images.";
}

?>
