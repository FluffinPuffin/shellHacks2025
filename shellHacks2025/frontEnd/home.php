
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get recent sessions from database
$recent_sessions = $db->getRecentSessions(3);
$session_count = $db->getSessionCount();

if (isset($_POST['new'])) {
    header("Location: initialQuestions.php");
    exit();
} elseif (isset($_POST['budget'])) {
    header("Location: budget.php");
    exit();
} elseif (isset($_POST['location'])) {
    header("Location: location.php");
    exit();
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
    <div class="home-container">
        <?php include 'navigation.php'?>
        <h1>Welcome to AI Budget App Locator</h1>
        <?php if (isset($_SESSION['username'])) { ?>
        <p>Hello, <?php echo $_SESSION['username'] ?? 'User'; ?>!</p>

        <div class="navigation">
            <form id="newBudget" action="home.php" method="post">
                <input type="submit" id="new" name="new" value="Start New Budget Analysis">
            </form>

            <form id="view" action="home.php" method="post">
                <input type="submit" id="budget" name="budget" value ="View Budget">
            </form>

            <form id="locations" action="home.php" method="post">
                <input type="submit" id="location" name="location" value="Location Comparison">
            </form>
        </div>

        <div class="recent-budgets">
            <h2>Recent Budgets (<?php echo $session_count; ?> total)</h2>
            <?php if (empty($recent_sessions)): ?>
                <p>No budget analyses yet. </p>
                <form id="newBudget" action="home.php" method="post">
                    <input type="submit" id="new" name="new" value="Create your first budget analysis">
                </form>
            <?php else: ?>
                <?php foreach ($recent_sessions as $session): ?>
                    <div class="budget-session">
                        <h3>Session: <?php echo htmlspecialchars($session['session_id']); ?></h3>
                        <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></p>
                        <?php if (isset($session['user_data']['household_data'])): ?>
                            <?php $data = $session['user_data']['household_data']; ?>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($data['name'] ?? 'N/A'); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($data['location'] ?? 'N/A'); ?></p>
                            <p><strong>Household Size:</strong> <?php echo htmlspecialchars($data['household_size'] ?? 'N/A'); ?> people</p>
                            <p><strong>Monthly Rent:</strong> $<?php echo number_format($data['rent'] ?? 0, 2); ?></p>
                        <?php endif; ?>
                        <?php if ($session['budget_analysis']): ?>
                            <p><strong>Status:</strong> <span style="color: green;">Analysis Complete</span></p>
                        <?php else: ?>
                            <p><strong>Status:</strong> <span style="color: orange;">Pending Analysis</span></p>
                        <?php endif; ?>
                        <hr>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <?php } else {

        }?>
    </div>
</body>
</html>
