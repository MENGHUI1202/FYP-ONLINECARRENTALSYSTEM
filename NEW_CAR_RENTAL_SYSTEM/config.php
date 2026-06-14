<?php
session_start();

date_default_timezone_set("Asia/Kuala_Lumpur");

$host = "localhost";
$username = "root";
$password = "";
$database = "car_rental_system_unified_copy_20260609_001225";

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

define("BASE_URL", "http://localhost/onlinecarrentalsystem/NEW_CAR_RENTAL_SYSTEM/");
define("SITE_NAME", "KH Car Rental");

function clean_input($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}
?>
