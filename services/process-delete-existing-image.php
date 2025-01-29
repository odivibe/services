<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';

header('Content-Type: application/json');

// Validate input
if (empty($_GET['image_id']) || !is_numeric($_GET['image_id'])) 
{
    echo json_encode(['success' => false, 'message' => 'Invalid image ID.']);
    exit();
}

$image_id = intval($_GET['image_id']);
$user_id = $_SESSION['user_id'];

// Fetch the image details
$stmt = $pdo->prepare("SELECT * FROM service_images WHERE id = :id");
$stmt->execute([':id' => $image_id]);
$image = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$image) 
{
    echo json_encode(['success' => false, 'message' => 'Image not found.']);
    exit();
}

// Delete the image file
$imagePath = '../uploads/services-images/' . $image['image_path'];

if (file_exists($imagePath)) 
{
    if (unlink($imagePath))
    {
        // Delete the image record from the database
        $stmt = $pdo->prepare("DELETE FROM service_images WHERE id = :id  AND service_id = :service_id");
        $stmt->execute([':id' => $image_id, ':service_id' => $image['service_id']]);

        echo json_encode(['success' => true, 'message' => 'Image deleted']);
        exit();
    }
}

?>

