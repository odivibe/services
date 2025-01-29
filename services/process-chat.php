<?php
header('Content-Type: application/json');

/*if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat'])) 
{
    $message = isset($_POST['message']) ? trim($_POST['message']) : '';

    if (empty($message)) {
        // Respond with success
        echo json_encode([
            'success' => false,
            'message' => 'error msg'
        ]);
    }
    else
    {
        // Respond with success
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
    }

    exit();
}*/

echo json_encode([
            'success' => true,
            'message' => 'yesssssssss'
        ]);
?>
