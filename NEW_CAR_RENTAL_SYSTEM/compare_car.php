<?php
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
ini_set("display_errors", "1");

require_once "config.php";

if(function_exists("mysqli_report")) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($value) {
    return "RM " . number_format((float)$value, 2);
}

function fetchRows($conn, $sql) {
    try {
        $result = $conn->query($sql);
        if(!$result) return [];
        return $result->fetch_all(MYSQLI_ASSOC);
    } catch(Throwable $ex) {
        return [];
    }
}

function fetchOne($conn, $sql) {
    $rows = fetchRows($conn, $sql);
    return $rows[0] ?? null;
}

function tableExists($conn, $table) {
    try {
        $table = $conn->real_escape_string($table);
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        return $result && $result->num_rows > 0;
    } catch(Throwable $ex) {
        return false;
    }
}

function columnExists($conn, $table, $column) {
    try {
        $table = $conn->real_escape_string($table);
        $column = $conn->real_escape_string($column);
        $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        return $result && $result->num_rows > 0;
    } catch(Throwable $ex) {
        return false;
    }
}

function firstColumn($conn, $table, $columns, $fallback = null) {
    foreach($columns as $column) {
        if(columnExists($conn, $table, $column)) return $column;
    }
    return $fallback;
}

function parseNumber($value) {
    if($value === null) return null;
    if(is_numeric($value)) return (float)$value;
    if(preg_match('/([0-9]+(?:\.[0-9]+)?)/', (string)$value, $m)) {
        return (float)$m[1];
    }
    return null;
}

