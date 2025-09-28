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
$household_data = null;

if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
    if ($current_session && isset($current_session['user_data']['household_data'])) {
        $household_data = $current_session['user_data']['household_data'];
    }
}

// Handle form submissions
if (isset($_POST['generate_comparison'])) {
    // Validate required fields (only profile data is required)
    $required_profile_fields = [
        'current_location', 'current_household_size', 'current_bath_bed'
    ];
    
    $missing_fields = [];
    foreach ($required_profile_fields as $field) {
        if (empty($_POST[$field])) {
            $missing_fields[] = str_replace('_', ' ', $field);
        }
    }
    
    if (!empty($missing_fields)) {
        $message = "Please fill in all required profile fields: " . implode(', ', $missing_fields);
    } else {
        // Parse bedroom/bathroom data safely
        $current_bath_bed = explode('/', $_POST['current_bath_bed']);
        $destination_bath_bed = explode('/', $_POST['destination_bath_bed']);
        
        // Get location multipliers for cost estimation
        function getLocationMultiplier($location) {
            $location = strtolower($location);
            if (strpos($location, 'new york') !== false || strpos($location, 'san francisco') !== false || strpos($location, 'los angeles') !== false) {
                return 1.4;
            } elseif (strpos($location, 'chicago') !== false || strpos($location, 'boston') !== false || strpos($location, 'seattle') !== false) {
                return 1.2;
            } elseif (strpos($location, 'texas') !== false || strpos($location, 'florida') !== false || strpos($location, 'arizona') !== false) {
                return 0.8;
            }
            return 1.0;
        }
        
        $current_household_size = (int)$_POST['current_household_size'];
        $current_bedrooms = (int)($current_bath_bed[0] ?? 0);
        $current_bathrooms = (float)($current_bath_bed[1] ?? 0);
        $current_location_multiplier = getLocationMultiplier($_POST['current_location']);
        
        $destination_household_size = (int)$_POST['destination_household_size'];
        $destination_bedrooms = (int)($destination_bath_bed[0] ?? 0);
        $destination_bathrooms = (float)($destination_bath_bed[1] ?? 0);
        $destination_location_multiplier = getLocationMultiplier($_POST['destination_location']);
        
        // Generate estimates for missing financial data
        $current_rent = (float)$_POST['current_rent'] ?: (800 * $current_location_multiplier * $current_household_size * (1 + ($current_bedrooms - 1) * 0.3));
        $current_utilities = (float)$_POST['current_utilities'] ?: (200 * $current_household_size * (1 + ($current_bathrooms - 1) * 0.2));
        $current_groceries = (float)$_POST['current_groceries'] ?: (400 * $current_household_size);
        
        $destination_rent = (float)$_POST['destination_rent'] ?: (800 * $destination_location_multiplier * $destination_household_size * (1 + ($destination_bedrooms - 1) * 0.3));
        $destination_utilities = (float)$_POST['destination_utilities'] ?: (200 * $destination_household_size * (1 + ($destination_bathrooms - 1) * 0.2));
        $destination_groceries = (float)$_POST['destination_groceries'] ?: (400 * $destination_household_size);
        
        // Prepare current location data
        $current_location_data = [
            'name' => $household_data['name'] ?? 'User',
            'age' => $household_data['age'] ?? 25,
            'location' => trim($_POST['current_location']),
            'household_size' => $current_household_size,
            'bedrooms' => $current_bedrooms,
            'bathrooms' => $current_bathrooms,
            'rent' => $current_rent,
            'utilities' => [
                'water' => $current_utilities,
                'phone' => $household_data['utilities']['phone'] ?? 0,
                'electricity' => $household_data['utilities']['electricity'] ?? 0,
                'other' => $household_data['utilities']['other'] ?? 0
            ],
            'groceries' => $current_groceries,
            'savings' => $household_data['savings'] ?? 0,
            'car_cost' => $household_data['car_cost'] ?? 0,
            'health_insurance' => $household_data['health_insurance'] ?? 0,
            'debt' => $household_data['debt'] ?? [
                'total_debt' => 0,
                'monthly_payment' => 0,
                'debt_type' => '',
                'interest_rate' => 0
            ],
            'monthly_payments' => $household_data['monthly_payments'] ?? []
        ];
        
        // Store destination data for comparison
        $destination_data = [
            'location' => trim($_POST['destination_location']),
            'household_size' => $destination_household_size,
            'bedrooms' => $destination_bedrooms,
            'bathrooms' => $destination_bathrooms,
            'rent' => $destination_rent,
            'utilities' => $destination_utilities,
            'groceries' => $destination_groceries
        ];
        
        // Check if we have an existing session to update
        if (isset($_SESSION['current_session_id']) && $current_session) {
            // Update existing session
            $success = $db->updateSession($_SESSION['current_session_id'], [
                'user_data' => [
                    'household_data' => $current_location_data,
                    'destination_data' => $destination_data,
                    'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
                    'advanced_analysis' => $current_session['user_data']['advanced_analysis'] ?? null
                ]
            ]);
        } else {
            // Create new session
            $new_session_id = 'session_' . uniqid();
            $user_data = [
                'household_data' => $current_location_data,
                'destination_data' => $destination_data,
                'app_requirements' => null,
                'advanced_analysis' => null
            ];
            $success = $db->createSession($new_session_id, $user_data);
            if ($success) {
                $_SESSION['current_session_id'] = $new_session_id;
            }
        }
        
        if ($success) {
            $message = "Location comparison generated successfully! Redirecting to budget page...";
            
            // Redirect to budget page after successful save
            header("refresh:2;url=budget.php");
            exit();
        } else {
            $message = "Error: Failed to save location comparison data.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Location Comparison - Budget App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css"/>
    <script src="./js/app.js"></script>
</head>

<body>
    <?php include 'navigation.php'?>
    
    <div class="compare-section">
        <h1 class="text-center">Location Comparison</h1>
        
        <?php if (isset($message)): ?>
            <div class="<?php echo (strpos($message, 'Error:') === 0 || strpos($message, 'Please fill in') === 0) ? 'error-message' : 'success-message'; ?> text-center">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="reports-container">
            <!-- Current Location Form -->
            <div class="location-form">
                <h3 class="text-center">Current Location <span class="required-indicator">*</span></h3>
                <p class="form-description">Profile information is required. Financial data is optional and will be estimated if not provided.</p>
                <form name="currentLocationForm" id="currentLocationForm" method="POST" action="">
                    <div class="report-item">
                        <label for="current_location">Location <span class="required">*</span></label>
                        <input type="text" id="current_location" name="current_location" value="<?php echo htmlspecialchars($household_data['location'] ?? ''); ?>" placeholder="Enter current city, state" required>
                    </div>
                    <div class="report-item">
                        <label for="current_household_size">Household Size <span class="required">*</span></label>
                        <input type="number" id="current_household_size" name="current_household_size" value="<?php echo htmlspecialchars($household_data['household_size'] ?? ''); ?>" placeholder="Number of people" min="1" max="20" required>
                    </div>
                    <div class="report-item">
                        <label for="current_bath_bed">Bedrooms/Bathrooms <span class="required">*</span></label>
                        <input type="text" id="current_bath_bed" name="current_bath_bed" value="<?php echo htmlspecialchars(($household_data['bedrooms'] ?? '') . '/' . ($household_data['bathrooms'] ?? '')); ?>" placeholder="e.g., 2/1" required>
                    </div>
                    <div class="cost-section">
                        <div class="report-item">
                            <label for="current_rent">Monthly Rent</label>
                            <input type="number" id="current_rent" name="current_rent" value="<?php echo htmlspecialchars($household_data['rent'] ?? ''); ?>" step="0.01" placeholder="0.00">
                        </div>
                        <div class="report-item">
                            <label for="current_utilities">Utilities</label>
                            <input type="number" id="current_utilities" name="current_utilities" value="<?php echo htmlspecialchars($household_data['utilities']['water'] ?? ''); ?>" step="0.01" placeholder="0.00">
                        </div>
                        <div class="report-item">
                            <label for="current_groceries">Groceries</label>
                            <input type="number" id="current_groceries" name="current_groceries" value="<?php echo htmlspecialchars($household_data['groceries'] ?? ''); ?>" step="0.01" placeholder="0.00">
                        </div>
                        <div class="report-item total text-center">
                            <strong>Total Monthly Cost:</strong>
                            <div class="total-amount">$<?php 
                                $total = ($household_data['rent'] ?? 0) + 
                                        ($household_data['utilities']['water'] ?? 0) + 
                                        ($household_data['groceries'] ?? 0);
                                echo number_format($total, 2);
                            ?></div>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Destination Form -->
            <div class="location-form">
                <h3 class="text-center">Destination Location <span class="optional-indicator">(Optional)</span></h3>
                <p class="form-description">Enter destination details to compare costs. Financial data will be estimated if not provided.</p>
                <form name="destinationForm" id="destinationForm" method="POST" action="">
                    <div class="report-item">
                        <label for="destination_location">Location</label>
                        <input type="text" id="destination_location" name="destination_location" value="" placeholder="Enter destination city, state">
                    </div>
                    <div class="report-item">
                        <label for="destination_household_size">Household Size</label>
                        <input type="number" id="destination_household_size" name="destination_household_size" value="" placeholder="Number of people" min="1">
                    </div>
                    <div class="report-item">
                        <label for="destination_bath_bed">Bedrooms/Bathrooms</label>
                        <input type="text" id="destination_bath_bed" name="destination_bath_bed" value="" placeholder="e.g., 2/1">
                    </div>
                    <div class="cost-section">
                        <div class="report-item">
                            <label for="destination_rent">Monthly Rent</label>
                            <input type="number" id="destination_rent" name="destination_rent" value="" step="0.01" placeholder="0.00">
                        </div>
                        <div class="report-item">
                            <label for="destination_utilities">Utilities</label>
                            <input type="number" id="destination_utilities" name="destination_utilities" value="" step="0.01" placeholder="0.00">
                        </div>
                        <div class="report-item">
                            <label for="destination_groceries">Groceries</label>
                            <input type="number" id="destination_groceries" name="destination_groceries" value="" step="0.01" placeholder="0.00">
                        </div>
                        <div class="report-item total text-center">
                            <strong>Total Monthly Cost:</strong>
                            <div class="total-amount">$0.00</div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Generate Comparison Button -->
        <div class="text-center mt-4">
            <button type="button" onclick="generateComparison()" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 3h18v18H3zM9 9h6v6H9z"></path>
                </svg>
                Generate Comparison
            </button>
        </div>
    </div>

<script>
// Function to calculate and update the total for current location
function updateCurrentTotal() {
    const rent = parseFloat(document.querySelector('input[name="current_rent"]').value) || 0;
    const utilities = parseFloat(document.querySelector('input[name="current_utilities"]').value) || 0;
    const groceries = parseFloat(document.querySelector('input[name="current_groceries"]').value) || 0;
    const total = rent + utilities + groceries;
    
    const totalElement = document.querySelector('.location-form:first-child .total-amount');
    if (totalElement) {
        totalElement.textContent = '$' + total.toFixed(2);
    }
}

// Function to calculate and update the total for destination
function updateDestinationTotal() {
    const rent = parseFloat(document.querySelector('input[name="destination_rent"]').value) || 0;
    const utilities = parseFloat(document.querySelector('input[name="destination_utilities"]').value) || 0;
    const groceries = parseFloat(document.querySelector('input[name="destination_groceries"]').value) || 0;
    const total = rent + utilities + groceries;
    
    const destinationTotalElement = document.querySelector('.location-form:last-child .total-amount');
    if (destinationTotalElement) {
        destinationTotalElement.textContent = '$' + total.toFixed(2);
    }
}

// Function to handle the generate comparison button
function generateComparison() {
    // Collect all form data
    const currentData = {
        location: document.querySelector('input[name="current_location"]').value,
        household_size: document.querySelector('input[name="current_household_size"]').value,
        bath_bed: document.querySelector('input[name="current_bath_bed"]').value,
        rent: document.querySelector('input[name="current_rent"]').value,
        utilities: document.querySelector('input[name="current_utilities"]').value,
        groceries: document.querySelector('input[name="current_groceries"]').value
    };
    
    const destinationData = {
        location: document.querySelector('input[name="destination_location"]').value,
        household_size: document.querySelector('input[name="destination_household_size"]').value,
        bath_bed: document.querySelector('input[name="destination_bath_bed"]').value,
        rent: document.querySelector('input[name="destination_rent"]').value,
        utilities: document.querySelector('input[name="destination_utilities"]').value,
        groceries: document.querySelector('input[name="destination_groceries"]').value
    };
    
    // Validate that destination has some data
    if (!destinationData.location && !destinationData.rent && !destinationData.utilities && !destinationData.groceries) {
        return;
    }
    
    // Create a hidden form to submit all data
    const hiddenForm = document.createElement('form');
    hiddenForm.method = 'POST';
    hiddenForm.action = '';
    
    // Add current location data
    Object.keys(currentData).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'current_' + key;
        input.value = currentData[key];
        hiddenForm.appendChild(input);
    });
    
    // Add destination data
    Object.keys(destinationData).forEach(key => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'destination_' + key;
        input.value = destinationData[key];
        hiddenForm.appendChild(input);
    });
    
    // Add submit flag
    const submitInput = document.createElement('input');
    submitInput.type = 'hidden';
    submitInput.name = 'generate_comparison';
    submitInput.value = '1';
    hiddenForm.appendChild(submitInput);
    
    // Add form to page and submit
    document.body.appendChild(hiddenForm);
    hiddenForm.submit();
}

// Add event listeners to form inputs
document.addEventListener('DOMContentLoaded', function() {
    const currentInputs = document.querySelectorAll('input[name="current_rent"], input[name="current_utilities"], input[name="current_groceries"]');
    currentInputs.forEach(input => {
        input.addEventListener('input', updateCurrentTotal);
    });
    
    const destinationInputs = document.querySelectorAll('input[name="destination_rent"], input[name="destination_utilities"], input[name="destination_groceries"]');
    destinationInputs.forEach(input => {
        input.addEventListener('input', updateDestinationTotal);
    });
    
    // Calculate initial totals
    updateCurrentTotal();
    updateDestinationTotal();
});
</script>
</body>
</html>



