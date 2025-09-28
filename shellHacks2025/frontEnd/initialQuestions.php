<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

// If everything inside is not empty and submit button is pressed then save to database
if (isset($_POST['submit']) && !empty($_POST['name']) && !empty($_POST['age']) && !empty($_POST['house']) && !empty($_POST['bedroom']) && !empty($_POST['bathroom']) && !empty($_POST['location'])) {
    // Validate input data
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $household_size = (int)$_POST['house'];
    $bedrooms = (int)$_POST['bedroom'];
    $bathrooms = (float)$_POST['bathroom'];
    $location = trim($_POST['location']);
    
    // Additional validation
    if ($age < 18 || $age > 120) {
        $error = "Please enter a valid age between 18 and 120.";
    } elseif ($household_size < 1 || $household_size > 20) {
        $error = "Please enter a valid household size between 1 and 20.";
    } elseif ($bedrooms < 0 || $bedrooms > 20) {
        $error = "Please enter a valid number of bedrooms.";
    } elseif ($bathrooms < 0 || $bathrooms > 20) {
        $error = "Please enter a valid number of bathrooms.";
    } else {
        // Generate a unique session ID
        $session_id = 'session_' . uniqid();
        
        // Prepare household data
        $household_data = [
            'name' => $name,
            'age' => $age,
            'household_size' => $household_size,
            'bedrooms' => $bedrooms,
            'bathrooms' => $bathrooms,
            'location' => $location,
            'rent' => 0, // Will be filled in later
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
        
        // Create session in database
        $user_data = [
            'household_data' => $household_data,
            'app_requirements' => null,
            'destination_data' => null,
            'advanced_analysis' => null
        ];
        
        if ($db->createSession($session_id, $user_data)) {
            $_SESSION['current_session_id'] = $session_id;
            $_SESSION['name'] = $name;
            $_SESSION['age'] = $age;
            $_SESSION['house'] = $household_size;
            $_SESSION['bedroom'] = $bedrooms;
            $_SESSION['bathroom'] = $bathrooms;
            $_SESSION['location'] = $location;
            $success = "Profile created successfully! Redirecting to budget page...";
            
            // Redirect after a short delay to show success message
            header("refresh:2;url=budget.php");
        } else {
            $error = "Failed to save session. Please try again.";
        }
    }
} else if (isset($_POST['submit'])) {
    $error = "Please fill in all required fields.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tell us more about yourself</title>
    <link rel="stylesheet" href="./css/style.css">
    <script src="./js/initialQuestions.js" ></script>
</head>
<body>
    <?php include 'navigation.php'?>

    <h1>Tell us more about yourself</h1>
    
    <?php if ($error): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="success-message">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    
    <form id="initialQuestions" action="initialQuestions.php" method="post">
        <!-- Step 1 -->
        <div class="step step-1">
            <label for="name">Name:</label>
            <input type="text" name="name">

            <label for="age">Age:</label>
            <input type="number" name="age" min="18">

            <label for="house">Household Size:</label>
            <input type="number" name="house" min="1">
        </div>

        <!-- Step 2 -->
        <div class="step step-2">
            <label for="bedroom">Bedrooms:</label>
            <input type="number" name="bedroom" min="1">
            <label for="bathroom">Bathrooms:</label>
            <input type="number" name="bathroom" min="1">
        </div>

        <!-- Step 3 -->
        <div class="step step-3">
            <label for="location">Location:</label>
            <input type="text" name="location">
            <input type="submit" value="Submit" name="submit">
        </div>

        <!-- Navigation -->
        <input type="button" value="Back" name="back">
        <input type="button" value="Next" name="next">
    </form>
</body>
</html>
