<?php

session_start();

require_once '../include/db.php';

header('Content-Type: application/json');

// Initialize $user_id to prevent undefined variable issues
$user_id = '';

if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in'])) 
{
    $user_id = $_SESSION['user_id'];
    
    // Prepare and execute the query
    $stmt = $pdo->prepare("SELECT is_premium_user FROM users WHERE id = :id LIMIT 1");
    $stmt->bindParam(':id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result) 
    {
        // Use the value from the database
        $isPremiumUser = $result['is_premium_user'];
    } 
    else 
    {
        // Default to non-premium if no record is found
        $isPremiumUser = 0;
    }

    // Return JSON response
    echo json_encode(['isPremiumUser' => $isPremiumUser]);
} 

?>