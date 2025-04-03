<?php
// Set error reporting for better debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Function to check if a function is available
function check_function($function_name) {
    return function_exists($function_name) && !in_array($function_name, explode(',', ini_get('disable_functions')));
}

// Function to get configuration value with a fallback
function get_config_value($ini_value, $default = 'Not configured') {
    $value = ini_get($ini_value);
    return (!empty($value) && $value != '0') ? $value : $default;
}

// Check server details
$server_info = [];
$server_info['PHP Version'] = PHP_VERSION;
$server_info['Server Software'] = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$server_info['Operating System'] = PHP_OS;
$server_info['Hostname'] = gethostname();

// Check mail configuration
$mail_info = [];
$mail_info['mail() Function Available'] = check_function('mail') ? 'Yes' : 'No';
$mail_info['mail() Function Disabled'] = in_array('mail', explode(',', ini_get('disable_functions'))) ? 'Yes' : 'No';
$mail_info['Sendmail Path'] = get_config_value('sendmail_path');
$mail_info['SMTP Host'] = get_config_value('SMTP');
$mail_info['SMTP Port'] = get_config_value('smtp_port');
$mail_info['Sendmail From'] = get_config_value('sendmail_from');

// Check PHPMailer availability
$phpmailer_info = [];
$phpmailer_installed = false;

// Check if PHPMailer is installed via Composer
if (file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
    $phpmailer_info['Installation Method'] = 'Composer';
    $phpmailer_installed = true;
}
// Check if PHPMailer is installed manually
elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
    $phpmailer_info['Installation Method'] = 'Manual';
    $phpmailer_installed = true;
} else {
    $phpmailer_info['Installation Method'] = 'Not installed';
}

// Check PHP extensions
$extensions_info = [];
$extensions_info['OpenSSL Extension'] = extension_loaded('openssl') ? 'Enabled' : 'Disabled';
$extensions_info['IMAP Extension'] = extension_loaded('imap') ? 'Enabled' : 'Disabled';
$extensions_info['DOM Extension'] = extension_loaded('dom') ? 'Enabled' : 'Disabled';
$extensions_info['JSON Extension'] = extension_loaded('json') ? 'Enabled' : 'Disabled';
$extensions_info['PCRE Extension'] = extension_loaded('pcre') ? 'Enabled' : 'Disabled';
$extensions_info['cURL Extension'] = extension_loaded('curl') ? 'Enabled' : 'Disabled';
$extensions_info['MBString Extension'] = extension_loaded('mbstring') ? 'Enabled' : 'Disabled';

// Check email configuraiton file
$config_file_exists = file_exists(__DIR__ . '/email_config.php');

// Test sending a mail if requested
$test_result = null;
$test_error = null;
$suggestions = null;

