
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Budget App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css"/>
</head>

<body>
    <div class="home-container">
        <?php include 'navigation.php'?>
        <h1>Welcome to AI Budget App Locator</h1>
        <?php if (isset($_SESSION['username'])) { ?>
        <p>Hello, <?php echo $_SESSION['username'] ?? 'User'; ?>!</p>
        
        <div class="navigation">
            <a href="initialQuestions.php" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                </svg>
                Start New Budget Analysis
            </a>
            <a href="budget.php" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3h18v18H3zM9 9h6v6H9z"></path>
                </svg>
                View Budget
            </a>
            <a href="location.php" class="btn">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                    <circle cx="12" cy="10" r="3"></circle>
                </svg>
                Location Comparison
            </a>
            <a href="logout.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16,17 21,12 16,7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </a>
        </div>

        <div class="recent-budgets">
            <h2>Recent Budgets (<?php echo $session_count; ?> total)</h2>
            <?php if (empty($recent_sessions)): ?>
                <p>No budget analyses yet. <a href="initialQuestions.php">Create your first budget analysis</a></p>
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

