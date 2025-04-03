<?php
/**
 * Centralized Email Configuration
 * 
 * This file contains the email configuration settings for the application.
 * Include this file whenever sending emails to ensure consistent configuration.
 */

// Gmail SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');
define('SMTP_AUTH', true);
define('SMTP_USERNAME', 'Shresthasanjay087@gmail.com');
define('SMTP_PASSWORD', 'gtyt cgfw ghxi jhhc'); // Your Gmail App Password

// For native PHP mail() function (which doesn't support STARTTLS properly)
define('USE_PHPMAILER_ONLY', true); // Set to true to disable fallback to mail() function

// Website URL (Change this to your public website URL)
// Use your computer's IP address instead of localhost so mobile devices can connect
define('WEBSITE_URL', 'http://192.168.1.76/Hostel');

// Examples:
// - Local testing: define('WEBSITE_URL', 'http://localhost/Hostel');
// - Production: define('WEBSITE_URL', 'https://yourdomain.com');
// - IP Address: define('WEBSITE_URL', 'http://192.168.1.100/Hostel');

// When testing locally, if you want to use the system from other devices:
// 1. Find your computer's IP address (using 'ipconfig' on Windows or 'ifconfig' on macOS/Linux)
// 2. Replace 'localhost' with your IP address, e.g., 'http://192.168.1.100/Hostel'
// 3. Make sure your firewall allows incoming connections to your web server

// Sender Information
define('SENDER_EMAIL', 'Shresthasanjay087@gmail.com');
define('SENDER_NAME', 'Hostel Management');

// Development Mode Settings
define('DEV_MODE', ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1'));

/**
 * Get Email Configuration for PHPMailer
 * 
 * @param object $mail PHPMailer instance
 * @return void
 */
function configureMailer($mail) {
    // Server settings
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = SMTP_AUTH;
    $mail->Username   = SMTP_USERNAME;
    $mail->Password   = SMTP_PASSWORD;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    
    // Debug settings (only in development mode)
    if (DEV_MODE) {
        // Use 0 for no output, 1 for commands, 2 for commands and data, 3 for verbose output, 4 for even more verbose
        $mail->SMTPDebug = 0; // Disable debug output to prevent header issues
    }
    
    // Set default sender
    $mail->setFrom(SENDER_EMAIL, SENDER_NAME);
}

/**
 * Instructions for getting Gmail App Password:
 * 
 * 1. Go to your Google Account at https://myaccount.google.com/
 * 2. Go to Security
 * 3. Under "Signing in to Google," select "App passwords"
 *    (You may need to enable 2-Step Verification first)
 * 4. Select "Mail" as the app and your device
 * 5. Click "Generate"
 * 6. Use the 16-character password that appears
 * 7. Replace 'your-app-password-here' in this file with that password
 */ 