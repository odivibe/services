<?php
// This file handles ajax for subcategories drop down element to be populated dynamically

require_once '../include/config.php';
require_once '../include/db.php';

if (isset($_GET['category_id'])) 
{
    $category_id = $_GET['category_id'];

    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE category_id = ?");
    $stmt->execute([$category_id]);
    $subcategories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (count($subcategories) > 0) 
    {
        foreach ($subcategories as $subcategory) 
        {
            echo "<option value='{$subcategory['id']}'>{$subcategory['name']}</option>";
        }
    } 
    else 
    {
        echo "<option value=''>---No subcategories found---</option>";
    }
}

?>
