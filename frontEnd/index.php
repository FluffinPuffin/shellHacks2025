<?php 
session_start();
// Redirect to login if not authenticated, otherwise go to home
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit();
} else {
    header("Location: login.php");
    exit();
}
?>
