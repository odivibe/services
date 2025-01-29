
<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';

// Redirect logged-in user to index page if not authenticated
if (!isset($_SESSION['user_id'])) 
{
    header("Location: " . BASE_URL . "account/login.php");
    exit();
}

// Validate and process the form
/*if ($_SERVER['REQUEST_METHOD'] === 'POST') 
{
    $user_id = $_SESSION['user_id'];
    $service_id = $_POST['service_id'] ?? null;

    // Validate service ID
    if ($service_id && is_numeric($service_id)) 
    {
        try 
        {
            // Check if the service is already in favourites
            $stmt = $pdo->prepare("SELECT id FROM user_favourites WHERE user_id = :user_id AND service_id = :service_id");
            $stmt->execute([':user_id' => $user_id, ':service_id' => $service_id]);
            $is_favourite = $stmt->rowCount() > 0;

            if ($is_favourite) 
            {
                // Remove from favourites
                $stmt = $pdo->prepare("DELETE FROM user_favourites WHERE user_id = :user_id AND service_id = :service_id");
                $stmt->execute([':user_id' => $user_id, ':service_id' => $service_id]);
            } 
            else 
            {
                // Add to favourites
                $stmt = $pdo->prepare("INSERT INTO user_favourites (user_id, service_id) VALUES (:user_id, :service_id)");
                $stmt->execute([':user_id' => $user_id, ':service_id' => $service_id]);
            }
        } 
        catch (PDOException $e) 
        {
            error_log("Database Error: " . $e->getMessage(), 3, '../errorss/custom-error.log');
            //header("Location: " . BASE_URL . "error.php?code=500");
            exit();
        }
    }
}

// Redirect back to the service details page
//header('Location: ' . $_SERVER['HTTP_REFERER']);
exit();*/

?>
