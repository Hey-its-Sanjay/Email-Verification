<?php
session_start();
include 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to access the dashboard.";
    header("Location: login.php");
    exit();
}

// Set page title for header
$pageTitle = "Dashboard - Hostel Management System";

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

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
        
        /* Dashboard Styles */
        .dashboard-container {
            margin-bottom: 50px;
        }
        
        .dashboard-header {
            background-color: #4CAF50;
            color: white;
            padding: 30px 0;
            margin-bottom: 30px;
        }
        
        .dashboard-header h1 {
            margin: 0 0 10px 0;
            font-size: 2rem;
        }
        
        .dashboard-header p {
            margin: 0;
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .dashboard-content {
            display: flex;
            gap: 30px;
        }
        
        /* Sidebar Styles */
        .dashboard-sidebar {
            width: 280px;
            flex-shrink: 0;
        }
        
        .user-profile {
            background-color: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            background-color: #4CAF50;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            font-weight: bold;
            margin-right: 15px;
        }
        
        .user-details h3 {
            margin: 0 0 5px 0;
            font-size: 1.2rem;
            color: #333;
        }
        
        .user-details p {
            margin: 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .dashboard-nav {
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .dashboard-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .dashboard-nav li {
            border-bottom: 1px solid #f0f0f0;
        }
        
        .dashboard-nav li:last-child {
            border-bottom: none;
        }
        
        .dashboard-nav a {
            display: block;
            padding: 15px 20px;
            color: #333;
            text-decoration: none;
            transition: all 0.3s;
        }
        
        .dashboard-nav li.active a {
            background-color: #4CAF50;
            color: white;
            font-weight: bold;
        }
        
        .dashboard-nav a:hover {
            background-color: #f9f9f9;
        }
        
        .dashboard-nav li.active a:hover {
            background-color: #4CAF50;
        }
        
        /* Main Content Styles */
        .dashboard-main {
            flex: 1;
        }
        
        .dashboard-section {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            padding: 25px;
        }
        
        .section-header {
            margin-bottom: 25px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 15px;
        }
        
        .section-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }
        
        /* Dashboard Cards */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .dashboard-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            display: flex;
            align-items: flex-start;
        }
        
        .card-icon {
            font-size: 2rem;
            margin-right: 15px;
        }
        
        .card-content h3 {
            margin: 0 0 10px 0;
            font-size: 1.1rem;
            color: #333;
        }
        
        .card-content p {
            margin: 0 0 15px 0;
            color: #666;
            font-size: 0.9rem;
        }
        
        .card-link {
            color: #4CAF50;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: bold;
        }
        
        .card-link:hover {
            text-decoration: underline;
        }
        
        /* Quick Actions */
        .quick-actions {
            margin-top: 30px;
        }
        
        .quick-actions h3 {
            margin: 0 0 15px 0;
            color: #333;
            font-size: 1.2rem;
        }
        
        .action-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .action-button {
            background-color: #f5f5f5;
            color: #333;
            text-decoration: none;
            padding: 10px 15px;
            border-radius: 5px;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .action-button:hover {
            background-color: #e0e0e0;
        }
        
        /* Responsive Design */
        @media (max-width: 900px) {
            .dashboard-content {
                flex-direction: column;
            }
            
            .dashboard-sidebar {
                width: 100%;
                margin-bottom: 20px;
            }
            
            .user-profile {
                margin-bottom: 15px;
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
        <a href="dashboard.php">Dashboard</a>
        <a href="logout.php">Logout</a>
    </nav>

<div class="dashboard-container">
    <div class="dashboard-header">
        <div class="container">
            <h1>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h1>
            <p>Manage your hostel account and settings from this dashboard.</p>
        </div>
    </div>
    
    <div class="container">
        <div class="dashboard-content">
            <div class="dashboard-sidebar">
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <div class="user-details">
                        <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                        <p><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>
                
                <nav class="dashboard-nav">
                    <ul>
                        <li class="active"><a href="#overview">Dashboard Overview</a></li>
                        <li><a href="#profile">My Profile</a></li>
                        <li><a href="#bookings">My Bookings</a></li>
                        <li><a href="#messages">Messages</a></li>
                        <li><a href="#settings">Account Settings</a></li>
                    </ul>
                </nav>
            </div>
            
            <div class="dashboard-main">
                <div class="dashboard-section" id="overview">
                    <div class="section-header">
                        <h2>Dashboard Overview</h2>
                    </div>
                    
                    <div class="dashboard-cards">
                        <div class="dashboard-card">
                            <div class="card-icon">🏠</div>
                            <div class="card-content">
                                <h3>Current Room</h3>
                                <p>You have no active room booking</p>
                                <a href="#" class="card-link">Book a Room</a>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="card-icon">📅</div>
                            <div class="card-content">
                                <h3>Upcoming Events</h3>
                                <p>No upcoming events</p>
                                <a href="#" class="card-link">View Calendar</a>
                            </div>
                        </div>
                        
                        <div class="dashboard-card">
                            <div class="card-icon">📋</div>
                            <div class="card-content">
                                <h3>Recent Activity</h3>
                                <p>Your account was created on <?php echo date('M j, Y', strtotime($user['created_at'])); ?></p>
                                <a href="#" class="card-link">View All Activity</a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="quick-actions">
                        <h3>Quick Actions</h3>
                        <div class="action-buttons">
                            <a href="#" class="action-button">Book a Room</a>
                            <a href="#" class="action-button">Submit a Request</a>
                            <a href="#" class="action-button">View Facilities</a>
                            <a href="#" class="action-button">Contact Support</a>
                        </div>
                    </div>
                </div>
                
                <!-- Placeholder sections -->
                <div class="dashboard-section" id="profile" style="display: none;">
                    <div class="section-header">
                        <h2>My Profile</h2>
                    </div>
                    <p>This section is under development.</p>
                </div>
                
                <div class="dashboard-section" id="bookings" style="display: none;">
                    <div class="section-header">
                        <h2>My Bookings</h2>
                    </div>
                    <p>This section is under development.</p>
                </div>
                
                <div class="dashboard-section" id="messages" style="display: none;">
                    <div class="section-header">
                        <h2>Messages</h2>
                    </div>
                    <p>This section is under development.</p>
                </div>
                
                <div class="dashboard-section" id="settings" style="display: none;">
                    <div class="section-header">
                        <h2>Account Settings</h2>
                    </div>
                    <p>This section is under development.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Dashboard JavaScript -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const navLinks = document.querySelectorAll('.dashboard-nav a');
        const sections = document.querySelectorAll('.dashboard-section');
        
        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all nav items
                navLinks.forEach(item => {
                    item.parentElement.classList.remove('active');
                });
                
                // Add active class to clicked nav item
                this.parentElement.classList.add('active');
                
                // Get the target section id
                const targetId = this.getAttribute('href').substring(1);
                
                // Hide all sections
                sections.forEach(section => {
                    section.style.display = 'none';
                });
                
                // Show the target section
                document.getElementById(targetId).style.display = 'block';
            });
        });
    });
</script>

<footer>
    <p>&copy; <?php echo date('Y'); ?> Hostel Management System. All rights reserved.</p>
</footer>
</body>
</html> 