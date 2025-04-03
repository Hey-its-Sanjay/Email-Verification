<?php
session_start();
include 'db.php';

// Set page title for header
$pageTitle = "Sign Up - Hostel Management System";

// For debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if PHPMailer is installed, if not create a note about it
$phpmailer_installed = file_exists(__DIR__ . '/vendor/autoload.php') || 
                       file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php');

if (!$phpmailer_installed) {
    $phpmailer_note = "PHPMailer is not installed. For better email delivery, please install PHPMailer by running:<br>";
    $phpmailer_note .= "<code>composer require phpmailer/phpmailer</code><br>";
    $phpmailer_note .= "Or download from <a href='https://github.com/PHPMailer/PHPMailer' target='_blank'>GitHub</a> and extract to a PHPMailer folder.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    
    // Start output buffering to prevent headers already sent error
    ob_start();
    
    // Check if email already exists in the users table
    $check_sql = "SELECT email FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $email);
    $check_stmt->execute();
    $check_stmt->store_result();
    
    if ($check_stmt->num_rows > 0) {
        // Email already exists in main users table
        $_SESSION['message'] = "Error: Email is already registered. Please use a different email or try to login.";
        header("Location: signup.php");
        exit();
    }
    
    // Also check the pending_users table
    $check_pending_sql = "SELECT email FROM pending_users WHERE email = ?";
    $check_pending_stmt = $conn->prepare($check_pending_sql);
    $check_pending_stmt->bind_param("s", $email);
    $check_pending_stmt->execute();
    $check_pending_stmt->store_result();
    
    if ($check_pending_stmt->num_rows > 0) {
        // Email exists in pending users
        $_SESSION['message'] = "Error: This email is already pending verification. Please check your inbox for the verification link or request a new one.";
        header("Location: signup.php");
        exit();
    }
    
    // Email not found in either table, proceed with registration
    $token = bin2hex(random_bytes(50)); // Generate verification token
    $created_at = date('Y-m-d H:i:s'); // Current timestamp

    // Insert user into pending_users table
    $sql = "INSERT INTO pending_users (name, email, password, verification_token, created_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssss", $name, $email, $password, $token, $created_at);
    
    if ($stmt->execute()) {
        // Send verification email
        $emailSent = sendVerificationEmail($email, $name, $token);
        
        if ($emailSent) {
            $_SESSION['message'] = "Registration pending! A verification link has been sent to your email. Please check your inbox (and spam folder) to complete your registration.";
        } else {
            $_SESSION['message'] = "Account created but could not send verification email. Please contact support.";
        }
    } else {
        $_SESSION['message'] = "Error: " . $stmt->error;
    }
    
    // End output buffering and discard any output
    ob_end_clean();
    
    header("Location: signup.php");
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
            <p>Thank you for registering with our Hostel Management System. To complete your registration, please click the button below to verify your email address:</p>
            <p style="text-align: center;">
                <a href="' . $verificationLink . '" class="button">Verify Email Address</a>
            </p>
            <p>If the button above doesn\'t work, you can also copy and paste the following link into your browser:</p>
            <p>' . $verificationLink . '</p>
            <p>This link will expire in 24 hours for security reasons.</p>
            <p>If you did not request this registration, please ignore this email.</p>
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
            // Check if email_viewer.php exists, if not the file will be created by the code below
            if (!file_exists(__DIR__ . '/email_viewer.php')) {
                error_log("Email viewer does not exist. It will be created.");
            }
            
            error_log("Running in development mode. Check email_viewer.php to see sent emails.");
            return true;
        }
        return false;
    }
    
    return $mailSent;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : 'Hostel Management System'; ?></title>
    <style>
        /* Basic styles */
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
        }
        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
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
        /* Rest of your styles remain the same */
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
        <div class="auth-container">
            <div class="auth-box">
                <h2>Create an Account</h2>
                
                <?php if (isset($phpmailer_note)): ?>
                    <div class="note"><?php echo $phpmailer_note; ?></div>
                <?php endif; ?>
                
                <form action="signup.php" method="POST">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" placeholder="Enter your full name" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" placeholder="Enter your email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Choose a password" required>
                    </div>
                    
                    <button type="submit" class="btn-submit">Sign Up</button>
                    
                    <?php if (isset($_SESSION['message'])): ?>
                        <div class="message <?php echo strpos($_SESSION['message'], 'Error') !== false ? 'error' : 'success'; ?>">
                            <?php echo $_SESSION['message']; $_SESSION['message'] = ''; ?>
                        </div>
                    <?php endif; ?>
                </form>
                
                <div class="auth-footer">
                    <p>Already have an account? <a href="login.php">Login</a></p>
                    <p><a href="resend_verification.php">Resend verification email</a></p>
                </div>
            </div>
            
            <?php if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1'): ?>
            <div class="dev-tools-box">
                <h3>Development Tools</h3>
                <ul class="dev-links">
                    <li><a href="email_viewer.php">View Sent Emails</a></li>
                    <li><a href="check_mail.php">Check Mail Configuration</a></li>
                    <li><a href="install_phpmailer.php">Install PHPMailer</a></li>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <footer style="text-align: center; margin-top: 2rem; padding: 1rem; background-color: #f1f1f1; color: #666;">
        <p>&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
    </footer>
</body>
</html>
