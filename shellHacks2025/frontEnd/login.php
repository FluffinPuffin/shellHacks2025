
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Budget App</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>

    <?php include 'navigation.php'?>
    <h1> Login </h1>
    
    <?php if (isset($error)): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <div class="loginpage">
        <form id="loginpage" action="login.php" method="post">
            <div class="UsernamePassword">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Enter your username" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" name="Submit" class="btn btn-primary">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                    <polyline points="10,17 15,12 10,7"></polyline>
                    <line x1="15" y1="12" x2="3" y2="12"></line>
                </svg>
                Sign In
            </button>
        </form>
        <div class="createAccount">
            <p>Don't have an account?</p>
            <a href="createAccount.php">Create Account</a>
        </div>
    </div>
</body>
</html>