<?php

function sanitizeInput($input) 
{
    // 1. Trim whitespace
    $input = trim($input);
    
    // 2. Remove HTML tags and encode special characters
    $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

    return $input;
}

?>