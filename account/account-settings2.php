<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/header.php';
?>

<div id="manage-account">
    <div class="sidebar">
        <a href="#" onclick="loadContent('change-email')">Change Email</a>
        <a href="#" onclick="loadContent('change-password')">Change Password</a>
        <a href="#" onclick="loadContent('change-profile-image')">Change Profile Image</a>
        <a href="#" onclick="loadContent('change-phone')">Change Phone</a>
        <a href="#" onclick="loadContent('add-socials')">Add Socials</a>
    </div>

    <div id="content-area">
        <h2>Welcome to Your Account Area</h2>
        <p>Manage your account here.</p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Attach event listeners to sidebar buttons
    const sidebarButtons = document.querySelectorAll('.sidebar a');
    const displayArea = document.getElementById('content-area');

    // Function to load content dynamically into the display area
    function loadContent(page) 
    {
        fetch(`${page}.php`)
            .then(response => response.text())
            .then(data => {
                displayArea.innerHTML = data; // Insert the form into the display area
                attachFormHandler(); // Attach the form submission handler
            })
            .catch(error => {
                displayArea.innerHTML = "<p>Error loading content. Please try again later.</p>";
            });
    }

    // Attach event listeners to each button in the sidebar
    sidebarButtons.forEach(button => {
        button.addEventListener('click', function (event) {
            event.preventDefault();
            const page = button.getAttribute('onclick').split("'")[1]; // Get the page name from onclick
            loadContent(page); // Load content based on button clicked
        });
    });

    // Attach form submission handler for dynamically loaded forms
    function attachFormHandler() 
    {
        const form = document.querySelector("#content-area form"); // current form loaded on content area
        
        if (form) 
        {
            form.addEventListener("submit", function (event) {
                event.preventDefault(); // Prevent page reload

                const formData = new FormData(form);

                fetch(form.action || form.getAttribute("data-action"), {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) 
                    {
                        displayArea.innerHTML = `<p style="color: green;">${data.message}</p>`;
                    } 
                    else 
                    {
                        displayArea.innerHTML = `<p style="color: red;">${data.message}</p>`;
                    }
                })
                .catch(error => {
                    displayArea.innerHTML = "<p style='color: red;'>Error processing request.</p>";
                });
            });
        }
    }
});

</script>

<?php require_once '../include/footer.php'; ?>
