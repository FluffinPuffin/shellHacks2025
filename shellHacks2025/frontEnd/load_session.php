<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Check if session ID was provided
if (!isset($_POST['session_id']) || empty($_POST['session_id'])) {
    header("Location: home.php?error=invalid_session");
    exit();
}

$session_id = $_POST['session_id'];

// Get the session from database
$session = $db->getSession($session_id);

if (!$session) {
    header("Location: home.php?error=session_not_found");
    exit();
}

// Set this as the current session
$_SESSION['current_session_id'] = $session_id;

// Redirect to budget page with loaded indicator
header("Location: budget.php?loaded=1");
exit();
?>
