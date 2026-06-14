<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function tableExists($conn, $table) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
    ");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["total"] ?? 0) > 0;
}

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = ?
        AND COLUMN_NAME = ?
    ");
    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["total"] ?? 0) > 0;
}

function firstColumn($conn, $table, $columns, $fallback = null) {
    foreach ($columns as $column) {
        if (columnExists($conn, $table, $column)) return $column;
    }
    return $fallback;
}


function getNavCartCount($conn) {
    $sessionCount = 0;
    if (!empty($_SESSION["cart"]) && is_array($_SESSION["cart"])) {
        $sessionCount = count($_SESSION["cart"]);
    }

    if (empty($_SESSION["user_id"]) || !tableExists($conn, "cart_items")) {
        return $sessionCount;
    }

    $stmt = $conn->prepare("\n        SELECT COUNT(*) AS total\n        FROM cart_items\n        WHERE user_id = ?\n        AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out')\n    ");

    if (!$stmt) {
        return $sessionCount;
    }

    $userId = (int)$_SESSION["user_id"];
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row["total"] ?? 0);
}

function fetchRows($conn, $sql, $types = "", $params = []) {
    if ($types !== "" && !empty($params)) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return [];
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();
        return $rows;
    }

    $result = $conn->query($sql);
    if (!$result) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}

function fetchOne($conn, $sql, $types = "", $params = []) {
    $rows = fetchRows($conn, $sql, $types, $params);
    return $rows[0] ?? null;
}

function hasValue($value) {
    return trim((string)$value) !== "";
}

function toDateTimeLabel($date, $time) {
    if (!$date || !$time) return "-";
    $timestamp = strtotime($date . " " . $time);
    if (!$timestamp) return $date . " " . $time;
    return date("d M Y, h:i A", $timestamp);
}

function rentalDays($pickupDate, $pickupTime, $returnDate, $returnTime) {
    $start = strtotime($pickupDate . " " . $pickupTime);
    $end = strtotime($returnDate . " " . $returnTime);
    if (!$start || !$end || $end <= $start) return 0;
    return max(1, (int)ceil(($end - $start) / 86400));
}

function resolveCarImageSrc($imagePath, $carName = "Car Image") {
    $imagePath = trim((string)$imagePath);

    if ($imagePath !== "" && preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    if ($imagePath !== "") {
        $localPath = __DIR__ . "/" . ltrim($imagePath, "/");
        if (is_file($localPath)) {
            return $imagePath;
        }
    }

    $safeName = htmlspecialchars((string)$carName, ENT_QUOTES, "UTF-8");
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520" viewBox="0 0 900 520">'
        . '<defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#eaf7ff"/><stop offset="1" stop-color="#f8fcff"/></linearGradient></defs>'
        . '<rect width="900" height="520" fill="url(#g)"/>'
        . '<rect x="58" y="64" width="784" height="392" rx="34" fill="#ffffff" opacity="0.72" stroke="#b8e4ff"/>'
        . '<path d="M198 318h475c32 0 58-24 58-54v-20c0-14-10-27-24-31l-96-28c-31-45-68-68-112-68H356c-54 0-94 24-128 70l-70 24c-17 6-29 22-29 40v13c0 30 26 54 69 54Z" fill="#1fa2df" opacity="0.92"/>'
        . '<circle cx="271" cy="323" r="45" fill="#10233d"/><circle cx="271" cy="323" r="20" fill="#d8f2ff"/>'
        . '<circle cx="624" cy="323" r="45" fill="#10233d"/><circle cx="624" cy="323" r="20" fill="#d8f2ff"/>'
        . '<path d="M336 188h159c36 0 67 18 93 53H280c19-35 37-53 56-53Z" fill="#d8f2ff" opacity="0.9"/>'
        . '<text x="450" y="92" text-anchor="middle" font-family="Segoe UI, Arial" font-size="28" font-weight="800" fill="#10233d">' . $safeName . '</text>'
        . '<text x="450" y="430" text-anchor="middle" font-family="Segoe UI, Arial" font-size="18" font-weight="700" fill="#6e8297">Vehicle photo</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function getCarImages($conn, $carId, $fallbackImage = "", $carName = "Car Image") {
    $images = [];

    if (tableExists($conn, "car_images")) {
        $carImageCarCol = firstColumn($conn, "car_images", ["car_id"], "car_id");
        $carImageUrlCol = firstColumn($conn, "car_images", ["image_url", "image", "image_path"], "image_url");
        $carImageSortCol = firstColumn($conn, "car_images", ["sort_order"], null);
        $imagePk = firstColumn($conn, "car_images", ["image_id", "id"], null);
        $orderBy = $carImageSortCol ? "ORDER BY $carImageSortCol ASC" : ($imagePk ? "ORDER BY $imagePk ASC" : "");

        $stmt = $conn->prepare("SELECT $carImageUrlCol AS image_url FROM car_images WHERE $carImageCarCol = ? $orderBy LIMIT 8");
        if ($stmt) {
            $stmt->bind_param("i", $carId);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if (!empty($row["image_url"])) {
                    $images[] = resolveCarImageSrc($row["image_url"], $carName);
                }
            }
            $stmt->close();
        }
    }

    if (empty($images) && !empty($fallbackImage)) {
        $images[] = resolveCarImageSrc($fallbackImage, $carName);
    }

    if (empty($images)) {
        $images[] = resolveCarImageSrc("", $carName);
    }

    return array_values(array_unique($images));
}

function featureList($text, $fallback) {
    $text = trim((string)$text);
    if ($text === "" || $text === "-") return $fallback;

    $items = preg_split('/[,\n\r;|]+/', $text);
    $items = array_values(array_filter(array_map("trim", $items)));
    return $items ?: $fallback;
}

$user = null;
if (!empty($_SESSION["user_id"]) && tableExists($conn, "users")) {
    $userIdCol = firstColumn($conn, "users", ["user_id", "id"], "user_id");
    $stmt = $conn->prepare("SELECT * FROM users WHERE $userIdCol = ? LIMIT 1");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$carId = (int)($_GET["car_id"] ?? 0);
$car = null;

if ($carId > 0 && tableExists($conn, "cars")) {
    $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
    $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
    $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
    $imageCol = firstColumn($conn, "cars", ["main_image", "image", "car_image"], null);
    $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
    $brandCol = firstColumn($conn, "cars", ["brand", "brand_name"], null);
    $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
    $categoryCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
    $yearCol = firstColumn($conn, "cars", ["year", "car_year"], null);
    $modelCol = firstColumn($conn, "cars", ["model"], null);
    $engineCol = firstColumn($conn, "cars", ["engine", "engine_capacity"], null);
    $horsepowerCol = firstColumn($conn, "cars", ["horsepower", "hp"], null);
    $accelerationCol = firstColumn($conn, "cars", ["acceleration_0_100", "acceleration", "zero_to_hundred"], null);
    $torqueCol = firstColumn($conn, "cars", ["torque"], null);
    $transmissionCol = firstColumn($conn, "cars", ["transmission"], null);
    $drivetrainCol = firstColumn($conn, "cars", ["drivetrain"], null);
    $fuelCol = firstColumn($conn, "cars", ["fuel_type"], null);
    $fuelConsumptionCol = firstColumn($conn, "cars", ["fuel_consumption"], null);
    $frontBrakeCol = firstColumn($conn, "cars", ["front_brake", "brake_front"], null);
    $rearBrakeCol = firstColumn($conn, "cars", ["rear_brake", "brake_rear"], null);
    $suspensionCol = firstColumn($conn, "cars", ["suspension", "suspension_type"], null);
    $seatsCol = firstColumn($conn, "cars", ["seats"], null);
    $doorsCol = firstColumn($conn, "cars", ["doors"], null);
    $luggageCol = firstColumn($conn, "cars", ["luggage_capacity", "luggage"], null);
    $tagCol = firstColumn($conn, "cars", ["car_tag", "tag", "badge"], null);
    $descCol = firstColumn($conn, "cars", ["description"], null);
    $safetyCol = firstColumn($conn, "cars", ["safety_features"], null);
    $comfortCol = firstColumn($conn, "cars", ["comfort_features"], null);
    $entertainmentCol = firstColumn($conn, "cars", ["entertainment_features"], null);
    $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);

    $select = [
        "c.$carIdCol AS car_id",
        "c.$carNameCol AS car_name",
        ($priceCol ? "c.$priceCol" : "0") . " AS price_per_day",
        ($imageCol ? "c.$imageCol" : "''") . " AS image",
        ($yearCol ? "c.$yearCol" : "''") . " AS car_year",
        ($modelCol ? "c.$modelCol" : "''") . " AS model",
        ($engineCol ? "c.$engineCol" : "'-'") . " AS engine",
        ($horsepowerCol ? "c.$horsepowerCol" : "0") . " AS horsepower",
        ($accelerationCol ? "c.$accelerationCol" : "'-'") . " AS acceleration_0_100",
        ($torqueCol ? "c.$torqueCol" : "'-'") . " AS torque",
        ($transmissionCol ? "c.$transmissionCol" : "'Automatic'") . " AS transmission",
        ($drivetrainCol ? "c.$drivetrainCol" : "'-'") . " AS drivetrain",
        ($fuelCol ? "c.$fuelCol" : "'Petrol'") . " AS fuel_type",
        ($fuelConsumptionCol ? "c.$fuelConsumptionCol" : "'-'") . " AS fuel_consumption",
        ($frontBrakeCol ? "c.$frontBrakeCol" : "'Ventilated front disc brake with caliper'") . " AS front_brake",
        ($rearBrakeCol ? "c.$rearBrakeCol" : "'Rear disc / drum brake setup'") . " AS rear_brake",
        ($suspensionCol ? "c.$suspensionCol" : "'MacPherson strut front suspension'") . " AS suspension",
        ($seatsCol ? "c.$seatsCol" : "5") . " AS seats",
        ($doorsCol ? "c.$doorsCol" : "'4'") . " AS doors",
        ($luggageCol ? "c.$luggageCol" : "'-'") . " AS luggage_capacity",
        ($tagCol ? "c.$tagCol" : "''") . " AS car_tag",
        ($descCol ? "c.$descCol" : "''") . " AS description",
        ($safetyCol ? "c.$safetyCol" : "''") . " AS safety_features",
        ($comfortCol ? "c.$comfortCol" : "''") . " AS comfort_features",
        ($entertainmentCol ? "c.$entertainmentCol" : "''") . " AS entertainment_features"
    ];

    $join = "";

    if ($brandIdCol && tableExists($conn, "brands")) {
        $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "brand_id");
        $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
        $select[] = "COALESCE(b.$brandNameCol, '-') AS brand";
        $join .= " LEFT JOIN brands b ON b.$brandPk = c.$brandIdCol ";
    } elseif ($brandCol) {
        $select[] = "c.$brandCol AS brand";
    } else {
        $select[] = "'-' AS brand";
    }

    if ($categoryIdCol && tableExists($conn, "categories")) {
        $categoryPk = firstColumn($conn, "categories", ["category_id", "id"], "category_id");
        $categoryNameCol = firstColumn($conn, "categories", ["category_name", "name"], "category_name");
        $select[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
        $join .= " LEFT JOIN categories cat ON cat.$categoryPk = c.$categoryIdCol ";
    } elseif ($categoryIdCol && tableExists($conn, "vehicle_categories")) {
        $categoryPk = firstColumn($conn, "vehicle_categories", ["category_id", "id"], "category_id");
        $categoryNameCol = firstColumn($conn, "vehicle_categories", ["category_name", "name"], "category_name");
        $select[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
        $join .= " LEFT JOIN vehicle_categories cat ON cat.$categoryPk = c.$categoryIdCol ";
    } elseif ($categoryCol) {
        $select[] = "c.$categoryCol AS category_name";
    } else {
        $select[] = "'Others' AS category_name";
    }

    if (tableExists($conn, "car_units")) {
        $unitCarCol = firstColumn($conn, "car_units", ["car_id"], "car_id");
        $unitStateCol = firstColumn($conn, "car_units", ["state_id"], null);
        $unitColorCol = firstColumn($conn, "car_units", ["color"], null);

        $select[] = $unitColorCol ? "MIN(NULLIF(TRIM(cu.$unitColorCol), '')) AS car_color" : "'Not specified' AS car_color";
        $join .= " LEFT JOIN car_units cu ON cu.$unitCarCol = c.$carIdCol ";

        if ($unitStateCol && tableExists($conn, "rental_states")) {
            $statePk = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
            $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
            $select[] = "GROUP_CONCAT(DISTINCT rs.$stateNameCol ORDER BY rs.$stateNameCol SEPARATOR ', ') AS state_names";
            $join .= " LEFT JOIN rental_states rs ON rs.$statePk = cu.$unitStateCol ";
        } else {
            $select[] = "'' AS state_names";
        }
    } else {
        $select[] = "'Not specified' AS car_color";
        $select[] = "'' AS state_names";
    }

    $where = "c.$carIdCol = ?";
    if ($statusCol) {
        $where .= " AND (LOWER(c.$statusCol) IN ('active','available') OR c.$statusCol = 1)";
    }

    $sql = "SELECT " . implode(", ", $select) . " FROM cars c $join WHERE $where GROUP BY c.$carIdCol LIMIT 1";
    $car = fetchOne($conn, $sql, "i", [$carId]);
}

$states = [];
$locations = [];

if (tableExists($conn, "rental_states")) {
    $stateIdCol = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
    $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
    $stateSlugCol = firstColumn($conn, "rental_states", ["state_slug", "slug"], null);
    $states = fetchRows($conn, "SELECT $stateIdCol AS state_id, $stateNameCol AS state_name, " . ($stateSlugCol ? "$stateSlugCol" : "LOWER(REPLACE($stateNameCol, ' ', '-'))") . " AS state_slug FROM rental_states ORDER BY $stateNameCol ASC");
}

if (!$states) {
    $states = [
        ["state_id" => 1, "state_name" => "Johor", "state_slug" => "johor"],
        ["state_id" => 2, "state_name" => "Melaka", "state_slug" => "melaka"],
        ["state_id" => 3, "state_name" => "Kuala Lumpur", "state_slug" => "kuala-lumpur"]
    ];
}

if (tableExists($conn, "rental_locations")) {
    $locationIdCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
    $locationNameCol = firstColumn($conn, "rental_locations", ["location_name", "name"], "location_name");
    $locationStateCol = firstColumn($conn, "rental_locations", ["state_id"], "state_id");
    $locations = fetchRows($conn, "SELECT $locationIdCol AS location_id, $locationNameCol AS location_name, $locationStateCol AS state_id FROM rental_locations ORDER BY $locationStateCol ASC, $locationNameCol ASC");
}

if (!$locations) {
    $locations = [
        ["location_id" => 1, "location_name" => "JB Sentral", "state_id" => 1],
        ["location_id" => 2, "location_name" => "Johor Bahru City Centre", "state_id" => 1],
        ["location_id" => 3, "location_name" => "Larkin Sentral", "state_id" => 1],
        ["location_id" => 4, "location_name" => "Melaka Sentral", "state_id" => 2],
        ["location_id" => 5, "location_name" => "MMU Melaka", "state_id" => 2],
        ["location_id" => 6, "location_name" => "Ayer Keroh", "state_id" => 2],
        ["location_id" => 7, "location_name" => "KL Sentral", "state_id" => 3],
        ["location_id" => 8, "location_name" => "Bukit Bintang", "state_id" => 3],
        ["location_id" => 9, "location_name" => "TBS Kuala Lumpur", "state_id" => 3]
    ];
}

$locationMap = [];
$locationNameMap = [];
foreach ($locations as $location) {
    $stateKey = (string)$location["state_id"];
    $locationMap[$stateKey][] = [
        "id" => (int)$location["location_id"],
        "name" => $location["location_name"]
    ];
    $locationNameMap[(int)$location["location_id"]] = $location["location_name"];
}

$stateNameMap = [];
foreach ($states as $state) {
    $stateNameMap[(int)$state["state_id"]] = $state["state_name"];
}

function findStateIdFromValue($states, $value) {
    if (is_numeric($value)) return (int)$value;

    $value = strtolower(trim((string)$value));

    foreach ($states as $state) {
        $id = (int)($state["state_id"] ?? 0);
        $name = strtolower((string)($state["state_name"] ?? ""));
        $slug = strtolower((string)($state["state_slug"] ?? str_replace(" ", "-", $name)));

        if ($value !== "" && ($value === $name || $value === $slug)) {
            return $id;
        }
    }

    return 0;
}

$trip = [
    "state" => findStateIdFromValue($states, $_GET["state"] ?? $_GET["state_id"] ?? $_GET["pickup_state"] ?? $_GET["car_state"] ?? ""),
    "pickup_location" => (int)($_GET["pickup_location"] ?? $_GET["pickup_location_id"] ?? $_GET["pickup"] ?? 0),
    "dropoff_location" => (int)($_GET["dropoff_location"] ?? $_GET["dropoff_location_id"] ?? $_GET["return_location"] ?? $_GET["dropoff"] ?? 0),
    "pickup_date" => trim($_GET["pickup_date"] ?? $_GET["start_date"] ?? ""),
    "pickup_time" => trim($_GET["pickup_time"] ?? $_GET["start_time"] ?? ""),
    "return_date" => trim($_GET["return_date"] ?? $_GET["end_date"] ?? ""),
    "return_time" => trim($_GET["return_time"] ?? $_GET["end_time"] ?? "")
];

if ($trip["return_time"] === "" && $trip["pickup_time"] !== "") {
    $trip["return_time"] = $trip["pickup_time"];
}

$hasTrip = $trip["state"] > 0
    && $trip["pickup_location"] > 0
    && $trip["dropoff_location"] > 0
    && hasValue($trip["pickup_date"])
    && hasValue($trip["pickup_time"])
    && hasValue($trip["return_date"])
    && hasValue($trip["return_time"]);

$days = $hasTrip ? rentalDays($trip["pickup_date"], $trip["pickup_time"], $trip["return_date"], $trip["return_time"]) : 0;
$estimatedTotal = $car && $days > 0 ? (float)$car["price_per_day"] * $days : 0;

$images = $car ? getCarImages($conn, (int)$car["car_id"], $car["image"] ?? "", $car["car_name"] ?? "Car Image") : [];

$safetyFeatures = $car ? featureList($car["safety_features"] ?? "", ["ABS", "Airbags", "Stability Control", "Reverse Camera", "Parking Sensor", "Emergency Brake Assist"]) : [];
$comfortFeatures = $car ? featureList($car["comfort_features"] ?? "", ["Air Conditioning", "Comfort Seats", "Spacious Cabin", "Power Windows", "Smart Key", "Premium Interior"]) : [];
$entertainmentFeatures = $car ? featureList($car["entertainment_features"] ?? "", ["Bluetooth", "USB Port", "Touchscreen Display", "Apple CarPlay", "Android Auto", "Audio System"]) : [];

$similarCars = [];
if ($car && tableExists($conn, "cars")) {
    $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
    $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
    $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
    $imageCol = firstColumn($conn, "cars", ["main_image", "image", "car_image"], null);
    $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
    $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
    $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);

    $similarSelect = [
        "c.$carIdCol AS car_id",
        "c.$carNameCol AS car_name",
        ($priceCol ? "c.$priceCol" : "0") . " AS price_per_day",
        ($imageCol ? "c.$imageCol" : "''") . " AS image"
    ];

    $similarJoin = "";
    if ($brandIdCol && tableExists($conn, "brands")) {
        $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "brand_id");
        $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
        $similarSelect[] = "COALESCE(b.$brandNameCol, '-') AS brand";
        $similarJoin .= " LEFT JOIN brands b ON b.$brandPk = c.$brandIdCol ";
    } else {
        $similarSelect[] = "'-' AS brand";
    }

    if ($categoryIdCol && tableExists($conn, "categories")) {
        $categoryPk = firstColumn($conn, "categories", ["category_id", "id"], "category_id");
        $categoryNameCol = firstColumn($conn, "categories", ["category_name", "name"], "category_name");
        $similarSelect[] = "COALESCE(cat.$categoryNameCol, 'Others') AS category_name";
        $similarJoin .= " LEFT JOIN categories cat ON cat.$categoryPk = c.$categoryIdCol ";
    } else {
        $similarSelect[] = "'Others' AS category_name";
    }

    $similarWhere = "c.$carIdCol <> ?";
    $similarParams = [(int)$car["car_id"]];
    $similarTypes = "i";

    if ($categoryIdCol && isset($categoryPk)) {
        $similarWhere .= " AND cat.$categoryNameCol = ?";
        $similarParams[] = (string)$car["category_name"];
        $similarTypes .= "s";
    }

    if ($statusCol) {
        $similarWhere .= " AND (LOWER(c.$statusCol) IN ('active','available') OR c.$statusCol = 1)";
    }

    $similarSql = "SELECT " . implode(", ", $similarSelect) . " FROM cars c $similarJoin WHERE $similarWhere GROUP BY c.$carIdCol ORDER BY ABS(" . ($priceCol ? "c.$priceCol" : "0") . " - ?) ASC, c.$carNameCol ASC LIMIT 3";
    $similarParams[] = (float)$car["price_per_day"];
    $similarTypes .= "d";
    $similarCars = fetchRows($conn, $similarSql, $similarTypes, $similarParams);
}

