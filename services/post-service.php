<?php
// This script will enter services into database

$page_title = 'Post your services here';
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/header.php';

// Fetch categories
$stmt = $pdo->prepare("SELECT * FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);



//add service to db
/*require_once '../include/configs.php';
require_once '../include/sanitize-input.php';
require_once '../include/connection.php';
require_once '../include/unique-code-generator.php';
require_once '../include/image-resizer.php';

$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") 
{
    // Sanitize input values
    $name = sanitizeInput($_POST["name"]);
    $category = sanitizeInput($_POST["category"]);
    $description = sanitizeInput($_POST["description"]);
    $price = sanitizeInput($_POST["price"]);
    $quantity = sanitizeInput($_POST["quantity"]);
    $size = sanitizeInput($_POST["size"]);

    // Validation for product name
    if (empty($name)) 
    {
        $errors['name'] = "Product name is required.";
    }

    // Validation for description
    if (empty($description)) 
    {
        $errors['description'] = "Description is required.";
    }

    // Validation for price
    if (!is_numeric($price) || $price <= 0) 
    {
        $errors['price'] = "Price must be a positive number.";
    }

    // Validation for quantity
    if (!is_numeric($quantity) || $quantity <= 0) 
    {
        $errors['quantity'] = "Quantity must be a positive number.";
    }

    // Validation for size
    if (!is_numeric($size) || $size <= 0) 
    {
        $errors['size'] = "Size must be a positive number.";
    }

    // Validation for images
    $allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    foreach ($_FILES["images"]['name'] as $key => $value) 
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
            $errors['images'] = "Invalid image size, maximum file size is 2mb.";
            break;
        }
    }

    if (empty($errors)) 
    {
        // Insert other form data into database
        $query = "INSERT INTO products (name, category, description, price, quantity, size) VALUES (:name, :category, :description, :price, :quantity, :size)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->bindParam(':size', $size);

        if ($stmt->execute()) 
        {
            $product_id = $conn->lastInsertId(); // Get the last inserted product ID

            // Resize and save images
            foreach ($_FILES["images"]['tmp_name'] as $key => $tmpName) 
            {
                // Resize image
                $fileName = $category . '-' . $product_id . '-' . $_FILES["images"]['name'][$key] . '-' . uniqid();
                $destination_folder = 'uploads/';

                // Check if the folder already exists
                if (!is_dir($destination_folder))
                {
                    mkdir($destination_folder, 0777, true);
                }

                $destination = $destination_folder . $fileName;

                // Insert image name and product ID into image table
                $query = "INSERT INTO products_image (image_name, product_id) VALUES (:image_name, :product_id)";
                $stmt = $conn->prepare($query);
                $stmt->bindParam(':image_name', $fileName);
                $stmt->bindParam(':product_id', $product_id);
                $stmt->execute();

                resizeImage($tmpName, $destination, 200, 200);
            }

            // Return success message
            $response = array('success' => true, 'message' => 'Product added successfully.');
            echo json_encode($response);
        } 
        else 
        {
            // If database insert fails
            $response = array('success' => false, 'message' => 'Failed to add product.');
            echo json_encode($response);
        }
    } 
    else 
    {
        // Return errors
        $response = array('success' => false, 'errors' => $errors);
        echo json_encode($response);
    }
} 
else 
{
    // If not a POST request
    $response = array('success' => false, 'message' => 'Invalid request method.');
    echo json_encode($response);
}*/

?>


<div class="form-container">
    <h1>Insert Service</h1>
    <form method="POST" enctype="multipart/form-data">
        <!-- Category -->
        <div class="form-group">
            <label for="category">Category:<span class="required">*</span></label>
            <select name="category" id="category">
                <option value="">---Select Category---</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Subcategory -->
        <div class="form-group">
            <label for="subcategory">Subcategory:<span class="required">*</span></label>
            <select name="subcategory" id="subcategory">
                <option value="">---Select Subcategory---</option>
            </select>
        </div>

        <!-- Title -->
        <div class="form-group">
            <label for="service_name">Title:<span class="required">*</span></label>
            <input type="text" name="service_name" id="service_name" maxlength="50" required>
        </div>

        <!-- Price -->
        <div class="form-group">
            <label for="price">Price:<span class="required">*</span></label>
            <input type="number" name="price" id="price" step="0.01" min="0" required>
        </div>

        <!-- Price Negotiable -->
        <div class="form-group">
            <label for="price_negotiable">Is the price negotiable?<span class="required">*</span></label>
            <select name="price_negotiable" id="price_negotiable">
                <option value="">---Select One---</option>
                <option value="1">Yes, negotiable</option>
                <option value="0">No, not negotiable</option>
            </select>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label for="service_description">Service Description:<span class="required">*</span></label>
            <textarea name="service_description" id="service_description" cols="50" rows="10" required></textarea>
        </div>

        <!-- Image Preview -->
        <div class="form-group">
            <div id="image-preview-container"></div>
        </div>

        <!-- Upload Instruction -->
        <div class="form-group">
            <p class="upload-instruction">Click the button to upload images:<span class="required">*</span></p>
        </div>

        <!-- Upload Button -->
        <div class="form-group">
            <!-- Custom file upload button -->
            <label for="images" class="custom-file-upload">
                <span>+</span>
            </label>
        </div>

        <!-- Image Input Element -->
        <div class="form-group">
            <input type="file" name="images[]" id="images" multiple>
        </div>

        <!-- Submit -->
        <div class="form-group">
            <button type="submit" name="submit">Submit Service</button>
        </div>
    </form>
</div>



<?php require_once '../include/footer.php'; ?>
