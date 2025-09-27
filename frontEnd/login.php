<?php session_start(); ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> Login Page </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="login-container">
        <h1>AI Budget App Locator</h1>
        <h2>Login</h2>
        
        <form method="post" action="auth.php">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            
            <div class="form-group">
                <input type="submit" value="Login" name="login">
            </div>
        </form>
        
        <p>Don't have an account? <a href="initialQuestions.php">Get Started</a></p>
    </div>
</body>
</html>