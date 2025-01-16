/**
AJAX handler for email resend verification button in message-output-manager.php
*/

let clickCount = 0;
const resendButton = document.getElementById('resend-button');
const messageOutput = document.getElementById('message-output');

resendButton.addEventListener("click", function () 
{
    clickCount++;

    // Disable the button after 3 clicks
    if (clickCount >= 3) 
    {
        //resendButton.disabled = true;
        resendButton.style.display = "none";
        messageOutput.innerHTML = "Max Attempts Reached.";
        return;
    }

    // Temporarily disable the button while processing
    resendButton.disabled = true;
    resendButton.textContent = "Processing...";

    // Create an XMLHttpRequest object
    const xhr = new XMLHttpRequest();
    xhr.open("POST", "resend-verification-email.php", true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

    xhr.onload = function () {
        // Reset button state after the response is received
        resendButton.disabled = false;
        resendButton.textContent = "Resend Verification Email";

        // Update the message output
        if (xhr.status === 200) 
        {
            messageOutput.innerHTML = xhr.responseText;
        } 
        else 
        {
            messageOutput.innerHTML = "An error occurred while sending the verification email.";
        }
    };

    xhr.onerror = function () {
        // Handle network errors
        messageOutput.innerHTML = "A network error occurred. Please try again later.";
        resendButton.disabled = false;
        resendButton.textContent = "Resend Verification Email";
    };

    // Send the request
    xhr.send();
});
