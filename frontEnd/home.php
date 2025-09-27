
<?php
session_start();

// if user is logged in, show recent budgets
// connect to database here
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>

    <div class="home-container">
        <?php include 'navigation.php'?>
        <h1>Welcome to AI Budget App Locator</h1>
        <p>Hello, <?php echo $_SESSION['username'] ?? 'User'; ?>!</p>

        <div class="recent-budgets">
            <h2>Recent Budgets</h2>
            <p>Your last 3 budget analyses will appear here.</p>
        </div>

        <?php if (isset($_SESSION['username'])){
            // display from database
        } else {

        }?>
    </div>
</body>
</html>
