<?php
session_start();

if (isset($_SESSION['username'])) {
    require_once 'config/database.php';

    // Get current session data
    $current_session = null;
    if (isset($_SESSION['current_session_id'])) {
        $current_session = $db->getSession($_SESSION['current_session_id']);
    }

    // Get all sessions for this user
    $all_sessions = $db->getRecentSessions(10);
    // Connect to database
    $pdo = new PDO('sqlite:budget_app.db');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch all sessions
    $stmt = $pdo->query("SELECT * FROM sessions ORDER BY created_at DESC");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch specific session
    $stmt = $pdo->prepare("SELECT * FROM sessions WHERE session_id = ?");
    $stmt->execute(["session_abc123"]);
    $session = $stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch user by username
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(["demo"]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // All sessions
    echo "<pre>";
    print_r($sessions);  // $sessions is from fetchAll(PDO::FETCH_ASSOC)
    echo "</pre>";

    // Specific session
    echo "<pre>";
    print_r($session);   // $session is from fetch(PDO::FETCH_ASSOC)
    echo "</pre>";

    // User info
    echo "<pre>";
    print_r($user);      // $user is from fetch(PDO::FETCH_ASSOC)
    echo "</pre>";

} else {

}

if (isset($_POST['Load'])) {
    // If Load button is pressed
    // values from the database should populate the fields
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

            <form>
                <label for="Savings"> Savings: </label>
                <input type="text" id="Savings" name="Savings" value=<?php // IF ELSE here ?>>

                <label for="Debt"> Debt: </label>
                <input type="text" id="Debt" name="Debt">

                <label for="Monthly"> Monthly Payments: </label>
                <input type="text" id="Monthly" name="Monthly">

                <label for="Rent"> Rent: </label>
                <input type="text" id="Rent" name="Rent">

                <label for="Utilities"> Utilities: </label>
                <input type="text" id="Utilities" name="Utilities">

                <label for="Other"> Other: </label>
                <input type="text" id="Other" name="Other">
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