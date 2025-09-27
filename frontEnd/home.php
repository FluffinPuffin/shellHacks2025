
<?php 
session_start();

// last 3 budgets
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
        <h1>Welcome to AI Budget App Locator</h1>
        <p>Hello, <?php echo $_SESSION['username'] ?? 'User'; ?>!</p>
        
        <div class="navigation">
            <a href="initialQuestions.php" class="btn">Start New Budget Analysis</a>
            <a href="budget.php" class="btn">View Budget</a>
            <a href="logout.php" class="btn">Logout</a>
        </div>
        
        <div class="recent-budgets">
            <h2>Recent Budgets</h2>
            <p>Your last 3 budget analyses will appear here.</p>
        </div>
    </div>
</body>
</html>
