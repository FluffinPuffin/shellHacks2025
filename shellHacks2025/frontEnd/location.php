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
    // Update current location data in session
    if ($household_data) {
        $household_data['location'] = $_POST['current_location'];
        $household_data['household_size'] = (int)$_POST['current_household_size'];
        $household_data['bedrooms'] = (int)explode('/', $_POST['current_bath_bed'])[0] ?? 0;
        $household_data['bathrooms'] = (int)explode('/', $_POST['current_bath_bed'])[1] ?? 0;
        $household_data['rent'] = (float)$_POST['current_rent'];
        $household_data['utilities']['water'] = (float)$_POST['current_utilities'];
        $household_data['groceries'] = (float)$_POST['current_groceries'];
        
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
        
        // Update session in database with both current and destination data
        $db->updateSession($_SESSION['current_session_id'], [
            'user_data' => [
                'household_data' => $household_data,
                'destination_data' => $destination_data,
                'app_requirements' => $current_session['user_data']['app_requirements'] ?? null
            ]
        ]);
        
        $message = "Location comparison generated successfully!";
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
    
    // Submit the form with all data
    const form = document.querySelector('form[name="currentLocationForm"]');
    const formData = new FormData(form);
    
    // Add destination data to form
    Object.keys(destinationData).forEach(key => {
        formData.append('destination_' + key, destinationData[key]);
    });
    
    formData.append('generate_comparison', '1');
    
    // Submit via fetch
    fetch('', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Redirect to budget page after successful generation
        window.location.href = 'budget.php';
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error generating comparison. Please try again.');
    });
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


