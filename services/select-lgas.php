<?php

require_once '../include/config.php';
require_once '../include/db.php';

if (isset($_GET['state_id'])) 
{
    $state_id = $_GET['state_id'];

    $stmt = $pdo->prepare("SELECT * FROM lgas WHERE state_id = ?");
    $stmt->execute([$state_id]);
    $lgas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($lgas) > 0) 
    {
        foreach ($lgas as $lga) 
        {
            echo "<option value='{$lga['id']}'>{$lga['name']}</option>";
        }
    } 
    else 
    {
        echo "<option value=''>---No LGAs available---</option>";
    }
}

?>
