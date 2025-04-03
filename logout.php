<?php
session_start();

// Unset all session variables
$_SESSION = array();

// If a session cookie is used, destroy that too
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Set a message for the user
$_SESSION['message'] = "You have been successfully logged out.";

// Redirect to login page
header("Location: login.php");
exit();
?> 