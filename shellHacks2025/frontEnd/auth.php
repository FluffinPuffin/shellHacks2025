<?php 
session_start();
require_once 'config/database.php';

// Handle login form submission
if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        $user = $db->authenticateUser($username, $password);
        
        if ($user) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Please enter both username and password";
    }
}

// If not logged in, redirect to login
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title>Authentication</title>
    <link rel="stylesheet" href="./css/style.css">
</head>
<body>

    <div class="auth-container">
        <h1>Authentication</h1>
        <p>Processing login...</p>
        <a href="login.php">Back to Login</a>
    </div>

</body>
</html>