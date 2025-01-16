<?php 

session_start();

require_once '../include/config.php'; // header.php depends on config.php, so place it before header.php
require_once '../include/db.php';

if (!isset($_SESSION['user_id']) && !isset($_SESSION['logged_in'])) 
{
    header("Location:" . BASE_URL . "account/login.php");
    exit();
}

// Fetch categories
$stmt = $pdo->prepare("SELECT * FROM categories");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch states
$stmt = $pdo->prepare("SELECT * FROM states");
$stmt->execute();
$states = $stmt->fetchAll(PDO::FETCH_ASSOC); 

?>

<?php require_once '../include/header.php'; ?>

<div class="form-container">
    <h1>Insert Service</h1>
    <form method="POST" enctype="multipart/form-data" id="service-form">

        <!-- form error -->
        <div class="form-group error">
            <div id="form-error"></div>
        </div>

        <!-- Title -->
        <div class="form-group">
            <label for="title">Title:<span class="required">*</span></label>
            <input type="text" name="title" id="title" maxlength="50" required>
            <div id="title-error" class="error-message"></div> 
        </div>

        <!-- Category -->
        <div class="form-group">
            <label for="category">Category:<span class="required">*</span></label>
            <select name="category" id="category" required>
                <option value="">---Select Category---</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category['id'] ?>"><?= $category['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <div id="category-error" class="error-message"></div> 
        </div>

        <!-- Subcategory -->
        <div class="form-group">
            <label for="subcategory">Subcategory:<span class="required">*</span></label>
            <select name="subcategory" id="subcategory" required>
                <option value="">---Select Subcategory---</option>
            </select>
            <div id="subcategory-error" class="error-message"></div> 
        </div>

        <!-- Price -->
        <div class="form-group">
            <label for="price">Price:<span class="required">*</span></label>
            <input type="number" name="price" id="price" step="0.01" min="0" required>
            <div id="price-error" class="error-message"></div> 
        </div>

        <!-- Price Negotiable -->
        <div class="form-group">
            <label for="negotiable">Is the price negotiable?<span class="required">*</span></label>
            <select name="negotiable" id="negotiable" required>
                <option value="">---Select One---</option>
                <option value="yes">Yes</option>
                <option value="no">No</option>
            </select>
            <div id="negotiable-error" class="error-message"></div>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label for="description">Service Description:<span class="required">*</span></label>
            <textarea name="description" id="description" cols="50" rows="10" required></textarea>
            <div id="description-error" class="error-message"></div>
        </div>

        <!-- State -->
        <div class="form-group">
            <label for="state">State:<span class="required">*</span></label>
            <select name="state" id="state" required>
                <option value="">---Select State---</option>
                <?php foreach ($states as $state): ?>
                    <option value="<?= $state['id'] ?>"><?= $state['name'] ?></option>
                <?php endforeach; ?>
            </select>
            <div id="state-error" class="error-message"></div>
        </div>

        <!-- LGA -->
        <div class="form-group">
            <label for="lga">LGA:<span class="required">*</span></label>
            <select name="lga" id="lga" required>
                <option value="">---Select LGA---</option>
            </select>
            <div id="lga-error" class="error-message"></div> 
        </div>

        <!-- Image Preview -->
        <div class="form-group">
            <div id="image-preview-container"></div>
            <div id="image-error" class="error-message error"></div>
        </div>

        <!-- Upload Instruction -->
        <div class="form-group">
            <p class="upload-instruction">
                Click the button to upload images:<span class="required">*</span>
            </p>
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
            <button type="submit" name="submit_service" id="submit-service-button">Submit Service</button>
        </div>
    </form>
</div>

<?php require_once '../include/footer.php'; ?>
