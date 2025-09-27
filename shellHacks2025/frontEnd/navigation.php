<?php // Navigation bar at top ?>
<nav>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <li><a href="budget.php">Budget Builder</a></li>
        <li><a href="location.php">Location Comparison</a></li>
        <li><a href="profile.php">Profile</a></li>
        <?php
        // If logged in show logout else show login
        if (isset($_SESSION['username'])) {
        ?>
            <li><a href="logout.php">Logout</a></li>

        <?php } else { ?>
            <li><a href="login.php">Login</a></li>
        <?php }?>

    </ul>
</nav>