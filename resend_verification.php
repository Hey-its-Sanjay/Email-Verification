<?php
session_start();
include 'db.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent headers already sent error
ob_start();

if (isset($_GET['email'])) {
    $email = $_GET['email'];
    
    // Check if email exists in pending_users table
    $check_sql = "SELECT * FROM pending_users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // User found in pending_users
        $user_data = $result->fetch_assoc();
        
        // Generate new verification token
        $token = bin2hex(random_bytes(50));
        
        // Update the token in the database
        $update_sql = "UPDATE pending_users SET verification_token = ?, updated_at = NOW() WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $token, $email);
        
        if ($update_stmt->execute()) {
            // Send verification email with new token
            $emailSent = sendVerificationEmail($email, $user_data['name'], $token);
            
            if ($emailSent) {
                $_SESSION['message'] = "A new verification link has been sent to your email. Please check your inbox and spam folder.";
            } else {
                $_SESSION['message'] = "Could not send verification email. Please try again later.";
            }
        } else {
            $_SESSION['message'] = "Error updating verification token: " . $conn->error;
        }
    } else {
        // Check if user exists in main users table
        $user_sql = "SELECT email_verified FROM users WHERE email = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param("s", $email);
        $user_stmt->execute();
        $user_stmt->store_result();
        
        if ($user_stmt->num_rows > 0) {
            $user_stmt->bind_result($email_verified);
            $user_stmt->fetch();
            
            if ($email_verified == 1) {
                $_SESSION['message'] = "This email is already verified. You can login.";
            } else {
                // User exists but not verified (unlikely with new workflow)
                $_SESSION['message'] = "There was an issue with your account. Please contact support.";
            }
        } else {
            $_SESSION['message'] = "Email not found. Please sign up first.";
        }
    }
    
    // End output buffering before redirect
    ob_end_clean();
    
    header("Location: login.php");
    exit();
}

