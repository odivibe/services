<?php
// Reject Review
require_once 'config.php';
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /login.php');
    exit;
}

if (isset($_GET['id'])) {
    $reviewId = $_GET['id'];

    // Reject review
    $stmt = $pdo->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$reviewId]);

    header('Location: admin-panel.php');
    exit;
}
?>
