<?php 

session_start();

require_once '../include/db.php';
require_once '../include/input-cleaner.php';

$user_id = '';

if (isset($_SESSION['user_id'])) 
{
    $user_id = $_SESSION['user_id'];
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_service"])) 
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
    $ad_id = intval(sanitizeInput($_POST["service_id"]));

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
    $maxSize = 5 * 1024 * 1024; // 5MB
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
            $errors['images'] = "Image size exceeds 5MB.";
            break;
        }
    }

    if (empty($errors)) 
    {
        try 
        {
            // Generate unique slug from the title
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $title), '-'));

            // Update service details
            $updateQuery = "UPDATE services SET category_id = :category_id, subcategory_id = :subcategory_id, title = :title, slug = :slug, description = :description, price = :price, is_negotiable = :is_negotiable, state_id = :state_id, lga_id = :lga_id WHERE id = :ad_id AND user_id = :user_id";
            $stmt = $pdo->prepare($updateQuery);
            $updated = [
                ':category_id' => $category_id,
                ':subcategory_id' => $subcategory_id,
                ':title' => $title,
                'slug' => $slug,
                'description' => $description,
                ':price' => $price,
                ':is_negotiable' => $negotiable,
                ':state_id' => $state_id,
                ':lga_id' => $lga_id,
                ':ad_id' => $ad_id,
                ':user_id' => $user_id,
            ];

            if ($stmt->execute($updated)) 
            {
                // Check if the ad already has a featured image
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM service_images WHERE service_id = :service_id AND is_featured = 1");
                $stmt->execute([':service_id' => $ad_id]);
                $has_featured = $stmt->fetchColumn() > 0;

                //$has_featured = $result ? true : false;

                // Handle image uploads for editing
                if (!empty($_FILES["images"]['tmp_name'])) 
                {
                    foreach ($_FILES["images"]['tmp_name'] as $key => $tmpName) 
                    {
                        $originalName = $_FILES['images']['name'][$key];
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                        // Validate file type
                        if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) 
                        {
                            continue; // Skip invalid files
                        }

                        $fileName = $slug . '-' . $ad_id . '-image-' . time() . '.' . $extension;
                        $destination = '../uploads/services-images/' . $fileName;

                        // Save the file
                        if (move_uploaded_file($tmpName, $destination)) 
                        {
                            // If no featured image exists, set the first uploaded image as featured (is_featured = 1)
                            //$is_featured = ($has_featured && $key === 0) ? 0 : 1;
                            //$is_featured = ($key === 0) ? 1 : 0;

                            $imageQuery = "INSERT INTO service_images (image_path, service_id, is_featured) 
                                           VALUES (:image_path, :service_id, :is_featured)";
                            $imageStmt = $pdo->prepare($imageQuery);
                            $imageStmt->execute([
                                ':image_path' => $fileName,
                                ':service_id' => $ad_id,
                                ':is_featured' => $is_featured,
                            ]);
                        }
                    }
                }


                $data = ['success' => true, 'message' => 'Updated'];
                echo json_encode($data);
            } 
            else 
            {
                $data = ['success' => false, 'message' => 'Failed'];
                echo json_encode($data);
            }
          
        } 
        catch (Exception $e) 
        {
            $data = ['success' => false, 'message' => $e->getMessage()];
            echo json_encode($data);
        }
   }
   else
   {
        $data = ['errors' => $errors];
        echo json_encode($data);
   }

}

?>