function getNavCartCount($conn) {
    $sessionCount = (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) ? count($_SESSION["cart"]) : 0;

    if(empty($_SESSION["user_id"]) || !tableExists($conn, "cart_items")) {
        return $sessionCount;
    }

    $userId = (int)$_SESSION["user_id"];
    $row = fetchOne($conn, "
        SELECT COUNT(*) AS total
        FROM cart_items
        WHERE user_id = $userId
        AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out')
    ");

    return (int)($row["total"] ?? $sessionCount);
}

function resolveCarImageSrc($imagePath, $carName = "Car Image") {
    $imagePath = trim((string)$imagePath);

    if($imagePath !== "" && preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    if($imagePath !== "") {
        $localPath = __DIR__ . "/" . ltrim($imagePath, "/");
        if(is_file($localPath)) return $imagePath;
    }

    $safeName = htmlspecialchars((string)$carName, ENT_QUOTES, "UTF-8");
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520" viewBox="0 0 900 520">'
        . '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#eaf7ff"/><stop offset="1" stop-color="#ffffff"/></linearGradient></defs>'
        . '<rect width="900" height="520" fill="url(#g)"/>'
        . '<rect x="60" y="62" width="780" height="396" rx="36" fill="#fff" opacity=".78" stroke="#b8e4ff"/>'
        . '<path d="M198 318h475c32 0 58-24 58-54v-20c0-14-10-27-24-31l-96-28c-31-45-68-68-112-68H356c-54 0-94 24-128 70l-70 24c-17 6-29 22-29 40v13c0 30 26 54 69 54Z" fill="#1fa2df" opacity=".94"/>'
        . '<circle cx="271" cy="323" r="45" fill="#10233d"/><circle cx="271" cy="323" r="20" fill="#d8f2ff"/>'
        . '<circle cx="624" cy="323" r="45" fill="#10233d"/><circle cx="624" cy="323" r="20" fill="#d8f2ff"/>'
        . '<text x="450" y="92" text-anchor="middle" font-family="Segoe UI, Arial" font-size="27" font-weight="800" fill="#10233d">' . $safeName . '</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function bestFor($car) {
    $category = strtolower((string)($car["category_name"] ?? ""));
    $brand = strtolower((string)($car["brand"] ?? ""));
    $seats = (int)parseNumber($car["seats"]);

    if($seats >= 7 || strpos($category, "mpv") !== false) return "Family / Group";
    if(strpos($category, "suv") !== false || strpos($category, "pickup") !== false) return "Outdoor / Luggage";
    if(strpos($brand, "bmw") !== false || strpos($brand, "mercedes") !== false || strpos($brand, "lexus") !== false) return "Business / Premium";
    if(strpos($category, "sedan") !== false || strpos($category, "hatch") !== false) return "City Driving";
    return "Daily Rental";
}

$user = null;
if(!empty($_SESSION["user_id"]) && tableExists($conn, "users")) {
    $userIdCol = firstColumn($conn, "users", ["user_id", "id"], "user_id");
    $userId = (int)$_SESSION["user_id"];
    $user = fetchOne($conn, "SELECT * FROM users WHERE `$userIdCol` = $userId LIMIT 1");
}

$navCartCount = getNavCartCount($conn);

$selectedIds = [];
for($i = 1; $i <= 3; $i++) {
    $key = "car" . $i;
    if(isset($_GET[$key]) && (int)$_GET[$key] > 0 && !in_array((int)$_GET[$key], $selectedIds)) {
        $selectedIds[] = (int)$_GET[$key];
    }
}

if(isset($_GET["car_id"]) && (int)$_GET["car_id"] > 0 && !in_array((int)$_GET["car_id"], $selectedIds)) {
    $selectedIds[] = (int)$_GET["car_id"];
}

$selectedIds = array_slice($selectedIds, 0, 3);
$compareClicked = isset($_GET["compare_now"]) && $_GET["compare_now"] === "1";

$brands = [];
if(tableExists($conn, "brands")) {
    $brandIdCol = firstColumn($conn, "brands", ["brand_id", "id"], "brand_id");
    $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
    $brands = fetchRows($conn, "SELECT `$brandIdCol` AS brand_id, `$brandNameCol` AS brand_name FROM brands ORDER BY `$brandNameCol` ASC");
}

$categories = [];
if(tableExists($conn, "categories")) {
    $catIdCol = firstColumn($conn, "categories", ["category_id", "id"], "category_id");
    $catNameCol = firstColumn($conn, "categories", ["category_name", "name"], "category_name");
    $categories = fetchRows($conn, "SELECT `$catIdCol` AS category_id, `$catNameCol` AS category_name FROM categories ORDER BY `$catNameCol` ASC");
} elseif(tableExists($conn, "vehicle_categories")) {
    $catIdCol = firstColumn($conn, "vehicle_categories", ["category_id", "id"], "category_id");
    $catNameCol = firstColumn($conn, "vehicle_categories", ["category_name", "name"], "category_name");
    $categories = fetchRows($conn, "SELECT `$catIdCol` AS category_id, `$catNameCol` AS category_name FROM vehicle_categories ORDER BY `$catNameCol` ASC");
}

$cars = [];
$selectedCars = [];

if(tableExists($conn, "cars")) {
    $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
    $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
    $modelCol = firstColumn($conn, "cars", ["model"], null);
    $yearCol = firstColumn($conn, "cars", ["year", "car_year"], null);
    $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
    $imageCol = firstColumn($conn, "cars", ["image", "main_image", "car_image", "image_url"], null);
    $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
    $brandTextCol = firstColumn($conn, "cars", ["brand", "brand_name"], null);
    $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
    $categoryTextCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
    $seatsCol = firstColumn($conn, "cars", ["seats"], null);
    $doorsCol = firstColumn($conn, "cars", ["doors"], null);
    $transmissionCol = firstColumn($conn, "cars", ["transmission"], null);
    $drivetrainCol = firstColumn($conn, "cars", ["drivetrain"], null);
    $engineCol = firstColumn($conn, "cars", ["engine", "engine_capacity"], null);
    $horsepowerCol = firstColumn($conn, "cars", ["horsepower", "hp"], null);
    $torqueCol = firstColumn($conn, "cars", ["torque"], null);
    $fuelCol = firstColumn($conn, "cars", ["fuel_type"], null);
    $fuelConsumptionCol = firstColumn($conn, "cars", ["fuel_consumption"], null);
    $luggageCol = firstColumn($conn, "cars", ["luggage_capacity"], null);
    $colorCol = firstColumn($conn, "cars", ["color"], null);
    $zeroCol = firstColumn($conn, "cars", ["zero_to_hundred", "zero_to_100", "acceleration"], null);
    $frontBrakeCol = firstColumn($conn, "cars", ["front_brake"], null);
    $rearBrakeCol = firstColumn($conn, "cars", ["rear_brake"], null);
    $suspensionCol = firstColumn($conn, "cars", ["suspension"], null);
    $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);

    $select = [
        "c.`$carIdCol` AS car_id",
        "c.`$carNameCol` AS car_name",
        ($modelCol ? "c.`$modelCol`" : "''") . " AS model",
        ($yearCol ? "c.`$yearCol`" : "''") . " AS car_year",
        ($priceCol ? "c.`$priceCol`" : "0") . " AS price_per_day",
        ($imageCol ? "c.`$imageCol`" : "''") . " AS image",
        ($brandIdCol ? "c.`$brandIdCol`" : "0") . " AS brand_id",
        ($categoryIdCol ? "c.`$categoryIdCol`" : "0") . " AS category_id",
        ($seatsCol ? "c.`$seatsCol`" : "'-'") . " AS seats",
        ($doorsCol ? "c.`$doorsCol`" : "'4'") . " AS doors",
        ($transmissionCol ? "c.`$transmissionCol`" : "'Automatic'") . " AS transmission",
        ($drivetrainCol ? "c.`$drivetrainCol`" : "'-'") . " AS drivetrain",
        ($engineCol ? "c.`$engineCol`" : "'-'") . " AS engine",
        ($horsepowerCol ? "c.`$horsepowerCol`" : "'0'") . " AS horsepower",
        ($torqueCol ? "c.`$torqueCol`" : "'-'") . " AS torque",
        ($fuelCol ? "c.`$fuelCol`" : "'Petrol'") . " AS fuel_type",
        ($fuelConsumptionCol ? "c.`$fuelConsumptionCol`" : "'-'") . " AS fuel_consumption",
        ($luggageCol ? "c.`$luggageCol`" : "'-'") . " AS luggage_capacity",
        ($colorCol ? "c.`$colorCol`" : "'-'") . " AS color",
        ($zeroCol ? "c.`$zeroCol`" : "'-'") . " AS zero_to_hundred",
        ($frontBrakeCol ? "c.`$frontBrakeCol`" : "'-'") . " AS front_brake",
        ($rearBrakeCol ? "c.`$rearBrakeCol`" : "'-'") . " AS rear_brake",
        ($suspensionCol ? "c.`$suspensionCol`" : "'-'") . " AS suspension"
    ];

    $join = "";

    if($brandIdCol && tableExists($conn, "brands")) {
        $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "brand_id");
        $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
        $select[] = "COALESCE(b.`$brandNameCol`, '-') AS brand";
        $join .= " LEFT JOIN brands b ON b.`$brandPk` = c.`$brandIdCol` ";
    } elseif($brandTextCol) {
        $select[] = "c.`$brandTextCol` AS brand";
    } else {
        $select[] = "'-' AS brand";
    }

    if($categoryIdCol && tableExists($conn, "categories")) {
        $categoryPk = firstColumn($conn, "categories", ["category_id", "id"], "category_id");
        $categoryNameCol = firstColumn($conn, "categories", ["category_name", "name"], "category_name");
        $select[] = "COALESCE(cat.`$categoryNameCol`, 'Others') AS category_name";
        $join .= " LEFT JOIN categories cat ON cat.`$categoryPk` = c.`$categoryIdCol` ";
    } elseif($categoryIdCol && tableExists($conn, "vehicle_categories")) {
        $categoryPk = firstColumn($conn, "vehicle_categories", ["category_id", "id"], "category_id");
        $categoryNameCol = firstColumn($conn, "vehicle_categories", ["category_name", "name"], "category_name");
        $select[] = "COALESCE(cat.`$categoryNameCol`, 'Others') AS category_name";
        $join .= " LEFT JOIN vehicle_categories cat ON cat.`$categoryPk` = c.`$categoryIdCol` ";
    } elseif($categoryTextCol) {
        $select[] = "c.`$categoryTextCol` AS category_name";
    } else {
        $select[] = "'Others' AS category_name";
    }

    $where = "1=1";
    if($statusCol) {
        $where .= " AND (LOWER(COALESCE(c.`$statusCol`, 'active')) IN ('active','available') OR c.`$statusCol` = 1)";
    }

    $cars = fetchRows($conn, "
        SELECT " . implode(", ", $select) . "
        FROM cars c
        $join
        WHERE $where
        ORDER BY brand ASC, category_name ASC, car_name ASC
    ");

    if($compareClicked && $selectedIds) {
        $idList = implode(",", array_map("intval", $selectedIds));
        $selectedCars = fetchRows($conn, "
            SELECT " . implode(", ", $select) . "
            FROM cars c
            $join
            WHERE $where AND c.`$carIdCol` IN ($idList)
            ORDER BY FIELD(c.`$carIdCol`, $idList)
        ");
    }
}

$lowestPrice = null;
$highestSeats = null;
$highestPower = null;
$bestFuel = null;

foreach($selectedCars as $car) {
    $price = (float)$car["price_per_day"];
    $seats = parseNumber($car["seats"]);
    $power = parseNumber($car["horsepower"]);
    $fuel = parseNumber($car["fuel_consumption"]);

    if($lowestPrice === null || $price < $lowestPrice) $lowestPrice = $price;
    if($seats !== null && ($highestSeats === null || $seats > $highestSeats)) $highestSeats = $seats;
    if($power !== null && ($highestPower === null || $power > $highestPower)) $highestPower = $power;
    if($fuel !== null && $fuel > 0 && ($bestFuel === null || $fuel < $bestFuel)) $bestFuel = $fuel;
}

$compareRows = [
    "Basic Information" => [
        ["Car Name", "car_name"],
        ["Brand", "brand"],
        ["Model", "model"],
        ["Year", "car_year"],
        ["Category", "category_name"],
        ["Best For", "best_for"]
    ],
    "Rental Price" => [
        ["Price Per Day", "price_per_day"]
    ],
    "Performance" => [
        ["Engine", "engine"],
        ["Horsepower", "horsepower"],
        ["Torque", "torque"],
        ["Transmission", "transmission"],
        ["Drivetrain", "drivetrain"],
        ["Fuel Type", "fuel_type"],
        ["Fuel Consumption", "fuel_consumption"],
        ["0-100 km/h", "zero_to_hundred"]
    ],
    "Practicality" => [
        ["Seats", "seats"],
        ["Doors", "doors"],
        ["Luggage Capacity", "luggage_capacity"],
        ["Color", "color"]
    ],
    "Brake & Suspension" => [
        ["Front Brake", "front_brake"],
        ["Rear Brake", "rear_brake"],
        ["Suspension", "suspension"]
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Compare Cars | KH Car Rental</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root {
    --sky50:#f5fbff;
    --sky100:#eaf7ff;
    --sky200:#d8f2ff;
    --sky500:#28a8ea;
    --sky600:#1284c6;
    --dark:#10233d;
    --muted:#6e8297;
    --orange:#ff7a1a;
    --green:#16a765;
    --red:#ef4444;
    --border:#d8ecfb;
    --shadow:0 24px 70px rgba(39,137,199,.13);
    --soft:0 12px 35px rgba(39,137,199,.10);
}
*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body {
    font-family:"Segoe UI",Tahoma,sans-serif;
    color:var(--dark);
    background:
        radial-gradient(circle at 8% 0%,rgba(210,239,255,.42),transparent 30%),
        radial-gradient(circle at 95% 8%,rgba(234,247,255,.45),transparent 34%),
        linear-gradient(180deg,#ffffff 0%,#f8fcff 48%,#ffffff 100%);
}
a{text-decoration:none;color:inherit}
button,input,select{font-family:inherit}

/* ===== Navbar ===== */
.navbar{
    position:sticky;
    top:0;
    z-index:100;
    height:64px;
    background:linear-gradient(135deg,rgba(224,247,255,.94),rgba(255,255,255,.96),rgba(240,250,255,.94));
    border-bottom:1px solid rgba(142,207,244,.42);
    backdrop-filter:blur(18px);
}
.nav-inner{
    width:min(1200px,calc(100% - 40px));
    height:64px;
    margin:auto;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
}
.menu-btn{display:none}
.brand{
    display:flex;
    align-items:center;
    gap:13px;
    font-size:15px;
    font-weight:950;
    white-space:nowrap;
    margin-right:28px;
}
.brand-icon{
    width:42px;
    height:42px;
    display:grid;
    place-items:center;
    border-radius:15px;
    color:var(--sky600);
    background:linear-gradient(135deg,#d8f2ff,#fff);
    border:1px solid rgba(142,207,244,.46);
    box-shadow:0 14px 28px rgba(40,168,234,.13);
}
.nav-links{
    flex:1;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:18px;
    list-style:none;
}
.nav-links a{
    display:inline-flex;
    align-items:center;
    gap:6px;
    padding:8px 7px;
    border-radius:999px;
    font-size:12px;
    font-weight:950;
    color:#2b4969;
    letter-spacing:.2px;
}
.nav-links a i{color:#2b4969;font-size:13px}
.nav-links a.active,
.nav-links a.active i,
.nav-links a:hover,
.nav-links a:hover i{color:var(--sky600)}
.avatar-wrap{position:relative;margin-left:0}
.avatar-btn{
    border:0;
    background:transparent;
    display:flex;
    align-items:center;
    gap:10px;
    cursor:pointer;
    font-weight:950;
    color:var(--dark);
}
.avatar-circle{
    width:40px;
    height:40px;
    border-radius:50%;
    display:grid;
    place-items:center;
    overflow:hidden;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),#0d3f82);
    border:3px solid #fff;
    box-shadow:0 14px 28px rgba(40,168,234,.18);
}
.avatar-circle img{width:100%;height:100%;object-fit:cover}
.dropdown{
    position:absolute;
    right:0;
    top:62px;
    width:260px;
    display:none;
    padding:12px;
    border-radius:24px;
    background:rgba(255,255,255,.96);
    border:1px solid var(--border);
    box-shadow:0 24px 70px rgba(39,137,199,.18);
}
.dropdown.show{display:block}
.dropdown a{
    min-height:54px;
    display:flex;
    align-items:center;
    gap:12px;
    padding:14px 16px;
    border-radius:17px;
    font-weight:900;
    color:#24415f;
}
.dropdown a:hover{background:var(--sky100);color:var(--sky600)}
.login-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:14px 20px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    font-weight:950;
}



/* ===== Footer ===== */
.footer{
    background:#12304f;
    color:#ffffff;
    padding:82px 34px 28px;
}
.footer-inner{
    width:min(1200px,calc(100% - 40px));
    margin:0 auto 42px;
    display:grid;
    grid-template-columns:1.35fr 1fr 1.2fr;
    gap:62px;
}
.footer h3{
    font-size:22px;
    font-weight:950;
    color:#ffffff;
    margin-bottom:18px;
    letter-spacing:-.3px;
}
.footer p,
.footer a{
    color:rgba(218,235,248,.76);
    font-size:15.5px;
    line-height:1.85;
    font-weight:650;
}
.footer-hover-link,
.footer .contact-link{
    width:fit-content;
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:3px 0;
    border-radius:12px;
    transition:color .22s ease,transform .22s ease,background .22s ease,padding .22s ease;
}
.footer-hover-link i{display:none}
.footer .contact-link i{
    width:18px;
    color:var(--sky500);
    transition:.22s ease;
}
.footer-hover-link:hover,
.footer .contact-link:hover{
    color:#ffffff;
    transform:translateX(6px);
    background:rgba(255,255,255,.055);
    padding-left:8px;
    padding-right:10px;
}
.footer .contact-link:hover i{
    color:#7fd0ff;
    transform:scale(1.08);
}
.footer .start-btn{
    margin-top:22px;
    width:fit-content;
    min-width:176px;
    min-height:52px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:0 24px;
    border-radius:17px;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    color:#ffffff;
    font-size:15.5px;
    font-weight:950;
    line-height:1;
    letter-spacing:.15px;
    box-shadow:0 18px 36px rgba(40,168,234,.25);
    border:1px solid rgba(113,210,255,.18);
}
.footer .start-btn i{color:#fff}
.footer-bottom{
    width:min(1200px,calc(100% - 40px));
    margin:0 auto;
    padding-top:22px;
    border-top:1px solid rgba(255,255,255,.14);
    text-align:center;
    color:rgba(218,235,248,.86);
    font-size:14px;
}
.back-top{
    position:fixed;
    right:28px;
    bottom:28px;
    width:54px;
    height:54px;
    border-radius:50%;
    border:0;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    box-shadow:0 20px 40px rgba(40,168,234,.3);
    cursor:pointer;
}




.nav-cart-link{position:relative;overflow:visible!important}
.cart-count-badge{
    position:absolute;
    top:-3px;
    right:-10px;
    min-width:17px;
    height:17px;
    padding:0 5px;
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    background:linear-gradient(135deg,#ff5a52,#e11d2e);
    color:#fff;
    border:2px solid #fff;
    box-shadow:0 8px 18px rgba(225,29,46,.28);
    font-size:9px;
    font-weight:950;
    line-height:1;
    z-index:5;
}

.page {
    width:min(1320px,100%);
    margin:22px auto 56px;
    padding:0 22px;
}

.hero {
    position:relative;
    min-height:205px;
    padding:34px 38px;
    border-radius:34px;
    overflow:hidden;
    background:
        radial-gradient(circle at 86% 18%,rgba(40,168,234,.22),transparent 32%),
        radial-gradient(circle at 100% 100%,rgba(16,35,61,.14),transparent 34%),
        linear-gradient(135deg,#ffffff 0%,#eef9ff 100%);
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 30px 90px rgba(18,132,198,.14);
    margin-bottom:18px;
}
.hero::after {
    content:"";
    position:absolute;
    right:-70px;
    bottom:-120px;
    width:340px;
    height:340px;
    border-radius:50%;
    border:38px solid rgba(40,168,234,.11);
    box-shadow:inset 0 0 0 36px rgba(16,35,61,.06);
}
.pill {
    display:inline-flex;
    align-items:center;
    gap:8px;
    width:fit-content;
    padding:7px 12px;
    border-radius:999px;
    background:rgba(40,168,234,.12);
    color:var(--sky600);
    border:1px solid rgba(40,168,234,.22);
    font-size:11px;
    font-weight:950;
    letter-spacing:.8px;
    text-transform:uppercase;
    margin-bottom:12px;
}
.hero h1 {
    position:relative;
    z-index:2;
    max-width:780px;
    font-size:clamp(40px,4.7vw,66px);
    line-height:.95;
    letter-spacing:-2.5px;
    font-weight:950;
    margin-bottom:14px;
}
.hero p {
    position:relative;
    z-index:2;
    max-width:780px;
    color:var(--muted);
    font-size:15px;
    line-height:1.6;
    font-weight:750;
}

.selector-panel {
    padding:22px;
    border-radius:30px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    margin-bottom:18px;
}
.panel-head {
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-end;
    margin-bottom:18px;
}
.panel-head h2 {
    font-size:30px;
    font-weight:950;
    letter-spacing:-.8px;
}
.panel-head p {
    color:var(--muted);
    font-size:13.5px;
    font-weight:750;
    line-height:1.55;
}
.count-badge {
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:9px 12px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    font-size:12px;
    font-weight:950;
    white-space:nowrap;
}
.selector-grid {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
}
.select-card {
    border-radius:24px;
    background:linear-gradient(135deg,#ffffff,#f7fcff);
    border:1px solid var(--border);
    box-shadow:var(--soft);
    overflow:hidden;
}
.select-card-head {
    min-height:60px;
    padding:15px;
    display:flex;
    align-items:center;
    gap:12px;
    background:linear-gradient(135deg,#10233d,#153e64);
    color:#fff;
}
.slot-icon {
    width:38px;
    height:38px;
    border-radius:14px;
    display:grid;
    place-items:center;
    background:rgba(255,255,255,.12);
}
.select-card-head strong {
    display:block;
    font-size:15px;
    font-weight:950;
}
.select-card-head span {
    display:block;
    font-size:11px;
    color:rgba(255,255,255,.72);
    font-weight:750;
}
.select-card-body {
    padding:14px;
}
.filter-row {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:9px;
}
.select-input {
    width:100%;
    height:42px;
    border:2px solid #e2f2ff;
    background:rgba(255,255,255,.94);
    color:var(--dark);
    border-radius:14px;
    padding:8px 10px;
    outline:none;
    font-size:12.5px;
    font-weight:850;
}
.select-input:focus {
    border-color:var(--sky500);
    box-shadow:0 0 0 .2rem rgba(40,168,234,.13);
}
.action-row {
    display:flex;
    gap:8px;
    margin-top:11px;
}
.btn {
    min-height:38px;
    border:0;
    border-radius:14px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:0 12px;
    cursor:pointer;
    font-size:12px;
    font-weight:950;
    transition:.22s;
}
.btn:hover {
    transform:translateY(-2px);
    box-shadow:var(--soft);
}
.btn-blue {
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
}
.btn-white {
    color:var(--sky600);
    background:#fff;
    border:1px solid var(--border);
}
.btn-clear {
    color:#e65100;
    background:#fff7ed;
    border:1px solid rgba(255,122,26,.24);
}

.compare-card-grid {
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
    margin-bottom:18px;
}
.car-card {
    min-height:100%;
    border-radius:28px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    overflow:hidden;
}
.car-image {
    height:185px;
    background:var(--sky100);
    overflow:hidden;
    position:relative;
}
.car-image img {
    width:100%;
    height:100%;
    object-fit:cover;
}
.price-tag {
    position:absolute;
    right:14px;
    bottom:14px;
    padding:8px 11px;
    border-radius:14px;
    color:#fff;
    background:linear-gradient(135deg,#ff9a4a,#ff7a1a);
    box-shadow:0 12px 28px rgba(255,122,26,.22);
    font-size:12px;
    font-weight:950;
}
.car-body {
    padding:16px;
}
.car-body h3 {
    font-size:22px;
    line-height:1.12;
    font-weight:950;
    margin-bottom:7px;
}
.car-sub {
    color:var(--muted);
    font-size:13px;
    font-weight:800;
    margin-bottom:13px;
}
.quick-specs {
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
}
.quick-spec {
    padding:10px;
    border-radius:14px;
    background:var(--sky50);
    border:1px solid var(--border);
}
.quick-spec span {
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    text-transform:uppercase;
    margin-bottom:4px;
}
.quick-spec strong {
    font-size:12.5px;
}
.compare-table-wrap {
    overflow:auto;
    border-radius:30px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
    margin-bottom:18px;
}
.compare-table {
    width:100%;
    min-width:980px;
    border-collapse:separate;
    border-spacing:0;
}
.compare-table th,
.compare-table td {
    padding:14px 16px;
    border-bottom:1px solid var(--border);
    vertical-align:middle;
    text-align:left;
}
.compare-table thead th {
    position:sticky;
    top:0;
    z-index:3;
    background:linear-gradient(135deg,#10233d,#153e64);
    color:#fff;
    font-size:13px;
    font-weight:950;
}
.compare-table thead th:first-child {
    border-top-left-radius:30px;
}
.compare-table thead th:last-child {
    border-top-right-radius:30px;
}
.compare-table thead img {
    width:78px;
    height:54px;
    border-radius:13px;
    object-fit:cover;
    border:1px solid rgba(255,255,255,.25);
}
.head-car {
    display:flex;
    align-items:center;
    gap:11px;
}
.head-car strong {
    display:block;
    color:#fff;
    font-size:13.5px;
    margin-bottom:3px;
}
.head-car span {
    display:block;
    color:rgba(255,255,255,.75);
    font-size:11px;
    font-weight:750;
}
.compare-item {
    width:190px;
    color:#31506f;
    font-size:12px;
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.5px;
    background:#f7fcff;
}
.group-row th {
    background:var(--sky100)!important;
    color:var(--sky600)!important;
    text-transform:uppercase;
    font-size:12px!important;
    letter-spacing:.7px;
}
.value-text {
    display:block;
    font-size:13px;
    font-weight:900;
    color:var(--dark);
}
.mini-badge {
    display:inline-flex;
    align-items:center;
    width:fit-content;
    margin-top:6px;
    padding:5px 8px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,var(--orange),#f15f12);
    font-size:10px;
    font-weight:950;
}
.back-top {
    position:fixed;
    right:28px;
    bottom:28px;
    width:54px;
    height:54px;
    border-radius:50%;
    border:0;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    box-shadow:0 20px 40px rgba(40,168,234,.3);
    cursor:pointer;
}


.compare-card-grid.is-empty{
    display:none;
}
.view-detail-btn{
    margin-top:14px;
    min-height:42px;
    border-radius:15px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    font-size:12.5px;
    font-weight:950;
    box-shadow:0 14px 28px rgba(40,168,234,.18);
    transition:.22s;
}
.view-detail-btn:hover{
    transform:translateY(-2px);
    box-shadow:0 18px 34px rgba(40,168,234,.25);
}
.slot-icon{
    color:#fff;
}
.slot-icon i{
    font-size:16px;
}


/* ===== Compare Fixes ===== */
.panel-actions{
    display:flex;
    align-items:center;
    justify-content:flex-end;
    gap:10px;
    flex-wrap:wrap;
}
.clear-all-btn{
    height:38px;
    padding:0 14px;
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    color:#e65100;
    background:#fff7ed;
    border:1px solid rgba(255,122,26,.24);
    font-size:12px;
    font-weight:950;
    white-space:nowrap;
    transition:.22s ease;
}
.clear-all-btn:hover{
    transform:translateY(-2px);
    box-shadow:var(--soft);
}
.slot-icon{
    width:34px!important;
    height:34px!important;
    min-width:34px!important;
    border-radius:12px!important;
    display:grid!important;
    place-items:center!important;
    background:rgba(255,255,255,.14)!important;
    color:#dff4ff!important;
    flex:0 0 34px!important;
}
.slot-icon i{
    font-size:14px!important;
    line-height:1!important;
}
.nav-inner{
    width:min(1500px,calc(100% - 40px))!important;
}
.brand{
    min-width:205px!important;
    margin-right:10px!important;
}
.nav-links{
    flex-wrap:nowrap!important;
    gap:16px!important;
}
.nav-links a{
    white-space:nowrap!important;
    word-break:normal!important;
    line-height:1!important;
    font-size:12px!important;
}
.avatar-wrap{
    min-width:205px!important;
    display:flex!important;
    justify-content:flex-end!important;
}
.login-btn{
    white-space:nowrap!important;
}


/* ===== Sequential Slot Lock ===== */
.select-card.slot-locked{
    opacity:.58;
    filter:grayscale(.35);
    background:linear-gradient(135deg,#f4f8fb,#ffffff)!important;
}
.select-card.slot-locked .select-card-head{
    background:linear-gradient(135deg,#7a8da3,#91a4b8)!important;
}
.select-card.slot-locked .select-input,
.select-card.slot-locked .btn{
    cursor:not-allowed!important;
}
.select-card.slot-locked .btn{
    opacity:.75;
}
.slot-lock-note{
    margin-top:10px;
    padding:10px 12px;
    border-radius:14px;
    background:#f3f8fc;
    border:1px solid #dbeaf5;
    color:#63798f;
    display:flex;
    align-items:center;
    gap:8px;
    font-size:12px;
    font-weight:900;
}
.slot-lock-note i{
    color:#8298ad;
}

@media(max-width:1180px) {
    .nav-links{display:none!important}
    .selector-grid,
    .compare-card-grid{grid-template-columns:1fr}
}
@media(max-width:760px) {
    .page{padding:0 14px}
    .hero{padding:28px 22px}
    .hero h1{font-size:40px}
    .panel-head{display:grid}
    .filter-row{grid-template-columns:1fr}
}
</style>
</head>
<body>
<header class="navbar">
    <div class="nav-inner">
        <a href="homepage.php" class="brand">
            <span class="brand-icon"><i class="fa-solid fa-car-side"></i></span>
            <span>KH Car Rental</span>
        </a>

        <ul class="nav-links">
            <li><a href="homepage.php"><i class="fa-solid fa-house"></i> HOME</a></li>
            <li><a href="catalogue.php"><i class="fa-solid fa-car"></i> CATALOGUE</a></li>
            <li><a href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> FIND CAR SMART</a></li>
            <li><a href="compare_car.php" class="active"><i class="fa-solid fa-code-compare"></i> COMPARE CAR</a></li>
            <li><a href="aboutus.php"><i class="fa-solid fa-circle-info"></i> ABOUT US</a></li>
            <li><a href="contactus.php"><i class="fa-solid fa-envelope"></i> CONTACT US</a></li>
            <li><a href="cart.php" class="nav-cart-link"><i class="fa-solid fa-cart-shopping"></i> CART<?php if($navCartCount > 0): ?><span class="cart-count-badge"><?= e($navCartCount > 99 ? "99+" : $navCartCount) ?></span><?php endif; ?></a></li>
        </ul>

        <div class="avatar-wrap">
            <?php if($user): ?>
                <button class="avatar-btn" type="button" id="avatarBtn">
                    <span class="avatar-circle">
                        <?php if(!empty($user["profile_picture"])): ?>
                            <img src="<?= e($user["profile_picture"]) ?>" alt="Profile">
                        <?php else: ?>
                            <?= e(strtoupper(substr($user["name"] ?? "U",0,1))) ?>
                        <?php endif; ?>
                    </span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="dropdown" id="profileDropdown">
                        <a href="my_profile.php"><i class="fa-solid fa-user"></i> Manage My Profile</a>
                        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                    </div>
            <?php else: ?>
                <a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> Login / Register</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="page">
    <section class="hero">
        <span class="pill"><i class="fa-solid fa-code-compare"></i> Compare Car</span>
        <h1>Compare Cars</h1>
        <p>Select up to 3 cars, filter by brand and category, then compare the full specifications side by side.</p>
    </section>

    <section class="selector-panel">
        <div class="panel-head">
            <div>
                <h2>Select Cars to Compare</h2>
                <p>This page is only for comparing cars. Choose 1 to 3 vehicles and the specifications will update instantly.</p>
            </div>
            <div class="panel-actions">
                <a class="clear-all-btn" href="compare_car.php"><i class="fa-solid fa-broom"></i> Clear All</a>
                <span class="count-badge"><i class="fa-solid fa-car"></i> Max 3 cars</span>
            </div>
        </div>

        <form method="GET" action="compare_car.php" id="compareForm">
            <input type="hidden" name="compare_now" value="1">
            <div class="selector-grid">
                <?php for($slot = 1; $slot <= 3; $slot++): ?>
                    <?php $selectedForSlot = $selectedIds[$slot - 1] ?? 0; ?>
                    <?php
                        $slotLocked = false;
                        if($slot == 2 && empty($selectedIds[0])) $slotLocked = true;
                        if($slot == 3 && (empty($selectedIds[0]) || empty($selectedIds[1]))) $slotLocked = true;
                    ?>
                    <div class="select-card <?= $slotLocked ? 'slot-locked' : '' ?>">
                        <div class="select-card-head">
                            <span class="slot-icon"><i class="fa-solid fa-car-side"></i></span>
                            <div>
                                <strong>Car Slot <?= e($slot) ?></strong>
                                <span>Filter then select car</span>
                            </div>
                        </div>
                        <div class="select-card-body">
                            <div class="filter-row">
                                <select class="select-input brand-filter" data-slot="<?= e($slot) ?>" <?= $slotLocked ? "disabled" : "" ?>>
                                    <option value="">All Brands</option>
                                    <?php foreach($brands as $brand): ?>
                                        <option value="<?= e($brand["brand_id"]) ?>"><?= e($brand["brand_name"]) ?></option>
                                    <?php endforeach; ?>
                                </select>

                                <select class="select-input category-filter" data-slot="<?= e($slot) ?>" <?= $slotLocked ? "disabled" : "" ?>>
                                    <option value="">All Categories</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?= e($category["category_id"]) ?>"><?= e($category["category_name"]) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <select class="select-input car-select" name="car<?= e($slot) ?>" data-slot="<?= e($slot) ?>" <?= $slotLocked ? "disabled" : "" ?>>
                                <option value="">Choose car</option>
                                <?php foreach($cars as $car): ?>
                                    <option
                                        value="<?= e($car["car_id"]) ?>"
                                        data-brand="<?= e($car["brand_id"]) ?>"
                                        data-category="<?= e($car["category_id"]) ?>"
                                        <?= (int)$selectedForSlot === (int)$car["car_id"] ? "selected" : "" ?>
                                    >
                                        <?= e($car["car_name"]) ?> • <?= e($car["brand"]) ?> • <?= e($car["category_name"]) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <?php if($slotLocked): ?>
                            <div class="slot-lock-note">
                                <i class="fa-solid fa-lock"></i>
                                <?php if($slot == 2): ?>
                                    Select Car Slot 1 first.
                                <?php else: ?>
                                    Select Car Slot 1 and 2 first.
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>

                            <div class="action-row">
                                <button type="submit" class="btn btn-blue" <?= $slotLocked ? "disabled" : "" ?>><i class="fa-solid fa-check"></i> Compare</button>
                                <button type="button" class="btn btn-clear clear-slot" data-slot="<?= e($slot) ?>" <?= $slotLocked ? "disabled" : "" ?>><i class="fa-solid fa-xmark"></i> Clear</button>
                            </div>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </form>
    </section>

    <section class="compare-card-grid <?= ($compareClicked && $selectedCars) ? 'has-selected' : 'is-empty' ?>">
        <?php for($i = 0; $i < 3; $i++): ?>
            <?php if(isset($selectedCars[$i])): ?>
                <?php $car = $selectedCars[$i]; ?>
                <article class="car-card">
                    <div class="car-image">
                        <img src="<?= e(resolveCarImageSrc($car["image"] ?? "", $car["car_name"] ?? "Car")) ?>" alt="<?= e($car["car_name"]) ?>">
                        <span class="price-tag"><?= e(money($car["price_per_day"])) ?> / day</span>
                    </div>
                    <div class="car-body">
                        <h3><?= e($car["car_name"]) ?></h3>
                        <div class="car-sub"><?= e($car["brand"]) ?> • <?= e($car["category_name"]) ?></div>
                        <div class="quick-specs">
                            <div class="quick-spec"><span>Engine</span><strong><?= e($car["engine"]) ?></strong></div>
                            <div class="quick-spec"><span>Horsepower</span><strong><?= e($car["horsepower"]) ?> hp</strong></div>
                            <div class="quick-spec"><span>Seats</span><strong><?= e($car["seats"]) ?> seats</strong></div>
                            <div class="quick-spec"><span>Transmission</span><strong><?= e($car["transmission"]) ?></strong></div>
                        </div>
                        <a class="view-detail-btn" href="car_details.php?car_id=<?= e($car["car_id"]) ?>">
                            <i class="fa-solid fa-circle-info"></i> View Details
                        </a>
                    </div>
                </article>
            <?php endif; ?>
        <?php endfor; ?>
    </section>

    <?php if($compareClicked && $selectedCars): ?>
        <section class="compare-table-wrap">
            <table class="compare-table">
                <thead>
                    <tr>
                        <th>Compare Item</th>
                        <?php foreach($selectedCars as $car): ?>
                            <th>
                                <div class="head-car">
                                    <img src="<?= e(resolveCarImageSrc($car["image"] ?? "", $car["car_name"] ?? "Car")) ?>" alt="<?= e($car["car_name"]) ?>">
                                    <div>
                                        <strong><?= e($car["car_name"]) ?></strong>
                                        <span><?= e($car["brand"]) ?> • <?= e($car["category_name"]) ?></span>
                                    </div>
                                </div>
                            </th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($compareRows as $groupTitle => $rows): ?>
                        <tr class="group-row">
                            <th colspan="<?= e(count($selectedCars) + 1) ?>"><?= e($groupTitle) ?></th>
                        </tr>

                        <?php foreach($rows as [$label, $key]): ?>
                            <tr>
                                <td class="compare-item"><?= e($label) ?></td>
                                <?php foreach($selectedCars as $car): ?>
                                    <td>
                                        <?php
                                            $value = "-";
                                            $badge = "";

                                            if($key === "best_for") {
                                                $value = bestFor($car);
                                            } elseif($key === "price_per_day") {
                                                $value = money($car["price_per_day"]);
                                                if($lowestPrice !== null && (float)$car["price_per_day"] == $lowestPrice) $badge = "Best Price";
                                            } elseif($key === "seats") {
                                                $value = $car["seats"] . " seats";
                                                $seatsValue = parseNumber($car["seats"]);
                                                if($highestSeats !== null && $seatsValue !== null && $seatsValue == $highestSeats) $badge = "Most Seats";
                                            } elseif($key === "horsepower") {
                                                $value = $car["horsepower"] . " hp";
                                                $powerValue = parseNumber($car["horsepower"]);
                                                if($highestPower !== null && $powerValue !== null && $powerValue == $highestPower && $powerValue > 0) $badge = "Highest Power";
                                            } elseif($key === "fuel_consumption") {
                                                $value = $car["fuel_consumption"];
                                                $fuelValue = parseNumber($car["fuel_consumption"]);
                                                if($bestFuel !== null && $fuelValue !== null && $fuelValue == $bestFuel) $badge = "Fuel Saving";
                                            } else {
                                                $value = $car[$key] ?? "-";
                                            }

                                            echo '<span class="value-text">' . e($value) . '</span>';
                                            if($badge !== "") echo '<span class="mini-badge">' . e($badge) . '</span>';
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </section>
    <?php endif; ?>
</main>

<footer class="footer">
    <div class="footer-inner">
        <div>
            <h3>KH Car Rental</h3>
            <p>KH Car Rental provides reliable, affordable and convenient car rental services across Johor, Melaka and Kuala Lumpur. Customers can search available cars, compare vehicles and manage bookings easily through our online system.</p>
            <a class="start-btn" href="catalogue.php"><i class="fa-solid fa-car-side"></i> START Browse</a>
        </div>

        <div>
            <h3>Quick Links</h3>
            <p><a class="footer-hover-link" href="homepage.php">HOME</a></p>
            <p><a class="footer-hover-link" href="catalogue.php">CATALOGUE</a></p>
            <p><a class="footer-hover-link" href="find_car_smart.php">FIND CAR SMART</a></p>
            <p><a class="footer-hover-link" href="compare_car.php">COMPARE CAR</a></p>
            <p><a class="footer-hover-link" href="aboutus.php">ABOUT US</a></p>
            <p><a class="footer-hover-link" href="contactus.php">CONTACT US</a></p>
            <p><a class="footer-hover-link" href="cart.php">CART</a></p>
        </div>

        <div>
            <h3>Contact</h3>
            <p><a class="contact-link" href="tel:+60123456789"><i class="fa-solid fa-phone"></i> +60 12-345 6789</a></p>
            <p><a class="contact-link" href="mailto:hoomenghui@student.mmu.edu.my"><i class="fa-solid fa-envelope"></i> hoomenghui@student.mmu.edu.my</a></p>
            <p><a class="contact-link" href="mailto:pangkanghorng@student.mmu.edu.my"><i class="fa-solid fa-envelope"></i> pangkanghorng@student.mmu.edu.my</a></p>
            <p><a class="contact-link" href="mailto:ngmengxin@student.mmu.edu.my"><i class="fa-solid fa-envelope"></i> ngmengxin@student.mmu.edu.my</a></p>
            <p><a class="contact-link" href="https://www.google.com/maps/search/?api=1&query=Multimedia+University+Melaka" target="_blank"><i class="fa-solid fa-location-dot"></i> Multimedia University, Melaka</a></p>
        </div>
    </div>

    <div class="footer-bottom">
        © 2026 KH Car Rental. All rights reserved.
    </div>
</footer>

<button class="back-top" type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})">
    <i class="fa-solid fa-arrow-up"></i>
</button>

<script>
const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");

if(avatarBtn && profileDropdown) {
    avatarBtn.addEventListener("click", function(event) {
        event.stopPropagation();
        profileDropdown.classList.toggle("show");
    });

    document.addEventListener("click", function() {
        profileDropdown.classList.remove("show");
    });
}

function filterSlot(slot) {
    const brand = document.querySelector('.brand-filter[data-slot="' + slot + '"]').value;
    const category = document.querySelector('.category-filter[data-slot="' + slot + '"]').value;
    const carSelect = document.querySelector('.car-select[data-slot="' + slot + '"]');

    Array.from(carSelect.options).forEach(option => {
        if(option.value === "") {
            option.hidden = false;
            return;
        }

        const brandMatch = !brand || option.dataset.brand === brand;
        const categoryMatch = !category || option.dataset.category === category;

        option.hidden = !(brandMatch && categoryMatch);
    });

    if(carSelect.selectedOptions[0] && carSelect.selectedOptions[0].hidden) {
        carSelect.value = "";
    }
}

document.querySelectorAll(".brand-filter, .category-filter").forEach(select => {
    select.addEventListener("change", function() {
        filterSlot(this.dataset.slot);
    });
});

document.querySelectorAll(".clear-slot").forEach(button => {
    button.addEventListener("click", function() {
        const slot = this.dataset.slot;
        const brandFilter = document.querySelector('.brand-filter[data-slot="' + slot + '"]');
        const categoryFilter = document.querySelector('.category-filter[data-slot="' + slot + '"]');
        const carSelect = document.querySelector('.car-select[data-slot="' + slot + '"]');

        if(brandFilter) brandFilter.value = "";
        if(categoryFilter) categoryFilter.value = "";
        if(carSelect) carSelect.value = "";

        filterSlot(slot);

        const hasOtherSelectedCars = Array.from(document.querySelectorAll(".car-select"))
            .some(select => select.value !== "");

        if(!hasOtherSelectedCars) {
            window.location.href = "compare_car.php";
            return;
        }

        document.getElementById("compareForm").submit();
    });
});

for(let i = 1; i <= 3; i++) {
    filterSlot(i);
}

function updateSequentialSlots() {
    const slot1 = document.querySelector('.car-select[data-slot="1"]');
    const slot2 = document.querySelector('.car-select[data-slot="2"]');
    const slot3 = document.querySelector('.car-select[data-slot="3"]');

    const slot2Card = document.querySelector('.select-card:nth-child(2)');
    const slot3Card = document.querySelector('.select-card:nth-child(3)');

    const slot2Locked = !slot1 || !slot1.value;
    const slot3Locked = slot2Locked || !slot2 || !slot2.value;

    [
        {slot: 2, locked: slot2Locked, card: slot2Card},
        {slot: 3, locked: slot3Locked, card: slot3Card}
    ].forEach(item => {
        const controls = document.querySelectorAll(
            '.brand-filter[data-slot="' + item.slot + '"], ' +
            '.category-filter[data-slot="' + item.slot + '"], ' +
            '.car-select[data-slot="' + item.slot + '"], ' +
            '.clear-slot[data-slot="' + item.slot + '"]'
        );

        controls.forEach(control => {
            control.disabled = item.locked;
            if(item.locked && control.classList.contains("car-select")) {
                control.value = "";
            }
        });

        const compareBtn = item.card ? item.card.querySelector('.btn-blue') : null;
        if(compareBtn) compareBtn.disabled = item.locked;

        if(item.card) {
            item.card.classList.toggle("slot-locked", item.locked);
        }
    });
}

document.querySelectorAll(".car-select").forEach(select => {
    select.addEventListener("change", updateSequentialSlots);
});

updateSequentialSlots();

</script>
</body>
</html>
