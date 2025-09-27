<?php
session_start();
require_once 'config/database.php';

// Get current session data
$current_session = null;
if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
}

// Get all sessions for this user
$all_sessions = $db->getRecentSessions(10);

if (isset($_POST['Load'])) {
    // If Load button is pressed
}

if (isset($_POST['Generate'])) {
    // If Generate button is pressed
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div class="budget-container">
        <?php include 'navigation.php'?>
        <h1>Budget Analysis</h1>
        <div class="budget-actions">
            <form id="loadInformation" action="budget.php" method="post">
                <label for="Generate"> Load Budget </label>
                <input type="submit" value="Load" name="Load">
            </form>
        </div>

        <div class="budget-actions">
            <form id="generatePage" action="budget.php" method="post">
                <label for="Generate"> Generate New Budget Analysis </label>
                <input type="submit" value="Generate" name="Generate">
            </form>
        </div>
    </div>
</body>
</html>