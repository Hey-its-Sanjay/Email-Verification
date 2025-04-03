<?php 

session_start();
include 'db.php';

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start output buffering to prevent headers already sent error
ob_start();

if(isset($_GET['token']) && isset($_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    
    // Check if the token exists in pending_users table
    $sql = "SELECT * FROM pending_users WHERE email = ? AND verification_token = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $email, $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        // Found the pending registration
        $user_data = $result->fetch_assoc();
        
        // First check if this email already exists in the users table
        // (could happen if someone tries to register again while verification is pending)
        $check_sql = "SELECT id FROM users WHERE email = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            // User already exists in main table
            $_SESSION['message'] = "This email is already registered. Please login.";
            
            // Clean up the pending registration since it's now a duplicate
            $delete_sql = "DELETE FROM pending_users WHERE email = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("s", $email);
            $delete_stmt->execute();
            
            // End output buffering before redirect
            ob_end_clean();
            
            header("Location: login.php");
            exit();
        }
        
        // Create new user in the main users table
        $insert_sql = "INSERT INTO users (name, email, password, email_verified, created_at) VALUES (?, ?, ?, 1, NOW())";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("sss", $user_data['name'], $user_data['email'], $user_data['password']);
        
        if($insert_stmt->execute()) {
            // Registration successful, now delete from pending_users table
            $delete_sql = "DELETE FROM pending_users WHERE email = ?";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("s", $email);
            $delete_stmt->execute();
            
            // Send welcome email
            sendWelcomeEmail($user_data['email'], $user_data['name']);
            
            $_SESSION['message'] = "Your email has been verified and your account has been created successfully! You can now login.";
            
            // End output buffering before redirect
            ob_end_clean();
            
            header("Location: login.php");
            exit();
        } else {
            $_SESSION['message'] = "Error creating account: " . $conn->error;
            
            // End output buffering before redirect
            ob_end_clean();
            
            header("Location: login.php");
            exit();
        }
    } else {
        // Check if the user might already be in the main users table
        $check_sql = "SELECT email_verified FROM users WHERE email = ? LIMIT 1";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $email);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $check_stmt->bind_result($email_verified);
            $check_stmt->fetch();
            
            if ($email_verified == 1) {
                $_SESSION['message'] = "This email is already verified. Please login.";
            } else {
                $_SESSION['message'] = "Invalid verification link.";
            }
        } else {
            $_SESSION['message'] = "Invalid verification link or the link has expired.";
        }
        
        // End output buffering before redirect
        ob_end_clean();
        
        header("Location: login.php");
        exit();
    }
} else {
    $_SESSION['message'] = "Invalid verification link.";
    
    // End output buffering before redirect
    ob_end_clean();
    
    header("Location: login.php");
    exit();
}

// Function to send welcome email
function sendWelcomeEmail($email, $name) {
    // Create login link using the configured website URL
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
    
    $loginLink = $baseUrl . "/login.php";
    
    // Email subject
    $subject = "Welcome to Hostel Management System";
    
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
                <h2>Welcome to Hostel Management System</h2>
            </div>
            <p>Dear ' . htmlspecialchars($name) . ',</p>
            <p>Thank you for verifying your email address. Your account has been successfully created!</p>
            <p>You can now login to access all the features of our Hostel Management System:</p>
            <p style="text-align: center;">
                <a href="' . $loginLink . '" class="button">Login to Your Account</a>
            </p>
            <p>If you have any questions or need assistance, please don\'t hesitate to contact our support team.</p>
            <p>We hope you enjoy using our system!</p>
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
        
        $filename = $emailLogDir . '/welcome_' . time() . '_' . md5($email) . '.html';
        file_put_contents($filename, $body);
        
        error_log("Welcome email to $email logged to file: $filename");
    }
    
    return $mailSent;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification - Hostel Management System</title>
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
            max-width: 600px; 
            margin: 50px auto; 
            padding: 20px; 
            background-color: white; 
            border-radius: 5px; 
            box-shadow: 0 2px 4px rgba(0,0,0,0.1); 
        }
        h1 { color: #4CAF50; }
        .icon { font-size: 48px; margin-bottom: 20px; }
        .success { color: #4CAF50; }
        .error { color: #f44336; }
        p { line-height: 1.6; }
        .button { 
            display: inline-block; 
            padding: 10px 20px; 
            background-color: #4CAF50; 
            color: white; 
            text-decoration: none; 
            border-radius: 4px; 
            margin-top: 20px; 
        }
        .button:hover { background-color: #45a049; }
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
        <h1>Email Verification</h1>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="<?php echo strpos($_SESSION['message'], 'successfully') !== false ? 'success' : 'error'; ?>">
                <div class="icon"><?php echo strpos($_SESSION['message'], 'successfully') !== false ? '✓' : '✗'; ?></div>
                <p><?php echo $_SESSION['message']; ?></p>
            </div>
            
            <?php if(strpos($_SESSION['message'], 'successfully') !== false): ?>
                <p>You can now access all features of our Hostel Management System.</p>
                <a href="login.php" class="button">Login to Your Account</a>
            <?php else: ?>
                <p>If you're having trouble with the verification process, you can:</p>
                <ul>
                    <li><a href="resend_verification.php">Request a new verification link</a></li>
                    <li><a href="signup.php">Try signing up again</a></li>
                    <li>Contact our support team for assistance</li>
                </ul>
            <?php endif; ?>
            
        <?php else: ?>
            <p>Processing your verification request...</p>
        <?php endif; ?>
    </div>
    
    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
    </footer>
</body>
</html>