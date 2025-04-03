<?php
session_start();
include 'db.php';

// Set page title for header
$pageTitle = "Login - Hostel Management System";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    
    // First check if email exists in pending_users table
    $pending_sql = "SELECT email FROM pending_users WHERE email = ?";
    $pending_stmt = $conn->prepare($pending_sql);
    $pending_stmt->bind_param("s", $email);
    $pending_stmt->execute();
    $pending_stmt->store_result();
    
    if ($pending_stmt->num_rows > 0) {
        // Email exists in pending_users, needs verification
        $_SESSION['message'] = "Your email has not been verified yet. Please check your inbox for the verification link.";
        // Show resend link
        $_SESSION['resend_email'] = $email;
        header("Location: login.php");
        exit();
    }
    
    // Check if user exists in main users table
    $sql = "SELECT id, name, password, email_verified FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        $stmt->bind_result($id, $name, $hashed_password, $email_verified);
        $stmt->fetch();
        
        // Verify password
        if (password_verify($password, $hashed_password)) {
            // Check if email is verified
            if ($email_verified == 1) {
                // Login successful
                $_SESSION['user_id'] = $id;
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
                $_SESSION['logged_in'] = true;
                
                // Redirect to dashboard or home page
                header("Location: index.php");
                exit();
            } else {
                $_SESSION['message'] = "Your email has not been verified yet. Please check your inbox for the verification link.";
                // Show resend link
                $_SESSION['resend_email'] = $email;
            }
        } else {
            $_SESSION['message'] = "Invalid email or password.";
        }
    } else {
        $_SESSION['message'] = "Invalid email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Hostel Management System</title>
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
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            color: #4CAF50;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .signup-link {
            text-align: center;
            margin-top: 15px;
        }
        .signup-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        .signup-link a:hover {
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

    <div class="login-container">
        <h2>Login</h2>
        
        <?php if(isset($_SESSION['message'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php endif; ?>

        <?php if(isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="" method="post">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" name="login">Login</button>
        </form>

        <div class="signup-link">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p>
            <p>Email not verified? <a href="resend_verification.php">Resend verification</a></p>
        </div>
    </div>

    <footer>
        <p>&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
    </footer>
</body>
</html> 