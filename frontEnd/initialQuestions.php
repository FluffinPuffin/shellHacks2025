<?php
session_start();

// If everything inside is not empty and submit button is pressed then encode to json and go home
if (isset($_POST['submit']) && !empty($_POST['name']) && !empty($_POST['age']) && !empty($_POST['house']) && !empty($_POST['bedroom']) && !empty($_POST['bathroom']) && !empty($_POST['location'])) {
    // data from from
    $jsonData = json_encode($_POST);
    header("Location: home.php");
    exit();
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
