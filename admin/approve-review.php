<?php
// Approve Review
require_once 'config.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /login.php');
    exit;
}

if (isset($_GET['id'])) {
    $reviewId = $_GET['id'];

    // Approve review
    $stmt = $pdo->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
    $stmt->execute([$reviewId]);

    header('Location: admin-panel.php');
    exit;
}
?>
