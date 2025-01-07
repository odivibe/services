<?php
// This script will generate slug

function generateSlug($string) 
{
    // Convert to lowercase
    $string = strtolower($string);
    // Replace spaces with hyphens
    $string = preg_replace('/\s+/', '-', $string);
    // Remove non-alphanumeric characters
    $string = preg_replace('/[^a-z0-9-]/', '', $string);
    return $string;
}

?>