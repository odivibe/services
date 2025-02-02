<?php
session_start();
require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/header.php';
?>

<div id="manage-account">
    <div class="sidebar">
        <a href="#" data-page="change-email">Change Email</a>
        <a href="#" data-page="change-password">Change Password</a>
        <a href="#" data-page="change-profile-picture">Change Profile Picture</a>
        <a href="#" data-page="change-phone">Change Phone</a>
        <a href="#" data-page="add-socials">Add Socials</a>
    </div>

    <div id="content-area">
        <h2>Welcome to Your Account Area</h2>
        <p>Manage your account here.</p>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    const displayArea = document.getElementById('content-area');

    // Attach event listeners to sidebar buttons
    sidebarLinks.forEach(button => {
        button.addEventListener('click', function (event) 
        {
            event.preventDefault();
            const page = button.getAttribute('data-page');

            fetch(`${page}.php`)
            .then(response => response.text())
            .then(data => {
                displayArea.innerHTML = data; // Insert form into display area
                attachFormHandler(); // Attach event listener to the new form
            })
            .catch(error => {
                displayArea.innerHTML = "<p>Error loading content. Please try again later.</p>";
            });
        });
    });

    // Function to handle form submission dynamically
    function attachFormHandler() 
    {
        const form = document.querySelector("#content-area form"); // current form in content area

        if (form) 
        {
            const submitButton = form.querySelector("button[type='submit']");

            form.addEventListener("submit", function (event) {

                clearErrors(); // clear previouse errors

                event.preventDefault();

                submitButton.disabled = true;

                const formData = new FormData(form);

                fetch(form.action, {
                    method: "POST",
                    body: formData,
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) 
                    {
                        displayArea.innerHTML += `<p style="color: green;">${data.message}</p>`;
                    } 
                    else 
                    {
                        if (data.success === false) 
                        {
                            displayErrorMessages(data.errors);
                        
                            //displayErrorMessages(data.errors);
                            
                            // Re-enable the button after form submission is done
                            submitButton.disabled = false;
                        }
                    }
                })
                .catch(error => {
                    displayArea.innerHTML += "<p style='color: red;'>Error processing request.</p>";

                    // Re-enable the button after form submission is done
                    submitButton.disabled = false;
                });
            });
        }
    }


    // Function to clear errors
    function clearErrors() 
    {
        const errorDivs = document.querySelectorAll(".error");
        errorDivs.forEach(div => {
            div.textContent = ""; // Clear error message
            //div.style.display = "none"; // Hide error div
        });
    }

    // Display error messages from backend
    function displayErrorMessages(errors) 
    {
        Object.entries(errors).forEach(([field, error]) => {
            const errorElement = document.getElementById(`${field}_error`);
            if (errorElement) 
            {
                errorElement.textContent = error;
            }
        });
    }
});

</script>

<?php require_once '../include/footer.php'; ?>
