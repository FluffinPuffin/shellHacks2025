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
    <title>Location Comparison</title>
    <link rel="stylesheet" href="./css/style.css"/>
</head>

<body>
    <?php include 'navigation.php'?>
    <?php if (isset($message)): ?>
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>
    <h2>Location Comparison</h2>
        <table border="1">
            <tr>
                <section class="compare-section">
                    <div class="reports-container">


                        <th>
                            <form name="currentLocationForm" id="currentLocationForm" method="POST" action="">
                                <div class="location-report">
                                    <h3>Current Location</h3>
                                    <div class="report-item">
                                        <strong>Location:</strong>
                                        <input type="text" name="current_location" value="<?php echo htmlspecialchars($household_data['location'] ?? ''); ?>">
                                    </div>
                                    <div class="report-item">
                                        <strong>Household Size:</strong>
                                        <input type="number" name="current_household_size" value="<?php echo htmlspecialchars($household_data['household_size'] ?? ''); ?>">
                                    </div>
                                    <div class="report-item">
                                        <strong>Bath/Bed:</strong>
                                        <input type="text" name="current_bath_bed" value="<?php echo htmlspecialchars(($household_data['bedrooms'] ?? '') . '/' . ($household_data['bathrooms'] ?? '')); ?>">
                                    </div>
                                    <div class="cost-section">
                                        <div class="report-item">
                                            <strong>Rent:</strong>
                                            <input type="number" name="current_rent" value="<?php echo htmlspecialchars($household_data['rent'] ?? ''); ?>" step="0.01">
                                        </div>
                                        <div class="report-item">
                                            <strong>Utilities:</strong>
                                            <input type="number" name="current_utilities" value="<?php echo htmlspecialchars($household_data['utilities']['water'] ?? ''); ?>" step="0.01">
                                        </div>
                                        <div class="report-item">
                                            <strong>Groceries:</strong>
                                            <input type="number" name="current_groceries" value="<?php echo htmlspecialchars($household_data['groceries'] ?? ''); ?>" step="0.01">
                                        </div>
                                        <div class="report-item total">
                                            <strong>Total:</strong>
                                            <span><strong>$<?php 
                                                $total = ($household_data['rent'] ?? 0) + 
                                                        ($household_data['utilities']['water'] ?? 0) + 
                                                        ($household_data['groceries'] ?? 0);
                                                echo number_format($total, 2);
                                            ?></strong></span>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </th>
                        <th>  
                            <div class="location-report">
                                <h3>Destination</h3>
                                <div class="report-item">
                                    <strong>Location:</strong>
                                    <input type="text" name="destination_location" value="">
                                </div>
                                <div class="report-item">
                                    <strong>Household Size:</strong>
                                    <input type="number" name="destination_household_size" value="">
                                </div>
                                <div class="report-item">
                                    <strong>Bath/Bed:</strong>
                                    <input type="text" name="destination_bath_bed" value="">
                                </div>
                                <div class="cost-section">
                                    <div class="report-item">
                                        <strong>Rent:</strong>
                                        <input type="number" name="destination_rent" value="" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Utilities:</strong>
                                        <input type="number" name="destination_utilities" value="" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Groceries:</strong>
                                        <input type="number" name="destination_groceries" value="" step="0.01">
                                    </div>
                                    <div class="report-item total">
                                        <strong>Total:</strong>
                                        <span><strong>TOTAL HERE</strong></span>
                                    </div>
                                </div>
                            </div>
                        </th>
                        
                    </div>
                </section>
            </tr>
        </table>
        
        <!-- Single Generate Button -->
        <div style="text-align: center; margin: 20px;">
            <button type="button" onclick="generateComparison()" style="background-color: #007bff; color: white; padding: 12px 24px; border: none; border-radius: 5px; font-size: 16px; cursor: pointer;">
                Generate Comparison
            </button>
        </div>

<script>
// Function to calculate and update the total for current location
function updateCurrentTotal() {
    const rent = parseFloat(document.querySelector('input[name="current_rent"]').value) || 0;
    const utilities = parseFloat(document.querySelector('input[name="current_utilities"]').value) || 0;
    const groceries = parseFloat(document.querySelector('input[name="current_groceries"]').value) || 0;
    const total = rent + utilities + groceries;
    
    const totalElement = document.querySelector('.location-report .total span strong');
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
    
    const destinationTotalElement = document.querySelectorAll('.location-report .total span strong')[1];
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


