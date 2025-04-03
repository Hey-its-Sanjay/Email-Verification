<?php
// This file allows viewing sent emails in development environments

// Set proper content type for HTML display
header('Content-Type: text/html; charset=utf-8');

// Email logs directory
$emailDir = __DIR__ . "/email_logs";

// Create directory if it doesn't exist
if (!file_exists($emailDir)) {
    mkdir($emailDir, 0777, true);
    echo "<h1>Email Viewer</h1>";
    echo "<p>No emails found yet. The email logs directory has been created.</p>";
    echo "<p><a href='signup.php'>Go back to signup</a> and try sending an email.</p>";
    exit;
}

// Get all HTML files in the logs directory
$emails = glob($emailDir . "/*.html");

// Sort by modification time (most recent first)
usort($emails, function($a, $b) {
    return filemtime($b) - filemtime($a);
});

// If no emails found
if (empty($emails)) {
    echo "<h1>Email Viewer</h1>";
    echo "<p>No emails found yet.</p>";
    echo "<p><a href='signup.php'>Go back to signup</a> and try sending an email.</p>";
    exit;
}

// Delete email if requested
if (isset($_GET["delete"]) && file_exists($_GET["delete"]) && strpos($_GET["delete"], $emailDir) === 0) {
    unlink($_GET["delete"]);
    header("Location: email_viewer.php");
    exit;
}

// Delete all emails if requested
if (isset($_GET["delete_all"]) && $_GET["delete_all"] === "1") {
    foreach ($emails as $email) {
        if (file_exists($email) && strpos($email, $emailDir) === 0) {
            unlink($email);
        }
    }
    header("Location: email_viewer.php");
    exit;
}

// Function to extract email subject
function extractSubject($content) {
    $subjectMatch = [];
    preg_match('/<h2>(.*?)<\/h2>/s', $content, $subjectMatch);
    return isset($subjectMatch[1]) ? trim($subjectMatch[1]) : 'Email Verification';
}

// Function to extract recipient name
function extractRecipient($content) {
    $recipientMatch = [];
    preg_match('/<p>Dear\s+([^<,]+)/', $content, $recipientMatch);
    return isset($recipientMatch[1]) ? trim($recipientMatch[1]) : 'Unknown';
}

// Function to extract verification link if exists
function extractVerificationLink($content) {
    $linkMatch = [];
    preg_match('/href=[\'"]([^\'"]+verify\.php[^\'"]+)[\'"]/', $content, $linkMatch);
    return isset($linkMatch[1]) ? $linkMatch[1] : '';
}

