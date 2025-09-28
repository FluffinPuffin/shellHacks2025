
<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Get recent sessions from database
$recent_sessions = $db->getRecentSessions(5);
$session_count = $db->getSessionCount();

// Get current session if available
$current_session = null;
if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
}
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

        <!-- Current Session Status -->
        <?php if ($current_session): ?>
        <div class="current-session-status">
            <h2>Current Session</h2>
            <div class="session-card">
                <?php if (isset($current_session['user_data']['household_data'])): ?>
                    <?php 
                    $data = $current_session['user_data']['household_data'];
                    $current_location = $data['location'] ?? 'N/A';
                    $destination_location = $current_session['user_data']['destination_data']['location'] ?? null;
                    ?>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($data['name'] ?? 'N/A'); ?></p>
                    <p><strong>Location:</strong> 
                        <?php 
                        if ($destination_location && $destination_location !== $current_location) {
                            echo htmlspecialchars($current_location) . ' → ' . htmlspecialchars($destination_location);
                        } else {
                            echo htmlspecialchars($current_location);
                        }
                        ?>
                    </p>
                    <p><strong>Household Size:</strong> <?php echo htmlspecialchars($data['household_size'] ?? 'N/A'); ?> people</p>
                    <p><strong>Last Updated:</strong> <?php echo date('M j, Y g:i A', strtotime($current_session['updated_at'])); ?></p>
                    <div class="session-actions">
                        <a href="budget.php" class="btn btn-primary">Continue Budget Analysis</a>
                        <?php if (isset($current_session['user_data']['destination_data'])): ?>
                            <a href="location.php" class="btn btn-secondary">View Location Comparison</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="recent-sessions">
            <div class="sessions-header">
                <h2>Recent Budget Sessions</h2>
                <div class="session-count-badge"><?php echo $session_count; ?> total</div>
            </div>
            
            <?php if (empty($recent_sessions)): ?>
                <div class="empty-sessions">
                    <div class="empty-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 11H5a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7a2 2 0 0 0-2-2h-4"></path>
                            <rect x="9" y="11" width="6" height="11"></rect>
                            <path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path>
                        </svg>
                    </div>
                    <h3>No budget sessions yet</h3>
                    <p>Start your first budget analysis to see your sessions here</p>
                    <a href="initialQuestions.php" class="btn btn-primary">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                        </svg>
                        Create First Budget
                    </a>
                </div>
            <?php else: ?>
                <div class="sessions-grid">
                    <?php foreach ($recent_sessions as $index => $session): ?>
                        <?php 
                        $data = $session['user_data']['household_data'] ?? null;
                        $hasAnalysis = !empty($session['budget_analysis']);
                        $hasDestination = !empty($session['user_data']['destination_data']);
                        $isCurrent = isset($_SESSION['current_session_id']) && $_SESSION['current_session_id'] === $session['session_id'];
                        ?>
                        <div class="session-card <?php echo $isCurrent ? 'current-session' : ''; ?>" data-session-id="<?php echo htmlspecialchars($session['session_id']); ?>">
                            <div class="session-header">
                                <div class="session-title">
                                    <h3><?php echo htmlspecialchars($data['name'] ?? 'Budget Session'); ?></h3>
                                    <div class="session-id">#<?php echo substr($session['session_id'], 0, 8); ?></div>
                                </div>
                                <div class="session-status">
                                    <?php if ($isCurrent): ?>
                                        <span class="status-badge current">Current</span>
                                    <?php elseif ($hasAnalysis): ?>
                                        <span class="status-badge complete">Complete</span>
                                    <?php else: ?>
                                        <span class="status-badge pending">Pending</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="session-content">
                                <?php if ($data): ?>
                                    <div class="session-details">
                                        <div class="detail-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                                <circle cx="12" cy="10" r="3"></circle>
                                            </svg>
                                            <span>
                                                <?php 
                                                $current_location = $data['location'] ?? 'N/A';
                                                $destination_location = $session['user_data']['destination_data']['location'] ?? null;
                                                
                                                if ($destination_location && $destination_location !== $current_location) {
                                                    echo htmlspecialchars($current_location) . ' → ' . htmlspecialchars($destination_location);
                                                } else {
                                                    echo htmlspecialchars($current_location);
                                                }
                                                ?>
                                            </span>
                                        </div>
                                        <div class="detail-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                                <circle cx="9" cy="7" r="4"></circle>
                                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                                            </svg>
                                            <span><?php echo htmlspecialchars($data['household_size'] ?? 'N/A'); ?> people</span>
                                        </div>
                                        <div class="detail-item">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                                            </svg>
                                            <span>
                                                <?php 
                                                $current_rent = $data['rent'] ?? 0;
                                                $destination_rent = $session['user_data']['destination_data']['rent'] ?? null;
                                                
                                                if ($destination_rent && $destination_rent != $current_rent) {
                                                    echo '$' . number_format($current_rent, 0) . ' → $' . number_format($destination_rent, 0) . '/month';
                                                } else {
                                                    echo '$' . number_format($current_rent, 0) . '/month';
                                                }
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="session-progress">
                                    <div class="progress-bar">
                                        <div class="progress-fill" style="width: <?php echo $hasAnalysis ? '100' : ($data ? '50' : '25'); ?>%"></div>
                                    </div>
                                    <div class="progress-text">
                                        <?php if ($hasAnalysis): ?>
                                            Analysis Complete
                                        <?php elseif ($data): ?>
                                            Data Collected
                                        <?php else: ?>
                                            In Progress
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="session-meta">
                                    <div class="created-date">
                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                            <line x1="16" y1="2" x2="16" y2="6"></line>
                                            <line x1="8" y1="2" x2="8" y2="6"></line>
                                            <line x1="3" y1="10" x2="21" y2="10"></line>
                                        </svg>
                                        <?php echo date('M j, Y', strtotime($session['created_at'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="session-actions">
                                <?php if (!$isCurrent): ?>
                                    <button class="btn btn-outline load-session" data-session-id="<?php echo htmlspecialchars($session['session_id']); ?>">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 15v4a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-4"></path>
                                            <polyline points="10,17 15,12 10,7"></polyline>
                                            <line x1="15" y1="12" x2="3" y2="12"></line>
                                        </svg>
                                        Load Session
                                    </button>
                                <?php endif; ?>
                                
                                <?php if ($hasAnalysis): ?>
                                    <a href="budget.php" class="btn btn-primary">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M3 3h18v18H3zM9 9h6v6H9z"></path>
                                        </svg>
                                        View Budget
                                    </a>
                                <?php elseif ($data): ?>
                                    <a href="budget.php" class="btn btn-primary">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                            <polyline points="14,2 14,8 20,8"></polyline>
                                            <line x1="16" y1="13" x2="8" y2="13"></line>
                                            <line x1="16" y1="17" x2="8" y2="17"></line>
                                            <polyline points="10,9 9,9 8,9"></polyline>
                                        </svg>
                                        Continue
                                    </a>
                                <?php endif; ?>
                                
                                <?php if ($hasDestination): ?>
                                    <a href="location.php" class="btn btn-secondary">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                        Compare
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <?php } else {

        }?>
    </div>

    <script>
        // Session loading functionality
        document.addEventListener('DOMContentLoaded', function() {
            const loadButtons = document.querySelectorAll('.load-session');
            
            loadButtons.forEach(button => {
                button.addEventListener('click', function() {
                    const sessionId = this.getAttribute('data-session-id');
                    loadSession(sessionId);
                });
            });
        });
        
        function loadSession(sessionId) {
            // Show loading state
            const button = document.querySelector(`[data-session-id="${sessionId}"]`);
            const originalText = button.innerHTML;
            button.innerHTML = '<span class="spinner"></span>Loading...';
            button.disabled = true;
            
            // Create form and submit
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'load_session.php';
            
            const sessionInput = document.createElement('input');
            sessionInput.type = 'hidden';
            sessionInput.name = 'session_id';
            sessionInput.value = sessionId;
            
            form.appendChild(sessionInput);
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html>

