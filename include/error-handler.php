<?php
/**
 * Handles errors by logging them and setting a session message.
 *
 * @param Exception|null $e Exception object (optional).
 * @param string $userMessage Custom user-friendly message.
 * @param string|null $technicalDetails Technical details for logging (if no exception is provided).
 * @param string $redirectPage Page to redirect the user to after logging the error.
 */
function handleError($userMessage, $e = null, $technicalDetails = null, $redirectPage = "errors/error-message.php") 
{
    // Prepare the log message
    $logMessage = "[" . date('Y-m-d H:i:s') . "] ";

    if ($e instanceof Exception) 
    {
        $logMessage .= "Error: " . $e->getMessage() 
                    . " | File: " . $e->getFile() 
                    . " | Line: " . $e->getLine();
    } 
    else 
    {
         $logMessage .= "Error: " . ($technicalDetails ?: 'No technical details provided.');
    }

    $logMessage .= PHP_EOL;
    $logMessage .= PHP_EOL;

    // Log the error to a custom file
    $logFile = '../errors/custom-error.log';
    error_log($logMessage, 3, $logFile);

    $_SESSION['show_message'] = $userMessage;

    // Redirect the user to the error message page
    header("Location: " . BASE_URL . $redirectPage);
}

?>
