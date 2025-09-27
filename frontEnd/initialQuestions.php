<?php
session_start();

if (isset($_POST['submit']) && !empty($_POST['name']) && !empty($_POST['age']) && !empty($_POST['house']) && !empty($_POST['bedroom']) && !empty($_POST['bathroom']) && !empty($_POST['location'])) {
    // Need username from create account
    $_SESSION['name'] = $_POST['name'];
    $_SESSION['age'] = $_POST['age'];
    $_SESSION['house'] = $_POST['house'];
    $_SESSION['bedroom'] = $_POST['bedroom'];
    $_SESSION['bathroom'] = $_POST['bathroom'];
    $_SESSION['location'] = $_POST['location'];
    header("Location: home.php");
} else {
    echo "<script>alert('Please fill in all fields.');</script>";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Tell us more about yourself</title>
    <link rel="stylesheet" href="./css/style.css">

</head>
<body>
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
<script src="./js/initialQuestions.js" ></script>
</html>
