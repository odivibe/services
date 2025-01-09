// Counter variable to track the number of clicks
var clickCount = 0;
var resendConfirmation = document.getElementById('resend-confirmation');
var resendMessage = document.getElementById('resend-message');
var messageOutput = document.getElementById('message-output');

function handleClick() 
{
    clickCount++;

    if (clickCount === 3) 
    {
        resendConfirmation.disabled = true;
    }

    // Indicate that the email is being sent
    resendMessage.innerHTML = 'Sending confirmation email...';

    // Make an asynchronous request to the server to resend the confirmation email
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'resend-verification-email.php', true);
    xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() 
    {
        if (xhr.readyState == 4 && xhr.status == 200) 
        {
            resendMessage.innerHTML = '';
            messageOutput.innerHTML = '';
            messageOutput.innerHTML = xhr.responseText;
        }
    };

    xhr.send();
}

resendConfirmation.addEventListener('click', handleClick);
