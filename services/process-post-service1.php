<?php 

session_start();

require_once '../include/db.php';
require_once '../include/input-cleaner.php';

$user_id = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) 
{
    $user_id = $_SESSION['user_id'];
}



if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["submit_service"])) 
{
    $errors = [];
    
    // Sanitize input
    $title = sanitizeInput($_POST["title"]);
    $category_id = sanitizeInput($_POST["category"]);
    $subcategory_id = sanitizeInput($_POST["subcategory"]);
    $description = sanitizeInput($_POST["description"]);
    $price = sanitizeInput($_POST["price"]);
    $negotiable = sanitizeInput($_POST["negotiable"]);
    $state_id = sanitizeInput($_POST["state"]);
    $lga_id = sanitizeInput($_POST["lga"]);

    // Validation
    if (empty($title)) 
    {
        $errors['title'] = "Title is required.";
    }

    if (empty($category_id)) 
    {
        $errors['category'] = "Category is required.";
    }

    if (empty($subcategory_id)) 
    {
        $errors['subcategory'] = "Subcategory is required.";
    }

    if (empty($description)) 
    {
        $errors['description'] = "Description is required.";
    }

    if (!is_numeric($price) || $price <= 0) 
    {
        $errors['price'] = "Price must be a positive number.";
    }

    if (empty($negotiable)) 
    {
        $errors['negotiable'] = "Please specify if the price is negotiable.";
    }

    if (empty($state_id)) 
    {
        $errors['state'] = "State is required.";
    }

    if (empty($lga_id)) 
    {
        $errors['lga'] = "LGA is required.";
    }

    // Image validation
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
    $maxSize = 3 * 1024 * 1024; // 3MB
    foreach ($_FILES["images"]['tmp_name'] as $key => $tmpName) 
    {
        $imageType = $_FILES["images"]['type'][$key];
        $imageSize = $_FILES["images"]['size'][$key];

        if (!in_array($imageType, $allowedTypes)) 
        {
            $errors['images'] = "Invalid file type.";
            break;
        }

        if ($imageSize > $maxSize) 
        {
            $errors['images'] = "Image size exceeds 3MB.";
            break;
        }
    }

    if (empty($errors)) 
    {
        // Generate slug from the title
        $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

        // Insert service data
        $query = "INSERT INTO services (title, category_id, subcategory_id, user_id, slug, description, price, is_negotiable, state_id, lga_id) 
                  VALUES (:title, :category_id, :subcategory_id, :user_id, :slug, :description, :price, :is_negotiable, :state_id, :lga_id)";
        $stmt = $pdo->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':category_id', $category_id);
        $stmt->bindParam(':subcategory_id', $subcategory_id);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':slug', $slug);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':is_negotiable', $negotiable);
        $stmt->bindParam(':state_id', $state_id);
        $stmt->bindParam(':lga_id', $lga_id);

        if ($stmt->execute()) 
        {
            $service_id = $pdo->lastInsertId();

            // Insert images
            foreach ($_FILES["images"]['tmp_name'] as $key => $tmpName) 
            {
                $originalName = $_FILES['images']['name'][$key];

                $extension = pathinfo($originalName, PATHINFO_EXTENSION); // Get file extension

                $fileName = $slug . '-' . $service_id . '-image-' . ($key + 1) . '.' . $extension;

                $destination = '../uploads/services-images/' . $fileName;
                move_uploaded_file($tmpName, $destination);

                // Mark the first image as featured
                $is_featured = ($key === 0) ? 1 : 0;

                $imageQuery = "INSERT INTO service_images (image_path, service_id, is_featured) 
                               VALUES (:image_path, :service_id, :is_featured)";
                $imageStmt = $pdo->prepare($imageQuery);
                $imageStmt->bindParam(':image_path', $fileName);
                $imageStmt->bindParam(':service_id', $service_id);
                $imageStmt->bindParam(':is_featured', $is_featured);
                $imageStmt->execute();
            }

            $data= array('success' => true, 'message' => 'Inserted');
            echo json_encode($data);
        } 
        else 
        {
            $data = array('success' => false, 'message' => 'Failed');
            echo json_encode($data);
        }
    } 
    else 
    {
        $data = array('success' => false, 'errorMessage' => $errors);
        echo json_encode($data);
    }
}

?>
