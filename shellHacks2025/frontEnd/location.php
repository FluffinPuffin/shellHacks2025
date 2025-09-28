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
    // Prepare current location data
    $current_location_data = [
        'name' => $_POST['current_location'] ?? 'User',
        'age' => 25, // Default age
        'location' => $_POST['current_location'],
        'household_size' => (int)$_POST['current_household_size'],
        'bedrooms' => (int)explode('/', $_POST['current_bath_bed'])[0] ?? 0,
        'bathrooms' => (int)explode('/', $_POST['current_bath_bed'])[1] ?? 0,
        'rent' => (float)$_POST['current_rent'],
        'utilities' => [
            'water' => (float)$_POST['current_utilities'],
            'phone' => 0,
            'electricity' => 0,
            'other' => 0
        ],
        'groceries' => (float)$_POST['current_groceries'],
        'savings' => 0,
        'debt' => [
            'total_debt' => 0,
            'monthly_payment' => 0,
            'debt_type' => '',
            'interest_rate' => 0
        ],
        'monthly_payments' => []
    ];
    
    // Store destination data for comparison
    $destination_data = [
        'location' => $_POST['destination_location'],
        'household_size' => (int)$_POST['destination_household_size'],
        'bedrooms' => (int)explode('/', $_POST['destination_bath_bed'])[0] ?? 0,
        'bathrooms' => (int)explode('/', $_POST['destination_bath_bed'])[1] ?? 0,
        'rent' => (float)$_POST['destination_rent'],
        'utilities' => (float)$_POST['destination_utilities'],
        'groceries' => (float)$_POST['destination_groceries']
    ];
    
    // Check if we have an existing session to update
    if (isset($_SESSION['current_session_id']) && $current_session) {
        // Update existing session
        $success = $db->updateSession($_SESSION['current_session_id'], [
            'user_data' => [
                'household_data' => $current_location_data,
                'destination_data' => $destination_data,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null
            ]
        ]);
    } else {
        // Create new session
        $new_session_id = 'session_' . uniqid();
        $user_data = [
            'household_data' => $current_location_data,
            'destination_data' => $destination_data,
            'app_requirements' => null
        ];
        $success = $db->createSession($new_session_id, $user_data);
        if ($success) {
            $_SESSION['current_session_id'] = $new_session_id;
        }
    }
    
    if ($success) {
        $message = "Location comparison generated successfully!";
        
        // Debug: Show what was saved
        if (isset($_GET['debug'])) {
            $message .= " | Debug: Session ID: " . $_SESSION['current_session_id'];
            $message .= " | Current: " . $current_location_data['location'];
            $message .= " | Destination: " . $destination_data['location'];
        } else {
            // Redirect to budget page after successful save
            header("Location: budget.php");
            exit();
        }
    } else {
        $message = "Error: Failed to save location comparison data.";
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
</head>

<body>
    <?php include 'navigation.php'?>
    
    <div class="compare-section">
        <h1 class="text-center">Location Comparison</h1>
        
        <?php if (isset($message)): ?>
            <div class="success-message text-center">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="reports-container">
            <!-- Current Location Form -->
            <div class="location-form">
                <h3 class="text-center">Current Location</h3>
                <form name="currentLocationForm" id="currentLocationForm" method="POST" action="">
                    <div class="report-item">
                        <label for="current_location">Location</label>
                        <input type="text" id="current_location" name="current_location" value="<?php echo htmlspecialchars($household_data['location'] ?? ''); ?>" placeholder="Enter current city, state">
                    </div>
                    <div class="report-item">
                        <label for="current_household_size">Household Size</label>
                        <input type="number" id="current_household_size" name="current_household_size" value="<?php echo htmlspecialchars($household_data['household_size'] ?? ''); ?>" placeholder="Number of people" min="1">
                    </div>
                    <div class="report-item">
                        <label for="current_bath_bed">Bedrooms/Bathrooms</label>
                        <input type="text" id="current_bath_bed" name="current_bath_bed" value="<?php echo htmlspecialchars(($household_data['bedrooms'] ?? '') . '/' . ($household_data['bathrooms'] ?? '')); ?>" placeholder="e.g., 2/1">
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
                <h3 class="text-center">Destination Location</h3>
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
        alert('Please enter destination information before generating comparison.');
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



