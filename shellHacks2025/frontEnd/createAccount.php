<?php
session_start();

// check if form submitted
if (isset($_POST['submit'])) {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username !== '' && $email !== '' && $password !== '') {
        // create session for login
        $_SESSION['username'] = $username;

        // you could save JSON if you want, right now unused
        $jsonData = json_encode($_POST);

        header("Location: initialQuestions.php");
        exit();
    } else {
        $error = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create Account</title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'; ?>

    <div class="container">
        <h1>Create Account</h1>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form id="createAccount" action="createAccount.php" method="POST" class="form-card">
            <label for="username"> Username: </label>
            <input type="text" id="username" name="username" required>

            <label for="email"> Email: </label>
            <input type="email" id="email" name="email" required>

            <label for="password"> Password: </label>
            <input type="password" id="password" name="password" required>

            <div class="budget-actions">
                <input type="submit" value="Create Account" name="submit">
            </div>
        </form>

        <div class="auth-switch">
            <p>Already have an account?</p>
            <a href="login.php" class="btn-link">Login</a>
        </div>
    </div>
</body>
</html>
