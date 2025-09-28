<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_clear'])) {
    try {
        $conn = $db->getConnection();
        
        // Clear all sessions
        $stmt = $conn->prepare("DELETE FROM sessions");
        $stmt->execute();
        $sessions_deleted = $stmt->rowCount();
        
        // Clear all user profiles
        $stmt = $conn->prepare("DELETE FROM user_profiles");
        $stmt->execute();
        $profiles_deleted = $stmt->rowCount();
        
        // Clear current session data
        unset($_SESSION['current_session_id']);
        
        $message = "Data cleared successfully! Deleted {$sessions_deleted} sessions and {$profiles_deleted} profiles.";
        
    } catch (Exception $e) {
        $error = "Error clearing data: " . $e->getMessage();
    }
}

// Get current data counts
$session_count = $db->getSessionCount();
$profile_count = 0;
try {
    $conn = $db->getConnection();
    $stmt = $conn->prepare("SELECT COUNT(*) FROM user_profiles");
    $stmt->execute();
    $profile_count = $stmt->fetchColumn();
} catch (Exception $e) {
    // Ignore error
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clear Data - Budget App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css"/>
</head>
<body>
    <div class="home-container">
        <?php include 'navigation.php'?>
        
        <h1>Clear Saved Data</h1>
        <p>This will remove all your saved budget sessions and profiles, giving you a clean start for testing.</p>
        
        <?php if ($message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="data-summary">
            <h2>Current Data</h2>
            <div class="summary-cards">
                <div class="summary-card">
                    <h3><?php echo $session_count; ?></h3>
                    <p>Budget Sessions</p>
                </div>
                <div class="summary-card">
                    <h3><?php echo $profile_count; ?></h3>
                    <p>User Profiles</p>
                </div>
            </div>
        </div>
        
        <?php if ($session_count > 0 || $profile_count > 0): ?>
            <div class="clear-warning">
                <h2>⚠️ Warning</h2>
                <p>This action will permanently delete:</p>
                <ul>
                    <li>All budget sessions and their data</li>
                    <li>All user profiles</li>
                    <li>All budget analyses and recommendations</li>
                </ul>
                <p><strong>This action cannot be undone!</strong></p>
            </div>
            
            <form method="POST" class="clear-form">
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="confirm_clear" required>
                        I understand this will permanently delete all my data
                    </label>
                </div>
                <button type="submit" class="btn btn-danger">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="3,6 5,6 21,6"></polyline>
                        <path d="M19,6v14a2,2,0,0,1-2,2H7a2,2,0,0,1-2-2V6m3,0V4a2,2,0,0,1,2-2h4a2,2,0,0,1,2,2V6"></path>
                        <line x1="10" y1="11" x2="10" y2="17"></line>
                        <line x1="14" y1="11" x2="14" y2="17"></line>
                    </svg>
                    Clear All Data
                </button>
            </form>
        <?php else: ?>
            <div class="empty-state">
                <h2>✅ No Data to Clear</h2>
                <p>Your database is already clean! You can start creating new budget sessions.</p>
                <a href="initialQuestions.php" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                    </svg>
                    Start New Budget
                </a>
            </div>
        <?php endif; ?>
        
        <div class="navigation">
            <a href="home.php" class="btn btn-secondary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                    <polyline points="9,22 9,12 15,12 15,22"></polyline>
                </svg>
                Back to Home
            </a>
        </div>
    </div>
</body>
</html>
