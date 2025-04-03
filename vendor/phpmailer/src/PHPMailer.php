<?php
// PHPMailer implementation with SMTP capability
class PHPMailer {
    public $Host = 'smtp.gmail.com'; // Default SMTP server
    public $SMTPAuth = true;
    public $Username = '';
    public $Password = '';
    public $SMTPSecure = 'tls';
    public $Port = 587;
    public $SMTPDebug = 0;
    private $to = [];
    private $from = ['email' => '', 'name' => ''];
    public $Subject = '';
    public $Body = '';
    public $isHTML = false;
    public $ErrorInfo = '';
    private $socket = null;
    private $useSMTP = false;

    public function isSMTP() {
        // Configure for SMTP
        $this->useSMTP = true;
        return $this;
    }

    public function setFrom($email, $name = '') {
        $this->from = ['email' => $email, 'name' => $name];
        return $this;
    }

    public function addAddress($email, $name = '') {
        $this->to[] = ['email' => $email, 'name' => $name];
        // Log recipient for debugging
        error_log("PHPMailer: Adding recipient: $email");
        return $this;
    }

    public function isHTML($isHTML = true) {
        $this->isHTML = $isHTML;
        return $this;
    }

    public function send() {
        if (empty($this->from['email']) || empty($this->to)) {
            $this->ErrorInfo = 'Sender or recipient not specified';
            error_log("PHPMailer Error: " . $this->ErrorInfo);
            return false;
        }

        try {
            error_log("PHPMailer: Attempting to send email from {$this->from['email']} to " . 
                      print_r(array_map(function($r) { return $r['email']; }, $this->to), true));
            
            if ($this->useSMTP && !empty($this->Username) && !empty($this->Password)) {
                return $this->sendSMTP();
            } else {
                return $this->sendMail();
            }
        } catch (Exception $e) {
            $this->ErrorInfo = $e->getMessage();
            error_log("PHPMailer Exception: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendMail() {
        // Send using PHP mail() function
        foreach ($this->to as $recipient) {
            $headers = "From: " . $this->from['name'] . " <" . $this->from['email'] . ">\r\n";
            $headers .= "Reply-To: " . $this->from['email'] . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            
            if ($this->isHTML) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            
            error_log("PHPMailer: Sending mail to: " . $recipient['email']);
            $success = mail($recipient['email'], $this->Subject, $this->Body, $headers);
            
            if (!$success) {
                $this->ErrorInfo = 'Email could not be sent via mail() function';
                error_log("PHPMailer Error: Failed to send email to: " . $recipient['email']);
                
                // Check mail server configuration
                $sendmail_path = ini_get('sendmail_path');
                $smtp_host = ini_get('SMTP');
                $smtp_port = ini_get('smtp_port');
                error_log("PHPMailer Debug - Sendmail path: " . ($sendmail_path ?: 'Not configured'));
                error_log("PHPMailer Debug - SMTP host: " . ($smtp_host ?: 'Not configured'));
                error_log("PHPMailer Debug - SMTP port: " . ($smtp_port ?: 'Not configured'));
                
                return false;
            }
            
            error_log("PHPMailer: Email successfully sent to " . $recipient['email'] . " via mail()");
        }
        return true;
    }
    
    private function sendSMTP() {
        error_log("PHPMailer: Using SMTP connection to {$this->Host}:{$this->Port}");
        
        // Create a compatible socket connection string
        $socket_context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]);
        
        $connect_host = ($this->SMTPSecure === 'ssl') ? 'ssl://'.$this->Host : $this->Host;
        
        try {
            // Use stream_socket_client instead of fsockopen for better TLS support
            $this->socket = @stream_socket_client(
                "$connect_host:{$this->Port}",
                $errno,
                $errstr,
                30,
                STREAM_CLIENT_CONNECT,
                $socket_context
            );
            
            if (!$this->socket) {
                $this->ErrorInfo = "SMTP connection failed: $errstr ($errno)";
                error_log("PHPMailer Error: " . $this->ErrorInfo);
                return false;
            }
            
            // Read server greeting
            $greeting = $this->readResponse();
            if (!$greeting || substr($greeting, 0, 3) !== '220') {
                $this->ErrorInfo = "SMTP server did not respond with greeting: $greeting";
                error_log("PHPMailer Error: " . $this->ErrorInfo);
                $this->closeConnection();
                return false;
            }
            
            // Send EHLO
            $response = $this->sendCommand("EHLO " . gethostname());
            if (substr($response, 0, 3) !== '250') {
                $this->ErrorInfo = "SMTP EHLO command failed: $response";
                error_log("PHPMailer Error: " . $this->ErrorInfo);
                $this->closeConnection();
                return false;
            }
            
            // Start TLS if needed and not already using SSL
            if ($this->SMTPSecure === 'tls') {
                $response = $this->sendCommand("STARTTLS");
                if (substr($response, 0, 3) !== '220') {
                    $this->ErrorInfo = "SMTP STARTTLS command failed: $response";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
                
                // Enable crypto on the stream
                if (!@stream_socket_enable_crypto(
                    $this->socket,
                    true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                )) {
                    $this->ErrorInfo = "Failed to enable TLS encryption";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
                
                // Send EHLO again after TLS
                $response = $this->sendCommand("EHLO " . gethostname());
                if (substr($response, 0, 3) !== '250') {
                    $this->ErrorInfo = "SMTP EHLO command failed after TLS: $response";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
            }
            
            // Authenticate
            if ($this->SMTPAuth) {
                $response = $this->sendCommand("AUTH LOGIN");
                if (substr($response, 0, 3) !== '334') {
                    $this->ErrorInfo = "SMTP AUTH command failed: $response";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
                
                $response = $this->sendCommand(base64_encode($this->Username));
                if (substr($response, 0, 3) !== '334') {
                    $this->ErrorInfo = "SMTP username authentication failed: $response";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
                
                $response = $this->sendCommand(base64_encode($this->Password));
                if (substr($response, 0, 3) !== '235') {
                    $this->ErrorInfo = "SMTP password authentication failed: $response";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
                
                error_log("PHPMailer: SMTP authentication successful");
            }
            
            // Set sender
            $response = $this->sendCommand("MAIL FROM:<" . $this->from['email'] . ">");
            if (substr($response, 0, 3) !== '250') {
                $this->ErrorInfo = "SMTP MAIL FROM command failed: $response";
                error_log("PHPMailer Error: " . $this->ErrorInfo);
                $this->closeConnection();
                return false;
            }
            
            // Set recipients
            foreach ($this->to as $recipient) {
                $response = $this->sendCommand("RCPT TO:<" . $recipient['email'] . ">");
                if (substr($response, 0, 3) !== '250' && substr($response, 0, 3) !== '251') {
                    $this->ErrorInfo = "SMTP RCPT TO command failed: $response";
                    error_log("PHPMailer Error: " . $this->ErrorInfo);
                    $this->closeConnection();
                    return false;
                }
            }
            
            // Send data
            $response = $this->sendCommand("DATA");
            if (substr($response, 0, 3) !== '354') {
                $this->ErrorInfo = "SMTP DATA command failed: $response";
                error_log("PHPMailer Error: " . $this->ErrorInfo);
                $this->closeConnection();
                return false;
            }
            
            // Prepare headers
            $headers = "From: " . $this->from['name'] . " <" . $this->from['email'] . ">\r\n";
            $headers .= "Reply-To: " . $this->from['email'] . "\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "To: " . $this->to[0]['email'] . "\r\n";
            $headers .= "Subject: " . $this->Subject . "\r\n";
            
            if ($this->isHTML) {
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            } else {
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            }
            
            // Send headers and body
            fwrite($this->socket, $headers . "\r\n" . $this->Body . "\r\n.\r\n");
            $response = $this->readResponse();
            
            // Check if message was accepted
            if (substr($response, 0, 3) !== '250') {
                $this->ErrorInfo = "SMTP message sending failed: $response";
                error_log("PHPMailer Error: " . $this->ErrorInfo);
                $this->closeConnection();
                return false;
            }
            
            // Quit and close connection
            $this->closeConnection();
            error_log("PHPMailer: Email successfully sent via SMTP");
            return true;
            
        } catch (Exception $e) {
            $this->ErrorInfo = "SMTP Exception: " . $e->getMessage();
            error_log("PHPMailer Error: " . $this->ErrorInfo);
            if ($this->socket) {
                $this->closeConnection();
            }
            return false;
        }
    }
    
    private function sendCommand($command) {
        if ($this->SMTPDebug) {
            error_log("SMTP >> " . $command);
        }
        fwrite($this->socket, $command . "\r\n");
        return $this->readResponse();
    }
    
    private function readResponse() {
        $response = '';
        while ($line = fgets($this->socket, 515)) {
            $response .= $line;
            // If line starts with a digit and a space, we're done
            if (strlen($line) > 3 && substr($line, 3, 1) === ' ') {
                break;
            }
        }
        
        if ($this->SMTPDebug) {
            error_log("SMTP << " . trim($response));
        }
        
        return $response;
    }
    
    private function closeConnection() {
        if ($this->socket) {
            // Send QUIT command
            $this->sendCommand("QUIT");
            // Close the socket
            fclose($this->socket);
            $this->socket = null;
        }
    }
}