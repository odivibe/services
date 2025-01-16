let imageArray = []; // Array to store all uploaded images

// Function to handle file uploads
function handleFileUpload(event) 
{
    const newFiles = Array.from(event.target.files); // Convert FileList to Array
    
    // Push new files into the existing array
    newFiles.forEach(file => {
        imageArray.push(file);
    });

    displayImages(); // Refresh the UI to show the updated array
}

// Function to display images in the UI
function displayImages() 
{
    const container = document.getElementById('image-preview-container');
    container.innerHTML = ''; // Clear the container before re-rendering

    imageArray.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            // Create image preview container
            const div = document.createElement('div');
            div.classList.add('image-preview');

            // Create image element
            const img = document.createElement('img');
            img.src = e.target.result;
            img.classList.add('preview-img');

            // Create delete button
            const deleteBtn = document.createElement('button');
            deleteBtn.innerText = 'X';
            deleteBtn.classList.add('delete-btn');
            deleteBtn.onclick = function() {
                removeImage(index); // Remove the image from the array
            };

            // Append image and button to the container
            div.appendChild(img);
            div.appendChild(deleteBtn);
            container.appendChild(div);
        };

        reader.readAsDataURL(file); // Read the file as a Data URL
    });
}

// Function to remove an image from the array
function removeImage(index) 
{
    imageArray.splice(index, 1); // Remove the image at the given index
    displayImages(); // Refresh the UI to reflect the remaining images
}

// Function to submit the form
function submitForm() 
{
    const form = document.getElementById('service-form');
    const formData = new FormData(form);

    // Append images from the array to the FormData
    imageArray.forEach((file, index) => {
        formData.append(`service_images[]`, file, file.name);
    });

    // Send the FormData to the server
    fetch('insert_service_action.php', {
        method: 'POST',
        body: formData,
    })
    .then((response) => response.text())
    .then((data) => {
        console.log(data);
        alert('Service uploaded successfully!');
    })
    .catch((error) => {
        console.error('Error:', error);
        alert('Failed to upload the service.');
    });
}
