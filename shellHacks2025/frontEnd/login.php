
<?php
    session_start();
    // if already logged in go home
    if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
        header("Location: home.php");
        exit();
    }
    // when form submitted and not empty
    if (isset($_POST['Submit']) && !empty($_POST['username']) && !empty($_POST['password'])) {
        // Simple demo authentication
        if ($_POST['username'] === 'demo' && $_POST['password'] === 'demo') {
            $_SESSION['logged_in'] = true;
            $_SESSION['username'] = $_POST['username'];
            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid username or password";
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

    <?php include 'navigation.php'?>
    <h1> Login </h1>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
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