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

function fetchRows($conn, $sql) {
    $result = $conn->query($sql);
    if (!$result) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getCarImages($conn, $carId, $fallbackImage = "") {
    $images = [];

    if (tableExists($conn, "car_images")) {
        $carImageCarCol = firstColumn($conn, "car_images", ["car_id"], "car_id");
        $carImageUrlCol = firstColumn($conn, "car_images", ["image_url", "image", "image_path"], "image_url");
        $carImageSortCol = firstColumn($conn, "car_images", ["sort_order"], null);

        $orderBy = $carImageSortCol ? "ORDER BY $carImageSortCol ASC" : "ORDER BY image_id ASC";
        $stmt = $conn->prepare("SELECT $carImageUrlCol AS image_url FROM car_images WHERE $carImageCarCol = ? $orderBy LIMIT 5");
        $stmt->bind_param("i", $carId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if (!empty($row["image_url"])) $images[] = $row["image_url"];
        }

        $stmt->close();
    }

    if (empty($images) && !empty($fallbackImage)) $images[] = $fallbackImage;
    return $images;
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
        . '<text x="450" y="430" text-anchor="middle" font-family="Segoe UI, Arial" font-size="18" font-weight="700" fill="#6e8297">Upload real vehicle photos to replace this placeholder</text>'
        . '</svg>';

    return 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($svg);
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

$navCartCount = getNavCartCount($conn);

$fixedCategories = ["Sedan", "SUV", "MPV", "Hatchback", "Pickup", "Luxury", "Sport", "Coupe", "EV"];
$fixedBrands = ["Mazda", "Toyota", "Honda", "Perodua", "Proton", "Nissan", "Mercedes", "BMW", "Lexus", "Volkswagen"];

$states = [];
$locations = [];
$availableCars = [];
$brands = $fixedBrands;
$categories = $fixedCategories;

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
    $addressCol = firstColumn($conn, "rental_locations", ["address"], null);
    $embedCol = firstColumn($conn, "rental_locations", ["map_embed_url", "embed_url"], null);
    $mapCol = firstColumn($conn, "rental_locations", ["map_url", "google_map_url"], null);
    $statusCol = firstColumn($conn, "rental_locations", ["status"], null);

    $whereLocation = $statusCol ? "WHERE LOWER($statusCol) IN ('active','available') OR $statusCol = 1" : "";

    $locations = fetchRows($conn, "
        SELECT 
            $locationIdCol AS location_id,
            $locationNameCol AS location_name,
            $locationStateCol AS state_id,
            " . ($addressCol ? "$addressCol" : "''") . " AS address,
            " . ($embedCol ? "$embedCol" : "''") . " AS map_embed_url,
            " . ($mapCol ? "$mapCol" : "''") . " AS map_url
        FROM rental_locations
        $whereLocation
        ORDER BY $locationStateCol ASC, $locationNameCol ASC
    ");
}

if (!$locations) {
    $locations = [
        ["location_id" => 1, "location_name" => "JB Sentral", "state_id" => 1, "address" => "JB Sentral, Johor Bahru, Johor", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 2, "location_name" => "Johor Bahru City Centre", "state_id" => 1, "address" => "Johor Bahru City Centre, Johor", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 3, "location_name" => "Larkin Sentral", "state_id" => 1, "address" => "Larkin Sentral, Johor Bahru, Johor", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 4, "location_name" => "Melaka Sentral", "state_id" => 2, "address" => "Melaka Sentral, Melaka", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 5, "location_name" => "MMU Melaka", "state_id" => 2, "address" => "Multimedia University Melaka, Bukit Beruang, Melaka", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 6, "location_name" => "Ayer Keroh", "state_id" => 2, "address" => "Ayer Keroh, Melaka", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 7, "location_name" => "KL Sentral", "state_id" => 3, "address" => "KL Sentral, Kuala Lumpur", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 8, "location_name" => "Bukit Bintang", "state_id" => 3, "address" => "Bukit Bintang, Kuala Lumpur", "map_embed_url" => "", "map_url" => ""],
        ["location_id" => 9, "location_name" => "TBS Kuala Lumpur", "state_id" => 3, "address" => "TBS Kuala Lumpur", "map_embed_url" => "", "map_url" => ""]
    ];
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

$stateId = findStateIdFromValue($states, $_GET["state"] ?? $_GET["pickup_state"] ?? "");
$pickupLocationId = (int)($_GET["pickup_location"] ?? 0);
$dropoffLocationId = (int)($_GET["dropoff_location"] ?? 0);
$pickupDate = trim($_GET["pickup_date"] ?? "");
$pickupTime = trim($_GET["pickup_time"] ?? "");
if ($pickupTime === "" && isset($_GET["pickup_hour"], $_GET["pickup_minute"])) {
    $pickupTime = str_pad((string)$_GET["pickup_hour"], 2, "0", STR_PAD_LEFT) . ":" . str_pad((string)$_GET["pickup_minute"], 2, "0", STR_PAD_LEFT);
}
$returnDate = trim($_GET["return_date"] ?? "");
$returnTime = trim($_GET["return_time"] ?? "");
if ($returnTime === "" && $pickupTime !== "") $returnTime = $pickupTime;
$selectedCategory = trim($_GET["category"] ?? "All");
$selectedBrand = trim($_GET["brand"] ?? "All");
$keyword = trim($_GET["keyword"] ?? "");
$minPrice = trim($_GET["min_price"] ?? "");
$maxPrice = trim($_GET["max_price"] ?? "");
$seats = trim($_GET["seats"] ?? "All");
$transmission = trim($_GET["transmission"] ?? "All");
$fuel = trim($_GET["fuel"] ?? "All");
$sort = trim($_GET["sort"] ?? "default");
$selectedCarId = max(0, (int)($_GET["car_id"] ?? 0));

$pickupDateTime = ($pickupDate && $pickupTime) ? "$pickupDate $pickupTime:00" : "";
$returnDateTime = ($returnDate && $returnTime) ? "$returnDate $returnTime:00" : "";

$selectedState = null;
$pickupLocation = null;
$dropoffLocation = null;

foreach ($states as $state) {
    if ((int)$state["state_id"] === $stateId) $selectedState = $state;
}

foreach ($locations as $location) {
    if ((int)$location["location_id"] === $pickupLocationId) $pickupLocation = $location;
    if ((int)$location["location_id"] === $dropoffLocationId) $dropoffLocation = $location;
}

$hasTrip = $stateId > 0 && $pickupLocationId > 0 && $dropoffLocationId > 0 && $pickupDateTime && $returnDateTime;
$tripInvalid = false;

if ($pickupDateTime && $returnDateTime && strtotime($returnDateTime) <= strtotime($pickupDateTime)) {
    $tripInvalid = true;
}

$rentalDays = 0;
if ($hasTrip && !$tripInvalid) {
    $rentalDays = max(1, (int)ceil((strtotime($returnDateTime) - strtotime($pickupDateTime)) / 86400));
}

$pickupLabel = ($pickupDateTime && strtotime($pickupDateTime)) ? date("d M Y, h:i A", strtotime($pickupDateTime)) : "Not selected";
$returnLabel = ($returnDateTime && strtotime($returnDateTime)) ? date("d M Y, h:i A", strtotime($returnDateTime)) : "Not selected";

if ($hasTrip && !$tripInvalid && tableExists($conn, "cars") && tableExists($conn, "car_units")) {
    $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
    $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
    $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
    $imageCol = firstColumn($conn, "cars", ["image", "main_image", "car_image"], null);
    $brandCol = firstColumn($conn, "cars", ["brand", "brand_name"], null);
    $brandIdCol = firstColumn($conn, "cars", ["brand_id"], null);
    $categoryCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
    $categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
    $yearCol = firstColumn($conn, "cars", ["year", "car_year"], null);
    $seatsCol = firstColumn($conn, "cars", ["seats"], null);
    $transmissionCol = firstColumn($conn, "cars", ["transmission"], null);
    $fuelCol = firstColumn($conn, "cars", ["fuel_type"], null);
    $engineCol = firstColumn($conn, "cars", ["engine", "engine_capacity"], null);
    $horsepowerCol = firstColumn($conn, "cars", ["horsepower", "hp"], null);
    $drivetrainCol = firstColumn($conn, "cars", ["drivetrain"], null);
    $tagCol = firstColumn($conn, "cars", ["car_tag", "tag", "badge", "best_for"], null);
    $statusCol = firstColumn($conn, "cars", ["status", "availability"], null);
    $descCol = null;

    $unitIdCol = firstColumn($conn, "car_units", ["unit_id", "id"], "unit_id");
    $unitCarCol = firstColumn($conn, "car_units", ["car_id"], "car_id");
    $unitStateCol = firstColumn($conn, "car_units", ["state_id"], null);
    $unitStatusCol = firstColumn($conn, "car_units", ["current_status", "status"], null);
    $unitColorCol = firstColumn($conn, "car_units", ["color", "car_color", "unit_color"], null);

    $select = [
        "c.$carIdCol AS car_id",
        "c.$carNameCol AS car_name",
        ($priceCol ? "c.$priceCol" : "0") . " AS price_per_day",
        ($imageCol ? "c.$imageCol" : "''") . " AS image",
        ($yearCol ? "c.$yearCol" : "''") . " AS car_year",
        ($seatsCol ? "c.$seatsCol" : "5") . " AS seats",
        ($transmissionCol ? "c.$transmissionCol" : "'Automatic'") . " AS transmission",
        ($fuelCol ? "c.$fuelCol" : "'Petrol'") . " AS fuel_type",
        ($engineCol ? "c.$engineCol" : "'-'") . " AS engine",
        ($horsepowerCol ? "c.$horsepowerCol" : "0") . " AS horsepower",
        ($drivetrainCol ? "c.$drivetrainCol" : "'FWD'") . " AS drivetrain",
        ($tagCol ? "c.$tagCol" : "''") . " AS car_tag",
        "'' AS description",
        "COUNT(DISTINCT cu.$unitIdCol) AS available_units",
        ($unitColorCol ? "MIN(NULLIF(TRIM(cu.$unitColorCol), ''))" : "'Not specified'") . " AS car_color"
    ];

    $join = " INNER JOIN car_units cu ON cu.$unitCarCol = c.$carIdCol ";

    if ($brandIdCol && tableExists($conn, "brands")) {
        $brandPk = firstColumn($conn, "brands", ["brand_id", "id"], "id");
        $brandNameCol = firstColumn($conn, "brands", ["brand_name", "name"], "brand_name");
        $select[] = "COALESCE(b.$brandNameCol, '-') AS brand";
        $join .= " LEFT JOIN brands b ON b.$brandPk = c.$brandIdCol ";
    } elseif ($brandIdCol && tableExists($conn, "car_brands")) {
        $brandPk = firstColumn($conn, "car_brands", ["brand_id", "id"], "brand_id");
        $brandNameCol = firstColumn($conn, "car_brands", ["brand_name", "name"], "brand_name");
        $select[] = "COALESCE(b.$brandNameCol, '-') AS brand";
        $join .= " LEFT JOIN car_brands b ON b.$brandPk = c.$brandIdCol ";
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

    $where = ["1=1"];

    if ($statusCol) $where[] = "(LOWER(c.$statusCol) IN ('active','available') OR c.$statusCol = 1)";
    if ($unitStateCol) $where[] = "cu.$unitStateCol = " . (int)$stateId;
    if ($unitStatusCol) $where[] = "LOWER(COALESCE(cu.$unitStatusCol, 'available')) NOT IN ('maintenance','inactive')";
    if ($selectedCarId > 0) $where[] = "c.$carIdCol = " . (int)$selectedCarId;

    if ($selectedBrand !== "" && $selectedBrand !== "All") {
        $safeBrand = $conn->real_escape_string($selectedBrand);
        if ($brandIdCol && tableExists($conn, "brands")) {
            $where[] = "LOWER(b.$brandNameCol) = LOWER('$safeBrand')";
        } elseif ($brandIdCol && tableExists($conn, "car_brands")) {
            $where[] = "LOWER(b.$brandNameCol) = LOWER('$safeBrand')";
        } elseif ($brandCol) {
            $where[] = "LOWER(c.$brandCol) = LOWER('$safeBrand')";
        }
    }

    if ($selectedCategory !== "" && $selectedCategory !== "All") {
        $safeCategory = $conn->real_escape_string($selectedCategory);
        if ($categoryIdCol && (tableExists($conn, "categories") || tableExists($conn, "vehicle_categories"))) {
            $where[] = "LOWER(cat.$categoryNameCol) = LOWER('$safeCategory')";
        } elseif ($categoryCol) {
            $where[] = "LOWER(c.$categoryCol) = LOWER('$safeCategory')";
        }
    }

    if ($keyword !== "") {
        $safeKey = $conn->real_escape_string($keyword);
        $where[] = "(LOWER(c.$carNameCol) LIKE LOWER('%$safeKey%')" . ($brandCol ? " OR LOWER(c.$brandCol) LIKE LOWER('%$safeKey%')" : "") . ")";
    }

    if ($minPrice !== "" && $priceCol) $where[] = "c.$priceCol >= " . (float)$minPrice;
    if ($maxPrice !== "" && $priceCol) $where[] = "c.$priceCol <= " . (float)$maxPrice;
    if ($seats !== "All" && $seatsCol) $where[] = "c.$seatsCol >= " . (int)$seats;
    if ($transmission !== "All" && $transmissionCol) $where[] = "LOWER(c.$transmissionCol) = LOWER('" . $conn->real_escape_string($transmission) . "')";
    if ($fuel !== "All" && $fuelCol) $where[] = "LOWER(c.$fuelCol) = LOWER('" . $conn->real_escape_string($fuel) . "')";

    if (tableExists($conn, "booking_items") && tableExists($conn, "bookings")) {
        $bookingItemCarCol = firstColumn($conn, "booking_items", ["car_id"], "car_id");
        $bookingItemBookingCol = firstColumn($conn, "booking_items", ["booking_id"], "booking_id");
        $bookingItemUnitCol = firstColumn($conn, "booking_items", ["unit_id", "car_unit_id"], null);
        $bookingItemStartCol = firstColumn($conn, "booking_items", ["start_datetime", "pickup_datetime"], "start_datetime");
        $bookingItemEndCol = firstColumn($conn, "booking_items", ["end_datetime", "return_datetime"], "end_datetime");
        $bookingPk = firstColumn($conn, "bookings", ["booking_id", "id"], "booking_id");
        $bookingStatusCol = firstColumn($conn, "bookings", ["booking_status", "status"], null);
        $blockedStatus = $bookingStatusCol ? "AND LOWER(b.$bookingStatusCol) NOT IN ('cancelled','rejected')" : "";
        $unitOverlapMatch = $bookingItemUnitCol
            ? "(bi.$bookingItemUnitCol = cu.$unitIdCol OR (bi.$bookingItemUnitCol IS NULL AND bi.$bookingItemCarCol = c.$carIdCol))"
            : "bi.$bookingItemCarCol = c.$carIdCol";

        $where[] = "NOT EXISTS (
            SELECT 1
            FROM booking_items bi
            INNER JOIN bookings b ON b.$bookingPk = bi.$bookingItemBookingCol
            WHERE $unitOverlapMatch
            $blockedStatus
            AND bi.$bookingItemStartCol < '" . $conn->real_escape_string($returnDateTime) . "'
            AND bi.$bookingItemEndCol > '" . $conn->real_escape_string($pickupDateTime) . "'
        )";
    }

    $availableCars = fetchRows($conn, "
        SELECT " . implode(", ", $select) . "
        FROM cars c
        $join
        WHERE " . implode(" AND ", $where) . "
        GROUP BY c.$carIdCol
    ");

    foreach ($availableCars as $car) {
        if (!empty($car["category_name"]) && !in_array($car["category_name"], $categories, true)) $categories[] = $car["category_name"];
        if (!empty($car["brand"]) && !in_array($car["brand"], $brands, true)) $brands[] = $car["brand"];
    }
}

usort($availableCars, function($a, $b) use ($sort) {
    if ($sort === "price_low") return (float)$a["price_per_day"] <=> (float)$b["price_per_day"];
    if ($sort === "price_high") return (float)$b["price_per_day"] <=> (float)$a["price_per_day"];
    if ($sort === "seats") return (int)$b["seats"] <=> (int)$a["seats"];
    if ($sort === "horsepower") return (int)$b["horsepower"] <=> (int)$a["horsepower"];
    if ($sort === "newest") return (int)$b["car_year"] <=> (int)$a["car_year"];
    return strcmp($a["car_name"], $b["car_name"]);
});

$mapQuery = $pickupLocation ? (($pickupLocation["address"] ?: $pickupLocation["location_name"]) . " Malaysia") : "Malaysia";
$mapEmbed = $pickupLocation["map_embed_url"] ?? "";
$mapUrl = $pickupLocation["map_url"] ?? "";
if (!$mapEmbed) $mapEmbed = "https://www.google.com/maps?q=" . urlencode($mapQuery) . "&output=embed";
if (!$mapUrl) $mapUrl = "https://www.google.com/maps/search/?api=1&query=" . urlencode($mapQuery);

function keepUrl($updates = []) {
    $query = array_merge($_GET, $updates);
    return "available_cars.php?" . http_build_query($query);
}

function carDetailsUrl($carId) {
    global $stateId, $pickupLocationId, $dropoffLocationId, $pickupDate, $pickupTime, $returnDate, $returnTime;

    $query = ["car_id" => (int)$carId];

    if ($stateId > 0 && $pickupLocationId > 0 && $dropoffLocationId > 0 && $pickupDate !== "" && $pickupTime !== "" && $returnDate !== "" && $returnTime !== "") {
        $query["state"] = (int)$stateId;
        $query["pickup_location"] = (int)$pickupLocationId;
        $query["dropoff_location"] = (int)$dropoffLocationId;
        $query["pickup_date"] = $pickupDate;
        $query["pickup_time"] = $pickupTime;
        $query["return_date"] = $returnDate;
        $query["return_time"] = $returnTime;
    }

    return "car_details.php?" . http_build_query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Available Cars | KH Car Rental</title>
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

/* ===== Hero ===== */
.hero{
    width:min(1280px,100%);
    margin:14px auto 0;
    padding:0 22px;
}
.hero-card{
    min-height:168px;
    padding:22px 28px;
    border-radius:26px;
    display:grid;
    grid-template-columns:1.45fr .9fr;
    gap:22px;
    align-items:center;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
    border:1px solid rgba(184,228,255,.82);
    box-shadow:var(--shadow);
}
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
.hero h1{
    font-size:clamp(36px,3.8vw,54px);
    line-height:1;
    letter-spacing:-1.9px;
    font-weight:950;
    margin-bottom:10px;
}
.hero p{
    max-width:660px;
    color:var(--muted);
    font-size:14px;
    line-height:1.48;
    font-weight:650;
}
.hero-badges{
    display:flex;
    flex-wrap:wrap;
    gap:9px;
    margin-top:16px;
}
.hero-badge{
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
.hero-badge i{color:var(--sky600)}

/* ===== Trip search ===== */
.trip-card{
    padding:16px 18px;
    border-radius:22px;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
    border:1px solid rgba(184,228,255,.82);
    box-shadow:0 22px 50px rgba(40,168,234,.12);
}
.trip-card.search-closed{
    min-height:88px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
}
.trip-card.search-closed .trip-form{display:none}
.trip-card.search-open{display:block}
.trip-card.search-open .trip-form{display:grid}
.trip-top{
    width:100%;
    margin:0;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
}
.trip-top h2{
    font-size:20px;
    line-height:1.05;
    letter-spacing:-.35px;
    font-weight:950;
}
.trip-top p{
    color:var(--muted);
    font-size:11.5px;
    line-height:1.55;
    margin-top:5px;
    font-weight:700;
}
.modify-btn{
    min-width:100px;
    min-height:36px;
    padding:0 13px;
    border:1px solid var(--border);
    border-radius:13px;
    background:var(--sky100);
    color:var(--sky600);
    font-size:12px;
    font-weight:950;
    cursor:pointer;
    white-space:nowrap;
    flex:0 0 auto;
}
.trip-form{
    margin-top:12px;
    grid-template-columns:1fr 1fr;
    gap:8px;
}
.full{grid-column:1/-1}
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

/* ===== Main layout ===== */
.main{
    width:min(1280px,100%);
    margin:14px auto 52px;
    padding:0 22px;
}
.catalogue-control-layout{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    align-items:start;
    margin-bottom:12px;
}
.category-panel,
.tools-panel{
    height:auto;
    min-height:0;
    padding:13px 18px;
    border-radius:22px;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
    border:1px solid rgba(184,228,255,.82);
    box-shadow:0 16px 44px rgba(39,137,199,.10);
}
.category-title{margin-bottom:8px}
.category-header-row{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:14px;
    margin-top:8px;
}
.category-header-row .pill{margin-bottom:8px}
.category-header-row h2{
    font-size:21px;
    line-height:1.05;
    margin:0 0 4px;
    font-weight:950;
}
.category-header-row p{
    color:var(--muted);
    font-size:12px;
    line-height:1.2;
    font-weight:650;
}
.category-header-row .category-all-tab{
    display:inline-flex;
    width:118px;
    height:36px;
    min-height:36px;
    flex:0 0 118px;
    border-radius:13px;
    font-size:12px;
    padding:0 12px;
    justify-content:center;
    align-items:center;
    gap:7px;
}
.category-tabs{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
    align-content:start;
}
.category-tabs a[href="catalogue.php#catalogueResults"],
.category-tabs .category-all-tab{display:none!important}
.category-tab{
    height:36px;
    min-height:36px;
    border-radius:13px;
    padding:0 10px;
    display:flex;
    align-items:center;
    justify-content:center;
    gap:6px;
    background:rgba(255,255,255,.84);
    border:1px solid var(--border);
    color:#24415f;
    font-size:12px;
    font-weight:950;
    transition:.24s;
    box-shadow:0 10px 24px rgba(40,168,234,.05);
}
.category-tab:hover,
.category-tab.active{
    color:#fff;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    box-shadow:0 18px 36px rgba(40,168,234,.22);
}

/* ===== Filter panel ===== */
.tools-form{
    display:grid;
    grid-template-columns:1fr 1fr;
    grid-template-areas:
        "keyword keyword"
        "brand sort"
        "min max"
        "actions actions"
        "advanced advanced";
    gap:7px 10px;
    align-content:start;
}
.tools-form > div:nth-of-type(1){grid-area:keyword}
.tools-form > div:nth-of-type(2){grid-area:brand}
.tools-form > div:nth-of-type(3){grid-area:min}
.tools-form > div:nth-of-type(4){grid-area:max}
.tools-form > div:nth-of-type(5){grid-area:sort}
.filter-actions-row{
    grid-area:actions;
    display:grid;
    grid-template-columns:1fr 1fr 1fr;
    gap:9px;
}
.filter-actions-row .btn,
.filter-actions-row .filter-toggle{
    width:100%;
    height:36px;
    min-height:36px;
}
.filter-toggle{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    color:var(--sky600);
    background:linear-gradient(135deg,#fff,var(--sky100));
    border:1px solid var(--border);
    border-radius:12px;
    font-size:12px;
    font-weight:950;
    cursor:pointer;
    transition:.24s;
}
.advanced-filter{
    grid-area:advanced;
    display:none;
    padding:9px;
    margin-top:0;
    border-radius:14px;
    background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(234,247,255,.72));
    border:1px solid var(--border);
}
.advanced-filter.show{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:8px;
}

/* ===== Results and cards ===== */
.result-head{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:18px;
    margin:12px 0 8px;
}
.result-head h2{
    font-size:26px;
    line-height:1.1;
    font-weight:950;
    letter-spacing:-.8px;
}
.result-head p{
    color:var(--muted);
    font-size:13px;
    line-height:1.4;
    font-weight:650;
}
.count-tag{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border-radius:999px;
    color:var(--sky600);
    background:var(--sky100);
    font-size:12.5px;
    font-weight:950;
    white-space:nowrap;
}
.car-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}
.car-card{
    border-radius:24px;
    background:
        radial-gradient(circle at 100% 0%,rgba(40,168,234,.08),transparent 28%),
        linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));
    border:1px solid rgba(184,228,255,.98);
    box-shadow:0 18px 46px rgba(29,109,164,.12);
    transition:.28s cubic-bezier(.2,.8,.2,1);
}
.car-card::before{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(130deg,transparent 0%,rgba(255,255,255,.55) 42%,transparent 58%);
    transform:translateX(-130%);
    transition:transform .75s ease;
    z-index:1;
    pointer-events:none;
}
.car-card:hover::before{transform:translateX(130%)}
.car-card:hover{
    transform:translateY(-9px);
    border-color:rgba(40,168,234,.42);
    box-shadow:0 28px 70px rgba(29,109,164,.20);
}
.car-media{
    position:relative;
    height:172px;
    display:grid;
    place-items:center;
    overflow:hidden;
    background:
        radial-gradient(circle at 78% 22%,rgba(40,168,234,.18),transparent 34%),
        linear-gradient(135deg,#edf9ff,#ffffff);
    z-index:2;
}
.car-media::after{
    content:"";
    position:absolute;
    inset:0;
    background:linear-gradient(180deg,rgba(16,35,61,.12) 0%,transparent 42%,rgba(16,35,61,.18) 100%);
    z-index:1;
    pointer-events:none;
}
.no-image{
    width:86px;
    height:86px;
    border-radius:30px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:linear-gradient(135deg,#d8f2ff,#fff);
    border:1px solid var(--border);
    font-size:36px;
}
.car-body{
    position:relative;
    z-index:2;
    padding:16px;
}
.car-title{
    display:flex;
    align-items:flex-start;
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
    padding:6px 9px;
    border-radius:999px;
    background:var(--sky100);
    color:var(--sky600);
    font-size:11px;
    font-weight:950;
    white-space:nowrap;
}
.spec-grid{
    display:grid;
    grid-template-columns:repeat(2,1fr);
    gap:7px;
    margin:10px 0;
}
.spec{
    display:flex;
    align-items:center;
    gap:9px;
    min-height:33px;
    padding:7px 9px;
    border-radius:12px;
    background:linear-gradient(135deg,rgba(234,247,255,.92),rgba(255,255,255,.82));
    border:1px solid rgba(216,236,251,.68);
    color:#2b4969;
    font-size:11.5px;
    font-weight:850;
}
.spec i{color:var(--sky600)}
.colour-spec{
    grid-column:1/-1;
    justify-content:flex-start;
    min-height:40px;
    padding:10px 13px;
    border-radius:16px;
    background:
        linear-gradient(135deg, rgba(40,168,234,.12), rgba(255,255,255,.92)),
        radial-gradient(circle at 96% 15%, rgba(255,122,26,.12), transparent 34%);
    border:1px solid rgba(40,168,234,.26);
    box-shadow:inset 0 1px 0 rgba(255,255,255,.78), 0 10px 22px rgba(40,168,234,.08);
    color:#17304f;
    font-weight:950;
}
.colour-spec i{
    width:24px;
    height:24px;
    display:inline-grid;
    place-items:center;
    border-radius:50%;
    background:linear-gradient(135deg,#d8f2ff,#ffffff);
    color:var(--sky600);
    box-shadow:0 7px 16px rgba(40,168,234,.13);
}
.desc{
    color:var(--muted);
    font-size:12px;
    line-height:1.45;
    font-weight:650;
    min-height:34px;
}
.actions{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin-top:12px;
}
.actions .btn{
    min-height:40px;
    border-radius:13px;
    font-size:12px;
}
.actions .btn-white:hover{
    color:var(--sky600);
    border-color:rgba(40,168,234,.38);
    background:linear-gradient(135deg,#ffffff,var(--sky100));
}
.car-tags{
    position:absolute;
    left:16px;
    top:16px;
    display:flex;
    gap:8px;
    flex-wrap:wrap;
    z-index:6;
}
.tag{
    padding:6px 9px;
    border-radius:999px;
    background:rgba(255,255,255,.92);
    color:var(--sky600);
    border:1px solid var(--border);
    font-size:10.5px;
    font-weight:950;
}
.tag.orange{
    color:var(--orange2);
    border-color:rgba(255,122,26,.28);
    background:rgba(255,247,239,.95);
}
.price-chip{
    position:absolute;
    right:16px;
    bottom:16px;
    z-index:6;
    padding:9px 12px;
    border-radius:15px;
    color:#fff;
    background:linear-gradient(135deg,#ff9a4a,#f15f12);
    box-shadow:0 16px 34px rgba(255,122,26,.30);
    font-size:12.5px;
    font-weight:950;
}
.empty{
    padding:38px;
    border-radius:28px;
    text-align:center;
    background:rgba(255,255,255,.78);
    border:1px dashed var(--border);
    color:var(--muted);
    font-weight:650;
}

/* ===== Image carousel ===== */
.car-carousel{
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    z-index:0;
    overflow:hidden;
    background:linear-gradient(135deg,#edf9ff,#ffffff);
}
.carousel-track{
    position:relative;
    width:100%;
    height:100%;
}
.carousel-img{
    position:absolute;
    inset:0;
    width:100%;
    height:100%;
    object-fit:cover;
    opacity:0;
    transform:scale(1.04);
    transition:opacity .42s ease,transform .55s ease;
}
.carousel-img.active{
    opacity:1;
    transform:scale(1);
}
.car-card:hover .carousel-img.active{transform:scale(1.045)}
.carousel-btn{
    position:absolute;
    top:50%;
    transform:translateY(-50%);
    width:36px;
    height:36px;
    border:1px solid rgba(255,255,255,.72);
    border-radius:50%;
    background:rgba(255,255,255,.86);
    color:var(--sky600);
    display:grid;
    place-items:center;
    cursor:pointer;
    z-index:5;
    opacity:0;
    transition:.25s ease;
    box-shadow:0 10px 24px rgba(16,35,61,.16);
    backdrop-filter:blur(12px);
}
.carousel-prev{left:14px}
.carousel-next{right:14px}
.car-card:hover .carousel-btn{opacity:1}
.carousel-btn:hover{
    background:var(--sky600);
    color:#fff;
    transform:translateY(-50%) scale(1.08);
}
.carousel-dots{
    position:absolute;
    left:50%;
    bottom:12px;
    transform:translateX(-50%);
    display:flex;
    gap:6px;
    z-index:5;
    padding:6px 8px;
    border-radius:999px;
    background:rgba(16,35,61,.28);
    backdrop-filter:blur(10px);
}
.carousel-dot{
    width:7px;
    height:7px;
    border-radius:999px;
    border:0;
    background:rgba(255,255,255,.62);
    cursor:pointer;
    transition:.22s ease;
}
.carousel-dot.active{
    width:18px;
    background:#fff;
}

/* ===== Modal ===== */
.modal{
    position:fixed;
    inset:0;
    z-index:999;
    display:none;
    place-items:center;
    background:rgba(13,31,55,.42);
    backdrop-filter:blur(10px);
    padding:18px;
}
.modal.show{display:grid}
.modal-card{
    width:min(620px,100%);
    border-radius:32px;
    background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.94));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 34px 90px rgba(23,48,79,.24);
    overflow:hidden;
}
.modal-head{
    padding:24px 26px 16px;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
}
.modal-head h2{
    font-size:28px;
    font-weight:950;
    letter-spacing:-.8px;
    margin-bottom:6px;
}
.modal-head p{color:var(--muted);font-weight:650}
.close{
    width:40px;
    height:40px;
    border:0;
    border-radius:14px;
    cursor:pointer;
    color:var(--sky600);
    background:var(--sky100);
}
.modal-body{padding:0 26px 26px}
.modal-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:12px;
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


/* ===== Check Availability Modal Result ===== */
.availability-result{
    display:none;
    grid-column:1/-1;
    border-radius:22px;
    padding:18px;
    border:1px solid var(--border);
    background:linear-gradient(135deg,rgba(255,255,255,.96),rgba(234,247,255,.68));
}
.availability-result.show{display:block}
.availability-result.success{
    border-color:rgba(20,184,116,.28);
    background:linear-gradient(135deg,#f0fff8,#ffffff);
}
.availability-result.danger{
    border-color:rgba(244,67,54,.24);
    background:linear-gradient(135deg,#fff4f2,#ffffff);
}
.availability-status{
    display:flex;
    align-items:flex-start;
    gap:12px;
    margin-bottom:14px;
}
.availability-status .status-icon{
    width:42px;
    height:42px;
    border-radius:16px;
    display:grid;
    place-items:center;
    flex:0 0 auto;
    color:#ffffff;
    background:linear-gradient(135deg,#17b26a,#079455);
}
.availability-result.danger .status-icon{
    background:linear-gradient(135deg,#ff6b5f,#d92d20);
}
.availability-status h3{
    font-size:20px;
    font-weight:950;
    line-height:1.15;
    margin-bottom:4px;
}
.availability-status p{
    color:var(--muted);
    font-size:13px;
    line-height:1.5;
    font-weight:650;
}
.availability-detail-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
    margin:12px 0 14px;
}
.availability-detail{
    border-radius:15px;
    padding:10px 12px;
    background:rgba(255,255,255,.84);
    border:1px solid rgba(216,236,251,.82);
}
.availability-detail span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:3px;
}
.availability-detail strong{
    display:block;
    color:var(--dark);
    font-size:13px;
    font-weight:950;
}
.availability-actions{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.availability-actions.three{
    grid-template-columns:1fr 1fr 1fr;
}
@media(max-width:760px){
    .availability-detail-grid,
    .availability-actions,
    .availability-actions.three{
        grid-template-columns:1fr;
    }
}


/* ===== Available Cars Page ===== */
.available-hero{
    width:min(1280px,100%);
    margin:14px auto 0;
    padding:0 22px;
}
.available-hero-card{
    padding:26px 28px;
    border-radius:28px;
    display:grid;
    grid-template-columns:.92fr 1.28fr;
    gap:22px;
    align-items:stretch;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.20),transparent 30%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
    border:1px solid rgba(184,228,255,.82);
    box-shadow:var(--shadow);
}
.available-hero h1{
    font-size:clamp(38px,4vw,58px);
    line-height:1;
    letter-spacing:-1.8px;
    font-weight:950;
    margin-bottom:12px;
}
.available-hero p{
    max-width:760px;
    color:var(--muted);
    font-size:14.5px;
    line-height:1.55;
    font-weight:650;
}
.trip-map-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:14px;
}
.hero-left-stack{
    display:flex;
    flex-direction:column;
    gap:16px;
}
.hero-left-stack .trip-summary-card{
    margin-top:2px;
}
.available-hero .hero-badges{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:10px;
    max-width:560px;
}
.available-hero .hero-badge{
    width:100%;
    justify-content:center;
}
.trip-summary-card,
.pickup-map-card{
    border-radius:24px;
    padding:18px;
    background:linear-gradient(135deg,rgba(255,255,255,.88),rgba(234,247,255,.72));
    border:1px solid var(--border);
    box-shadow:var(--soft);
}
.summary-title{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:12px;
    margin-bottom:12px;
}
.summary-title h2,
.pickup-map-card h2{
    font-size:22px;
    line-height:1.05;
    font-weight:950;
    letter-spacing:-.4px;
}
.summary-grid{
    display:grid;
    grid-template-columns:1fr;
    gap:9px;
}
.summary-item{
    min-height:58px;
    border-radius:16px;
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:10px;
    background:rgba(255,255,255,.88);
    border:1px solid rgba(216,236,251,.8);
}
.summary-item i{
    width:34px;
    height:34px;
    border-radius:12px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:var(--sky100);
}
.summary-item span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:2px;
}
.summary-item strong{
    display:block;
    color:var(--dark);
    font-size:13px;
    font-weight:950;
}
.map-box{
    margin-top:12px;
    overflow:hidden;
    border-radius:19px;
    border:1px solid var(--border);
    background:var(--sky100);
}
.map-box iframe{
    width:100%;
    height:390px;
    border:0;
    display:block;
}
.available-hero .pickup-map-card .map-box iframe{
    height:430px;
}
.available-hero .pickup-map-card{
    min-height:100%;
    display:flex;
    flex-direction:column;
}
.available-hero .pickup-map-card .map-box{
    flex:1;
}
.available-hero .pickup-map-card .map-box iframe{
    min-height:430px;
}
.location-address{
    color:var(--muted);
    font-size:13px;
    line-height:1.5;
    font-weight:700;
    margin-top:10px;
}
.map-actions{
    margin-top:12px;
    display:grid;
    grid-template-columns:1fr;
}
.available-main{
    width:min(1280px,100%);
    margin:18px auto 54px;
    padding:0 22px;
}
.search-panel,
.available-content-card{
    border-radius:26px;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.16),transparent 30%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(248,253,255,.94));
    border:1px solid rgba(184,228,255,.82);
    box-shadow:0 18px 48px rgba(39,137,199,.10);
}
.search-panel{
    padding:18px;
    margin-bottom:18px;
}
.search-panel-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    gap:14px;
    margin-bottom:12px;
}
.search-panel h2,
.available-content-card h2{
    font-size:24px;
    font-weight:950;
    letter-spacing:-.5px;
    margin-bottom:4px;
}
.search-panel p,
.available-content-card p{
    color:var(--muted);
    font-size:13px;
    line-height:1.45;
    font-weight:650;
}
.modify-form{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:10px;
}
.modify-form .wide{
    grid-column:span 2;
}
.available-content-card{
    padding:18px;
}
.available-head{
    display:flex;
    align-items:flex-end;
    justify-content:space-between;
    gap:16px;
    margin-bottom:16px;
}
.available-filter-bar{
    display:grid;
    grid-template-columns:1fr 1fr 1fr 1fr auto;
    gap:10px;
    padding:12px;
    border-radius:20px;
    background:rgba(234,247,255,.58);
    border:1px solid var(--border);
    margin-bottom:16px;
}
.available-car-grid{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:16px;
}
.available-badge{
    display:inline-flex;
    align-items:center;
    gap:7px;
    padding:8px 12px;
    border-radius:999px;
    color:#0b7d5b;
    background:#e8fff6;
    border:1px solid rgba(10,180,130,.18);
    font-size:12px;
    font-weight:950;
}
.status-line{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:10px;
    margin:10px 0 12px;
}
.status-line small{
    color:var(--muted);
    font-weight:750;
}
.price-summary{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:8px;
    margin:10px 0 12px;
}
.price-mini{
    border-radius:14px;
    padding:9px 10px;
    background:rgba(255,255,255,.86);
    border:1px solid var(--border);
}
.price-mini span{
    display:block;
    color:var(--muted);
    font-size:10px;
    text-transform:uppercase;
    font-weight:950;
    margin-bottom:2px;
}
.price-mini strong{
    color:var(--dark);
    font-size:13px;
    font-weight:950;
}
.trip-warning{
    margin-bottom:16px;
    padding:13px 14px;
    border-radius:18px;
    border:1px solid rgba(255,122,26,.22);
    background:#fff8f2;
    color:#b45309;
    font-size:13px;
    line-height:1.5;
    font-weight:800;
}
.empty-state{
    padding:44px 20px;
    border-radius:24px;
    text-align:center;
    border:1px dashed var(--border);
    background:rgba(255,255,255,.76);
}
.empty-state i{
    width:70px;
    height:70px;
    border-radius:24px;
    display:grid;
    place-items:center;
    margin:0 auto 14px;
    color:var(--sky600);
    background:var(--sky100);
    font-size:28px;
}
.empty-state h2{
    font-size:25px;
    margin-bottom:8px;
}
.empty-state p{
    color:var(--muted);
    font-weight:650;
    line-height:1.55;
    margin-bottom:18px;
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
    .available-hero-card,
    .trip-map-grid{grid-template-columns:1fr}
    .available-car-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    .modify-form,
    .available-filter-bar{grid-template-columns:1fr 1fr}
}
@media(max-width:760px){
    .available-hero,.available-main{padding:0 14px}
    .available-hero-card{padding:20px}
    .modify-form,
    .modify-form .wide,
    .available-filter-bar,
    .available-car-grid,
    .price-summary{grid-template-columns:1fr}
    .available-head,
    .search-panel-top{display:grid}
}


/* ===== Requested homepage-style search and available layout fixes ===== */
.search-panel.search-closed .modify-form{display:none!important}
.search-panel.search-open .modify-form{display:grid!important}
.search-panel{
    background:linear-gradient(145deg,rgba(255,255,255,.92),rgba(229,246,255,.78))!important;
    border-radius:28px!important;
    border:1px solid rgba(184,228,255,.95)!important;
    box-shadow:0 24px 65px rgba(39,137,199,.13)!important;
}
.search-panel-top{
    align-items:center!important;
}
.search-panel-top h2{
    font-size:28px!important;
    letter-spacing:-.8px!important;
}
.search-panel-actions{
    display:flex;
    align-items:center;
    gap:10px;
    flex-wrap:wrap;
}
.modify-form{
    margin-top:18px!important;
    grid-template-columns:repeat(4,minmax(0,1fr))!important;
    gap:14px!important;
}
.modify-form .wide{grid-column:1/-1}
.time-combo{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px;
}
.fixed-time-display{
    width:100%;
    height:44px;
    min-height:44px;
    border:2px solid #e2f2ff;
    background:rgba(255,255,255,.94);
    color:var(--dark);
    border-radius:14px;
    padding:10px 12px;
    display:flex;
    align-items:center;
    gap:9px;
    font-size:12px;
    font-weight:850;
}
.fixed-time-display i{color:var(--sky600)}
.trip-map-grid{
    grid-template-columns:.82fr 1.18fr!important;
}
.pickup-map-card{
    min-height:100%!important;
}
.map-box{
    height:430px!important;
}
.map-box iframe{
    width:100%!important;
    height:100%!important;
}
.available-hero-card{
    grid-template-columns:.92fr 1.35fr!important;
}
.summary-title{
    display:block!important;
}
.summary-title h2{margin-bottom:12px}
@media(max-width:980px){
    .available-hero-card,
    .trip-map-grid,
    .modify-form{grid-template-columns:1fr!important}
}


/* ===== Final requested available layout overrides ===== */
.available-hero-card.final-trip-layout{
    grid-template-columns:1.05fr 1fr!important;
    gap:22px!important;
    align-items:stretch!important;
}
.available-left-panel,
.available-right-panel{
    display:flex;
    flex-direction:column;
    gap:16px;
}
.available-left-panel .pickup-map-card,
.available-right-panel .trip-summary-card{
    height:100%;
}
.available-left-panel .map-box{
    height:430px!important;
}
.available-left-panel .map-box iframe{
    height:100%!important;
}
.available-right-panel .search-panel{
    margin:0!important;
    width:100%!important;
    padding:18px!important;
    border-radius:24px!important;
}
.available-right-panel .search-panel.search-closed .modify-form{
    display:none!important;
}
.available-right-panel .search-panel.search-open .modify-form{
    display:grid!important;
}
.available-right-panel .search-panel-top{
    display:flex!important;
    align-items:center!important;
    justify-content:space-between!important;
    gap:12px!important;
    margin-bottom:0!important;
}
.available-right-panel .search-panel.search-open .search-panel-top{margin-bottom:14px!important;}
.available-right-panel .search-panel-top h2{font-size:22px!important;margin:0!important;}
.available-right-panel .search-panel-top p{display:none!important;}
.available-right-panel .search-panel-actions{display:flex!important;gap:10px!important;}
.available-right-panel .modify-form{grid-template-columns:1fr 1fr!important;gap:10px!important;}
.available-right-panel .modify-form .wide{grid-column:1 / -1!important;}
.available-right-panel .btn.btn-orange{width:100%!important;}
.available-hero .hero-badges{
    grid-template-columns:repeat(3, minmax(0,1fr))!important;
    max-width:560px!important;
}
.available-hero .hero-badge{
    min-height:44px!important;
    white-space:nowrap!important;
    text-align:center!important;
}
.summary-item.period-combined strong{
    white-space:normal!important;
    line-height:1.35!important;
}
@media(max-width:980px){
    .available-hero-card.final-trip-layout{grid-template-columns:1fr!important;}
    .available-right-panel .modify-form{grid-template-columns:1fr!important;}
    .available-hero .hero-badges{grid-template-columns:1fr!important;}
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
@media(max-width:1180px){
    .nav-links{display:none!important;}
    .menu-btn{display:grid!important;}
}

/* Map should not be too large */
.available-left-panel .pickup-map-card{
    min-height:auto!important;
    height:auto!important;
}
.available-left-panel .map-box,
.available-hero .pickup-map-card .map-box,
.pickup-map-card .map-box{
    height:310px!important;
    flex:unset!important;
}
.available-left-panel .map-box iframe,
.available-hero .pickup-map-card .map-box iframe,
.pickup-map-card .map-box iframe,
.map-box iframe{
    height:310px!important;
    min-height:310px!important;
}
.map-actions .btn{
    min-height:42px!important;
}
/* Keep “Car rental price only” below price and on one line */
.price-mini small{
    display:block!important;
    margin-top:4px!important;
    color:var(--muted)!important;
    font-size:10px!important;
    font-weight:850!important;
    line-height:1.1!important;
    white-space:nowrap!important;
    text-transform:none!important;
}
.price-mini strong{
    display:block!important;
    white-space:nowrap!important;
}
.price-summary{
    grid-template-columns:1fr 1.25fr!important;
}
@media(max-width:760px){
    .available-left-panel .map-box,
    .available-hero .pickup-map-card .map-box,
    .pickup-map-card .map-box,
    .available-left-panel .map-box iframe,
    .available-hero .pickup-map-card .map-box iframe,
    .pickup-map-card .map-box iframe,
    .map-box iframe{
        height:260px!important;
        min-height:260px!important;
    }
    .price-summary{grid-template-columns:1fr!important;}
}



/* ===== USER REQUESTED: COMPACT AVAILABLE HERO / MOVE CHANGE TRIP UP ===== */
.available-hero{
    width:min(1180px,100%)!important;
    margin:10px auto 0!important;
    padding:0 18px!important;
}
.available-hero-card.final-trip-layout{
    grid-template-columns:1fr 1fr!important;
    gap:18px!important;
    padding:20px 22px!important;
    border-radius:26px!important;
    align-items:start!important;
}
.available-left-panel,
.available-right-panel{
    gap:12px!important;
}
.available-hero h1{
    font-size:clamp(34px,3.1vw,48px)!important;
    line-height:1.03!important;
    margin-bottom:10px!important;
}
.available-hero p{
    font-size:13.5px!important;
    line-height:1.48!important;
}
.available-hero .hero-badges{
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
    gap:8px!important;
    max-width:520px!important;
    margin-top:12px!important;
}
.available-hero .hero-badge{
    min-height:36px!important;
    padding:8px 10px!important;
    font-size:12px!important;
}
.available-left-panel .pickup-map-card,
.available-right-panel .trip-summary-card{
    height:auto!important;
    min-height:0!important;
    flex:none!important;
}
.pickup-map-card,
.trip-summary-card,
.available-right-panel .search-panel{
    padding:16px!important;
    border-radius:22px!important;
}
.pickup-map-card h2,
.summary-title h2{
    font-size:20px!important;
    margin-bottom:8px!important;
}
.location-address{
    margin-top:6px!important;
    font-size:12.5px!important;
}
.available-left-panel .map-box,
.available-hero .pickup-map-card .map-box,
.pickup-map-card .map-box,
.available-left-panel .map-box iframe,
.available-hero .pickup-map-card .map-box iframe,
.pickup-map-card .map-box iframe,
.map-box iframe{
    height:250px!important;
    min-height:250px!important;
}
.map-box{
    margin-top:9px!important;
    border-radius:17px!important;
}
.map-actions{
    margin-top:9px!important;
}
.map-actions .btn{
    min-height:38px!important;
    padding:9px 12px!important;
}
.summary-grid{
    gap:8px!important;
}
.summary-item{
    min-height:48px!important;
    padding:8px 10px!important;
    border-radius:14px!important;
}
.summary-item i{
    width:30px!important;
    height:30px!important;
    border-radius:10px!important;
    font-size:13px!important;
}
.summary-item span{
    font-size:9px!important;
    margin-bottom:1px!important;
}
.summary-item strong{
    font-size:12.2px!important;
    line-height:1.25!important;
}
.summary-item.period-combined strong{
    white-space:nowrap!important;
    overflow:hidden!important;
    text-overflow:ellipsis!important;
}
.available-right-panel .search-panel{
    margin-top:0!important;
    padding:14px 16px!important;
}
.available-right-panel .search-panel-top{
    align-items:center!important;
    min-height:0!important;
}
.available-right-panel .search-panel-top h2{
    font-size:19px!important;
    line-height:1.1!important;
}
.available-right-panel .search-panel.search-open .search-panel-top{
    margin-bottom:10px!important;
}
.available-right-panel .modify-form{
    gap:8px!important;
}
.available-right-panel .modify-form label{
    font-size:10px!important;
    margin-bottom:4px!important;
}
.available-right-panel .input,
.available-right-panel .fixed-time-display{
    min-height:38px!important;
    height:38px!important;
    border-radius:12px!important;
    padding:8px 10px!important;
    font-size:12px!important;
}
.available-right-panel .btn,
.available-right-panel .modify-btn{
    min-height:36px!important;
    padding:8px 12px!important;
    border-radius:12px!important;
    font-size:12px!important;
}
.available-main{
    width:min(1180px,100%)!important;
    margin:14px auto 48px!important;
    padding:0 18px!important;
}
@media(max-width:980px){
    .available-hero-card.final-trip-layout{
        grid-template-columns:1fr!important;
    }
    .summary-item.period-combined strong{
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

<section class="available-hero">
    <div class="available-hero-card final-trip-layout">
        <div class="available-left-panel">
            <div>
                <span class="pill"><i class="fa-solid fa-calendar-check"></i> Available Cars</span>
                <h1>Available Cars For Your Trip</h1>
                <p>This page only shows cars that match your selected state and rental period. Cars are grouped by state, so a Johor car only appears for Johor, a Melaka car only appears for Melaka, and a Kuala Lumpur car only appears for Kuala Lumpur.</p>
                <div class="hero-badges">
                    <span class="hero-badge"><i class="fa-solid fa-location-dot"></i> State-based Cars</span>
                    <span class="hero-badge"><i class="fa-solid fa-clock"></i> Time Conflict Check</span>
                    <span class="hero-badge"><i class="fa-solid fa-cart-plus"></i> Add Available Car</span>
                </div>
            </div>

            <div class="pickup-map-card">
                <span class="pill"><i class="fa-solid fa-map"></i> Pickup Location Map</span>
                <h2><?= e($pickupLocation["location_name"] ?? "Pickup Location") ?></h2>
                <p class="location-address"><?= e($pickupLocation["address"] ?? "Select a pickup location to view address.") ?></p>
                <div class="map-box">
                    <iframe src="<?= e($mapEmbed) ?>" loading="lazy" allowfullscreen></iframe>
                </div>
                <div class="map-actions">
                    <a class="btn btn-blue" href="<?= e($mapUrl) ?>" target="_blank"><i class="fa-solid fa-location-arrow"></i> Open in Google Maps</a>
                </div>
            </div>
        </div>

        <div class="available-right-panel">
            <div class="trip-summary-card">
                <div class="summary-title"><h2>Your Trip</h2></div>
                <div class="summary-grid">
                    <div class="summary-item"><i class="fa-solid fa-map-location-dot"></i><div><span>Pickup State</span><strong><?= e($selectedState["state_name"] ?? "Not selected") ?></strong></div></div>
                    <div class="summary-item"><i class="fa-solid fa-location-dot"></i><div><span>Pickup Location</span><strong><?= e($pickupLocation["location_name"] ?? "Not selected") ?></strong></div></div>
                    <div class="summary-item"><i class="fa-solid fa-location-arrow"></i><div><span>Drop-off Location</span><strong><?= e($dropoffLocation["location_name"] ?? "Not selected") ?></strong></div></div>
                    <div class="summary-item period-combined"><i class="fa-solid fa-calendar-days"></i><div><span>Rental Period & Days</span><strong><?= e($pickupLabel) ?> → <?= e($returnLabel) ?> ; <?= e($rentalDays ?: "-") ?> day(s)</strong></div></div>
                </div>
            </div>

            <section class="search-panel search-open" id="modifySearch">
                <div class="search-panel-top">
                    <div>
                        <span class="pill"><i class="fa-solid fa-sliders"></i> Trip Settings</span>
                        <h2>Change Trip Details</h2>
                        <p>Update pickup state, location or rental period to refresh available car results.</p>
                    </div>
                    <div class="search-panel-actions">
                        <a class="btn btn-white" href="catalogue.php"><i class="fa-solid fa-car-side"></i> Back to Catalogue</a>
                    </div>
                </div>

                <form class="modify-form" method="GET" action="available_cars.php" id="availableSearchForm">
                    <?php if($selectedCarId > 0): ?>
                        <input type="hidden" name="car_id" value="<?= e($selectedCarId) ?>">
                    <?php endif; ?>
                    <div>
                        <label>Pickup State</label>
                        <select class="input" name="state" id="pickupState" required>
                            <option value="">Select State</option>
                            <?php foreach($states as $state): ?>
                                <option value="<?= e($state["state_id"]) ?>" <?= (int)$state["state_id"] === $stateId ? "selected" : "" ?>><?= e($state["state_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Category</label>
                        <select class="input" name="category">
                            <option value="All">All Categories</option>
                            <?php foreach($categories as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= strtolower($selectedCategory) === strtolower($cat) ? "selected" : "" ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Pickup Location</label>
                        <select class="input" name="pickup_location" id="pickupLocation" required>
                            <option value="">Select Pickup Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?= e($location["location_id"]) ?>" data-state="<?= e($location["state_id"]) ?>" <?= (int)$location["location_id"] === $pickupLocationId ? "selected" : "" ?>><?= e($location["location_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Drop-off Location</label>
                        <select class="input" name="dropoff_location" id="dropoffLocation" required>
                            <option value="">Select Drop-off Location</option>
                            <?php foreach($locations as $location): ?>
                                <option value="<?= e($location["location_id"]) ?>" data-state="<?= e($location["state_id"]) ?>" <?= (int)$location["location_id"] === $dropoffLocationId ? "selected" : "" ?>><?= e($location["location_name"]) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label>Pickup Date</label>
                        <input class="input" type="date" name="pickup_date" id="pickupDate" value="<?= e($pickupDate) ?>" required>
                    </div>
                    <div>
                        <label>Pickup Time</label>
                        <div class="time-combo" id="pickupTimeGroup">
                            <select class="input time-part" id="pickupHour" required><option value="">Hour</option></select>
                            <select class="input time-part" id="pickupMinute" required><option value="">Minute</option></select>
                        </div>
                        <input type="hidden" name="pickup_time" id="pickupTime" value="<?= e($pickupTime) ?>" required>
                    </div>
                    <div>
                        <label>Return Date</label>
                        <input class="input" type="date" name="return_date" id="returnDate" value="<?= e($returnDate) ?>" required>
                    </div>
                    <div>
                        <label>Return Time</label>
                        <div class="fixed-time-display" id="returnTimeDisplay"><i class="fa-solid fa-lock"></i><span>Same as pickup time</span></div>
                        <input type="hidden" name="return_time" id="returnTime" value="<?= e($returnTime) ?>" required>
                    </div>
                    <div class="inline-error wide" id="tripError"></div>
                    <div class="wide"><button class="btn btn-orange" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search Available Cars</button></div>
                </form>
            </section>
        </div>
    </div>
</section>

<main class="available-main">
<section class="available-content-card">
        <div class="available-head">
            <div>
                <span class="pill"><i class="fa-solid fa-car"></i> Search Result</span>
                <h2><?= count($availableCars) ?> Available Cars</h2>
                <p>Only available vehicles for your selected state and rental time are shown here.</p>
            </div>
            <span class="count-tag"><i class="fa-solid fa-layer-group"></i> <?= count($availableCars) ?> Models</span>
        </div>

        <form class="available-filter-bar" method="GET" action="available_cars.php">
            <?php if($selectedCarId > 0): ?>
                <input type="hidden" name="car_id" value="<?= e($selectedCarId) ?>">
            <?php endif; ?>
            <input type="hidden" name="state" value="<?= e($stateId) ?>">
            <input type="hidden" name="pickup_location" value="<?= e($pickupLocationId) ?>">
            <input type="hidden" name="dropoff_location" value="<?= e($dropoffLocationId) ?>">
            <input type="hidden" name="pickup_date" value="<?= e($pickupDate) ?>">
            <input type="hidden" name="pickup_time" value="<?= e($pickupTime) ?>">
            <input type="hidden" name="return_date" value="<?= e($returnDate) ?>">
            <input type="hidden" name="return_time" value="<?= e($returnTime) ?>">

            <select class="input" name="brand"><option value="All">All Brands</option><?php foreach($brands as $brand): ?><option value="<?= e($brand) ?>" <?= strtolower($selectedBrand) === strtolower($brand) ? "selected" : "" ?>><?= e($brand) ?></option><?php endforeach; ?></select>
            <input class="input" type="number" name="min_price" value="<?= e($minPrice) ?>" placeholder="Min Price">
            <input class="input" type="number" name="max_price" value="<?= e($maxPrice) ?>" placeholder="Max Price">
            <select class="input" name="sort">
                <option value="default" <?= $sort === "default" ? "selected" : "" ?>>Name A-Z</option>
                <option value="price_low" <?= $sort === "price_low" ? "selected" : "" ?>>Price Low to High</option>
                <option value="price_high" <?= $sort === "price_high" ? "selected" : "" ?>>Price High to Low</option>
                <option value="newest" <?= $sort === "newest" ? "selected" : "" ?>>Newest Cars</option>
                <option value="seats" <?= $sort === "seats" ? "selected" : "" ?>>Most Seats</option>
                <option value="horsepower" <?= $sort === "horsepower" ? "selected" : "" ?>>Highest Horsepower</option>
            </select>
            <button class="btn btn-blue" type="submit"><i class="fa-solid fa-filter"></i> Filter</button>
        </form>

        <?php if(!$hasTrip): ?>
            <div class="trip-warning"><i class="fa-solid fa-circle-exclamation"></i> Please select pickup state, pickup location, drop-off location, pickup date/time and return date/time to view available cars.</div>
        <?php elseif($tripInvalid): ?>
            <div class="trip-warning"><i class="fa-solid fa-circle-exclamation"></i> Return date/time must be later than pickup date/time.</div>
        <?php endif; ?>

        <?php if(empty($availableCars)): ?>
            <div class="empty-state">
                <i class="fa-solid fa-car-burst"></i>
                <h2>No cars available for your selected trip</h2>
                <p>Try another pickup date, return date, state, or browse all car models in Catalogue.</p>
                <a class="btn btn-blue" href="#modifySearch"><i class="fa-solid fa-calendar-days"></i> Modify Search</a>
                <a class="btn btn-white" href="catalogue.php"><i class="fa-solid fa-car-side"></i> Back to Catalogue</a>
            </div>
        <?php else: ?>
            <div class="available-car-grid">
                <?php foreach($availableCars as $car): ?>
                    <?php
                        $image = trim($car["image"] ?? "");
                        $tag = trim($car["car_tag"] ?? "");
                        $carouselImages = getCarImages($conn, (int)$car["car_id"], $image);
                        $estimatedTotal = (float)$car["price_per_day"] * max(1, (int)$rentalDays);
                    ?>
                    <article class="car-card">
                        <div class="car-media">
                            <div class="car-tags">
                                <span class="tag"><?= e($car["category_name"]) ?></span>
                                <?php if($tag): ?><span class="tag orange"><?= e($tag) ?></span><?php endif; ?>
                            </div>

                            <?php if(!empty($carouselImages)): ?>
                                <div class="car-carousel">
                                    <div class="carousel-track">
                                        <?php foreach($carouselImages as $index => $carImage): ?>
                                            <img class="carousel-img <?= $index === 0 ? 'active' : '' ?>" src="<?= e(resolveCarImageSrc($carImage, $car["car_name"])) ?>" alt="<?= e($car["car_name"]) ?> image <?= $index + 1 ?>">
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if(count($carouselImages) > 1): ?>
                                        <button class="carousel-btn carousel-prev" type="button" aria-label="Previous image"><i class="fa-solid fa-chevron-left"></i></button>
                                        <button class="carousel-btn carousel-next" type="button" aria-label="Next image"><i class="fa-solid fa-chevron-right"></i></button>
                                        <div class="carousel-dots">
                                            <?php foreach($carouselImages as $index => $carImage): ?>
                                                <button class="carousel-dot <?= $index === 0 ? 'active' : '' ?>" type="button" data-index="<?= $index ?>"></button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-image"><i class="fa-solid fa-car-side"></i></div>
                            <?php endif; ?>

                            <span class="price-chip">RM <?= e(number_format((float)$car["price_per_day"], 2)) ?> / day</span>
                        </div>

                        <div class="car-body">
                            <div class="car-title">
                                <h3><?= e($car["car_name"]) ?></h3>
                                <span class="brand-tag"><?= e($car["brand"]) ?></span>
                            </div>

                            <div class="status-line">
                                <span class="available-badge"><i class="fa-solid fa-circle-check"></i> Available Now</span>
                                <small><?= (int)($car["available_units"] ?? 0) ?> unit(s)</small>
                            </div>

                            <div class="spec-grid">
                                <div class="spec"><i class="fa-solid fa-users"></i> <?= e($car["seats"]) ?> Seats</div>
                                <div class="spec"><i class="fa-solid fa-gears"></i> <?= e($car["transmission"]) ?></div>
                                <div class="spec"><i class="fa-solid fa-gas-pump"></i> <?= e($car["fuel_type"]) ?></div>
                                <div class="spec"><i class="fa-solid fa-screwdriver-wrench"></i> <?= e($car["engine"]) ?></div>
                                <div class="spec"><i class="fa-solid fa-gauge-high"></i> <?= e($car["horsepower"]) ?> hp</div>
                                <div class="spec"><i class="fa-solid fa-road"></i> <?= e($car["drivetrain"]) ?></div>
                                <div class="spec colour-spec"><i class="fa-solid fa-palette"></i> <?= e($car["car_color"] ?? "Not specified") ?> Colour</div>
                            </div>

                            <div class="price-summary">
                                <div class="price-mini"><span>Rental Days</span><strong><?= e($rentalDays) ?> day(s)</strong></div>
                                <div class="price-mini"><span>Estimated Rental Total</span><strong>RM <?= e(number_format($estimatedTotal, 2)) ?></strong><small>Car rental price only</small></div>
                            </div>

                            <div class="actions">
                                <a class="btn btn-white" href="<?= e(carDetailsUrl($car["car_id"])) ?>"><i class="fa-solid fa-circle-info"></i> View Details</a>
                                <a class="btn btn-blue" href="add_to_cart.php?car_id=<?= e($car["car_id"]) ?>&<?= e(http_build_query($_GET)) ?>"><i class="fa-solid fa-cart-plus"></i> Add to Cart</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
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
function filterLocations(stateSelect, pickupSelect, dropoffSelect){
    const stateId = stateSelect.value;
    [pickupSelect, dropoffSelect].forEach(select=>{
        [...select.options].forEach(option=>{
            if(!option.value) return;
            option.hidden = stateId && option.dataset.state !== stateId;
        });
        if(select.selectedOptions[0] && select.selectedOptions[0].hidden) select.value = "";
    });
}

function showError(errorBox, message){
    if(!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.add("show");
}

function clearError(errorBox){
    if(!errorBox) return;
    errorBox.textContent = "";
    errorBox.classList.remove("show");
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

function buildTimeOptions(hourSelect, minuteSelect){
    if(hourSelect && hourSelect.options.length <= 1){
        for(let hour = 0; hour < 24; hour++){
            const value = String(hour).padStart(2, "0");
            hourSelect.add(new Option(formatHourLabel(hour), value));
        }
    }
    if(minuteSelect && minuteSelect.options.length <= 1){
        for(let minute = 0; minute < 60; minute += 5){
            const value = String(minute).padStart(2, "0");
            minuteSelect.add(new Option(value, value));
        }
    }
}

function setupTimeCombo(){
    const hourSelect = document.getElementById("pickupHour");
    const minuteSelect = document.getElementById("pickupMinute");
    const pickupHidden = document.getElementById("pickupTime");
    const returnHidden = document.getElementById("returnTime");
    const display = document.getElementById("returnTimeDisplay");

    if(!hourSelect || !minuteSelect || !pickupHidden || !returnHidden) return;

    buildTimeOptions(hourSelect, minuteSelect);

    if(pickupHidden.value && pickupHidden.value.includes(":")){
        const [hour, minute] = pickupHidden.value.split(":");
        hourSelect.value = hour;
        minuteSelect.value = minute;
    }

    function sync(){
        if(hourSelect.value !== "" && minuteSelect.value !== ""){
            const selectedTime = `${hourSelect.value}:${minuteSelect.value}`;
            pickupHidden.value = selectedTime;
            returnHidden.value = selectedTime;
            if(display) display.querySelector("span").textContent = formatFixedTimeLabel(selectedTime);
        }else{
            pickupHidden.value = "";
            returnHidden.value = "";
            if(display) display.querySelector("span").textContent = "Same as pickup time";
        }
        pickupHidden.dispatchEvent(new Event("change"));
        returnHidden.dispatchEvent(new Event("change"));
    }

    hourSelect.addEventListener("change", sync);
    minuteSelect.addEventListener("change", sync);
    sync();
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

function setupTripValidation(){
    const pickupDate = document.getElementById("pickupDate");
    const pickupTime = document.getElementById("pickupTime");
    const returnDate = document.getElementById("returnDate");
    const returnTime = document.getElementById("returnTime");
    const form = document.getElementById("availableSearchForm");
    const errorBox = document.getElementById("tripError");
    if(!form || !pickupDate || !pickupTime || !returnDate || !returnTime) return;

    const today = localDateValue();
    pickupDate.min = today;

    const pickupHour = document.getElementById("pickupHour");
    const pickupMinute = document.getElementById("pickupMinute");

    function refreshMinimumPickupOptions(){
        const minimum = minimumPickupDateTime();
        const minimumDate = localDateValue(minimum);
        const minimumHour = String(minimum.getHours()).padStart(2, "0");
        const minimumMinute = String(minimum.getMinutes()).padStart(2, "0");

        pickupDate.min = minimumDate;

        if(!pickupHour || !pickupMinute) return;

        Array.from(pickupHour.options).forEach(option => {
            if(option.value === ""){
                option.disabled = false;
                return;
            }

            option.disabled = pickupDate.value === minimumDate && option.value < minimumHour;
        });

        Array.from(pickupMinute.options).forEach(option => {
            if(option.value === ""){
                option.disabled = false;
                return;
            }

            option.disabled = pickupDate.value === minimumDate &&
                pickupHour.value === minimumHour &&
                option.value < minimumMinute;
        });

        if(pickupHour.selectedOptions[0] && pickupHour.selectedOptions[0].disabled){
            pickupHour.value = "";
            pickupMinute.value = "";
            pickupTime.value = "";
            returnTime.value = "";
        }

        if(pickupMinute.selectedOptions[0] && pickupMinute.selectedOptions[0].disabled){
            pickupMinute.value = "";
            pickupTime.value = "";
            returnTime.value = "";
        }
    }

    function addOneDay(dateValue){
        const d = new Date(`${dateValue}T00:00:00`);
        d.setDate(d.getDate() + 1);
        return localDateValue(d);
    }

    function syncReturnTime(){
        returnTime.value = pickupTime.value || "";
        refreshMinimumPickupOptions();
        const display = document.getElementById("returnTimeDisplay");
        if(display) display.querySelector("span").textContent = formatFixedTimeLabel(returnTime.value);
    }

    function updateReturnMin(){
        if(pickupDate.value){
            const minReturn = addOneDay(pickupDate.value);
            returnDate.min = minReturn;
            if(returnDate.value && returnDate.value < minReturn) returnDate.value = "";
        }else{
            returnDate.min = addOneDay(today);
        }
    }

    function validate(){
        clearError(errorBox);
        [pickupDate,pickupTime,returnDate,returnTime].forEach(input=>input.classList.remove("error"));
        syncReturnTime();

        const todayValue = localDateValue();
        if(pickupDate.value && pickupDate.value < todayValue){
            pickupDate.value = "";
            pickupDate.classList.add("error");
            showError(errorBox, "Pickup date cannot be earlier than today.");
            return false;
        }

        if(returnDate.value && returnDate.value < todayValue){
            returnDate.value = "";
            returnDate.classList.add("error");
            showError(errorBox, "Return date cannot be earlier than today.");
            return false;
        }

        updateReturnMin();

        if(!pickupDate.value || !pickupTime.value || !returnDate.value || !returnTime.value) return true;

        const pickup = new Date(`${pickupDate.value}T${pickupTime.value}`);
        const returned = new Date(`${returnDate.value}T${returnTime.value}`);

        if(pickup < minimumPickupDateTime()){
            [pickupDate,pickupTime].forEach(input=>input.classList.add("error"));
            showError(errorBox, "Pickup time must be at least 1 hour from now.");
            return false;
        }

        if(returned <= pickup){
            [returnDate,returnTime].forEach(input=>input.classList.add("error"));
            showError(errorBox, "Return date must be at least the next day. Return time is fixed same as pickup time.");
            return false;
        }

        return true;
    }

    pickupDate.addEventListener("change", () => {
        refreshMinimumPickupOptions();
        validate();
    });

    if(pickupHour){
        pickupHour.addEventListener("change", () => {
            refreshMinimumPickupOptions();
            validate();
        });
    }

    if(pickupMinute){
        pickupMinute.addEventListener("change", () => {
            refreshMinimumPickupOptions();
            validate();
        });
    }

    pickupTime.addEventListener("change", () => {
        refreshMinimumPickupOptions();
        validate();
    });

    returnDate.addEventListener("change", validate);

    form.addEventListener("submit", e=>{
        refreshMinimumPickupOptions();
        if(!validate()) e.preventDefault();
    });

    refreshMinimumPickupOptions();
    updateReturnMin();
    syncReturnTime();
}

const pickupState = document.getElementById("pickupState");
const pickupLocation = document.getElementById("pickupLocation");
const dropoffLocation = document.getElementById("dropoffLocation");

if(pickupState && pickupLocation && dropoffLocation){
    pickupState.addEventListener("change", ()=>filterLocations(pickupState,pickupLocation,dropoffLocation));
    filterLocations(pickupState,pickupLocation,dropoffLocation);
}
setupTimeCombo();
setupTripValidation();

const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");
if(avatarBtn && profileDropdown){
    avatarBtn.addEventListener("click", e=>{
        e.stopPropagation();
        profileDropdown.classList.toggle("show");
    });
    document.addEventListener("click", ()=>profileDropdown.classList.remove("show"));
}

document.querySelectorAll(".car-carousel").forEach(carousel => {
    const images = [...carousel.querySelectorAll(".carousel-img")];
    const dots = [...carousel.querySelectorAll(".carousel-dot")];
    const prev = carousel.querySelector(".carousel-prev");
    const next = carousel.querySelector(".carousel-next");
    let current = 0;
    if (images.length <= 1) return;
    function showImage(index) {
        current = (index + images.length) % images.length;
        images.forEach((img, i) => img.classList.toggle("active", i === current));
        dots.forEach((dot, i) => dot.classList.toggle("active", i === current));
    }
    prev?.addEventListener("click", event => { event.preventDefault(); event.stopPropagation(); showImage(current - 1); });
    next?.addEventListener("click", event => { event.preventDefault(); event.stopPropagation(); showImage(current + 1); });
    dots.forEach(dot => {
        dot.addEventListener("click", event => {
            event.preventDefault();
            event.stopPropagation();
            showImage(parseInt(dot.dataset.index, 10));
        });
    });
});
</script>
</body>
</html>
