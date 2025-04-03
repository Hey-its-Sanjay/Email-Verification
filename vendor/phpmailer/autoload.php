<?php
// PHPMailer simple autoloader
require_once __DIR__ . '/src/PHPMailer.php';

// Make the PHPMailer class available in the global namespace for backward compatibility
if (!class_exists('PHPMailer', false)) {
    class_alias('PHPMailer\\PHPMailer\\PHPMailer', 'PHPMailer');
} 