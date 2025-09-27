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
$budget_data = null;
$message = '';

if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
    if ($current_session && isset($current_session['user_data']['household_data'])) {
        $budget_data = $current_session['user_data']['household_data'];
    }
}

// Get all sessions for this user
$all_sessions = $db->getRecentSessions(10);

// Function to generate advanced budget analysis data
function generateAdvancedBudgetData($budget_data) {
    // Calculate current totals
    $current_rent = $budget_data['rent'] ?? 0;
    $current_utilities = ($budget_data['utilities']['water'] ?? 0) + 
                        ($budget_data['utilities']['phone'] ?? 0) + 
                        ($budget_data['utilities']['electricity'] ?? 0) + 
                        ($budget_data['utilities']['other'] ?? 0);
    $current_groceries = $budget_data['groceries'] ?? 0;
    $current_debt_payment = $budget_data['debt']['monthly_payment'] ?? 0;
    $current_savings = $budget_data['savings'] ?? 0;
    
    $total_expenses = $current_rent + $current_utilities + $current_groceries + $current_debt_payment;
    
    // Generate advanced analysis based on location and household size
    $location = $budget_data['location'] ?? 'Unknown';
    $household_size = $budget_data['household_size'] ?? 1;
    $age = $budget_data['age'] ?? 25;
    
    // Location-based cost adjustments (simplified)
    $location_multiplier = 1.0;
    if (stripos($location, 'new york') !== false || stripos($location, 'san francisco') !== false || stripos($location, 'los angeles') !== false) {
        $location_multiplier = 1.3;
    } elseif (stripos($location, 'chicago') !== false || stripos($location, 'boston') !== false || stripos($location, 'seattle') !== false) {
        $location_multiplier = 1.15;
    } elseif (stripos($location, 'texas') !== false || stripos($location, 'florida') !== false || stripos($location, 'arizona') !== false) {
        $location_multiplier = 0.9;
    }
    
    // Age-based recommendations
    $age_factor = 1.0;
    if ($age < 25) {
        $age_factor = 0.8; // Younger people typically spend less
    } elseif ($age > 50) {
        $age_factor = 1.1; // Older people may have higher healthcare costs
    }
    
    // Generate optimized recommendations
    $advanced_analysis = [
        'current_analysis' => [
            'total_monthly_expenses' => $total_expenses,
            'expense_breakdown' => [
                'housing' => $current_rent,
                'utilities' => $current_utilities,
                'groceries' => $current_groceries,
                'debt_payments' => $current_debt_payment,
                'savings' => $current_savings
            ],
            'expense_percentages' => [
                'housing_percent' => $total_expenses > 0 ? round(($current_rent / $total_expenses) * 100, 1) : 0,
                'utilities_percent' => $total_expenses > 0 ? round(($current_utilities / $total_expenses) * 100, 1) : 0,
                'groceries_percent' => $total_expenses > 0 ? round(($current_groceries / $total_expenses) * 100, 1) : 0,
                'debt_percent' => $total_expenses > 0 ? round(($current_debt_payment / $total_expenses) * 100, 1) : 0
            ]
        ],
        'recommendations' => [
            'optimized_rent' => round($current_rent * $location_multiplier * 0.9, 2), // 10% reduction target
            'optimized_utilities' => [
                'water' => round(($budget_data['utilities']['water'] ?? 0) * 0.85, 2),
                'phone' => round(($budget_data['utilities']['phone'] ?? 0) * 0.8, 2),
                'electricity' => round(($budget_data['utilities']['electricity'] ?? 0) * 0.9, 2),
                'other' => round(($budget_data['utilities']['other'] ?? 0) * 0.9, 2)
            ],
            'optimized_groceries' => round($current_groceries * 0.9, 2), // 10% reduction target
            'recommended_savings' => round($total_expenses * 0.2, 2), // 20% of expenses as savings goal
            'emergency_fund_target' => round($total_expenses * 6, 2) // 6 months of expenses
        ],
        'insights' => [
            'housing_affordability' => $current_rent > ($total_expenses * 0.3) ? 'High' : 'Good',
            'debt_to_income_ratio' => $total_expenses > 0 ? round(($current_debt_payment / $total_expenses) * 100, 1) : 0,
            'savings_rate' => $total_expenses > 0 ? round(($current_savings / $total_expenses) * 100, 1) : 0,
            'location_cost_index' => round($location_multiplier * 100, 0),
            'household_efficiency' => $household_size > 1 ? round($total_expenses / $household_size, 2) : $total_expenses
        ],
        'potential_savings' => [
            'monthly_savings_potential' => round($total_expenses * 0.15, 2), // 15% potential savings
            'annual_savings_potential' => round($total_expenses * 0.15 * 12, 2),
            'areas_for_improvement' => [
                'utilities_optimization' => round($current_utilities * 0.1, 2),
                'groceries_optimization' => round($current_groceries * 0.1, 2),
                'debt_consolidation' => $current_debt_payment > 0 ? round($current_debt_payment * 0.05, 2) : 0
            ]
        ],
        'generated_at' => date('Y-m-d H:i:s'),
        'analysis_version' => '1.0'
    ];
    
    return $advanced_analysis;
}

