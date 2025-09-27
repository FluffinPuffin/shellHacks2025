<?php
    session_start();
    // if already logged in go home
    if (isset($_SESSION['username'])) {
        header("Location: home.php");
        exit();
    }
    // when form submitted and not empty
    if (isset($_POST['Submit']) && !empty($_POST['username']) && !empty($_POST['password'])) {
        // Check Database for account then send to home page else pop error
        $_SESSION['username'] = $_POST['username'];
        // this is the json string (for login idk)
        $jsonData = json_encode($_POST);
        header("Location: home.php");
    }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> Login Page </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'?>
    <h1> Login </h1>
    <div class = "loginpage">
        <form id="loginpage" action="login.php" method="post">
            <div class="UsernamePassword">
                <div>
                    <label for="username"> Username: </label>
                    <input type="text" id="username" name="username" required>
                </div>
                <div>
                    <label for="password"> Password: </label>
                    <input type="password" id="password" name="password" required>
                </div>
            </div>
                <input type="submit" value="submit" name="Submit">
        </form>
        <div class="createAccount">
            <p> Don't have an account? </p>
            <a href="createAccount.php"> Create Account </a>
        </div>
    </div>
</body>
</html>