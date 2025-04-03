<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to get server IP addresses
function getServerIPs() {
    $ips = array();
    
    // Try to get IPs through various methods
    // Method 1: Using PHP's built-in functions
    if (function_exists('exec')) {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows
            exec('ipconfig', $output);
            foreach ($output as $line) {
                if (strpos($line, 'IPv4') !== false) {
                    preg_match('/\d+\.\d+\.\d+\.\d+/', $line, $matches);
                    if (isset($matches[0])) {
                        $ips[] = array(
                            'ip' => $matches[0],
                            'source' => 'ipconfig (Windows)',
                        );
                    }
                }
            }
        } else {
            // Linux/Unix/Mac
            exec('ifconfig || ip addr', $output);
            $output = implode("\n", $output);
            preg_match_all('/inet (?:addr:)?(\d+\.\d+\.\d+\.\d+)/', $output, $matches);
            foreach ($matches[1] as $ip) {
                if ($ip != '127.0.0.1') {
                    $ips[] = array(
                        'ip' => $ip,
                        'source' => 'ifconfig/ip addr (Unix)',
                    );
                }
            }
        }
    }
    
    // Method 2: Using server variables
    if (isset($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] != '127.0.0.1') {
        $ips[] = array(
            'ip' => $_SERVER['SERVER_ADDR'],
            'source' => '$_SERVER[\'SERVER_ADDR\']',
        );
    }
    
    if (isset($_SERVER['LOCAL_ADDR']) && $_SERVER['LOCAL_ADDR'] != '127.0.0.1') {
        $ips[] = array(
            'ip' => $_SERVER['LOCAL_ADDR'],
            'source' => '$_SERVER[\'LOCAL_ADDR\']',
        );
    }
    
    // Method 3: Using network functions
    if (function_exists('gethostname') && function_exists('gethostbyname')) {
        $hostname = gethostname();
        $ip = gethostbyname($hostname);
        if ($ip != $hostname && $ip != '127.0.0.1') {
            $ips[] = array(
                'ip' => $ip,
                'source' => 'gethostbyname(gethostname())',
            );
        }
    }
    
    // Method 4: Use socket connection
    if (function_exists('socket_create') && function_exists('socket_connect')) {
        $sock = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_connect($sock, '8.8.8.8', 53);
        socket_getsockname($sock, $ip);
        socket_close($sock);
        if (isset($ip) && $ip != '127.0.0.1') {
            $ips[] = array(
                'ip' => $ip,
                'source' => 'UDP socket to 8.8.8.8',
            );
        }
    }
    
    return $ips;
}

// Get the server IPs
$server_ips = getServerIPs();

// Get the host and URI for demonstration
$host = $_SERVER['HTTP_HOST'];
$uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
$http_protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';

// Current IP configuration
$current_config = null;
if (file_exists(__DIR__ . '/email_config.php')) {
    include_once __DIR__ . '/email_config.php';
    if (defined('WEBSITE_URL')) {
        $current_config = WEBSITE_URL;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server IP Configuration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        h1, h2 {
            color: #4CAF50;
        }
        code {
            background-color: #f4f4f4;
            padding: 2px 5px;
            border-radius: 3px;
            font-family: monospace;
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
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        pre {
            background-color: #f4f4f4;
            padding: 10px;
            border-radius: 5px;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .card {
            background-color: #fff;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .code-block {
            background-color: #f8f9fa;
            padding: 15px;
            border-left: 4px solid #4CAF50;
            margin-bottom: 20px;
        }
        .url {
            word-break: break-all;
        }
    </style>
</head>
<body>
    <h1>Server IP Configuration Tool</h1>
    
    <div class="card">
        <h2>Detected IP Addresses</h2>
        <?php if (empty($server_ips)): ?>
            <p class="warning">No IP addresses detected. You may need to manually check your IP address.</p>
        <?php else: ?>
            <table>
                <tr>
                    <th>IP Address</th>
                    <th>Source</th>
                    <th>URL to Use</th>
                </tr>
                <?php foreach ($server_ips as $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($data['ip']); ?></td>
                        <td><?php echo htmlspecialchars($data['source']); ?></td>
                        <td class="url"><?php echo $http_protocol . '://' . $data['ip'] . $uri; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>Current Configuration</h2>
        <?php if ($current_config): ?>
            <p>Current WEBSITE_URL in email_config.php:</p>
            <pre><?php echo htmlspecialchars($current_config); ?></pre>
        <?php else: ?>
            <p class="warning">WEBSITE_URL is not defined in email_config.php or the file doesn't exist.</p>
        <?php endif; ?>
    </div>
    
    <div class="card">
        <h2>How to Make Verification Links Work on Mobile</h2>
        <p>Follow these steps to make your verification links work when clicked from a mobile device:</p>
        
        <ol>
            <li>
                <p>Open the <code>email_config.php</code> file and update the WEBSITE_URL constant with your server's IP address:</p>
                <div class="code-block">
                    <code>define('WEBSITE_URL', 'http://<?php echo !empty($server_ips) ? $server_ips[0]['ip'] : 'YOUR_SERVER_IP'; ?>/Hostel');</code>
                </div>
            </li>
            
            <li>
                <p>Make sure your mobile device is connected to the same WiFi network as your computer.</p>
            </li>
            
            <li>
                <p>Ensure your firewall allows incoming connections to your web server (XAMPP).</p>
                <p>For Windows: Check Windows Firewall settings and add XAMPP as an exception.</p>
            </li>
            
            <li>
                <p>Test if your mobile can access your server by visiting this URL on your mobile browser:</p>
                <p class="url"><code><?php echo $http_protocol . '://' . (!empty($server_ips) ? $server_ips[0]['ip'] : 'YOUR_SERVER_IP') . $uri; ?></code></p>
            </li>
        </ol>
    </div>
    
    <div class="card">
        <h2>Testing</h2>
        <p>After updating your configuration, test it by:</p>
        <ol>
            <li>Register a new account with a valid email address</li>
            <li>Check the email on your mobile device</li>
            <li>Click the verification link in the email</li>
            <li>You should be redirected to your server and the verification should work</li>
        </ol>
    </div>
    
    <p><a href="signup.php">Back to Signup Page</a></p>
</body>
</html> 