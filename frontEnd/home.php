<?php session_start();
// last 3 budgets
// Example data structure - you can populate this from database or other sources
$destinations = [];
// Example: $destinations = ['Paris', 'Tokyo', 'New York'];
// Or from POST/GET: if(isset($_POST['destinations'])) $destinations = $_POST['destinations'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> Home</title>
    <link rel="stylesheet" href="./css/style.css">


    <!-- This is to handle buttons on the rows, I dont know how they connect to budget just yet
    -->
    <script>
        function selectDestination(destination, index) {
            // Handle the button click - you can customize this
            alert('Selected: ' + destination + ' (Index: ' + index + ')');
            // Or redirect to another page with the destination
            // window.location.href = 'budget.php?destination=' + encodeURIComponent(destination);
        }
    </script>
</head>

<body>
    <?php include 'navigation.php'?>
    <p>HOME PAGE <p>
    <h1>Home</h1>
    
    <section class="table-section">
        <table border="1">
            <tr>
                <th>Action</th>
                <th>Destination</th>
            </tr>
            <?php if (!empty($destinations)): ?>
                <?php foreach ($destinations as $index => $destination): ?>
                    <tr>
                        <td>
                            <button type="button" onclick="selectDestination('<?php echo htmlspecialchars($destination); ?>', <?php echo $index; ?>)">
                                Select
                            </button>
                        </td>
                        <td><?php echo htmlspecialchars($destination); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="2">No destinations available</td>
                </tr>
            <?php endif; ?>
        </table>
    </section>
    
</body>
</html>
