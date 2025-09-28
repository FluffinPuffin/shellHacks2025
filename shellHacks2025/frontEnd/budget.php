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
$destination_data = null;
$message = '';

if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
    if ($current_session && isset($current_session['user_data']['household_data'])) {
        $budget_data = $current_session['user_data']['household_data'];
    }
    if ($current_session && isset($current_session['user_data']['destination_data'])) {
        $destination_data = $current_session['user_data']['destination_data'];
    }
}

// Get all sessions for this user
$all_sessions = $db->getRecentSessions(10);

// Function to generate cost breakdown using the specified prompt
function generateCostBreakdown($location, $household_size) {
    // Create the prompt as specified
    $prompt = "Give me a cost break down average cost of the monthly payments for {$household_size} people paying for the average phone bill (no MVNO), cost of owning a car, health insurance in {$location} based on recent sources only a single number per category in the format of the text below with no other text or notes \n 'Phone: number,Car: number,Health Insurance: number'";
    
    // For now, we'll use realistic estimates based on location and household size
    // In a real implementation, this would call an AI service with the prompt
    
    // Location-based multipliers
    $location_multiplier = 1.0;
    if (stripos($location, 'new york') !== false || stripos($location, 'san francisco') !== false || stripos($location, 'los angeles') !== false) {
        $location_multiplier = 1.4;
    } elseif (stripos($location, 'chicago') !== false || stripos($location, 'boston') !== false || stripos($location, 'seattle') !== false) {
        $location_multiplier = 1.2;
    } elseif (stripos($location, 'texas') !== false || stripos($location, 'florida') !== false || stripos($location, 'arizona') !== false) {
        $location_multiplier = 0.8;
    }
    
    // Generate realistic estimates
    $phone_cost = round(80 * $household_size * $location_multiplier, 2); // Average phone bill per person
    $car_cost = round(600 * $location_multiplier, 2); // Monthly car ownership cost
    $health_insurance_cost = round(400 * $household_size * $location_multiplier, 2); // Health insurance per person
    
    return [
        'phone' => $phone_cost,
        'car' => $car_cost,
        'health_insurance' => $health_insurance_cost
    ];
}

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
    
    // Generate cost breakdown using the specified prompt format
    $cost_breakdown = generateCostBreakdown($location, $household_size);
    
    // Parse the cost breakdown response
    $phone_cost = $cost_breakdown['phone'] ?? 0;
    $car_cost = $cost_breakdown['car'] ?? 0;
    $health_insurance_cost = $cost_breakdown['health_insurance'] ?? 0;
    
    // Location-based cost adjustments for other expenses
    $location_multiplier = 1.0;
    if (stripos($location, 'new york') !== false || stripos($location, 'san francisco') !== false || stripos($location, 'los angeles') !== false) {
        $location_multiplier = 1.3;
    } elseif (stripos($location, 'chicago') !== false || stripos($location, 'boston') !== false || stripos($location, 'seattle') !== false) {
        $location_multiplier = 1.15;
    } elseif (stripos($location, 'texas') !== false || stripos($location, 'florida') !== false || stripos($location, 'arizona') !== false) {
        $location_multiplier = 0.9;
    }
    
    // Generate realistic baseline recommendations if current values are zero
    $baseline_rent = $current_rent > 0 ? $current_rent : (800 * $location_multiplier * $household_size);
    $baseline_utilities = $current_utilities > 0 ? $current_utilities : (200 * $household_size);
    $baseline_groceries = $current_groceries > 0 ? $current_groceries : (400 * $household_size);
    $baseline_savings = $current_savings > 0 ? $current_savings : (300 * $household_size);
    
    // Calculate realistic optimized recommendations using generated costs
    $optimized_rent = $current_rent > 0 ? round($current_rent * 0.9, 2) : round($baseline_rent * 0.9, 2);
    $optimized_utilities = [
        'water' => $current_utilities > 0 ? round(($budget_data['utilities']['water'] ?? 0) * 0.85, 2) : round(50 * $household_size, 2),
        'phone' => $phone_cost, // Use generated phone cost
        'electricity' => $current_utilities > 0 ? round(($budget_data['utilities']['electricity'] ?? 0) * 0.9, 2) : round(80 * $household_size, 2),
        'other' => $current_utilities > 0 ? round(($budget_data['utilities']['other'] ?? 0) * 0.9, 2) : round(30 * $household_size, 2)
    ];
    $optimized_groceries = $current_groceries > 0 ? round($current_groceries * 0.9, 2) : round($baseline_groceries * 0.9, 2);
    $recommended_savings = $total_expenses > 0 ? round($total_expenses * 0.2, 2) : round($baseline_savings, 2);
    
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
            'optimized_rent' => $optimized_rent,
            'optimized_utilities' => $optimized_utilities,
            'optimized_groceries' => $optimized_groceries,
            'recommended_savings' => $recommended_savings,
            'car_cost' => $car_cost,
            'health_insurance_cost' => $health_insurance_cost,
            'emergency_fund_target' => round(($total_expenses > 0 ? $total_expenses : ($baseline_rent + $baseline_utilities + $baseline_groceries + $car_cost + $health_insurance_cost)) * 6, 2)
        ],
        'insights' => [
            'housing_affordability' => $current_rent > ($total_expenses * 0.3) ? 'High' : 'Good',
            'debt_to_income_ratio' => $total_expenses > 0 ? round(($current_debt_payment / $total_expenses) * 100, 1) : 0,
            'savings_rate' => $total_expenses > 0 ? round(($current_savings / $total_expenses) * 100, 1) : 0,
            'location_cost_index' => round($location_multiplier * 100, 0),
            'household_efficiency' => $household_size > 1 ? round($total_expenses / $household_size, 2) : $total_expenses
        ],
        'potential_savings' => [
            'monthly_savings_potential' => round(($total_expenses > 0 ? $total_expenses : ($baseline_rent + $baseline_utilities + $baseline_groceries)) * 0.15, 2),
            'annual_savings_potential' => round(($total_expenses > 0 ? $total_expenses : ($baseline_rent + $baseline_utilities + $baseline_groceries)) * 0.15 * 12, 2),
            'areas_for_improvement' => [
                'utilities_optimization' => round(($current_utilities > 0 ? $current_utilities : $baseline_utilities) * 0.1, 2),
                'groceries_optimization' => round(($current_groceries > 0 ? $current_groceries : $baseline_groceries) * 0.1, 2),
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
    // Load selected session data
    if (isset($_POST['load_session_id']) && !empty($_POST['load_session_id'])) {
        $selected_session_id = $_POST['load_session_id'];
        $selected_session = $db->getSession($selected_session_id);
        
        if ($selected_session && isset($selected_session['user_data']['household_data'])) {
            $budget_data = $selected_session['user_data']['household_data'];
            $destination_data = $selected_session['user_data']['destination_data'] ?? null;
            $current_session = $selected_session; // Update current session
            $_SESSION['current_session_id'] = $selected_session_id; // Update session ID
            $message = "Budget data loaded successfully!";
        } else {
            $message = "Selected budget data not found.";
        }
    } else {
        $message = "Please select a budget to load.";
    }
}

if (isset($_POST['Save'])) {
    // Save current budget data by replacing the oldest session
    // Get current form data
    $current_budget_data = [
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
        'car_cost' => (float)($_POST['car_cost'] ?? $budget_data['car_cost'] ?? 0),
        'health_insurance' => (float)($_POST['health_insurance'] ?? $budget_data['health_insurance'] ?? 0),
        'debt' => [
            'total_debt' => (float)($_POST['total_debt'] ?? $budget_data['debt']['total_debt'] ?? 0),
            'monthly_payment' => (float)($_POST['monthly_debt'] ?? $budget_data['debt']['monthly_payment'] ?? 0),
            'debt_type' => $_POST['debt_type'] ?? $budget_data['debt']['debt_type'] ?? '',
            'interest_rate' => (float)($_POST['interest_rate'] ?? $budget_data['debt']['interest_rate'] ?? 0)
        ],
        'monthly_payments' => $budget_data['monthly_payments'] ?? []
    ];
    
    // Create new session data
    $new_session_data = [
        'user_data' => [
            'household_data' => $current_budget_data,
            'destination_data' => $current_session['user_data']['destination_data'] ?? null,
            'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
            'advanced_analysis' => $current_session['user_data']['advanced_analysis'] ?? null
        ]
    ];
    
    // Generate new session ID
    $new_session_id = uniqid('budget_', true);
    
    // Create the new session (this will automatically clean up old sessions)
    if ($db->createSession($new_session_id, $new_session_data)) {
        $budget_data = $current_budget_data; // Update the current budget_data for display
        $current_session = $db->getSession($new_session_id);
        $_SESSION['current_session_id'] = $new_session_id;
        $message = "Budget data saved successfully! (Replaced oldest session)";
    } else {
        $message = "Failed to save budget data.";
    }
}

if (isset($_POST['NewBudget'])) {
    // Create a new budget session
    $new_session_id = uniqid('budget_', true);
    $new_budget_data = [
        'name' => '',
        'age' => 0,
        'location' => '',
        'household_size' => 1,
        'bedrooms' => 0,
        'bathrooms' => 0,
        'rent' => 0,
        'utilities' => [
            'water' => 0,
            'phone' => 0,
            'electricity' => 0,
            'other' => 0
        ],
        'groceries' => 0,
        'savings' => 0,
        'car_cost' => 0,
        'health_insurance' => 0,
        'debt' => [
            'total_debt' => 0,
            'monthly_payment' => 0,
            'debt_type' => '',
            'interest_rate' => 0
        ],
        'monthly_payments' => []
    ];
    
    $new_session_data = [
        'user_data' => [
            'household_data' => $new_budget_data,
            'app_requirements' => null,
            'advanced_analysis' => null
        ]
    ];
    
    if ($db->createSession($new_session_id, $new_session_data)) {
        $budget_data = $new_budget_data;
        $current_session = $db->getSession($new_session_id);
        $_SESSION['current_session_id'] = $new_session_id;
        $message = "New budget created successfully!";
    } else {
        $message = "Failed to create new budget.";
    }
}

if (isset($_POST['Generate'])) {
    // Generate new budget analysis with advanced data
    if ($current_session) {
        // Use current form data or existing budget data
        $current_data = [
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
        
        // Generate advanced budget analysis data
        $advanced_data = generateAdvancedBudgetData($current_data);
        
        // Update budget_data with optimized recommendations for the form fields
        $optimized_budget_data = [
            'name' => $current_data['name'],
            'age' => $current_data['age'],
            'location' => $current_data['location'],
            'household_size' => $current_data['household_size'],
            'bedrooms' => $current_data['bedrooms'],
            'bathrooms' => $current_data['bathrooms'],
            'rent' => $advanced_data['recommendations']['optimized_rent'],
            'utilities' => [
                'water' => $advanced_data['recommendations']['optimized_utilities']['water'],
                'phone' => $advanced_data['recommendations']['optimized_utilities']['phone'],
                'electricity' => $advanced_data['recommendations']['optimized_utilities']['electricity'],
                'other' => $advanced_data['recommendations']['optimized_utilities']['other']
            ],
            'groceries' => $advanced_data['recommendations']['optimized_groceries'],
            'savings' => $advanced_data['recommendations']['recommended_savings'],
            'car_cost' => $advanced_data['recommendations']['car_cost'],
            'health_insurance' => $advanced_data['recommendations']['health_insurance_cost'],
            'debt' => $current_data['debt'], // Keep current debt info
            'monthly_payments' => $current_data['monthly_payments']
        ];
        
        // Update the session with both optimized budget data and advanced analysis
        $update_data = [
            'user_data' => [
                'household_data' => $optimized_budget_data,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
                'advanced_analysis' => $advanced_data
            ]
        ];
        
        if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
            $budget_data = $optimized_budget_data; // Update the current budget_data for display
            $message = "Advanced budget analysis generated and applied to form fields!";
        } else {
            $message = "Failed to save advanced analysis.";
        }
    } else {
        $message = "Please create a budget session first.";
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
            'car_cost' => (float)($_POST['car_cost'] ?? $budget_data['car_cost'] ?? 0),
            'health_insurance' => (float)($_POST['health_insurance'] ?? $budget_data['health_insurance'] ?? 0),
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
                'destination_data' => $current_session['user_data']['destination_data'] ?? null,
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
        
        <!-- Debug Information (remove in production) -->
        <?php if (isset($_GET['debug'])): ?>
        <div style="background-color: #f0f0f0; padding: 10px; margin: 10px; border: 1px solid #ccc;">
            <h3>Debug Information:</h3>
            <p><strong>Current Session ID:</strong> <?php echo $_SESSION['current_session_id'] ?? 'Not set'; ?></p>
            <p><strong>Budget Data:</strong> <?php echo $budget_data ? 'Loaded' : 'Not loaded'; ?></p>
            <p><strong>Destination Data:</strong> <?php echo $destination_data ? 'Loaded' : 'Not loaded'; ?></p>
            <?php if ($current_session): ?>
                <p><strong>Session Data Keys:</strong> <?php echo implode(', ', array_keys($current_session['user_data'] ?? [])); ?></p>
                <?php if (isset($current_session['user_data']['destination_data'])): ?>
                    <p><strong>Destination Data:</strong> <?php echo htmlspecialchars(json_encode($current_session['user_data']['destination_data'])); ?></p>
                <?php endif; ?>
                <?php if (isset($current_session['user_data']['household_data'])): ?>
                    <p><strong>Household Data Location:</strong> <?php echo htmlspecialchars($current_session['user_data']['household_data']['location'] ?? 'Not set'); ?></p>
                <?php endif; ?>
            <?php endif; ?>
            <p><strong>All Sessions Count:</strong> <?php echo count($all_sessions); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="budget-actions">
            <form id="loadInformation" action="budget.php" method="post">
                <select name="load_session_id">
                    <option value="">Select a saved budget to load...</option>
                    <?php foreach ($all_sessions as $session): ?>
                        <?php if (isset($session['user_data']['household_data'])): ?>
                            <?php $data = $session['user_data']['household_data']; ?>
                            <option value="<?php echo $session['session_id']; ?>">
                                <?php echo htmlspecialchars($data['name'] ?? 'Unknown User'); ?> - 
                                <?php echo date('M j, Y g:i A', strtotime($session['created_at'])); ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Load Selected Budget" name="Load">
            </form>
            
            <form id="saveInformation" action="budget.php" method="post">
                <input type="submit" value="Save Current Budget" name="Save">
            </form>
            
            <form id="newBudget" action="budget.php" method="post">
                <input type="submit" value="Create New Budget" name="NewBudget">
            </form>
        </div>

        <form name="budgetForm" id="budgetForm" method="POST" action="">
            <div class="budget-actions">
                <input type="submit" value="Generate Analysis" name="Generate">
            </div>
            <table border="1">
                <tr>
                    <section class="compare-section">
                        <div class="reports-container">
                            <th>
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
                                        <div class="report-item">
                                            <strong>Car Ownership Cost:</strong>
                                            <input type="number" name="car_cost" value="<?php echo htmlspecialchars($budget_data['car_cost'] ?? ''); ?>" step="0.01">
                                        </div>
                                        <div class="report-item">
                                            <strong>Health Insurance:</strong>
                                            <input type="number" name="health_insurance" value="<?php echo htmlspecialchars($budget_data['health_insurance'] ?? ''); ?>" step="0.01">
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
                                                        ($budget_data['car_cost'] ?? 0) + 
                                                        ($budget_data['health_insurance'] ?? 0) + 
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
        </form>

        <!-- Location Comparison Section -->
        <?php if ($destination_data): ?>
        <div class="location-comparison">
            <h2>Location Comparison</h2>
            <div class="comparison-container">
                <div class="comparison-card current-location">
                    <h3>Current Location</h3>
                    <div class="comparison-item">
                        <strong>Location:</strong> <?php echo htmlspecialchars($budget_data['location'] ?? 'N/A'); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Household Size:</strong> <?php echo htmlspecialchars($budget_data['household_size'] ?? 'N/A'); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Bedrooms/Bathrooms:</strong> <?php echo htmlspecialchars(($budget_data['bedrooms'] ?? 'N/A') . '/' . ($budget_data['bathrooms'] ?? 'N/A')); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Rent:</strong> $<?php echo number_format($budget_data['rent'] ?? 0, 2); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Utilities:</strong> $<?php echo number_format($budget_data['utilities']['water'] ?? 0, 2); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Groceries:</strong> $<?php echo number_format($budget_data['groceries'] ?? 0, 2); ?>
                    </div>
                    <div class="comparison-item total">
                        <strong>Total Monthly:</strong> $<?php 
                            $current_total = ($budget_data['rent'] ?? 0) + 
                                           ($budget_data['utilities']['water'] ?? 0) + 
                                           ($budget_data['groceries'] ?? 0);
                            echo number_format($current_total, 2);
                        ?>
                    </div>
                </div>
                
                <div class="comparison-card destination-location">
                    <h3>Destination Location</h3>
                    <div class="comparison-item">
                        <strong>Location:</strong> <?php echo htmlspecialchars($destination_data['location'] ?? 'N/A'); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Household Size:</strong> <?php echo htmlspecialchars($destination_data['household_size'] ?? 'N/A'); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Bedrooms/Bathrooms:</strong> <?php echo htmlspecialchars(($destination_data['bedrooms'] ?? 'N/A') . '/' . ($destination_data['bathrooms'] ?? 'N/A')); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Rent:</strong> $<?php echo number_format($destination_data['rent'] ?? 0, 2); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Utilities:</strong> $<?php echo number_format($destination_data['utilities'] ?? 0, 2); ?>
                    </div>
                    <div class="comparison-item">
                        <strong>Groceries:</strong> $<?php echo number_format($destination_data['groceries'] ?? 0, 2); ?>
                    </div>
                    <div class="comparison-item total">
                        <strong>Total Monthly:</strong> $<?php 
                            $destination_total = ($destination_data['rent'] ?? 0) + 
                                               ($destination_data['utilities'] ?? 0) + 
                                               ($destination_data['groceries'] ?? 0);
                            echo number_format($destination_total, 2);
                        ?>
                    </div>
                </div>
            </div>
            
            <!-- Comparison Summary -->
            <div class="comparison-summary">
                <h3>Comparison Summary</h3>
                <div class="summary-item">
                    <strong>Monthly Difference:</strong> 
                    <?php 
                        $difference = $destination_total - $current_total;
                        $difference_text = $difference >= 0 ? '+' : '';
                        $difference_color = $difference >= 0 ? 'red' : 'green';
                        echo '<span style="color: ' . $difference_color . ';">' . $difference_text . '$' . number_format($difference, 2) . '</span>';
                    ?>
                </div>
                <div class="summary-item">
                    <strong>Annual Difference:</strong> 
                    <?php 
                        $annual_difference = $difference * 12;
                        $annual_difference_text = $annual_difference >= 0 ? '+' : '';
                        $annual_difference_color = $annual_difference >= 0 ? 'red' : 'green';
                        echo '<span style="color: ' . $annual_difference_color . ';">' . $annual_difference_text . '$' . number_format($annual_difference, 2) . '</span>';
                    ?>
                </div>
                <div class="summary-item">
                    <strong>Percentage Change:</strong> 
                    <?php 
                        if ($current_total > 0) {
                            $percentage_change = (($destination_total - $current_total) / $current_total) * 100;
                            $percentage_text = $percentage_change >= 0 ? '+' : '';
                            $percentage_color = $percentage_change >= 0 ? 'red' : 'green';
                            echo '<span style="color: ' . $percentage_color . ';">' . $percentage_text . number_format($percentage_change, 1) . '%</span>';
                        } else {
                            echo 'N/A';
                        }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

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
                        <div class="analysis-item">
                            <strong>Car Ownership Cost:</strong> $<?php echo number_format($analysis['recommendations']['car_cost'], 2); ?>
                        </div>
                        <div class="analysis-item">
                            <strong>Health Insurance:</strong> $<?php echo number_format($analysis['recommendations']['health_insurance_cost'], 2); ?>
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