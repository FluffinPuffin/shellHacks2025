<?php session_start();?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta content="text/html;charset=utf-8" http-equiv="Content-Type">
    <title> </title>
    <link rel="stylesheet" href="./css/style.css">
</head>

<body>
    <?php include 'navigation.php'?>
    <p>LOCATION<p>
        <table border="1">
            <tr>
                <section class="compare-section">
                    <div class="reports-container">


                        <th>
                            <form name="currentLocationForm" id="currentLocationForm" method="POST" action="">
                                <div class="location-report">
                                    <h3>Current Location</h3>
                                    <div class="report-item">
                                        <strong>Location:</strong>
                                        <input type="text" name="current_location" value="">
                                    </div>
                                    <div class="report-item">
                                        <strong>Household Size:</strong>
                                        <input type="number" name="current_household_size" value="">
                                    </div>
                                    <div class="report-item">
                                        <strong>Bath/Bed:</strong>
                                        <input type="text" name="current_bath_bed" value="">
                                    </div>
                                    <div class="cost-section">
                                        <div class="report-item">
                                            <strong>Rent:</strong>
                                            <input type="number" name="current_rent" value="" step="0.01">
                                        </div>
                                        <div class="report-item">
                                            <strong>Utilities:</strong>
                                            <input type="number" name="current_utilities" value="" step="0.01">
                                        </div>
                                        <div class="report-item">
                                            <strong>Groceries:</strong>
                                            <input type="number" name="current_groceries" value="" step="0.01">
                                        </div>
                                        <div class="report-item total">
                                            <strong>Total:</strong>
                                            <span><strong>TOTAL HERE</strong></span>
                                        </div>
                                    </div>
                                    <input type="submit" name="submit_current" value="Submit">
                                </div>
                            </form>
                        </th>
                        <th>  
                            <form name="destinationForm" id="destinationForm" method="POST" action="">
                                <div class="location-report">
                                <h3>Destination</h3>
                                <div class="report-item">
                                    <strong>Location:</strong>
                                    <input type="text" name="destination_location" value="">
                                </div>
                                <div class="report-item">
                                    <strong>Household Size:</strong>
                                    <input type="number" name="destination_household_size" value="">
                                </div>
                                <div class="report-item">
                                    <strong>Bath/Bed:</strong>
                                    <input type="text" name="destination_bath_bed" value="">
                                </div>
                                <div class="cost-section">
                                    <div class="report-item">
                                        <strong>Rent:</strong>
                                        <input type="number" name="destination_rent" value="" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Utilities:</strong>
                                        <input type="number" name="destination_utilities" value="" step="0.01">
                                    </div>
                                    <div class="report-item">
                                        <strong>Groceries:</strong>
                                        <input type="number" name="destination_groceries" value="" step="0.01">
                                    </div>
                                    <div class="report-item total">
                                        <strong>Total:</strong>
                                        <span><strong>TOTAL HERE</strong></span>
                                    </div>
                                </div>
                                <input type="submit" name="submit_destination" value="Submit">
                            </div>
                            </form>
                        </th>
                        
                    </div>
                </section>
            </tr>
        </table>
</body>
</html>
