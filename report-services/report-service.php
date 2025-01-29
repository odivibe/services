<?php

session_start();

require_once '../include/config.php';
require_once '../include/db.php';
require_once '../include/input-cleaner.php';
require_once '../include/error-handler.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) 
{
    header("Location: " . BASE_URL . "account/login.php");
    exit();
}

$errors = [];

// Verify service ID and hash
if (isset($_GET['id'], $_GET['token'])) 
{
    $service_id = intval($_GET['id']);
    $hash = $_GET['token'];
    $user_id = $_SESSION['user_id'];

    // Verify the hash
    $expectedHash = hash_hmac('sha256', $service_id, SECRET_KEY);

    if ($hash !== $expectedHash) 
    {
        header("Location: " . BASE_URL . "index.php");
        exit();
    }
}


// Check if the user has already reported this ad
$stmt = $pdo->prepare("SELECT * FROM ad_reports WHERE service_id = ? AND user_id = ?");
$stmt->execute([$service_id, $user_id]);

if ($stmt->rowCount() > 0) 
{
    $_SESSION['report_message'] = 'You have already reported this ad.';
    header("Location: " . BASE_URL . "report-services/report-service-message-output.php");
    exit();
}

// Process report submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['report_ad'])) 
{
    $reason = isset($_POST['reason']) ? sanitizeInput($_POST['reason']) : '';
    $details = isset($_POST['details']) ? sanitizeInput($_POST['details']) : '';

    if (empty($reason)) 
    {
        $errors['reason'] = 'Please select a reason for reporting.';
    }

    if (empty($details)) 
    {
        $errors['details'] = 'Please fill the details.';
    }

    if (empty($errors)) 
    {
        // Insert the report into the database
        $stmt = $pdo->prepare("INSERT INTO ad_reports (service_id, user_id, report_reason, report_details) VALUES (?, ?, ?, ?)");
        $stmt->execute([$service_id, $user_id, $reason, $details]);

        // Recalculate the report count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM ad_reports WHERE service_id = ?");
        $stmt->execute([$service_id]);
        $report_count = $stmt->fetchColumn();

        // Set the threshold for flagging an ad (e.g., 5 reports)
        $threshold = 5;

        if ($report_count >= $threshold) 
        {
            // Flag the ad as reported
            $stmt = $pdo->prepare("UPDATE services SET is_flagged = 1 WHERE id = ?");
            $stmt->execute([$service_id]);

            $_SESSION['report_message'] = 'This ad has been flagged for review by the admin.';
            header("Location: " . BASE_URL . "report-services/report-service-message-output.php");
            exit();
        } 
        else 
        {
            $_SESSION['report_message'] = 'Thank you for reporting this ad.';
            header("Location: " . BASE_URL . "report-services/report-service-message-output.php");
            exit();
        }
    }
}

?>

<?php require_once '../include/header.php'; ?>

<main class="body-container">
    <div class="form-container">
        <h1>Report Service</h1>
        <form action="" method="POST">

            <!-- Description -->
            <div class="form-group">
                <label for="reason">Reason:<span class="required">*</span></label>
                <select name="reason" id="reason">
                    <option value="">-- Select one --</option>
                    <option value="misleading_info">Misleading Information</option>
                    <option value="illegal_content">Illegal Content</option>
                    <option value="spam">Spam</option>
                    <option value="offensive_language">Offensive Language</option>
                    <option value="fraud">Fraud</option>
                    <option value="copyright_violation">Copyright Violation</option>
                    <option value="scam">Scam</option>
                    <option value="inappropriate_content">Inappropriate Content</option>
                    <option value="duplicate_listing">Duplicate Listing</option>
                    <option value="privacy_violation">Privacy Violation</option>
                    <option value="discrimination">Discrimination or Hate Speech</option>
                    <option value="false_claims">False or Deceptive Claims</option>
                    <option value="violent_content">Violent or Harmful Content</option>
                </select>
                <div id="reason-error" class="error">
                    <?php echo isset($errors['reason']) ? $errors['reason'] : ''; ?>
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="details">Report Details:<span class="required">*</span></label>
                <textarea name="details" id="details" cols="50" rows="10" maxlength="200">
                </textarea>
                <div id="details-error" class="error">
                    <?php echo isset($errors['details']) ? $errors['details'] : ''; ?>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="report_ad">Report Ad</button>
            </div>
        </form>
    </div>
</main>

<?php require_once '../include/footer.php'; ?>
