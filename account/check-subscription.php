<?php
header('Content-Type: application/json'); // Ensure the response is JSON

//$isPremiumUserDb = 1; // 1 = Premium user, 0 = Non-premium user
$isPremiumUser = 1;

/*if ($isPremiumUserDb === 1) 
{
    $isPremiumUser = 1;
}
else
{
    $isPremiumUser = 0;
}*/

echo json_encode(['isPremiumUser' => $isPremiumUser]);
?>