function sendVerificationEmail($email, $name, $token) {
    // Create verification link using the configured website URL
    if (file_exists(__DIR__ . '/email_config.php')) {
        include_once __DIR__ . '/email_config.php';
        if (defined('WEBSITE_URL')) {
            $baseUrl = WEBSITE_URL;
        } else {
            // Fallback method if WEBSITE_URL is not defined
            $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $baseUrl .= $_SERVER['HTTP_HOST'];
            if (dirname($_SERVER['PHP_SELF']) != '/') {
                $baseUrl .= dirname($_SERVER['PHP_SELF']);
            }
        }
    } else {
        // Legacy method if email_config.php doesn't exist
        $baseUrl = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $baseUrl .= $_SERVER['HTTP_HOST'];
        if (dirname($_SERVER['PHP_SELF']) != '/') {
            $baseUrl .= dirname($_SERVER['PHP_SELF']);
        }
    }
    
    $verificationLink = $baseUrl . "/verify.php?email=" . urlencode($email) . "&token=" . urlencode($token);
    
    // Email subject
    $subject = "Verify Your Email Address - Hostel Management System";
    
    // Email body in HTML format
    $body = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; }
            .container { padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
            .header { background-color: #4CAF50; color: white; padding: 10px; text-align: center; border-radius: 5px 5px 0 0; }
            .button { display: inline-block; background-color: #4CAF50; color: white; text-decoration: none; padding: 10px 20px; border-radius: 5px; margin: 20px 0; }
            .footer { font-size: 12px; text-align: center; margin-top: 30px; color: #777; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>Email Verification</h2>
            </div>
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            <p>You requested a new verification link. To complete your registration, please click the button below to verify your email address:</p>
            <p style="text-align: center;">
                <a href="' . $verificationLink . '" class="button">Verify Email Address</a>
            </p>
            <p>If the button above doesn\'t work, you can also copy and paste the following link into your browser:</p>
            <p>' . $verificationLink . '</p>
            <p>This link will expire in 24 hours for security reasons.</p>
            <p>If you did not request this verification, please ignore this email.</p>
            <div class="footer">
                <p>This is an automated message, please do not reply to this email.</p>
                <p>&copy; ' . date("Y") . ' Hostel Management System. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';
    
    // Try to use PHPMailer if available
    $phpmailer_available = false;
    
    // Include PHPMailer helper
    if (file_exists(__DIR__ . '/phpmailer_fix.php')) {
        require_once __DIR__ . '/phpmailer_fix.php';
        // $phpmailer_available is set in the included file
    } else {
        // Legacy loading method as fallback
        if (file_exists(__DIR__ . '/vendor/autoload.php')) {
            // Using Composer
            require __DIR__ . '/vendor/autoload.php';
            $phpmailer_available = true;
        } elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
            // Using manual installation
            require __DIR__ . '/PHPMailer/src/Exception.php';
            require __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require __DIR__ . '/PHPMailer/src/SMTP.php';
            $phpmailer_available = true;
        } else {
            $phpmailer_available = false;
        }
    }
    
    $mailSent = false;
    
    // Use PHPMailer if available
    if ($phpmailer_available) {
        try {
            // The PHPMailer class should be available now through our helper
            $mail = new PHPMailer(true);
            
            // Load email configuration if available
            if (file_exists(__DIR__ . '/email_config.php')) {
                include_once __DIR__ . '/email_config.php';
                configureMailer($mail);
            } else {
                // Fallback configuration
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'Shresthasanjay087@gmail.com';
                $mail->Password   = 'your-app-password-here';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->setFrom('Shresthasanjay087@gmail.com', 'Hostel Management');
            }
            
            // Recipients
            $mail->addAddress($email, $name);
            
            // Content
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body;
            $mail->{"AltBody"} = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n\n"], $body));
            
            $mailSent = $mail->send();
        } catch (Exception $e) {
            error_log("PHPMailer Error: " . $e->getMessage());
            $mailSent = false;
        }
    } else {
        // Fall back to PHP mail() function if PHPMailer is not available
        // Configure mail settings first
        ini_set('SMTP', 'smtp.gmail.com');
        ini_set('smtp_port', 587);
        ini_set('sendmail_from', 'Shresthasanjay087@gmail.com');
        
        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
        $headers .= "From: Hostel Management <Shresthasanjay087@gmail.com>" . "\r\n";
        
        $mailSent = mail($email, $subject, $body, $headers);
    }
    
    // If email sending fails, save to file
    if (!$mailSent) {
        // Save the email details to a file for debugging/viewing
        $emailLogDir = __DIR__ . '/email_logs';
        if (!file_exists($emailLogDir)) {
            mkdir($emailLogDir, 0777, true);
        }
        
        $filename = $emailLogDir . '/email_' . time() . '_' . md5($email) . '.html';
        file_put_contents($filename, $body);
        
        error_log("Email to $email logged to file: $filename");
        
        // For development environments, simulate success
        if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1') {
            error_log("Running in development mode. Check email_viewer.php to see sent emails.");
            return true;
        }
        return false;
    }
    
    return $mailSent;
}

// If this page is accessed directly without an email parameter, show a form
if (!isset($_GET['email'])):
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - Hostel Management System</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
        }
        header {
            background-color: #4CAF50;
            color: white;
            padding: 1rem;
            text-align: center;
        }
        nav {
            display: flex;
            justify-content: center;
            background-color: #388E3C;
            padding: 0.5rem;
        }
        nav a {
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            margin: 0 0.25rem;
            border-radius: 3px;
        }
        nav a:hover {
            background-color: #2E7D32;
        }
        .container {
            max-width: 500px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 5px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h2 {
            color: #4CAF50;
            margin-top: 0;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .message {
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .links {
            margin-top: 20px;
            text-align: center;
        }
        .links a {
            color: #4CAF50;
            text-decoration: none;
            margin: 0 10px;
        }
        .links a:hover {
            text-decoration: underline;
        }
        footer {
            text-align: center;
            margin-top: 2rem;
            padding: 1rem;
            background-color: #f1f1f1;
            color: #666;
        }
    </style>
</head>
<body>
    <header>
        <h1>Hostel Management System</h1>
    </header>
    <nav>
        <a href="index.php">Home</a>
        <a href="login.php">Login</a>
        <a href="signup.php">Sign Up</a>
    </nav>

    <div class="container">
        <h2>Resend Verification Email</h2>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="message <?php echo strpos($_SESSION['message'], 'success') !== false ? 'success' : 'error'; ?>">
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        
        <form action="" method="GET">
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo isset($_GET['email']) ? htmlspecialchars($_GET['email']) : ''; ?>" placeholder="Enter your email" required>
            </div>
            
            <button type="submit">Resend Verification Email</button>
        </form>
        
        <div class="links">
            <a href="login.php">Back to Login</a>
            <a href="signup.php">Create New Account</a>
        </div>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
    </footer>
</body>
</html>
<?php endif; ?> 