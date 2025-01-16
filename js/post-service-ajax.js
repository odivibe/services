//populate LGA dynamically
document.addEventListener('DOMContentLoaded', function () 
{
    const stateDropdown = document.getElementById("state");
    
    stateDropdown.addEventListener("change", function () {
        fetchLgas(this.value);
    });

    function fetchLgas(stateId) 
    {
        const lgaDropdown = document.getElementById("lga");

        if (!stateId) 
        {
            lgaDropdown.innerHTML = '<option value="">---Select LGA---</option>';
            return;
        }

        fetch(`select-lgas.php?state_id=${stateId}`)
        .then(response => response.text())
        .then(data => {
            lgaDropdown.innerHTML = '<option value="">---Select LGA---</option>';
            lgaDropdown.innerHTML += data;
        })
        .catch(error => {
            lgaDropdown.innerHTML = '<option value="">---Error loading LGA---</option>';
        });
    }

});


//populate subcategories dynamically
document.addEventListener('DOMContentLoaded', function () 
{
    // Fetch subcategories
    const categoryDropdown = document.getElementById('category');

    categoryDropdown.addEventListener('change', function () {
        fetchSubcategories(this.value);
    });

    function fetchSubcategories(categoryId) 
    {
        const subcategoryDropdown = document.getElementById('subcategory');

        if (!categoryId) 
        {
            subcategoryDropdown.innerHTML = '<option value="">---Select Subcategory---</option>';
            return;
        }

        fetch(`select-subcategories.php?category_id=${categoryId}`)
        .then(response => response.text())
        .then(data => {
            subcategoryDropdown.innerHTML = '<option value="">---Select Subcategory---</option>';
            subcategoryDropdown.innerHTML += data;
        })
        .catch(error => {
            subcategoryDropdown.innerHTML = '<option value="">---Error loading subcategories---</option>';
        });
    }

});


document.addEventListener('DOMContentLoaded', function () {
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
        const maxSize = 3 * 1024 * 1024; // 3MB

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

            if (data.message === 'Inserted') 
            {
                form.reset();
                uploadedImages.length = 0; // Clear images
                //showUploadedImages();
                window.location.href = 'myads.php'; // redirect
            } 
            else 
            {
                if (data.message === 'Failed') 
                {
                    formError.innerHTML = 'Failed to submit the service, try again';
                }
                
                if (data.errorMessage) 
                {
                    displayErrorMessages(data.errorMessage);
                    //alert(data.errorMessage);
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
