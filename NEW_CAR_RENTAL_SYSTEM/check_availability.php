<?php
require_once "config.php";
header("Content-Type: application/json; charset=UTF-8");

function respond($data) {
    echo json_encode($data);
    exit;
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


function getLocationStateId($conn, $locationId) {
    if (!tableExists($conn, "rental_locations")) return 0;
    $idCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
    $stateCol = firstColumn($conn, "rental_locations", ["state_id"], "state_id");
    $stmt = $conn->prepare("SELECT $stateCol AS state_id FROM rental_locations WHERE $idCol = ? LIMIT 1");
    $stmt->bind_param("i", $locationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["state_id"] ?? 0);
}

function getLocationName($conn, $locationId) {
    if (!tableExists($conn, "rental_locations")) return "";
    $idCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
    $nameCol = firstColumn($conn, "rental_locations", ["location_name", "name"], "location_name");

    $stmt = $conn->prepare("SELECT $nameCol AS location_name FROM rental_locations WHERE $idCol = ? LIMIT 1");
    $stmt->bind_param("i", $locationId);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return $row["location_name"] ?? "";
}

$carId = (int)($_POST["car_id"] ?? $_GET["car_id"] ?? 0);
$stateId = (int)($_POST["state"] ?? $_GET["state"] ?? 0);
$pickupLocationId = (int)($_POST["pickup_location"] ?? $_GET["pickup_location"] ?? 0);
$dropoffLocationId = (int)($_POST["dropoff_location"] ?? $_GET["dropoff_location"] ?? 0);
$pickupDate = trim($_POST["pickup_date"] ?? $_GET["pickup_date"] ?? "");
$pickupTime = trim($_POST["pickup_time"] ?? $_GET["pickup_time"] ?? "");
if ($pickupTime === "" && isset($_POST["pickup_hour"], $_POST["pickup_minute"])) {
    $pickupTime = str_pad((string)$_POST["pickup_hour"], 2, "0", STR_PAD_LEFT) . ":" . str_pad((string)$_POST["pickup_minute"], 2, "0", STR_PAD_LEFT);
} elseif ($pickupTime === "" && isset($_GET["pickup_hour"], $_GET["pickup_minute"])) {
    $pickupTime = str_pad((string)$_GET["pickup_hour"], 2, "0", STR_PAD_LEFT) . ":" . str_pad((string)$_GET["pickup_minute"], 2, "0", STR_PAD_LEFT);
}
$returnDate = trim($_POST["return_date"] ?? $_GET["return_date"] ?? "");
$returnTime = trim($_POST["return_time"] ?? $_GET["return_time"] ?? "");
if ($pickupTime !== "") $returnTime = $pickupTime;

if ($carId <= 0 || $stateId <= 0 || $pickupLocationId <= 0 || $dropoffLocationId <= 0 || !$pickupDate || !$pickupTime || !$returnDate || !$returnTime) {
    respond(["ok" => false, "message" => "Please complete all pickup, drop-off, date and time fields."]);
}

$pickupLocationState = getLocationStateId($conn, $pickupLocationId);
$dropoffLocationState = getLocationStateId($conn, $dropoffLocationId);
if (($pickupLocationState > 0 && $pickupLocationState !== $stateId) || ($dropoffLocationState > 0 && $dropoffLocationState !== $stateId)) {
    respond(["ok" => false, "message" => "Pickup and drop-off location must be in the selected pickup state."]);
}

$pickupDateTime = "$pickupDate $pickupTime:00";
$returnDateTime = "$returnDate $returnTime:00";
$pickupTs = strtotime($pickupDateTime);
$returnTs = strtotime($returnDateTime);

if (!$pickupTs || !$returnTs || $returnTs <= $pickupTs) {
    respond(["ok" => false, "message" => "Return date/time must be later than pickup date/time."]);
}

if (!tableExists($conn, "cars")) {
    respond(["ok" => false, "message" => "Cars table was not found."]);
}

$carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
$carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");
$priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
$categoryCol = firstColumn($conn, "cars", ["type", "category", "category_name"], null);
$categoryIdCol = firstColumn($conn, "cars", ["category_id"], null);
$statusCol = firstColumn($conn, "cars", ["status", "availability"], null);

$select = [
    "c.$carIdCol AS car_id",
    "c.$carNameCol AS car_name",
    ($priceCol ? "c.$priceCol" : "0") . " AS price_per_day"
];

$join = "";

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

$where = "c.$carIdCol = ?";
if ($statusCol) {
    $where .= " AND (LOWER(c.$statusCol) IN ('active','available') OR c.$statusCol = 1)";
}

$stmt = $conn->prepare("SELECT " . implode(", ", $select) . " FROM cars c $join WHERE $where LIMIT 1");
$stmt->bind_param("i", $carId);
$stmt->execute();
$car = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$car) {
    respond(["ok" => false, "message" => "Selected car was not found or inactive."]);
}

