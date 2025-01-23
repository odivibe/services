<?php
// Admin check
session_start();
if (!isset($_SESSION['admin_id'])) {
    header('Location: /login.php'); // Redirect if not logged in as admin
    exit;
}

// Fetch pending reviews
$stmt = $pdo->prepare("SELECT * FROM reviews WHERE status = 'pending'");
$stmt->execute();
$pendingReviews = $stmt->fetchAll();
?>

<h2>Pending Reviews</h2>
<table>
    <thead>
        <tr>
            <th>Service</th>
            <th>User</th>
            <th>Rating</th>
            <th>Comment</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($pendingReviews as $review): ?>
            <tr>
                <td><?php echo $review['service_id']; ?></td>
                <td><?php echo $review['user_id']; ?></td>
                <td><?php echo $review['rating']; ?></td>
                <td><?php echo $review['comment']; ?></td>
                <td>
                    <a href="approve-review.php?id=<?php echo $review['id']; ?>">Approve</a> | 
                    <a href="reject-review.php?id=<?php echo $review['id']; ?>">Reject</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
