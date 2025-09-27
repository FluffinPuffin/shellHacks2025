
<?php
session_start();

// regen button
// save and exit button
// by month and year
$dbPath = 'C:/xampp/htdocs/shell/shellHacks2025/budget_app.db';

// Open the database
try {
    $db = new SQLite3($dbPath);
    echo "Connected to database successfully!";
} catch (Exception $e) {
    echo "Failed to connect: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'?>
    <p>BUDGET PAGE<p>
</body>
</html>