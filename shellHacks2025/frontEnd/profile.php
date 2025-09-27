<?php
session_start();

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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title>Profile - Budget App</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'?>

    <div class="profile-container">
        <h1>Profile</h1>
        <?php if(isset($_SESSION['username'])){ ?>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</p>
        <?php if (isset($success_message)): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="profile-form">
            <div class="form-group">
                <label for="name">Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($profile_data['name']); ?>" required>
            </div>

            <div class="form-group">
                <label for="age">Age:</label>
                <input type="number" id="age" name="age" value="<?php echo htmlspecialchars($profile_data['age']); ?>" min="1" max="120" required>
            </div>

            <div class="form-group">
                <label for="location">Current Location:</label>
                <input type="text" id="location" name="location" value="<?php echo htmlspecialchars($profile_data['location']); ?>" placeholder="City, State" required>
            </div>

            <div class="form-group">
                <button type="submit" class="btn btn-primary">Save Profile</button>
            </div>
        </form>
        <?php }?>
        <p>If you are not logged in, please login to view and edit your profile.</p>
    </div>

</body>
</html>