if (!tableExists($conn, "car_units")) {
    respond(["ok" => false, "message" => "car_units table is required to check state-based availability."]);
}

$unitIdCol = firstColumn($conn, "car_units", ["unit_id", "id"], "unit_id");
$unitCarCol = firstColumn($conn, "car_units", ["car_id"], "car_id");
$unitStateCol = firstColumn($conn, "car_units", ["state_id"], null);
$unitStatusCol = firstColumn($conn, "car_units", ["current_status", "status"], null);

$unitWhere = ["cu.$unitCarCol = ?"];
$types = "i";
$params = [$carId];

if ($unitStateCol) {
    $unitWhere[] = "cu.$unitStateCol = ?";
    $types .= "i";
    $params[] = $stateId;
}

if ($unitStatusCol) {
    $unitWhere[] = "LOWER(COALESCE(cu.$unitStatusCol, 'available')) NOT IN ('maintenance','inactive')";
}

$notExists = "";

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
        ? "(bi.$bookingItemUnitCol = cu.$unitIdCol OR (bi.$bookingItemUnitCol IS NULL AND bi.$bookingItemCarCol = cu.$unitCarCol))"
        : "bi.$bookingItemCarCol = cu.$unitCarCol";

    $notExists = "
        AND NOT EXISTS (
            SELECT 1
            FROM booking_items bi
            INNER JOIN bookings b ON b.$bookingPk = bi.$bookingItemBookingCol
            WHERE $unitOverlapMatch
            $blockedStatus
            AND bi.$bookingItemStartCol < ?
            AND bi.$bookingItemEndCol > ?
        )
    ";

    $types .= "ss";
    $params[] = $returnDateTime;
    $params[] = $pickupDateTime;
}

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT cu.$unitIdCol) AS total
    FROM car_units cu
    WHERE " . implode(" AND ", $unitWhere) . "
    $notExists
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$availableUnits = (int)($row["total"] ?? 0);
$rentalDays = max(1, (int)ceil(($returnTs - $pickupTs) / 86400));
$pricePerDay = (float)($car["price_per_day"] ?? 0);

respond([
    "ok" => true,
    "available" => $availableUnits > 0,
    "available_units" => $availableUnits,
    "car_id" => (int)$car["car_id"],
    "car_name" => $car["car_name"],
    "category_name" => $car["category_name"] ?? "",
    "state_id" => $stateId,
    "pickup_location_id" => $pickupLocationId,
    "dropoff_location_id" => $dropoffLocationId,
    "pickup_location_name" => getLocationName($conn, $pickupLocationId),
    "dropoff_location_name" => getLocationName($conn, $dropoffLocationId),
    "pickup_label" => date("d M Y, h:i A", $pickupTs),
    "return_label" => date("d M Y, h:i A", $returnTs),
    "pickup_datetime" => $pickupDateTime,
    "return_datetime" => $returnDateTime,
    "rental_days" => $rentalDays,
    "price_per_day" => $pricePerDay,
    "estimated_total" => $rentalDays * $pricePerDay
]);
