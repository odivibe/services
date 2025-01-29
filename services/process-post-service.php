<?php 

session_start();

require_once '../include/db.php';
require_once '../include/input-cleaner.php';

$user_id = '';

if (isset($_SESSION['user_id'])) 
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

            // Insert service data
            $query = "INSERT INTO services (title, category_id, subcategory_id, user_id, slug, description, price, is_negotiable, state_id, lga_id) 
                    VALUES (:title, :category_id, :subcategory_id, :user_id, :slug, :description, :price, :is_negotiable, :state_id, :lga_id)";
            $stmt = $pdo->prepare($query);
            $serviceData = [
                ':title' => $title,
                ':category_id' => $category_id,
                ':subcategory_id' => $subcategory_id,
                ':user_id' => $user_id,
                ':slug' => $slug,
                ':description' => $description,
                ':price' => $price,
                ':is_negotiable' => $negotiable,
                ':state_id' => $state_id,
                ':lga_id' => $lga_id,
            ];

            if ($stmt->execute($serviceData)) 
            {
                $service_id = $pdo->lastInsertId();

                // Handle image uploads
                if (!empty($_FILES["images"]['tmp_name'])) 
                {
                    // Define the upload directory
                    $uploadDir = '../uploads/services-images/';

                    // Check if the directory exists; if not, create it with proper permissions
                    if (!is_dir($uploadDir)) 
                    {
                        mkdir($uploadDir, 0755, true);
                    }

                    foreach ($_FILES["images"]['tmp_name'] as $key => $tmpName) 
                    {
                        $originalName = $_FILES['images']['name'][$key];
                        $extension = pathinfo($originalName, PATHINFO_EXTENSION);

                        // Validate file type
                        if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif'])) 
                        {
                            continue; // Skip invalid files
                        }

                        $fileName = $slug . '-' . $service_id . '-image-' . time() . '-' . uniqid() . '.' . $extension;
                        $destination = $uploadDir . $fileName;

                        // Save the file
                        if (move_uploaded_file($tmpName, $destination)) 
                        {
                            // Determine if this is the featured image
                            $is_featured = ($key === 0) ? 1 : 0;

                            // Insert image record into the database
                            $imageQuery = "INSERT INTO service_images (image_path, service_id, is_featured) 
                                           VALUES (:image_path, :service_id, :is_featured)";
                            $imageStmt = $pdo->prepare($imageQuery);
                            $imageStmt->execute([
                                ':image_path' => $fileName,
                                ':service_id' => $service_id,
                                ':is_featured' => $is_featured,
                            ]);
                        }
                    }

                    $data = ['success' => true, 'message' => 'Inserted.'];
                    echo json_encode($data);
                } 
                else 
                {
                    $data = ['success' => false, 'message' => 'No images uploaded.'];
                    echo json_encode($data);
                }   
            } 
            else 
            {
                $data = ['success' => false, 'message' => 'Data insertion failed'];
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
