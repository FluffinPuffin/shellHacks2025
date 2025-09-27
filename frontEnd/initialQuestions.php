<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// If everything inside is not empty and submit button is pressed then save to database
if (isset($_POST['submit']) && !empty($_POST['name']) && !empty($_POST['age']) && !empty($_POST['house']) && !empty($_POST['bedroom']) && !empty($_POST['bathroom']) && !empty($_POST['location'])) {
    // Generate a unique session ID
    $session_id = 'session_' . uniqid();
    
    // Prepare household data
    $household_data = [
        'name' => $_POST['name'],
        'age' => (int)$_POST['age'],
        'household_size' => (int)$_POST['house'],
        'bedrooms' => (int)$_POST['bedroom'],
        'bathrooms' => (int)$_POST['bathroom'],
        'location' => $_POST['location'],
        'rent' => 0, // Will be filled in later
        'utilities' => [
            'water' => 0,
            'phone' => 0,
            'electricity' => 0,
            'other' => 0
        ],
        'groceries' => 0,
        'savings' => 0,
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
        'app_requirements' => null
    ];
    
    if ($db->createSession($session_id, $user_data)) {
        $_SESSION['current_session_id'] = $session_id;
        $_SESSION['name'] = $_POST['name'];
        $_SESSION['age'] = $_POST['age'];
        $_SESSION['house'] = $_POST['house'];
        $_SESSION['bedroom'] = $_POST['bedroom'];
        $_SESSION['bathroom'] = $_POST['bathroom'];
        $_SESSION['location'] = $_POST['location'];
        header("Location: home.php");
        exit();
    } else {
        $error = "Failed to save session. Please try again.";
    }
} else if (isset($_POST['submit'])) {
    $error = "Please fill in all fields.";
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
