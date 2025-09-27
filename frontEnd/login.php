<?php
    session_start();
    if (isset($_POST['Submit']) && !empty($_POST['name']) && !empty($_POST['password'])) {
        if ($_POST['name'] == $_SESSION['username'] && $_POST['password'] == $_SESSION['password']) {
            // Check Database for account then send to home page else pop error
            $_SESSION['username'] = $_POST['username'];
            $jsonData = json_encode($_POST);
            header("Location: home.php");
        } else {

        }
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
    </div>
</body>
</html>