$queryTrip = $hasTrip ? http_build_query([
    "state" => $trip["state"],
    "pickup_location" => $trip["pickup_location"],
    "dropoff_location" => $trip["dropoff_location"],
    "pickup_date" => $trip["pickup_date"],
    "pickup_time" => $trip["pickup_time"],
    "return_date" => $trip["return_date"],
    "return_time" => $trip["return_time"]
]) : "";

$navCartCount = getNavCartCount($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title><?= $car ? e($car["car_name"]) . " | KH Car Rental" : "Car Details | KH Car Rental" ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>

:root{
    --sky50:#f5fbff;
    --sky100:#eaf7ff;
    --sky200:#d8f2ff;
    --sky500:#28a8ea;
    --sky600:#1284c6;
    --dark:#10233d;
    --muted:#6e8297;
    --orange:#ff7a1a;
    --orange2:#f15f12;
    --border:#d8ecfb;
    --shadow:0 24px 70px rgba(39,137,199,.13);
    --soft:0 12px 35px rgba(39,137,199,.10);
}

*{box-sizing:border-box;margin:0;padding:0}
html{scroll-behavior:smooth}
body{
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







/* ===== Restored shared form styles for Check Availability modal ===== */
.pill{
    display:inline-flex;
    align-items:center;
    gap:8px;
    width:fit-content;
    padding:6px 11px;
    border-radius:999px;
    background:rgba(40,168,234,.12);
    color:var(--sky600);
    border:1px solid rgba(40,168,234,.22);
    font-size:10.5px;
    font-weight:950;
    letter-spacing:.8px;
    text-transform:uppercase;
    margin-bottom:10px;
}
label{
    display:block;
    font-size:9.5px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    line-height:1;
    margin-bottom:4px;
}
.input{
    width:100%;
    height:34px;
    min-height:34px;
    border:2px solid #e2f2ff;
    background:rgba(255,255,255,.94);
    color:var(--dark);
    border-radius:11px;
    padding:6px 10px;
    outline:none;
    font-size:12px;
    font-weight:750;
    transition:.24s;
}
.input:focus{
    border-color:var(--sky500);
    box-shadow:0 0 0 .2rem rgba(40,168,234,.13);
}
.input.error{border-color:#ff4d4f!important;background:#fff5f5!important}
.inline-error{
    display:none;
    grid-column:1/-1;
    color:#d63031;
    background:#fff5f5;
    border:1px solid rgba(255,77,79,.2);
    border-radius:14px;
    padding:10px 12px;
    font-size:12.5px;
    font-weight:800;
}
.inline-error.show{display:block}

/* ===== Buttons ===== */
.btn,
.category-tab,
.filter-toggle,
.modify-btn,
.start-btn,
.login-btn,
.car-card{
    position:relative;
    overflow:hidden;
}
.btn{
    min-height:36px;
    border:0;
    border-radius:12px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:0 10px;
    font-size:12px;
    font-weight:950;
    cursor:pointer;
    transition:.24s;
}
.btn-blue{
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
}
.btn-white{
    background:#fff;
    color:var(--sky600);
    border:1px solid var(--border);
}
.btn-orange{
    width:100%;
    color:#fff;
    background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);
    box-shadow:0 18px 34px rgba(255,122,26,.26);
}
.btn::before,
.category-tab::before,
.filter-toggle::before,
.modify-btn::before,
.start-btn::before,
.login-btn::before{
    content:"";
    position:absolute;
    top:0;
    left:-120%;
    width:55%;
    height:100%;
    background:linear-gradient(120deg,transparent,rgba(255,255,255,.42),transparent);
    transform:skewX(-22deg);
    transition:left .58s ease;
    pointer-events:none;
}
.btn:hover::before,
.category-tab:hover::before,
.filter-toggle:hover::before,
.modify-btn:hover::before,
.start-btn:hover::before,
.login-btn:hover::before{left:130%}
.btn:hover,
.category-tab:hover,
.filter-toggle:hover,
.modify-btn:hover,
.start-btn:hover,
.login-btn:hover{
    transform:translateY(-3px);
    box-shadow:var(--soft);
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

/* ===== Responsive ===== */
@media(max-width:1220px){
    .hero-card{grid-template-columns:1fr}
    .catalogue-control-layout{grid-template-columns:1fr}
    .car-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:999px){
    .nav-links{display:none}
    .category-tabs{grid-template-columns:repeat(3,minmax(0,1fr))}
    .tools-form{grid-template-columns:1fr 1fr}
    .footer-inner{grid-template-columns:1fr;gap:34px}
}
@media(max-width:760px){
    .hero,.main{padding:0 14px}
    .hero-card{padding:20px}
    .category-header-row{
        flex-direction:column;
        align-items:stretch;
    }
    .category-header-row .category-all-tab{
        width:100%;
        flex:auto;
    }
    .category-tabs,
    .tools-form,
    .advanced-filter,
    .filter-actions-row,
    .car-grid,
    .modal-grid,
    .actions,
    .trip-form{
        grid-template-columns:1fr;
    }
    .result-head{display:grid}
    .hero h1{font-size:42px}
}


/* ===== FINAL FIX: footer START Browse slim long button ===== */
footer.footer .footer-inner > div:first-child > a.start-btn,
footer.footer a.start-btn,
.footer a.start-btn,
a.start-btn{
    display:inline-flex!important;
    align-items:center!important;
    justify-content:center!important;
    gap:10px!important;

    width:auto!important;
    min-width:210px!important;
    height:44px!important;
    min-height:44px!important;
    padding:0 28px!important;

    border-radius:16px!important;
    background:linear-gradient(135deg,#28a8ea,#1284c6)!important;
    color:#ffffff!important;
    border:1px solid rgba(113,210,255,.22)!important;
    box-shadow:0 16px 32px rgba(40,168,234,.24)!important;

    font-size:15.5px!important;
    font-weight:950!important;
    line-height:1!important;
    letter-spacing:.15px!important;
    text-decoration:none!important;
    white-space:nowrap!important;
    overflow:hidden!important;
}

footer.footer .footer-inner > div:first-child > a.start-btn i,
footer.footer a.start-btn i,
.footer a.start-btn i,
a.start-btn i{
    color:#ffffff!important;
    font-size:15.5px!important;
    line-height:1!important;
}

footer.footer .footer-inner > div:first-child > a.start-btn:hover,
footer.footer a.start-btn:hover,
.footer a.start-btn:hover,
a.start-btn:hover{
    transform:translateY(-2px)!important;
    color:#ffffff!important;
    box-shadow:0 20px 38px rgba(40,168,234,.32)!important;
}




/* ===== Homepage-matching search time controls ===== */
.time-combo{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.time-combo .input{
    width:100%;
}
.fixed-time-display{
    width:100%;
    min-height:34px;
    height:34px;
    border:2px solid #e2f2ff;
    background:rgba(255,255,255,.94);
    color:var(--dark);
    border-radius:11px;
    padding:6px 10px;
    display:flex;
    align-items:center;
    gap:9px;
    font-size:12px;
    font-weight:850;
}
.fixed-time-display i{
    color:var(--sky600);
}
.search-open .trip-form{
    grid-template-columns:1fr 1fr;
}
.applied-filter-row{
    width:100%;
    margin:0 0 14px;
    display:flex;
    align-items:center;
    flex-wrap:wrap;
    gap:8px;
}
.applied-filter-title,
.filter-chip{
    min-height:32px;
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:0 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:950;
    border:1px solid var(--border);
    background:rgba(255,255,255,.86);
    color:var(--sky600);
}
.applied-filter-title{
    background:var(--sky100);
}
.modal-card{
    width:min(760px,calc(100% - 32px))!important;
}
.modal-grid{
    grid-template-columns:1fr 1fr;
}
.availability-result.show{
    display:block;
}
.availability-result{
    margin-top:8px;
}
.availability-actions.three{
    grid-template-columns:1fr 1fr 1fr;
}
@media(max-width:760px){
    .modal-grid,
    .search-open .trip-form{
        grid-template-columns:1fr;
    }
    .availability-actions.three{
        grid-template-columns:1fr;
    }
}



/* ===== NAVBAR / MAP / PRICE FIX (requested) ===== */
.nav-inner{
    width:min(1320px,calc(100% - 24px))!important;
    gap:12px!important;
}
.brand{
    margin-right:10px!important;
    flex-shrink:0!important;
}
.nav-links{
    gap:12px!important;
    flex-wrap:nowrap!important;
    min-width:0!important;
}
.nav-links a{
    white-space:nowrap!important;
    font-size:11.5px!important;
    padding:8px 5px!important;
}
.avatar-wrap{
    flex-shrink:0!important;
}
.login-btn{
    white-space:nowrap!important;
    flex-shrink:0!important;
    min-width:max-content!important;
    padding:13px 18px!important;
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

@media(max-width:1180px){
    .nav-links{display:none!important;}
    .menu-btn{display:grid!important;}
}



/* ===== Car Details Page ===== */
.detail-page{
    width:min(1280px,100%);
    margin:18px auto 58px;
    padding:0 22px;
}
.breadcrumb{
    display:flex;
    align-items:center;
    gap:9px;
    flex-wrap:wrap;
    margin:14px 0;
    color:var(--muted);
    font-size:12px;
    font-weight:850;
}
.breadcrumb a{color:var(--sky600)}
.detail-hero{
    display:grid;
    grid-template-columns:1.08fr .92fr;
    gap:18px;
    align-items:stretch;
    padding:22px;
    border-radius:30px;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.25),transparent 32%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(247,253,255,.94));
    border:1px solid rgba(184,228,255,.92);
    box-shadow:var(--shadow);
}
.gallery-card,
.detail-info-card,
.spec-section,
.feature-card,
.similar-section,
.trip-summary-card{
    background:
        radial-gradient(circle at 100% 0%,rgba(40,168,234,.08),transparent 28%),
        linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));
    border:1px solid rgba(184,228,255,.98);
    box-shadow:0 18px 46px rgba(29,109,164,.12);
    border-radius:26px;
}
.gallery-card{padding:14px}
.main-photo{
    position:relative;
    height:430px;
    border-radius:22px;
    overflow:hidden;
    background:linear-gradient(135deg,#edf9ff,#ffffff);
    border:1px solid var(--border);
}
.main-photo img{
    width:100%;
    height:100%;
    object-fit:cover;
    display:block;
}
.gallery-nav{
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    width:42px;
    height:42px;
    border:1px solid rgba(255,255,255,.82);
    border-radius:50%;
    background:rgba(255,255,255,.86);
    color:var(--sky600);
    display:grid;
    place-items:center;
    cursor:pointer;
    z-index:5;
    box-shadow:0 10px 24px rgba(16,35,61,.16);
    backdrop-filter:blur(12px);
}
.gallery-nav:hover{background:var(--sky600);color:#fff}
.gallery-prev{left:14px}
.gallery-next{right:14px}
.thumbnail-row{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:9px;
    margin-top:10px;
}
.thumb-btn{
    height:76px;
    border:2px solid transparent;
    border-radius:16px;
    overflow:hidden;
    cursor:pointer;
    background:#fff;
}
.thumb-btn.active{border-color:var(--sky500)}
.thumb-btn img{width:100%;height:100%;object-fit:cover;display:block}
.detail-info-card{
    padding:26px;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    gap:18px;
}
.detail-title h1{
    font-size:clamp(34px,3.4vw,52px);
    line-height:1;
    letter-spacing:-1.6px;
    font-weight:950;
    margin:6px 0 10px;
}
.detail-meta{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin-bottom:14px;
}
.detail-price{
    display:inline-flex;
    align-items:flex-end;
    gap:7px;
    width:fit-content;
    padding:14px 18px;
    border-radius:18px;
    color:#fff;
    background:linear-gradient(135deg,#ff9a4a,#f15f12);
    box-shadow:0 18px 36px rgba(255,122,26,.26);
    font-size:26px;
    font-weight:950;
}
.detail-price small{
    font-size:13px;
    font-weight:900;
    opacity:.95;
    padding-bottom:4px;
}
.detail-short{
    color:var(--muted);
    font-size:14px;
    line-height:1.65;
    font-weight:700;
}
.quick-spec-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:9px;
}
.quick-spec{
    min-height:54px;
    display:flex;
    align-items:center;
    gap:12px;
    padding:12px 13px;
    border-radius:16px;
    background:linear-gradient(135deg,rgba(234,247,255,.92),rgba(255,255,255,.82));
    border:1px solid rgba(216,236,251,.78);
    color:#2b4969;
    font-size:13px;
    font-weight:900;
}
.quick-spec i{
    width:28px;
    height:28px;
    display:grid;
    place-items:center;
    border-radius:50%;
    color:var(--sky600);
    background:#eaf7ff;
}
.quick-spec.wide{grid-column:1/-1}
.detail-action-row{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:10px;
    margin-top:6px;
}
.detail-action-row .btn{min-height:48px;border-radius:16px;font-size:13px}
.trip-summary-card{
    margin-top:18px;
    padding:20px;
}
.trip-summary-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    gap:16px;
    margin-bottom:14px;
}
.trip-summary-head h2,
.spec-section h2,
.similar-section h2{
    font-size:24px;
    font-weight:950;
    letter-spacing:-.5px;
}
.trip-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
}
.trip-mini{
    padding:13px;
    border-radius:16px;
    background:#fff;
    border:1px solid var(--border);
}
.trip-mini span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:4px;
}
.trip-mini strong{
    display:block;
    color:var(--dark);
    font-size:13px;
    font-weight:950;
}
.trip-mini.wide{grid-column:span 2}
.section-grid{
    display:grid;
    grid-template-columns:1.1fr .9fr;
    gap:18px;
    margin-top:18px;
}
.spec-section{padding:22px}
.full-spec-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:10px;
    margin-top:16px;
}
.full-spec{
    display:flex;
    align-items:center;
    gap:12px;
    min-height:58px;
    padding:13px;
    border-radius:17px;
    background:#fff;
    border:1px solid var(--border);
}
.full-spec i{
    width:34px;
    height:34px;
    border-radius:14px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    flex:0 0 auto;
}
.full-spec span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
}
.full-spec strong{
    display:block;
    color:var(--dark);
    font-size:13px;
    font-weight:950;
}
.features-column{
    display:grid;
    gap:14px;
}
.feature-card{padding:20px}
.feature-card h3{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:18px;
    font-weight:950;
    margin-bottom:12px;
}
.feature-card h3 i{color:var(--sky600)}
.feature-list{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}
.feature-pill{
    padding:8px 10px;
    border-radius:999px;
    background:var(--sky100);
    border:1px solid var(--border);
    color:#2b4969;
    font-size:12px;
    font-weight:850;
}
.similar-section{
    margin-top:18px;
    padding:22px;
}
.similar-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:18px;
    margin-bottom:14px;
}
.similar-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
}
.similar-card{
    border-radius:22px;
    overflow:hidden;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:0 14px 34px rgba(29,109,164,.10);
}
.similar-img{
    height:150px;
    background:linear-gradient(135deg,#edf9ff,#ffffff);
}
.similar-img img{width:100%;height:100%;object-fit:cover;display:block}
.similar-body{padding:14px}
.similar-body h3{font-size:17px;font-weight:950;line-height:1.18;margin-bottom:7px}
.similar-body p{color:var(--muted);font-weight:750;font-size:12px;margin-bottom:12px}
.availability-note{
    margin-top:10px;
    padding:12px 14px;
    border-radius:16px;
    background:rgba(234,247,255,.8);
    border:1px solid var(--border);
    color:var(--muted);
    font-size:12.5px;
    font-weight:750;
    line-height:1.55;
}
.detail-empty{
    width:min(760px,calc(100% - 32px));
    margin:80px auto;
    padding:42px;
    text-align:center;
    border-radius:28px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:var(--shadow);
}
.detail-empty i{
    width:70px;
    height:70px;
    border-radius:24px;
    display:grid;
    place-items:center;
    margin:0 auto 18px;
    color:var(--sky600);
    background:var(--sky100);
    font-size:30px;
}

/* Modal for details page */
.detail-modal{
    position:fixed;
    inset:0;
    z-index:999;
    display:none;
    place-items:center;
    background:rgba(13,31,55,.42);
    backdrop-filter:blur(10px);
    padding:18px;
}
.detail-modal.show{display:grid}
.detail-modal-card{
    width:min(780px,100%);
    max-height:92vh;
    overflow-y:auto;
    border-radius:32px;
    background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.94));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 34px 90px rgba(23,48,79,.24);
}
.detail-modal-head{
    padding:24px 26px 16px;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
}
.detail-modal-head h2{font-size:28px;font-weight:950;letter-spacing:-.8px;margin:8px 0 6px}
.detail-modal-head p{color:var(--muted);font-weight:700}
.detail-modal-body{padding:0 26px 26px}
.detail-modal-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.result-card{
    grid-column:1/-1;
    display:none;
    margin-top:6px;
    padding:18px;
    border-radius:22px;
    border:1px solid var(--border);
}
.result-card.show{display:block}
.result-card.success{
    border-color:rgba(20,184,116,.28);
    background:linear-gradient(135deg,#f0fff8,#ffffff);
}
.result-card.danger{
    border-color:rgba(244,67,54,.24);
    background:linear-gradient(135deg,#fff4f2,#ffffff);
}
.result-headline{
    display:flex;
    gap:12px;
    align-items:flex-start;
    margin-bottom:14px;
}
.result-icon{
    width:44px;
    height:44px;
    border-radius:16px;
    display:grid;
    place-items:center;
    color:#fff;
    background:linear-gradient(135deg,#17b26a,#079455);
}
.result-card.danger .result-icon{background:linear-gradient(135deg,#ff6b5f,#d92d20)}
.result-headline h3{font-size:22px;font-weight:950;line-height:1.15}
.result-headline p{color:var(--muted);font-weight:700;line-height:1.45;margin-top:4px}
.result-info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.result-info{
    padding:12px 13px;
    border-radius:15px;
    background:#fff;
    border:1px solid var(--border);
}
.result-info span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:4px;
}
.result-info strong{font-size:13px;font-weight:950}
.result-info.wide{grid-column:1/-1}
.result-actions{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:10px;
    margin-top:12px;
}
.result-actions.two{grid-template-columns:1fr 1fr}
@media(max-width:1120px){
    .detail-hero,.section-grid{grid-template-columns:1fr}
    .trip-grid{grid-template-columns:1fr 1fr}
}
@media(max-width:760px){
    .detail-page{padding:0 14px}
    .detail-hero{padding:14px}
    .main-photo{height:280px}
    .thumbnail-row{grid-template-columns:repeat(3,1fr)}
    .quick-spec-grid,.full-spec-grid,.similar-grid,.detail-modal-grid,.result-info-grid,.result-actions,.result-actions.two,.detail-action-row,.trip-grid{
        grid-template-columns:1fr;
    }
    .trip-mini.wide{grid-column:span 1}
}



/* ===== CLEAN USER-FRIENDLY CAR DETAILS LAYOUT ===== */
.detail-page.clean-detail-page{
    width:min(1280px,100%);
    margin:18px auto 58px;
    padding:0 22px;
}
.clean-detail-layout{
    display:grid;
    grid-template-columns:minmax(0,1.15fr) minmax(360px,.85fr);
    gap:18px;
    align-items:start;
}
.clean-card{
    background:linear-gradient(145deg,rgba(255,255,255,.98),rgba(247,253,255,.94));
    border:1px solid rgba(184,228,255,.95);
    border-radius:26px;
    box-shadow:0 18px 46px rgba(29,109,164,.11);
}
.clean-gallery-card{padding:14px;}
.clean-main-photo{
    position:relative;
    height:460px;
    border-radius:22px;
    overflow:hidden;
    background:linear-gradient(135deg,#edf9ff,#fff);
    border:1px solid var(--border);
}
.clean-main-photo img{width:100%;height:100%;object-fit:cover;display:block;}
.photo-label{
    position:absolute;
    left:16px;
    top:16px;
    z-index:4;
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    color:#fff;
    background:rgba(16,35,61,.62);
    backdrop-filter:blur(10px);
    font-size:11px;
    font-weight:950;
}
.clean-thumbnail-row{
    display:grid;
    grid-template-columns:repeat(5,1fr);
    gap:9px;
    margin-top:10px;
}
.clean-thumb-btn{
    height:76px;
    border:2px solid transparent;
    border-radius:16px;
    overflow:hidden;
    cursor:pointer;
    background:#fff;
    padding:0;
}
.clean-thumb-btn.active{border-color:var(--sky500);box-shadow:0 10px 22px rgba(40,168,234,.18);}
.clean-thumb-btn img{width:100%;height:100%;object-fit:cover;display:block;}
.clean-side-panel{
    padding:22px;
    position:sticky;
    top:84px;
}
.car-heading-row{
    display:flex;
    justify-content:space-between;
    gap:16px;
    align-items:flex-start;
    margin-bottom:12px;
}
.clean-car-title h1{
    font-size:clamp(30px,3.2vw,46px);
    line-height:1.02;
    letter-spacing:-1.25px;
    font-weight:950;
    margin:8px 0 9px;
}
.clean-subtitle{
    color:var(--muted);
    font-size:13px;
    line-height:1.45;
    font-weight:750;
}
.clean-price-box{
    margin:16px 0;
    padding:15px 16px;
    border-radius:20px;
    background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);
    color:#fff;
    box-shadow:0 18px 36px rgba(255,122,26,.25);
}
.clean-price-box span{display:block;font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.6px;opacity:.9;margin-bottom:3px;}
.clean-price-box strong{font-size:30px;font-weight:950;line-height:1;}
.clean-price-box small{font-size:13px;font-weight:900;opacity:.95;}
.best-tags{
    display:flex;
    flex-wrap:wrap;
    gap:8px;
    margin:12px 0 16px;
}
.best-tag{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:8px 10px;
    border-radius:999px;
    background:var(--sky100);
    border:1px solid var(--border);
    color:#24415f;
    font-size:12px;
    font-weight:900;
}
.best-tag i{color:var(--sky600);}
.clean-quick-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:9px;
    margin:14px 0;
}
.clean-quick-item{
    min-height:54px;
    display:flex;
    align-items:center;
    gap:11px;
    padding:12px;
    border-radius:16px;
    background:#fff;
    border:1px solid var(--border);
    color:#2b4969;
    font-size:12.5px;
    font-weight:900;
}
.clean-quick-item i{
    width:30px;
    height:30px;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    border-radius:13px;
    color:var(--sky600);
    background:var(--sky100);
}
.clean-actions{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin-top:14px;
}
.clean-actions .btn{min-height:46px;border-radius:15px;font-size:12px;}
.clean-actions .wide{grid-column:1/-1;}
.clean-section{
    margin-top:18px;
    padding:22px;
}
.section-title-row{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:14px;
    margin-bottom:14px;
}
.section-title-row h2{
    font-size:24px;
    font-weight:950;
    letter-spacing:-.55px;
    line-height:1.1;
    margin-top:6px;
}
.section-title-row p{
    color:var(--muted);
    font-size:13px;
    font-weight:700;
    line-height:1.45;
    margin-top:4px;
}
.clean-trip-grid{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
}
.clean-info-box{
    padding:13px;
    border-radius:16px;
    background:#fff;
    border:1px solid var(--border);
}
.clean-info-box span,
.group-spec span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:4px;
}
.clean-info-box strong,
.group-spec strong{display:block;color:var(--dark);font-size:13px;font-weight:950;line-height:1.3;}
.clean-info-box.wide{grid-column:span 2;}
.content-columns{
    display:grid;
    grid-template-columns:1.05fr .95fr;
    gap:18px;
    margin-top:18px;
}
.why-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
}
.why-card{
    padding:14px;
    border-radius:18px;
    background:#fff;
    border:1px solid var(--border);
}
.why-card i{
    width:36px;
    height:36px;
    display:grid;
    place-items:center;
    border-radius:14px;
    color:var(--sky600);
    background:var(--sky100);
    margin-bottom:10px;
}
.why-card h3{font-size:14px;font-weight:950;margin-bottom:5px;}
.why-card p{color:var(--muted);font-size:12px;font-weight:700;line-height:1.45;}
.spec-group-grid{
    display:grid;
    gap:12px;
}
.spec-group{
    padding:16px;
    border-radius:20px;
    background:#fff;
    border:1px solid var(--border);
}
.spec-group h3{
    display:flex;
    align-items:center;
    gap:9px;
    font-size:16px;
    font-weight:950;
    margin-bottom:12px;
}
.spec-group h3 i{color:var(--sky600);}
.group-spec-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:9px;
}
.group-spec{
    min-height:56px;
    padding:11px 12px;
    border-radius:15px;
    background:linear-gradient(135deg,rgba(234,247,255,.88),rgba(255,255,255,.9));
    border:1px solid rgba(216,236,251,.8);
}
.clean-feature-card{padding:18px;}
.clean-feature-card + .clean-feature-card{margin-top:12px;}
.clean-feature-card h3{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:16px;
    font-weight:950;
    margin-bottom:12px;
}
.clean-feature-card h3 i{color:var(--sky600);}
.clean-feature-list{display:flex;flex-wrap:wrap;gap:8px;}
.clean-feature-pill{
    padding:8px 10px;
    border-radius:999px;
    background:var(--sky100);
    border:1px solid var(--border);
    color:#2b4969;
    font-size:12px;
    font-weight:850;
}
.requirement-list{display:grid;gap:9px;}
.requirement-item{
    display:flex;
    align-items:center;
    gap:10px;
    padding:10px 12px;
    border-radius:15px;
    background:#fff;
    border:1px solid var(--border);
    color:#2b4969;
    font-size:12.5px;
    font-weight:850;
}
.requirement-item i{color:var(--sky600);}
.clean-similar-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:14px;
}
.clean-similar-card{
    overflow:hidden;
    border-radius:22px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:0 14px 34px rgba(29,109,164,.09);
}
.clean-similar-img{height:150px;background:linear-gradient(135deg,#edf9ff,#ffffff);}
.clean-similar-img img{width:100%;height:100%;object-fit:cover;display:block;}
.clean-similar-body{padding:14px;}
.clean-similar-body h3{font-size:17px;font-weight:950;line-height:1.18;margin-bottom:7px;}
.clean-similar-body p{color:var(--muted);font-weight:750;font-size:12px;line-height:1.4;margin-bottom:12px;}
.detail-modal-card{width:min(690px,calc(100% - 24px));border-radius:24px;}
.detail-modal-head{padding:18px 20px 12px;}
.detail-modal-head h2{font-size:23px;margin:6px 0 4px;}
.detail-modal-body{padding:0 20px 20px;}
.detail-modal-grid{gap:9px;}
.detail-modal .input{height:34px;min-height:34px;font-size:12px;}
.detail-modal label{font-size:9px;margin-bottom:4px;}
.result-card{padding:14px;border-radius:18px;}
.result-headline h3{font-size:18px;}
.result-icon{width:38px;height:38px;border-radius:14px;}
.result-info{padding:9px 10px;border-radius:13px;}
.result-actions{gap:8px;}
.result-actions .btn{min-height:38px;font-size:11px;border-radius:12px;}
@media(max-width:1120px){
    .clean-detail-layout,.content-columns{grid-template-columns:1fr;}
    .clean-side-panel{position:static;}
    .clean-trip-grid{grid-template-columns:1fr 1fr;}
}
@media(max-width:760px){
    .detail-page.clean-detail-page{padding:0 14px;}
    .clean-main-photo{height:290px;}
    .clean-thumbnail-row{grid-template-columns:repeat(3,1fr);}
    .clean-quick-grid,.clean-actions,.clean-trip-grid,.why-grid,.group-spec-grid,.clean-similar-grid,.detail-modal-grid,.result-info-grid,.result-actions,.result-actions.two{grid-template-columns:1fr;}
    .clean-info-box.wide{grid-column:span 1;}
    .clean-car-title h1{font-size:34px;}
}


/* ===== FINAL CLEAN ALIGNMENT UPDATE ===== */
.clean-section{
    margin-top:16px;
}
.why-choose-section,
.before-rent-section{
    padding:22px;
}
.why-grid-balanced{
    grid-template-columns:repeat(4,minmax(0,1fr));
    align-items:stretch;
}
.why-grid-balanced .why-card{
    min-height:170px;
    height:100%;
    display:flex;
    flex-direction:column;
}
.why-grid-balanced .why-card p{
    flex:1;
}
.detail-section-grid{
    display:grid;
    grid-template-columns:minmax(0,1.35fr) minmax(330px,.65fr);
    gap:16px;
    align-items:start;
    margin-top:16px;
}
.detail-section-grid .clean-section{
    margin-top:0;
}
.aligned-spec-groups{
    gap:12px;
}
.aligned-spec-groups .spec-group{
    min-height:0;
}
.aligned-feature-stack{
    display:grid;
    gap:12px;
}
.aligned-feature-stack .clean-feature-card{
    margin-top:0!important;
    height:auto;
}
.aligned-feature-stack .clean-feature-card + .clean-feature-card{
    margin-top:0!important;
}
.before-rent-grid{
    display:grid;
    grid-template-columns:repeat(4,minmax(0,1fr));
    gap:12px;
    align-items:stretch;
}
.before-rent-card{
    min-height:132px;
    display:flex;
    align-items:flex-start;
    gap:12px;
    padding:16px;
    border-radius:18px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:0 10px 24px rgba(29,109,164,.06);
}
.before-rent-card i{
    width:38px;
    height:38px;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    border-radius:15px;
    color:var(--sky600);
    background:var(--sky100);
}
.before-rent-card h3{
    font-size:14px;
    font-weight:950;
    margin:0 0 6px;
    line-height:1.2;
}
.before-rent-card p{
    color:var(--muted);
    font-size:12px;
    font-weight:700;
    line-height:1.45;
}
@media(max-width:1120px){
    .why-grid-balanced,
    .before-rent-grid{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
    .detail-section-grid{
        grid-template-columns:1fr;
    }
}
@media(max-width:760px){
    .why-grid-balanced,
    .before-rent-grid{
        grid-template-columns:1fr;
    }
    .before-rent-card{
        min-height:0;
    }
}


/* ===== USER REQUEST UPDATE: cleaner detail flow ===== */
.clean-price-box{
    background:linear-gradient(135deg,#eaf7ff,#ffffff)!important;
    color:var(--dark)!important;
    border:1px solid rgba(40,168,234,.24)!important;
    box-shadow:0 16px 34px rgba(40,168,234,.12)!important;
}
.clean-price-box span{color:var(--sky600)!important;opacity:1!important;}
.clean-price-box small{color:var(--sky600)!important;opacity:1!important;}
.gallery-estimate-card{
    margin-top:14px;
    padding:16px;
    border-radius:22px;
    display:grid;
    grid-template-columns:1fr auto;
    gap:14px;
    align-items:center;
    background:linear-gradient(135deg,#ffffff,#eaf7ff);
    border:1px solid rgba(40,168,234,.20);
    box-shadow:0 14px 30px rgba(40,168,234,.10);
}
.gallery-estimate-label{
    display:inline-flex;
    align-items:center;
    gap:8px;
    color:var(--sky600);
    font-size:11px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:6px;
}
.gallery-estimate-left h3{
    font-size:24px;
    line-height:1.1;
    font-weight:950;
    letter-spacing:-.45px;
    margin-bottom:4px;
    color:var(--dark);
}
.gallery-estimate-left p{
    color:var(--muted);
    font-size:12.5px;
    font-weight:750;
    line-height:1.45;
}
.gallery-estimate-detail{
    min-width:260px;
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
}
.gallery-estimate-detail div{
    padding:10px 12px;
    border-radius:15px;
    background:#fff;
    border:1px solid var(--border);
}
.gallery-estimate-detail span{
    display:block;
    color:var(--muted);
    font-size:9.5px;
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.5px;
    margin-bottom:4px;
}
.gallery-estimate-detail strong{
    display:block;
    color:var(--dark);
    font-size:12px;
    line-height:1.25;
    font-weight:950;
}
.gallery-check-btn{
    width:220px;
    min-height:46px;
    border-radius:16px;
}
.side-action-clean{
    grid-template-columns:1fr 1fr;
}
.side-action-clean .wide{
    grid-column:1/-1;
}
.features-row-section{
    padding:22px;
}
.feature-row-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
    align-items:stretch;
}
.feature-row-card{
    min-height:220px;
    padding:18px;
    border-radius:20px;
    background:#fff;
    border:1px solid var(--border);
    box-shadow:0 12px 28px rgba(29,109,164,.07);
}
.feature-row-card h3{
    display:flex;
    align-items:center;
    gap:10px;
    font-size:16px;
    font-weight:950;
    margin-bottom:14px;
}
.feature-row-card h3 i{
    width:34px;
    height:34px;
    display:grid;
    place-items:center;
    border-radius:14px;
    color:var(--sky600);
    background:var(--sky100);
}
.feature-bullet-list{
    list-style:none;
    display:grid;
    gap:10px;
}
.feature-bullet-list li{
    display:flex;
    align-items:flex-start;
    gap:9px;
    color:#2b4969;
    font-size:12.5px;
    font-weight:850;
    line-height:1.35;
}
.feature-bullet-list li i{
    color:var(--sky600);
    margin-top:2px;
    font-size:12px;
    flex:0 0 auto;
}
.full-spec-section{
    padding:22px;
}
.full-spec-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:14px;
}
.full-spec-grid .spec-group{
    height:100%;
}
.full-spec-grid .group-spec-grid{
    grid-template-columns:1fr;
}
@media(max-width:1120px){
    .feature-row-grid,
    .full-spec-grid{
        grid-template-columns:1fr;
    }
    .gallery-estimate-card{
        grid-template-columns:1fr;
    }
    .gallery-estimate-detail{
        width:100%;
        min-width:0;
    }
    .gallery-check-btn{
        width:100%;
    }
}
@media(max-width:760px){
    .gallery-estimate-detail,
    .side-action-clean{
        grid-template-columns:1fr;
    }
}


/* ===== PHOTO ZOOM + LIVE ESTIMATE UPDATE ===== */
.action-slot:empty{display:none;}
.action-slot .btn{width:100%;}
.photo-zoom-btn{
    position:absolute;
    right:18px;
    top:18px;
    z-index:7;
    min-height:38px;
    padding:0 15px;
    border:1px solid rgba(255,255,255,.75);
    border-radius:999px;
    display:inline-flex;
    align-items:center;
    gap:8px;
    cursor:pointer;
    color:var(--sky600);
    background:rgba(255,255,255,.88);
    box-shadow:0 12px 26px rgba(16,35,61,.14);
    font-size:12px;
    font-weight:950;
    backdrop-filter:blur(12px);
}
.photo-zoom-btn:hover{background:var(--sky600);color:#fff;transform:translateY(-2px);}
.photo-zoom-modal{
    position:fixed;
    inset:0;
    z-index:1200;
    display:none;
    place-items:center;
    padding:26px;
    background:rgba(10,27,48,.72);
    backdrop-filter:blur(12px);
}
.photo-zoom-modal.show{display:grid;}
.photo-zoom-panel{
    position:relative;
    width:min(1100px,96vw);
    height:min(760px,88vh);
    overflow:hidden;
    border-radius:26px;
    background:#ffffff;
    border:1px solid rgba(184,228,255,.6);
    box-shadow:0 34px 90px rgba(0,0,0,.32);
    display:grid;
    place-items:center;
}
.photo-zoom-panel img{
    max-width:100%;
    max-height:100%;
    object-fit:contain;
    transform:scale(1);
    transition:transform .18s ease;
    cursor:grab;
}
.photo-zoom-close,
.photo-zoom-control{
    position:absolute;
    z-index:5;
    width:44px;
    height:44px;
    border:0;
    border-radius:15px;
    display:grid;
    place-items:center;
    cursor:pointer;
    color:var(--sky600);
    background:rgba(255,255,255,.92);
    box-shadow:0 12px 28px rgba(16,35,61,.16);
}
.photo-zoom-close{right:16px;top:16px;}
.zoom-in{right:16px;bottom:118px;}
.zoom-out{right:16px;bottom:68px;}
.zoom-reset{right:16px;bottom:18px;}
.photo-zoom-close:hover,
.photo-zoom-control:hover{background:var(--sky600);color:#fff;}
@media(max-width:760px){
    .photo-zoom-btn{right:14px;top:64px;min-height:34px;font-size:11px;}
    .photo-zoom-modal{padding:14px;}
    .photo-zoom-panel{height:78vh;border-radius:20px;}
}


/* ===== FINAL REQUEST FIX: aligned estimate box, technical specs and 2x4 reminders ===== */
.estimate-card-polished{
    grid-template-columns:minmax(220px,.8fr) minmax(360px,1.15fr) auto!important;
    align-items:stretch!important;
    gap:14px!important;
}
.estimate-card-polished .gallery-estimate-left{
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.estimate-detail-polished{
    min-width:0!important;
    width:100%;
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
}
.estimate-detail-polished div{
    position:relative;
    min-height:66px;
    padding:11px 12px 11px 42px!important;
}
.estimate-detail-polished div i{
    position:absolute;
    left:12px;
    top:50%;
    transform:translateY(-50%);
    width:22px;
    height:22px;
    display:grid;
    place-items:center;
    border-radius:9px;
    color:var(--sky600);
    background:var(--sky100);
    font-size:11px;
}
.gallery-trip-note{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
    min-width:350px;
    align-self:center;
}
.gallery-trip-note div{
    min-height:54px;
    padding:9px 10px;
    border-radius:15px;
    display:flex;
    align-items:center;
    gap:8px;
    background:#fff;
    border:1px solid var(--border);
}
.gallery-trip-note i{
    width:24px;
    height:24px;
    display:grid;
    place-items:center;
    border-radius:10px;
    color:var(--sky600);
    background:var(--sky100);
    font-size:11px;
}
.gallery-trip-note span{
    color:#2b4969;
    font-size:11.5px;
    font-weight:950;
    line-height:1.2;
}
.full-spec-grid .group-spec-grid{
    grid-template-columns:repeat(2,minmax(0,1fr));
}
.full-spec-grid .group-spec{
    min-height:58px;
}
.before-rent-grid{
    grid-template-columns:repeat(4,minmax(0,1fr))!important;
}
.before-rent-card{
    min-height:142px!important;
}
.why-grid-balanced .why-card{
    min-height:185px;
}
@media(max-width:1180px){
    .estimate-card-polished{
        grid-template-columns:1fr!important;
    }
    .gallery-trip-note{
        min-width:0;
        grid-template-columns:repeat(3,minmax(0,1fr));
    }
}
@media(max-width:900px){
    .before-rent-grid{
        grid-template-columns:repeat(2,minmax(0,1fr))!important;
    }
}
@media(max-width:760px){
    .gallery-trip-note,
    .estimate-detail-polished,
    .before-rent-grid{
        grid-template-columns:1fr!important;
    }
}



/* ===== FINAL CLEAN ESTIMATE CARD FIX ===== */
.estimate-card-simple{
    margin-top:16px;
    padding:20px 22px;
    border-radius:22px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(234,247,255,.72));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 14px 34px rgba(40,168,234,.10);
}
.estimate-simple-label{
    display:inline-flex;
    align-items:center;
    gap:8px;
    margin-bottom:8px;
    color:var(--sky600);
    font-size:11px;
    font-weight:950;
    letter-spacing:.6px;
    text-transform:uppercase;
}
.estimate-card-simple h3{
    display:flex;
    align-items:center;
    gap:10px;
    color:var(--dark);
    font-size:28px;
    font-weight:950;
    line-height:1.05;
    margin-bottom:6px;
}
.estimate-card-simple p{
    color:var(--muted);
    font-size:13px;
    font-weight:850;
    line-height:1.45;
}
.estimate-card-simple small{
    display:block;
    margin-top:4px;
    color:#6e8297;
    font-size:11.5px;
    font-weight:800;
}
.estimate-check-icon{
    color:#13b981;
    font-size:18px;
}
.estimate-card-simple .btn{
    width:220px;
    min-height:46px;
    flex:0 0 auto;
}
@media(max-width:760px){
    .estimate-card-simple{display:grid;}
    .estimate-card-simple .btn{width:100%;}
}



/* ===== FINAL FIX: compact availability modal no internal scroll ===== */
.detail-modal{
    padding:10px!important;
    align-items:center!important;
}
.detail-modal-card{
    width:min(680px,calc(100% - 20px))!important;
    max-height:none!important;
    overflow:visible!important;
    border-radius:26px!important;
}
.detail-modal-head{
    padding:14px 20px 8px!important;
}
.detail-modal-head .pill{
    margin-bottom:6px!important;
    padding:5px 10px!important;
    font-size:10px!important;
}
.detail-modal-head h2{
    font-size:22px!important;
    margin:4px 0 3px!important;
    line-height:1.05!important;
}
.detail-modal-head p{
    font-size:13px!important;
    line-height:1.25!important;
}
.detail-modal .close{
    width:36px!important;
    height:36px!important;
    border-radius:13px!important;
}
.detail-modal-body{
    padding:0 20px 16px!important;
}
.detail-modal-grid{
    gap:7px 10px!important;
}
.detail-modal label{
    font-size:8.5px!important;
    margin-bottom:3px!important;
}
.detail-modal .input,
.detail-modal .fixed-time-display{
    height:31px!important;
    min-height:31px!important;
    border-radius:10px!important;
    padding:5px 10px!important;
    font-size:12px!important;
}
.detail-modal .time-combo{
    gap:8px!important;
}
.detail-modal .inline-error{
    padding:8px 11px!important;
    border-radius:12px!important;
    font-size:12px!important;
}
.detail-modal .btn-orange{
    min-height:38px!important;
    border-radius:13px!important;
}
.result-card{
    margin-top:4px!important;
    padding:12px!important;
    border-radius:18px!important;
}
.result-headline{
    gap:9px!important;
    margin-bottom:9px!important;
}
.result-icon{
    width:34px!important;
    height:34px!important;
    border-radius:13px!important;
}
.result-headline h3{
    font-size:18px!important;
    line-height:1.08!important;
}
.result-headline p{
    font-size:12px!important;
    line-height:1.25!important;
    margin-top:2px!important;
}
.result-info-grid{
    gap:7px!important;
}
.result-info{
    padding:8px 10px!important;
    border-radius:13px!important;
}
.result-info span{
    font-size:9px!important;
    margin-bottom:2px!important;
}
.result-info strong{
    font-size:12.5px!important;
    line-height:1.25!important;
}
.result-actions{
    margin-top:9px!important;
    gap:8px!important;
}
.result-actions .btn{
    min-height:34px!important;
    border-radius:11px!important;
    font-size:10.5px!important;
}
@media(max-height:760px){
    .detail-modal-card{transform:scale(.92);transform-origin:center center;}
}
@media(max-height:690px){
    .detail-modal-card{transform:scale(.84);}
}
@media(max-width:760px){
    .detail-modal-card{
        width:min(560px,calc(100% - 16px))!important;
        max-height:calc(100vh - 16px)!important;
        overflow-y:auto!important;
        transform:none!important;
    }
}


/* ===== FINAL FIX: no-trip estimate card no overlap ===== */
.estimate-card-simple.no-estimate{
    grid-template-columns:minmax(0,1fr) 220px!important;
    align-items:center!important;
}
.estimate-card-simple.no-estimate .estimate-simple-content{
    min-width:0!important;
    max-width:100%!important;
}
.estimate-card-simple.no-estimate h3{
    white-space:normal!important;
    max-width:100%!important;
}
.estimate-card-simple.no-estimate p,
.estimate-card-simple.no-estimate small{
    white-space:normal!important;
    max-width:620px!important;
}
.estimate-card-simple.no-estimate #galleryEstimateAction{
    justify-self:end!important;
    width:220px!important;
}
.estimate-card-simple.no-estimate #galleryEstimateAction .btn{
    width:220px!important;
}
@media(max-width:760px){
    .estimate-card-simple.no-estimate{
        grid-template-columns:1fr!important;
    }
    .estimate-card-simple.no-estimate #galleryEstimateAction,
    .estimate-card-simple.no-estimate #galleryEstimateAction .btn{
        width:100%!important;
        justify-self:stretch!important;
    }
}



/* ===== CLEAN FINAL ESTIMATE PRICE LAYOUT (NO STACKED BROKEN BOXES) ===== */
.estimate-card-simple.has-estimate{
    display:grid!important;
    grid-template-columns:minmax(245px,1fr) minmax(330px,350px)!important;
    align-items:center!important;
    gap:16px!important;
    width:100%!important;
    padding:18px 20px!important;
    overflow:hidden!important;
}
.estimate-card-simple.has-estimate .estimate-simple-content{
    min-width:0!important;
    max-width:100%!important;
}
.estimate-card-simple.has-estimate h3{
    display:flex!important;
    align-items:center!important;
    gap:10px!important;
    white-space:nowrap!important;
    font-size:28px!important;
    line-height:1.05!important;
    margin:0 0 7px!important;
    letter-spacing:-.6px!important;
}
.estimate-card-simple.has-estimate p{
    white-space:nowrap!important;
    font-size:13px!important;
    line-height:1.3!important;
    margin:0!important;
}
.estimate-card-simple.has-estimate small{
    display:block!important;
    max-width:300px!important;
    margin-top:8px!important;
    font-size:11.5px!important;
    line-height:1.35!important;
}
.estimate-card-simple.has-estimate #galleryEstimateAction{
    display:none!important;
}
.estimate-card-simple.has-estimate .gallery-estimate-detail,
.estimate-card-simple.has-estimate .estimate-detail-polished{
    width:100%!important;
    min-width:0!important;
    max-width:350px!important;
    display:grid!important;
    grid-template-columns:repeat(2,minmax(0,1fr))!important;
    gap:10px!important;
    justify-self:end!important;
    grid-column:auto!important;
}
.estimate-card-simple.has-estimate .gallery-estimate-detail > div,
.estimate-card-simple.has-estimate .estimate-detail-polished > div{
    position:relative!important;
    min-width:0!important;
    min-height:70px!important;
    padding:13px 12px 13px 46px!important;
    border-radius:16px!important;
    overflow:visible!important;
    display:flex!important;
    flex-direction:column!important;
    justify-content:center!important;
    background:#fff!important;
    border:1px solid var(--border)!important;
}
.estimate-card-simple.has-estimate .gallery-estimate-detail > div i,
.estimate-card-simple.has-estimate .estimate-detail-polished > div i{
    position:absolute!important;
    left:14px!important;
    top:50%!important;
    transform:translateY(-50%)!important;
    width:26px!important;
    height:26px!important;
    display:grid!important;
    place-items:center!important;
    border-radius:11px!important;
    color:var(--sky600)!important;
    background:var(--sky100)!important;
    font-size:12px!important;
    flex:0 0 auto!important;
}
.estimate-card-simple.has-estimate .gallery-estimate-detail > div span,
.estimate-card-simple.has-estimate .estimate-detail-polished > div span{
    display:block!important;
    width:100%!important;
    color:var(--muted)!important;
    font-size:10px!important;
    line-height:1.05!important;
    font-weight:950!important;
    letter-spacing:.35px!important;
    text-transform:uppercase!important;
    margin:0 0 5px!important;
    white-space:normal!important;
    overflow:visible!important;
    text-overflow:clip!important;
}
.estimate-card-simple.has-estimate .gallery-estimate-detail > div strong,
.estimate-card-simple.has-estimate .estimate-detail-polished > div strong{
    display:block!important;
    width:100%!important;
    color:var(--dark)!important;
    font-size:13px!important;
    line-height:1.22!important;
    font-weight:950!important;
    white-space:normal!important;
    overflow:visible!important;
    text-overflow:clip!important;
    word-break:normal!important;
    overflow-wrap:break-word!important;
}
@media(max-width:1160px){
    .estimate-card-simple.has-estimate{
        grid-template-columns:1fr!important;
    }
    .estimate-card-simple.has-estimate .gallery-estimate-detail,
    .estimate-card-simple.has-estimate .estimate-detail-polished{
        max-width:none!important;
        justify-self:stretch!important;
    }
}
@media(max-width:760px){
    .estimate-card-simple.has-estimate .gallery-estimate-detail,
    .estimate-card-simple.has-estimate .estimate-detail-polished{
        grid-template-columns:1fr!important;
    }
    .estimate-card-simple.has-estimate h3,
    .estimate-card-simple.has-estimate p{
        white-space:normal!important;
    }
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
            <li><a href="catalogue.php" class="active"><i class="fa-solid fa-car"></i> CATALOGUE</a></li>
            <li><a href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> FIND CAR SMART</a></li>
            <li><a href="compare_car.php"><i class="fa-solid fa-code-compare"></i> COMPARE CAR</a></li>
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

<?php if(!$car): ?>
    <div class="detail-empty">
        <i class="fa-solid fa-circle-exclamation"></i>
        <h1>Car not found</h1>
        <p>The selected car does not exist or is currently unavailable.</p>
        <br>
        <a class="btn btn-blue" href="catalogue.php"><i class="fa-solid fa-car-side"></i> Back to Catalogue</a>
    </div>
<?php else: ?>
<main class="detail-page clean-detail-page">
    <?php
        $detailBestTags = [];
        $carKeyword = strtolower(($car["car_name"] ?? "") . " " . ($car["category_name"] ?? "") . " " . ($car["brand"] ?? ""));
        if (str_contains($carKeyword, "mpv") || str_contains($carKeyword, "alza") || str_contains($carKeyword, "br-v")) {
            $detailBestTags = ["Family Trip", "7 Seats", "Comfort Ride", "Group Travel"];
        } elseif (str_contains($carKeyword, "luxury") || str_contains($carKeyword, "bmw") || str_contains($carKeyword, "mercedes") || str_contains($carKeyword, "lexus")) {
            $detailBestTags = ["Business Trip", "Luxury Travel", "Premium Comfort", "City Drive"];
        } elseif (str_contains($carKeyword, "pickup") || str_contains($carKeyword, "hilux")) {
            $detailBestTags = ["Outdoor Use", "Strong Utility", "Adventure", "Extra Space"];
        } elseif (str_contains($carKeyword, "suv")) {
            $detailBestTags = ["Family Travel", "Comfort Ride", "Long Trip", "Spacious Cabin"];
        } else {
            $detailBestTags = ["City Drive", "Fuel Saving", "Daily Rental", "Easy Parking"];
        }
    
        $lowerName = strtolower($car["car_name"] ?? "");
        $lowerType = strtolower($car["category_name"] ?? "");
        $whyReasons = [];

        if (str_contains($lowerName, "alphard") || str_contains($lowerName, "vellfire")) {
            $whyReasons = [
                ["icon" => "fa-crown", "title" => "Premium MPV Comfort", "text" => "The spacious cabin and executive seating feel more premium than a normal family MPV."],
                ["icon" => "fa-people-group", "title" => "Perfect for Group Travel", "text" => "It is suitable for family trips, airport pickup, business guests and long-distance travel with passengers."],
                ["icon" => "fa-couch", "title" => "Relaxing Passenger Experience", "text" => "The quiet cabin, comfortable seats and smooth ride help passengers feel less tired during the journey."],
                ["icon" => "fa-route", "title" => "Best for Special Trips", "text" => "A strong choice when customers want a more presentable car for events, VIP pickup or comfortable travel."]
            ];
        } elseif (str_contains($lowerName, "bmw") || str_contains($lowerName, "mercedes") || str_contains($lowerName, "lexus")) {
            $whyReasons = [
                ["icon" => "fa-briefcase", "title" => "Professional Image", "text" => "This car gives a more premium and professional impression for business trips or formal occasions."],
                ["icon" => "fa-gauge-high", "title" => "Confident Performance", "text" => "Its engine, handling and transmission make city driving and highway travel feel smoother and more responsive."],
                ["icon" => "fa-couch", "title" => "Premium Comfort", "text" => "The cabin is more refined, making it suitable for customers who care about comfort and driving feel."],
                ["icon" => "fa-road", "title" => "Great for City & Highway", "text" => "It is a good choice for customers who want a balance of comfort, style and daily usability."]
            ];
        } elseif (str_contains($lowerName, "hilux") || str_contains($lowerType, "pickup")) {
            $whyReasons = [
                ["icon" => "fa-truck-pickup", "title" => "Strong Utility", "text" => "The pickup body is useful for outdoor plans, work use and carrying larger items."],
                ["icon" => "fa-mountain-sun", "title" => "Adventure Ready", "text" => "It is suitable for customers who need a tougher vehicle for mixed road conditions."],
                ["icon" => "fa-shield-halved", "title" => "Practical & Durable", "text" => "The higher body and strong setup make it feel more confident for longer trips."],
                ["icon" => "fa-box-open", "title" => "Extra Load Space", "text" => "The rear deck gives customers more flexibility compared with a normal sedan or hatchback."]
            ];
        } elseif (str_contains($lowerType, "mpv") || str_contains($lowerName, "alza") || str_contains($lowerName, "br-v")) {
            $whyReasons = [
                ["icon" => "fa-people-group", "title" => "Family Friendly", "text" => "The seating layout is suitable for families or groups who need more passenger space."],
                ["icon" => "fa-suitcase-rolling", "title" => "More Practical Space", "text" => "It gives better flexibility for luggage, daily items and longer trips."],
                ["icon" => "fa-couch", "title" => "Comfort for Passengers", "text" => "Passengers can sit more comfortably compared with smaller compact cars."],
                ["icon" => "fa-map-location-dot", "title" => "Good for Travel Plans", "text" => "A practical option for customers planning weekend trips, airport pickup or family outings."]
            ];
        } elseif (str_contains($lowerType, "suv")) {
            $whyReasons = [
                ["icon" => "fa-car-side", "title" => "Balanced Daily SUV", "text" => "It offers a good balance of comfort, cabin height and practicality for daily travel."],
                ["icon" => "fa-road", "title" => "Comfortable Long Drive", "text" => "The higher seating position and larger cabin make longer trips feel easier."],
                ["icon" => "fa-suitcase", "title" => "Useful Boot Space", "text" => "It is suitable for customers carrying luggage, shopping bags or family items."],
                ["icon" => "fa-shield-halved", "title" => "Confident Road Feel", "text" => "The SUV body gives customers a more stable and confident driving feeling."]
            ];
        } else {
            $whyReasons = [
                ["icon" => "fa-gas-pump", "title" => "Easy Daily Rental", "text" => "This car is simple to drive and suitable for normal daily travel."],
                ["icon" => "fa-city", "title" => "Good for City Use", "text" => "It is convenient for city driving, parking and short trips."],
                ["icon" => "fa-wallet", "title" => "Budget Friendly", "text" => "The daily rental price is practical for customers who want to control cost."],
                ["icon" => "fa-car", "title" => "Simple & Reliable", "text" => "A good choice for customers who want a straightforward rental car without unnecessary complexity."]
            ];
        }
    ?>

    <div class="breadcrumb">
        <a href="homepage.php">Home</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="catalogue.php">Catalogue</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span><?= e($car["car_name"]) ?></span>
    </div>

    <section class="clean-detail-layout">
        <div class="clean-card clean-gallery-card">
            <div class="clean-main-photo">
                <span class="photo-label"><i class="fa-solid fa-image"></i> Vehicle Photos</span>
                <img id="mainCarPhoto" src="<?= e($images[0]) ?>" alt="<?= e($car["car_name"]) ?>">
                <button class="photo-zoom-btn" type="button" id="openPhotoZoom"><i class="fa-solid fa-magnifying-glass-plus"></i> Zoom</button>
                <?php if(count($images) > 1): ?>
                    <button class="gallery-nav gallery-prev" type="button" id="galleryPrev"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="gallery-nav gallery-next" type="button" id="galleryNext"><i class="fa-solid fa-chevron-right"></i></button>
                <?php endif; ?>
            </div>

            <?php if(count($images) > 1): ?>
                <div class="clean-thumbnail-row">
                    <?php foreach(array_slice($images, 0, 5) as $index => $image): ?>
                        <button class="clean-thumb-btn thumb-btn <?= $index === 0 ? "active" : "" ?>" type="button" data-index="<?= e($index) ?>">
                            <img src="<?= e($image) ?>" alt="<?= e($car["car_name"]) ?> image <?= e($index + 1) ?>">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="estimate-card-simple <?= ($hasTrip && $days > 0) ? "has-estimate" : "no-estimate" ?>">
                <div class="estimate-simple-content">
                    <span class="estimate-simple-label"><i class="fa-solid fa-calculator"></i> Estimated Rental Price</span>
                    <?php if($hasTrip && $days > 0): ?>
                        <h3 id="estimateTitle">
                            RM <?= e(number_format($estimatedTotal, 2)) ?>
                            <i class="fa-solid fa-circle-check estimate-check-icon"></i>
                        </h3>
                        <p id="estimateText">RM <?= e(number_format((float)$car["price_per_day"], 2)) ?> / day × <?= e($days) ?> day(s)</p>
                        <small id="estimateTripText">The estimated total will appear here after checking availability.</small>
                    <?php else: ?>
                        <h3 id="estimateTitle">Check your trip first</h3>
                        <p id="estimateText">Choose pickup location, return date and time to calculate the rental price for this car.</p>
                        <small id="estimateTripText">The estimated total will appear here after checking availability.</small>
                    <?php endif; ?>
                </div>

                <?php if($hasTrip && $days > 0): ?>
                    <div class="gallery-estimate-detail estimate-detail-polished" id="galleryEstimateDetail">
                        <div>
                            <i class="fa-solid fa-location-dot"></i>
                            <span>Pickup</span>
                            <strong><?= e($locationNameMap[$trip["pickup_location"]] ?? "-") ?></strong>
                        </div>
                        <div>
                            <i class="fa-solid fa-flag-checkered"></i>
                            <span>Drop-off</span>
                            <strong><?= e($locationNameMap[$trip["dropoff_location"]] ?? "-") ?></strong>
                        </div>
                        <div>
                            <i class="fa-solid fa-calendar-days"></i>
                            <span>Pickup Date</span>
                            <strong><?= e(toDateTimeLabel($trip["pickup_date"], $trip["pickup_time"])) ?></strong>
                        </div>
                        <div>
                            <i class="fa-solid fa-clock"></i>
                            <span>Rental Days</span>
                            <strong><?= e($days) ?> day(s)</strong>
                        </div>
                    </div>
                <?php endif; ?>

                <div id="galleryEstimateAction">
                    <?php if(!$hasTrip): ?>
                        <button class="btn btn-orange gallery-check-btn" type="button" id="openCheckModal">
                            <i class="fa-solid fa-calendar-check"></i> Check Availability
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <aside class="clean-card clean-side-panel">
            <div class="clean-car-title">
                <span class="pill"><i class="fa-solid fa-car-side"></i> Vehicle Details</span>
                <h1><?= e($car["car_name"]) ?></h1>
                <p class="clean-subtitle">
                    <?= e($car["brand"]) ?> • <?= e($car["category_name"]) ?> • <?= e($car["transmission"]) ?>
                </p>
            </div>

            <div class="detail-meta">
                <span class="tag"><?= e($car["category_name"]) ?></span>
                <span class="tag orange"><?= e($car["car_tag"] ?: $car["brand"]) ?></span>
                <?php if(!empty($car["state_names"])): ?>
                    <span class="tag"><i class="fa-solid fa-location-dot"></i> <?= e($car["state_names"]) ?></span>
                <?php endif; ?>
            </div>

            <div class="clean-price-box">
                <span>Daily Rental Price</span>
                <strong>RM <?= e(number_format((float)$car["price_per_day"], 2)) ?></strong>
                <small>/ day</small>
            </div>

            <div class="best-tags">
                <?php foreach($detailBestTags as $tagItem): ?>
                    <span class="best-tag"><i class="fa-solid fa-check"></i> <?= e($tagItem) ?></span>
                <?php endforeach; ?>
            </div>

            <div class="clean-quick-grid">
                <div class="clean-quick-item"><i class="fa-solid fa-users"></i> <?= e($car["seats"]) ?> Seats</div>
                <div class="clean-quick-item"><i class="fa-solid fa-gears"></i> <?= e($car["transmission"]) ?></div>
                <div class="clean-quick-item"><i class="fa-solid fa-gas-pump"></i> <?= e($car["fuel_type"]) ?></div>
                <div class="clean-quick-item"><i class="fa-solid fa-gauge-high"></i> <?= e($car["horsepower"]) ?> hp</div>
                <div class="clean-quick-item"><i class="fa-solid fa-screwdriver-wrench"></i> <?= e($car["engine"]) ?></div>
                <div class="clean-quick-item"><i class="fa-solid fa-palette"></i> <?= e($car["car_color"] ?: "Not specified") ?></div>
            </div>

            <p class="clean-subtitle">
                <?= e($car["description"] ?: "This page separates vehicle photos, booking actions, specifications and features clearly so customers can understand the car faster before checking availability.") ?>
            </p>

            <div class="clean-actions side-action-clean">
                <div id="sideAddToCartSlot" class="wide action-slot">
                    <?php if($hasTrip): ?>
                        <a class="btn btn-blue wide" href="add_to_cart.php?car_id=<?= e($car["car_id"]) ?>&<?= e($queryTrip) ?>">
                            <i class="fa-solid fa-cart-plus"></i> Add to Cart
                        </a>
                    <?php endif; ?>
                </div>
                <a class="btn btn-white" href="compare_car.php?add=<?= e($car["car_id"]) ?>">
                    <i class="fa-solid fa-code-compare"></i> Compare
                </a>
                <a class="btn btn-white" href="catalogue.php#catalogueResults">
                    <i class="fa-solid fa-arrow-left"></i> Catalogue
                </a>
            </div>
        </aside>
    </section>

    <section class="clean-card clean-section features-row-section">
        <div class="section-title-row">
            <div>
                <span class="pill"><i class="fa-solid fa-sparkles"></i> Vehicle Features</span>
                <h2>Comfort, Safety & Entertainment</h2>
                <p>Key features are shown first so customers can understand the car before reading the full specifications.</p>
            </div>
        </div>

        <div class="feature-row-grid">
            <section class="feature-row-card">
                <h3><i class="fa-solid fa-shield-halved"></i> Safety Features</h3>
                <ul class="feature-bullet-list">
                    <?php foreach($safetyFeatures as $feature): ?>
                        <li><i class="fa-solid fa-circle-check"></i><span><?= e($feature) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="feature-row-card">
                <h3><i class="fa-solid fa-couch"></i> Comfort Features</h3>
                <ul class="feature-bullet-list">
                    <?php foreach($comfortFeatures as $feature): ?>
                        <li><i class="fa-solid fa-circle-check"></i><span><?= e($feature) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>

            <section class="feature-row-card">
                <h3><i class="fa-solid fa-music"></i> Entertainment Features</h3>
                <ul class="feature-bullet-list">
                    <?php foreach($entertainmentFeatures as $feature): ?>
                        <li><i class="fa-solid fa-circle-check"></i><span><?= e($feature) ?></span></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>
    </section>

    <section class="clean-card clean-section specs-clean-block full-spec-section">
        <div class="section-title-row">
            <div>
                <span class="pill"><i class="fa-solid fa-list-check"></i> Configuration</span>
                <h2>Vehicle Specifications</h2>
                <p>The specifications are grouped clearly so customers can compare performance, practicality and rental information easily.</p>
            </div>
        </div>

        <div class="spec-group-grid aligned-spec-groups full-spec-grid">
            <div class="spec-group">
                <h3><i class="fa-solid fa-gauge-high"></i> Performance</h3>
                <div class="group-spec-grid">
                    <div class="group-spec"><span>Engine</span><strong><?= e($car["engine"]) ?></strong></div>
                    <div class="group-spec"><span>Horsepower</span><strong><?= e($car["horsepower"]) ?> hp</strong></div>
                    <div class="group-spec"><span>0-100 Acceleration</span><strong><?= e($car["acceleration_0_100"] ?: "-") ?></strong></div>
                    <div class="group-spec"><span>Torque</span><strong><?= e($car["torque"]) ?></strong></div>
                    <div class="group-spec"><span>Drivetrain</span><strong><?= e($car["drivetrain"]) ?></strong></div>
                    <div class="group-spec"><span>Transmission</span><strong><?= e($car["transmission"]) ?></strong></div>
                    <div class="group-spec"><span>Fuel Type</span><strong><?= e($car["fuel_type"]) ?></strong></div>
                    <div class="group-spec"><span>Front Brake</span><strong><?= e($car["front_brake"] ?: "-") ?></strong></div>
                    <div class="group-spec"><span>Rear Brake</span><strong><?= e($car["rear_brake"] ?: "-") ?></strong></div>
                    <div class="group-spec"><span>Suspension</span><strong><?= e($car["suspension"] ?: "-") ?></strong></div>
                </div>
            </div>

            <div class="spec-group">
                <h3><i class="fa-solid fa-suitcase-rolling"></i> Practicality</h3>
                <div class="group-spec-grid">
                    <div class="group-spec"><span>Seats</span><strong><?= e($car["seats"]) ?></strong></div>
                    <div class="group-spec"><span>Doors</span><strong><?= e($car["doors"]) ?></strong></div>
                    <div class="group-spec"><span>Luggage Capacity</span><strong><?= e($car["luggage_capacity"] ?: "-") ?></strong></div>
                    <div class="group-spec"><span>Fuel Consumption</span><strong><?= e($car["fuel_consumption"] ?: "-") ?></strong></div>
                </div>
            </div>

            <div class="spec-group">
                <h3><i class="fa-solid fa-circle-info"></i> Rental Info</h3>
                <div class="group-spec-grid">
                    <div class="group-spec"><span>Brand</span><strong><?= e($car["brand"]) ?></strong></div>
                    <div class="group-spec"><span>Model</span><strong><?= e($car["model"] ?: $car["car_name"]) ?></strong></div>
                    <div class="group-spec"><span>Year</span><strong><?= e($car["car_year"] ?: "-") ?></strong></div>
                    <div class="group-spec"><span>Category</span><strong><?= e($car["category_name"]) ?></strong></div>
                    <div class="group-spec"><span>Colour</span><strong><?= e($car["car_color"] ?: "Not specified") ?></strong></div>
                    <div class="group-spec"><span>Price Per Day</span><strong>RM <?= e(number_format((float)$car["price_per_day"], 2)) ?></strong></div>
                </div>
            </div>
        </div>
    </section>

    <section class="clean-card clean-section why-choose-section">
        <div class="section-title-row">
            <div>
                <span class="pill"><i class="fa-solid fa-star"></i> Why Choose</span>
                <h2>Why Choose <?= e($car["car_name"]) ?>?</h2>
                <p>These reasons focus on this vehicle's real rental purpose, comfort and driving suitability.</p>
            </div>
        </div>

        <div class="why-grid why-grid-balanced">
            <?php foreach($whyReasons as $reason): ?>
                <div class="why-card">
                    <i class="fa-solid <?= e($reason["icon"]) ?>"></i>
                    <h3><?= e($reason["title"]) ?></h3>
                    <p><?= e($reason["text"]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="clean-card clean-section before-rent-section">
        <div class="section-title-row">
            <div>
                <span class="pill"><i class="fa-solid fa-clipboard-check"></i> Before You Rent</span>
                <h2>Before You Rent</h2>
                <p>Important rental reminders are placed here so customers know what to prepare before booking.</p>
            </div>
        </div>

        <div class="before-rent-grid">
            <div class="before-rent-card">
                <i class="fa-solid fa-id-card"></i>
                <div>
                    <h3>Valid IC / Passport</h3>
                    <p>Prepare your real identification document for verification during collection.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-id-badge"></i>
                <div>
                    <h3>Driving License</h3>
                    <p>A valid driving license is required before the rental can be approved.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-credit-card"></i>
                <div>
                    <h3>Payment First</h3>
                    <p>The displayed amount is estimated. Final payment will confirm the booking request.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-user-check"></i>
                <div>
                    <h3>Admin Approval</h3>
                    <p>Your booking status will be updated after admin checking and approval.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-clock"></i>
                <div>
                    <h3>Arrive On Time</h3>
                    <p>Please collect and return the vehicle according to the selected rental time.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-camera"></i>
                <div>
                    <h3>Check Car Condition</h3>
                    <p>Inspect the vehicle and report any visible damage before driving away.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-gas-pump"></i>
                <div>
                    <h3>Fuel Reminder</h3>
                    <p>Return the car with the required fuel level based on the rental policy.</p>
                </div>
            </div>
            <div class="before-rent-card">
                <i class="fa-solid fa-phone-volume"></i>
                <div>
                    <h3>Contact Support</h3>
                    <p>Contact KH Car Rental immediately if you face any issue during your rental.</p>
                </div>
            </div>
        </div>
    </section>

    <?php if(!empty($similarCars)): ?>
        <section class="clean-card clean-section">
            <div class="section-title-row">
                <div>
                    <span class="pill"><i class="fa-solid fa-layer-group"></i> Similar Cars</span>
                    <h2>Similar Cars You May Like</h2>
                    <p>Based on the same category and similar daily rental price.</p>
                </div>
                <a class="btn btn-white" href="catalogue.php?category=<?= e($car["category_name"]) ?>#catalogueResults">
                    View More
                </a>
            </div>

            <div class="clean-similar-grid">
                <?php foreach($similarCars as $similar): ?>
                    <?php $similarImage = resolveCarImageSrc($similar["image"] ?? "", $similar["car_name"] ?? "Car Image"); ?>
                    <article class="clean-similar-card">
                        <div class="clean-similar-img">
                            <img src="<?= e($similarImage) ?>" alt="<?= e($similar["car_name"]) ?>">
                        </div>
                        <div class="clean-similar-body">
                            <h3><?= e($similar["car_name"]) ?></h3>
                            <p><?= e($similar["brand"] ?? "-") ?> • <?= e($similar["category_name"] ?? "-") ?> • RM <?= e(number_format((float)$similar["price_per_day"], 2)) ?> / day</p>
                            <a class="btn btn-blue" href="car_details.php?car_id=<?= e($similar["car_id"]) ?><?= $hasTrip ? '&' . e($queryTrip) : '' ?>">
                                <i class="fa-solid fa-circle-info"></i> View Details
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endif; ?>
</main>

<div class="detail-modal" id="checkModal">
    <div class="detail-modal-card">
        <div class="detail-modal-head">
            <div>
                <span class="pill"><i class="fa-solid fa-calendar-check"></i> Check Selected Vehicle</span>
                <h2>Check Availability</h2>
                <p>Check availability for <?= e($car["car_name"]) ?></p>
            </div>
            <button class="close" type="button" id="closeCheckModal"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <div class="detail-modal-body">
            <form class="detail-modal-grid" method="POST" action="check_availability.php" id="detailAvailabilityForm">
                <input type="hidden" name="car_id" value="<?= e($car["car_id"]) ?>">

                <div class="full">
                    <label>Pickup State</label>
                    <select class="input" name="state" id="modalState" required>
                        <option value="">Select State</option>
                        <?php foreach($states as $state): ?>
                            <option value="<?= e($state["state_id"]) ?>" <?= (int)$state["state_id"] === $trip["state"] ? "selected" : "" ?>>
                                <?= e($state["state_name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label>Pickup Location</label>
                    <select class="input" name="pickup_location" id="modalPickupLocation" required>
                        <option value="">Select Pickup Location</option>
                    </select>
                </div>

                <div>
                    <label>Drop-off Location</label>
                    <select class="input" name="dropoff_location" id="modalDropoffLocation" required>
                        <option value="">Select Drop-off Location</option>
                    </select>
                </div>

                <div>
                    <label>Pickup Date</label>
                    <input class="input" type="date" name="pickup_date" id="modalPickupDate" value="<?= e($trip["pickup_date"]) ?>" required>
                </div>

                <div>
                    <label>Pickup Time</label>
                    <div class="time-combo">
                        <select class="input" id="modalPickupHour" required>
                            <option value="">Hour</option>
                        </select>
                        <select class="input" id="modalPickupMinute" required>
                            <option value="">Minute</option>
                        </select>
                    </div>
                    <input type="hidden" name="pickup_time" id="modalPickupTime" value="<?= e($trip["pickup_time"]) ?>" required>
                </div>

                <div>
                    <label>Return Date</label>
                    <input class="input" type="date" name="return_date" id="modalReturnDate" value="<?= e($trip["return_date"]) ?>" required>
                </div>

                <div>
                    <label>Return Time</label>
                    <div class="fixed-time-display" id="modalReturnDisplay">
                        <i class="fa-solid fa-lock"></i>
                        <span>Same as pickup time</span>
                    </div>
                    <input type="hidden" name="return_time" id="modalReturnTime" value="<?= e($trip["return_time"]) ?>" required>
                </div>

                <div class="inline-error" id="modalError"></div>

                <div class="result-card" id="checkResult"></div>

                <div class="full">
                    <button class="btn btn-orange" type="submit" id="checkSubmitBtn">
                        <i class="fa-solid fa-magnifying-glass"></i> Check Now
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php endif; ?>

<div class="photo-zoom-modal" id="photoZoomModal">
    <div class="photo-zoom-panel">
        <button class="photo-zoom-close" type="button" id="closePhotoZoom"><i class="fa-solid fa-xmark"></i></button>
        <button class="photo-zoom-control zoom-out" type="button" id="zoomOutBtn"><i class="fa-solid fa-magnifying-glass-minus"></i></button>
        <button class="photo-zoom-control zoom-in" type="button" id="zoomInBtn"><i class="fa-solid fa-magnifying-glass-plus"></i></button>
        <button class="photo-zoom-control zoom-reset" type="button" id="zoomResetBtn"><i class="fa-solid fa-rotate-left"></i></button>
        <img id="zoomPhoto" src="<?= e($images[0]) ?>" alt="<?= e($car["car_name"]) ?> zoom photo">
    </div>
</div>

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
<button class="back-top" type="button" onclick="window.scrollTo({top:0,behavior:'smooth'})"><i class="fa-solid fa-arrow-up"></i></button>

<script>
const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");
if(avatarBtn && profileDropdown){
    avatarBtn.addEventListener("click", function(event){
        event.stopPropagation();
        profileDropdown.classList.toggle("show");
    });
    document.addEventListener("click", function(){ profileDropdown.classList.remove("show"); });
}

const images = <?= json_encode($images, JSON_UNESCAPED_SLASHES) ?>;
let currentImageIndex = 0;
const mainCarPhoto = document.getElementById("mainCarPhoto");
const thumbButtons = document.querySelectorAll(".thumb-btn");

function showImage(index){
    if(!mainCarPhoto || !images.length) return;
    currentImageIndex = (index + images.length) % images.length;
    mainCarPhoto.src = images[currentImageIndex];
    if(zoomPhoto && photoZoomModal && photoZoomModal.classList.contains("show")) updateZoomPhoto();

    thumbButtons.forEach(btn => btn.classList.remove("active"));
    const activeThumb = document.querySelector(`.thumb-btn[data-index="${currentImageIndex}"]`);
    if(activeThumb) activeThumb.classList.add("active");
}

const galleryPrev = document.getElementById("galleryPrev");
const galleryNext = document.getElementById("galleryNext");
if(galleryPrev) galleryPrev.addEventListener("click", () => showImage(currentImageIndex - 1));
if(galleryNext) galleryNext.addEventListener("click", () => showImage(currentImageIndex + 1));
thumbButtons.forEach(btn => btn.addEventListener("click", () => showImage(Number(btn.dataset.index || 0))));

const photoZoomModal = document.getElementById("photoZoomModal");
const openPhotoZoom = document.getElementById("openPhotoZoom");
const closePhotoZoom = document.getElementById("closePhotoZoom");
const zoomPhoto = document.getElementById("zoomPhoto");
const zoomInBtn = document.getElementById("zoomInBtn");
const zoomOutBtn = document.getElementById("zoomOutBtn");
const zoomResetBtn = document.getElementById("zoomResetBtn");
let zoomScale = 1;

function updateZoomPhoto(){
    if(!zoomPhoto) return;
    zoomPhoto.src = images[currentImageIndex] || (mainCarPhoto ? mainCarPhoto.src : "");
    zoomPhoto.style.transform = `scale(${zoomScale})`;
}
function setZoom(scale){
    zoomScale = Math.min(3, Math.max(1, scale));
    updateZoomPhoto();
}
if(openPhotoZoom && photoZoomModal){
    openPhotoZoom.addEventListener("click", function(){
        setZoom(1);
        updateZoomPhoto();
        photoZoomModal.classList.add("show");
    });
}
if(mainCarPhoto && photoZoomModal){
    mainCarPhoto.addEventListener("dblclick", function(){
        setZoom(1);
        updateZoomPhoto();
        photoZoomModal.classList.add("show");
    });
}
if(closePhotoZoom && photoZoomModal) closePhotoZoom.addEventListener("click", () => photoZoomModal.classList.remove("show"));
if(photoZoomModal){
    photoZoomModal.addEventListener("click", event => {
        if(event.target === photoZoomModal) photoZoomModal.classList.remove("show");
    });
}
if(zoomInBtn) zoomInBtn.addEventListener("click", () => setZoom(zoomScale + 0.25));
if(zoomOutBtn) zoomOutBtn.addEventListener("click", () => setZoom(zoomScale - 0.25));
if(zoomResetBtn) zoomResetBtn.addEventListener("click", () => setZoom(1));
if(zoomPhoto){
    zoomPhoto.addEventListener("wheel", function(event){
        event.preventDefault();
        setZoom(zoomScale + (event.deltaY < 0 ? 0.15 : -0.15));
    }, {passive:false});
}

const checkModal = document.getElementById("checkModal");
const closeCheckModal = document.getElementById("closeCheckModal");
function openAvailabilityModal(){
    if(checkModal) checkModal.classList.add("show");
}
document.addEventListener("click", function(event){
    const opener = event.target.closest("#openCheckModal, .open-check-modal");
    if(opener){
        event.preventDefault();
        openAvailabilityModal();
    }
});
if(closeCheckModal && checkModal) closeCheckModal.addEventListener("click", () => checkModal.classList.remove("show"));
if(checkModal) {
    checkModal.addEventListener("click", event => {
        if(event.target === checkModal) checkModal.classList.remove("show");
    });
}

const locationMap = <?= json_encode($locationMap, JSON_UNESCAPED_UNICODE) ?>;
const savedPickupLocation = "<?= e($trip["pickup_location"]) ?>";
const savedDropoffLocation = "<?= e($trip["dropoff_location"]) ?>";
const savedPickupTime = "<?= e($trip["pickup_time"]) ?>";

const modalState = document.getElementById("modalState");
const modalPickupLocation = document.getElementById("modalPickupLocation");
const modalDropoffLocation = document.getElementById("modalDropoffLocation");
const modalPickupHour = document.getElementById("modalPickupHour");
const modalPickupMinute = document.getElementById("modalPickupMinute");
const modalPickupTime = document.getElementById("modalPickupTime");
const modalReturnTime = document.getElementById("modalReturnTime");
const modalPickupDate = document.getElementById("modalPickupDate");
const modalReturnDate = document.getElementById("modalReturnDate");
const modalReturnDisplay = document.getElementById("modalReturnDisplay");
const detailAvailabilityForm = document.getElementById("detailAvailabilityForm");
const modalError = document.getElementById("modalError");
const checkResult = document.getElementById("checkResult");
const checkSubmitBtn = document.getElementById("checkSubmitBtn");

function showModalError(message){
    if(!modalError) return;
    modalError.textContent = message;
    modalError.classList.add("show");
}

function clearModalError(){
    if(!modalError) return;
    modalError.textContent = "";
    modalError.classList.remove("show");
}

function formatHourLabel(hour){
    const displayHour = hour % 12 === 0 ? 12 : hour % 12;
    const suffix = hour >= 12 ? "PM" : "AM";
    return String(displayHour).padStart(2, "0") + " " + suffix;
}

function formatTimeLabel(time){
    if(!time || !time.includes(":")) return "Same as pickup time";
    const [hourText, minuteText] = time.split(":");
    const hour = Number(hourText);
    return formatHourLabel(hour).replace(" ", ":" + minuteText + " ") + " (Fixed)";
}


function localDateValue(date = new Date()){
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function minimumPickupDateTime(){
    const minimum = new Date();
    minimum.setHours(minimum.getHours() + 1);
    minimum.setSeconds(0, 0);
    return minimum;
}

function addOneDayLocal(dateValue){
    const date = new Date(`${dateValue}T00:00:00`);
    date.setDate(date.getDate() + 1);
    return localDateValue(date);
}

function setupModalDateLimits(){
    if(!modalPickupDate || !modalReturnDate) return;

    const today = localDateValue();
    modalPickupDate.min = today;
    modalReturnDate.min = today;

    function refreshMinimumPickupOptions(){
        const minimum = minimumPickupDateTime();
        const minimumDate = localDateValue(minimum);
        const minimumHour = String(minimum.getHours()).padStart(2, "0");
        const minimumMinute = String(minimum.getMinutes()).padStart(2, "0");

        modalPickupDate.min = minimumDate;

        if(!modalPickupHour || !modalPickupMinute) return;

        Array.from(modalPickupHour.options).forEach(option => {
            if(option.value === ""){
                option.disabled = false;
                return;
            }

            option.disabled = modalPickupDate.value === minimumDate && option.value < minimumHour;
        });

        Array.from(modalPickupMinute.options).forEach(option => {
            if(option.value === ""){
                option.disabled = false;
                return;
            }

            option.disabled = modalPickupDate.value === minimumDate &&
                modalPickupHour.value === minimumHour &&
                option.value < minimumMinute;
        });

        if(modalPickupHour.selectedOptions[0] && modalPickupHour.selectedOptions[0].disabled){
            modalPickupHour.value = "";
            modalPickupMinute.value = "";
            modalPickupTime.value = "";
            modalReturnTime.value = "";
        }

        if(modalPickupMinute.selectedOptions[0] && modalPickupMinute.selectedOptions[0].disabled){
            modalPickupMinute.value = "";
            modalPickupTime.value = "";
            modalReturnTime.value = "";
        }
    }

    window.refreshCarDetailsMinimumPickupOptions = refreshMinimumPickupOptions;

    function updateReturnDateMin(){
        const todayValue = localDateValue(minimumPickupDateTime());
        modalPickupDate.min = todayValue;
        refreshMinimumPickupOptions();

        if(modalPickupDate.value && modalPickupDate.value < todayValue){
            modalPickupDate.value = "";
        }

        if(modalPickupDate.value){
            const minReturn = addOneDayLocal(modalPickupDate.value);
            modalReturnDate.min = minReturn;
            if(modalReturnDate.value && modalReturnDate.value < minReturn){
                modalReturnDate.value = "";
            }
        }else{
            modalReturnDate.min = todayValue;
            if(modalReturnDate.value && modalReturnDate.value < todayValue){
                modalReturnDate.value = "";
            }
        }
    }

    modalPickupDate.addEventListener("change", () => {
        updateReturnDateMin();
        refreshMinimumPickupOptions();
        updateFixedReturnTime();
    });

    modalReturnDate.addEventListener("change", updateReturnDateMin);

    if(modalPickupHour){
        modalPickupHour.addEventListener("change", () => {
            refreshMinimumPickupOptions();
            updateFixedReturnTime();
        });
    }

    if(modalPickupMinute){
        modalPickupMinute.addEventListener("change", () => {
            refreshMinimumPickupOptions();
            updateFixedReturnTime();
        });
    }

    updateReturnDateMin();
    refreshMinimumPickupOptions();
}

function buildTimeSelects(){
    if(!modalPickupHour || !modalPickupMinute) return;

    modalPickupHour.innerHTML = '<option value="">Hour</option>';
    for(let hour = 0; hour < 24; hour++){
        const value = String(hour).padStart(2, "0");
        modalPickupHour.add(new Option(formatHourLabel(hour), value));
    }

    modalPickupMinute.innerHTML = '<option value="">Minute</option>';
    for(let minute = 0; minute < 60; minute += 5){
        const value = String(minute).padStart(2, "0");
        modalPickupMinute.add(new Option(value, value));
    }

    if(savedPickupTime && savedPickupTime.includes(":")){
        const [h, m] = savedPickupTime.split(":");
        modalPickupHour.value = h;
        modalPickupMinute.value = m;
    }

    if(typeof window.refreshCarDetailsMinimumPickupOptions === "function"){
        window.refreshCarDetailsMinimumPickupOptions();
    }
}

function updateFixedReturnTime(){
    if(!modalPickupHour || !modalPickupMinute || !modalPickupTime || !modalReturnTime || !modalReturnDisplay) return;

    if(modalPickupHour.value !== "" && modalPickupMinute.value !== ""){
        const selectedTime = modalPickupHour.value + ":" + modalPickupMinute.value;
        modalPickupTime.value = selectedTime;
        modalReturnTime.value = selectedTime;
        modalReturnDisplay.querySelector("span").textContent = formatTimeLabel(selectedTime);
    }else{
        modalPickupTime.value = "";
        modalReturnTime.value = "";
        modalReturnDisplay.querySelector("span").textContent = "Same as pickup time";
    }
}

function loadModalLocations(){
    if(!modalState || !modalPickupLocation || !modalDropoffLocation) return;
    const selectedState = modalState.value;
    const locations = locationMap[selectedState] || [];

    modalPickupLocation.innerHTML = '<option value="">Select Pickup Location</option>';
    modalDropoffLocation.innerHTML = '<option value="">Select Drop-off Location</option>';

    locations.forEach(location => {
        modalPickupLocation.add(new Option(location.name, location.id));
        modalDropoffLocation.add(new Option(location.name, location.id));
    });

    if(savedPickupLocation) modalPickupLocation.value = savedPickupLocation;
    if(savedDropoffLocation) modalDropoffLocation.value = savedDropoffLocation;
}

function resultInfo(label, value, wide = false){
    return `<div class="result-info ${wide ? "wide" : ""}"><span>${label}</span><strong>${value || "-"}</strong></div>`;
}

function money(value){
    return `RM ${Number(value || 0).toFixed(2)}`;
}

function formatEstimateDate(value, fallbackDate = "", fallbackTime = ""){
    let raw = (value || "").toString().trim();
    if(!raw && fallbackDate){
        raw = `${fallbackDate} ${fallbackTime || ""}`.trim();
    }
    if(!raw) return "-";

    const normalized = raw.replace(" ", "T");
    const date = new Date(normalized);
    if(!Number.isNaN(date.getTime())){
        const day = String(date.getDate()).padStart(2,"0");
        const month = date.toLocaleString("en-US", {month:"short"});
        const year = date.getFullYear();
        let hour = date.getHours();
        const minute = String(date.getMinutes()).padStart(2,"0");
        const ampm = hour >= 12 ? "PM" : "AM";
        hour = hour % 12 || 12;
        return `${day} ${month} ${year}, ${String(hour).padStart(2,"0")}:${minute} ${ampm}`;
    }

    return raw;
}

function updatePageTripEstimate(data, addToCartQuery){
    const estimateTitle = document.getElementById("estimateTitle");
    const estimateText = document.getElementById("estimateText");
    const estimateTripText = document.getElementById("estimateTripText");
    const estimateAction = document.getElementById("galleryEstimateAction");
    const estimateDetail = document.getElementById("galleryEstimateDetail");
    const sideSlot = document.getElementById("sideAddToCartSlot");
    const estimateCard = document.querySelector(".estimate-card-simple");
    const total = money(data.estimated_total || 0);
    const daily = money(data.price_per_day || <?= json_encode((float)($car["price_per_day"] ?? 0)) ?>);
    const days = data.rental_days || 1;

    if(estimateCard){
        estimateCard.classList.remove("no-estimate");
        estimateCard.classList.add("has-estimate");
    }
    if(estimateTitle) estimateTitle.innerHTML = `${total} <i class="fa-solid fa-circle-check estimate-check-icon"></i>`;
    if(estimateText) estimateText.textContent = `${daily} / day × ${days} day(s)`;
    if(estimateTripText) estimateTripText.textContent = "The estimated total will appear here after checking availability.";

    const pickupDateLabel = data.pickup_label || formatEstimateDate(data.pickup_datetime, data.pickup_date, data.pickup_time);

    const detailHtml = `
        <div><i class="fa-solid fa-location-dot"></i><span>Pickup</span><strong>${data.pickup_location_name || "-"}</strong></div>
        <div><i class="fa-solid fa-flag-checkered"></i><span>Drop-off</span><strong>${data.dropoff_location_name || "-"}</strong></div>
        <div><i class="fa-solid fa-calendar-days"></i><span>Pickup Date</span><strong>${pickupDateLabel}</strong></div>
        <div><i class="fa-solid fa-clock"></i><span>Rental Days</span><strong>${days} day(s)</strong></div>
    `;

    if(estimateDetail){
        estimateDetail.innerHTML = detailHtml;
        estimateDetail.style.display = "grid";
    }else if(estimateAction){
        const newDetail = document.createElement("div");
        newDetail.className = "gallery-estimate-detail estimate-detail-polished";
        newDetail.id = "galleryEstimateDetail";
        newDetail.innerHTML = detailHtml;
        estimateAction.before(newDetail);
    }

    const addBtn = `<a class="btn btn-blue wide" href="add_to_cart.php?${addToCartQuery}"><i class="fa-solid fa-cart-plus"></i> Add to Cart</a>`;
    if(sideSlot) sideSlot.innerHTML = addBtn;
    if(estimateAction) estimateAction.innerHTML = "";
}

function renderAvailable(data){
    const addToCartQuery = new URLSearchParams({
        car_id: data.car_id || document.querySelector('input[name="car_id"]').value,
        state: data.state_id || data.state || document.getElementById('modalState').value,
        pickup_location: data.pickup_location_id || data.pickup_location || document.getElementById('modalPickupLocation').value,
        dropoff_location: data.dropoff_location_id || data.dropoff_location || document.getElementById('modalDropoffLocation').value,
        pickup_date: data.pickup_date || document.getElementById('modalPickupDate').value,
        pickup_time: data.pickup_time || document.getElementById('modalPickupTime').value,
        return_date: data.return_date || document.getElementById('modalReturnDate').value,
        return_time: data.return_time || document.getElementById('modalReturnTime').value
    }).toString();

    updatePageTripEstimate(data, addToCartQuery);

    if(!checkResult) return;
    checkResult.className = "result-card success show";
    checkResult.innerHTML = `
        <div class="result-headline">
            <div class="result-icon"><i class="fa-solid fa-check"></i></div>
            <div>
                <h3>Available for your trip</h3>
                <p>${data.car_name} is available for your selected date and location.</p>
            </div>
        </div>
        <div class="result-info-grid">
            ${resultInfo("Pickup", data.pickup_location_name)}
            ${resultInfo("Drop-off", data.dropoff_location_name)}
            ${resultInfo("Rental Period & Days", `${data.pickup_label} → ${data.return_label} ; ${data.rental_days} day(s)`, true)}
            ${resultInfo("Estimated Rental Total", `RM ${Number(data.estimated_total || 0).toFixed(2)}`)}
        </div>
        <div class="result-actions">
            <a class="btn btn-blue" href="add_to_cart.php?${addToCartQuery}"><i class="fa-solid fa-cart-plus"></i> Add to Cart</a>
            <a class="btn btn-white" href="available_cars.php?${addToCartQuery}"><i class="fa-solid fa-layer-group"></i> View Cars for This Trip</a>
            <button class="btn btn-white" type="button" onclick="document.getElementById('checkResult').className='result-card';document.getElementById('checkResult').innerHTML='';document.getElementById('checkSubmitBtn').style.display='inline-flex';"><i class="fa-solid fa-calendar-days"></i> Choose Another Date</button>
        </div>
    `;
    if(checkSubmitBtn) checkSubmitBtn.style.display = "none";
}

function renderUnavailable(data){
    const searchQuery = new URLSearchParams({
        state: data.state_id || modalState.value,
        pickup_location: data.pickup_location_id || modalPickupLocation.value,
        dropoff_location: data.dropoff_location_id || modalDropoffLocation.value,
        pickup_date: data.pickup_date || document.getElementById("modalPickupDate").value,
        pickup_time: data.pickup_time || modalPickupTime.value,
        return_date: data.return_date || document.getElementById("modalReturnDate").value,
        return_time: data.return_time || modalReturnTime.value,
        category: "<?= e($car["category_name"] ?? "All") ?>"
    }).toString();

    if(!checkResult) return;
    checkResult.className = "result-card danger show";
    checkResult.innerHTML = `
        <div class="result-headline">
            <div class="result-icon"><i class="fa-solid fa-xmark"></i></div>
            <div>
                <h3>Not Available</h3>
                <p>${data.car_name || "This car"} is not available for your selected date and location.</p>
            </div>
        </div>
        <div class="result-actions two">
            <button class="btn btn-white" type="button" onclick="document.getElementById('checkResult').className='result-card';document.getElementById('checkResult').innerHTML='';document.getElementById('checkSubmitBtn').style.display='inline-flex';"><i class="fa-solid fa-calendar-days"></i> Choose Another Date</button>
            <a class="btn btn-blue" href="available_cars.php?${searchQuery}"><i class="fa-solid fa-layer-group"></i> View Similar Available Cars</a>
        </div>
    `;
    if(checkSubmitBtn) checkSubmitBtn.style.display = "none";
}

buildTimeSelects();
loadModalLocations();
updateFixedReturnTime();
setupModalDateLimits();

if(modalState) modalState.addEventListener("change", () => {
    modalPickupLocation.dataset.touched = "1";
    modalDropoffLocation.dataset.touched = "1";
    loadModalLocations();
});

if(modalPickupHour) modalPickupHour.addEventListener("change", () => {
    if(typeof window.refreshCarDetailsMinimumPickupOptions === "function") window.refreshCarDetailsMinimumPickupOptions();
    updateFixedReturnTime();
});
if(modalPickupMinute) modalPickupMinute.addEventListener("change", () => {
    if(typeof window.refreshCarDetailsMinimumPickupOptions === "function") window.refreshCarDetailsMinimumPickupOptions();
    updateFixedReturnTime();
});

if(detailAvailabilityForm){
    detailAvailabilityForm.addEventListener("submit", async function(event){
        event.preventDefault();
        clearModalError();
        updateFixedReturnTime();

        if(!modalState.value || !modalPickupLocation.value || !modalDropoffLocation.value || !modalPickupDate.value || !modalPickupTime.value || !modalReturnDate.value || !modalReturnTime.value){
            showModalError("Please complete all pickup, drop-off, date and time fields.");
            return;
        }

        const todayValue = localDateValue();
        if(modalPickupDate.value < todayValue){
            modalPickupDate.classList.add("error");
            showModalError("Pickup date cannot be earlier than today.");
            return;
        }

        const minReturnDate = addOneDayLocal(modalPickupDate.value);
        if(modalReturnDate.value < minReturnDate){
            modalReturnDate.classList.add("error");
            showModalError("Return date must be at least the next day. Past dates are not allowed.");
            return;
        }

        const pickupStart = new Date(modalPickupDate.value + "T" + modalPickupTime.value + ":00");
        const returnEnd = new Date(modalReturnDate.value + "T" + modalReturnTime.value + ":00");

        if(pickupStart < minimumPickupDateTime()){
            modalPickupDate.classList.add("error");
            modalPickupTime.classList.add("error");
            showModalError("Pickup time must be at least 1 hour from now.");
            return;
        }

        if(returnEnd <= pickupStart){
            showModalError("Return date must be after pickup date. Minimum rental period is 1 day.");
            return;
        }

        const originalText = checkSubmitBtn ? checkSubmitBtn.innerHTML : "";
        if(checkSubmitBtn){
            checkSubmitBtn.disabled = true;
            checkSubmitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Checking...';
        }

        try{
            const formData = new FormData(detailAvailabilityForm);
            const response = await fetch("check_availability.php", {
                method:"POST",
                body:formData,
                headers:{"X-Requested-With":"XMLHttpRequest"}
            });

            const responseText = await response.text();
            let data = null;

            try{
                data = JSON.parse(responseText);
            }catch(parseError){
                showModalError("Check availability failed because the server did not return JSON. Please check check_availability.php.");
                console.error("check_availability.php response:", responseText);
                return;
            }

            if(!data.ok){
                showModalError(data.message || "Unable to check availability.");
                return;
            }

            if(data.available){
                renderAvailable(data);
            }else{
                renderUnavailable(data);
            }
        }catch(error){
            showModalError("System error. Please try again.");
        }finally{
            if(checkSubmitBtn){
                checkSubmitBtn.disabled = false;
                checkSubmitBtn.innerHTML = originalText;
            }
        }
    });
}
</script>
</body>
</html>
