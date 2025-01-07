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

// Service image upload logic
document.addEventListener('DOMContentLoaded', function() {
    const imageUpload = document.getElementById('images');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const uploadedImages = [];
    const maxImagePremium = 10; // Premium users number of images
    const maxImageNonPremium = 5; // Non-premium users number of images
    let maxImages;

    function checkSubscription() 
    {
        fetch('../account/check-subscription.php')
            .then(response => {

                if (!response.ok) 
                {
                    throw new Error('Failed to fetch subscription status');
                }

                return response.json();
            })
            .then(data => {

                maxImages = data.isPremiumUser === 1 ? maxImagePremium : maxImageNonPremium;
            })
            .catch(error => {
                console.error('Error:', error.message);
                maxImages = 0; // Default value on error
            });
    }

    checkSubscription();


// Access the value later with a delay due to asycn task that run on background
//setTimeout(() => { alert(maxImages);}, 1000);


    imageUpload.addEventListener('change', handleImageChange);

    function handleImageChange() 
    {
        const files = Array.from(imageUpload.files);
        const validFiles = files.filter(validateFile);
        
        // Check if the number of uploaded images exceeds the limit
        if (uploadedImages.length + validFiles.length > maxImages) 
        {
            alert(`You can only upload a maximum of ${maxImages} images.`);
            return;
        }

        uploadedImages.push(...validFiles);
        showUploadedImages();
    }

    function validateFile(file) 
    {
        const allowedTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB

        if (!allowedTypes.includes(file.type)) 
        {
            alert(`Invalid file type: ${file.type}`);
            return false;
        }

        if (file.size > maxSize) 
        {
            alert(`File size exceeds the limit of 5MB.`);
            return false;
        }
        return true;
    }

    function showUploadedImages() 
    {
        imagePreviewContainer.innerHTML = ''; // Clear existing previews
        uploadedImages.forEach((file, index) => {
            // Create image container
            const imageContainer = document.createElement('div');

            // Create image element
            const imageElement = document.createElement('img');
            const objectURL = URL.createObjectURL(file);
            imageElement.src = objectURL;
            imageContainer.appendChild(imageElement);

            // Create delete button
            const deleteButton = document.createElement('span');
            deleteButton.textContent = 'X';
            deleteButton.addEventListener('click', () => {
                URL.revokeObjectURL(objectURL); // Release the object URL before removing the image
                uploadedImages.splice(index, 1);
                showUploadedImages(); // Re-render image previews
            });
            imageContainer.appendChild(deleteButton);
            imagePreviewContainer.appendChild(imageContainer);
        });
    }
});
