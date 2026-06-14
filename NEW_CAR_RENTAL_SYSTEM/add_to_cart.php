<?php
require_once "config.php";

function tableExists($conn, $table) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
    $stmt->bind_param("s", $table);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["total"] ?? 0) > 0;
}

function columnExists($conn, $table, $column) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
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

function fetchOne($conn, $sql, $types = "", $params = []) {
    if ($types !== "" && !empty($params)) {
        $stmt = $conn->prepare($sql);
        if (!$stmt) return null;
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result ? $result->fetch_assoc() : null;
        $stmt->close();
        return $row ?: null;
    }

    $result = $conn->query($sql);
    if (!$result) return null;
    return $result->fetch_assoc() ?: null;
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

function findStateIdFromValue($conn, $value) {
    if (is_numeric($value)) return (int)$value;
    if (!tableExists($conn, "rental_states")) return 0;

    $stateIdCol = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
    $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
    $stateSlugCol = firstColumn($conn, "rental_states", ["state_slug", "slug"], null);
    $value = strtolower(trim((string)$value));

    $slugSelect = $stateSlugCol ? "$stateSlugCol AS state_slug" : "LOWER(REPLACE($stateNameCol, ' ', '-')) AS state_slug";
    $states = fetchRows($conn, "SELECT $stateIdCol AS state_id, $stateNameCol AS state_name, $slugSelect FROM rental_states");

    foreach ($states as $state) {
        $name = strtolower((string)($state["state_name"] ?? ""));
        $slug = strtolower((string)($state["state_slug"] ?? ""));
        if ($value !== "" && ($value === $name || $value === $slug)) {
            return (int)$state["state_id"];
        }
    }

    return 0;
}

function hasBookingOverlap($conn, $carId, $unitId, $stateId, $startDatetime, $endDatetime) {
    if (!tableExists($conn, "booking_items") || !tableExists($conn, "bookings")) return false;

    $bookingItemCarCol = firstColumn($conn, "booking_items", ["car_id"], "car_id");
    $bookingItemUnitCol = firstColumn($conn, "booking_items", ["unit_id", "car_unit_id"], null);
    $bookingItemBookingCol = firstColumn($conn, "booking_items", ["booking_id"], "booking_id");
    $bookingItemStartCol = firstColumn($conn, "booking_items", ["start_datetime", "pickup_datetime"], "start_datetime");
    $bookingItemEndCol = firstColumn($conn, "booking_items", ["end_datetime", "return_datetime"], "end_datetime");
    $bookingPk = firstColumn($conn, "bookings", ["booking_id", "id"], "booking_id");
    $bookingStatusCol = firstColumn($conn, "bookings", ["booking_status", "status"], null);
    $paymentStatusCol = firstColumn($conn, "bookings", ["payment_status"], null);

    $where = ["bi.$bookingItemStartCol < ?", "bi.$bookingItemEndCol > ?"];
    $types = "ss";
    $params = [$endDatetime, $startDatetime];

    if ($unitId > 0 && $bookingItemUnitCol) {
        $where[] = "bi.$bookingItemUnitCol = ?";
        $types .= "i";
        $params[] = $unitId;
    } else {
        $where[] = "bi.$bookingItemCarCol = ?";
        $types .= "i";
        $params[] = $carId;
    }

    $blockedStatus = $bookingStatusCol ? "AND LOWER(COALESCE(b.$bookingStatusCol, 'pending')) NOT IN ('cancelled','rejected')" : "";
    $paymentFilter = $paymentStatusCol ? "AND LOWER(COALESCE(b.$paymentStatusCol, 'pending')) NOT IN ('failed','refunded')" : "";

    $row = fetchOne($conn, "
        SELECT COUNT(*) AS total
        FROM booking_items bi
        INNER JOIN bookings b ON b.$bookingPk = bi.$bookingItemBookingCol
        WHERE " . implode(" AND ", $where) . "
        $blockedStatus
        $paymentFilter
    ", $types, $params);

    return (int)($row["total"] ?? 0) > 0;
}

function findAvailableUnit($conn, $carId, $stateId, $startDatetime, $endDatetime) {
    if (!tableExists($conn, "car_units")) return 0;

    $unitIdCol = firstColumn($conn, "car_units", ["unit_id", "id"], "unit_id");
    $unitCarCol = firstColumn($conn, "car_units", ["car_id"], "car_id");
    $unitStateCol = firstColumn($conn, "car_units", ["state_id"], null);
    $unitStatusCol = firstColumn($conn, "car_units", ["current_status", "status"], null);

    $where = ["$unitCarCol = ?"];
    $types = "i";
    $params = [$carId];

    if ($unitStateCol) {
        $where[] = "$unitStateCol = ?";
        $types .= "i";
        $params[] = $stateId;
    }

    if ($unitStatusCol) {
        $where[] = "LOWER(COALESCE($unitStatusCol, 'available')) NOT IN ('maintenance','inactive')";
    }

    $units = fetchRows($conn, "SELECT $unitIdCol AS unit_id FROM car_units WHERE " . implode(" AND ", $where), $types, $params);
    foreach ($units as $unit) {
        $unitId = (int)$unit["unit_id"];
        if (!hasBookingOverlap($conn, $carId, $unitId, $stateId, $startDatetime, $endDatetime)) {
            return $unitId;
        }
    }

    return 0;
}

function redirectMissingTrip() {
    header("Location: catalogue.php?cart_error=missing_trip");
    exit;
}

$carId = (int)($_GET["car_id"] ?? $_POST["car_id"] ?? 0);
$stateId = findStateIdFromValue($conn, $_GET["state"] ?? $_POST["state"] ?? $_GET["pickup_state"] ?? $_POST["pickup_state"] ?? "");
$pickupLocationId = (int)($_GET["pickup_location"] ?? $_POST["pickup_location"] ?? 0);
$dropoffLocationId = (int)($_GET["dropoff_location"] ?? $_POST["dropoff_location"] ?? 0);
$pickupDate = trim($_GET["pickup_date"] ?? $_POST["pickup_date"] ?? "");
$pickupTime = trim($_GET["pickup_time"] ?? $_POST["pickup_time"] ?? "");
$returnDate = trim($_GET["return_date"] ?? $_POST["return_date"] ?? "");
$returnTime = trim($_GET["return_time"] ?? $_POST["return_time"] ?? "");

if ($pickupTime === "" && isset($_GET["pickup_hour"], $_GET["pickup_minute"])) {
    $pickupTime = str_pad((string)$_GET["pickup_hour"], 2, "0", STR_PAD_LEFT) . ":" . str_pad((string)$_GET["pickup_minute"], 2, "0", STR_PAD_LEFT);
} elseif ($pickupTime === "" && isset($_POST["pickup_hour"], $_POST["pickup_minute"])) {
    $pickupTime = str_pad((string)$_POST["pickup_hour"], 2, "0", STR_PAD_LEFT) . ":" . str_pad((string)$_POST["pickup_minute"], 2, "0", STR_PAD_LEFT);
}

if ($returnTime === "" && $pickupTime !== "") {
    $returnTime = $pickupTime;
}

if ($carId <= 0 || $stateId <= 0 || $pickupLocationId <= 0 || $dropoffLocationId <= 0 || !$pickupDate || !$pickupTime || !$returnDate || !$returnTime) {
    redirectMissingTrip();
}

$startDatetime = "$pickupDate $pickupTime:00";
$endDatetime = "$returnDate $returnTime:00";
$startTs = strtotime($startDatetime);
$endTs = strtotime($endDatetime);

if (!$startTs || !$endTs || $endTs <= $startTs) {
    redirectMissingTrip();
}

$currentUrl = "add_to_cart.php?" . http_build_query($_GET ?: $_POST);
if (empty($_SESSION["user_id"])) {
    header("Location: login.php?redirect=" . urlencode($currentUrl));
    exit;
}

$userId = (int)$_SESSION["user_id"];

if (!tableExists($conn, "cart_items")) {
    $_SESSION["cart"] = $_SESSION["cart"] ?? [];
    $key = md5($carId . "|" . $stateId . "|" . $pickupLocationId . "|" . $dropoffLocationId . "|" . $pickupDate . "|" . $pickupTime . "|" . $returnDate . "|" . $returnTime);
    $_SESSION["cart"][$key] = [
        "car_id" => $carId,
        "state" => $stateId,
        "pickup_location" => $pickupLocationId,
        "dropoff_location" => $dropoffLocationId,
        "pickup_date" => $pickupDate,
        "pickup_time" => $pickupTime,
        "return_date" => $returnDate,
        "return_time" => $returnTime,
        "added_at" => date("Y-m-d H:i:s")
    ];
    header("Location: cart.php?added=1");
    exit;
}

if (!tableExists($conn, "cars")) {
    header("Location: catalogue.php?cart_error=no_cars_table");
    exit;
}

$carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
$priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);
$statusCol = firstColumn($conn, "cars", ["status", "availability"], null);
$where = "WHERE $carIdCol = ?";
if ($statusCol) {
    $where .= " AND (LOWER($statusCol) IN ('active','available') OR $statusCol = 1)";
}

$car = fetchOne($conn, "SELECT $carIdCol AS car_id, " . ($priceCol ? "$priceCol" : "0") . " AS price_per_day FROM cars $where LIMIT 1", "i", [$carId]);
if (!$car) {
    header("Location: catalogue.php?cart_error=car_not_found");
    exit;
}

$rentalDays = max(1, (int)ceil(($endTs - $startTs) / 86400));
$pricePerDay = (float)($car["price_per_day"] ?? 0);
$subtotal = $pricePerDay * $rentalDays;
$unitId = findAvailableUnit($conn, $carId, $stateId, $startDatetime, $endDatetime);
$status = $unitId > 0 ? "active" : "unavailable";
$unitIdForDb = $unitId > 0 ? $unitId : null;

$existing = fetchOne($conn, "
    SELECT cart_item_id
    FROM cart_items
    WHERE user_id = ?
    AND car_id = ?
    AND pickup_state_id = ?
    AND pickup_location = ?
    AND dropoff_location = ?
    AND start_datetime = ?
    AND end_datetime = ?
    AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out')
    LIMIT 1
", "iiiiiss", [$userId, $carId, $stateId, $pickupLocationId, $dropoffLocationId, $startDatetime, $endDatetime]);

if ($existing) {
    $cartItemId = (int)$existing["cart_item_id"];
    $stmt = $conn->prepare("
        UPDATE cart_items
        SET unit_id = ?, rental_days = ?, price_per_day = ?, subtotal = ?, status = ?, updated_at = NOW()
        WHERE cart_item_id = ? AND user_id = ?
    ");
    if ($stmt) {
        $stmt->bind_param("iiddsii", $unitIdForDb, $rentalDays, $pricePerDay, $subtotal, $status, $cartItemId, $userId);
        $stmt->execute();
        $stmt->close();
    }
} else {
    $stmt = $conn->prepare("
        INSERT INTO cart_items
        (user_id, car_id, unit_id, pickup_state_id, pickup_location, dropoff_location, start_datetime, end_datetime, rental_days, price_per_day, subtotal, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        header("Location: catalogue.php?cart_error=insert_failed");
        exit;
    }
    $stmt->bind_param("iiiiiissidds", $userId, $carId, $unitIdForDb, $stateId, $pickupLocationId, $dropoffLocationId, $startDatetime, $endDatetime, $rentalDays, $pricePerDay, $subtotal, $status);
    $stmt->execute();
    $stmt->close();
}

header("Location: cart.php?added=1");
exit;