// Handle form submissions
if (isset($_POST['Load'])) {
    // Load the most recent session data
    if (!empty($all_sessions)) {
        $latest_session = $all_sessions[0];
        if (isset($latest_session['user_data']['household_data'])) {
            $budget_data = $latest_session['user_data']['household_data'];
            $message = "Latest budget data loaded!";
        }
    } else {
        $message = "No budget data found to load.";
    }
}

if (isset($_POST['Generate'])) {
    // Generate new budget analysis with advanced data
    if ($current_session && $budget_data) {
        // Generate advanced budget analysis data
        $advanced_data = generateAdvancedBudgetData($budget_data);
        
        // Update the session with advanced analysis
        $update_data = [
            'user_data' => [
                'household_data' => $budget_data,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
                'advanced_analysis' => $advanced_data
            ]
        ];
        
        if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
            $message = "Advanced budget analysis generated successfully!";
        } else {
            $message = "Failed to save advanced analysis.";
        }
    } else {
        $message = "Please create a budget session and load data first.";
    }
}

if (isset($_POST['Update'])) {
    // Update budget data
    if ($current_session) {
        $updated_data = [
            'name' => $_POST['name'] ?? $budget_data['name'] ?? '',
            'age' => (int)($_POST['age'] ?? $budget_data['age'] ?? 0),
            'location' => $_POST['location'] ?? $budget_data['location'] ?? '',
            'household_size' => (int)($_POST['household_size'] ?? $budget_data['household_size'] ?? 0),
            'bedrooms' => (int)($_POST['bedrooms'] ?? $budget_data['bedrooms'] ?? 0),
            'bathrooms' => (float)($_POST['bathrooms'] ?? $budget_data['bathrooms'] ?? 0),
            'rent' => (float)($_POST['rent'] ?? $budget_data['rent'] ?? 0),
            'utilities' => [
                'water' => (float)($_POST['water'] ?? $budget_data['utilities']['water'] ?? 0),
                'phone' => (float)($_POST['phone'] ?? $budget_data['utilities']['phone'] ?? 0),
                'electricity' => (float)($_POST['electricity'] ?? $budget_data['utilities']['electricity'] ?? 0),
                'other' => (float)($_POST['other_utilities'] ?? $budget_data['utilities']['other'] ?? 0)
            ],
            'groceries' => (float)($_POST['groceries'] ?? $budget_data['groceries'] ?? 0),
            'savings' => (float)($_POST['savings'] ?? $budget_data['savings'] ?? 0),
            'debt' => [
                'total_debt' => (float)($_POST['total_debt'] ?? $budget_data['debt']['total_debt'] ?? 0),
                'monthly_payment' => (float)($_POST['monthly_debt'] ?? $budget_data['debt']['monthly_payment'] ?? 0),
                'debt_type' => $_POST['debt_type'] ?? $budget_data['debt']['debt_type'] ?? '',
                'interest_rate' => (float)($_POST['interest_rate'] ?? $budget_data['debt']['interest_rate'] ?? 0)
            ],
            'monthly_payments' => $budget_data['monthly_payments'] ?? []
        ];
        
        $update_data = [
            'user_data' => [
                'household_data' => $updated_data,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null
            ]
        ];
        
        if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
            $budget_data = $updated_data;
            $message = "Budget data updated successfully!";
        } else {
            $message = "Failed to update budget data.";
        }
    }
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
        
        <?php if ($message): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        
        <div class="budget-actions">
            <form id="loadInformation" action="budget.php" method="post">
                <input type="submit" value="Load Latest Budget" name="Load">
            </form>
            
            <form id="generatePage" action="budget.php" method="post">
                <input type="submit" value="Generate Analysis" name="Generate">
            </form>
        </div>

        <table border="1">
            <tr>
                <section class="compare-section">
                    <div class="reports-container">
                        <th>
                            <form name="budgetForm" id="budgetForm" method="POST" action="">
                                <div class="location-report">
                                    <h3>Personal Information</h3>
                                    <div class="report-item">
                                        <strong>Name:</strong>
                                        <input type="text" name="name" value="<?php echo htmlspecialchars($budget_data['name'] ?? ''); ?>">
                                    </div>
                                    <div class="report-item">
                                        <strong>Age:</strong>
                                        <input type="number" name="age" value="<?php echo htmlspecialchars($budget_data['age'] ?? ''); ?>" min="18">
                                    </div>
                                    <div class="report-item">
                                        <strong>Location:</strong>
                                        <input type="text" name="location" value="<?php echo htmlspecialchars($budget_data['location'] ?? ''); ?>">
                                    </div>
                                    <div class="report-item">
                                        <strong>Household Size:</strong>
                                        <input type="number" name="household_size" value="<?php echo htmlspecialchars($budget_data['household_size'] ?? ''); ?>" min="1">
                                    </div>
                                    <div class="report-item">
                                        <strong>Bedrooms:</strong>
                                        <input type="number" name="bedrooms" value="<?php echo htmlspecialchars($budget_data['bedrooms'] ?? ''); ?>" min="0">
                                    </div>
                                    <div class="report-item">
                                        <strong>Bathrooms:</strong>
                                        <input type="number" name="bathrooms" value="<?php echo htmlspecialchars($budget_data['bathrooms'] ?? ''); ?>" min="0" step="0.5">
                                    </div>
                                </div>
                            </form>
                        </th>
                        
                        <th>
                            <div class="location-report">
                                <h3>Financial Information</h3>
                                <div class="cost-section">
                                    <div class="report-item">
                                        <strong>Monthly Rent:</strong>
                                        <input type="number" name="rent" value="<?php echo htmlspecialchars($budget_data['rent'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Water Bill:</strong>
                                        <input type="number" name="water" value="<?php echo htmlspecialchars($budget_data['utilities']['water'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Phone Bill:</strong>
                                        <input type="number" name="phone" value="<?php echo htmlspecialchars($budget_data['utilities']['phone'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Electricity:</strong>
                                        <input type="number" name="electricity" value="<?php echo htmlspecialchars($budget_data['utilities']['electricity'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Other Utilities:</strong>
                                        <input type="number" name="other_utilities" value="<?php echo htmlspecialchars($budget_data['utilities']['other'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Groceries:</strong>
                                        <input type="number" name="groceries" value="<?php echo htmlspecialchars($budget_data['groceries'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Savings Goal:</strong>
                                        <input type="number" name="savings" value="<?php echo htmlspecialchars($budget_data['savings'] ?? ''); ?>" step="0.01">
                                    </div>
                                </div>
                            </div>
                        </th>
                        
                        <th>
                            <div class="location-report">
                                <h3>Debt Information</h3>
                                <div class="cost-section">
                                    <div class="report-item">
                                        <strong>Total Debt:</strong>
                                        <input type="number" name="total_debt" value="<?php echo htmlspecialchars($budget_data['debt']['total_debt'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Monthly Debt Payment:</strong>
                                        <input type="number" name="monthly_debt" value="<?php echo htmlspecialchars($budget_data['debt']['monthly_payment'] ?? ''); ?>" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Debt Type:</strong>
                                        <input type="text" name="debt_type" value="<?php echo htmlspecialchars($budget_data['debt']['debt_type'] ?? ''); ?>">
                                    </div>
                                    <div class="report-item">
                                        <strong>Interest Rate (%):</strong>
                                        <input type="number" name="interest_rate" value="<?php echo htmlspecialchars($budget_data['debt']['interest_rate'] ?? ''); ?>" step="0.01">
                                    </div>
                                </div>
                                
                                <div class="report-item total">
                                    <strong>Total Monthly Expenses:</strong>
                                    <span><strong>$<?php 
                                        if ($budget_data) {
                                            $total = ($budget_data['rent'] ?? 0) + 
                                                    (($budget_data['utilities']['water'] ?? 0) + 
                                                     ($budget_data['utilities']['phone'] ?? 0) + 
                                                     ($budget_data['utilities']['electricity'] ?? 0) + 
                                                     ($budget_data['utilities']['other'] ?? 0)) + 
                                                    ($budget_data['groceries'] ?? 0) + 
                                                    ($budget_data['debt']['monthly_payment'] ?? 0);
                                            echo number_format($total, 2);
                                        } else {
                                            echo "0.00";
                                        }
                                    ?></strong></span>
                                </div>
                                
                                <input type="submit" name="Update" value="Update Budget">
                            </div>
                        </th>
                    </div>
                </section>
            </tr>
        </table>
        
        <!-- Advanced Analysis Section -->
        <?php if (isset($current_session['user_data']['advanced_analysis'])): ?>
            <?php $analysis = $current_session['user_data']['advanced_analysis']; ?>
            <div class="advanced-analysis">
                <h2>Advanced Budget Analysis</h2>
                <div class="analysis-grid">
                    <div class="analysis-card">
                        <h3>Current Analysis</h3>
                        <div class="analysis-item">
                            <strong>Total Monthly Expenses:</strong> $<?php echo number_format($analysis['current_analysis']['total_monthly_expenses'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Housing Percentage:</strong> <?php echo $analysis['current_analysis']['expense_percentages']['housing_percent']; ?>%
                        </div>
                        <div class="analysis-item">
                            <strong>Utilities Percentage:</strong> <?php echo $analysis['current_analysis']['expense_percentages']['utilities_percent']; ?>%
                        </div>
                        <div class="analysis-item">
                            <strong>Groceries Percentage:</strong> <?php echo $analysis['current_analysis']['expense_percentages']['groceries_percent']; ?>%
                        </div>
                        <div class="analysis-item">
                            <strong>Debt Percentage:</strong> <?php echo $analysis['current_analysis']['expense_percentages']['debt_percent']; ?>%
                        </div>
                    </div>
                    
                    <div class="analysis-card">
                        <h3>Optimized Recommendations</h3>
                        <div class="analysis-item">
                            <strong>Optimized Rent:</strong> $<?php echo number_format($analysis['recommendations']['optimized_rent'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Optimized Water:</strong> $<?php echo number_format($analysis['recommendations']['optimized_utilities']['water'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Optimized Phone:</strong> $<?php echo number_format($analysis['recommendations']['optimized_utilities']['phone'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Optimized Electricity:</strong> $<?php echo number_format($analysis['recommendations']['optimized_utilities']['electricity'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Optimized Groceries:</strong> $<?php echo number_format($analysis['recommendations']['optimized_groceries'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Recommended Savings:</strong> $<?php echo number_format($analysis['recommendations']['recommended_savings'], 2); ?>
                        </div>
                    </div>
                    
                    <div class="analysis-card">
                        <h3>Financial Insights</h3>
                        <div class="analysis-item">
                            <strong>Housing Affordability:</strong> <?php echo $analysis['insights']['housing_affordability']; ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Debt-to-Expense Ratio:</strong> <?php echo $analysis['insights']['debt_to_income_ratio']; ?>%
                        </div>
                        <div class="analysis-item">
                            <strong>Current Savings Rate:</strong> <?php echo $analysis['insights']['savings_rate']; ?>%
                        </div>
                        <div class="analysis-item">
                            <strong>Location Cost Index:</strong> <?php echo $analysis['insights']['location_cost_index']; ?>%
                        </div>
                        <div class="analysis-item">
                            <strong>Cost per Person:</strong> $<?php echo number_format($analysis['insights']['household_efficiency'], 2); ?>
                        </div>
                    </div>
                    
                    <div class="analysis-card">
                        <h3>Savings Potential</h3>
                        <div class="analysis-item">
                            <strong>Monthly Savings Potential:</strong> $<?php echo number_format($analysis['potential_savings']['monthly_savings_potential'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Annual Savings Potential:</strong> $<?php echo number_format($analysis['potential_savings']['annual_savings_potential'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Utilities Optimization:</strong> $<?php echo number_format($analysis['potential_savings']['areas_for_improvement']['utilities_optimization'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Groceries Optimization:</strong> $<?php echo number_format($analysis['potential_savings']['areas_for_improvement']['groceries_optimization'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Emergency Fund Target:</strong> $<?php echo number_format($analysis['recommendations']['emergency_fund_target'], 2); ?>
                        </div>
                    </div>
                </div>
                <div class="analysis-footer">
                    <p><em>Analysis generated on: <?php echo $analysis['generated_at']; ?></em></p>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Recent Sessions -->
        <?php if (!empty($all_sessions)): ?>
        <div class="recent-sessions">
            <h2>Recent Budget Sessions</h2>
            <div class="sessions-grid">
                <?php foreach ($all_sessions as $session): ?>
                    <?php if (isset($session['user_data']['household_data'])): ?>
                        <?php $data = $session['user_data']['household_data']; ?>
                        <div class="session-card">
                            <h3><?php echo htmlspecialchars($data['name'] ?? 'Unknown User'); ?></h3>
                            <p><strong>Location:</strong> <?php echo htmlspecialchars($data['location'] ?? 'N/A'); ?></p>
                            <p><strong>Created:</strong> <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?></p>
                            <p><strong>Total Monthly:</strong> $<?php 
                                $total = ($data['rent'] ?? 0) + 
                                        (($data['utilities']['water'] ?? 0) + 
                                         ($data['utilities']['phone'] ?? 0) + 
                                         ($data['utilities']['electricity'] ?? 0) + 
                                         ($data['utilities']['other'] ?? 0)) + 
                                        ($data['groceries'] ?? 0) + 
                                        ($data['debt']['monthly_payment'] ?? 0);
                                echo number_format($total, 2);
                            ?></p>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>