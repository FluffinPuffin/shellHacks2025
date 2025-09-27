<?php
    session_start();
    // is ssubmitted and not empty
    if (isset($_POST['submit']) && !empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password'])) {

        // create session for login and json fro data
        $_SESSION['username'] = $_POST['username'];
        $jsonData = json_encode($_POST);

        header("Location: initialQuestions.php");
        exit();
    } else if (isset($_POST['submit'])) {

    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> Create Account </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'?>
    <h1>Create Account</h1>
    <form id="createAccount" action="createAccount.php" method="POST">
        <label for="username"> Username: </label>
        <input type="text" id="username" name="username" required>

        <label for="email">Email:</label>
        <input type="email" name="email" id = 'email'>

        <label for="password">Password:</label>
        <input type="password" name="password" id = 'password'>

        <input type="submit" value="Create Account" name="submit">
    </form>
    <div class="createAccount">
        <p> Login </p>
        <a href="login.php"> Create Account </a>
    </div>
</body>
</html>