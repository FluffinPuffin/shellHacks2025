
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get current session data
$current_session = null;
if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
}

// Get all sessions for this user
$all_sessions = $db->getRecentSessions(10);
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
        <h1>Budget Analysis</h1>
        
        <?php if ($current_session): ?>
            <div class="current-session">
                <h2>Current Session: <?php echo htmlspecialchars($current_session['session_id']); ?></h2>
                <?php if (isset($current_session['user_data']['household_data'])): ?>
                    <?php $data = $current_session['user_data']['household_data']; ?>
                    <div class="budget-summary">
                        <h3>Household Information</h3>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($data['name'] ?? 'N/A'); ?></p>
                        <p><strong>Location:</strong> <?php echo htmlspecialchars($data['location'] ?? 'N/A'); ?></p>
                        <p><strong>Household Size:</strong> <?php echo htmlspecialchars($data['household_size'] ?? 'N/A'); ?> people</p>
                        <p><strong>Housing:</strong> <?php echo htmlspecialchars($data['bedrooms'] ?? 'N/A'); ?> bedrooms, <?php echo htmlspecialchars($data['bathrooms'] ?? 'N/A'); ?> bathrooms</p>
                        
                        <h3>Financial Summary</h3>
                        <p><strong>Monthly Rent:</strong> $<?php echo number_format($data['rent'] ?? 0, 2); ?></p>
                        <p><strong>Utilities:</strong> $<?php echo number_format(($data['utilities']['water'] ?? 0) + ($data['utilities']['phone'] ?? 0) + ($data['utilities']['electricity'] ?? 0) + ($data['utilities']['other'] ?? 0), 2); ?></p>
                        <p><strong>Groceries:</strong> $<?php echo number_format($data['groceries'] ?? 0, 2); ?></p>
                        <p><strong>Savings Goal:</strong> $<?php echo number_format($data['savings'] ?? 0, 2); ?></p>
                        <p><strong>Debt Payment:</strong> $<?php echo number_format($data['debt']['monthly_payment'] ?? 0, 2); ?></p>
                    </div>
                    
                    <?php if ($current_session['budget_analysis']): ?>
                        <div class="analysis-results">
                            <h3>AI Budget Analysis</h3>
                            <div class="analysis-content">
                                <?php echo nl2br(htmlspecialchars($current_session['budget_analysis']['raw_analysis'] ?? 'No analysis available')); ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="no-analysis">
                            <p>No budget analysis available yet. <a href="initialQuestions.php">Complete your budget setup</a></p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="no-session">
                <h2>No Active Session</h2>
                <p>You don't have an active budget session. <a href="initialQuestions.php">Start a new budget analysis</a></p>
            </div>
        <?php endif; ?>
        
        <div class="session-history">
            <h2>All Budget Sessions</h2>
            <?php if (empty($all_sessions)): ?>
                <p>No budget sessions found.</p>
            <?php else: ?>
                <?php foreach ($all_sessions as $session): ?>
                    <div class="session-item">
                        <h3><?php echo htmlspecialchars($session['session_id']); ?></h3>
                        <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></p>
                        <?php if (isset($session['user_data']['household_data'])): ?>
                            <?php $data = $session['user_data']['household_data']; ?>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($data['name'] ?? 'N/A'); ?></p>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($data['location'] ?? 'N/A'); ?></p>
                        <?php endif; ?>
                        <p><strong>Status:</strong> 
                            <?php if ($session['budget_analysis']): ?>
                                <span style="color: green;">Analysis Complete</span>
                            <?php else: ?>
                                <span style="color: orange;">Pending Analysis</span>
                            <?php endif; ?>
                        </p>
                        <hr>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="budget-actions">
            <a href="initialQuestions.php" class="btn">Start New Analysis</a>
            <a href="home.php" class="btn">Back to Home</a>
        </div>
    </div>
</body>
</html>