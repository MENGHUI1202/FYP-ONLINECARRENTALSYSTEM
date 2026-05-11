<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];
$jpjTable = "jpj_requests";

function tableExists($conn, $table)
{
    $table = $conn->real_escape_string($table);
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

function columnExists($conn, $table, $column)
{
    $table = $conn->real_escape_string($table);
    $column = $conn->real_escape_string($column);
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result && $result->num_rows > 0;
}

function getFirstExistingTable($conn, $tables)
{
    foreach ($tables as $table) {
        if (tableExists($conn, $table)) {
            return $table;
        }
    }
    return null;
}

function getFirstExistingColumn($conn, $table, $columns)
{
    if (!$table) return null;

    foreach ($columns as $column) {
        if (columnExists($conn, $table, $column)) {
            return $column;
        }
    }

    return null;
}

function getCcCategory($engine_cc)
{
    $cc = (int)filter_var($engine_cc, FILTER_SANITIZE_NUMBER_INT);

    if ($cc <= 1600) {
        return "Below 1600cc";
    } elseif ($cc <= 2000) {
        return "1601cc - 2000cc";
    } else {
        return "2001cc - 3000cc";
    }
}

function calculateRoadTax($engine_cc)
{
    $cc = (int)filter_var($engine_cc, FILTER_SANITIZE_NUMBER_INT);

    if ($cc <= 1600) {
        return 90.00;
    } elseif ($cc <= 2000) {
        return 280.00;
    } elseif ($cc <= 2500) {
        return 880.00;
    } else {
        return 1630.00;
    }
}

function formatMoney($amount)
{
    return "RM " . number_format((float)$amount, 2);
}

$userTable = getFirstExistingTable($conn, ['users', 'user', 'customers', 'customer']);
$username = $_SESSION['username'] ?? "User";
$email = $_SESSION['email'] ?? "";

if ($userTable) {
    $userIdColumn = getFirstExistingColumn($conn, $userTable, ['user_id', 'id', 'customer_id']);
    $nameColumn = getFirstExistingColumn($conn, $userTable, ['username', 'name', 'full_name', 'user_name']);
    $emailColumn = getFirstExistingColumn($conn, $userTable, ['email', 'user_email']);

    if ($userIdColumn) {
        $stmt = $conn->prepare("SELECT * FROM `$userTable` WHERE `$userIdColumn` = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $userResult = $stmt->get_result();
        $userData = $userResult->fetch_assoc();

        if ($userData) {
            if ($nameColumn && !empty($userData[$nameColumn])) {
                $username = $userData[$nameColumn];
            }

            if ($emailColumn && !empty($userData[$emailColumn])) {
                $email = $userData[$emailColumn];
            }
        }
    }
}

$carOptions = [
    "Toyota Vios" => [
        "1.5J" => "1500cc",
        "1.5E" => "1500cc",
        "1.5G" => "1500cc",
        "1.5G Limited" => "1500cc",
        "GR-S" => "1500cc"
    ],
    "Toyota Yaris" => [
        "1.5E" => "1500cc",
        "1.5G" => "1500cc",
        "GR-S" => "1500cc"
    ],
    "Toyota Corolla Altis" => [
        "1.8E" => "1800cc",
        "1.8G" => "1800cc"
    ],
    "Toyota Corolla Cross" => [
        "1.8G" => "1800cc",
        "1.8V" => "1800cc",
        "Hybrid Electric" => "1800cc",
        "GR Sport" => "1800cc"
    ],
    "Toyota Camry" => [
        "2.5V" => "2500cc",
        "2.5 Hybrid" => "2500cc",
        "2.5 Luxury" => "2500cc"
    ],
    "Toyota Hilux" => [
        "Single Cab 2.4 MT 4x4" => "2400cc",
        "2.4E MT 4x4" => "2400cc",
        "2.4E AT 4x4" => "2400cc",
        "2.4V AT 4x4" => "2400cc",
        "2.8 Rogue AT 4x4" => "2800cc",
        "GR Sport 2.8 AT 4x4" => "2800cc"
    ],
    "Toyota Fortuner" => [
        "2.4 AT 4x4" => "2400cc",
        "2.7 SRZ AT 4x4" => "2700cc",
        "2.8 VRZ AT 4x4" => "2800cc"
    ],
    "Toyota Innova" => [
        "2.0E" => "2000cc",
        "2.0G" => "2000cc",
        "2.0X" => "2000cc"
    ],
    "Toyota Innova Zenix" => [
        "2.0V" => "2000cc",
        "2.0 Hybrid" => "2000cc"
    ],
    "Toyota Rush" => [
        "1.5G" => "1500cc",
        "1.5S" => "1500cc"
    ],
    "Toyota Harrier" => [
        "2.0 Luxury" => "2000cc",
        "2.0 Premium" => "2000cc"
    ],
    "Toyota RAV4" => [
        "2.0L" => "2000cc",
        "2.5L" => "2500cc"
    ],
    "Toyota Alphard" => [
        "2.5 X" => "2500cc",
        "2.5 G" => "2500cc",
        "2.5 Executive Lounge" => "2500cc"
    ],
    "Toyota Vellfire" => [
        "2.5" => "2500cc",
        "2.5 ZG" => "2500cc"
    ],
    "Toyota Hiace" => [
        "2.5 Panel Van" => "2500cc",
        "2.8 Panel Van" => "2800cc"
    ],
    "Toyota GR86" => [
        "2.4 Manual" => "2400cc",
        "2.4 Automatic" => "2400cc"
    ],
    "Toyota GR Supra" => [
        "3.0 AT" => "3000cc"
    ]
];

$platePrefixes = ["MCA", "VLT", "WXY", "JPN", "BMM", "TYT", "JPJ", "WYY", "VAB", "WRC", "MDS", "JRM"];

if (!isset($_SESSION['jpj_plate_prefix'])) {
    $_SESSION['jpj_plate_prefix'] = $platePrefixes[array_rand($platePrefixes)];
}

$randomPlatePrefix = $_SESSION['jpj_plate_prefix'];

$insurancePlans = [
    "Below 1600cc" => [
        "Basic Protection" => [
            "price" => 1450.00,
            "desc" => "Basic yearly protection for small engine Toyota models."
        ],
        "Standard Protection" => [
            "price" => 1850.00,
            "desc" => "Balanced insurance package for normal daily driving."
        ],
        "Premium Protection" => [
            "price" => 2380.00,
            "desc" => "Higher coverage package with better protection benefits."
        ]
    ],
    "1601cc - 2000cc" => [
        "Basic Protection" => [
            "price" => 1750.00,
            "desc" => "Basic yearly protection for medium engine Toyota models."
        ],
        "Standard Protection" => [
            "price" => 2250.00,
            "desc" => "Balanced insurance package for 1.8L to 2.0L vehicles."
        ],
        "Premium Protection" => [
            "price" => 2880.00,
            "desc" => "Higher coverage package for better protection."
        ]
    ],
    "2001cc - 3000cc" => [
        "Basic Protection" => [
            "price" => 2450.00,
            "desc" => "Basic yearly protection for higher engine capacity vehicles."
        ],
        "Standard Protection" => [
            "price" => 3180.00,
            "desc" => "Balanced insurance package for large Toyota models."
        ],
        "Premium Protection" => [
            "price" => 3880.00,
            "desc" => "Premium coverage package for higher value vehicles."
        ]
    ]
];

$successMessage = "";
$errorMessage = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $car_model = trim($_POST['car_model'] ?? "");
    $car_variant = trim($_POST['car_variant'] ?? "");
    $plate_prefix = trim($_POST['plate_prefix'] ?? "");
    $plate_digit_1 = trim($_POST['plate_digit_1'] ?? "");
    $plate_digit_2 = trim($_POST['plate_digit_2'] ?? "");
    $plate_digit_3 = trim($_POST['plate_digit_3'] ?? "");
    $plate_digit_4 = trim($_POST['plate_digit_4'] ?? "");
    $insurance_plan = trim($_POST['insurance_plan'] ?? "");

    $plateDigits = $plate_digit_1 . $plate_digit_2 . $plate_digit_3 . $plate_digit_4;
    $plate_number = $plate_prefix . " " . $plateDigits;

    if (!tableExists($conn, $jpjTable)) {
        $errorMessage = "JPJ request table does not exist. Please create jpj_requests table in phpMyAdmin first.";
    } elseif (
        $car_model === "" ||
        $car_variant === "" ||
        $plate_prefix === "" ||
        $plate_digit_1 === "" ||
        $plate_digit_2 === "" ||
        $plate_digit_3 === "" ||
        $insurance_plan === ""
    ) {
        $errorMessage = "Please complete car, number plate and insurance fields.";
    } elseif (!isset($carOptions[$car_model][$car_variant])) {
        $errorMessage = "Invalid car model or variant selected.";
    } elseif (!in_array($plate_prefix, $platePrefixes)) {
        $errorMessage = "Invalid plate prefix selected.";
    } elseif (
        !ctype_digit($plate_digit_1) ||
        !ctype_digit($plate_digit_2) ||
        !ctype_digit($plate_digit_3) ||
        ($plate_digit_4 !== "" && !ctype_digit($plate_digit_4))
    ) {
        $errorMessage = "Invalid plate number selected.";
    } else {
        $engine_cc = $carOptions[$car_model][$car_variant];
        $ccCategory = getCcCategory($engine_cc);

        if (!isset($insurancePlans[$ccCategory][$insurance_plan])) {
            $errorMessage = "Invalid insurance plan selected.";
        } else {
            $insurance_price = $insurancePlans[$ccCategory][$insurance_plan]['price'];
            $road_tax = calculateRoadTax($engine_cc);
            $registration_fee = 300.00;
            $service_fee = 150.00;
            $total_amount = $insurance_price + $road_tax + $registration_fee + $service_fee;
            $payment_method = "Not Required";
            $payment_status = "Pending Approval";

            $stmt = $conn->prepare("
                INSERT INTO `$jpjTable`
                (user_id, username, email, car_model, car_variant, plate_prefix, plate_number, engine_cc, insurance_plan, insurance_price, road_tax, registration_fee, service_fee, total_amount, payment_method, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->bind_param(
                "issssssssddddsss",
                $user_id,
                $username,
                $email,
                $car_model,
                $car_variant,
                $plate_prefix,
                $plate_number,
                $engine_cc,
                $insurance_plan,
                $insurance_price,
                $road_tax,
                $registration_fee,
                $service_fee,
                $total_amount,
                $payment_method,
                $payment_status
            );

            if ($stmt->execute()) {
                $_SESSION['jpj_plate_prefix'] = $platePrefixes[array_rand($platePrefixes)];
                $successMessage = "Your JPJ number plate and insurance request has been submitted successfully. Please wait for admin approval.";
            } else {
                $errorMessage = "Failed to submit JPJ request. Please try again.";
            }
        }
    }
}

$applications = [];
$latestApplication = null;

if (tableExists($conn, $jpjTable)) {
    $stmt = $conn->prepare("
        SELECT *
        FROM `$jpjTable`
        WHERE user_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $applicationResult = $stmt->get_result();

    while ($row = $applicationResult->fetch_assoc()) {
        $applications[] = $row;
    }

    $latestApplication = $applications[0] ?? null;
}

$latestStatus = $latestApplication['payment_status'] ?? "Not Submitted";
$statusClass = "status-pending";

if ($latestStatus === "Approved") {
    $statusClass = "status-approved";
} elseif ($latestStatus === "Rejected") {
    $statusClass = "status-rejected";
} elseif ($latestStatus === "Not Submitted") {
    $statusClass = "status-none";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>JPJ Registration | Toyota Car Selling</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --red: #ed1c24;
            --dark-red: #b80f18;
            --black: #111111;
            --dark: #1b1b1b;
            --white: #ffffff;
            --light: #f7f7f7;
            --soft-red: #fff1f2;
            --gray: #777777;
            --border: #e5e5e5;
            --green: #16a34a;
            --orange: #f59e0b;
            --danger: #dc2626;
            --shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
            --radius: 26px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        body {
            background:
                radial-gradient(circle at top left, rgba(237, 28, 36, 0.12), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #f5f5f5 100%);
            color: var(--black);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .page-wrapper {
            max-width: 1250px;
            margin: 0 auto;
            padding: 34px 28px 70px;
        }

        .top-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 18px;
            margin-bottom: 26px;
        }

        .brand {
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 0.5px;
        }

        .brand span {
            color: var(--red);
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--black);
            color: var(--white);
            padding: 12px 20px;
            border-radius: 999px;
            font-size: 14px;
            font-weight: 900;
            transition: 0.25s;
        }

        .back-btn:hover {
            background: var(--red);
            transform: translateY(-2px);
        }

        .hero {
            background:
                linear-gradient(135deg, rgba(17, 17, 17, 0.96), rgba(39, 39, 39, 0.96)),
                radial-gradient(circle at top right, rgba(237, 28, 36, 0.35), transparent 35%);
            color: var(--white);
            border-radius: 34px;
            padding: 45px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
            margin-bottom: 28px;
        }

        .hero::after {
            content: "";
            position: absolute;
            width: 360px;
            height: 360px;
            border-radius: 50%;
            background: rgba(237, 28, 36, 0.15);
            right: -120px;
            top: -130px;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 820px;
        }

        .breadcrumb {
            font-size: 14px;
            color: #dddddd;
            margin-bottom: 18px;
        }

        .breadcrumb span {
            color: var(--red);
            font-weight: 900;
        }

        .hero h1 {
            font-size: 42px;
            line-height: 1.15;
            margin-bottom: 14px;
            font-weight: 900;
        }

        .hero h1 span {
            color: var(--red);
        }

        .hero p {
            color: #dddddd;
            line-height: 1.8;
            font-size: 16px;
        }

        .welcome-card {
            background: var(--white);
            border: 1px solid var(--border);
            box-shadow: var(--shadow);
            border-radius: 28px;
            padding: 26px 30px;
            display: flex;
            justify-content: space-between;
            gap: 20px;
            align-items: center;
            margin-bottom: 28px;
        }

        .welcome-left h2 {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .welcome-left h2 span {
            color: var(--red);
        }

        .welcome-left p {
            color: var(--gray);
            font-size: 15px;
            line-height: 1.7;
        }

        .welcome-status {
            text-align: right;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 10px 17px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
            margin-bottom: 8px;
            white-space: nowrap;
        }

        .status-pending {
            background: #fff4df;
            color: #9a5b00;
        }

        .status-approved {
            background: #e8f8ee;
            color: #166534;
        }

        .status-rejected {
            background: #ffe8e8;
            color: #b91c1c;
        }

        .status-none {
            background: #eeeeee;
            color: #555555;
        }

        .status-sub {
            font-size: 13px;
            color: var(--gray);
        }

        .top-status-grid {
            display: grid;
            grid-template-columns: 1.4fr 1fr 1fr;
            gap: 22px;
            margin-bottom: 28px;
        }

        .status-card {
            background: var(--white);
            padding: 26px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
        }

        .status-card.dark {
            background: linear-gradient(135deg, var(--black), #2b2b2b);
            color: var(--white);
            border: none;
        }

        .status-card h3 {
            font-size: 16px;
            color: var(--gray);
            margin-bottom: 12px;
        }

        .status-card.dark h3 {
            color: #cccccc;
        }

        .status-card .big {
            font-size: 30px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .status-card .small {
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
        }

        .status-card.dark .small {
            color: #dddddd;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1.1fr 0.9fr;
            gap: 28px;
            align-items: start;
        }

        .panel {
            background: var(--white);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .panel-header {
            padding: 25px 28px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .panel-header h2 {
            font-size: 24px;
            font-weight: 900;
        }

        .panel-header span {
            color: var(--red);
        }

        .panel-header p {
            margin-top: 7px;
            color: var(--gray);
            font-size: 14px;
            line-height: 1.6;
        }

        .step-tag {
            background: var(--black);
            color: var(--white);
            padding: 9px 15px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .panel-body {
            padding: 28px;
        }

        .form-group {
            margin-bottom: 22px;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        label {
            display: block;
            font-size: 14px;
            font-weight: 900;
            margin-bottom: 9px;
        }

        select,
        input {
            width: 100%;
            padding: 14px 15px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: #fafafa;
            font-size: 15px;
            outline: none;
            transition: 0.3s;
        }

        select:focus,
        input:focus {
            border-color: var(--red);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(237, 28, 36, 0.08);
        }

        .helper-text {
            font-size: 13px;
            color: var(--gray);
            margin-top: 7px;
            line-height: 1.5;
        }

        .plate-builder {
            background: #fafafa;
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 20px;
        }

        .plate-preview {
            background: var(--black);
            color: var(--white);
            border: 2px solid var(--red);
            border-radius: 16px;
            padding: 18px;
            text-align: center;
            font-size: 28px;
            font-weight: 900;
            letter-spacing: 3px;
            margin-bottom: 18px;
            box-shadow: 0 12px 28px rgba(0, 0, 0, 0.18);
        }

        .plate-control-row {
            display: grid;
            grid-template-columns: 1.2fr 1fr 1fr 1fr 1fr;
            gap: 12px;
        }

        .plate-locked {
            width: 100%;
            padding: 14px 15px;
            border-radius: 14px;
            border: 1px solid var(--border);
            background: #efefef;
            font-size: 15px;
            font-weight: 900;
            display: flex;
            justify-content: center;
            align-items: center;
            color: var(--black);
        }

        .plate-control-row select {
            text-align: center;
            font-weight: 900;
        }

        .insurance-list {
            display: grid;
            grid-template-columns: 1fr;
            gap: 14px;
        }

        .insurance-option input {
            display: none;
        }

        .insurance-card {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            padding: 18px;
            border: 1px solid var(--border);
            border-radius: 18px;
            cursor: pointer;
            transition: 0.3s;
            background: #fafafa;
        }

        .insurance-option input:checked + .insurance-card {
            border-color: var(--red);
            background: #fff5f7;
            box-shadow: 0 12px 28px rgba(237, 28, 36, 0.10);
        }

        .insurance-card h4 {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .insurance-card p {
            color: var(--gray);
            font-size: 13px;
            line-height: 1.5;
        }

        .insurance-price {
            font-size: 18px;
            font-weight: 900;
            color: var(--red);
            white-space: nowrap;
        }

        .insurance-note {
            background: #fff5f5;
            border: 1px solid #ffd1d1;
            color: #7f1d1d;
            border-radius: 16px;
            padding: 15px;
            font-size: 13px;
            line-height: 1.6;
            margin-bottom: 14px;
        }

        .summary-box {
            background: linear-gradient(135deg, var(--black), #202020);
            color: var(--white);
            padding: 28px;
            border-radius: var(--radius);
            position: sticky;
            top: 30px;
            overflow: hidden;
        }

        .summary-box h2 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .summary-box .summary-sub {
            color: #cccccc;
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 24px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding: 13px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.11);
            font-size: 14px;
        }

        .summary-row span:first-child {
            color: #cccccc;
        }

        .summary-row span:last-child {
            font-weight: 900;
            text-align: right;
        }

        .summary-total {
            margin-top: 18px;
            padding: 20px;
            border-radius: 18px;
            background: rgba(237, 28, 36, 0.16);
            border: 1px solid rgba(237, 28, 36, 0.36);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
        }

        .summary-total span:first-child {
            color: #dddddd;
            font-size: 14px;
        }

        .summary-total span:last-child {
            font-size: 26px;
            font-weight: 900;
        }

        .btn-submit {
            width: 100%;
            margin-top: 24px;
            padding: 15px 22px;
            border: none;
            border-radius: 999px;
            background: var(--red);
            color: var(--white);
            font-size: 15px;
            font-weight: 900;
            cursor: pointer;
            transition: 0.3s;
        }

        .btn-submit:hover {
            background: var(--dark-red);
            transform: translateY(-2px);
            box-shadow: 0 15px 30px rgba(237, 28, 36, 0.25);
        }

        .note-box {
            margin-top: 18px;
            background: rgba(255, 255, 255, 0.08);
            padding: 16px;
            border-radius: 16px;
            color: #dddddd;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert {
            padding: 16px 18px;
            border-radius: 16px;
            margin-bottom: 22px;
            font-size: 14px;
            font-weight: 800;
            line-height: 1.6;
        }

        .alert-success {
            background: #e7f9ed;
            color: #0e7a34;
            border: 1px solid #bdebc9;
        }

        .alert-error {
            background: #ffe8e8;
            color: #bc1b1b;
            border: 1px solid #ffc9c9;
        }

        .history-section {
            margin-top: 32px;
        }

        .history-table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 850px;
        }

        th,
        td {
            padding: 16px 18px;
            text-align: left;
            border-bottom: 1px solid var(--border);
            font-size: 14px;
        }

        th {
            background: #fafafa;
            font-weight: 900;
        }

        td {
            color: #333333;
        }

        .table-car {
            font-weight: 900;
            color: var(--black);
        }

        .table-small {
            font-size: 12px;
            color: var(--gray);
            margin-top: 4px;
        }

        .empty-state {
            text-align: center;
            padding: 55px 25px;
            color: var(--gray);
        }

        .empty-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            background: #fff2f4;
            color: var(--red);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 30px;
            font-weight: 900;
        }

        footer {
            margin-top: 40px;
            background: var(--black);
            color: var(--white);
            padding: 28px;
            text-align: center;
            border-radius: 24px;
        }

        footer p {
            color: #bbbbbb;
            font-size: 14px;
        }

        @media (max-width: 992px) {
            .top-actions,
            .welcome-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .welcome-status {
                text-align: left;
            }

            .hero {
                padding: 36px 26px;
            }

            .hero h1 {
                font-size: 34px;
            }

            .top-status-grid,
            .content-grid {
                grid-template-columns: 1fr;
            }

            .summary-box {
                position: static;
            }
        }

        @media (max-width: 650px) {
            .page-wrapper {
                padding: 24px 16px 50px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .plate-control-row {
                grid-template-columns: 1fr 1fr;
            }

            .panel-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .summary-row,
            .summary-total {
                flex-direction: column;
                align-items: flex-start;
            }

            .summary-row span:last-child {
                text-align: left;
            }
        }
    </style>
</head>

<body>

<div class="page-wrapper">

    <div class="top-actions">
        <a href="homepage.php" class="brand">Toyota<span>Drive</span></a>
        <a href="dashboard.php#check-status" class="back-btn">← Back to Dashboard</a>
    </div>

    <section class="hero">
        <div class="hero-content">
            <div class="breadcrumb">
                Home / My Profile / <span>JPJ Registration</span>
            </div>

            <h1>Welcome, <span><?php echo htmlspecialchars($username); ?></span></h1>

            <p>
                This is your JPJ number plate and insurance selection page. The plate prefix is randomly generated by the system.
                You only need to choose the number digits and insurance package before submitting for admin approval.
            </p>
        </div>
    </section>

    <section class="welcome-card">
        <div class="welcome-left">
            <h2>JPJ Request for <span><?php echo htmlspecialchars($username); ?></span></h2>
            <p>
                Your request will be submitted as a simulation only. No real payment will be charged.
                Once admin approves the request, the latest JPJ summary will appear in your dashboard.
            </p>
        </div>

        <div class="welcome-status">
            <div class="status-pill <?php echo $statusClass; ?>">
                <?php echo htmlspecialchars($latestStatus); ?>
            </div>
            <div class="status-sub">Latest JPJ application status</div>
        </div>
    </section>

    <section class="top-status-grid">
        <div class="status-card dark">
            <h3>Current JPJ Status</h3>

            <?php if ($latestApplication): ?>
                <div class="big"><?php echo htmlspecialchars($latestApplication['payment_status']); ?></div>
                <div class="small">
                    Latest request:
                    <?php echo htmlspecialchars($latestApplication['car_model']); ?>
                    <?php echo htmlspecialchars($latestApplication['car_variant']); ?>
                </div>

                <div class="status-pill <?php echo $statusClass; ?>">
                    <?php echo htmlspecialchars($latestApplication['payment_status']); ?>
                </div>
            <?php else: ?>
                <div class="big">Not Submitted</div>
                <div class="small">You have not submitted any JPJ registration request yet.</div>
                <div class="status-pill status-none">No Request</div>
            <?php endif; ?>
        </div>

        <div class="status-card">
            <h3>Total Requests</h3>
            <div class="big"><?php echo count($applications); ?></div>
            <div class="small">Total JPJ registration requests submitted by your account.</div>
        </div>

        <div class="status-card">
            <h3>Plate Prefix</h3>
            <div class="big"><?php echo htmlspecialchars($randomPlatePrefix); ?></div>
            <div class="small">This prefix is randomly generated. You only select the number digits.</div>
        </div>
    </section>

    <section class="content-grid">

        <div class="panel">
            <div class="panel-header">
                <div>
                    <h2>Submit <span>JPJ Request</span></h2>
                    <p>Select your Toyota model, number plate digits and insurance plan.</p>
                </div>
                <div class="step-tag">User Submit</div>
            </div>

            <div class="panel-body">

                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="jpj.php" id="jpjForm">

                    <div class="form-row">
                        <div class="form-group">
                            <label for="car_model">Toyota Model</label>
                            <select name="car_model" id="car_model" required>
                                <option value="">Select Toyota Model</option>
                                <?php foreach ($carOptions as $model => $variants): ?>
                                    <option value="<?php echo htmlspecialchars($model); ?>">
                                        <?php echo htmlspecialchars($model); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="helper-text">Choose the Toyota vehicle that you want to register with JPJ.</div>
                        </div>

                        <div class="form-group">
                            <label for="car_variant">Variant</label>
                            <select name="car_variant" id="car_variant" required>
                                <option value="">Select Variant</option>
                                <?php foreach ($carOptions as $model => $variants): ?>
                                    <optgroup label="<?php echo htmlspecialchars($model); ?>">
                                        <?php foreach ($variants as $variant => $cc): ?>
                                            <option
                                                value="<?php echo htmlspecialchars($variant); ?>"
                                                data-model="<?php echo htmlspecialchars($model); ?>"
                                                data-cc="<?php echo htmlspecialchars($cc); ?>"
                                            >
                                                <?php echo htmlspecialchars($model . " - " . $variant . " (" . $cc . ")"); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </optgroup>
                                <?php endforeach; ?>
                            </select>
                            <div class="helper-text">All Toyota variants are listed here. Selecting a variant will auto-match the model.</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Preferred Plate Number</label>

                        <div class="plate-builder">
                            <div class="plate-preview" id="platePreview"><?php echo htmlspecialchars($randomPlatePrefix); ?> 000</div>

                            <div class="plate-control-row">
                                <div class="plate-locked"><?php echo htmlspecialchars($randomPlatePrefix); ?></div>
                                <input type="hidden" name="plate_prefix" id="plate_prefix" value="<?php echo htmlspecialchars($randomPlatePrefix); ?>">

                                <select name="plate_digit_1" id="plate_digit_1" required>
                                    <?php for ($i = 0; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>

                                <select name="plate_digit_2" id="plate_digit_2" required>
                                    <?php for ($i = 0; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>

                                <select name="plate_digit_3" id="plate_digit_3" required>
                                    <?php for ($i = 0; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>

                                <select name="plate_digit_4" id="plate_digit_4">
                                    <option value="">-</option>
                                    <?php for ($i = 0; $i <= 9; $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="helper-text">
                                The front letter is randomly assigned by the system. You only choose 3 or 4 number digits.
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Insurance Package</label>

                        <div class="insurance-note">
                            Insurance package will change based on the selected vehicle engine CC.
                            Please choose one package before submitting your JPJ request.
                        </div>

                        <div class="insurance-list" id="insuranceList">
                            <div class="helper-text">
                                Please select a Toyota variant first. The insurance choices and prices will appear here.
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">
                        Submit JPJ Request for Admin Approval
                    </button>

                </form>
            </div>
        </div>

        <aside class="summary-box">
            <h2>JPJ Summary</h2>
            <p class="summary-sub">
                The amount below is automatically calculated based on your selected vehicle CC and insurance package.
            </p>

            <div class="summary-row">
                <span>Selected Model</span>
                <span id="summaryModel">-</span>
            </div>

            <div class="summary-row">
                <span>Variant</span>
                <span id="summaryVariant">-</span>
            </div>

            <div class="summary-row">
                <span>Engine CC</span>
                <span id="summaryCc">-</span>
            </div>

            <div class="summary-row">
                <span>Plate Number</span>
                <span id="summaryPlate"><?php echo htmlspecialchars($randomPlatePrefix); ?> 000</span>
            </div>

            <div class="summary-row">
                <span>Insurance</span>
                <span id="summaryInsurance">RM 0.00</span>
            </div>

            <div class="summary-row">
                <span>Road Tax</span>
                <span id="summaryRoadTax">RM 0.00</span>
            </div>

            <div class="summary-row">
                <span>Registration Fee</span>
                <span>RM 300.00</span>
            </div>

            <div class="summary-row">
                <span>Service Fee</span>
                <span>RM 150.00</span>
            </div>

            <div class="summary-total">
                <span>Total Simulation Amount</span>
                <span id="summaryTotal">RM 450.00</span>
            </div>

            <div class="note-box">
                After submission, the status will be shown as <strong>Pending Approval</strong>.
                Once admin approves the request, user can view the confirmed JPJ summary in dashboard.
            </div>
        </aside>

    </section>

    <section class="panel history-section">
        <div class="panel-header">
            <div>
                <h2>My JPJ <span>Application History</span></h2>
                <p>All JPJ registration requests submitted by your account will appear here.</p>
            </div>
            <div class="step-tag">Admin Review</div>
        </div>

        <div class="history-table-wrapper">
            <?php if (count($applications) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Vehicle</th>
                            <th>Plate No.</th>
                            <th>Insurance</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Admin Remark</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($applications as $application): ?>
                            <?php
                            $rowStatusClass = "status-pending";

                            if ($application['payment_status'] === "Approved") {
                                $rowStatusClass = "status-approved";
                            } elseif ($application['payment_status'] === "Rejected") {
                                $rowStatusClass = "status-rejected";
                            }
                            ?>

                            <tr>
                                <td>
                                    <?php echo date("d M Y", strtotime($application['created_at'])); ?>
                                    <div class="table-small">
                                        <?php echo date("h:i A", strtotime($application['created_at'])); ?>
                                    </div>
                                </td>

                                <td>
                                    <div class="table-car">
                                        <?php echo htmlspecialchars($application['car_model']); ?>
                                    </div>
                                    <div class="table-small">
                                        <?php echo htmlspecialchars($application['car_variant']); ?>
                                        ·
                                        <?php echo htmlspecialchars($application['engine_cc']); ?>
                                    </div>
                                </td>

                                <td>
                                    <strong><?php echo htmlspecialchars($application['plate_number']); ?></strong>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($application['insurance_plan']); ?>
                                    <div class="table-small">
                                        RM <?php echo number_format($application['insurance_price'], 2); ?>
                                    </div>
                                </td>

                                <td>
                                    <strong>RM <?php echo number_format($application['total_amount'], 2); ?></strong>
                                </td>

                                <td>
                                    <span class="status-pill <?php echo $rowStatusClass; ?>">
                                        <?php echo htmlspecialchars($application['payment_status']); ?>
                                    </span>
                                </td>

                                <td>
                                    <?php
                                    if (!empty($application['admin_remark'])) {
                                        echo htmlspecialchars($application['admin_remark']);
                                    } else {
                                        echo "<span class='table-small'>No remark yet</span>";
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon">!</div>
                    <h3>No JPJ Request Yet</h3>
                    <p>You have not submitted any JPJ registration request. Please complete the form above first.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <p>© 2026 ToyotaDrive. JPJ module is a simulation feature for academic project purpose only.</p>
    </footer>

</div>

<script>
    const carData = <?php echo json_encode($carOptions); ?>;
    const fixedPlatePrefix = <?php echo json_encode($randomPlatePrefix); ?>;

    const insuranceData = {
        "Below 1600cc": {
            "Basic Protection": {
                price: 1450,
                desc: "Basic yearly protection for small engine Toyota models."
            },
            "Standard Protection": {
                price: 1850,
                desc: "Balanced insurance package for normal daily driving."
            },
            "Premium Protection": {
                price: 2380,
                desc: "Higher coverage package with better protection benefits."
            }
        },
        "1601cc - 2000cc": {
            "Basic Protection": {
                price: 1750,
                desc: "Basic yearly protection for medium engine Toyota models."
            },
            "Standard Protection": {
                price: 2250,
                desc: "Balanced insurance package for 1.8L to 2.0L vehicles."
            },
            "Premium Protection": {
                price: 2880,
                desc: "Higher coverage package for better protection."
            }
        },
        "2001cc - 3000cc": {
            "Basic Protection": {
                price: 2450,
                desc: "Basic yearly protection for higher engine capacity vehicles."
            },
            "Standard Protection": {
                price: 3180,
                desc: "Balanced insurance package for large Toyota models."
            },
            "Premium Protection": {
                price: 3880,
                desc: "Premium coverage package for higher value vehicles."
            }
        }
    };

    const carModelSelect = document.getElementById("car_model");
    const carVariantSelect = document.getElementById("car_variant");
    const insuranceList = document.getElementById("insuranceList");

    const plateDigit1 = document.getElementById("plate_digit_1");
    const plateDigit2 = document.getElementById("plate_digit_2");
    const plateDigit3 = document.getElementById("plate_digit_3");
    const plateDigit4 = document.getElementById("plate_digit_4");
    const platePreview = document.getElementById("platePreview");

    const summaryModel = document.getElementById("summaryModel");
    const summaryVariant = document.getElementById("summaryVariant");
    const summaryCc = document.getElementById("summaryCc");
    const summaryPlate = document.getElementById("summaryPlate");
    const summaryInsurance = document.getElementById("summaryInsurance");
    const summaryRoadTax = document.getElementById("summaryRoadTax");
    const summaryTotal = document.getElementById("summaryTotal");

    let currentInsurancePrice = 0;
    let currentRoadTax = 0;

    function formatRM(amount) {
        return "RM " + Number(amount).toLocaleString("en-MY", {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function getCcCategory(engineCc) {
        const cc = parseInt(engineCc.replace(/\D/g, ""));

        if (cc <= 1600) {
            return "Below 1600cc";
        } else if (cc <= 2000) {
            return "1601cc - 2000cc";
        } else {
            return "2001cc - 3000cc";
        }
    }

    function calculateRoadTax(engineCc) {
        const cc = parseInt(engineCc.replace(/\D/g, ""));

        if (cc <= 1600) {
            return 90;
        } else if (cc <= 2000) {
            return 280;
        } else if (cc <= 2500) {
            return 880;
        } else {
            return 1630;
        }
    }

    function updatePlatePreview() {
        const digits =
            plateDigit1.value +
            plateDigit2.value +
            plateDigit3.value +
            plateDigit4.value;

        const finalPlate = fixedPlatePrefix + " " + digits;

        platePreview.textContent = finalPlate;
        summaryPlate.textContent = finalPlate;
    }

    function updateTotal() {
        const registrationFee = 300;
        const serviceFee = 150;
        const total = currentInsurancePrice + currentRoadTax + registrationFee + serviceFee;

        summaryInsurance.textContent = formatRM(currentInsurancePrice);
        summaryRoadTax.textContent = formatRM(currentRoadTax);
        summaryTotal.textContent = formatRM(total);
    }

    function updateInsuranceOptions(engineCc) {
        const category = getCcCategory(engineCc);
        const plans = insuranceData[category];

        insuranceList.innerHTML = "";

        Object.keys(plans).forEach((planName, index) => {
            const item = plans[planName];
            const price = item.price;

            const wrapper = document.createElement("div");
            wrapper.className = "insurance-option";

            const input = document.createElement("input");
            input.type = "radio";
            input.name = "insurance_plan";
            input.id = "insurance_" + index;
            input.value = planName;
            input.required = true;
            input.dataset.price = price;

            const label = document.createElement("label");
            label.className = "insurance-card";
            label.setAttribute("for", "insurance_" + index);

            label.innerHTML = `
                <div>
                    <h4>${planName}</h4>
                    <p>${category} · ${item.desc}</p>
                </div>
                <div class="insurance-price">${formatRM(price)}</div>
            `;

            input.addEventListener("change", function () {
                currentInsurancePrice = Number(this.dataset.price);
                updateTotal();
            });

            wrapper.appendChild(input);
            wrapper.appendChild(label);
            insuranceList.appendChild(wrapper);
        });
    }

    function rebuildVariantListByModel(model) {
        carVariantSelect.innerHTML = `<option value="">Select Variant</option>`;

        if (!model || !carData[model]) {
            Object.keys(carData).forEach(groupModel => {
                const group = document.createElement("optgroup");
                group.label = groupModel;

                Object.keys(carData[groupModel]).forEach(variant => {
                    const option = document.createElement("option");
                    option.value = variant;
                    option.textContent = groupModel + " - " + variant + " (" + carData[groupModel][variant] + ")";
                    option.dataset.model = groupModel;
                    option.dataset.cc = carData[groupModel][variant];
                    group.appendChild(option);
                });

                carVariantSelect.appendChild(group);
            });

            return;
        }

        Object.keys(carData[model]).forEach(variant => {
            const option = document.createElement("option");
            option.value = variant;
            option.textContent = variant + " (" + carData[model][variant] + ")";
            option.dataset.model = model;
            option.dataset.cc = carData[model][variant];
            carVariantSelect.appendChild(option);
        });
    }

    carModelSelect.addEventListener("change", function () {
        const selectedModel = this.value;

        summaryModel.textContent = selectedModel || "-";
        summaryVariant.textContent = "-";
        summaryCc.textContent = "-";

        currentInsurancePrice = 0;
        currentRoadTax = 0;

        insuranceList.innerHTML = `
            <div class="helper-text">
                Please select a Toyota variant first. Insurance packages will appear based on engine CC.
            </div>
        `;

        updateTotal();
        rebuildVariantListByModel(selectedModel);
    });

    carVariantSelect.addEventListener("change", function () {
        const selectedOption = carVariantSelect.options[carVariantSelect.selectedIndex];

        if (!selectedOption || !selectedOption.value) {
            return;
        }

        const selectedModel = selectedOption.dataset.model || carModelSelect.value;
        const selectedVariant = selectedOption.value;
        const engineCc = selectedOption.dataset.cc || "";

        if (selectedModel && carModelSelect.value !== selectedModel) {
            carModelSelect.value = selectedModel;
        }

        summaryModel.textContent = selectedModel || "-";
        summaryVariant.textContent = selectedVariant || "-";
        summaryCc.textContent = engineCc || "-";

        currentRoadTax = calculateRoadTax(engineCc);
        currentInsurancePrice = 0;

        updateInsuranceOptions(engineCc);
        updateTotal();
    });

    [plateDigit1, plateDigit2, plateDigit3, plateDigit4].forEach(item => {
        item.addEventListener("change", updatePlatePreview);
    });

    rebuildVariantListByModel("");
    updatePlatePreview();
</script>

</body>
</html>