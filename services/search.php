<?php
require_once '../include/config.php';
require_once '../include/db.php';

$query = isset($_GET['query']) ? $_GET['query'] : '';

try {
    $stmt = $pdo->prepare("SELECT * FROM subcategories WHERE name LIKE :query");
    $stmt->bindValue(':query', "%$query%", PDO::PARAM_STR);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    include '../include/header.php';
    echo "<h1>Search Results</h1>";
    
    if ($results) {
        foreach ($results as $result) {
            echo "<p><a href='/subcategory.php?slug=" . htmlspecialchars($result['slug']) . "'>" . htmlspecialchars($result['name']) . "</a></p>";
        }
    } else {
        echo "<p>No results found for '" . htmlspecialchars($query) . "'</p>";
    }
    
    include '../include/footer.php';
} catch (PDOException $e) {
    error_log("Search error: " . $e->getMessage());
    echo "<p>An error occurred. Please try again later.</p>";
}
?>
