<?php
/**
 * PHPMailer Import Helper
 * 
 * This file provides a function to properly import PHPMailer classes
 * regardless of installation method (Composer or manual installation)
 */

// Function to load PHPMailer
function loadPHPMailer() {
    // Check if PHPMailer is already loaded
    if (class_exists('\\PHPMailer\\PHPMailer\\PHPMailer', false)) {
        return true;
    }
    
    $phpmailer_loaded = false;
    
    // Try loading via Composer autoload
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        $phpmailer_loaded = class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
    }
    
    // Try manual installation with src directory
    if (!$phpmailer_loaded && file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        $phpmailer_loaded = class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
    }
    
    // Check if PHPMailer is in a subfolder of the vendor directory
    if (!$phpmailer_loaded && file_exists(__DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php')) {
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/Exception.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/PHPMailer.php';
        require_once __DIR__ . '/vendor/phpmailer/phpmailer/src/SMTP.php';
        $phpmailer_loaded = class_exists('\\PHPMailer\\PHPMailer\\PHPMailer');
    }
    
    // If PHPMailer is now loaded, create class aliases for backward compatibility
    if ($phpmailer_loaded) {
        // Create a class alias for PHPMailer in the global namespace
        if (!class_exists('PHPMailer', false)) {
            class_alias('\\PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
        }
        
        // Create a class alias for Exception in the global namespace
        if (!class_exists('phpmailerException', false)) {
            class_alias('\\PHPMailer\\PHPMailer\\Exception', 'phpmailerException');
        }
        
        return true;
    }
    
    return false;
}

// Auto-load PHPMailer when this file is included
$phpmailer_available = loadPHPMailer(); 