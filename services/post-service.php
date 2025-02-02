<?php 

session_start();

require_once '../include/config.php'; // header.php depends on config.php, so place it before header.php
require_once '../include/db.php';

if (!isset($_SESSION['user_id'])) 
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

<main class="body-container">
    <div class="form-container">
        <h1>Insert Service</h1>
        <form method="POST" enctype="multipart/form-data" id="service-form">
            
            <!-- form error -->
            <div class="form-group error">
                <div id="form-error"></div>
            </div>

            <!-- CSRF Token -->
            <div class="form-group">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div id="csrf_token_error" class="error">
                    <?php echo $errorMessages['csrf_token']; ?>
                </div>
            </div>

            <!-- Title -->
            <div class="form-group">
                <label for="title">Title:<span class="required">*</span></label>
                <input type="text" name="title" id="title" maxlength="100" required>
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
                <textarea name="description" id="description" cols="50" rows="10" maxlength="300" required></textarea>
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
                <div id="image-preview-container" class="image-preview-wrapper"></div>
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
</main>

<script>
document.addEventListener('DOMContentLoaded', function () 
{
    const formError = document.getElementById('form-error');
    const imageError = document.getElementById('image-error');
    const imageUpload = document.getElementById('images');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const uploadedImages = [];
    const maxImagePremium = 10; // Premium user limit
    const maxImageNonPremium = 5; // Non-premium user limit
    let maxImages = maxImageNonPremium; // Default to non-premium
    let isSubmitting = false;

    // Event listeners
    imageUpload.addEventListener('change', handleImageChange);

    const form = document.querySelector('form');
    const submitButton = form.querySelector('button[type="submit"]');
    form.addEventListener('submit', handleFormSubmit);

    checkSubscription();

    // Check subscription status
    function checkSubscription() 
    {
        fetch('check-subscription.php').then(response => {
            if (!response.ok) 
            {
                throw new Error('Failed to fetch subscription status');
            }
            return response.json();
        }).then(data => {
            maxImages = data.isPremiumUser === 1 ? maxImagePremium : maxImageNonPremium;
        }).catch(error => {
            console.error('Subscription check error:', error);
        });
    }

    // Handle image uploads
    function handleImageChange() 
    {
        const files = Array.from(imageUpload.files);
        const validFiles = files.filter(validateFile);

        // Check upload limit
        if (uploadedImages.length + validFiles.length > maxImages) {
            imageError.innerHTML = `You can only upload a maximum of ${maxImages} images.`;
            return;
        }

        uploadedImages.push(...validFiles);
        showUploadedImages();
    }

    // Validate file type and size
    function validateFile(file) 
    {
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 3MB

        if (!allowedTypes.includes(file.type)) {
            imageError.innerHTML = `Invalid file type: ${file.type}`;
            return false;
        }

        if (file.size > maxSize) {
            imageError.innerHTML = `File size exceeds the 3MB limit.`;
            return false;
        }

        return true;
    }

    // Show uploaded images with a delete option
    function showUploadedImages() 
    {
        imagePreviewContainer.innerHTML = '';
        uploadedImages.forEach((file, index) => {
            const imageContainer = document.createElement('div');
            imageContainer.classList.add('image-container');

            const imageElement = document.createElement('img');
            const objectURL = URL.createObjectURL(file);
            imageElement.src = objectURL;

            const deleteImg = document.createElement('span');
            deleteImg.textContent = 'X';
            deleteImg.addEventListener('click', () => {
                URL.revokeObjectURL(objectURL);
                uploadedImages.splice(index, 1);
                showUploadedImages();
            });

            imageContainer.appendChild(imageElement);
            imageContainer.appendChild(deleteImg);
            imagePreviewContainer.appendChild(imageContainer);
        });
    }

    // Handle form submission
    function handleFormSubmit(event) 
    {
        event.preventDefault();
        formError.innerHTML = '';
        imageError.innerHTML = '';

        if (isSubmitting) 
        {
            formError.innerHTML = 'Form is already being submitted. Please wait.';
            return;
        }

        if (uploadedImages.length === 0) 
        {
            imageError.innerHTML = 'Please upload at least one image.';
            return;
        }

        isSubmitting = true;
        submitButton.disabled = true;

        const formData = new FormData();

        // Append form data
        Array.from(form.elements).forEach(element => {

            if (element.name && element.type !== 'file') 
            {
                formData.append(element.name, element.value);
            }
        });

        // Append images
        uploadedImages.forEach((file, index) => {
            formData.append(`images[]`, file);
        });


        // Submit form
        fetch('process-post-service.php', {
            method: 'POST',
            body: formData,
        }).then(response => {

            if (!response.ok) 
            {
                //throw new Error('Failed to submit the service.');
                formError.innerHTML = 'Failed to submit the service.';
            }

            return response.json();

        }).then(data => {

            if (data.success) 
            {
                form.reset();
                uploadedImages.length = 0; // Clear images
                window.location.href = 'myads.php'; // redirect
            } 
            else 
            {
                if (data.success === false) 
                {
                    formError.innerHTML = data.message;
                }
                
                if (data.errors) 
                {
                    displayErrorMessages(data.errors);
                }
            }

        }).catch(error => {
            formError.innerHTML = 'An error occurred: ' + error.message;
        }).finally(() => {
            isSubmitting = false;
            submitButton.disabled = false;
        });
    }

    // Clear error messages
    function clearErrorMessages() 
    {
        imageError.innerHTML = '';
        formError.innerHTML = '';
    }

    // Display error messages from backend
    function displayErrorMessages(errors) 
    {
        Object.entries(errors).forEach(([field, error]) => {
            const errorElement = document.getElementById(`${field}-error`);
            if (errorElement) {
                errorElement.textContent = error;
            }
        });
    }
});
</script>

<?php require_once '../include/footer.php'; ?>
