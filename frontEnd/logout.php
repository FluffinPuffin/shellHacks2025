<?
session_start();
if(isset($_SESSION['logged_in'])) {
	unset($_SESSION['logged_in']);
}
    header("Location: login.php");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> Logout Page </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <div id = logout>
        <h1> Logout Page </h1>
        <p> Logging you out.... <p>
        <a href="login.php"> Click here if you are not redirected </a>
    </div>
</body>
</html>