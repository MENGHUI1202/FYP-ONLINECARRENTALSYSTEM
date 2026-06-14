<?php
error_reporting(E_ALL);
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

function resolveCarImageSrc($imagePath, $carName = "Car Image") {
    $imagePath = trim((string)$imagePath);

    if($imagePath !== "" && preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    if($imagePath !== "") {
        $localPath = __DIR__ . "/" . ltrim($imagePath, "/");
        if(is_file($localPath)) {
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
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
}

function rentalDays($start, $end) {
    $startTime = strtotime($start);
    $endTime = strtotime($end);
    if(!$startTime || !$endTime || $endTime <= $startTime) return 0;
    return max(1, (int)ceil(($endTime - $startTime) / 86400));
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

function availableUnitCount($conn, $carId, $stateId, $startDateTime, $endDateTime) {
    if(!tableExists($conn, "car_units")) return 1;

    $unitIdCol = firstColumn($conn, "car_units", ["unit_id", "id"], "unit_id");
    $unitCarCol = firstColumn($conn, "car_units", ["car_id"], "car_id");
    $unitStateCol = firstColumn($conn, "car_units", ["state_id"], null);
    $unitStatusCol = firstColumn($conn, "car_units", ["current_status", "status"], null);

    $where = "cu.`$unitCarCol` = " . (int)$carId;

    if($unitStateCol) {
        $where .= " AND cu.`$unitStateCol` = " . (int)$stateId;
    }

    if($unitStatusCol) {
        $where .= " AND LOWER(COALESCE(cu.`$unitStatusCol`, 'available')) NOT IN ('maintenance','inactive')";
    }

    if(tableExists($conn, "booking_items") && columnExists($conn, "booking_items", "start_datetime") && columnExists($conn, "booking_items", "end_datetime")) {
        $biUnitCol = firstColumn($conn, "booking_items", ["unit_id"], null);
        $biCarCol = firstColumn($conn, "booking_items", ["car_id"], "car_id");

        $safeStart = $conn->real_escape_string($startDateTime);
        $safeEnd = $conn->real_escape_string($endDateTime);

        $overlapMatch = $biUnitCol
            ? "(bi.`$biUnitCol` = cu.`$unitIdCol` OR (bi.`$biUnitCol` IS NULL AND bi.`$biCarCol` = cu.`$unitCarCol`))"
            : "bi.`$biCarCol` = cu.`$unitCarCol`";

        $where .= "
            AND NOT EXISTS (
                SELECT 1
                FROM booking_items bi
                WHERE $overlapMatch
                AND '$safeStart' < bi.end_datetime
                AND '$safeEnd' > bi.start_datetime
            )
        ";
    }

    $row = fetchOne($conn, "SELECT COUNT(*) AS total FROM car_units cu WHERE $where");
    return (int)($row["total"] ?? 0);
}

function hasText($text, $needle) {
    return strpos(strtolower((string)$text), strtolower((string)$needle)) !== false;
}

function scoreCar($car, $answers) {
    $score = 0;
    $reasons = [];

    $category = strtolower((string)($car["category_name"] ?? ""));
    $brand = strtolower((string)($car["brand"] ?? ""));
    $seats = (int)($car["seats"] ?? 5);
    $price = (float)($car["price_per_day"] ?? 0);
    $fuel = strtolower((string)($car["fuel_type"] ?? ""));
    $hp = (int)($car["horsepower"] ?? 0);

    if(($answers["passengers"] ?? "") === "1-2") {
        if($seats <= 5) { $score += 16; $reasons[] = "Good size for 1–2 passengers."; }
    } elseif(($answers["passengers"] ?? "") === "3-5") {
        if($seats >= 5) { $score += 16; $reasons[] = "Comfortable for 3–5 passengers."; }
    } elseif(($answers["passengers"] ?? "") === "6-7") {
        if($seats >= 7 || hasText($category, "mpv")) { $score += 16; $reasons[] = "Suitable seating capacity for group travel."; }
        elseif(hasText($category, "suv")) $score += 8;
    }

    if(($answers["budget"] ?? "") === "below150") {
        if($price < 150) { $score += 16; $reasons[] = "Matches your low daily budget."; }
        elseif($price <= 200) $score += 7;
    } elseif(($answers["budget"] ?? "") === "150-250") {
        if($price >= 150 && $price <= 250) { $score += 16; $reasons[] = "Fits your RM150–RM250 budget."; }
        elseif($price <= 300) $score += 7;
    } elseif(($answers["budget"] ?? "") === "250-400") {
        if($price >= 250 && $price <= 400) { $score += 16; $reasons[] = "Fits your RM250–RM400 budget."; }
        elseif($price <= 450) $score += 7;
    } elseif(($answers["budget"] ?? "") === "above400") {
        if($price > 400) { $score += 16; $reasons[] = "Suitable for a premium budget."; }
        elseif($price >= 300) $score += 7;
    }

    $purpose = $answers["purpose"] ?? "";
    if($purpose === "city" && (hasText($category, "sedan") || hasText($category, "hatch"))) {
        $score += 14; $reasons[] = "Suitable for city driving.";
    } elseif($purpose === "family" && (hasText($category, "mpv") || hasText($category, "suv") || $seats >= 7)) {
        $score += 14; $reasons[] = "Good fit for family trips.";
    } elseif($purpose === "business" && (hasText($category, "sedan") || hasText($category, "luxury") || hasText($brand, "bmw") || hasText($brand, "mercedes") || hasText($brand, "lexus"))) {
        $score += 14; $reasons[] = "Professional look for business use.";
    } elseif($purpose === "long" && (hasText($category, "suv") || hasText($category, "sedan") || hasText($category, "mpv"))) {
        $score += 14; $reasons[] = "Comfortable for long-distance travel.";
    } elseif($purpose === "luxury" && (hasText($category, "luxury") || hasText($category, "sport") || hasText($brand, "bmw") || hasText($brand, "mercedes") || hasText($brand, "lexus"))) {
        $score += 14; $reasons[] = "Premium choice for luxury experience.";
    } elseif($purpose === "outdoor" && (hasText($category, "pickup") || hasText($category, "suv") || hasText($category, "mpv"))) {
        $score += 14; $reasons[] = "Practical for outdoor or luggage-heavy trips.";
    }

    $priority = $answers["priority"] ?? "";
    if($priority === "price" && $price <= 180) { $score += 10; $reasons[] = "Lower daily rental cost."; }
    if($priority === "fuel" && (hasText($fuel, "hybrid") || hasText($fuel, "ev") || hasText($category, "sedan") || hasText($category, "hatch"))) { $score += 10; $reasons[] = "Better fuel-saving choice."; }
    if($priority === "comfort" && (hasText($category, "suv") || hasText($category, "mpv") || hasText($category, "luxury"))) { $score += 10; $reasons[] = "Comfort-focused vehicle type."; }
    if($priority === "space" && ($seats >= 7 || hasText($category, "mpv") || hasText($category, "suv") || hasText($category, "pickup"))) { $score += 10; $reasons[] = "More cabin or luggage space."; }
    if($priority === "performance" && ($hp >= 180 || hasText($category, "sport") || hasText($category, "luxury"))) { $score += 10; $reasons[] = "Better performance for driving feel."; }
    if($priority === "premium" && (hasText($category, "luxury") || hasText($brand, "bmw") || hasText($brand, "mercedes") || hasText($brand, "lexus"))) { $score += 10; $reasons[] = "Premium appearance matches your preference."; }

    $luggage = $answers["luggage"] ?? "";
    if($luggage === "no") { $score += 6; }
    if($luggage === "medium" && (hasText($category, "sedan") || hasText($category, "suv") || hasText($category, "mpv"))) { $score += 6; $reasons[] = "Enough space for medium luggage."; }
    if($luggage === "large" && (hasText($category, "suv") || hasText($category, "mpv") || hasText($category, "pickup"))) { $score += 6; $reasons[] = "Suitable for larger luggage."; }

    $road = $answers["road_type"] ?? "";
    if($road === "city" && (hasText($category, "sedan") || hasText($category, "hatch"))) { $score += 7; $reasons[] = "Well suited for city roads."; }
    if($road === "highway" && (hasText($category, "sedan") || hasText($category, "suv") || hasText($category, "luxury"))) { $score += 7; $reasons[] = "Stable choice for highway travel."; }
    if($road === "mixed" && (hasText($category, "suv") || hasText($category, "mpv") || hasText($category, "sedan"))) { $score += 7; $reasons[] = "Flexible for mixed routes."; }
    if($road === "rough" && (hasText($category, "suv") || hasText($category, "pickup"))) { $score += 7; $reasons[] = "More practical for rougher routes."; }

    $drive = $answers["driving_style"] ?? "";
    if($drive === "easy" && (hasText($category, "sedan") || hasText($category, "hatch"))) { $score += 7; $reasons[] = "Easy to drive and park."; }
    if($drive === "smooth" && (hasText($category, "suv") || hasText($category, "sedan") || hasText($category, "mpv"))) { $score += 7; $reasons[] = "Good for smooth and relaxed driving."; }
    if($drive === "power" && ($hp >= 180 || hasText($category, "sport") || hasText($category, "luxury"))) { $score += 7; $reasons[] = "Better power for a responsive drive."; }
    if($drive === "premium" && (hasText($category, "luxury") || hasText($brand, "bmw") || hasText($brand, "mercedes") || hasText($brand, "lexus"))) { $score += 7; $reasons[] = "Matches a premium driving feel."; }

    $fuelPref = $answers["fuel_pref"] ?? "";
    if($fuelPref === "fuel_saving" && (hasText($fuel, "hybrid") || hasText($fuel, "ev") || hasText($category, "sedan") || hasText($category, "hatch"))) { $score += 5; $reasons[] = "Better match for fuel-saving use."; }
    if($fuelPref === "petrol" && hasText($fuel, "petrol")) { $score += 5; $reasons[] = "Matches your petrol preference."; }
    if($fuelPref === "ev_hybrid" && (hasText($fuel, "ev") || hasText($fuel, "hybrid"))) { $score += 5; $reasons[] = "Matches your EV / hybrid preference."; }
    if($fuelPref === "no_preference") { $score += 3; }

    $brandPref = $answers["brand_pref"] ?? "";
    if($brandPref === "local" && (hasText($brand, "perodua") || hasText($brand, "proton"))) { $score += 5; $reasons[] = "Matches your local-brand preference."; }
    if($brandPref === "japanese" && (hasText($brand, "toyota") || hasText($brand, "honda") || hasText($brand, "mazda") || hasText($brand, "nissan") || hasText($brand, "lexus"))) { $score += 5; $reasons[] = "Matches your Japanese-brand preference."; }
    if($brandPref === "european" && (hasText($brand, "bmw") || hasText($brand, "mercedes") || hasText($brand, "volkswagen"))) { $score += 5; $reasons[] = "Matches your European-brand preference."; }
    if($brandPref === "no_preference") { $score += 3; }

    $feature = $answers["feature"] ?? "";
    if($feature === "safety" && (hasText($category, "suv") || hasText($category, "sedan") || hasText($category, "luxury"))) { $score += 5; $reasons[] = "Good fit for safety-focused rental."; }
    if($feature === "comfort" && (hasText($category, "suv") || hasText($category, "mpv") || hasText($category, "luxury"))) { $score += 5; $reasons[] = "Good fit for comfort-focused rental."; }
    if($feature === "style" && (hasText($category, "luxury") || hasText($category, "sport") || hasText($brand, "bmw") || hasText($brand, "mercedes") || hasText($brand, "lexus"))) { $score += 5; $reasons[] = "Strong match for style and image."; }
    if($feature === "practical" && (hasText($category, "mpv") || hasText($category, "suv") || hasText($category, "pickup") || hasText($category, "sedan"))) { $score += 5; $reasons[] = "Practical for everyday rental needs."; }

    if(!$reasons) $reasons[] = "Available for your selected trip.";
    return [min(100, $score), array_values(array_unique($reasons))];
}

$user = null;
if(!empty($_SESSION["user_id"]) && tableExists($conn, "users")) {
    $userIdCol = firstColumn($conn, "users", ["user_id", "id"], "user_id");
    $userId = (int)$_SESSION["user_id"];
    $user = fetchOne($conn, "SELECT * FROM users WHERE `$userIdCol` = $userId LIMIT 1");
}

$navCartCount = getNavCartCount($conn);

$states = [];
if(tableExists($conn, "rental_states")) {
    $stateIdCol = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
    $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
    $states = fetchRows($conn, "SELECT `$stateIdCol` AS state_id, `$stateNameCol` AS state_name FROM rental_states ORDER BY `$stateNameCol` ASC");
}

if(!$states) {
    $states = [
        ["state_id" => 1, "state_name" => "Johor"],
        ["state_id" => 2, "state_name" => "Melaka"],
        ["state_id" => 3, "state_name" => "Kuala Lumpur"]
    ];
}

$locations = [];
if(tableExists($conn, "rental_locations")) {
    $locationIdCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
    $locationNameCol = firstColumn($conn, "rental_locations", ["location_name", "name"], "location_name");
    $locationStateCol = firstColumn($conn, "rental_locations", ["state_id"], "state_id");
    $locations = fetchRows($conn, "SELECT `$locationIdCol` AS location_id, `$locationNameCol` AS location_name, `$locationStateCol` AS state_id FROM rental_locations ORDER BY `$locationStateCol` ASC, `$locationNameCol` ASC");
}

if(!$locations) {
    $locations = [
        ["location_id" => 1, "location_name" => "JB Sentral", "state_id" => 1],
        ["location_id" => 2, "location_name" => "Johor Bahru City Centre", "state_id" => 1],
        ["location_id" => 3, "location_name" => "Bukit Indah", "state_id" => 1],
        ["location_id" => 4, "location_name" => "Melaka Sentral", "state_id" => 2],
        ["location_id" => 5, "location_name" => "MMU Melaka", "state_id" => 2],
        ["location_id" => 6, "location_name" => "Jonker Street", "state_id" => 2],
        ["location_id" => 7, "location_name" => "KL Sentral", "state_id" => 3],
        ["location_id" => 8, "location_name" => "Bukit Bintang", "state_id" => 3],
        ["location_id" => 9, "location_name" => "Cheras", "state_id" => 3]
    ];
}

$cars = [];
if(tableExists($conn, "cars")) {
    $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
    $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
    $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
    $imageCol = firstColumn($conn, "cars", ["image", "main_image", "car_image", "image_url"], null);
    $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
    $brandCol = firstColumn($conn, "cars", ["brand", "brand_name"], null);
    $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
    $categoryCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
    $yearCol = firstColumn($conn, "cars", ["year", "car_year"], null);
    $seatsCol = firstColumn($conn, "cars", ["seats"], null);
    $transmissionCol = firstColumn($conn, "cars", ["transmission"], null);
    $fuelCol = firstColumn($conn, "cars", ["fuel_type"], null);
    $engineCol = firstColumn($conn, "cars", ["engine", "engine_capacity"], null);
    $horsepowerCol = firstColumn($conn, "cars", ["horsepower", "hp"], null);
    $drivetrainCol = firstColumn($conn, "cars", ["drivetrain"], null);
    $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);

    $select = [
        "c.`$carIdCol` AS car_id",
        "c.`$carNameCol` AS car_name",
        ($priceCol ? "c.`$priceCol`" : "0") . " AS price_per_day",
        ($imageCol ? "c.`$imageCol`" : "''") . " AS image",
        ($yearCol ? "c.`$yearCol`" : "''") . " AS car_year",
        ($seatsCol ? "c.`$seatsCol`" : "5") . " AS seats",
        ($transmissionCol ? "c.`$transmissionCol`" : "'Automatic'") . " AS transmission",
        ($fuelCol ? "c.`$fuelCol`" : "'Petrol'") . " AS fuel_type",
        ($engineCol ? "c.`$engineCol`" : "'-'") . " AS engine",
        ($horsepowerCol ? "c.`$horsepowerCol`" : "0") . " AS horsepower",
        ($drivetrainCol ? "c.`$drivetrainCol`" : "'FWD'") . " AS drivetrain"
    ];

    $join = "";

    if($brandIdCol && tableExists($conn, "brands")) {
        $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "brand_id");
        $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
        $select[] = "COALESCE(b.`$brandNameCol`, '-') AS brand";
        $join .= " LEFT JOIN brands b ON b.`$brandPk` = c.`$brandIdCol` ";
    } elseif($brandCol) {
        $select[] = "c.`$brandCol` AS brand";
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
    } elseif($categoryCol) {
        $select[] = "c.`$categoryCol` AS category_name";
    } else {
        $select[] = "'Others' AS category_name";
    }

    $where = "1=1";
    if($statusCol) {
        $where .= " AND (LOWER(COALESCE(c.`$statusCol`, 'active')) IN ('active','available') OR c.`$statusCol` = 1)";
    }

    $cars = fetchRows($conn, "SELECT " . implode(", ", $select) . " FROM cars c $join WHERE $where ORDER BY c.`$carNameCol` ASC");
}

$trip = [
    "state" => (int)($_GET["state"] ?? 0),
    "pickup_location" => (int)($_GET["pickup_location"] ?? 0),
    "dropoff_location" => (int)($_GET["dropoff_location"] ?? 0),
    "pickup_date" => trim($_GET["pickup_date"] ?? ""),
    "pickup_time" => trim($_GET["pickup_time"] ?? ""),
    "return_date" => trim($_GET["return_date"] ?? ""),
    "return_time" => trim($_GET["return_time"] ?? "")
];

$answers = [
    "passengers" => trim($_GET["passengers"] ?? ""),
    "budget" => trim($_GET["budget"] ?? ""),
    "purpose" => trim($_GET["purpose"] ?? ""),
    "priority" => trim($_GET["priority"] ?? ""),
    "luggage" => trim($_GET["luggage"] ?? ""),
    "road_type" => trim($_GET["road_type"] ?? ""),
    "driving_style" => trim($_GET["driving_style"] ?? ""),
    "fuel_pref" => trim($_GET["fuel_pref"] ?? ""),
    "brand_pref" => trim($_GET["brand_pref"] ?? ""),
    "feature" => trim($_GET["feature"] ?? "")
];

$submitted = isset($_GET["smart_search"]);
$hasTrip = $trip["state"] > 0 && $trip["pickup_location"] > 0 && $trip["dropoff_location"] > 0 && $trip["pickup_date"] !== "" && $trip["pickup_time"] !== "" && $trip["return_date"] !== "" && $trip["return_time"] !== "";
$hasAnswers = $answers["passengers"] !== "" && $answers["budget"] !== "" && $answers["purpose"] !== "" && $answers["priority"] !== "" && $answers["luggage"] !== "" && $answers["road_type"] !== "" && $answers["driving_style"] !== "" && $answers["fuel_pref"] !== "" && $answers["brand_pref"] !== "" && $answers["feature"] !== "";

$errors = [];
$recommendations = [];
$rentalDays = 0;

if($submitted) {
    if(!$hasTrip) $errors[] = "Please complete your trip details first.";
    if(!$hasAnswers) $errors[] = "Please answer all smart assistant questions.";

    if($hasTrip) {
        $start = $trip["pickup_date"] . " " . $trip["pickup_time"] . ":00";
        $end = $trip["return_date"] . " " . $trip["return_time"] . ":00";

        $startTs = strtotime($start);
        $endTs = strtotime($end);
        $minStartTs = time() + 3600;

        if(!$startTs || !$endTs) {
            $errors[] = "Invalid pickup or return date/time.";
        } elseif($startTs < $minStartTs) {
            $errors[] = "Pickup time must be at least 1 hour after the current time.";
        } elseif($endTs <= $startTs) {
            $errors[] = "Return date/time must be after pickup date/time.";
        } elseif(($endTs - $startTs) < 86400) {
            $errors[] = "Minimum rental duration is 1 day.";
        } elseif($trip["pickup_time"] !== $trip["return_time"]) {
            $errors[] = "Return time must be the same as pickup time.";
        } else {
            $rentalDays = rentalDays($start, $end);
        }
    }

    if(!$errors) {
        $startSql = date("Y-m-d H:i:s", strtotime($start));
        $endSql = date("Y-m-d H:i:s", strtotime($end));

        foreach($cars as $car) {
            $available = availableUnitCount($conn, (int)$car["car_id"], (int)$trip["state"], $startSql, $endSql);
            if($available <= 0) continue;

            [$score, $reasons] = scoreCar($car, $answers);
            if($score <= 0) continue;

            $car["available_units"] = $available;
            $car["match_score"] = $score;
            $car["match_reasons"] = $reasons;
            $car["rental_days"] = $rentalDays;
            $car["estimated_total"] = (float)$car["price_per_day"] * $rentalDays;
            $recommendations[] = $car;
        }

        usort($recommendations, function($a, $b) {
            if((int)$b["match_score"] === (int)$a["match_score"]) {
                return (float)$a["price_per_day"] <=> (float)$b["price_per_day"];
            }
            return (int)$b["match_score"] <=> (int)$a["match_score"];
        });

        $recommendations = array_slice($recommendations, 0, 6);
    }
}

$stateNameMap = [];
foreach($states as $state) $stateNameMap[(int)$state["state_id"]] = $state["state_name"];

$locationNameMap = [];
foreach($locations as $location) $locationNameMap[(int)$location["location_id"]] = $location["location_name"];

$todayMin = date("Y-m-d");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Find Car Smart | KH Car Rental</title>
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

/* ===== Page: clean premium AI ===== */
.smart-page{
    width:min(1220px,100%);
    margin:22px auto 52px;
    padding:0 22px;
}

.smart-hero{
    position:relative;
    min-height:230px;
    padding:38px 42px;
    border-radius:32px;
    overflow:hidden;
    display:grid;
    grid-template-columns:1.15fr .75fr;
    gap:28px;
    align-items:center;
    background:
        radial-gradient(circle at 88% 18%,rgba(40,168,234,.20),transparent 32%),
        radial-gradient(circle at 100% 100%,rgba(16,35,61,.14),transparent 34%),
        linear-gradient(135deg,#ffffff 0%,#eef9ff 100%);
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 30px 90px rgba(18,132,198,.14);
    margin-bottom:18px;
}

.smart-hero::before{
    content:"AI CAR MATCHING ENGINE";
    position:absolute;
    top:28px;
    right:34px;
    padding:9px 16px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,#10233d,#16466f);
    font-size:11px;
    font-weight:950;
    letter-spacing:1px;
    box-shadow:0 18px 40px rgba(16,35,61,.22);
}

.smart-hero::after{
    content:"";
    position:absolute;
    right:-48px;
    bottom:-82px;
    width:310px;
    height:310px;
    border-radius:50%;
    border:36px solid rgba(40,168,234,.10);
    box-shadow:inset 0 0 0 34px rgba(16,35,61,.06);
}

.smart-hero-content{position:relative;z-index:2}
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
    margin-bottom:12px;
}
.smart-hero h1{
    max-width:760px;
    font-size:clamp(42px,5vw,70px);
    line-height:.95;
    letter-spacing:-2.8px;
    font-weight:950;
    margin-bottom:16px;
}
.smart-hero p{
    max-width:740px;
    color:var(--muted);
    font-size:15.5px;
    line-height:1.65;
    font-weight:700;
}
.hero-line{
    width:min(560px,100%);
    height:1px;
    margin:20px 0;
    background:linear-gradient(90deg,rgba(40,168,234,.58),transparent);
}
.hero-tags{
    display:flex;
    flex-wrap:wrap;
    gap:9px;
}
.hero-tag{
    min-height:38px;
    display:inline-flex;
    align-items:center;
    gap:10px;
    padding:0 13px;
    border-radius:14px;
    background:rgba(255,255,255,.82);
    border:1px solid var(--border);
    box-shadow:var(--soft);
    font-size:12.5px;
    font-weight:950;
}
.hero-tag i{color:var(--sky600)}

.ai-card{
    position:relative;
    z-index:2;
    width:100%;
    max-width:390px;
    justify-self:end;
    padding:24px;
    border-radius:30px;
    color:#fff;
    background:linear-gradient(135deg,#10233d,#153e64);
    box-shadow:0 30px 80px rgba(16,35,61,.28);
    border:1px solid rgba(255,255,255,.10);
}
.ai-card-top{
    display:flex;
    align-items:center;
    gap:16px;
}
.ai-icon{
    width:64px;
    height:64px;
    border-radius:22px;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#28a8ea,#1284c6);
    box-shadow:0 18px 42px rgba(40,168,234,.28);
    font-size:25px;
}
.ai-card h3{
    font-size:24px;
    font-weight:950;
    letter-spacing:-.4px;
}
.ai-card p{
    color:rgba(255,255,255,.76);
    font-size:13px;
    margin:3px 0 0;
    line-height:1.45;
    font-weight:800;
}
.ai-line{
    height:2px;
    margin:20px 0;
    border-radius:999px;
    background:linear-gradient(90deg,#28a8ea,rgba(40,168,234,.10));
}
.ai-metrics{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
}
.ai-metric{
    padding:12px 9px;
    border-radius:18px;
    background:rgba(255,255,255,.08);
    border:1px solid rgba(255,255,255,.10);
    text-align:center;
}
.ai-metric strong{
    display:block;
    font-size:17px;
    font-weight:950;
}
.ai-metric span{
    color:rgba(255,255,255,.68);
    font-size:10px;
    font-weight:850;
    text-transform:uppercase;
    letter-spacing:.4px;
}

.alert{
    display:flex;
    gap:12px;
    padding:15px;
    border-radius:18px;
    background:#fff4f2;
    color:#b42318;
    border:1px solid rgba(244,67,54,.22);
    margin-bottom:18px;
    font-weight:800;
}

.chat-shell{
    width:min(1080px,100%);
    margin:0 auto;
    border-radius:34px;
    overflow:hidden;
    background:linear-gradient(135deg,rgba(255,255,255,.99),rgba(247,252,255,.97));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 30px 90px rgba(18,132,198,.14);
}

.chat-top{
    min-height:106px;
    padding:24px 30px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:28px;
    background:
        radial-gradient(circle at 92% 0%,rgba(40,168,234,.25),transparent 30%),
        linear-gradient(135deg,#ffffff,#eef9ff);
    border-bottom:1px solid rgba(184,228,255,.95);
}

.chat-title{
    display:flex;
    align-items:center;
    gap:16px;
}
.chat-avatar{
    width:58px;
    height:58px;
    border-radius:21px;
    display:grid;
    place-items:center;
    color:#fff;
    font-size:23px;
    background:linear-gradient(135deg,#28a8ea,#1284c6);
    box-shadow:0 18px 42px rgba(40,168,234,.25);
}
.chat-title h2{
    font-size:28px;
    font-weight:950;
    letter-spacing:-.7px;
    line-height:1.05;
    margin-bottom:7px;
}
.chat-title p{
    color:var(--muted);
    font-size:13.5px;
    font-weight:850;
}
.online-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    margin-right:10px;
    padding:5px 10px;
    border-radius:999px;
    color:var(--sky600);
    background:var(--sky100);
    border:1px solid var(--border);
    font-size:11px;
    font-weight:950;
}
.online-badge::before{
    content:"";
    width:8px;
    height:8px;
    border-radius:50%;
    background:#16a765;
}
.progress-box{
    width:300px;
    padding:16px;
    border-radius:22px;
    background:rgba(255,255,255,.80);
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 14px 34px rgba(39,137,199,.09);
}
.progress-label{
    display:flex;
    align-items:center;
    justify-content:space-between;
    color:#10233d;
    font-size:12px;
    font-weight:950;
    margin-bottom:10px;
}
.progress-label span:first-child{
    color:var(--sky600);
    font-size:11px;
    letter-spacing:.6px;
    text-transform:uppercase;
}
.progress-bar{
    height:10px;
    background:#e6f4ff;
    border-radius:999px;
    overflow:hidden;
}
.progress-bar i{
    display:block;
    width:6%;
    height:100%;
    background:linear-gradient(90deg,#28a8ea,#1284c6,#ff7a1a);
    border-radius:999px;
    box-shadow:0 6px 18px rgba(40,168,234,.24);
    transition:width .2s ease;
}

.chat-window{
    height:clamp(620px,72vh,820px);
    min-height:620px;
    max-height:820px;
    overflow-y:auto;
    overflow-x:hidden;
    overscroll-behavior:contain;
    padding:32px 32px 120px;
    background:
        radial-gradient(circle at 0% 0%,rgba(40,168,234,.08),transparent 28%),
        linear-gradient(90deg,rgba(40,168,234,.035) 1px,transparent 1px),
        linear-gradient(rgba(40,168,234,.035) 1px,transparent 1px),
        linear-gradient(180deg,#f7fcff,#ffffff);
    background-size:auto,36px 36px,36px 36px,auto;
    scrollbar-width:thin;
    scrollbar-color:#1284c6 #eaf7ff;
}
.chat-window::-webkit-scrollbar{width:10px}
.chat-window::-webkit-scrollbar-track{background:#eaf7ff;border-radius:999px}
.chat-window::-webkit-scrollbar-thumb{
    background:linear-gradient(180deg,#28a8ea,#1284c6);
    border-radius:999px;
    border:2px solid #eaf7ff;
}

.chat-step{
    display:none;
    margin-bottom:28px;
}
.chat-step.visible{
    display:block;
    animation:questionPop .16s ease-out both;
}
@keyframes questionPop{
    from{opacity:0;transform:translateY(8px)}
    to{opacity:1;transform:translateY(0)}
}
.chat-step.answered .answer-panel,
.chat-step.answered .option-row{
    display:none;
}
.bot-message{
    display:flex;
    align-items:flex-start;
    gap:12px;
    max-width:780px;
    margin-bottom:12px;
}
.bot-icon{
    width:48px;
    height:48px;
    min-width:48px;
    border-radius:18px;
    display:grid;
    place-items:center;
    color:#fff;
    background:linear-gradient(135deg,#28a8ea,#0b5f9d);
    box-shadow:0 14px 32px rgba(40,168,234,.22);
}
.bot-bubble{
    border-radius:8px 24px 24px 24px;
    padding:16px 18px;
    background:#fff;
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 14px 34px rgba(39,137,199,.09);
}
.bot-bubble strong{
    display:block;
    font-size:16.5px;
    font-weight:950;
    margin-bottom:5px;
}
.bot-bubble p{
    color:var(--muted);
    font-size:13.5px;
    line-height:1.55;
    font-weight:750;
}
.answer-panel{
    width:min(820px,100%);
    margin-left:auto;
    margin-right:0;
    padding:18px;
    border-radius:28px 10px 28px 28px;
    background:
        radial-gradient(circle at 100% 0%, rgba(40,168,234,.13), transparent 34%),
        linear-gradient(135deg,rgba(235,248,255,.96),rgba(255,255,255,.94));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 12px 30px rgba(39,137,199,.08);
}
.option-row{
    width:min(820px,100%);
    margin-left:auto;
    margin-right:0;
    display:flex;
    flex-wrap:wrap;
    justify-content:flex-end;
    gap:10px;
}
.choice-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px;
    width:100%;
}
.location-grid{
    grid-template-columns:repeat(4,minmax(0,1fr));
}
.choice-btn,.option-btn{
    min-height:52px;
    padding:10px 12px;
    border-radius:20px 8px 20px 20px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    cursor:pointer;
    color:var(--dark);
    background:#fff;
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 10px 24px rgba(39,137,199,.07);
    font-size:13px;
    font-weight:950;
    transition:.22s ease;
}
.choice-btn:hover,.option-btn:hover{
    transform:translateY(-3px);
    background:#f2fbff;
    border-color:#28a8ea;
}
.choice-btn i,.option-btn i{color:var(--sky600)}
.choice-btn.selected,.option-btn.selected{
    background:linear-gradient(135deg,#1284c6,#10233d);
    color:#fff;
    border-color:transparent;
    box-shadow:0 18px 38px rgba(18,132,198,.22);
}
.choice-btn.selected i,.option-btn.selected i{color:#fff}
.choice-btn.is-hidden{display:none!important}

.input{
    width:100%;
    height:44px;
    border:2px solid #e2f2ff;
    background:rgba(255,255,255,.94);
    color:var(--dark);
    border-radius:14px;
    padding:8px 12px;
    outline:none;
    font-size:13px;
    font-weight:800;
    transition:.24s;
}
.input:focus{
    border-color:var(--sky500);
    box-shadow:0 0 0 .2rem rgba(40,168,234,.13);
}
.input.error{
    border-color:#ff4d4f!important;
    background:#fff5f5!important;
}
.field-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
}
.field-grid label{
    display:block;
    font-size:10px;
    font-weight:950;
    letter-spacing:.6px;
    color:#31506f;
    text-transform:uppercase;
    margin-bottom:6px;
}
.time-combo{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.fixed-time-display{
    min-height:44px;
    border-radius:14px;
    border:2px solid #e2f2ff;
    background:#f6fbff;
    color:var(--dark);
    display:flex;
    align-items:center;
    gap:10px;
    padding:8px 12px;
    font-size:13px;
    font-weight:900;
}
.fixed-time-display i{color:var(--sky600)}
.next-btn,.submit-btn,.restart-btn{
    min-height:44px;
    border:0;
    border-radius:15px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;
    padding:0 16px;
    cursor:pointer;
    font-weight:950;
    transition:.22s ease;
}
.next-btn{
    width:100%;
    margin-top:12px;
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
}
.submit-btn{
    color:#fff;
    background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);
    box-shadow:0 18px 34px rgba(255,122,26,.26);
}
.restart-btn{
    color:var(--sky600);
    background:#fff;
    border:1px solid var(--border);
}
.next-btn:hover,.submit-btn:hover,.restart-btn:hover{
    transform:translateY(-3px);
    box-shadow:var(--soft);
}
.inline-error{
    display:none;
    margin-top:12px;
    color:#d63031;
    background:#fff5f5;
    border:1px solid rgba(255,77,79,.2);
    border-radius:14px;
    padding:10px 12px;
    font-size:12.5px;
    font-weight:800;
}
.inline-error.show{display:block}
.user-reply{
    display:flex;
    justify-content:flex-end;
    margin:10px 0 18px auto;
    max-width:620px;
}
.user-bubble{
    display:inline-flex;
    align-items:center;
    gap:8px;
    max-width:100%;
    padding:13px 16px;
    border-radius:24px 8px 24px 24px;
    background:linear-gradient(135deg,#1284c6,#10233d);
    color:#fff;
    box-shadow:0 14px 32px rgba(18,132,198,.20);
    font-weight:950;
    line-height:1.35;
}
.user-bubble span{
    color:rgba(255,255,255,.72);
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.5px;
    font-weight:950;
}
.final-actions{
    display:flex;
    justify-content:flex-end;
    gap:10px;
    margin-left:auto;
    width:min(820px,100%);
}

.results{
    width:min(1180px,100%);
    margin:24px auto 0;
    padding:30px;
    border-radius:34px;
    overflow:hidden;
    background:
        radial-gradient(circle at 100% 0%,rgba(40,168,234,.14),transparent 30%),
        linear-gradient(135deg,#ffffff,#f7fcff);
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 30px 90px rgba(18,132,198,.13);
}
.results.hidden{display:none}
.results-head{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    padding:6px 4px 20px;
    border-bottom:1px solid rgba(184,228,255,.95);
}
.results-head h2{
    font-size:42px;
    font-weight:950;
    letter-spacing:-1.2px;
    line-height:1.02;
    margin-bottom:8px;
}
.results-head p{
    max-width:720px;
    color:var(--muted);
    font-size:14px;
    line-height:1.55;
    font-weight:750;
}
.count-tag{
    padding:11px 16px;
    border-radius:999px;
    background:linear-gradient(135deg,#10233d,#1284c6);
    color:#fff;
    box-shadow:0 15px 34px rgba(18,132,198,.20);
    font-size:12px;
    font-weight:950;
    white-space:nowrap;
}
.car-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
    margin-top:20px;
}
.car-card{
    position:relative;
    border-radius:28px;
    border:1px solid rgba(184,228,255,.95);
    background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.96));
    box-shadow:0 20px 50px rgba(18,132,198,.11);
    overflow:hidden;
    transition:.28s ease;
}
.car-card::before{
    content:"AI Recommended";
    position:absolute;
    top:14px;
    right:14px;
    z-index:5;
    padding:7px 10px;
    border-radius:999px;
    background:rgba(16,35,61,.88);
    color:#fff;
    font-size:10px;
    font-weight:950;
    letter-spacing:.5px;
}
.car-card:hover{
    transform:translateY(-8px);
    box-shadow:0 30px 76px rgba(18,132,198,.18);
}
.car-img{
    position:relative;
    height:190px;
    display:grid;
    place-items:center;
    overflow:hidden;
    background:linear-gradient(135deg,#eef9ff,#ffffff);
}
.car-img img{
    width:100%;
    height:100%;
    object-fit:cover;
}
.match{
    position:absolute;
    left:14px;
    top:14px;
    padding:8px 11px;
    border-radius:999px;
    color:#fff;
    background:linear-gradient(135deg,#16a765,#087747);
    box-shadow:0 12px 28px rgba(22,167,101,.20);
    font-size:12px;
    font-weight:950;
}
.price{
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
.car-body{padding:16px}
.car-title{
    display:flex;
    justify-content:space-between;
    gap:12px;
    margin-bottom:10px;
}
.car-title h3{
    font-size:20px;
    line-height:1.15;
    font-weight:950;
}
.brand-tag{
    height:28px;
    padding:0 9px;
    display:inline-flex;
    align-items:center;
    border-radius:999px;
    background:var(--sky100);
    color:var(--sky600);
    font-size:11px;
    font-weight:950;
    white-space:nowrap;
}
.specs{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:7px;
    margin:10px 0;
}
.spec{
    min-height:34px;
    padding:7px 9px;
    border-radius:12px;
    background:var(--sky50);
    border:1px solid var(--border);
    font-size:11.5px;
    font-weight:850;
    color:#2b4969;
}
.spec i{color:var(--sky600);margin-right:6px}
.total-box{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-bottom:10px;
}
.total-box div{
    padding:10px;
    border-radius:13px;
    border:1px solid var(--border);
}
.total-box span{
    display:block;
    color:var(--muted);
    font-size:10px;
    text-transform:uppercase;
    font-weight:950;
    margin-bottom:3px;
}
.total-box strong{font-size:13.5px}
.reason{
    padding:12px;
    border-radius:14px;
    background:#f5fbff;
    border:1px solid rgba(184,228,255,.95);
    margin-bottom:12px;
}
.reason strong{
    display:block;
    color:var(--sky600);
    font-size:12px;
    text-transform:uppercase;
    margin-bottom:6px;
}
.reason strong::before{
    content:"\f0eb";
    font-family:"Font Awesome 6 Free";
    font-weight:900;
    margin-right:7px;
}
.reason ul{
    margin-left:17px;
    color:#2b4969;
    font-size:12.5px;
    line-height:1.45;
    font-weight:750;
}
.actions{
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:8px;
}
.actions a{
    min-height:39px;
    border-radius:13px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    font-size:11.5px;
    font-weight:950;
}
.btn-blue{
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
}
.btn-white{
    color:var(--sky600);
    background:#fff;
    border:1px solid var(--border);
}
.empty{
    text-align:center;
    padding:40px;
}
.empty-icon{
    width:70px;
    height:70px;
    margin:0 auto 14px;
    border-radius:24px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
    font-size:28px;
}
.empty h2{
    font-size:28px;
    margin-bottom:8px;
    font-weight:950;
}
.empty p{
    color:var(--muted);
    font-weight:750;
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
@media(max-width:1180px){
    .nav-links{display:none!important}
    .smart-hero{grid-template-columns:1fr}
    .ai-card{display:none}
    .car-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .chat-top{display:grid}
    .progress-box{width:100%}
    .choice-grid,.location-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
}
@media(max-width:760px){
    .smart-page{padding:0 14px}
    .smart-hero{padding:28px 22px}
    .smart-hero::before{position:relative;display:inline-block;top:auto;right:auto;margin-bottom:14px}
    .smart-hero h1{font-size:42px}
    .chat-window{height:calc(100vh - 225px);min-height:540px;padding:20px 20px 110px}
    .field-grid,.car-grid,.choice-grid,.location-grid,.actions{grid-template-columns:1fr}
    .answer-panel,.option-row,.final-actions{margin-left:0;justify-content:flex-start}
    .results-head{display:grid}
}

/* ===== Recommended Cars up to 6 cards ===== */
.car-grid{
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
}
@media(max-width:1220px){
    .car-grid{grid-template-columns:repeat(2,minmax(0,1fr))!important;}
}
@media(max-width:760px){
    .car-grid{grid-template-columns:1fr!important;}
}

/* ===== FINAL EXACT CATALOGUE NAVBAR FIX ===== */
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


/* prevent Find Car Smart page responsive rules from changing catalogue navbar */
.navbar {
    height: 86px !important;
    background: rgba(255,255,255,.68) !important;
    backdrop-filter: blur(20px) saturate(160%) !important;
    -webkit-backdrop-filter: blur(20px) saturate(160%) !important;
    border-bottom: 1px solid rgba(184,228,255,.85) !important;
    box-shadow: 0 18px 45px rgba(39,137,199,.09) !important;
}
.nav-inner {
    width: min(1320px, calc(100% - 42px)) !important;
    height: 86px !important;
    margin: 0 auto !important;
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    gap: 18px !important;
}
.brand {
    display: flex !important;
    align-items: center !important;
    gap: 14px !important;
    min-width: 230px !important;
    font-size: 18px !important;
    font-weight: 950 !important;
    color: #10233d !important;
    white-space: nowrap !important;
}
.brand-icon {
    width: 52px !important;
    height: 52px !important;
    border-radius: 18px !important;
    display: grid !important;
    place-items: center !important;
    color: #1284c6 !important;
    background: rgba(234,247,255,.88) !important;
    border: 1px solid rgba(184,228,255,.95) !important;
    box-shadow: inset 0 0 0 1px rgba(255,255,255,.7) !important;
}
.nav-links {
    flex: 1 !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 26px !important;
    list-style: none !important;
    margin: 0 !important;
    padding: 0 !important;
}
.nav-links li {
    list-style: none !important;
}
.nav-links a {
    display: inline-flex !important;
    align-items: center !important;
    gap: 8px !important;
    padding: 10px 0 !important;
    border-radius: 0 !important;
    font-size: 13.5px !important;
    line-height: 1.05 !important;
    font-weight: 950 !important;
    letter-spacing: .2px !important;
    color: #2b4969 !important;
    background: transparent !important;
    white-space: nowrap !important;
}
.nav-links a i {
    font-size: 14px !important;
    color: inherit !important;
}
.nav-links a:hover,
.nav-links a.active {
    color: #1284c6 !important;
    background: transparent !important;
}
.avatar-wrap {
    position: relative !important;
    min-width: 210px !important;
    display: flex !important;
    justify-content: flex-end !important;
    flex-shrink: 0 !important;
}
.login-btn {
    height: 62px !important;
    min-width: 210px !important;
    padding: 0 26px !important;
    border-radius: 999px !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 10px !important;
    background: linear-gradient(135deg,#28a8ea,#1284c6) !important;
    color: #fff !important;
    font-size: 19px !important;
    font-weight: 950 !important;
    box-shadow: 0 18px 35px rgba(40,168,234,.24) !important;
    white-space: nowrap !important;
}
.login-btn i {
    color: #fff !important;
}
.nav-cart-link {
    position: relative !important;
    overflow: visible !important;
}
.cart-count-badge {
    position:absolute !important;
    top:-6px !important;
    right:-12px !important;
    min-width:18px !important;
    height:18px !important;
    padding:0 5px !important;
    border-radius:999px !important;
    display:inline-flex !important;
    align-items:center !important;
    justify-content:center !important;
    background:linear-gradient(135deg,#ff5a52,#e11d2e) !important;
    color:#fff !important;
    border:2px solid #fff !important;
    box-shadow:0 8px 18px rgba(225,29,46,.28) !important;
    font-size:9px !important;
    font-weight:950 !important;
    line-height:1 !important;
    z-index:5 !important;
}

/* keep catalogue desktop navbar visible until same breakpoint */
@media (min-width: 1181px) {
    .nav-links {
        display: flex !important;
    }
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

@media(max-width:999px){
    .footer-inner{
        grid-template-columns:1fr;
        gap:34px;
    }
}
@media(max-width:760px){
    .footer{
        padding:58px 18px 24px;
    }
    .footer-inner,
    .footer-bottom{
        width:100%;
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
            <li><a href="catalogue.php"><i class="fa-solid fa-car"></i> CATALOGUE</a></li>
            <li><a href="find_car_smart.php" class="active"><i class="fa-solid fa-wand-magic-sparkles"></i> FIND CAR SMART</a></li>
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

<main class="smart-page">
    <section class="smart-hero">
        <div class="smart-hero-content">
            <span class="pill"><i class="fa-solid fa-wand-magic-sparkles"></i> Find Car Smart</span>
            <h1>KH Smart Car Assistant</h1>
            <p>Answer one question at a time. The system will check your trip details, understand your needs, and recommend suitable available cars.</p>
            <div class="hero-line"></div>
            <div class="hero-tags">
                <span class="hero-tag"><i class="fa-solid fa-message"></i> Chat-style flow</span>
                <span class="hero-tag"><i class="fa-solid fa-calendar-check"></i> Availability checked</span>
                <span class="hero-tag"><i class="fa-solid fa-brain"></i> Match scoring</span>
            </div>
        </div>

        <div class="ai-card">
            <div class="ai-card-top">
                <div class="ai-icon"><i class="fa-solid fa-robot"></i></div>
                <div>
                    <h3>KH AI Matcher</h3>
                    <p>Smart scoring • Availability checked • Fast recommendation</p>
                </div>
            </div>
            <div class="ai-line"></div>
            <div class="ai-metrics">
                <div class="ai-metric"><strong>16</strong><span>Signals</span></div>
                <div class="ai-metric"><strong>Live</strong><span>Check</span></div>
                <div class="ai-metric"><strong>AI</strong><span>Match</span></div>
            </div>
        </div>
    </section>

    <?php if($errors): ?>
        <div class="alert">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <div>
                <?php foreach($errors as $error): ?>
                    <p><?= e($error) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <form method="GET" action="find_car_smart.php#recommendationResults" id="smartSearchForm">
        <input type="hidden" name="smart_search" value="1">

        <section class="chat-shell">
            <div class="chat-top">
                <div class="chat-title">
                    <div class="chat-avatar"><i class="fa-solid fa-robot"></i></div>
                    <div>
                        <h2>KH Smart Assistant</h2>
                        <p><span class="online-badge">Online</span> Trip Details → Preferences → Recommendations</p>
                    </div>
                </div>

                <div class="progress-box">
                    <div class="progress-label"><span>AI Progress</span><strong id="stepText">Step 1 of 16</strong></div>
                    <div class="progress-bar"><i id="progressFill"></i></div>
                </div>
            </div>

            <div class="chat-window" id="chatWindow">
                <div class="chat-step" data-step="1">
                    <div class="bot-message">
                        <span class="bot-icon"><i class="fa-solid fa-robot"></i></span>
                        <div class="bot-bubble">
                            <strong>Hi! I can help you find the best car.</strong>
                            <p>Which state would you like to pick up your car from?</p>
                        </div>
                    </div>
                    <div class="answer-panel">
                        <input type="hidden" name="state" id="pickupState" value="<?= e($trip["state"]) ?>">
                        <div class="choice-grid">
                            <?php foreach($states as $state): ?>
                                <button type="button" class="choice-btn direct-choice" data-target="pickupState" data-value="<?= e($state["state_id"]) ?>" data-next="2">
                                    <i class="fa-solid fa-location-dot"></i> <?= e($state["state_name"]) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="chat-step" data-step="2">
                    <div class="bot-message">
                        <span class="bot-icon"><i class="fa-solid fa-map-pin"></i></span>
                        <div class="bot-bubble">
                            <strong>Which pickup location do you prefer?</strong>
                            <p>Only locations under your selected state will be shown.</p>
                        </div>
                    </div>
                    <div class="answer-panel">
                        <input type="hidden" name="pickup_location" id="pickupLocation" value="<?= e($trip["pickup_location"]) ?>">
                        <div class="choice-grid location-grid" id="pickupLocationChoices">
                            <?php foreach($locations as $location): ?>
                                <button type="button" class="choice-btn location-choice direct-choice" data-state="<?= e($location["state_id"]) ?>" data-target="pickupLocation" data-value="<?= e($location["location_id"]) ?>" data-next="3">
                                    <i class="fa-solid fa-map-pin"></i> <?= e($location["location_name"]) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="chat-step" data-step="3">
                    <div class="bot-message">
                        <span class="bot-icon"><i class="fa-solid fa-location-arrow"></i></span>
                        <div class="bot-bubble">
                            <strong>Where would you like to return the car?</strong>
                            <p>Select your drop-off location.</p>
                        </div>
                    </div>
                    <div class="answer-panel">
                        <input type="hidden" name="dropoff_location" id="dropoffLocation" value="<?= e($trip["dropoff_location"]) ?>">
                        <div class="choice-grid location-grid" id="dropoffLocationChoices">
                            <?php foreach($locations as $location): ?>
                                <button type="button" class="choice-btn location-choice direct-choice" data-state="<?= e($location["state_id"]) ?>" data-target="dropoffLocation" data-value="<?= e($location["location_id"]) ?>" data-next="4">
                                    <i class="fa-solid fa-flag-checkered"></i> <?= e($location["location_name"]) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <div class="chat-step" data-step="4">
                    <div class="bot-message">
                        <span class="bot-icon"><i class="fa-solid fa-calendar-days"></i></span>
                        <div class="bot-bubble">
                            <strong>When do you want to pick up the car?</strong>
                            <p>Pickup time must be at least 1 hour after the current time. Past date/time is not allowed.</p>
                        </div>
                    </div>
                    <div class="answer-panel">
                        <div class="field-grid">
                            <div>
                                <label>Pickup Date</label>
                                <input class="input" type="date" name="pickup_date" id="pickupDate" min="<?= e($todayMin) ?>" value="<?= e($trip["pickup_date"]) ?>" required>
                            </div>
                            <div>
                                <label>Pickup Time</label>
                                <div class="time-combo">
                                    <select class="input" id="pickupHour" required><option value="">Hour</option></select>
                                    <select class="input" id="pickupMinute" required><option value="">Minute</option></select>
                                </div>
                                <input type="hidden" name="pickup_time" id="pickupTime" value="<?= e($trip["pickup_time"]) ?>" required>
                            </div>
                        </div>
                        <div class="inline-error" id="pickupTimeError"></div>
                        <button type="button" class="next-btn" data-next="5">Next <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="chat-step" data-step="5">
                    <div class="bot-message">
                        <span class="bot-icon"><i class="fa-solid fa-calendar-check"></i></span>
                        <div class="bot-bubble">
                            <strong>When will you return the car?</strong>
                            <p>Return time follows pickup time automatically. Rental duration cannot be less than 1 day.</p>
                        </div>
                    </div>
                    <div class="answer-panel">
                        <div class="field-grid">
                            <div>
                                <label>Return Date</label>
                                <input class="input" type="date" name="return_date" id="returnDate" min="<?= e($todayMin) ?>" value="<?= e($trip["return_date"]) ?>" required>
                            </div>
                            <div>
                                <label>Return Time</label>
                                <div class="fixed-time-display" id="returnTimeDisplay"><i class="fa-solid fa-lock"></i><span>Same as pickup time</span></div>
                                <input type="hidden" name="return_time" id="returnTime" value="<?= e($trip["return_time"]) ?>" required>
                            </div>
                        </div>
                        <div class="inline-error" id="returnDateError"></div>
                        <button type="button" class="next-btn" data-next="6">Next <i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </div>

                <div class="chat-step" data-step="6">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-users"></i></span><div class="bot-bubble"><strong>How many passengers?</strong><p>This helps choose the right car size.</p></div></div>
                    <div class="option-row" data-name="passengers">
                        <button type="button" class="option-btn" data-name="passengers" data-value="1-2"><i class="fa-solid fa-user"></i> 1–2 passengers</button>
                        <button type="button" class="option-btn" data-name="passengers" data-value="3-5"><i class="fa-solid fa-users"></i> 3–5 passengers</button>
                        <button type="button" class="option-btn" data-name="passengers" data-value="6-7"><i class="fa-solid fa-people-group"></i> 6–7 passengers</button>
                    </div>
                    <input type="hidden" name="passengers" id="passengers" value="<?= e($answers["passengers"]) ?>">
                </div>

                <div class="chat-step" data-step="7">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-wallet"></i></span><div class="bot-bubble"><strong>What is your budget per day?</strong><p>Choose your comfortable daily budget.</p></div></div>
                    <div class="option-row" data-name="budget">
                        <button type="button" class="option-btn" data-name="budget" data-value="below150"><i class="fa-solid fa-tag"></i> Below RM150</button>
                        <button type="button" class="option-btn" data-name="budget" data-value="150-250"><i class="fa-solid fa-tags"></i> RM150–RM250</button>
                        <button type="button" class="option-btn" data-name="budget" data-value="250-400"><i class="fa-solid fa-gem"></i> RM250–RM400</button>
                        <button type="button" class="option-btn" data-name="budget" data-value="above400"><i class="fa-solid fa-crown"></i> Above RM400</button>
                    </div>
                    <input type="hidden" name="budget" id="budget" value="<?= e($answers["budget"]) ?>">
                </div>

                <div class="chat-step" data-step="8">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-route"></i></span><div class="bot-bubble"><strong>What is your main trip purpose?</strong><p>This helps understand how you will use the car.</p></div></div>
                    <div class="option-row" data-name="purpose">
                        <button type="button" class="option-btn" data-name="purpose" data-value="city"><i class="fa-solid fa-city"></i> City Driving</button>
                        <button type="button" class="option-btn" data-name="purpose" data-value="family"><i class="fa-solid fa-people-roof"></i> Family Trip</button>
                        <button type="button" class="option-btn" data-name="purpose" data-value="business"><i class="fa-solid fa-briefcase"></i> Business Trip</button>
                        <button type="button" class="option-btn" data-name="purpose" data-value="long"><i class="fa-solid fa-road"></i> Long Distance</button>
                        <button type="button" class="option-btn" data-name="purpose" data-value="luxury"><i class="fa-solid fa-star"></i> Luxury</button>
                        <button type="button" class="option-btn" data-name="purpose" data-value="outdoor"><i class="fa-solid fa-mountain-sun"></i> Outdoor / Luggage</button>
                    </div>
                    <input type="hidden" name="purpose" id="purpose" value="<?= e($answers["purpose"]) ?>">
                </div>

                <div class="chat-step" data-step="9">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-star"></i></span><div class="bot-bubble"><strong>What matters most to you?</strong><p>Select your main priority.</p></div></div>
                    <div class="option-row" data-name="priority">
                        <button type="button" class="option-btn" data-name="priority" data-value="price"><i class="fa-solid fa-sack-dollar"></i> Lowest Price</button>
                        <button type="button" class="option-btn" data-name="priority" data-value="fuel"><i class="fa-solid fa-gas-pump"></i> Fuel Saving</button>
                        <button type="button" class="option-btn" data-name="priority" data-value="comfort"><i class="fa-solid fa-couch"></i> Comfort</button>
                        <button type="button" class="option-btn" data-name="priority" data-value="space"><i class="fa-solid fa-boxes-stacked"></i> Large Space</button>
                        <button type="button" class="option-btn" data-name="priority" data-value="performance"><i class="fa-solid fa-gauge-high"></i> Performance</button>
                        <button type="button" class="option-btn" data-name="priority" data-value="premium"><i class="fa-solid fa-wand-magic-sparkles"></i> Premium Look</button>
                    </div>
                    <input type="hidden" name="priority" id="priority" value="<?= e($answers["priority"]) ?>">
                </div>

                <div class="chat-step" data-step="10">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-suitcase-rolling"></i></span><div class="bot-bubble"><strong>Do you need luggage space?</strong><p>This avoids recommending a car that is too small.</p></div></div>
                    <div class="option-row" data-name="luggage">
                        <button type="button" class="option-btn" data-name="luggage" data-value="no"><i class="fa-solid fa-ban"></i> No</button>
                        <button type="button" class="option-btn" data-name="luggage" data-value="medium"><i class="fa-solid fa-suitcase"></i> Medium luggage</button>
                        <button type="button" class="option-btn" data-name="luggage" data-value="large"><i class="fa-solid fa-suitcase-rolling"></i> Large luggage</button>
                    </div>
                    <input type="hidden" name="luggage" id="luggage" value="<?= e($answers["luggage"]) ?>">
                </div>

                <div class="chat-step" data-step="11">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-road"></i></span><div class="bot-bubble"><strong>What kind of route will you mostly drive on?</strong><p>This helps match city, highway, SUV, or pickup cars.</p></div></div>
                    <div class="option-row" data-name="road_type">
                        <button type="button" class="option-btn" data-name="road_type" data-value="city"><i class="fa-solid fa-city"></i> Mostly city roads</button>
                        <button type="button" class="option-btn" data-name="road_type" data-value="highway"><i class="fa-solid fa-road"></i> Mostly highway</button>
                        <button type="button" class="option-btn" data-name="road_type" data-value="mixed"><i class="fa-solid fa-map"></i> Mixed route</button>
                        <button type="button" class="option-btn" data-name="road_type" data-value="rough"><i class="fa-solid fa-mountain"></i> Rough / outdoor route</button>
                    </div>
                    <input type="hidden" name="road_type" id="road_type" value="<?= e($answers["road_type"]) ?>">
                </div>

                <div class="chat-step" data-step="12">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-gauge-high"></i></span><div class="bot-bubble"><strong>What driving feel do you prefer?</strong><p>This makes the recommendation more accurate.</p></div></div>
                    <div class="option-row" data-name="driving_style">
                        <button type="button" class="option-btn" data-name="driving_style" data-value="easy"><i class="fa-solid fa-leaf"></i> Easy & simple</button>
                        <button type="button" class="option-btn" data-name="driving_style" data-value="smooth"><i class="fa-solid fa-cloud"></i> Smooth & comfortable</button>
                        <button type="button" class="option-btn" data-name="driving_style" data-value="power"><i class="fa-solid fa-bolt"></i> Powerful drive</button>
                        <button type="button" class="option-btn" data-name="driving_style" data-value="premium"><i class="fa-solid fa-crown"></i> Premium feeling</button>
                    </div>
                    <input type="hidden" name="driving_style" id="driving_style" value="<?= e($answers["driving_style"]) ?>">
                </div>

                <div class="chat-step" data-step="13">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-gas-pump"></i></span><div class="bot-bubble"><strong>Do you have any fuel preference?</strong><p>This is used as a matching signal when fuel data exists.</p></div></div>
                    <div class="option-row" data-name="fuel_pref">
                        <button type="button" class="option-btn" data-name="fuel_pref" data-value="fuel_saving"><i class="fa-solid fa-seedling"></i> Fuel saving</button>
                        <button type="button" class="option-btn" data-name="fuel_pref" data-value="petrol"><i class="fa-solid fa-gas-pump"></i> Petrol</button>
                        <button type="button" class="option-btn" data-name="fuel_pref" data-value="ev_hybrid"><i class="fa-solid fa-charging-station"></i> EV / Hybrid</button>
                        <button type="button" class="option-btn" data-name="fuel_pref" data-value="no_preference"><i class="fa-solid fa-circle-minus"></i> No preference</button>
                    </div>
                    <input type="hidden" name="fuel_pref" id="fuel_pref" value="<?= e($answers["fuel_pref"]) ?>">
                </div>

                <div class="chat-step" data-step="14">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-award"></i></span><div class="bot-bubble"><strong>Do you prefer any brand style?</strong><p>You can choose a brand direction or leave it open.</p></div></div>
                    <div class="option-row" data-name="brand_pref">
                        <button type="button" class="option-btn" data-name="brand_pref" data-value="local"><i class="fa-solid fa-flag"></i> Local brands</button>
                        <button type="button" class="option-btn" data-name="brand_pref" data-value="japanese"><i class="fa-solid fa-torii-gate"></i> Japanese brands</button>
                        <button type="button" class="option-btn" data-name="brand_pref" data-value="european"><i class="fa-solid fa-shield-halved"></i> European brands</button>
                        <button type="button" class="option-btn" data-name="brand_pref" data-value="no_preference"><i class="fa-solid fa-circle-minus"></i> No preference</button>
                    </div>
                    <input type="hidden" name="brand_pref" id="brand_pref" value="<?= e($answers["brand_pref"]) ?>">
                </div>

                <div class="chat-step" data-step="15">
                    <div class="bot-message"><span class="bot-icon"><i class="fa-solid fa-shield-halved"></i></span><div class="bot-bubble"><strong>What final feature is most important?</strong><p>This final question helps choose the best car among close matches.</p></div></div>
                    <div class="option-row" data-name="feature">
                        <button type="button" class="option-btn" data-name="feature" data-value="safety"><i class="fa-solid fa-shield"></i> Safety</button>
                        <button type="button" class="option-btn" data-name="feature" data-value="comfort"><i class="fa-solid fa-couch"></i> Comfort</button>
                        <button type="button" class="option-btn" data-name="feature" data-value="style"><i class="fa-solid fa-star"></i> Style / image</button>
                        <button type="button" class="option-btn" data-name="feature" data-value="practical"><i class="fa-solid fa-toolbox"></i> Practicality</button>
                    </div>
                    <input type="hidden" name="feature" id="feature" value="<?= e($answers["feature"]) ?>">
                </div>

                <div class="chat-step" data-step="16">
                    <div class="bot-message">
                        <span class="bot-icon"><i class="fa-solid fa-microchip"></i></span>
                        <div class="bot-bubble">
                            <strong>AI matching profile completed.</strong>
                            <p>I will scan available cars, calculate match score, and recommend the most suitable options.</p>
                        </div>
                    </div>
                    <div class="final-actions">
                        <button type="button" class="restart-btn" id="restartSmart"><i class="fa-solid fa-rotate-left"></i> Start Over</button>
                        <button type="submit" class="submit-btn"><i class="fa-solid fa-wand-magic-sparkles"></i> Generate AI Match</button>
                    </div>
                </div>
            </div>
        </section>
    </form>

    <section class="results <?= $submitted ? "" : "hidden" ?>" id="recommendationResults">
        <div class="results-head">
            <div>
                <span class="pill"><i class="fa-solid fa-sparkles"></i> AI Result</span>
                <h2>Recommended Cars</h2>
                <p>These cars are selected using your trip details, preference answers, availability, and match scoring.</p>
            </div>
            <span class="count-tag"><i class="fa-solid fa-car-side"></i> <?= e(count($recommendations)) ?> match(es)</span>
        </div>

        <?php if($submitted && !$errors && $recommendations): ?>
            <div class="car-grid">
                <?php foreach($recommendations as $car): ?>
                    <?php
                        $img = resolveCarImageSrc($car["image"] ?? "", $car["car_name"] ?? "Car");
                        $carId = (int)($car["car_id"] ?? 0);
                        $tripParams = [
                            "car_id" => $carId,
                            "state" => $trip["state"],
                            "pickup_location" => $trip["pickup_location"],
                            "dropoff_location" => $trip["dropoff_location"],
                            "pickup_date" => $trip["pickup_date"],
                            "pickup_time" => $trip["pickup_time"],
                            "return_date" => $trip["return_date"],
                            "return_time" => $trip["return_time"]
                        ];
                        $viewUrl = "car_details.php?" . http_build_query($tripParams);
                        $cartUrl = "add_to_cart.php?" . http_build_query($tripParams);
                        $compareUrl = "compare_car.php?" . http_build_query(["car_id" => $carId]);
                    ?>
                    <article class="car-card">
                        <div class="car-img">
                            <img src="<?= e($img) ?>" alt="<?= e($car["car_name"] ?? "Car") ?>">
                            <span class="match"><?= e((int)$car["match_score"]) ?>% Match</span>
                            <span class="price"><?= e(money($car["price_per_day"] ?? 0)) ?> / day</span>
                        </div>
                        <div class="car-body">
                            <div class="car-title">
                                <h3><?= e($car["car_name"] ?? "Rental Car") ?></h3>
                                <span class="brand-tag"><?= e($car["brand"] ?? "-") ?></span>
                            </div>

                            <div class="specs">
                                <div class="spec"><i class="fa-solid fa-layer-group"></i><?= e($car["category_name"] ?? "-") ?></div>
                                <div class="spec"><i class="fa-solid fa-users"></i><?= e($car["seats"] ?? "5") ?> Seats</div>
                                <div class="spec"><i class="fa-solid fa-gears"></i><?= e($car["transmission"] ?? "-") ?></div>
                                <div class="spec"><i class="fa-solid fa-gauge-high"></i><?= e($car["horsepower"] ?? "0") ?> hp</div>
                            </div>

                            <div class="total-box">
                                <div><span>Duration</span><strong><?= e($car["rental_days"] ?? 1) ?> day(s)</strong></div>
                                <div><span>Estimated Total</span><strong><?= e(money($car["estimated_total"] ?? 0)) ?></strong></div>
                            </div>

                            <div class="reason">
                                <strong>Why recommended</strong>
                                <ul>
                                    <?php foreach(array_slice($car["match_reasons"] ?? ["Best match for your selected preferences."], 0, 3) as $reason): ?>
                                        <li><?= e($reason) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>

                            <div class="actions">
                                <a class="btn-white" href="<?= e($viewUrl) ?>"><i class="fa-solid fa-circle-info"></i> View</a>
                                <a class="btn-blue" href="<?= e($cartUrl) ?>"><i class="fa-solid fa-cart-plus"></i> Cart</a>
                                <a class="btn-white" href="<?= e($compareUrl) ?>"><i class="fa-solid fa-code-compare"></i> Compare</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php elseif($submitted && !$errors): ?>
            <div class="empty">
                <div class="empty-icon"><i class="fa-solid fa-car-burst"></i></div>
                <h2>No suitable available cars found</h2>
                <p>Try changing date/time, increasing budget, or choosing another priority.</p>
            </div>
        <?php else: ?>
            <div class="empty">
                <div class="empty-icon"><i class="fa-solid fa-comments"></i></div>
                <h2>Complete the chat first</h2>
                <p>Your AI recommendation will appear after you answer all questions.</p>
            </div>
        <?php endif; ?>
    </section>
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
if(avatarBtn && profileDropdown){
    avatarBtn.addEventListener("click", function(event){
        event.stopPropagation();
        profileDropdown.classList.toggle("show");
    });
    document.addEventListener("click", function(){
        profileDropdown.classList.remove("show");
    });
}

const chatWindow = document.getElementById("chatWindow");
const steps = Array.from(document.querySelectorAll(".chat-step"));
const stepText = document.getElementById("stepText");
const progressFill = document.getElementById("progressFill");
const form = document.getElementById("smartSearchForm");

const pickupState = document.getElementById("pickupState");
const pickupLocation = document.getElementById("pickupLocation");
const dropoffLocation = document.getElementById("dropoffLocation");
const pickupDate = document.getElementById("pickupDate");
const pickupHour = document.getElementById("pickupHour");
const pickupMinute = document.getElementById("pickupMinute");
const pickupTime = document.getElementById("pickupTime");
const returnDate = document.getElementById("returnDate");
const returnTime = document.getElementById("returnTime");
const returnTimeDisplay = document.getElementById("returnTimeDisplay");
const pickupTimeError = document.getElementById("pickupTimeError");
const returnDateError = document.getElementById("returnDateError");
const restartSmart = document.getElementById("restartSmart");

function escapeHtml(text){
    const div = document.createElement("div");
    div.textContent = String(text ?? "");
    return div.innerHTML;
}

function localDateValue(date = new Date()){
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, "0");
    const day = String(date.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
}

function addDaysLocal(dateValue, days){
    const date = new Date(`${dateValue}T00:00:00`);
    date.setDate(date.getDate() + days);
    return localDateValue(date);
}

function roundUpToNext5(date){
    const d = new Date(date.getTime());
    d.setSeconds(0, 0);
    const minutes = d.getMinutes();
    const extra = minutes % 5 === 0 ? 0 : 5 - (minutes % 5);
    d.setMinutes(minutes + extra);
    return d;
}

function minPickupDateTime(){
    return roundUpToNext5(new Date(Date.now() + 60 * 60 * 1000));
}

function formatHourLabel(hour){
    const displayHour = hour % 12 === 0 ? 12 : hour % 12;
    const ampm = hour >= 12 ? "PM" : "AM";
    return `${String(displayHour).padStart(2, "0")} ${ampm}`;
}

function formatFixedTimeLabel(timeValue){
    if(!timeValue || !timeValue.includes(":")) return "Same as pickup time";
    const [hourText, minuteText] = timeValue.split(":");
    const hour = Number(hourText);
    const displayHour = hour % 12 === 0 ? 12 : hour % 12;
    const ampm = hour >= 12 ? "PM" : "AM";
    return `${String(displayHour).padStart(2, "0")}:${minuteText} ${ampm} (Fixed)`;
}

function buildTimeOptions(){
    if(!pickupHour || !pickupMinute) return;

    pickupHour.innerHTML = '<option value="">Hour</option>';
    pickupMinute.innerHTML = '<option value="">Minute</option>';

    for(let hour = 0; hour < 24; hour++){
        const value = String(hour).padStart(2, "0");
        pickupHour.add(new Option(formatHourLabel(hour), value));
    }

    for(let minute = 0; minute < 60; minute += 5){
        const value = String(minute).padStart(2, "0");
        pickupMinute.add(new Option(value, value));
    }

    if(pickupTime && pickupTime.value && pickupTime.value.includes(":")){
        const [h, m] = pickupTime.value.split(":");
        pickupHour.value = h;
        pickupMinute.value = m;
    }
}

function syncPickupTime(){
    if(pickupHour && pickupMinute && pickupHour.value !== "" && pickupMinute.value !== ""){
        pickupTime.value = pickupHour.value + ":" + pickupMinute.value;
        returnTime.value = pickupTime.value;
    }else{
        pickupTime.value = "";
        returnTime.value = "";
    }

    if(returnTimeDisplay){
        returnTimeDisplay.querySelector("span").textContent = formatFixedTimeLabel(returnTime.value);
    }
}

function showInlineError(box, message){
    if(!box) return;
    box.textContent = message;
    box.classList.add("show");
}

function clearInlineError(box){
    if(!box) return;
    box.textContent = "";
    box.classList.remove("show");
}

function updateDateLimits(){
    const today = localDateValue();
    const minStart = minPickupDateTime();
    const minStartDate = localDateValue(minStart);

    if(pickupDate){
        pickupDate.min = today;
        if(pickupDate.value && pickupDate.value < today) pickupDate.value = "";
    }

    if(returnDate){
        if(pickupDate && pickupDate.value){
            returnDate.min = addDaysLocal(pickupDate.value, 1);
        }else{
            returnDate.min = addDaysLocal(today, 1);
        }

        if(returnDate.value && returnDate.value < returnDate.min){
            returnDate.value = "";
        }
    }

    filterPickupTimeOptions();

    if(pickupDate && pickupDate.value && pickupDate.value < minStartDate){
        pickupDate.value = "";
    }
}

function filterPickupTimeOptions(){
    if(!pickupDate || !pickupHour || !pickupMinute) return;

    const minStart = minPickupDateTime();
    const selectedDate = pickupDate.value;

    Array.from(pickupHour.options).forEach(option => {
        if(option.value === "") return;
        option.disabled = false;
    });

    Array.from(pickupMinute.options).forEach(option => {
        if(option.value === "") return;
        option.disabled = false;
    });

    if(selectedDate === localDateValue(minStart)){
        const minHour = String(minStart.getHours()).padStart(2, "0");
        const minMinute = String(minStart.getMinutes()).padStart(2, "0");

        Array.from(pickupHour.options).forEach(option => {
            if(option.value === "") return;
            option.disabled = option.value < minHour;
        });

        if(pickupHour.value && pickupHour.value < minHour){
            pickupHour.value = "";
            pickupMinute.value = "";
        }

        Array.from(pickupMinute.options).forEach(option => {
            if(option.value === "") return;
            option.disabled = pickupHour.value === minHour && option.value < minMinute;
        });

        if(pickupHour.value === minHour && pickupMinute.value && pickupMinute.value < minMinute){
            pickupMinute.value = "";
        }
    }

    syncPickupTime();
}

function validatePickupDateTime(){
    clearInlineError(pickupTimeError);
    syncPickupTime();

    if(!pickupDate.value || !pickupTime.value){
        showInlineError(pickupTimeError, "Please select pickup date and time.");
        return false;
    }

    const selected = new Date(`${pickupDate.value}T${pickupTime.value}:00`);
    const minimum = minPickupDateTime();

    if(selected < minimum){
        showInlineError(pickupTimeError, "Pickup time must be at least 1 hour after the current time. Past time is not allowed.");
        return false;
    }

    return true;
}

function validateReturnDate(){
    clearInlineError(returnDateError);
    syncPickupTime();

    if(!returnDate.value){
        showInlineError(returnDateError, "Please select return date.");
        return false;
    }

    if(!validatePickupDateTime()) return false;

    const minReturn = addDaysLocal(pickupDate.value, 1);
    if(returnDate.value < minReturn){
        showInlineError(returnDateError, "Minimum rental duration is 1 day. Return date must be at least the next day.");
        return false;
    }

    const start = new Date(`${pickupDate.value}T${pickupTime.value}:00`);
    const end = new Date(`${returnDate.value}T${returnTime.value}:00`);

    if((end - start) < 86400000){
        showInlineError(returnDateError, "Minimum rental duration is 1 day.");
        return false;
    }

    return true;
}

function updateLocationButtons(){
    const stateId = pickupState ? pickupState.value : "";

    document.querySelectorAll(".location-choice").forEach(button => {
        const match = !stateId || button.dataset.state === stateId;
        button.classList.toggle("is-hidden", !match);
    });
}

function getButtonText(button){
    return button ? button.textContent.trim().replace(/\s+/g, " ") : "Selected";
}

function getFieldAnswerText(step){
    if(step.dataset.step === "4"){
        return `Pickup: ${pickupDate.value} ${pickupTime.value}`;
    }
    if(step.dataset.step === "5"){
        return `Return: ${returnDate.value} ${returnTime.value}`;
    }
    return "Selected";
}

function addUserReply(step, text){
    if(!step || step.classList.contains("answered")) return;
    step.classList.add("answered");

    const reply = document.createElement("div");
    reply.className = "user-reply";
    reply.innerHTML = `<div class="user-bubble"><span>You selected</span><i class="fa-solid fa-check"></i> ${escapeHtml(text)}</div>`;
    step.appendChild(reply);
}

function updateProgress(stepNumber){
    const total = steps.length || 16;
    const progress = Math.min(100, Math.max(6, (stepNumber / total) * 100));
    if(stepText) stepText.textContent = `Step ${Math.min(stepNumber, total)} of ${total}`;
    if(progressFill) progressFill.style.width = progress + "%";
}

function scrollLatestIntoView(){
    if(!chatWindow) return;
    chatWindow.scrollTop = chatWindow.scrollHeight;
}

function revealStep(stepNumber){
    const step = document.querySelector(`.chat-step[data-step="${stepNumber}"]`);
    if(!step) return;

    step.classList.add("visible");
    updateProgress(stepNumber);

    requestAnimationFrame(() => {
        scrollLatestIntoView();
    });
}

function clearFutureSteps(fromStep){
    steps.forEach(step => {
        const number = parseInt(step.dataset.step || "0", 10);
        if(number > fromStep){
            step.classList.remove("visible", "answered");
            const reply = step.querySelector(".user-reply");
            if(reply) reply.remove();
        }
    });
}

function resetDependentLocation(){
    if(pickupLocation) pickupLocation.value = "";
    if(dropoffLocation) dropoffLocation.value = "";
    document.querySelectorAll('.direct-choice[data-target="pickupLocation"], .direct-choice[data-target="dropoffLocation"]').forEach(button => button.classList.remove("selected"));
}

document.querySelectorAll(".direct-choice").forEach(button => {
    button.addEventListener("click", function(){
        const target = document.getElementById(this.dataset.target);
        const step = this.closest(".chat-step");
        const next = parseInt(this.dataset.next || "1", 10);

        if(!target || !step) return;

        if(this.dataset.target === "pickupState"){
            target.value = this.dataset.value;
            resetDependentLocation();
            updateLocationButtons();
        }else{
            target.value = this.dataset.value;
        }

        document.querySelectorAll(`.direct-choice[data-target="${this.dataset.target}"]`).forEach(btn => btn.classList.remove("selected"));
        this.classList.add("selected");

        addUserReply(step, getButtonText(this));
        clearFutureSteps(next - 1);
        revealStep(next);
    });
});

document.querySelectorAll(".option-btn").forEach(button => {
    button.addEventListener("click", function(){
        const name = this.dataset.name;
        const input = document.getElementById(name);
        const step = this.closest(".chat-step");
        const stepNo = parseInt(step.dataset.step || "1", 10);
        const next = Math.min(16, stepNo + 1);

        if(!input || !step) return;

        input.value = this.dataset.value;

        document.querySelectorAll(`.option-btn[data-name="${name}"]`).forEach(btn => btn.classList.remove("selected"));
        this.classList.add("selected");

        addUserReply(step, getButtonText(this));
        clearFutureSteps(stepNo);
        revealStep(next);
    });
});

document.querySelectorAll(".next-btn").forEach(button => {
    button.addEventListener("click", function(){
        const step = this.closest(".chat-step");
        const next = parseInt(this.dataset.next || "1", 10);
        const stepNo = parseInt(step.dataset.step || "1", 10);

        if(stepNo === 4 && !validatePickupDateTime()) return;
        if(stepNo === 5 && !validateReturnDate()) return;

        addUserReply(step, getFieldAnswerText(step));
        clearFutureSteps(stepNo);
        revealStep(next);
    });
});

function initializeSelectedButtons(){
    document.querySelectorAll(".direct-choice").forEach(button => {
        const target = document.getElementById(button.dataset.target);
        if(target && target.value && target.value === button.dataset.value){
            button.classList.add("selected");
        }
    });

    document.querySelectorAll(".option-btn").forEach(button => {
        const input = document.getElementById(button.dataset.name);
        if(input && input.value && input.value === button.dataset.value){
            button.classList.add("selected");
        }
    });
}

if(pickupHour) pickupHour.addEventListener("change", function(){
    filterPickupTimeOptions();
    updateDateLimits();
});
if(pickupMinute) pickupMinute.addEventListener("change", function(){
    syncPickupTime();
    updateDateLimits();
});
if(pickupDate) pickupDate.addEventListener("change", function(){
    updateDateLimits();
    validatePickupDateTime();
});
if(returnDate) returnDate.addEventListener("change", validateReturnDate);

if(form){
    form.addEventListener("submit", function(event){
        if(!validatePickupDateTime() || !validateReturnDate()){
            event.preventDefault();
            return;
        }

        const requiredFields = ["pickupState", "pickupLocation", "dropoffLocation", "pickupDate", "pickupTime", "returnDate", "returnTime", "passengers", "budget", "purpose", "priority", "luggage", "road_type", "driving_style", "fuel_pref", "brand_pref", "feature"];
        for(const id of requiredFields){
            const field = document.getElementById(id);
            if(!field || !field.value){
                event.preventDefault();
                alert("Please complete all questions before generating AI match.");
                return;
            }
        }
    });
}

if(restartSmart){
    restartSmart.addEventListener("click", function(){
        window.location.href = "find_car_smart.php";
    });
}

buildTimeOptions();
syncPickupTime();
updateDateLimits();
updateLocationButtons();
initializeSelectedButtons();

<?php if($submitted || ($hasTrip && $hasAnswers)): ?>
    steps.forEach(step => step.classList.add("visible"));
    updateProgress(16);
<?php else: ?>
    revealStep(1);
<?php endif; ?>
</script>
</body>
</html>