// View a specific email if requested
if (isset($_GET["view"]) && file_exists($_GET["view"]) && strpos($_GET["view"], $emailDir) === 0) {
    $emailContent = file_get_contents($_GET["view"]);
    $emailSubject = extractSubject($emailContent);
    $recipientName = extractRecipient($emailContent);
    $verificationLink = extractVerificationLink($emailContent);
    $emailDate = date("Y-m-d H:i:s", filemtime($_GET["view"]));
    $emailFilename = basename($_GET["view"]);
    
    // Display email with viewer interface
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>View Email: <?php echo htmlspecialchars($emailSubject); ?></title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; color: #333; line-height: 1.6; }
            .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
            .header { background-color: #4CAF50; color: white; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
            .header h1 { margin: 0; font-size: 22px; }
            .controls { background-color: #fff; padding: 15px; margin-bottom: 20px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .email-meta { margin-bottom: 15px; background-color: #e9e9e9; padding: 10px; border-radius: 5px; }
            .email-meta p { margin: 5px 0; }
            .email-content { background-color: #fff; border: 1px solid #ddd; border-radius: 5px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
            .btn { display: inline-block; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px; font-weight: bold; }
            .btn-primary { background-color: #4CAF50; color: white; }
            .btn-secondary { background-color: #2196F3; color: white; }
            .btn-danger { background-color: #f44336; color: white; }
            .btn:hover { opacity: 0.9; }
            .verification-section { margin-top: 20px; background-color: #e9e9e9; padding: 15px; border-radius: 5px; }
            @media (max-width: 768px) {
                .header { flex-direction: column; text-align: center; }
                .header .actions { margin-top: 10px; }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>Email Viewer - <?php echo htmlspecialchars($emailSubject); ?></h1>
            <div class="actions">
                <a href="email_viewer.php" class="btn btn-secondary">Back to List</a>
                <a href="?delete=<?php echo urlencode($_GET["view"]); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this email?')">Delete Email</a>
            </div>
        </div>

        <div class="container">
            <div class="controls">
                <a href="signup.php" class="btn btn-primary">Back to Signup</a>
                <?php if (!empty($verificationLink)): ?>
                <a href="<?php echo htmlspecialchars($verificationLink); ?>" class="btn btn-primary" target="_blank">Test Verification Link</a>
                <?php endif; ?>
                <a href="check_mail.php" class="btn btn-secondary">Check Mail Configuration</a>
            </div>

            <div class="email-meta">
                <p><strong>To:</strong> <?php echo htmlspecialchars($recipientName); ?></p>
                <p><strong>Date Sent:</strong> <?php echo htmlspecialchars($emailDate); ?></p>
                <p><strong>File:</strong> <?php echo htmlspecialchars($emailFilename); ?></p>
                <?php if (!empty($verificationLink)): ?>
                <p><strong>Verification URL:</strong> <small><?php echo htmlspecialchars($verificationLink); ?></small></p>
                <?php endif; ?>
            </div>

            <div class="email-content">
                <?php echo $emailContent; ?>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Otherwise, show the list of emails
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Viewer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; color: #333; line-height: 1.6; }
        .container { max-width: 1000px; margin: 0 auto; padding: 20px; }
        .header { background-color: #4CAF50; color: white; padding: 15px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { margin: 0; font-size: 22px; }
        .email-list { list-style: none; padding: 0; }
        .email-item { margin-bottom: 15px; padding: 15px; background-color: white; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .email-item:hover { background-color: #f9f9f9; }
        .email-link { color: #0066cc; text-decoration: none; display: block; }
        .email-link:hover { text-decoration: underline; }
        .email-meta { font-size: 13px; color: #666; margin-top: 8px; }
        .subject { font-weight: bold; font-size: 16px; color: #333; margin-bottom: 5px; }
        .actions { display: flex; margin-top: 10px; justify-content: space-between; }
        .btn { display: inline-block; padding: 8px 15px; text-decoration: none; border-radius: 4px; margin-right: 10px; font-weight: bold; }
        .btn-primary { background-color: #4CAF50; color: white; }
        .btn-secondary { background-color: #2196F3; color: white; }
        .btn-danger { background-color: #f44336; color: white; }
        .btn:hover { opacity: 0.9; }
        .controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background-color: #fff; padding: 15px; border-radius: 5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .search-form { display: flex; }
        .search-form input { padding: 8px; border: 1px solid #ddd; border-radius: 4px 0 0 4px; width: 250px; }
        .search-form button { background-color: #4CAF50; color: white; border: none; border-radius: 0 4px 4px 0; padding: 8px 15px; cursor: pointer; }
        .no-emails { padding: 20px; background-color: #fff; border-radius: 5px; text-align: center; }
        @media (max-width: 768px) {
            .header { flex-direction: column; text-align: center; }
            .header .actions { margin-top: 10px; }
            .controls { flex-direction: column; }
            .search-form { margin-bottom: 10px; width: 100%; }
            .search-form input { width: 100%; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Email Viewer (Development Only)</h1>
        <div class="actions">
            <a href="signup.php" class="btn btn-primary">Back to Signup</a>
            <a href="check_mail.php" class="btn btn-secondary">Check Mail Configuration</a>
        </div>
    </div>

    <div class="container">
        <div class="controls">
            <div>
                <p>This tool allows you to view emails sent by the system during development.</p>
            </div>
            <div class="actions">
                <a href="?delete_all=1" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete ALL emails? This cannot be undone.')">Delete All Emails</a>
            </div>
        </div>
        
        <h2>Sent Emails (<?php echo count($emails); ?>):</h2>
        <ul class="email-list">
            <?php foreach ($emails as $email): 
                $timestamp = filemtime($email);
                $date = date("Y-m-d H:i:s", $timestamp);
                $filename = basename($email);
                
                // Only read file if it exists
                if (!file_exists($email)) continue;
                
                // Read content of email
                $content = file_get_contents($email);
                
                // Extract information
                $recipient = extractRecipient($content);
                $subject = extractSubject($content);
                $verificationLink = extractVerificationLink($content);
            ?>
            <li class="email-item">
                <div class="subject"><?php echo htmlspecialchars($subject); ?></div>
                <a class="email-link" href="?view=<?php echo urlencode($email); ?>">
                    To: <?php echo htmlspecialchars($recipient); ?>
                    <div class="email-meta">
                        <strong>Date:</strong> <?php echo $date; ?><br>
                        <strong>File:</strong> <?php echo htmlspecialchars($filename); ?>
                    </div>
                </a>
                <div class="actions">
                    <a href="?view=<?php echo urlencode($email); ?>" class="btn btn-secondary">View Email</a>
                    <?php if (!empty($verificationLink)): ?>
                    <a href="<?php echo htmlspecialchars($verificationLink); ?>" class="btn btn-primary" target="_blank">Test Verification Link</a>
                    <?php endif; ?>
                    <a href="?delete=<?php echo urlencode($email); ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this email?')">Delete</a>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</body>
</html> 