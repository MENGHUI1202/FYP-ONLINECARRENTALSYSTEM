<?php
$conn = new mysqli("localhost", "root", "", "car_rental_db");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);
?>
<?php

define('SITE_NAME', 'NO 1 Car Rental'); 

define('CONTACT_PHONE', '+60 12-345 6789');
?>