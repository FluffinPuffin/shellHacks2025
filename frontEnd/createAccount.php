<?php
    session_start();
    if (isset($_POST['submit']) && !empty($_POST['username']) && !empty($_POST['email']) && !empty($_POST['password'])) {
        $_SESSION['username'] = $_POST['username'];
        $jsonData = json_encode($_POST);
        header("Location: initialQuestions.php");
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
    <h1>Create Account</h1>
    <form id="createAccount" action="createAccount.php" method="post">
        <label for="username">Username:</label>
        <input type="text" name="username" required>

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="password">Password:</label>
        <input type="password" name="password" required>

        <input type="submit" value="Create Account" name="submit">
    </form>
</body>
</html>