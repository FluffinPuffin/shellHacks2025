<?
    session_start();
    if (isset($_POST['submit'])) {
        // Save the data to the session
        $_SESSION['name'] = $_POST['name'];
        $_SESSION['age'] = $_POST['age'];
        $_SESSION['house'] = $_POST['house'];
        $_SESSION['bedroom'] = $_POST['bedroom'];
        $_SESSION['bathroom'] = $_POST['bathroom'];
        $_SESSION['location'] = $_POST['location'];

        // Redirect to a new page
        header("Location: home.php");
    }
    $step = 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> </title>
    <link rel="stylesheet" href="./css/style.css">
    <script src="./js/initialQuestions.js"></script>
</head>

<body>
    <h1> Tell us more about yourself </h1>
    <div class = "initialQuestions">
        <form id="initialQuestions" action="initialQuestions.php" method="post">
        <?php
            if ($step == 0) {
        ?>
        <div class="step-1">
            <label for="name"> Name: </label>
            <input type="text" id="name" name="name" required>

            <label for="age"> Age: </label>
            <input type="number" id="age" name="age" min="18" required>

            <label for="house"> Houshold Size: </label>
            <input type="number" id="house" name="house" min="1" required>
        </div>
        <? } if ($step == 1) {
        ?>
        <div class="step-2">
            <label for="bedroom"> Bedrooms: </label>
            <input type="number" id="bedroom" name="bedroom" min="1" required>

            <label for="bathroom"> Bathrooms: </label>
            <input type="number" id="bathroom" name="bathroom" min="1" required>
        </div>
        <?php } if ($step == 2) {
        ?>
        <div class="step-3">
            <label for="location"> Location: </label>
            <input type="text" id="location" name="location" required>
            <input type="submit" value="Submit" name="submit">
        </div>
        <?php
            }
        ?>
            <input type="button" value="back" name="back">
            <input type="button" value="next" name="Next">
        </form>
    </div>
</body>
</html>