<?php
session_start();

// Set page title for header
$pageTitle = "Home - Hostel Management System";

// Remove header include
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
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
        
        /* Hero Section */
        .hero-section {
            background-color: #4CAF50;
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        
        .hero-content h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 30px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .hero-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
        }
        
        .btn-primary {
            background-color: white;
            color: #4CAF50;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #f1f1f1;
            transform: translateY(-2px);
        }
        
        .btn-secondary {
            background-color: transparent;
            color: white;
            padding: 12px 25px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: bold;
            border: 2px solid white;
            transition: all 0.3s;
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }
        
        /* Features Section */
        .features-section {
            padding: 80px 0;
            background-color: #f9f9f9;
        }
        
        .section-title {
            text-align: center;
            margin-bottom: 50px;
            color: #333;
            font-size: 2rem;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        
        .feature-card {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
        }
        
        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: #333;
            margin-bottom: 15px;
        }
        
        /* About Section */
        .about-section {
            padding: 80px 0;
        }
        
        .about-content {
            display: flex;
            align-items: center;
            gap: 50px;
        }
        
        .about-text {
            flex: 1;
        }
        
        .about-text h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .about-text p {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .about-text a {
            color: #4CAF50;
            text-decoration: none;
            font-weight: bold;
        }
        
        .about-text a:hover {
            text-decoration: underline;
        }
        
        .about-image {
            flex: 1;
        }
        
        .about-image img {
            max-width: 100%;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        /* Dev Tools Section */
        .dev-tools-section {
            padding: 60px 0;
            background-color: #f1f1f1;
        }
        
        .dev-tools-intro {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .dev-tools-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .dev-tool-card {
            background-color: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            text-decoration: none;
            color: #333;
            transition: transform 0.3s;
        }
        
        .dev-tool-card:hover {
            transform: translateY(-3px);
        }
        
        .dev-tool-card h3 {
            color: #4CAF50;
            margin-bottom: 10px;
        }
        
        /* Responsive fixes */
        @media (max-width: 768px) {
            .about-content {
                flex-direction: column;
            }
            
            .hero-content h1 {
                font-size: 2rem;
            }
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
        <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php endif; ?>
    </nav>

<div class="hero-section">
    <div class="container">
        <div class="hero-content">
            <h1>Welcome to Hostel Management System</h1>
            <p>A secure and efficient system for managing hostel operations and accommodations.</p>
            
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="hero-buttons">
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="signup.php" class="btn btn-secondary">Sign Up</a>
                </div>
            <?php else: ?>
                <div class="hero-buttons">
                    <a href="dashboard.php" class="btn btn-primary">Go to Dashboard</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="features-section">
    <div class="container">
        <h2 class="section-title">Key Features</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🔒</div>
                <h3>Secure Registration</h3>
                <p>Our system uses secure email verification to ensure that all accounts are legitimate and protected.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🏨</div>
                <h3>Room Management</h3>
                <p>Easily browse available rooms, make reservations, and manage your accommodation preferences.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📱</div>
                <h3>User-Friendly Interface</h3>
                <p>Our intuitive design makes it easy to navigate and use all features of the system efficiently.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📊</div>
                <h3>Advanced Dashboard</h3>
                <p>Get insights and manage all your hostel-related activities from a centralized dashboard.</p>
            </div>
        </div>
    </div>
</div>

<div class="about-section">
    <div class="container">
        <div class="about-content">
            <div class="about-text">
                <h2>About Our System</h2>
                <p>The Hostel Management System is designed to streamline the process of hostel accommodation management for both administrators and residents. Our platform provides a comprehensive solution for room allocation, user registration, and facility management.</p>
                <p>With our secure email verification process, we ensure that all accounts are protected and legitimate, providing peace of mind for all users.</p>
                
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p>Ready to get started? <a href="signup.php">Create an account</a> today!</p>
                <?php endif; ?>
            </div>
            
            <div class="about-image">
                <img src="https://via.placeholder.com/500x300?text=Hostel+Management" alt="Hostel Management">
            </div>
        </div>
    </div>
</div>

<?php if ($_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_NAME'] == '127.0.0.1'): ?>
<div class="dev-tools-section">
    <div class="container">
        <h2 class="section-title">Development Tools</h2>
        <p class="dev-tools-intro">Since you're running in development mode, you have access to these useful tools:</p>
        
        <div class="dev-tools-grid">
            <a href="check_mail.php" class="dev-tool-card">
                <h3>📧 Check Mail Configuration</h3>
                <p>Verify your mail settings and test email functionality</p>
            </a>
            
            <a href="email_viewer.php" class="dev-tool-card">
                <h3>📨 Email Viewer</h3>
                <p>View all emails sent by the system during development</p>
            </a>
            
            <a href="install_phpmailer.php" class="dev-tool-card">
                <h3>⚙️ Install PHPMailer</h3>
                <p>Set up PHPMailer for improved email delivery</p>
            </a>
        </div>
    </div>
</div>
<?php endif; ?>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
</footer>
</body>
</html>

<?php
// Remove footer include
?> 