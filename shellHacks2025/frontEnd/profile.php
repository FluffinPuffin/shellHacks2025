<?php
session_start();
if (isset($_SESSION['username'])) {
    require_once 'config/database.php';
// Handle form submission
if ($_POST) {
    // Process the profile form data here
    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? '';
    $location = $_POST['location'] ?? '';
    $household_size = $_POST['household_size'] ?? '';
    $bedrooms = $_POST['bedrooms'] ?? '';
    $bathrooms = $_POST['bathrooms'] ?? '';

    // You can add database saving logic here
    $success_message = "Profile updated successfully!";
}

// Load existing profile data (you can load from database here)
$profile_data = [
    'name' => $_POST['name'] ?? '',
    'age' => $_POST['age'] ?? '',
    'location' => $_POST['location'] ?? '',
    'household_size' => $_POST['household_size'] ?? '',
    'bedrooms' => $_POST['bedrooms'] ?? '',
    'bathrooms' => $_POST['bathrooms'] ?? ''
];
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

        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="profile-form">
            <div class="form-group">
                <label for="name">Full Name</label>
                <input type="text" id="name" name="name" value="<?php if (isset($_SESSION['username']))  echo htmlspecialchars($profile_data['name']); ?>" placeholder="Enter your full name" required>
            </div>

            <div class="form-group">
                <label for="age">Age</label>
                <input type="number" id="age" name="age" value="<?php if (isset($_SESSION['username'])) echo htmlspecialchars($profile_data['age']); ?>" min="1" max="120" placeholder="Enter your age" required>
            </div>

            <div class="form-group">
                <label for="location">Current Location</label>
                <input type="text" id="location" name="location" value="<?php if (isset($_SESSION['username']))  echo htmlspecialchars($profile_data['location']); ?>" placeholder="City, State" required>
            </div>

            <div class="form-group">
                <button type="submit" id="saveButton" class="btn btn-primary" disabled>
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