<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

// Get current session data
$current_session = null;
$profile_data = [
    'name' => '',
    'age' => '',
    'location' => '',
    'household_size' => '',
    'bedrooms' => '',
    'bathrooms' => ''
];

if (isset($_SESSION['current_session_id'])) {
    $current_session = $db->getSession($_SESSION['current_session_id']);
    if ($current_session && isset($current_session['user_data']['household_data'])) {
        $household_data = $current_session['user_data']['household_data'];
        $profile_data = [
            'name' => $household_data['name'] ?? '',
            'age' => $household_data['age'] ?? '',
            'location' => $household_data['location'] ?? '',
            'household_size' => $household_data['household_size'] ?? '',
            'bedrooms' => $household_data['bedrooms'] ?? '',
            'bathrooms' => $household_data['bathrooms'] ?? ''
        ];
    } elseif ($current_session) {
        // Session exists but no household data - use session variables as fallback
        $profile_data = [
            'name' => $_SESSION['name'] ?? '',
            'age' => $_SESSION['age'] ?? '',
            'location' => $_SESSION['location'] ?? '',
            'household_size' => $_SESSION['house'] ?? '',
            'bedrooms' => $_SESSION['bedroom'] ?? '',
            'bathrooms' => $_SESSION['bathroom'] ?? ''
        ];
    }
}

// Handle form submission
if ($_POST && isset($_POST['update_profile'])) {
    // Validate input data
    $name = trim($_POST['name'] ?? '');
    $age = (int)($_POST['age'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $household_size = (int)($_POST['household_size'] ?? 0);
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (float)($_POST['bathrooms'] ?? 0);

    // Validation
    if (empty($name) || empty($location)) {
        $error_message = "Name and location are required fields.";
    } elseif ($age < 18 || $age > 120) {
        $error_message = "Please enter a valid age between 18 and 120.";
    } elseif ($household_size < 1 || $household_size > 20) {
        $error_message = "Please enter a valid household size between 1 and 20.";
    } elseif ($bedrooms < 0 || $bedrooms > 20) {
        $error_message = "Please enter a valid number of bedrooms.";
    } elseif ($bathrooms < 0 || $bathrooms > 20) {
        $error_message = "Please enter a valid number of bathrooms.";
    } else {
        // Update the session data
        if ($current_session && isset($current_session['user_data']['household_data'])) {
            $updated_household_data = $current_session['user_data']['household_data'];
            $updated_household_data['name'] = $name;
            $updated_household_data['age'] = $age;
            $updated_household_data['location'] = $location;
            $updated_household_data['household_size'] = $household_size;
            $updated_household_data['bedrooms'] = $bedrooms;
            $updated_household_data['bathrooms'] = $bathrooms;
            
            $update_data = [
                'user_data' => [
                    'household_data' => $updated_household_data,
                    'destination_data' => $current_session['user_data']['destination_data'] ?? null,
                    'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
                    'advanced_analysis' => $current_session['user_data']['advanced_analysis'] ?? null
                ]
            ];
            
            if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
                $success_message = "Profile updated successfully!";
                $profile_data = [
                    'name' => $name,
                    'age' => $age,
                    'location' => $location,
                    'household_size' => $household_size,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms
                ];
            } else {
                $error_message = "Failed to update profile. Please try again.";
            }
        } elseif ($current_session) {
            // Session exists but no household data - create new household data
            $new_household_data = [
                'name' => $name,
                'age' => $age,
                'location' => $location,
                'household_size' => $household_size,
                'bedrooms' => $bedrooms,
                'bathrooms' => $bathrooms,
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
            
            $update_data = [
                'user_data' => [
                    'household_data' => $new_household_data,
                    'destination_data' => $current_session['user_data']['destination_data'] ?? null,
                    'app_requirements' => $current_session['user_data']['app_requirements'] ?? null,
                    'advanced_analysis' => $current_session['user_data']['advanced_analysis'] ?? null
                ]
            ];
            
            if ($db->updateSession($_SESSION['current_session_id'], $update_data)) {
                $success_message = "Profile created successfully!";
                $profile_data = [
                    'name' => $name,
                    'age' => $age,
                    'location' => $location,
                    'household_size' => $household_size,
                    'bedrooms' => $bedrooms,
                    'bathrooms' => $bathrooms
                ];
            } else {
                $error_message = "Failed to create profile. Please try again.";
            }
        } else {
            $error_message = "No active session found. Please create a new budget analysis.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - Budget App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'?>

    <div class="profile-container">
        <h1>Profile</h1>
        <?php if (isset($_SESSION['username'])){ ?>

        <?php if ($success_message): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="profile-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($profile_data['name']); ?>" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($profile_data['age']); ?>" min="18" max="120" placeholder="Enter your age" required>
            </div>

            <div class="form-group">
                <label for="location">Current Location</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($profile_data['location']); ?>" placeholder="City, State" required>
            </div>

            <div class="form-group">
                <label for="household_size">Household Size</label>
                <input type="number" id="household_size" name="household_size" value="<?php echo htmlspecialchars($profile_data['household_size']); ?>" min="1" max="20" placeholder="Number of people" required>
            </div>

            <div class="form-group">
                <label for="bedrooms">Bedrooms</label>
                <input type="number" id="bedrooms" name="bedrooms" value="<?php echo htmlspecialchars($profile_data['bedrooms']); ?>" min="0" max="20" placeholder="Number of bedrooms" required>
            </div>

            <div class="form-group">
                <label for="bathrooms">Bathrooms</label>
                <input type="number" id="bathrooms" name="bathrooms" value="<?php echo htmlspecialchars($profile_data['bathrooms']); ?>" min="0" max="20" step="0.5" placeholder="Number of bathrooms" required>
            </div>

            <div class="form-group">
                <button type="submit" name="update_profile" id="saveButton" class="btn btn-primary">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <polyline points="17,21 17,13 7,13 7,21"></polyline>
                        <polyline points="7,3 7,8 15,8"></polyline>
                    </svg>
                    Save Profile
                </button>
            </div>
        </form>
        <?php } else { ?>
            <p>Please log in to view and edit your profile.</p>
            <?php } ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.profile-form');
            const submitButton = document.getElementById('saveButton');
            const inputs = form.querySelectorAll('input[type="text"], input[type="number"]');

            // Store original values
            const originalValues = {};
            inputs.forEach(input => {
                originalValues[input.name] = input.value;
            });

            // Function to check if any field has changed
            function checkForChanges() {
                let hasChanges = false;

                inputs.forEach(input => {
                    if (input.value !== originalValues[input.name]) {
                        hasChanges = true;
                    }
                });

                // Enable/disable submit button based on changes
                submitButton.disabled = !hasChanges;

                // Optional: Add visual feedback
                if (hasChanges) {
                    submitButton.style.opacity = '1';
                    submitButton.style.cursor = 'pointer';
                } else {
                    submitButton.style.opacity = '0.6';
                    submitButton.style.cursor = 'not-allowed';
                }
            }

            // Add event listeners to all form inputs
            inputs.forEach(input => {
                input.addEventListener('input', checkForChanges);
                input.addEventListener('change', checkForChanges);
            });

            // Initial check
            checkForChanges();
        });
    </script>

</body>
</html>