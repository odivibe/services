<?php

// Session cookie
/*session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => false, // true = production, https only, false = development, http
    'httponly' => true, // Prevent JavaScript access
    'samesite' => 'Strict' // Prevent cross-site attacks
]);
session_start();*/


define('SECRET_KEY', 'service ads classified'); // Secret key for hashing
define('CURRENCY_TYPE_SYMBOLE', '₦'); // currency symbole for prices
define('SITE_NAME', 'Service Ads Classified Platform');
define('BASE_URL', determineBaseURL());

// Custom error handler
/*set_error_handler(function($errno, $errstr, $errfile, $errline) {
    http_response_code(500);
    require_once '../include/500.php';
    exit();
});

// Custom exception handler
set_exception_handler(function($exception) {
    http_response_code(500);
    require_once '../include/500.php';
    exit();
});

// Handle 404 manually in routes
if (!file_exists($requestedFile)) {
    http_response_code(404);
    require_once '../include/404.php';
    exit();
}*/


function determineBaseURL() 
{
    $baseURL = 'http://localhost/classified/'; // Define the default base URL for localhost

    // Check if the server is localhost
    if ($_SERVER['HTTP_HOST'] === 'localhost') 
    {
        return $baseURL;
    }
    elseif ($_SERVER['HTTP_HOST'] === 'your-ngrok-subdomain.ngrok.io') 
    {
        return 'https://your-ngrok-subdomain.ngrok.io/';
    }
    else 
    {
        // Dynamically determine protocol (http or https)
        $protocol = isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] === "on" ? "https" : "http";
        return $protocol . "://" . $_SERVER["HTTP_HOST"] . "/classified/";
    }

    return $baseURL;
}

date_default_timezone_set('America/New_York'); // Timezone Setting


ini_set('log_errors', 1); // Enable error log
ini_set('error_log', BASE_URL . 'logs/custom-error.log'); // Log to custom error file

// Debug Mode (for development)
define('DEBUG_MODE', true);

if (DEBUG_MODE) 
{
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} 
else 
{
    ini_set('display_errors', 0);
    error_reporting(0);
}

?>