if (isset($_POST['test_email']) && !empty($_POST['test_email'])) {
    $test_email = filter_var($_POST['test_email'], FILTER_SANITIZE_EMAIL);
    
    if ($phpmailer_installed) {
        // Try sending via PHPMailer
        if (file_exists(__DIR__ . '/phpmailer_fix.php')) {
            require_once __DIR__ . '/phpmailer_fix.php';
        } elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
            require_once __DIR__ . '/vendor/autoload.php';
        } elseif (file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        }
        
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            
            // Load configuration if available
            if ($config_file_exists) {
                include_once __DIR__ . '/email_config.php';
                configureMailer($mail);
            } else {
                // Basic configuration
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'Shresthasanjay087@gmail.com';
                $mail->Password = 'your-app-password-here'; // This needs to be changed
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->setFrom('Shresthasanjay087@gmail.com', 'Mail Test');
            }
            
            // Enable debug output
            $mail->SMTPDebug = 2;
            ob_start();
            
            $mail->addAddress($test_email);
            $mail->Subject = 'PHPMailer Test Email';
            $mail->Body = 'This is a test email sent from your hostel management system to verify email functionality.';
            
            $mail->send();
            $debug_output = ob_get_clean();
            
            $test_result = "Test email sent successfully via PHPMailer. Check your inbox (and spam folder).";
            $test_details = $debug_output;
        } catch (Exception $e) {
            $debug_output = ob_get_clean();
            $test_error = "PHPMailer Error: " . $e->getMessage();
            $test_details = $debug_output;
        }
    } else {
        // Check if we should use mail() function or skip it
        $skip_mail_function = false;
        
        if ($config_file_exists) {
            include_once __DIR__ . '/email_config.php';
            // Check if USE_PHPMAILER_ONLY is defined and true
            $skip_mail_function = defined('USE_PHPMAILER_ONLY') && USE_PHPMAILER_ONLY === true;
        }
        
        if ($skip_mail_function) {
            $test_error = "Gmail requires TLS encryption which the PHP mail() function doesn't support properly. Please install PHPMailer to send emails.";
            $suggestions = "
- Gmail requires a secure connection with TLS encryption
- The PHP mail() function doesn't support STARTTLS properly
- PHPMailer is required to send emails via Gmail
- Click the 'Install PHPMailer Now' button below to install it automatically";
        } else {
            // Try sending via PHP mail() function with proper settings
            if ($config_file_exists) {
                // Set SMTP settings via ini_set for mail()
                ini_set('SMTP', SMTP_HOST);
                ini_set('smtp_port', SMTP_PORT);
                ini_set('sendmail_from', SENDER_EMAIL);
            } else {
                // Set Gmail SMTP settings
                ini_set('SMTP', 'smtp.gmail.com');
                ini_set('smtp_port', 587);
                ini_set('sendmail_from', 'Shresthasanjay087@gmail.com');
            }
            
            $subject = 'Mail Function Test Email';
            $message = 'This is a test email sent from your hostel management system to verify email functionality.';
            $headers = "From: Shresthasanjay087@gmail.com\r\n";
            $headers .= "Reply-To: Shresthasanjay087@gmail.com\r\n";
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            
            if (mail($test_email, $subject, $message, $headers)) {
                $test_result = "Test email sent successfully via PHP mail() function. Check your inbox (and spam folder).";
            } else {
                $error = error_get_last();
                $test_error = "Failed to send test email via PHP mail() function. Error: " . ($error ? $error['message'] : 'Unknown error');
                $suggestions = "
- Gmail requires a secure connection with TLS encryption
- The PHP mail() function doesn't support STARTTLS properly
- PHPMailer is required to send emails via Gmail
- Click the 'Install PHPMailer Now' button below to install it automatically";
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mail Configuration Check</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1, h2 {
            color: #4CAF50;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        .status {
            font-weight: bold;
        }
        .good {
            color: #4CAF50;
        }
        .warning {
            color: #FF9800;
        }
        .error {
            color: #F44336;
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        pre {
            background-color: #f5f5f5;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        button, input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover, input[type="submit"]:hover {
            background-color: #45a049;
        }
        input[type="email"] {
            padding: 10px;
            width: 250px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #0066cc;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
        .action-buttons {
            margin: 20px 0;
        }
        .action-buttons .btn {
            margin-right: 10px;
        }
        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            white-space: pre-line;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Mail Configuration Check</h1>
        
        <h2>Server Information</h2>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
            </tr>
            <?php foreach ($server_info as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Mail Configuration</h2>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <?php foreach ($mail_info as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                    <td class="status">
                        <?php 
                        if ($key == 'mail() Function Available' && $value == 'Yes') {
                            echo '<span class="good">OK</span>';
                        } elseif ($key == 'mail() Function Disabled' && $value == 'No') {
                            echo '<span class="good">OK</span>';
                        } elseif (($key == 'Sendmail Path' || $key == 'SMTP Host') && $value != 'Not configured') {
                            echo '<span class="good">OK</span>';
                        } elseif ($key == 'mail() Function Available' && $value == 'No') {
                            echo '<span class="error">Problem</span>';
                        } elseif ($key == 'mail() Function Disabled' && $value == 'Yes') {
                            echo '<span class="error">Problem</span>';
                        } else {
                            echo '<span class="warning">Warning</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>PHPMailer Information</h2>
        <table>
            <tr>
                <th>Parameter</th>
                <th>Value</th>
                <th>Status</th>
            </tr>
            <?php foreach ($phpmailer_info as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td><?php echo htmlspecialchars($value); ?></td>
                    <td class="status">
                        <?php 
                        if ($key == 'Installation Method' && ($value == 'Composer' || $value == 'Manual')) {
                            echo '<span class="good">OK</span>';
                        } else {
                            echo '<span class="warning">Warning</span>';
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            
            <tr>
                <td>Email Configuration File</td>
                <td><?php echo $config_file_exists ? 'Present' : 'Not found'; ?></td>
                <td class="status">
                    <?php echo $config_file_exists ? '<span class="good">OK</span>' : '<span class="warning">Warning</span>'; ?>
                </td>
            </tr>
        </table>
        
        <h2>Required PHP Extensions</h2>
        <table>
            <tr>
                <th>Extension</th>
                <th>Status</th>
            </tr>
            <?php foreach ($extensions_info as $key => $value): ?>
                <tr>
                    <td><?php echo htmlspecialchars($key); ?></td>
                    <td class="status <?php echo $value == 'Enabled' ? 'good' : 'error'; ?>">
                        <?php echo htmlspecialchars($value); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        
        <h2>Send Test Email</h2>
        <form method="post" action="">
            <input type="email" name="test_email" placeholder="Enter email address" required>
            <input type="submit" value="Send Test Email">
        </form>
        
        <?php if ($test_result): ?>
            <div class="success-message">
                <p><?php echo $test_result; ?></p>
            </div>
            <?php if (isset($test_details)): ?>
                <h3>Debug Information:</h3>
                <pre><?php echo htmlspecialchars($test_details); ?></pre>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($test_error): ?>
            <div class="error-message">
                <p><?php echo $test_error; ?></p>
            </div>
            <?php if (isset($test_details)): ?>
                <h3>Debug Information:</h3>
                <pre><?php echo htmlspecialchars($test_details); ?></pre>
            <?php endif; ?>
            
            <?php if (strpos($test_error, 'Gmail requires') !== false || strpos($test_error, 'STARTTLS') !== false): ?>
                <div class="action-buttons">
                    <a href="simple_phpmailer_installer.php" class="btn">Install PHPMailer Now</a>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <h2>Recommendations</h2>
        <ol>
            <?php if (!$phpmailer_installed): ?>
                <li>Install PHPMailer library - <a href="install_phpmailer.php">Run Installer</a></li>
            <?php endif; ?>
            
            <?php if (!$config_file_exists): ?>
                <li>Verify that the email_config.php file exists and contains proper credentials.</li>
            <?php endif; ?>
            
            <?php if ($mail_info['mail() Function Available'] == 'No' || $mail_info['mail() Function Disabled'] == 'Yes'): ?>
                <li>The PHP mail() function is not available. Use PHPMailer with SMTP configuration instead.</li>
            <?php endif; ?>
            
            <?php foreach ($extensions_info as $key => $value): ?>
                <?php if ($value == 'Disabled'): ?>
                    <li>Enable the <?php echo str_replace(' Extension', '', $key); ?> PHP extension.</li>
                <?php endif; ?>
            <?php endforeach; ?>
            
            <li>For Gmail: Make sure you've created an App Password - <a href="https://myaccount.google.com/apppasswords" target="_blank">Generate here</a></li>
            <li>Update the password in email_config.php with your Gmail App Password.</li>
            <li>Verify that your SMTP configuration matches your email provider's requirements.</li>
        </ol>
        
        <h2>XAMPP Mail Configuration</h2>
        <p>If you're using XAMPP on Windows, follow these steps to configure mail:</p>
        <ol>
            <li>Open <code>php.ini</code> file in your XAMPP installation directory (typically <code>D:\xampp\php\php.ini</code>)</li>
            <li>Find the <code>[mail function]</code> section</li>
            <li>Set the following values:
                <pre>
[mail function]
SMTP=smtp.gmail.com
smtp_port=587
sendmail_from=Shresthasanjay087@gmail.com
mail.add_x_header=On
                </pre>
            </li>
            <li>Save the php.ini file</li>
            <li>Restart Apache server in XAMPP control panel</li>
            <li><strong>Important:</strong> Since Gmail requires secure authentication, it's recommended to use PHPMailer instead of the native mail() function.</li>
        </ol>
        
        <h2>PHPMailer Quick Installation</h2>
        <p>To make email work properly, install PHPMailer with these steps:</p>
        <ol>
            <li><a href="install_phpmailer.php" class="button">Run Automatic Installer</a></li>
            <li>Or <a href="https://github.com/PHPMailer/PHPMailer/archive/refs/heads/master.zip" target="_blank">Download PHPMailer manually</a>, extract to a folder named "PHPMailer" in your project root.</li>
            <li>After installation, test sending email with the form above.</li>
        </ol>
        
        <h2>Suggestions</h2>
        <?php if (isset($suggestions)): ?>
            <div class="warning-message">
                <p><?php echo $suggestions; ?></p>
            </div>
        <?php endif; ?>
        
        <p>
            <a href="signup.php" class="back-link">Back to Signup Page</a>
        </p>
    </div>
</body>
</html> 