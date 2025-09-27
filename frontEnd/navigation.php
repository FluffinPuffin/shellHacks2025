<?php // Navigation bar at top ?>
<nav>
    <ul style="list-style: none; display: flex; gap: 2em; margin: 0; padding: 0; justify-content: center;">
        <?php
        // If logged in show logout else show login
        if (isset($_SESSION['username'])) {
        ?>
            <li><a href="logout.php" style="color: purple; text-decoration: none;">Logout</a></li>
            <li><a href="profile.php" style="color: purple; text-decoration: none;">Profile</a></li>
        <?php } else { ?>
            <li><a href="login.php" style="color: purple; text-decoration: none;">Login</a></li>
        <?php }?>

        <li><a href="budget.php" style="color: purple; text-decoration: none;">Budget Builder</a></li>
        <li><a href="location.php" style="color: purple; text-decoration: none;">Location Comparison</a></li>

        <li><a href="home.php" style="color: purple; text-decoration: none;">Home</a></li>
    </ul>
</nav>