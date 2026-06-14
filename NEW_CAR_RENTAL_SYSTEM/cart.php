<?php
require_once "config.php";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

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

function money($value) {
    return "RM " . number_format((float)$value, 2);
}

function dateTimeLabel($value) {
    $time = strtotime((string)$value);
    if (!$time) return "-";
    return date("d M Y, h:i A", $time);
}

function dateInputValue($datetime) {
    $time = strtotime((string)$datetime);
    return $time ? date("Y-m-d", $time) : "";
}

function timeInputValue($datetime) {
    $time = strtotime((string)$datetime);
    return $time ? date("H:i", $time) : "";
}

function resolveCarImageSrc($imagePath, $carName = "Car Image") {
    $imagePath = trim((string)$imagePath);

    if ($imagePath !== "" && preg_match('/^https?:\/\//i', $imagePath)) {
        return $imagePath;
    }

    if ($imagePath !== "") {
        $localPath = __DIR__ . "/" . ltrim($imagePath, "/");
        if (is_file($localPath)) return $imagePath;
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

function getCarImage($conn, $carId, $fallbackImage = "", $carName = "Car Image") {
    if (tableExists($conn, "car_images")) {
        $carImageCarCol = firstColumn($conn, "car_images", ["car_id"], "car_id");
        $carImageUrlCol = firstColumn($conn, "car_images", ["image_url", "image", "image_path"], "image_url");
        $carImageSortCol = firstColumn($conn, "car_images", ["sort_order"], null);
        $imagePk = firstColumn($conn, "car_images", ["image_id", "id"], null);
        $orderBy = $carImageSortCol ? "ORDER BY $carImageSortCol ASC" : ($imagePk ? "ORDER BY $imagePk ASC" : "");
        $row = fetchOne($conn, "SELECT $carImageUrlCol AS image_url FROM car_images WHERE $carImageCarCol = ? $orderBy LIMIT 1", "i", [$carId]);
        if (!empty($row["image_url"])) return resolveCarImageSrc($row["image_url"], $carName);
    }

    return resolveCarImageSrc($fallbackImage, $carName);
}


function getPromoByCode($conn, $code, $userId = 0) {
    $code = strtoupper(trim((string)$code));
    if ($code === "" || !tableExists($conn, "promo_codes")) return null;
    if ($userId <= 0 && !empty($_SESSION["user_id"])) $userId = (int)$_SESSION["user_id"];

    $promoIdCol = firstColumn($conn, "promo_codes", ["promo_id", "id"], "id");
    $promoCodeCol = firstColumn($conn, "promo_codes", ["promo_code", "code"], "promo_code");
    $promoNameCol = firstColumn($conn, "promo_codes", ["promo_name", "name", "description"], null);
    $discountCol = firstColumn($conn, "promo_codes", ["discount_percent", "discount_percentage"], "discount_percent");
    $statusCol = firstColumn($conn, "promo_codes", ["status"], null);

    $where = "UPPER($promoCodeCol) = ?";
    if ($statusCol) {
        $where .= " AND (LOWER($statusCol) = 'active' OR $statusCol = 1)";
    }
    if (columnExists($conn, "promo_codes", "deleted_at")) {
        $where .= " AND deleted_at IS NULL";
    }

    $nameSelect = $promoNameCol ? "$promoNameCol AS promo_name" : "$promoCodeCol AS promo_name";
    $row = fetchOne($conn, "
        SELECT $promoIdCol AS promo_id, $promoCodeCol AS promo_code, $discountCol AS discount_percent, $nameSelect
        FROM promo_codes
        WHERE $where
        LIMIT 1
    ", "s", [$code]);

    if (!$row) return null;

    if (tableExists($conn, "promo_code_assignments")) {
        $assignment = fetchOne($conn, "
            SELECT
                COUNT(*) AS assigned_total,
                SUM(CASE WHEN user_id = ? AND LOWER(COALESCE(status, 'active')) = 'active' THEN 1 ELSE 0 END) AS assigned_to_user
            FROM promo_code_assignments
            WHERE promo_id = ?
        ", "ii", [$userId, (int)$row["promo_id"]]);

        if ((int)($assignment["assigned_total"] ?? 0) > 0 && (int)($assignment["assigned_to_user"] ?? 0) <= 0) {
            return null;
        }
    }

    $row["discount_percent"] = max(0, min(100, (float)($row["discount_percent"] ?? 0)));
    return $row;
}

function hasUsedPromo($conn, $userId, $promoId) {
    if (!tableExists($conn, "promo_code_usage")) return false;

    $promoCol = firstColumn($conn, "promo_code_usage", ["promo_id"], "promo_id");
    $userCol = firstColumn($conn, "promo_code_usage", ["user_id"], "user_id");
    $usedAtCol = firstColumn($conn, "promo_code_usage", ["used_at"], null);
    $bookingCol = firstColumn($conn, "promo_code_usage", ["booking_id"], null);

    $where = "$promoCol = ? AND $userCol = ?";
    if ($usedAtCol || $bookingCol) {
        $checks = [];
        if ($usedAtCol) $checks[] = "$usedAtCol IS NOT NULL";
        if ($bookingCol) $checks[] = "$bookingCol IS NOT NULL";
        $where .= " AND (" . implode(" OR ", $checks) . ")";
    }

    $row = fetchOne($conn, "SELECT COUNT(*) AS total FROM promo_code_usage WHERE $where", "ii", [$promoId, $userId]);
    return (int)($row["total"] ?? 0) > 0;
}

if (empty($_SESSION["user_id"])) {
    header("Location: login.php?redirect=cart.php");
    exit;
}

$userId = (int)$_SESSION["user_id"];
$user = null;


$navCartCount = getNavCartCount($conn);

$insurancePackages = [
    "basic" => [
        "name" => "Basic Coverage",
        "price" => 0,
        "unit" => "day",
        "desc" => "Third-party liability, basic damage coverage, RM 3,000 excess."
    ],
    "standard" => [
        "name" => "Standard Coverage",
        "price" => 20,
        "unit" => "day",
        "desc" => "Theft protection, RM 1,500 excess, 24/7 roadside assistance."
    ],
    "premium" => [
        "name" => "Premium Coverage",
        "price" => 45,
        "unit" => "day",
        "desc" => "Zero excess, personal accident cover, windscreen and tyre protection, priority support."
    ]
];

$addonServices = [
    "gps" => ["name" => "GPS Navigation", "price" => 15, "unit" => "day", "icon" => "fa-location-crosshairs"],
    "child_seat" => ["name" => "Child Seat", "price" => 10, "unit" => "day", "icon" => "fa-child-reaching"],
    "dashcam" => ["name" => "Dashcam", "price" => 12, "unit" => "day", "icon" => "fa-video"],
    "touchngo" => ["name" => "Touch n Go Card", "price" => 5, "unit" => "booking", "icon" => "fa-credit-card"],
    "phone_holder" => ["name" => "Car Phone Holder", "price" => 6, "unit" => "day", "icon" => "fa-mobile-screen-button"],
    "umbrella" => ["name" => "Rain Umbrella", "price" => 5, "unit" => "booking", "icon" => "fa-umbrella"],
    "cooler_box" => ["name" => "Portable Cooler Box", "price" => 15, "unit" => "day", "icon" => "fa-box"],
    "roof_rack" => ["name" => "Roof Rack", "price" => 25, "unit" => "day", "icon" => "fa-road"],
    "first_aid" => ["name" => "First Aid Kit", "price" => 8, "unit" => "booking", "icon" => "fa-kit-medical"],
    "luggage_strap" => ["name" => "Luggage Strap Set", "price" => 6, "unit" => "booking", "icon" => "fa-suitcase-rolling"]
];

$driverAgeGroups = [
    "normal" => [
        "name" => "25–69 years",
        "price" => 0,
        "desc" => "No surcharge. Standard driver age group for KH Car Rental."
    ],
    "under25" => [
        "name" => "Under 25 years",
        "price" => 10,
        "desc" => "Young driver surcharge applies for additional rental risk coverage."
    ],
    "over69" => [
        "name" => "Over 69 years",
        "price" => 15,
        "desc" => "Senior driver surcharge applies for additional rental risk coverage."
    ]
];

$fuelPolicies = [
    "none" => [
        "name" => "No Fuel Package",
        "desc" => "Fuel is not included in the rental price. Customer is responsible for fuel usage during the rental period."
    ],
    "prepaid_fuel" => [
        "name" => "Prepaid Fuel Package",
        "desc" => "Add a prepaid fuel package for convenience. The charge depends on the selected vehicle category."
    ]
];

function fallbackFuelChargeByCategory($categoryName) {
    $category = strtolower(trim((string)$categoryName));

    if (in_array($category, ["sedan", "hatchback"], true)) return 60.00;
    if (in_array($category, ["suv", "mpv"], true)) return 80.00;
    if (in_array($category, ["pickup", "luxury"], true)) return 100.00;
    if (in_array($category, ["sport", "coupe"], true)) return 120.00;
    if ($category === "ev") return 0.00;

    return 80.00;
}

function fuelChargeByCategory($conn, $categoryName) {
    $categoryName = trim((string)$categoryName);

    if ($categoryName !== "" && tableExists($conn, "fuel_packages")) {
        $stmt = $conn->prepare("
            SELECT fuel_charge, status
            FROM fuel_packages
            WHERE LOWER(category_name) = LOWER(?)
            LIMIT 1
        ");

        if ($stmt) {
            $stmt->bind_param("s", $categoryName);
            $stmt->execute();
            $row = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if ($row) {
                if (strtolower((string)($row["status"] ?? "active")) !== "active") return 0.00;
                return (float)($row["fuel_charge"] ?? 0);
            }
        }
    }

    return fallbackFuelChargeByCategory($categoryName);
}

function cleanCartAddons($sessionAddons, $insurancePackages, $addonServices, $fuelPolicies, $driverAgeGroups = []) {
    $sessionAddons = is_array($sessionAddons) ? $sessionAddons : [];
    $insurance = (string)($sessionAddons["insurance"] ?? "basic");
    if (!isset($insurancePackages[$insurance])) $insurance = "basic";

    $services = $sessionAddons["services"] ?? [];
    if (!is_array($services)) $services = [];
    $services = array_values(array_unique(array_filter(array_map("strval", $services), function($key) use ($addonServices) {
        return isset($addonServices[$key]);
    })));

    $fuel = (string)($sessionAddons["fuel"] ?? "none");
    if (!isset($fuelPolicies[$fuel])) $fuel = "none";

    $driverAge = (string)($sessionAddons["driver_age"] ?? "normal");
    if (!isset($driverAgeGroups[$driverAge])) $driverAge = "normal";

    return ["insurance" => $insurance, "services" => $services, "fuel" => $fuel, "driver_age" => $driverAge];
}

$addonMessages = [];
$addonMessageTypes = [];
$tripMessages = [];
$tripMessageTypes = [];
$tripSearchQueries = [];
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["addon_action"])) {
    $targetCartItemId = (int)($_POST["cart_item_id"] ?? 0);
    $selectedInsurance = (string)($_POST["insurance_package"] ?? "basic");
    $selectedServices = $_POST["addon_services"] ?? [];
    $selectedFuel = (string)($_POST["fuel_policy"] ?? "none");
    $selectedDriverAge = (string)($_POST["driver_age"] ?? "normal");

    if ($targetCartItemId > 0) {
        $_SESSION["cart_item_addons"] = $_SESSION["cart_item_addons"] ?? [];
        $_SESSION["cart_item_addons"][(string)$targetCartItemId] = cleanCartAddons([
            "insurance" => $selectedInsurance,
            "services" => is_array($selectedServices) ? $selectedServices : [],
            "fuel" => $selectedFuel,
            "driver_age" => $selectedDriverAge
        ], $insurancePackages, $addonServices, $fuelPolicies, $driverAgeGroups);

        $addonMessages[(string)$targetCartItemId] = "Extra protection and services updated.";
        $addonMessageTypes[(string)$targetCartItemId] = "ok";
    }
}

$_SESSION["cart_item_addons"] = $_SESSION["cart_item_addons"] ?? [];

if (tableExists($conn, "users")) {
    $userIdCol = firstColumn($conn, "users", ["user_id", "id"], "user_id");
    $user = fetchOne($conn, "SELECT * FROM users WHERE $userIdCol = ? LIMIT 1", "i", [$userId]);
}

$voucherMessage = "";
$voucherMessageType = "";
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && isset($_POST["voucher_action"])) {
    $voucherAction = (string)$_POST["voucher_action"];

    if ($voucherAction === "remove") {
        unset($_SESSION["cart_voucher"]);
        $voucherMessage = "Voucher removed.";
        $voucherMessageType = "ok";
    }

    if ($voucherAction === "apply") {
        $voucherCode = strtoupper(trim((string)($_POST["voucher_code"] ?? "")));
        $promo = getPromoByCode($conn, $voucherCode, $userId);

        if ($voucherCode === "") {
            $voucherMessage = "Please enter a voucher code.";
            $voucherMessageType = "error";
        } elseif (!$promo) {
            unset($_SESSION["cart_voucher"]);
            $voucherMessage = "Invalid or inactive voucher code.";
            $voucherMessageType = "error";
        } elseif ((float)$promo["discount_percent"] <= 0) {
            unset($_SESSION["cart_voucher"]);
            $voucherMessage = "This voucher does not have a valid discount.";
            $voucherMessageType = "error";
        } elseif (hasUsedPromo($conn, $userId, (int)$promo["promo_id"])) {
            unset($_SESSION["cart_voucher"]);
            $voucherMessage = "This voucher has already been used.";
            $voucherMessageType = "error";
        } else {
            $_SESSION["cart_voucher"] = [
                "promo_id" => (int)$promo["promo_id"],
                "promo_code" => strtoupper((string)$promo["promo_code"]),
                "promo_name" => (string)$promo["promo_name"],
                "discount_percent" => (float)$promo["discount_percent"]
            ];
            $voucherMessage = strtoupper((string)$promo["promo_code"]) . " applied successfully.";
            $voucherMessageType = "ok";
        }
    }
}

function hasBookingOverlap($conn, $carId, $unitId, $stateId, $startDatetime, $endDatetime) {
    if (!tableExists($conn, "booking_items") || !tableExists($conn, "bookings")) return false;

    $where = ["? < bi.end_datetime", "? > bi.start_datetime"];
    $types = "ss";
    $params = [$startDatetime, $endDatetime];

    if ($unitId > 0) {
        $where[] = "bi.unit_id = ?";
        $types .= "i";
        $params[] = $unitId;
    } else {
        $where[] = "bi.car_id = ?";
        $types .= "i";
        $params[] = $carId;

        if ($stateId > 0) {
            $where[] = "(bi.pickup_state_id = ? OR bi.pickup_state_id IS NULL)";
            $types .= "i";
            $params[] = $stateId;
        }
    }

    $sql = "
        SELECT COUNT(*) AS total
        FROM booking_items bi
        INNER JOIN bookings b ON b.booking_id = bi.booking_id
        WHERE " . implode(" AND ", $where) . "
        AND LOWER(COALESCE(b.booking_status, 'pending')) NOT IN ('rejected','cancelled')
        AND LOWER(COALESCE(b.payment_status, 'pending')) NOT IN ('failed','refunded')
    ";

    $row = fetchOne($conn, $sql, $types, $params);
    return (int)($row["total"] ?? 0) > 0;
}

function findAvailableUnit($conn, $carId, $stateId, $startDatetime, $endDatetime) {
    if (!tableExists($conn, "car_units")) return 0;

    $unitIdCol = firstColumn($conn, "car_units", ["unit_id", "id"], "unit_id");
    $unitCarCol = firstColumn($conn, "car_units", ["car_id"], "car_id");
    $unitStateCol = firstColumn($conn, "car_units", ["state_id"], "state_id");
    $unitStatusCol = firstColumn($conn, "car_units", ["current_status", "status"], null);

    $where = ["$unitCarCol = ?", "$unitStateCol = ?"];
    $types = "ii";
    $params = [$carId, $stateId];

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


function importSessionCartToDatabase($conn, $userId) {
    if (empty($_SESSION["cart"]) || !is_array($_SESSION["cart"]) || !tableExists($conn, "cart_items") || !tableExists($conn, "cars")) {
        return;
    }

    $carIdCol = firstColumn($conn, "cars", ["car_id", "id"], "car_id");
    $priceCol = firstColumn($conn, "cars", ["price_per_day", "daily_rate", "price"], null);

    foreach ($_SESSION["cart"] as $key => $sessionItem) {
        $carId = (int)($sessionItem["car_id"] ?? 0);
        $stateId = (int)($sessionItem["state"] ?? $sessionItem["pickup_state_id"] ?? 0);
        $pickupLocation = (int)($sessionItem["pickup_location"] ?? 0);
        $dropoffLocation = (int)($sessionItem["dropoff_location"] ?? 0);
        $pickupDate = trim((string)($sessionItem["pickup_date"] ?? ""));
        $pickupTime = trim((string)($sessionItem["pickup_time"] ?? ""));
        $returnDate = trim((string)($sessionItem["return_date"] ?? ""));
        $returnTime = trim((string)($sessionItem["return_time"] ?? ""));

        if ($carId <= 0 || $stateId <= 0 || $pickupLocation <= 0 || $dropoffLocation <= 0 || $pickupDate === "" || $pickupTime === "" || $returnDate === "" || $returnTime === "") {
            unset($_SESSION["cart"][$key]);
            continue;
        }

        $startDatetime = $pickupDate . " " . $pickupTime . ":00";
        $endDatetime = $returnDate . " " . $returnTime . ":00";
        $startTs = strtotime($startDatetime);
        $endTs = strtotime($endDatetime);

        if (!$startTs || !$endTs || $endTs <= $startTs) {
            unset($_SESSION["cart"][$key]);
            continue;
        }

        $car = fetchOne($conn, "SELECT " . ($priceCol ? $priceCol : "0") . " AS price_per_day FROM cars WHERE $carIdCol = ? LIMIT 1", "i", [$carId]);
        if (!$car) {
            unset($_SESSION["cart"][$key]);
            continue;
        }

        $rentalDays = max(1, (int)ceil(($endTs - $startTs) / 86400));
        $pricePerDay = (float)($car["price_per_day"] ?? 0);
        $subtotal = $pricePerDay * $rentalDays;
        $unitId = findAvailableUnit($conn, $carId, $stateId, $startDatetime, $endDatetime);
        $status = $unitId > 0 ? "active" : "unavailable";

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
        ", "iiiiiss", [$userId, $carId, $stateId, $pickupLocation, $dropoffLocation, $startDatetime, $endDatetime]);

        if ($existing) {
            $cartItemId = (int)$existing["cart_item_id"];
            $stmt = $conn->prepare("
                UPDATE cart_items
                SET unit_id = ?, rental_days = ?, price_per_day = ?, subtotal = ?, status = ?, updated_at = NOW()
                WHERE cart_item_id = ? AND user_id = ?
            ");
            if ($stmt) {
                $stmt->bind_param("iiddsii", $unitId, $rentalDays, $pricePerDay, $subtotal, $status, $cartItemId, $userId);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            $stmt = $conn->prepare("
                INSERT INTO cart_items
                (user_id, car_id, unit_id, pickup_state_id, pickup_location, dropoff_location, start_datetime, end_datetime, rental_days, price_per_day, subtotal, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param("iiiiiissidds", $userId, $carId, $unitId, $stateId, $pickupLocation, $dropoffLocation, $startDatetime, $endDatetime, $rentalDays, $pricePerDay, $subtotal, $status);
                $stmt->execute();
                $stmt->close();
            }
        }

        unset($_SESSION["cart"][$key]);
    }

    if (empty($_SESSION["cart"])) {
        unset($_SESSION["cart"]);
    }
}

$tripMessages = $tripMessages ?? [];
$tripMessageTypes = $tripMessageTypes ?? [];
$tripSearchQueries = $tripSearchQueries ?? [];

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && ($_POST["trip_action"] ?? "") === "check_save") {
    $targetCartItemId = (int)($_POST["cart_item_id"] ?? 0);
    $newStateId = (int)($_POST["pickup_state_id"] ?? 0);
    $newPickupLocation = (int)($_POST["pickup_location"] ?? 0);
    $newDropoffLocation = (int)($_POST["dropoff_location"] ?? 0);
    $newPickupDate = trim((string)($_POST["pickup_date"] ?? ""));
    $newPickupTime = trim((string)($_POST["pickup_time"] ?? ""));
    $newReturnDate = trim((string)($_POST["return_date"] ?? ""));
    $newReturnTime = trim((string)($_POST["return_time"] ?? ""));
    $tripKey = (string)$targetCartItemId;

    if ($targetCartItemId <= 0 || $newStateId <= 0 || $newPickupLocation <= 0 || $newDropoffLocation <= 0 || $newPickupDate === "" || $newPickupTime === "" || $newReturnDate === "" || $newReturnTime === "") {
        $tripMessages[$tripKey] = "Please complete all trip details before saving changes.";
        $tripMessageTypes[$tripKey] = "error";
    } else {
        $newStartDatetime = $newPickupDate . " " . $newPickupTime . ":00";
        $newEndDatetime = $newReturnDate . " " . $newReturnTime . ":00";
        $newStartTs = strtotime($newStartDatetime);
        $newEndTs = strtotime($newEndDatetime);
        $todayStart = strtotime(date("Y-m-d 00:00:00"));

        $tripSearchQueries[$tripKey] = http_build_query([
            "state" => $newStateId,
            "pickup_location" => $newPickupLocation,
            "dropoff_location" => $newDropoffLocation,
            "pickup_date" => $newPickupDate,
            "pickup_time" => $newPickupTime,
            "return_date" => $newReturnDate,
            "return_time" => $newReturnTime
        ]);

        if (!$newStartTs || !$newEndTs || $newEndTs <= $newStartTs) {
            $tripMessages[$tripKey] = "Return date and time must be later than pickup date and time. Your original cart details have not been changed.";
            $tripMessageTypes[$tripKey] = "error";
        } elseif ($newStartTs < $todayStart) {
            $tripMessages[$tripKey] = "Pickup date cannot be in the past. Your original cart details have not been changed.";
            $tripMessageTypes[$tripKey] = "error";
        } else {
            $stateOk = true;
            if (tableExists($conn, "rental_locations")) {
                $locStateRows = fetchRows($conn, "SELECT location_id, state_id FROM rental_locations WHERE location_id IN (?, ?)", "ii", [$newPickupLocation, $newDropoffLocation]);
                foreach ($locStateRows as $locStateRow) {
                    if ((int)($locStateRow["state_id"] ?? 0) !== $newStateId) $stateOk = false;
                }
                if (count($locStateRows) < 2 && $newPickupLocation !== $newDropoffLocation) $stateOk = false;
            }

            if (!$stateOk) {
                $tripMessages[$tripKey] = "Pickup and drop-off locations must belong to the selected pickup state. Your original cart details have not been changed.";
                $tripMessageTypes[$tripKey] = "error";
            } else {
                $cartItem = fetchOne($conn, "SELECT * FROM cart_items WHERE cart_item_id = ? AND user_id = ? AND LOWER(COALESCE(status, 'active')) NOT IN ('removed','checked_out') LIMIT 1", "ii", [$targetCartItemId, $userId]);

                if (!$cartItem) {
                    $tripMessages[$tripKey] = "Cart item was not found. Please refresh and try again.";
                    $tripMessageTypes[$tripKey] = "error";
                } else {
                    $carIdForTrip = (int)($cartItem["car_id"] ?? 0);

                    $originalPickupDate = dateInputValue($cartItem["start_datetime"] ?? "");
                    $originalPickupTime = timeInputValue($cartItem["start_datetime"] ?? "");
                    $originalReturnDate = dateInputValue($cartItem["end_datetime"] ?? "");
                    $originalReturnTime = timeInputValue($cartItem["end_datetime"] ?? "");

                    $sameAsOriginalTrip =
                        (int)($cartItem["pickup_state_id"] ?? 0) === $newStateId &&
                        (int)($cartItem["pickup_location"] ?? 0) === $newPickupLocation &&
                        (int)($cartItem["dropoff_location"] ?? 0) === $newDropoffLocation &&
                        $originalPickupDate === $newPickupDate &&
                        $originalPickupTime === $newPickupTime &&
                        $originalReturnDate === $newReturnDate &&
                        $originalReturnTime === $newReturnTime;

                    if ($sameAsOriginalTrip) {
                        unset($tripMessages[$tripKey], $tripMessageTypes[$tripKey], $tripSearchQueries[$tripKey]);
                    } else {
                        $availableUnitForTrip = findAvailableUnit($conn, $carIdForTrip, $newStateId, $newStartDatetime, $newEndDatetime);

                        if ($availableUnitForTrip <= 0) {
                            $tripMessages[$tripKey] = "This car is not available for the selected new trip details. Your original cart details have not been changed.";
                            $tripMessageTypes[$tripKey] = "error";
                        } else {
                        $newRentalDays = max(1, (int)ceil(($newEndTs - $newStartTs) / 86400));
                        $newPricePerDay = (float)($cartItem["price_per_day"] ?? 0);
                        if ($newPricePerDay <= 0 && tableExists($conn, "cars")) {
                            $carPriceRow = fetchOne($conn, "SELECT price_per_day FROM cars WHERE car_id = ? LIMIT 1", "i", [$carIdForTrip]);
                            $newPricePerDay = (float)($carPriceRow["price_per_day"] ?? 0);
                        }
                        $newSubtotal = $newPricePerDay * $newRentalDays;
                        $newStatus = "active";

                        $stmt = $conn->prepare("\n                            UPDATE cart_items\n                            SET unit_id = ?, pickup_state_id = ?, pickup_location = ?, dropoff_location = ?, start_datetime = ?, end_datetime = ?, rental_days = ?, price_per_day = ?, subtotal = ?, status = ?, updated_at = NOW()\n                            WHERE cart_item_id = ? AND user_id = ?\n                        ");

                        if ($stmt) {
                            $stmt->bind_param("iiiissiddsii", $availableUnitForTrip, $newStateId, $newPickupLocation, $newDropoffLocation, $newStartDatetime, $newEndDatetime, $newRentalDays, $newPricePerDay, $newSubtotal, $newStatus, $targetCartItemId, $userId);
                            if ($stmt->execute()) {
                                $tripMessages[$tripKey] = "Trip details updated successfully. This car is still available for your new rental period.";
                                $tripMessageTypes[$tripKey] = "ok";
                            } else {
                                $tripMessages[$tripKey] = "Unable to update trip details. Please try again.";
                                $tripMessageTypes[$tripKey] = "error";
                            }
                            $stmt->close();
                        } else {
                            $tripMessages[$tripKey] = "Unable to update trip details. Please try again.";
                            $tripMessageTypes[$tripKey] = "error";
                        }
                    }
                    }
                }
            }
        }
    }
}

$cartStates = [];
$cartLocations = [];
if (tableExists($conn, "rental_states")) {
    $stateIdCol = firstColumn($conn, "rental_states", ["state_id", "id"], "state_id");
    $stateNameCol = firstColumn($conn, "rental_states", ["state_name", "name"], "state_name");
    $cartStates = fetchRows($conn, "SELECT $stateIdCol AS state_id, $stateNameCol AS state_name FROM rental_states ORDER BY $stateNameCol ASC");
}
if (!$cartStates) {
    $cartStates = [
        ["state_id" => 1, "state_name" => "Johor"],
        ["state_id" => 2, "state_name" => "Melaka"],
        ["state_id" => 3, "state_name" => "Kuala Lumpur"]
    ];
}
if (tableExists($conn, "rental_locations")) {
    $locationIdCol = firstColumn($conn, "rental_locations", ["location_id", "id"], "location_id");
    $locationNameCol = firstColumn($conn, "rental_locations", ["location_name", "name"], "location_name");
    $locationStateCol = firstColumn($conn, "rental_locations", ["state_id"], "state_id");
    $cartLocations = fetchRows($conn, "SELECT $locationIdCol AS location_id, $locationNameCol AS location_name, $locationStateCol AS state_id FROM rental_locations ORDER BY $locationStateCol ASC, $locationNameCol ASC");
}
if (!$cartLocations) {
    $cartLocations = [
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

$cartItems = [];
$totals = ["total" => 0, "available" => 0, "unavailable" => 0, "car_subtotal" => 0.00, "insurance" => 0.00, "addons" => 0.00, "driver_age" => 0.00, "fuel" => 0.00, "services_total" => 0.00, "subtotal" => 0.00, "discount" => 0.00, "grand" => 0.00];
$serviceChargeDays = 0;
$prepaidFuelPreview = 0.00;
$fuelBreakdown = [];
$cartTableReady = tableExists($conn, "cart_items");

if ($cartTableReady) {
    importSessionCartToDatabase($conn, $userId);


    $brandSelect = "'-' AS brand";
    $brandJoin = "";
    if (tableExists($conn, "brands") && columnExists($conn, "cars", "brand_id")) {
        $brandSelect = "COALESCE(b.brand_name, '-') AS brand";
        $brandJoin = " LEFT JOIN brands b ON b.brand_id = c.brand_id ";
    } elseif (columnExists($conn, "cars", "brand")) {
        $brandSelect = "COALESCE(c.brand, '-') AS brand";
    }

    $categorySelect = "'Others' AS category_name";
    $categoryJoin = "";
    if (tableExists($conn, "categories") && columnExists($conn, "cars", "category_id")) {
        $categorySelect = "COALESCE(cat.category_name, 'Others') AS category_name";
        $categoryJoin = " LEFT JOIN categories cat ON cat.category_id = c.category_id ";
    } elseif (tableExists($conn, "vehicle_categories") && columnExists($conn, "cars", "category_id")) {
        $categorySelect = "COALESCE(cat.category_name, 'Others') AS category_name";
        $categoryJoin = " LEFT JOIN vehicle_categories cat ON cat.category_id = c.category_id ";
    } elseif (columnExists($conn, "cars", "type")) {
        $categorySelect = "COALESCE(c.type, 'Others') AS category_name";
    }

    $imageCol = firstColumn($conn, "cars", ["image", "main_image", "car_image"], null);
    $imageSelect = $imageCol ? "c.$imageCol AS image" : "'' AS image";
    $carNameCol = firstColumn($conn, "cars", ["car_name", "name"], "car_name");

    $sql = "
        SELECT
            ci.cart_item_id,
            ci.user_id,
            ci.car_id,
            ci.unit_id,
            ci.pickup_state_id,
            ci.pickup_location,
            ci.dropoff_location,
            ci.start_datetime,
            ci.end_datetime,
            ci.rental_days,
            ci.price_per_day,
            ci.subtotal,
            ci.status,
            ci.created_at,
            c.$carNameCol AS car_name,
            $imageSelect,
            $brandSelect,
            $categorySelect,
            COALESCE(rs.state_name, '-') AS pickup_state_name,
            COALESCE(pl.location_name, '-') AS pickup_location_name,
            COALESCE(dl.location_name, '-') AS dropoff_location_name
        FROM cart_items ci
        INNER JOIN cars c ON c.car_id = ci.car_id
        $brandJoin
        $categoryJoin
        LEFT JOIN rental_states rs ON rs.state_id = ci.pickup_state_id
        LEFT JOIN rental_locations pl ON pl.location_id = ci.pickup_location
        LEFT JOIN rental_locations dl ON dl.location_id = ci.dropoff_location
        WHERE ci.user_id = ?
        AND LOWER(COALESCE(ci.status, 'active')) NOT IN ('removed','checked_out')
        ORDER BY ci.created_at DESC, ci.cart_item_id DESC
    ";

    $cartItems = fetchRows($conn, $sql, "i", [$userId]);

    foreach ($cartItems as $index => $item) {
        $carId = (int)$item["car_id"];
        $unitId = (int)($item["unit_id"] ?? 0);
        $stateId = (int)$item["pickup_state_id"];
        $startDatetime = (string)$item["start_datetime"];
        $endDatetime = (string)$item["end_datetime"];
        $currentAvailable = false;
        $availableUnit = 0;

        if ($unitId > 0) {
            $unitStatusCol = firstColumn($conn, "car_units", ["current_status", "status"], null);
            $unitStatusSql = $unitStatusCol ? "AND LOWER(COALESCE($unitStatusCol, 'available')) NOT IN ('maintenance','inactive')" : "";
            $unit = fetchOne($conn, "SELECT unit_id FROM car_units WHERE unit_id = ? AND car_id = ? AND state_id = ? $unitStatusSql LIMIT 1", "iii", [$unitId, $carId, $stateId]);
            $currentAvailable = !empty($unit) && !hasBookingOverlap($conn, $carId, $unitId, $stateId, $startDatetime, $endDatetime);
            $availableUnit = $currentAvailable ? $unitId : 0;
        }

        if (!$currentAvailable) {
            $availableUnit = findAvailableUnit($conn, $carId, $stateId, $startDatetime, $endDatetime);
            $currentAvailable = $availableUnit > 0;
        }

        $itemSubtotal = (float)$item["price_per_day"] * max(1, (int)$item["rental_days"]);
        $cartItems[$index]["computed_subtotal"] = $itemSubtotal;
        $cartItems[$index]["current_available"] = $currentAvailable;
        $cartItems[$index]["available_unit_id"] = $availableUnit;
        $cartItems[$index]["image_src"] = getCarImage($conn, $carId, $item["image"] ?? "", $item["car_name"] ?? "Car Image");
        $cartItems[$index]["details_query"] = http_build_query([
            "car_id" => $carId,
            "state" => $stateId,
            "pickup_location" => (int)$item["pickup_location"],
            "dropoff_location" => (int)$item["dropoff_location"],
            "pickup_date" => dateInputValue($startDatetime),
            "pickup_time" => timeInputValue($startDatetime),
            "return_date" => dateInputValue($endDatetime),
            "return_time" => timeInputValue($endDatetime)
        ]);
        $cartItems[$index]["trip_query"] = http_build_query([
            "state" => $stateId,
            "pickup_location" => (int)$item["pickup_location"],
            "dropoff_location" => (int)$item["dropoff_location"],
            "pickup_date" => dateInputValue($startDatetime),
            "pickup_time" => timeInputValue($startDatetime),
            "return_date" => dateInputValue($endDatetime),
            "return_time" => timeInputValue($endDatetime)
        ]);

        $itemAddonKey = (string)(int)$item["cart_item_id"];
        $itemAddons = cleanCartAddons($_SESSION["cart_item_addons"][$itemAddonKey] ?? [], $insurancePackages, $addonServices, $fuelPolicies, $driverAgeGroups);
        $itemServiceDays = max(1, (int)$item["rental_days"]);
        $itemInsurancePackage = $insurancePackages[$itemAddons["insurance"]] ?? $insurancePackages["basic"];
        $itemDriverAgeGroup = $driverAgeGroups[$itemAddons["driver_age"] ?? "normal"] ?? $driverAgeGroups["normal"];
        $itemInsuranceTotal = $currentAvailable ? (float)$itemInsurancePackage["price"] * $itemServiceDays : 0.00;
        $itemDriverAgeTotal = $currentAvailable ? (float)$itemDriverAgeGroup["price"] * $itemServiceDays : 0.00;
        $itemAddonsTotal = 0.00;
        $itemSelectedServiceLabels = [];

        foreach ($itemAddons["services"] as $serviceKey) {
            if (!isset($addonServices[$serviceKey])) continue;
            $service = $addonServices[$serviceKey];
            $itemSelectedServiceLabels[] = $service["name"] . " (" . money($service["price"]) . " / " . ((string)$service["unit"] === "day" ? "day" : "booking") . ")";
            $itemAddonsTotal += $currentAvailable
                ? ((string)$service["unit"] === "day" ? (float)$service["price"] * $itemServiceDays : (float)$service["price"])
                : 0.00;
        }

        $itemFuelChargePreview = fuelChargeByCategory($conn, (string)($item["category_name"] ?? "Others"));
        $itemFuelTotal = ($currentAvailable && $itemAddons["fuel"] === "prepaid_fuel") ? $itemFuelChargePreview : 0.00;
        $itemServicesTotal = $itemInsuranceTotal + $itemDriverAgeTotal + $itemAddonsTotal + $itemFuelTotal;

        $cartItems[$index]["active_addons"] = $itemAddons;
        $cartItems[$index]["insurance_total"] = $itemInsuranceTotal;
        $cartItems[$index]["driver_age_total"] = $itemDriverAgeTotal;
        $cartItems[$index]["addons_total"] = $itemAddonsTotal;
        $cartItems[$index]["fuel_total"] = $itemFuelTotal;
        $cartItems[$index]["fuel_charge_preview"] = $itemFuelChargePreview;
        $cartItems[$index]["services_total"] = $itemServicesTotal;
        $cartItems[$index]["insurance_name"] = $itemInsurancePackage["name"] ?? "Basic Coverage";
        $cartItems[$index]["driver_age_name"] = $itemDriverAgeGroup["name"] ?? "25–69 years";
        $cartItems[$index]["selected_service_labels"] = $itemSelectedServiceLabels;
        $cartItems[$index]["fuel_name"] = ($itemAddons["fuel"] === "prepaid_fuel") ? "Prepaid Fuel Package" : "No Fuel Package";

        $newStatus = $currentAvailable ? "active" : "unavailable";
        if (($item["status"] ?? "") !== $newStatus) {
            $stmt = $conn->prepare("UPDATE cart_items SET status = ? WHERE cart_item_id = ? AND user_id = ?");
            if ($stmt) {
                $cartId = (int)$item["cart_item_id"];
                $stmt->bind_param("sii", $newStatus, $cartId, $userId);
                $stmt->execute();
                $stmt->close();
            }
        }

        $totals["total"]++;
        if ($currentAvailable) {
            $totals["available"]++;
            $totals["car_subtotal"] += $itemSubtotal;
            $totals["insurance"] += $itemInsuranceTotal;
            $totals["driver_age"] += $itemDriverAgeTotal;
            $totals["addons"] += $itemAddonsTotal;
            $totals["fuel"] += $itemFuelTotal;
            $totals["services_total"] += $itemServicesTotal;
            $serviceChargeDays += max(1, (int)$item["rental_days"]);
        } else {
            $totals["unavailable"]++;
        }
    }

    $totals["subtotal"] = $totals["car_subtotal"] + $totals["services_total"];

    $activeVoucher = $_SESSION["cart_voucher"] ?? null;
    if (is_array($activeVoucher) && !empty($activeVoucher["promo_code"])) {
        $promo = getPromoByCode($conn, (string)$activeVoucher["promo_code"], $userId);

        if (!$promo || hasUsedPromo($conn, $userId, (int)$promo["promo_id"])) {
            unset($_SESSION["cart_voucher"]);
            $activeVoucher = null;
            if ($voucherMessage === "") {
                $voucherMessage = "The selected voucher is no longer available.";
                $voucherMessageType = "error";
            }
        } else {
            $_SESSION["cart_voucher"] = [
                "promo_id" => (int)$promo["promo_id"],
                "promo_code" => strtoupper((string)$promo["promo_code"]),
                "promo_name" => (string)$promo["promo_name"],
                "discount_percent" => (float)$promo["discount_percent"]
            ];
            $activeVoucher = $_SESSION["cart_voucher"];
            $totals["discount"] = round($totals["subtotal"] * ((float)$activeVoucher["discount_percent"] / 100), 2);
        }
    }

    $totals["grand"] = max(0, $totals["subtotal"] - $totals["discount"]);
}

$activeVoucher = $_SESSION["cart_voucher"] ?? null;
$hasTripEditBlockingError = in_array("error", $tripMessageTypes ?? [], true);
$canProceed = $totals["available"] > 0 && !$hasTripEditBlockingError;
$checkoutMessage = "";
if (($_GET["checkout_required"] ?? "") === "1") {
    $checkoutMessage = "Please select the car you want to pay for before continuing to payment.";
}

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST" && ($_POST["checkout_action"] ?? "") === "select_items") {
    $selectedCheckoutIds = $_POST["checkout_item_ids"] ?? [];
    if (!is_array($selectedCheckoutIds)) {
        $selectedCheckoutIds = [];
    }

    $selectedCheckoutIds = array_values(array_unique(array_filter(array_map("intval", $selectedCheckoutIds), fn($id) => $id > 0)));
    $availableCartIds = [];
    foreach ($cartItems as $item) {
        if (!empty($item["current_available"])) {
            $availableCartIds[] = (int)$item["cart_item_id"];
        }
    }

    $validCheckoutIds = array_values(array_intersect($selectedCheckoutIds, $availableCartIds));

    if (empty($validCheckoutIds)) {
        $checkoutMessage = "Please select at least one available car before payment.";
    } else {
        $_SESSION["checkout_cart_item_ids"] = $validCheckoutIds;
        header("Location: payment.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Cart | KH Car Rental</title>
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
    --green:#16a765;
    --red:#e2453b;
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

/* ===== Navbar: same as catalogue ===== */
.navbar{position:sticky;top:0;z-index:100;height:64px;background:linear-gradient(135deg,rgba(224,247,255,.94),rgba(255,255,255,.96),rgba(240,250,255,.94));border-bottom:1px solid rgba(142,207,244,.42);backdrop-filter:blur(18px)}
.nav-inner{width:min(1320px,calc(100% - 24px));height:64px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:12px}
.menu-btn{display:none}.brand{display:flex;align-items:center;gap:13px;font-size:15px;font-weight:950;white-space:nowrap;margin-right:10px;flex-shrink:0}.brand-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:15px;color:var(--sky600);background:linear-gradient(135deg,#d8f2ff,#fff);border:1px solid rgba(142,207,244,.46);box-shadow:0 14px 28px rgba(40,168,234,.13)}
.nav-links{flex:1;display:flex;align-items:center;justify-content:center;gap:12px;list-style:none;flex-wrap:nowrap;min-width:0}.nav-links a{display:inline-flex;align-items:center;gap:6px;padding:8px 5px;border-radius:999px;font-size:11.5px;font-weight:950;color:#2b4969;letter-spacing:.2px;white-space:nowrap}.nav-links a i{color:#2b4969;font-size:13px}.nav-links a.active,.nav-links a.active i,.nav-links a:hover,.nav-links a:hover i{color:var(--sky600)}
.avatar-wrap{position:relative;margin-left:0;flex-shrink:0}.avatar-btn{border:0;background:transparent;display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:950;color:var(--dark)}.avatar-circle{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;overflow:hidden;color:#fff;background:linear-gradient(135deg,var(--sky500),#0d3f82);border:3px solid #fff;box-shadow:0 14px 28px rgba(40,168,234,.18)}.avatar-circle img{width:100%;height:100%;object-fit:cover}.dropdown{position:absolute;right:0;top:62px;width:260px;display:none;padding:12px;border-radius:24px;background:rgba(255,255,255,.96);border:1px solid var(--border);box-shadow:0 24px 70px rgba(39,137,199,.18)}.dropdown.show{display:block}.dropdown a{min-height:54px;display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:17px;font-weight:900;color:#24415f}.dropdown a:hover{background:var(--sky100);color:var(--sky600)}.login-btn{display:inline-flex;align-items:center;gap:8px;padding:13px 18px;border-radius:999px;color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600));font-weight:950;white-space:nowrap;flex-shrink:0;min-width:max-content}

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

@media(max-width:1180px){.nav-links{display:none!important}.menu-btn{display:grid!important}}

.btn{min-height:42px;border:0;border-radius:14px;display:inline-flex;align-items:center;justify-content:center;gap:9px;padding:0 16px;font-size:12.5px;font-weight:950;cursor:pointer;transition:.24s;position:relative;overflow:hidden}.btn:hover{transform:translateY(-2px);box-shadow:var(--soft)}.btn-blue{color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600))}.btn-white{background:#fff;color:var(--sky600);border:1px solid var(--border)}.btn-orange{color:#fff;background:linear-gradient(135deg,#ff9a4a,#ff7a1a 48%,#f15f12);box-shadow:0 18px 34px rgba(255,122,26,.24)}.btn-danger{color:#fff;background:linear-gradient(135deg,#ff6b5f,#d92d20)}.btn-disabled{opacity:.55;cursor:not-allowed;pointer-events:none;filter:grayscale(.2)}

.cart-page{width:min(1280px,100%);margin:20px auto 58px;padding:0 22px}.breadcrumb{display:flex;align-items:center;gap:9px;flex-wrap:wrap;margin:14px 0;color:var(--muted);font-size:12px;font-weight:850}.breadcrumb a{color:var(--sky600)}
.cart-hero{padding:28px;border-radius:30px;background:radial-gradient(circle at 100% 0%,rgba(184,228,255,.25),transparent 32%),linear-gradient(135deg,rgba(255,255,255,.98),rgba(247,253,255,.94));border:1px solid rgba(184,228,255,.92);box-shadow:var(--shadow);display:grid;grid-template-columns:1.35fr .65fr;gap:20px;align-items:center;margin-bottom:18px}.pill{display:inline-flex;align-items:center;gap:8px;width:fit-content;padding:6px 11px;border-radius:999px;background:rgba(40,168,234,.12);color:var(--sky600);border:1px solid rgba(40,168,234,.22);font-size:10.5px;font-weight:950;letter-spacing:.8px;text-transform:uppercase;margin-bottom:10px}.cart-hero h1{font-size:clamp(38px,4vw,56px);line-height:1;letter-spacing:-1.8px;font-weight:950;margin-bottom:10px}.cart-hero p{color:var(--muted);font-size:14px;line-height:1.55;font-weight:700;max-width:720px}.hero-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.hero-stat{min-height:86px;border-radius:20px;background:rgba(255,255,255,.82);border:1px solid var(--border);box-shadow:var(--soft);display:grid;place-items:center;text-align:center;padding:12px}.hero-stat strong{font-size:27px;font-weight:950}.hero-stat span{font-size:11px;color:var(--muted);font-weight:950;text-transform:uppercase;letter-spacing:.5px}
.reminder{display:flex;align-items:flex-start;gap:14px;padding:16px 18px;border-radius:24px;background:linear-gradient(135deg,rgba(255,250,239,.96),rgba(255,255,255,.92));border:1px solid rgba(255,122,26,.22);box-shadow:0 14px 32px rgba(255,122,26,.10);margin-bottom:18px}.reminder i{width:42px;height:42px;border-radius:16px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#ff9a4a,#f15f12);flex:0 0 auto}.reminder h3{font-size:17px;font-weight:950;margin-bottom:3px}.reminder p{color:var(--muted);font-size:13px;line-height:1.45;font-weight:750}
.cart-layout{display:grid;grid-template-columns:1fr 380px;gap:18px;align-items:start}.cart-list{display:grid;gap:16px}.cart-item{border-radius:28px;background:radial-gradient(circle at 100% 0%,rgba(40,168,234,.08),transparent 28%),linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));border:1px solid rgba(184,228,255,.98);box-shadow:0 18px 46px rgba(29,109,164,.12);padding:16px;display:grid;grid-template-columns:230px 1fr;gap:16px}.cart-side{display:grid;gap:10px;align-content:start}.cart-media{height:180px;border-radius:22px;overflow:hidden;border:1px solid var(--border);background:linear-gradient(135deg,#edf9ff,#fff);position:relative}.cart-media img{width:100%;height:100%;object-fit:cover;display:block}.media-actions{display:grid;grid-template-columns:1fr;gap:8px}.media-actions .btn{width:100%;min-height:38px;border-radius:14px}.media-actions .remove-form{width:100%}.status-badge{position:absolute;left:12px;top:12px;display:inline-flex;align-items:center;gap:7px;padding:7px 10px;border-radius:999px;color:#fff;font-size:11px;font-weight:950;box-shadow:0 12px 24px rgba(16,35,61,.16)}.status-badge.available{background:linear-gradient(135deg,#19bc78,#079455)}.status-badge.unavailable{background:linear-gradient(135deg,#ff6b5f,#d92d20)}.item-head{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;margin-bottom:10px}.item-head h2{font-size:25px;line-height:1.1;font-weight:950;letter-spacing:-.5px}.item-head p{color:var(--muted);font-size:13px;font-weight:850;margin-top:4px}.price-mini{padding:10px 12px;border-radius:16px;background:var(--sky100);color:var(--sky600);font-size:12px;font-weight:950;white-space:nowrap}.info-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:12px 0}.info-box{min-height:70px;border-radius:16px;background:rgba(255,255,255,.86);border:1px solid var(--border);display:flex;align-items:center;gap:11px;padding:12px}.info-box i{width:34px;height:34px;border-radius:13px;display:grid;place-items:center;color:var(--sky600);background:var(--sky100);flex:0 0 auto}.info-box span{display:block;color:var(--muted);font-size:10px;font-weight:950;letter-spacing:.55px;text-transform:uppercase;margin-bottom:3px}.info-box strong{display:block;color:var(--dark);font-size:13.5px;font-weight:950;line-height:1.25}.status-note{border-radius:17px;padding:12px 14px;font-size:13px;font-weight:800;line-height:1.45;margin:8px 0 12px}.status-note.available{color:#087747;background:#f0fff8;border:1px solid rgba(20,184,116,.22)}.status-note.unavailable{color:#b42318;background:#fff4f2;border:1px solid rgba(244,67,54,.22)}.item-actions{display:flex;gap:9px;flex-wrap:wrap}.remove-form{display:inline-flex}.summary-card{position:sticky;top:84px;border-radius:28px;background:radial-gradient(circle at 100% 0%,rgba(40,168,234,.10),transparent 28%),linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));border:1px solid rgba(184,228,255,.98);box-shadow:0 18px 46px rgba(29,109,164,.12);padding:20px}.summary-card h2{font-size:25px;font-weight:950;letter-spacing:-.5px;margin-bottom:14px}.summary-row{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:11px 0;border-bottom:1px solid rgba(216,236,251,.92);font-size:13.5px;font-weight:850;color:#2b4969}.summary-row strong{color:var(--dark);font-weight:950}.summary-row.grand{border-bottom:0;padding-top:16px}.summary-row.grand span{font-size:16px;font-weight:950;color:var(--dark)}.summary-row.grand strong{font-size:28px;color:var(--sky600)}.voucher-box{margin:14px 0;padding:14px;border-radius:20px;background:rgba(234,247,255,.74);border:1px solid var(--border)}.voucher-box label{display:block;font-size:10px;font-weight:950;letter-spacing:.7px;text-transform:uppercase;color:var(--sky600);margin-bottom:8px}.voucher-row{display:grid;grid-template-columns:1fr 86px;gap:8px}.voucher-row input{height:38px;border-radius:13px;border:2px solid #e2f2ff;background:#fff;padding:0 11px;font-weight:850;outline:none}.voucher-message{margin-top:10px;padding:9px 10px;border-radius:13px;font-size:12px;font-weight:850;line-height:1.35}.voucher-message.ok{color:#087747;background:#f0fff8;border:1px solid rgba(20,184,116,.22)}.voucher-message.error{color:#b42318;background:#fff4f2;border:1px solid rgba(244,67,54,.22)}.applied-voucher{margin-top:10px;display:flex;justify-content:space-between;align-items:center;gap:10px;padding:9px 10px;border-radius:13px;background:#fff;border:1px solid var(--border);font-size:12px;font-weight:900;color:#2b4969}.applied-voucher strong{color:var(--sky600)}.voucher-remove{border:0;background:transparent;color:#d92d20;font-size:12px;font-weight:950;cursor:pointer}.summary-warning{margin:12px 0;padding:12px;border-radius:17px;background:#fff4f2;border:1px solid rgba(244,67,54,.22);color:#b42318;font-size:12.5px;font-weight:850;line-height:1.45}.summary-ok{margin:12px 0;padding:12px;border-radius:17px;background:#f0fff8;border:1px solid rgba(20,184,116,.22);color:#087747;font-size:12.5px;font-weight:850;line-height:1.45}.summary-card .btn{width:100%;margin-top:8px}.empty-cart{padding:56px 24px;border-radius:30px;text-align:center;background:linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));border:1px dashed rgba(40,168,234,.35);box-shadow:var(--shadow)}.empty-icon{width:88px;height:88px;margin:0 auto 18px;border-radius:30px;display:grid;place-items:center;color:var(--sky600);background:linear-gradient(135deg,#d8f2ff,#fff);border:1px solid var(--border);font-size:36px}.empty-cart h2{font-size:32px;font-weight:950;margin-bottom:8px}.empty-cart p{color:var(--muted);font-size:14px;font-weight:750;line-height:1.5;margin-bottom:18px}.empty-actions{display:flex;justify-content:center;gap:10px;flex-wrap:wrap}.system-box{padding:22px;border-radius:24px;background:#fff4f2;border:1px solid rgba(244,67,54,.22);color:#b42318;font-weight:850;line-height:1.5}


.addon-panel{margin:14px 0;border-radius:22px;background:linear-gradient(135deg,rgba(255,255,255,.95),rgba(234,247,255,.72));border:1px solid var(--border);overflow:hidden}.addon-panel summary{list-style:none;cursor:pointer;padding:14px 15px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:13px;font-weight:950;color:var(--dark)}.addon-panel summary::-webkit-details-marker{display:none}.addon-title{display:flex;align-items:center;gap:9px}.addon-title i{width:34px;height:34px;border-radius:13px;display:grid;place-items:center;color:var(--sky600);background:#fff;border:1px solid var(--border)}.addon-total-pill{padding:7px 10px;border-radius:999px;background:var(--sky100);color:var(--sky600);font-size:11px;font-weight:950;white-space:nowrap}.addon-body{padding:0 14px 14px}.addon-section{padding:13px;border-radius:18px;background:rgba(255,255,255,.78);border:1px solid rgba(216,236,251,.92);margin-bottom:10px}.addon-section h3{font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.65px;color:var(--sky600);margin-bottom:10px}.choice-grid{display:grid;grid-template-columns:1fr;gap:8px}.choice-card{position:relative;display:flex;align-items:flex-start;gap:10px;padding:11px;border-radius:16px;background:#fff;border:1px solid var(--border);cursor:pointer;transition:.22s}.choice-card:hover{border-color:rgba(40,168,234,.45);transform:translateY(-1px);box-shadow:0 10px 24px rgba(40,168,234,.08)}.choice-card input{margin-top:3px;accent-color:var(--sky600)}.choice-card strong{display:block;font-size:12.5px;font-weight:950;color:var(--dark);line-height:1.25}.choice-card small{display:block;margin-top:3px;font-size:11px;font-weight:750;color:var(--muted);line-height:1.35}.choice-price{margin-left:auto;white-space:nowrap;color:var(--sky600);font-size:11.5px;font-weight:950}.service-grid{display:grid;grid-template-columns:1fr 1fr;gap:8px}.service-card{position:relative;display:flex;align-items:center;gap:9px;min-height:50px;padding:9px;border-radius:15px;background:#fff;border:1px solid var(--border);cursor:pointer;transition:.22s}.service-card:hover{border-color:rgba(40,168,234,.45);transform:translateY(-1px);box-shadow:0 10px 24px rgba(40,168,234,.08)}.service-card input{accent-color:var(--sky600)}.service-card i{width:30px;height:30px;border-radius:12px;display:grid;place-items:center;color:var(--sky600);background:var(--sky100);flex:0 0 auto}.service-card strong{display:block;font-size:11.5px;font-weight:950;line-height:1.2}.service-card small{display:block;color:var(--muted);font-size:10px;font-weight:850;margin-top:2px}.addon-actions{display:grid;grid-template-columns:1fr;gap:8px}.addon-message{margin-top:8px;padding:9px 10px;border-radius:13px;font-size:12px;font-weight:850;line-height:1.35}.addon-message.ok{color:#087747;background:#f0fff8;border:1px solid rgba(20,184,116,.22)}.addon-message.error{color:#b42318;background:#fff4f2;border:1px solid rgba(244,67,54,.22)}.mini-summary{padding:9px 10px;border-radius:15px;background:#fff;border:1px solid var(--border);color:#2b4969;font-size:11.5px;font-weight:850;line-height:1.45;margin-bottom:10px}.mini-summary strong{color:var(--dark)}.fuel-breakdown{margin-top:8px;padding:9px 10px;border-radius:14px;background:rgba(234,247,255,.74);border:1px solid var(--border);display:grid;gap:6px}.fuel-breakdown-row{display:flex;align-items:center;justify-content:space-between;gap:10px;font-size:11px;font-weight:850;color:#2b4969}.fuel-breakdown-row strong{color:var(--dark);font-weight:950}.fuel-breakdown-row em{font-style:normal;color:var(--sky600);font-weight:950;white-space:nowrap}@media(max-width:760px){.service-grid{grid-template-columns:1fr}}
.addon-modal{position:fixed;inset:0;z-index:1000;display:none;place-items:center;padding:18px;background:rgba(13,31,55,.48);backdrop-filter:blur(10px)}.addon-modal.show{display:grid}.addon-modal-card{width:min(780px,calc(100% - 24px));max-height:min(88vh,760px);border-radius:28px;background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.96));border:1px solid rgba(184,228,255,.95);box-shadow:0 34px 90px rgba(23,48,79,.25);overflow:hidden;display:flex;flex-direction:column}.addon-modal-head{padding:18px 22px 14px;display:flex;align-items:flex-start;justify-content:space-between;gap:16px;border-bottom:1px solid rgba(216,236,251,.88)}.addon-modal-head h2{font-size:24px;font-weight:950;letter-spacing:-.6px;line-height:1.1}.addon-modal-head p{margin-top:4px;color:var(--muted);font-size:12.5px;font-weight:750;line-height:1.4}.addon-close{width:40px;height:40px;border:0;border-radius:14px;background:var(--sky100);color:var(--sky600);cursor:pointer;font-size:16px;flex:0 0 auto}.addon-modal .addon-body{padding:16px 18px 18px;overflow-y:auto}.open-addon-btn{width:100%;min-height:42px;border-radius:15px;margin-top:10px}.addon-modal .addon-section{margin-bottom:12px}.addon-modal .choice-grid{grid-template-columns:1fr}.addon-modal .service-grid{grid-template-columns:repeat(2,minmax(0,1fr))}@media(max-width:760px){.addon-modal .service-grid{grid-template-columns:1fr}.addon-modal-card{max-height:90vh}.addon-modal-head h2{font-size:20px}}

/* ===== Footer: same as catalogue ===== */
.footer{background:#12304f;color:#ffffff;padding:82px 34px 28px}.footer-inner{width:min(1200px,calc(100% - 40px));margin:0 auto 42px;display:grid;grid-template-columns:1.35fr 1fr 1.2fr;gap:62px}.footer h3{font-size:22px;font-weight:950;color:#ffffff;margin-bottom:18px;letter-spacing:-.3px}.footer p,.footer a{color:rgba(218,235,248,.76);font-size:15.5px;line-height:1.85;font-weight:650}.footer-hover-link,.footer .contact-link{width:fit-content;display:inline-flex;align-items:center;gap:10px;padding:3px 0;border-radius:12px;transition:color .22s ease,transform .22s ease,background .22s ease,padding .22s ease}.footer-hover-link i{display:none}.footer .contact-link i{width:18px;color:var(--sky500);transition:.22s ease}.footer-hover-link:hover,.footer .contact-link:hover{color:#ffffff;transform:translateX(6px);background:rgba(255,255,255,.055);padding-left:8px;padding-right:10px}.footer .contact-link:hover i{color:#7fd0ff;transform:scale(1.08)}footer.footer .footer-inner > div:first-child > a.start-btn,footer.footer a.start-btn,.footer a.start-btn,a.start-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;width:auto!important;min-width:210px!important;height:44px!important;min-height:44px!important;padding:0 28px!important;border-radius:16px!important;background:linear-gradient(135deg,#28a8ea,#1284c6)!important;color:#ffffff!important;border:1px solid rgba(113,210,255,.22)!important;box-shadow:0 16px 32px rgba(40,168,234,.24)!important;font-size:15.5px!important;font-weight:950!important;line-height:1!important;letter-spacing:.15px!important;text-decoration:none!important;white-space:nowrap!important;overflow:hidden!important;margin-top:22px}footer.footer .footer-inner > div:first-child > a.start-btn i,footer.footer a.start-btn i,.footer a.start-btn i,a.start-btn i{color:#ffffff!important;font-size:15.5px!important;line-height:1!important}footer.footer .footer-inner > div:first-child > a.start-btn:hover,footer.footer a.start-btn:hover,.footer a.start-btn:hover,a.start-btn:hover{transform:translateY(-2px)!important;color:#ffffff!important;box-shadow:0 20px 38px rgba(40,168,234,.32)!important}.footer-bottom{width:min(1200px,calc(100% - 40px));margin:0 auto;padding-top:22px;border-top:1px solid rgba(255,255,255,.14);text-align:center;color:rgba(218,235,248,.86);font-size:14px}.back-top{position:fixed;right:28px;bottom:28px;width:54px;height:54px;border-radius:50%;border:0;color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600));box-shadow:0 20px 40px rgba(40,168,234,.3);cursor:pointer}
@media(max-width:1080px){.cart-layout,.cart-hero{grid-template-columns:1fr}.summary-card{position:static}.hero-stats{grid-template-columns:repeat(3,1fr)}}@media(max-width:760px){.cart-page{padding:0 14px}.cart-item{grid-template-columns:1fr}.cart-media{height:220px}.info-grid{grid-template-columns:1fr}.hero-stats{grid-template-columns:1fr}.footer-inner{grid-template-columns:1fr;gap:34px}.footer{padding:60px 24px 26px}}


/* ===== Premium Cart Add-on Upgrade ===== */
.btn::before,.cart-item::before,.summary-card::before,.choice-card::before,.service-card::before,.info-box::before,.open-addon-btn::before{
    content:"";
    position:absolute;
    top:0;
    left:-130%;
    width:55%;
    height:100%;
    background:linear-gradient(120deg,transparent,rgba(255,255,255,.48),transparent);
    transform:skewX(-22deg);
    transition:left .62s ease;
    pointer-events:none;
}
.btn:hover::before,.cart-item:hover::before,.summary-card:hover::before,.choice-card:hover::before,.service-card:hover::before,.info-box:hover::before,.open-addon-btn:hover::before{left:135%;}
.cart-item,.summary-card,.info-box,.choice-card,.service-card,.open-addon-btn{position:relative;overflow:hidden;}
.cart-item:hover,.summary-card:hover{border-color:rgba(40,168,234,.42);box-shadow:0 28px 70px rgba(29,109,164,.18);}
.info-box:hover,.choice-card:hover,.service-card:hover{transform:translateY(-3px);border-color:rgba(40,168,234,.46);box-shadow:0 16px 32px rgba(40,168,234,.13);}
.choice-card:has(input:checked),.service-card:has(input:checked){border-color:rgba(40,168,234,.72);background:linear-gradient(135deg,#ffffff,#eaf7ff);box-shadow:0 16px 36px rgba(40,168,234,.16);}
.choice-card:has(input:checked)::after,.service-card:has(input:checked)::after{content:"\f00c";font-family:"Font Awesome 6 Free";font-weight:900;position:absolute;right:10px;top:10px;width:22px;height:22px;border-radius:50%;display:grid;place-items:center;background:linear-gradient(135deg,var(--sky500),var(--sky600));color:#fff;font-size:10px;}
.choice-card span,.service-card span{min-width:0;display:block;padding-right:24px;}
.service-card i,.choice-icon{width:34px;height:34px;border-radius:13px;display:grid;place-items:center;color:var(--sky600);background:var(--sky100);border:1px solid rgba(216,236,251,.85);flex:0 0 34px;}
.choice-card input,.service-card input{flex:0 0 auto;}
.addon-modal-card{width:min(860px,calc(100% - 24px));max-height:min(90vh,790px);}
.addon-modal-head{background:linear-gradient(135deg,rgba(234,247,255,.92),rgba(255,255,255,.98));}
.addon-modal .addon-body{padding-bottom:84px;}
.addon-actions{position:sticky;bottom:0;margin:14px -18px -18px;padding:12px 18px;background:linear-gradient(180deg,rgba(255,255,255,.72),rgba(255,255,255,.98) 36%,rgba(234,247,255,.96));border-top:1px solid rgba(216,236,251,.92);z-index:8;}
.addon-actions .btn{min-height:44px;border-radius:15px;}
.addon-section h3{display:flex;align-items:center;gap:8px;}
.addon-section h3 i{width:25px;height:25px;border-radius:10px;display:grid;place-items:center;background:var(--sky100);color:var(--sky600);font-size:12px;}
.addon-summary-strip{margin:12px 0 0;padding:12px;border-radius:18px;background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(234,247,255,.72));border:1px solid var(--border);display:grid;gap:8px;}
.addon-summary-title{display:flex;align-items:center;gap:8px;color:var(--sky600);font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.65px;}
.addon-chip-row{display:flex;flex-wrap:wrap;gap:7px;}
.addon-chip{display:inline-flex;align-items:center;gap:6px;max-width:100%;padding:7px 10px;border-radius:999px;background:#fff;border:1px solid rgba(216,236,251,.92);color:#2b4969;font-size:11px;font-weight:900;line-height:1.2;}
.addon-chip i{color:var(--sky600);font-size:11px;}
.summary-card{position:static!important;}
.summary-card h2{display:flex;align-items:center;gap:10px;}
.summary-card h2::before{content:"\f570";font-family:"Font Awesome 6 Free";font-weight:900;width:38px;height:38px;border-radius:14px;display:grid;place-items:center;background:var(--sky100);color:var(--sky600);font-size:15px;}
.summary-row{align-items:flex-start;}
.summary-row span{display:inline-flex;align-items:center;gap:8px;}
.summary-row span i{width:22px;height:22px;border-radius:9px;display:grid;place-items:center;background:var(--sky100);color:var(--sky600);font-size:10px;flex:0 0 22px;}
.summary-detail{margin:10px 0 2px;padding:11px;border-radius:16px;background:rgba(255,255,255,.76);border:1px solid rgba(216,236,251,.88);display:grid;gap:8px;}
.summary-detail-title{display:flex;align-items:center;gap:8px;color:var(--sky600);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.65px;}
.summary-detail-list{display:grid;gap:6px;}
.summary-detail-item{display:flex;justify-content:space-between;gap:10px;font-size:11.5px;font-weight:850;color:#2b4969;line-height:1.35;}
.summary-detail-item strong{color:var(--dark);font-weight:950;text-align:right;}
@media(max-width:760px){.addon-modal .service-grid{grid-template-columns:1fr}.summary-detail-item{display:grid}.summary-detail-item strong{text-align:left}.addon-actions{margin-left:-18px;margin-right:-18px}}



/* ===== FINAL CART UX FIXES ===== */
.cart-hero{
    padding:22px 26px!important;
    border-radius:26px!important;
    min-height:150px!important;
}
.cart-hero h1{
    font-size:clamp(34px,3.4vw,48px)!important;
    margin-bottom:8px!important;
}
.cart-hero p{
    font-size:13.5px!important;
    line-height:1.45!important;
}
.hero-stat{
    min-height:76px!important;
    border-radius:18px!important;
    padding:10px!important;
}
.hero-stat strong{
    font-size:24px!important;
}
.hero-stat span{
    font-size:10.5px!important;
}
.summary-card::before,
.summary-card:hover::before{
    display:none!important;
    content:none!important;
}
.summary-card:hover{
    transform:none!important;
    border-color:rgba(184,228,255,.98)!important;
    box-shadow:0 18px 46px rgba(29,109,164,.12)!important;
}
.summary-detail:hover,
.summary-row:hover,
.voucher-box:hover{
    transform:none!important;
    box-shadow:none!important;
}
.choice-card,
.service-card{
    align-items:center!important;
    padding:12px 14px!important;
    overflow:hidden!important;
}
.choice-card > span,
.service-card > span{
    min-width:0!important;
    flex:1 1 auto!important;
    padding-right:8px!important;
}
.choice-card input,
.service-card input{
    width:18px!important;
    height:18px!important;
    flex:0 0 18px!important;
    appearance:none!important;
    -webkit-appearance:none!important;
    border:2px solid #96a8bc!important;
    background:#fff!important;
    display:grid!important;
    place-items:center!important;
    margin:0!important;
    cursor:pointer!important;
}
.choice-card input[type="radio"]{
    border-radius:50%!important;
}
.service-card input[type="checkbox"]{
    border-radius:6px!important;
}
.choice-card input:checked,
.service-card input:checked{
    border-color:#16a765!important;
    background:linear-gradient(135deg,#18bd74,#079455)!important;
}
.choice-card input:checked::before,
.service-card input:checked::before{
    content:"\2713"!important;
    color:#fff!important;
    font-size:12px!important;
    font-weight:950!important;
    line-height:1!important;
}
.choice-card:has(input:checked)::after,
.service-card:has(input:checked)::after{
    display:none!important;
    content:none!important;
}
.choice-card:has(input:checked),
.service-card:has(input:checked){
    border-color:rgba(22,167,101,.48)!important;
    background:linear-gradient(135deg,#f0fff8,#ffffff)!important;
    box-shadow:0 14px 32px rgba(22,167,101,.12)!important;
}
.choice-icon,
.service-card i{
    flex:0 0 36px!important;
    width:36px!important;
    height:36px!important;
    border-radius:14px!important;
}
.choice-price{
    flex:0 0 auto!important;
    padding-left:8px!important;
    font-size:11.5px!important;
}
.addon-actions{
    position:static!important;
    margin:12px 0 0!important;
    padding:0!important;
    background:transparent!important;
    border-top:0!important;
    z-index:auto!important;
}
.addon-actions .btn{
    min-height:46px!important;
    border-radius:16px!important;
}
.addon-modal .addon-body{
    padding-bottom:18px!important;
}
.addon-message-above{
    margin:10px 0 8px!important;
}
.open-addon-btn{
    box-shadow:0 16px 34px rgba(40,168,234,.16)!important;
}
.media-actions .btn{
    min-height:42px!important;
    border-radius:16px!important;
    font-size:12.5px!important;
    box-shadow:0 14px 28px rgba(40,168,234,.10)!important;
}
.media-actions .btn-white{
    background:linear-gradient(135deg,#ffffff,#f4fbff)!important;
    border:1px solid rgba(40,168,234,.26)!important;
}
.media-actions .btn-danger{
    background:linear-gradient(135deg,#fff4f2,#ffffff)!important;
    color:#d92d20!important;
    border:1px solid rgba(217,45,32,.20)!important;
}
.media-actions .btn-danger:hover{
    background:linear-gradient(135deg,#ff6b5f,#d92d20)!important;
    color:#fff!important;
}
.summary-detail{
    padding:12px 13px!important;
    border-radius:18px!important;
    background:linear-gradient(135deg,rgba(255,255,255,.92),rgba(244,251,255,.76))!important;
}
.summary-detail-title{
    font-size:10.3px!important;
    color:#1284c6!important;
}
.summary-detail-item{
    align-items:flex-start!important;
}
.summary-detail-item span{
    min-width:0!important;
}
.summary-detail-item strong{
    white-space:nowrap!important;
}
.summary-ok,
.summary-warning{
    display:flex!important;
    align-items:center!important;
    gap:10px!important;
    padding:13px 14px!important;
    border-radius:18px!important;
    line-height:1.35!important;
}
.summary-ok i,
.summary-warning i{
    width:28px!important;
    height:28px!important;
    border-radius:50%!important;
    display:grid!important;
    place-items:center!important;
    flex:0 0 auto!important;
    color:#fff!important;
}
.summary-ok i{
    background:linear-gradient(135deg,#18bd74,#079455)!important;
}
.summary-warning i{
    background:linear-gradient(135deg,#ff6b5f,#d92d20)!important;
}
.checkout-select-card{display:flex;align-items:flex-start;gap:11px;padding:13px 14px;border-radius:17px;background:linear-gradient(135deg,#f0fff8,#fff);border:1px solid rgba(20,184,116,.22);color:#087747;font-size:12px;font-weight:950;line-height:1.35;cursor:pointer}
.checkout-select-card input{width:18px;height:18px;margin-top:1px;accent-color:var(--green);flex:0 0 auto}
.checkout-select-card.disabled{background:#f5f8fb;border-color:#dce8f2;color:#8aa0b5;cursor:not-allowed}
.checkout-select-card.disabled input{accent-color:#aebdca}
.checkout-select-card small{display:block;color:inherit;opacity:.76;font-size:10.5px;font-weight:850;margin-top:3px}
.checkout-action-form{margin-top:8px}
.checkout-empty-warning{display:none;margin:12px 0;padding:12px;border-radius:17px;background:#fff8e6;border:1px solid rgba(255,176,32,.38);color:#9a5b00;font-size:12.5px;font-weight:850;line-height:1.45}
.checkout-empty-warning.show{display:block}
.checkout-message{margin:12px 0;padding:12px;border-radius:17px;background:#fff8e6;border:1px solid rgba(255,176,32,.38);color:#9a5b00;font-size:12.5px;font-weight:850;line-height:1.45}
.btn::before,.cart-item::before,.choice-card::before,.service-card::before,.info-box::before,.open-addon-btn::before{
    pointer-events:none!important;
}
@media(max-width:760px){
    .cart-hero{padding:18px!important;}
    .choice-card,.service-card{align-items:flex-start!important;}
    .choice-price{margin-left:0!important;}
}

/* ===== FINAL REQUEST: compact summary stats + view detail hover ===== */
.summary-mini-stats{
    display:grid;
    grid-template-columns:repeat(3,minmax(0,1fr));
    gap:8px;
    margin:0 0 14px;
}
.summary-mini-stat{
    min-height:64px;
    padding:9px 7px;
    border-radius:17px;
    background:rgba(255,255,255,.88);
    border:1px solid var(--border);
    box-shadow:0 10px 24px rgba(40,168,234,.08);
    display:grid;
    place-items:center;
    text-align:center;
}
.summary-mini-stat strong{
    font-size:22px;
    line-height:1;
    font-weight:950;
    color:var(--dark);
}
.summary-mini-stat span{
    margin-top:4px;
    font-size:9.5px;
    line-height:1.15;
    color:var(--muted);
    font-weight:950;
    text-transform:uppercase;
    letter-spacing:.45px;
}
.media-actions .btn-white:hover{
    background:linear-gradient(135deg,#eaf7ff,#ffffff)!important;
    color:var(--sky600)!important;
    border-color:rgba(40,168,234,.48)!important;
    box-shadow:0 16px 34px rgba(40,168,234,.18)!important;
}
.cart-page{
    margin-top:14px!important;
}
.reminder{
    margin-top:0!important;
}
@media(max-width:760px){
    .summary-mini-stats{grid-template-columns:1fr 1fr 1fr;gap:7px;}
    .summary-mini-stat{min-height:58px;padding:8px 5px;}
    .summary-mini-stat strong{font-size:19px;}
    .summary-mini-stat span{font-size:8.8px;}
}



/* ===== FINAL COLOR REQUEST: payment green, service orange, stronger view detail hover ===== */
.summary-card a[href="payment.php"].btn,
.summary-card .btn.btn-blue[href="payment.php"]{
    background:linear-gradient(135deg,#22c77a,#16a765 48%,#079455)!important;
    color:#ffffff!important;
    border:1px solid rgba(22,167,101,.28)!important;
    box-shadow:0 18px 36px rgba(22,167,101,.26)!important;
}
.summary-card a[href="payment.php"].btn:hover,
.summary-card .btn.btn-blue[href="payment.php"]:hover{
    background:linear-gradient(135deg,#32d889,#16a765 48%,#078145)!important;
    box-shadow:0 22px 42px rgba(22,167,101,.34)!important;
}
.summary-card .btn-disabled{
    background:linear-gradient(135deg,#9fdcbc,#77cfa3)!important;
    color:#ffffff!important;
}
.open-addon-btn,
button.open-addon-btn,
.btn.open-addon-btn{
    background:linear-gradient(135deg,#ffae5f,#ff7a1a 48%,#f15f12)!important;
    color:#ffffff!important;
    border:1px solid rgba(255,122,26,.30)!important;
    box-shadow:0 18px 36px rgba(255,122,26,.28)!important;
}
.open-addon-btn:hover,
button.open-addon-btn:hover,
.btn.open-addon-btn:hover{
    background:linear-gradient(135deg,#ffc07d,#ff8a2b 48%,#f15f12)!important;
    color:#ffffff!important;
    box-shadow:0 22px 44px rgba(255,122,26,.36)!important;
}
.media-actions .btn-white:hover,
.media-actions a.btn-white:hover{
    background:linear-gradient(135deg,#c9efff,#e8f8ff 48%,#ffffff)!important;
    color:#0878bb!important;
    border-color:rgba(18,132,198,.75)!important;
    box-shadow:0 18px 38px rgba(40,168,234,.28)!important;
}
.media-actions .btn-white:hover i,
.media-actions a.btn-white:hover i{
    color:#0878bb!important;
}



/* ===== EDIT TRIP DETAILS MODAL ===== */
.btn-green{
    color:#fff!important;
    background:linear-gradient(135deg,#22c77a,#16a765 48%,#079455)!important;
    border:1px solid rgba(22,167,101,.28)!important;
    box-shadow:0 18px 36px rgba(22,167,101,.24)!important;
}
.btn-green:hover{
    background:linear-gradient(135deg,#33d98a,#16a765 48%,#078145)!important;
    box-shadow:0 22px 42px rgba(22,167,101,.32)!important;
}
.btn-browse{
    background:linear-gradient(135deg,#ffffff,#edf9ff)!important;
    color:#1284c6!important;
    border:1px solid rgba(40,168,234,.26)!important;
}
.btn-browse:hover{
    background:linear-gradient(135deg,#d8f2ff,#ffffff)!important;
    color:#0878bb!important;
    border-color:rgba(18,132,198,.62)!important;
    box-shadow:0 18px 38px rgba(40,168,234,.24)!important;
}
.btn-trip{
    background:linear-gradient(135deg,#fff7ef,#ffffff)!important;
    color:#f15f12!important;
    border:1px solid rgba(255,122,26,.28)!important;
}
.btn-trip:hover{
    background:linear-gradient(135deg,#ffead6,#ffffff)!important;
    color:#df550d!important;
    border-color:rgba(255,122,26,.58)!important;
    box-shadow:0 18px 38px rgba(255,122,26,.22)!important;
}
.btn-keep{
    background:linear-gradient(135deg,#ffffff,#f8fcff)!important;
    color:#6e8297!important;
    border:1px solid rgba(216,236,251,.92)!important;
    cursor:pointer!important;
}
.btn-keep:hover{
    background:linear-gradient(135deg,#eaf7ff,#ffffff)!important;
    color:#1284c6!important;
    border-color:rgba(40,168,234,.42)!important;
    box-shadow:0 16px 32px rgba(40,168,234,.16)!important;
}
.trip-modal{
    position:fixed;
    inset:0;
    z-index:1001;
    display:none;
    place-items:center;
    padding:18px;
    background:rgba(13,31,55,.48);
    backdrop-filter:blur(10px);
}
.trip-modal.show{display:grid;}
.trip-modal-card{
    width:min(760px,calc(100% - 24px));
    max-height:min(90vh,760px);
    border-radius:28px;
    background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.96));
    border:1px solid rgba(184,228,255,.95);
    box-shadow:0 34px 90px rgba(23,48,79,.25);
    overflow:hidden;
    display:flex;
    flex-direction:column;
}
.trip-modal-head{
    padding:18px 22px 14px;
    display:flex;
    align-items:flex-start;
    justify-content:space-between;
    gap:16px;
    border-bottom:1px solid rgba(216,236,251,.88);
    background:linear-gradient(135deg,rgba(234,247,255,.92),rgba(255,255,255,.98));
}
.trip-modal-head h2{
    font-size:24px;
    line-height:1.1;
    font-weight:950;
    letter-spacing:-.6px;
}
.trip-modal-head p{
    margin-top:4px;
    color:var(--muted);
    font-size:12.5px;
    line-height:1.4;
    font-weight:750;
}
.trip-close{
    width:40px;
    height:40px;
    border:0;
    border-radius:14px;
    background:var(--sky100);
    color:var(--sky600);
    cursor:pointer;
    font-size:16px;
    flex:0 0 auto;
    transition:.22s ease;
}
.trip-close:hover{transform:translateY(-2px);background:#d8f2ff;}
.trip-edit-form{
    padding:16px 18px 18px;
    overflow-y:auto;
}
.trip-edit-alert{
    display:none;
    align-items:flex-start;
    gap:10px;
    padding:12px 13px;
    border-radius:17px;
    background:linear-gradient(135deg,#f0fff8,#ffffff);
    border:1px solid rgba(22,167,101,.20);
    color:#087747;
    font-size:12.5px;
    font-weight:850;
    line-height:1.45;
    margin-bottom:12px;
}
.trip-edit-alert.show{display:flex;}
.trip-edit-alert i{
    width:26px;
    height:26px;
    border-radius:50%;
    display:grid;
    place-items:center;
    background:linear-gradient(135deg,#22c77a,#079455);
    color:#fff;
    flex:0 0 auto;
    font-size:11px;
}
.trip-form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:10px 12px;
}
.trip-field.full{grid-column:1/-1;}
.trip-field label{
    display:block;
    margin-bottom:5px;
    color:#2b4969;
    font-size:10px;
    font-weight:950;
    letter-spacing:.65px;
    text-transform:uppercase;
}
.trip-input{
    width:100%;
    min-height:42px;
    border:2px solid #e2f2ff;
    background:rgba(255,255,255,.94);
    color:var(--dark);
    border-radius:14px;
    padding:9px 12px;
    outline:none;
    font-size:13px;
    font-weight:850;
    transition:.22s ease;
}
.trip-input:focus{
    border-color:var(--sky500);
    box-shadow:0 0 0 .22rem rgba(40,168,234,.13);
    background:#fff;
}
.trip-modal-actions{
    margin-top:14px;
    padding-top:12px;
    border-top:1px solid rgba(216,236,251,.92);
    display:grid;
    grid-template-columns:1.35fr .65fr;
    gap:10px;
}
.trip-message{
    margin:10px 0 12px;
    display:flex;
    align-items:flex-start;
    gap:11px;
    padding:13px 14px;
    border-radius:18px;
    font-size:13px;
    line-height:1.45;
    font-weight:800;
}
.trip-message.ok{
    color:#087747;
    background:#f0fff8;
    border:1px solid rgba(20,184,116,.22);
}
.trip-message.error{
    color:#b42318;
    background:#fff4f2;
    border:1px solid rgba(244,67,54,.22);
}
.trip-message > div:first-child i{
    width:30px;
    height:30px;
    border-radius:50%;
    display:grid;
    place-items:center;
    color:#fff;
    flex:0 0 auto;
}
.trip-message.ok > div:first-child i{background:linear-gradient(135deg,#18bd74,#079455);}
.trip-message.error > div:first-child i{background:linear-gradient(135deg,#ff6b5f,#d92d20);}
.trip-message strong{display:block;margin-bottom:2px;color:inherit;font-weight:950;}
.trip-message p{margin:0;color:inherit;font-weight:800;}
.trip-message-actions{
    margin-top:10px;
    display:flex;
    flex-wrap:wrap;
    gap:8px;
}
.trip-message-actions .btn{min-height:36px;border-radius:13px;font-size:11.5px;}
@media(max-width:760px){
    .trip-form-grid,.trip-modal-actions{grid-template-columns:1fr;}
    .trip-modal-card{max-height:90vh;}
    .trip-modal-head h2{font-size:20px;}
}


/* ===== FINAL FIX: trip edit unavailable action buttons 1x3 and payment block ===== */
.trip-message-actions{
    display:grid!important;
    grid-template-columns:repeat(3,minmax(0,1fr))!important;
    gap:10px!important;
    align-items:stretch!important;
    margin-top:12px!important;
}
.trip-message-actions .btn{
    width:100%!important;
    min-height:44px!important;
    border-radius:16px!important;
    white-space:nowrap!important;
}
.btn-disabled,
.btn-disabled:hover{
    background:linear-gradient(135deg,#d8e3ed,#b8c7d6)!important;
    color:#ffffff!important;
    box-shadow:none!important;
    transform:none!important;
    cursor:not-allowed!important;
    pointer-events:none!important;
    opacity:.78!important;
}
@media(max-width:760px){
    .trip-message-actions{grid-template-columns:1fr!important;}
}



/* ===== FINAL REQUEST: cleaner service button, soft yellow browsing, car-only summary ===== */
.btn-browse,
a.btn-browse{
    background:linear-gradient(135deg,#fffdf4,#fff8dc 52%,#ffffff)!important;
    color:#c97a00!important;
    border:1px solid rgba(255,190,75,.40)!important;
    box-shadow:0 10px 22px rgba(255,190,75,.10)!important;
}
.btn-browse i,
a.btn-browse i{
    color:#d88900!important;
}
.btn-browse:hover,
a.btn-browse:hover{
    background:linear-gradient(135deg,#fff4bf,#fff9e7 52%,#ffffff)!important;
    color:#a86600!important;
    border-color:rgba(255,173,13,.58)!important;
    box-shadow:0 16px 34px rgba(255,190,75,.18)!important;
}
.btn-browse:hover i,
a.btn-browse:hover i{
    color:#a86600!important;
}
.open-addon-btn .addon-total-pill{
    background:#ffffff!important;
    color:#f15f12!important;
    border:1px solid rgba(255,255,255,.72)!important;
    padding:7px 12px!important;
    font-size:11px!important;
    letter-spacing:.2px!important;
}
.car-rental-only-list .summary-detail-item{
    align-items:flex-start!important;
}
.summary-rental-price{
    margin-top:3px!important;
    padding-top:8px!important;
    border-top:1px dashed rgba(40,168,234,.25)!important;
}
.summary-rental-price span,
.summary-extra-total span{
    color:var(--dark)!important;
    font-weight:950!important;
}
.summary-rental-price strong,
.summary-extra-total strong{
    color:var(--sky600)!important;
    font-size:13px!important;
}
.summary-extra-total{
    margin-top:4px!important;
    padding-top:8px!important;
    border-top:1px dashed rgba(40,168,234,.25)!important;
}


/* ===== Per-car service breakdown in order summary ===== */
.car-service-summary{
    gap:9px!important;
}
.summary-car-divider{
    height:1px;
    width:100%;
    margin:4px 0 2px;
    border-top:1px dashed rgba(40,168,234,.28);
}
.car-service-summary .service-title{
    margin-top:2px;
}
.car-service-summary .summary-extra-total{
    margin-top:3px!important;
    padding-top:8px!important;
    border-top:1px dashed rgba(40,168,234,.25)!important;
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
            <li><a href="compare_car.php"><i class="fa-solid fa-code-compare"></i> COMPARE CAR</a></li>
            <li><a href="aboutus.php"><i class="fa-solid fa-circle-info"></i> ABOUT US</a></li>
            <li><a href="contactus.php"><i class="fa-solid fa-envelope"></i> CONTACT US</a></li>
            <li><a href="cart.php" class="active nav-cart-link"><i class="fa-solid fa-cart-shopping"></i> CART<?php if($navCartCount > 0): ?><span class="cart-count-badge"><?= e($navCartCount > 99 ? "99+" : $navCartCount) ?></span><?php endif; ?></a></li>
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

<main class="cart-page">
    <div class="breadcrumb">
        <a href="homepage.php">Home</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Cart</span>
    </div>

    <section class="reminder">
        <i class="fa-solid fa-circle-exclamation"></i>
        <div>
            <h3>Availability may change before payment</h3>
            <p>Add to Cart does not confirm a booking yet. Your vehicle will be checked again before payment and the booking is only submitted after successful payment and admin approval.</p>
        </div>
    </section>

    <?php if(!$cartTableReady): ?>
        <div class="system-box">
            <strong>Cart table not found.</strong><br>
            Please import the `cart_items` table SQL before using the cart page.
        </div>
    <?php elseif(empty($cartItems)): ?>
        <section class="empty-cart">
            <div class="empty-icon"><i class="fa-solid fa-cart-shopping"></i></div>
            <h2>Your cart is empty</h2>
            <p>Start browsing rental cars and add your preferred available car to cart.</p>
            <div class="empty-actions">
                <a class="btn btn-blue" href="catalogue.php"><i class="fa-solid fa-car"></i> Browse Catalogue</a>
                <a class="btn btn-white" href="available_cars.php"><i class="fa-solid fa-magnifying-glass"></i> Search Available Cars</a>
            </div>
        </section>
    <?php else: ?>
        <div class="cart-layout">
            <section class="cart-list">
                <?php foreach($cartItems as $item): ?>
                    <?php $available = !empty($item["current_available"]); ?>
                    <article class="cart-item">
                        <div class="cart-side">
                            <div class="cart-media">
                                <img src="<?= e($item["image_src"]) ?>" alt="<?= e($item["car_name"]) ?>">
                                <span class="status-badge <?= $available ? 'available' : 'unavailable' ?>">
                                    <i class="fa-solid <?= $available ? 'fa-circle-check' : 'fa-circle-xmark' ?>"></i>
                                    <?= $available ? 'Available' : 'Unavailable' ?>
                                </span>
                            </div>

                            <div class="media-actions">
                                <label class="checkout-select-card <?= $available ? '' : 'disabled' ?>">
                                    <input
                                        type="checkbox"
                                        class="checkout-item-toggle"
                                        value="<?= e($item["cart_item_id"]) ?>"
                                        data-name="<?= e($item["car_name"]) ?>"
                                        data-car-subtotal="<?= e((float)($item["computed_subtotal"] ?? 0)) ?>"
                                        data-insurance="<?= e((float)($item["insurance_total"] ?? 0)) ?>"
                                        data-driver="<?= e((float)($item["driver_age_total"] ?? 0)) ?>"
                                        data-addons="<?= e((float)($item["addons_total"] ?? 0)) ?>"
                                        data-fuel="<?= e((float)($item["fuel_total"] ?? 0)) ?>"
                                        data-services="<?= e((float)($item["services_total"] ?? 0)) ?>"
                                        <?= $available ? 'checked' : 'disabled' ?>
                                    >
                                    <span>
                                        Pay this car
                                        <small><?= $available ? 'Selected for checkout' : 'Unavailable for checkout' ?></small>
                                    </span>
                                </label>
                                <a class="btn btn-browse" href="available_cars.php?<?= e($item["trip_query"]) ?>"><i class="fa-solid fa-car-side"></i> Continue Browsing</a>
                                <?php if($available): ?>
                                    <a class="btn btn-white" href="car_details.php?<?= e($item["details_query"]) ?>"><i class="fa-solid fa-circle-info"></i> View Details</a>
                                    <button class="btn btn-trip" type="button" data-trip-modal="tripModal<?= e($item["cart_item_id"]) ?>"><i class="fa-solid fa-pen-to-square"></i> Edit Trip Details</button>
                                    <form class="remove-form" method="POST" action="remove_cart_item.php" onsubmit="return confirm('Remove this car from cart?');">
                                        <input type="hidden" name="cart_item_id" value="<?= e($item["cart_item_id"]) ?>">
                                        <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Remove</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-trip" type="button" data-trip-modal="tripModal<?= e($item["cart_item_id"]) ?>"><i class="fa-solid fa-pen-to-square"></i> Edit Trip Details</button>
                                    <a class="btn btn-blue" href="available_cars.php?<?= e($item["trip_query"]) ?>"><i class="fa-solid fa-magnifying-glass"></i> Search Other Cars</a>
                                    <form class="remove-form" method="POST" action="remove_cart_item.php" onsubmit="return confirm('Remove this unavailable car from cart?');">
                                        <input type="hidden" name="cart_item_id" value="<?= e($item["cart_item_id"]) ?>">
                                        <button class="btn btn-danger" type="submit"><i class="fa-solid fa-trash"></i> Remove</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="cart-content">
                            <div class="item-head">
                                <div>
                                    <h2><?= e($item["car_name"]) ?></h2>
                                    <p><?= e($item["brand"]) ?> • <?= e($item["category_name"]) ?> • <?= e($item["pickup_state_name"]) ?></p>
                                </div>
                                <div class="price-mini"><?= e(money($item["price_per_day"])) ?> / day</div>
                            </div>

                            <div class="info-grid">
                                <div class="info-box"><i class="fa-solid fa-location-dot"></i><div><span>Pickup</span><strong><?= e($item["pickup_location_name"]) ?></strong></div></div>
                                <div class="info-box"><i class="fa-solid fa-flag-checkered"></i><div><span>Drop-off</span><strong><?= e($item["dropoff_location_name"]) ?></strong></div></div>
                                <div class="info-box"><i class="fa-solid fa-calendar-day"></i><div><span>Pickup Date & Time</span><strong><?= e(dateTimeLabel($item["start_datetime"])) ?></strong></div></div>
                                <div class="info-box"><i class="fa-solid fa-calendar-check"></i><div><span>Return Date & Time</span><strong><?= e(dateTimeLabel($item["end_datetime"])) ?></strong></div></div>
                                <div class="info-box"><i class="fa-solid fa-clock"></i><div><span>Rental Days</span><strong><?= e($item["rental_days"]) ?> day(s)</strong></div></div>
                                <div class="info-box"><i class="fa-solid fa-receipt"></i><div><span>Subtotal</span><strong><?= e(money($item["computed_subtotal"])) ?></strong></div></div>
                            </div>

                            <?php if($available): ?>
                                <div class="status-note available"><i class="fa-solid fa-circle-check"></i> This car is still available for your selected rental period.</div>
                            <?php else: ?>
                                <div class="status-note unavailable"><i class="fa-solid fa-triangle-exclamation"></i> This car is no longer available for your selected rental period. Please remove it or search other cars.</div>
                            <?php endif; ?>

                            <?php
                                $itemTripMessage = $tripMessages[(string)$item["cart_item_id"]] ?? "";
                                $itemTripMessageType = $tripMessageTypes[(string)$item["cart_item_id"]] ?? "ok";
                                $itemTripSearchQuery = $tripSearchQueries[(string)$item["cart_item_id"]] ?? $item["trip_query"];
                            ?>
                            <?php if($itemTripMessage !== ""): ?>
                                <div class="trip-message <?= e($itemTripMessageType) ?>">
                                    <div><i class="fa-solid <?= $itemTripMessageType === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i></div>
                                    <div>
                                        <strong><?= $itemTripMessageType === 'ok' ? 'Trip Updated' : 'Trip Not Updated' ?></strong>
                                        <p><?= e($itemTripMessage) ?></p>
                                        <?php if($itemTripMessageType !== 'ok'): ?>
                                            <div class="trip-message-actions">
                                                <button class="btn btn-white" type="button" data-trip-modal="tripModal<?= e($item["cart_item_id"]) ?>"><i class="fa-solid fa-calendar-days"></i> Choose Another Date</button>
                                                <a class="btn btn-blue" href="available_cars.php?<?= e($itemTripSearchQuery) ?>"><i class="fa-solid fa-car-side"></i> Search Similar Cars</a>
                                                <a class="btn btn-keep" href="cart.php"><i class="fa-solid fa-shield-halved"></i> Keep Original Trip</a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <div class="trip-modal" id="tripModal<?= e($item["cart_item_id"]) ?>">
                                <div class="trip-modal-card">
                                    <div class="trip-modal-head">
                                        <div>
                                            <h2>Edit Trip Details</h2>
                                            <p><?= e($item["car_name"]) ?> • Check availability again before saving changes.</p>
                                        </div>
                                        <button class="trip-close" type="button" data-close-trip="tripModal<?= e($item["cart_item_id"]) ?>"><i class="fa-solid fa-xmark"></i></button>
                                    </div>

                                    <form method="POST" action="cart.php" class="trip-edit-form" data-trip-form>
                                        <input type="hidden" name="trip_action" value="check_save">
                                        <input type="hidden" name="cart_item_id" value="<?= e($item["cart_item_id"]) ?>">

                                        <div class="trip-edit-alert" data-trip-change-alert>
                                            <i class="fa-solid fa-circle-info"></i>
                                            <span>Save Changes will only update your cart if this car is available for the new trip. If not available, your original trip remains unchanged.</span>
                                        </div>

                                        <div class="trip-form-grid">
                                            <div class="trip-field full">
                                                <label>Pickup State</label>
                                                <select class="trip-input" name="pickup_state_id" data-trip-state required>
                                                    <option value="">Select State</option>
                                                    <?php foreach($cartStates as $stateOption): ?>
                                                        <option value="<?= e($stateOption["state_id"]) ?>" <?= (int)$item["pickup_state_id"] === (int)$stateOption["state_id"] ? "selected" : "" ?>><?= e($stateOption["state_name"]) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="trip-field">
                                                <label>Pickup Location</label>
                                                <select class="trip-input" name="pickup_location" data-trip-location required>
                                                    <option value="">Select Pickup Location</option>
                                                    <?php foreach($cartLocations as $locationOption): ?>
                                                        <option value="<?= e($locationOption["location_id"]) ?>" data-state="<?= e($locationOption["state_id"]) ?>" <?= (int)$item["pickup_location"] === (int)$locationOption["location_id"] ? "selected" : "" ?>><?= e($locationOption["location_name"]) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="trip-field">
                                                <label>Drop-off Location</label>
                                                <select class="trip-input" name="dropoff_location" data-trip-location required>
                                                    <option value="">Select Drop-off Location</option>
                                                    <?php foreach($cartLocations as $locationOption): ?>
                                                        <option value="<?= e($locationOption["location_id"]) ?>" data-state="<?= e($locationOption["state_id"]) ?>" <?= (int)$item["dropoff_location"] === (int)$locationOption["location_id"] ? "selected" : "" ?>><?= e($locationOption["location_name"]) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="trip-field">
                                                <label>Pickup Date</label>
                                                <input class="trip-input" type="date" name="pickup_date" value="<?= e(dateInputValue($item["start_datetime"])) ?>" min="<?= e(date("Y-m-d")) ?>" required>
                                            </div>

                                            <div class="trip-field">
                                                <label>Pickup Time</label>
                                                <input class="trip-input" type="time" name="pickup_time" value="<?= e(timeInputValue($item["start_datetime"])) ?>" step="300" required>
                                            </div>

                                            <div class="trip-field">
                                                <label>Return Date</label>
                                                <input class="trip-input" type="date" name="return_date" value="<?= e(dateInputValue($item["end_datetime"])) ?>" min="<?= e(date("Y-m-d")) ?>" required>
                                            </div>

                                            <div class="trip-field">
                                                <label>Return Time</label>
                                                <input class="trip-input" type="time" name="return_time" value="<?= e(timeInputValue($item["end_datetime"])) ?>" step="300" required>
                                            </div>
                                        </div>

                                        <div class="trip-modal-actions">
                                            <button class="btn btn-green" type="submit"><i class="fa-solid fa-calendar-check"></i> Save Changes / Check Again</button>
                                            <button class="btn btn-white" type="button" data-reset-trip="tripModal<?= e($item["cart_item_id"]) ?>"><i class="fa-solid fa-rotate-left"></i> Keep Original Trip</button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <?php if($available): ?>
                                <div class="addon-summary-strip">
                                    <div class="addon-summary-title"><i class="fa-solid fa-sparkles"></i> Selected Protection & Add-ons</div>
                                    <div class="addon-chip-row">
                                        <span class="addon-chip"><i class="fa-solid fa-shield-halved"></i> <?= e($item["insurance_name"] ?? "Basic Coverage") ?> · <?= e(money((float)($item["insurance_total"] ?? 0))) ?></span>
                                        <span class="addon-chip"><i class="fa-solid fa-id-card"></i> <?= e($item["driver_age_name"] ?? "25–69 years") ?> · <?= e(money((float)($item["driver_age_total"] ?? 0))) ?></span>
                                        <span class="addon-chip"><i class="fa-solid fa-gas-pump"></i> <?= e($item["fuel_name"] ?? "No Fuel Package") ?> · <?= e(money((float)($item["fuel_total"] ?? 0))) ?></span>
                                        <?php if(!empty($item["selected_service_labels"])): ?>
                                            <?php foreach($item["selected_service_labels"] as $serviceLabel): ?>
                                                <span class="addon-chip"><i class="fa-solid fa-circle-plus"></i> <?= e($serviceLabel) ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="addon-chip"><i class="fa-solid fa-circle-info"></i> No optional add-on selected</span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php
                                    $itemAddons = $item["active_addons"] ?? cleanCartAddons([], $insurancePackages, $addonServices, $fuelPolicies, $driverAgeGroups);
                                    $itemAddonMessage = $addonMessages[(string)$item["cart_item_id"]] ?? "";
                                    $itemAddonMessageType = $addonMessageTypes[(string)$item["cart_item_id"]] ?? "ok";
                                ?>
                                <?php if($itemAddonMessage !== ""): ?>
                                    <div class="addon-message addon-message-above <?= e($itemAddonMessageType) ?>"><?= e($itemAddonMessage) ?></div>
                                <?php endif; ?>

                                <button class="btn btn-blue open-addon-btn" type="button" data-addon-modal="addonModal<?= e($item["cart_item_id"]) ?>">
                                    <i class="fa-solid fa-shield-heart"></i> Extra Protection & Services
                                </button>

                                <div class="addon-modal" id="addonModal<?= e($item["cart_item_id"]) ?>">
                                    <div class="addon-modal-card">
                                        <div class="addon-modal-head">
                                            <div>
                                                <h2>Extra Protection & Services</h2>
                                                <p><?= e($item["car_name"]) ?> • <?= e($item["rental_days"]) ?> day(s). Select add-ons for this vehicle only.</p>
                                            </div>
                                            <button class="addon-close" type="button" data-close-addon="addonModal<?= e($item["cart_item_id"]) ?>"><i class="fa-solid fa-xmark"></i></button>
                                        </div>
<form method="POST" action="cart.php" class="addon-body">
                                        <input type="hidden" name="addon_action" value="save">
                                        <input type="hidden" name="cart_item_id" value="<?= e($item["cart_item_id"]) ?>">

                                        <div class="mini-summary">
                                            Charges are calculated for this vehicle only: <strong><?= e($item["rental_days"]) ?> day(s)</strong>.
                                        </div>

                                        <div class="addon-section">
                                            <h3><i class="fa-solid fa-shield-heart"></i> Insurance Package</h3>
                                            <div class="choice-grid">
                                                <?php foreach($insurancePackages as $key => $package): ?>
                                                    <label class="choice-card">
                                                        <input type="radio" name="insurance_package" value="<?= e($key) ?>" <?= $itemAddons["insurance"] === $key ? "checked" : "" ?>>
                                                        <i class="fa-solid fa-shield-halved choice-icon"></i>
                                                        <span>
                                                            <strong><?= e($package["name"]) ?></strong>
                                                            <small><?= e($package["desc"]) ?></small>
                                                        </span>
                                                        <em class="choice-price"><?= ((float)$package["price"] > 0) ? "+" . e(money($package["price"])) . "/day" : "Free" ?></em>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="addon-section">
                                            <h3><i class="fa-solid fa-id-card"></i> Driver Age Surcharge</h3>
                                            <div class="choice-grid">
                                                <?php foreach($driverAgeGroups as $key => $ageGroup): ?>
                                                    <label class="choice-card">
                                                        <input type="radio" name="driver_age" value="<?= e($key) ?>" <?= ($itemAddons["driver_age"] ?? "normal") === $key ? "checked" : "" ?>>
                                                        <i class="fa-solid fa-user-shield choice-icon"></i>
                                                        <span>
                                                            <strong><?= e($ageGroup["name"]) ?></strong>
                                                            <small><?= e($ageGroup["desc"]) ?></small>
                                                        </span>
                                                        <em class="choice-price"><?= ((float)$ageGroup["price"] > 0) ? "+" . e(money($ageGroup["price"])) . "/day" : "Free" ?></em>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="addon-section">
                                            <h3><i class="fa-solid fa-plus"></i> Add-on Services</h3>
                                            <div class="service-grid">
                                                <?php foreach($addonServices as $key => $service): ?>
                                                    <label class="service-card">
                                                        <input type="checkbox" name="addon_services[]" value="<?= e($key) ?>" <?= in_array($key, $itemAddons["services"], true) ? "checked" : "" ?>>
                                                        <i class="fa-solid <?= e($service["icon"]) ?>"></i>
                                                        <span>
                                                            <strong><?= e($service["name"]) ?></strong>
                                                            <small>+<?= e(money($service["price"])) ?> / <?= e($service["unit"] === "day" ? "day" : "booking") ?></small>
                                                        </span>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="addon-section">
                                            <h3><i class="fa-solid fa-gas-pump"></i> Fuel Option</h3>
                                            <div class="choice-grid">
                                                <?php foreach($fuelPolicies as $key => $policy): ?>
                                                    <label class="choice-card">
                                                        <input type="radio" name="fuel_policy" value="<?= e($key) ?>" <?= $itemAddons["fuel"] === $key ? "checked" : "" ?>>
                                                        <i class="fa-solid fa-gas-pump choice-icon"></i>
                                                        <span>
                                                            <strong><?= e($policy["name"]) ?></strong>
                                                            <small><?= e($policy["desc"]) ?></small>
                                                            <?php if($key === "prepaid_fuel"): ?>
                                                                <span class="fuel-breakdown">
                                                                    <span class="fuel-breakdown-row">
                                                                        <strong><?= e($item["category_name"] ?? "Others") ?></strong>
                                                                        <em><?= (float)($item["fuel_charge_preview"] ?? 0) > 0 ? e(money((float)$item["fuel_charge_preview"])) : "Not Available" ?></em>
                                                                    </span>
                                                                </span>
                                                            <?php endif; ?>
                                                        </span>
                                                        <em class="choice-price"><?= $key === "prepaid_fuel" ? "+" . e(money((float)($item["fuel_charge_preview"] ?? 0))) : "Free" ?></em>
                                                    </label>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>

                                        <div class="addon-actions">
                                            <button class="btn btn-blue" type="submit"><i class="fa-solid fa-rotate"></i> Update Services</button>

                                        </div>
                                    </form>
                                    </div>
                                </div>
                            <?php endif; ?>

                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <aside class="summary-card">
                <div class="summary-mini-stats">
                    <div class="summary-mini-stat"><strong><?= e($totals["total"]) ?></strong><span>Total Cars</span></div>
                    <div class="summary-mini-stat"><strong><?= e($totals["available"]) ?></strong><span>Available</span></div>
                    <div class="summary-mini-stat"><strong><?= e($totals["unavailable"]) ?></strong><span>Unavailable</span></div>
                </div>
                <h2>Order Summary</h2>
                <div class="summary-row"><span><i class="fa-solid fa-receipt"></i> Car Rental</span><strong id="summaryCarRental"><?= e(money($totals["car_subtotal"])) ?></strong></div>

                <?php foreach($cartItems as $summaryItem): ?>
                    <?php if(empty($summaryItem["current_available"])) continue; ?>
                    <div class="summary-detail car-service-summary" data-summary-cart-id="<?= e($summaryItem["cart_item_id"]) ?>">
                        <div class="summary-detail-title"><i class="fa-solid fa-car"></i> <?= e($summaryItem["car_name"] ?? "Selected Car") ?></div>

                        <div class="summary-detail-list car-rental-only-list">
                            <div class="summary-detail-item"><span>Car price per day</span><strong><?= e(money((float)($summaryItem["price_per_day"] ?? 0))) ?></strong></div>
                            <div class="summary-detail-item"><span>Rental duration</span><strong><?= e((int)($summaryItem["rental_days"] ?? 0)) ?> day(s)</strong></div>
                            <div class="summary-detail-item"><span>Total calculation</span><strong><?= e(money((float)($summaryItem["price_per_day"] ?? 0))) ?> × <?= e((int)($summaryItem["rental_days"] ?? 0)) ?> day(s)</strong></div>
                            <div class="summary-detail-item summary-rental-price"><span>Rental Price</span><strong><?= e(money((float)($summaryItem["computed_subtotal"] ?? 0))) ?></strong></div>
                        </div>

                        <div class="summary-car-divider"></div>

                        <div class="summary-detail-title service-title"><i class="fa-solid fa-list-check"></i> Selected service breakdown</div>
                        <div class="summary-detail-list">
                            <div class="summary-detail-item"><span>Insurance Package</span><strong><?= e(money((float)($summaryItem["insurance_total"] ?? 0))) ?></strong></div>
                            <div class="summary-detail-item"><span>Driver Age Surcharge</span><strong><?= e(money((float)($summaryItem["driver_age_total"] ?? 0))) ?></strong></div>
                            <div class="summary-detail-item"><span>Add-on Services</span><strong><?= e(money((float)($summaryItem["addons_total"] ?? 0))) ?></strong></div>
                            <div class="summary-detail-item"><span>Fuel Option</span><strong><?= e(money((float)($summaryItem["fuel_total"] ?? 0))) ?></strong></div>
                            <div class="summary-detail-item summary-extra-total"><span>Total Extra Protection & Services</span><strong><?= e(money((float)($summaryItem["services_total"] ?? 0))) ?></strong></div>
                        </div>
                    </div>
                <?php endforeach; ?>


                <div class="summary-row"><span><i class="fa-solid fa-shield-heart"></i> Extra Protection & Services</span><strong id="summaryServices"><?= e(money($totals["services_total"])) ?></strong></div>
                <div class="summary-row"><span><i class="fa-solid fa-calculator"></i> Subtotal</span><strong id="summarySubtotal"><?= e(money($totals["subtotal"])) ?></strong></div>
                <div class="summary-row"><span><i class="fa-solid fa-ticket"></i> Voucher Discount</span><strong id="summaryDiscount"><?= e(money($totals["discount"])) ?></strong></div>

                <div class="voucher-box">
                    <label>Have a voucher?</label>
                    <form method="POST" action="cart.php" class="voucher-row">
                        <input type="hidden" name="voucher_action" value="apply">
                        <input type="text" name="voucher_code" placeholder="NEWUSER5" value="<?= e($activeVoucher["promo_code"] ?? "") ?>">
                        <button class="btn btn-white" type="submit">Apply</button>
                    </form>

                    <?php if($activeVoucher): ?>
                        <div class="applied-voucher">
                            <span><strong><?= e($activeVoucher["promo_code"]) ?></strong> - <?= e((float)$activeVoucher["discount_percent"]) ?>% discount</span>
                            <form method="POST" action="cart.php">
                                <input type="hidden" name="voucher_action" value="remove">
                                <button class="voucher-remove" type="submit">Remove</button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if($voucherMessage !== ""): ?>
                        <div class="voucher-message <?= e($voucherMessageType) ?>"><?= e($voucherMessage) ?></div>
                    <?php endif; ?>
                </div>

                <div class="summary-row grand"><span>Grand Total</span><strong id="summaryGrand"><?= e(money($totals["grand"])) ?></strong></div>

                <?php if($canProceed): ?>
                    <div class="summary-ok"><i class="fa-solid fa-circle-check"></i> Select one or more available cars, then proceed to payment.</div>
                    <?php if($checkoutMessage !== ""): ?>
                        <div class="checkout-message"><i class="fa-solid fa-circle-exclamation"></i> <?= e($checkoutMessage) ?></div>
                    <?php endif; ?>
                    <div class="checkout-empty-warning" id="checkoutEmptyWarning"><i class="fa-solid fa-circle-exclamation"></i> Please select at least one available car before payment.</div>
                    <form class="checkout-action-form" method="POST" action="cart.php" id="checkoutForm">
                        <input type="hidden" name="checkout_action" value="select_items">
                        <div id="checkoutHiddenInputs"></div>
                        <button class="btn btn-blue" type="submit"><i class="fa-solid fa-credit-card"></i> Proceed to Payment</button>
                    </form>
                <?php else: ?>
                    <div class="summary-warning"><i class="fa-solid fa-triangle-exclamation"></i> Some cars are no longer available. Please remove unavailable items before payment.</div>
                    <span class="btn btn-blue btn-disabled"><i class="fa-solid fa-lock"></i> Proceed to Payment</span>
                <?php endif; ?>
            </aside>
        </div>
    <?php endif; ?>
</main>

<footer class="footer">
    <div class="footer-inner">
        <div>
            <h3>KH Car Rental</h3>
            <p>KH Car Rental provides reliable, affordable and convenient car rental services across Johor, Melaka and Kuala Lumpur. Customers can search available cars, compare vehicles and manage bookings easily through our online system.</p>
            <a href="catalogue.php" class="start-btn"><i class="fa-solid fa-car-side"></i> START Browse</a>
        </div>

        <div>
            <h3>Quick Links</h3>
            <p><a href="homepage.php" class="footer-hover-link">HOME</a></p>
            <p><a href="catalogue.php" class="footer-hover-link">CATALOGUE</a></p>
            <p><a href="find_car_smart.php" class="footer-hover-link">FIND CAR SMART</a></p>
            <p><a href="compare_car.php" class="footer-hover-link">COMPARE CAR</a></p>
            <p><a href="aboutus.php" class="footer-hover-link">ABOUT US</a></p>
            <p><a href="contactus.php" class="footer-hover-link">CONTACT US</a></p>
            <p><a href="cart.php" class="footer-hover-link">CART</a></p>
        </div>

        <div>
            <h3>Contact</h3>
            <p><a href="tel:+60123456789" class="contact-link"><i class="fa-solid fa-phone"></i> +60 12-345 6789</a></p>
            <p><a href="mailto:hoomenghui@student.mmu.edu.my" class="contact-link"><i class="fa-solid fa-envelope"></i> hoomenghui@student.mmu.edu.my</a></p>
            <p><a href="mailto:pangkanghorng@student.mmu.edu.my" class="contact-link"><i class="fa-solid fa-envelope"></i> pangkanghorng@student.mmu.edu.my</a></p>
            <p><a href="mailto:ngmengxin@student.mmu.edu.my" class="contact-link"><i class="fa-solid fa-envelope"></i> ngmengxin@student.mmu.edu.my</a></p>
            <p><a href="https://maps.google.com/?q=Multimedia+University+Melaka" target="_blank" class="contact-link"><i class="fa-solid fa-location-dot"></i> Multimedia University, Melaka</a></p>
        </div>
    </div>
    <div class="footer-bottom">© 2026 KH Car Rental. All rights reserved.</div>
</footer>

<button class="back-top" type="button" id="backTop"><i class="fa-solid fa-arrow-up"></i></button>

<script>
const avatarBtn = document.getElementById("avatarBtn");
const profileDropdown = document.getElementById("profileDropdown");
if(avatarBtn && profileDropdown){
    avatarBtn.addEventListener("click", (event)=>{
        event.stopPropagation();
        profileDropdown.classList.toggle("show");
    });
    document.addEventListener("click", ()=>profileDropdown.classList.remove("show"));
}

const backTop = document.getElementById("backTop");
if(backTop){
    backTop.addEventListener("click", ()=>window.scrollTo({top:0,behavior:"smooth"}));
}

const checkoutToggles = document.querySelectorAll(".checkout-item-toggle");
const checkoutForm = document.getElementById("checkoutForm");
const checkoutHiddenInputs = document.getElementById("checkoutHiddenInputs");
const checkoutEmptyWarning = document.getElementById("checkoutEmptyWarning");
const discountPercent = <?= e((float)($activeVoucher["discount_percent"] ?? 0)) ?>;
const moneyFormatter = new Intl.NumberFormat("en-MY", { minimumFractionDigits: 2, maximumFractionDigits: 2 });

function formatMoney(value){
    return "RM " + moneyFormatter.format(Math.max(0, Number(value) || 0));
}

function selectedCheckoutItems(){
    return Array.from(checkoutToggles).filter(toggle => toggle.checked && !toggle.disabled);
}

function updateCheckoutSummary(){
    const selected = selectedCheckoutItems();
    let carRental = 0;
    let services = 0;

    selected.forEach(toggle => {
        carRental += Number(toggle.dataset.carSubtotal || 0);
        services += Number(toggle.dataset.services || 0);
    });

    document.querySelectorAll("[data-summary-cart-id]").forEach(row => {
        const rowId = row.dataset.summaryCartId;
        row.style.display = selected.some(toggle => toggle.value === rowId) ? "" : "none";
    });

    const subtotal = carRental + services;
    const discount = subtotal * (discountPercent / 100);
    const grand = Math.max(0, subtotal - discount);

    const carEl = document.getElementById("summaryCarRental");
    const servicesEl = document.getElementById("summaryServices");
    const subtotalEl = document.getElementById("summarySubtotal");
    const discountEl = document.getElementById("summaryDiscount");
    const grandEl = document.getElementById("summaryGrand");

    if(carEl) carEl.textContent = formatMoney(carRental);
    if(servicesEl) servicesEl.textContent = formatMoney(services);
    if(subtotalEl) subtotalEl.textContent = formatMoney(subtotal);
    if(discountEl) discountEl.textContent = formatMoney(discount);
    if(grandEl) grandEl.textContent = formatMoney(grand);

    if(checkoutEmptyWarning){
        checkoutEmptyWarning.classList.toggle("show", selected.length === 0);
    }
}

checkoutToggles.forEach(toggle => {
    toggle.addEventListener("change", updateCheckoutSummary);
});

if(checkoutForm){
    checkoutForm.addEventListener("submit", event => {
        const selected = selectedCheckoutItems();
        if(checkoutHiddenInputs){
            checkoutHiddenInputs.innerHTML = "";
            selected.forEach(toggle => {
                const input = document.createElement("input");
                input.type = "hidden";
                input.name = "checkout_item_ids[]";
                input.value = toggle.value;
                checkoutHiddenInputs.appendChild(input);
            });
        }

        if(selected.length === 0){
            event.preventDefault();
            if(checkoutEmptyWarning){
                checkoutEmptyWarning.classList.add("show");
                checkoutEmptyWarning.scrollIntoView({behavior:"smooth", block:"center"});
            }
        }
    });
}

updateCheckoutSummary();
</script>

<script>
function initTripOriginalValues(form){
    if(!form || form.dataset.originalReady === "1") return;

    form.querySelectorAll("[name='pickup_state_id'], [name='pickup_location'], [name='dropoff_location'], [name='pickup_date'], [name='pickup_time'], [name='return_date'], [name='return_time']").forEach(function(field){
        field.dataset.originalValue = field.value || "";
    });

    form.dataset.originalReady = "1";
}

function tripFormHasChanges(form){
    if(!form) return false;
    initTripOriginalValues(form);

    return Array.from(form.querySelectorAll("[name='pickup_state_id'], [name='pickup_location'], [name='dropoff_location'], [name='pickup_date'], [name='pickup_time'], [name='return_date'], [name='return_time']")).some(function(field){
        return (field.value || "") !== (field.dataset.originalValue || "");
    });
}

function updateTripChangeAlert(form){
    if(!form) return;
    const alertBox = form.querySelector("[data-trip-change-alert]");
    if(!alertBox) return;

    if(tripFormHasChanges(form)){
        alertBox.classList.add("show");
    }else{
        alertBox.classList.remove("show");
    }
}

function resetTripFormToOriginal(form){
    if(!form) return;
    initTripOriginalValues(form);

    form.querySelectorAll("[name='pickup_state_id'], [name='pickup_location'], [name='dropoff_location'], [name='pickup_date'], [name='pickup_time'], [name='return_date'], [name='return_time']").forEach(function(field){
        field.value = field.dataset.originalValue || "";
    });

    updateTripChangeAlert(form);
}

document.addEventListener("click", function(e){
    const openBtn = e.target.closest("[data-addon-modal]");
    if(openBtn){
        const modal = document.getElementById(openBtn.dataset.addonModal);
        if(modal) modal.classList.add("show");
    }

    const closeBtn = e.target.closest("[data-close-addon]");
    if(closeBtn){
        const modal = document.getElementById(closeBtn.dataset.closeAddon);
        if(modal) modal.classList.remove("show");
    }

    if(e.target.classList && e.target.classList.contains("addon-modal")){
        e.target.classList.remove("show");
    }
});

document.addEventListener("keydown", function(e){
    if(e.key === "Escape"){
        document.querySelectorAll(".addon-modal.show").forEach(function(modal){
            modal.classList.remove("show");
        });
    }
});
</script>

<script>
function syncTripLocationOptions(modal){
    if(!modal) return;
    const stateSelect = modal.querySelector("[data-trip-state]");
    if(!stateSelect) return;
    const stateValue = stateSelect.value;
    modal.querySelectorAll("select[data-trip-location]").forEach(function(select){
        let selectedStillVisible = false;
        Array.from(select.options).forEach(function(option){
            if(option.value === ""){
                option.hidden = false;
                return;
            }
            const matches = !stateValue || option.dataset.state === stateValue;
            option.hidden = !matches;
            if(option.selected && matches) selectedStillVisible = true;
        });
        if(!selectedStillVisible){
            const firstAvailable = Array.from(select.options).find(function(option){
                return option.value !== "" && !option.hidden;
            });
            select.value = firstAvailable ? firstAvailable.value : "";
        }
    });
}

document.addEventListener("click", function(e){
    const openTripBtn = e.target.closest("[data-trip-modal]");
    if(openTripBtn){
        const modal = document.getElementById(openTripBtn.dataset.tripModal);
        if(modal){
            const form = modal.querySelector("[data-trip-form]");
            if(form) initTripOriginalValues(form);
            modal.classList.add("show");
            syncTripLocationOptions(modal);
            if(form) updateTripChangeAlert(form);
        }
    }

    const resetTripBtn = e.target.closest("[data-reset-trip]");
    if(resetTripBtn){
        const modal = document.getElementById(resetTripBtn.dataset.resetTrip);
        if(modal){
            const form = modal.querySelector("[data-trip-form]");
            if(form){
                resetTripFormToOriginal(form);
            }
            syncTripLocationOptions(modal);
            if(form) updateTripChangeAlert(form);
            modal.classList.add("show");
        }
    }

    const closeTripBtn = e.target.closest("[data-close-trip]");
    if(closeTripBtn){
        const modal = document.getElementById(closeTripBtn.dataset.closeTrip);
        if(modal) modal.classList.remove("show");
    }

    if(e.target.classList && e.target.classList.contains("trip-modal")){
        e.target.classList.remove("show");
    }
});

document.addEventListener("change", function(e){
    const form = e.target.closest("[data-trip-form]");
    if(e.target.matches("[data-trip-state]")){
        syncTripLocationOptions(e.target.closest(".trip-modal"));
    }
    if(form) updateTripChangeAlert(form);
});

document.addEventListener("input", function(e){
    const form = e.target.closest("[data-trip-form]");
    if(form) updateTripChangeAlert(form);
});

document.addEventListener("DOMContentLoaded", function(){
    document.querySelectorAll(".trip-modal").forEach(function(modal){
        const form = modal.querySelector("[data-trip-form]");
        if(form) initTripOriginalValues(form);
        syncTripLocationOptions(modal);
        if(form) updateTripChangeAlert(form);
    });
});

document.addEventListener("keydown", function(e){
    if(e.key === "Escape"){
        document.querySelectorAll(".trip-modal.show").forEach(function(modal){
            modal.classList.remove("show");
        });
    }
});
</script>

</body>
</html>
