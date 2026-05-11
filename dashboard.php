<?php
session_start();
include("config.php");

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = (int)$_SESSION['user_id'];

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

function formatMoney($amount)
{
    return "RM " . number_format((float)$amount, 2);
}

$userTable = getFirstExistingTable($conn, ['users', 'user', 'customers', 'customer']);

if (!$userTable) {
    die("User table not found. Please check your database table name.");
}

$userIdColumn = getFirstExistingColumn($conn, $userTable, ['user_id', 'id', 'customer_id']);
$nameColumn = getFirstExistingColumn($conn, $userTable, ['username', 'name', 'full_name', 'user_name']);
$emailColumn = getFirstExistingColumn($conn, $userTable, ['email', 'user_email']);
$phoneColumn = getFirstExistingColumn($conn, $userTable, ['phone', 'phone_number', 'contact', 'contact_number']);
$licenseColumn = getFirstExistingColumn($conn, $userTable, ['driving_license', 'license_no', 'license_number', 'driving_license_no']);
$addressColumn = getFirstExistingColumn($conn, $userTable, ['address', 'user_address']);
$passwordColumn = getFirstExistingColumn($conn, $userTable, ['password', 'user_password']);
$createdAtColumn = getFirstExistingColumn($conn, $userTable, ['created_at', 'register_date', 'created_on', 'date_created']);

if (!$userIdColumn) {
    die("User ID column not found.");
}

$stmt = $conn->prepare("SELECT * FROM `$userTable` WHERE `$userIdColumn` = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();
$userData = $userResult->fetch_assoc();

if (!$userData) {
    die("User account not found.");
}

$fullName = $nameColumn ? ($userData[$nameColumn] ?? '') : ($_SESSION['username'] ?? 'User');
$email = $emailColumn ? ($userData[$emailColumn] ?? '') : ($_SESSION['email'] ?? '');
$phone = $phoneColumn ? ($userData[$phoneColumn] ?? '') : '';
$drivingLicense = $licenseColumn ? ($userData[$licenseColumn] ?? '') : '';
$address = $addressColumn ? ($userData[$addressColumn] ?? '') : '';
$memberSince = "2026";

if ($createdAtColumn && !empty($userData[$createdAtColumn])) {
    $memberSince = date("Y", strtotime($userData[$createdAtColumn]));
}

$profileMessage = "";
$profileMessageType = "";
$passwordMessage = "";
$passwordMessageType = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (isset($_POST['action']) && $_POST['action'] === "update_profile") {
        $newName = trim($_POST['full_name'] ?? '');
        $newEmail = trim($_POST['email'] ?? '');
        $newPhone = trim($_POST['phone'] ?? '');
        $newLicense = trim($_POST['driving_license'] ?? '');
        $newAddress = trim($_POST['address'] ?? '');

        if ($newName === "" || $newEmail === "") {
            $profileMessage = "Full name and email are required.";
            $profileMessageType = "error";
        } else {
            $fields = [];
            $types = "";
            $values = [];

            if ($nameColumn) {
                $fields[] = "`$nameColumn` = ?";
                $types .= "s";
                $values[] = $newName;
            }

            if ($emailColumn) {
                $fields[] = "`$emailColumn` = ?";
                $types .= "s";
                $values[] = $newEmail;
            }

            if ($phoneColumn) {
                $fields[] = "`$phoneColumn` = ?";
                $types .= "s";
                $values[] = $newPhone;
            }

            if ($licenseColumn) {
                $fields[] = "`$licenseColumn` = ?";
                $types .= "s";
                $values[] = $newLicense;
            }

            if ($addressColumn) {
                $fields[] = "`$addressColumn` = ?";
                $types .= "s";
                $values[] = $newAddress;
            }

            if (!empty($fields)) {
                $types .= "i";
                $values[] = $user_id;

                $sql = "UPDATE `$userTable` SET " . implode(", ", $fields) . " WHERE `$userIdColumn` = ?";
                $updateStmt = $conn->prepare($sql);
                $updateStmt->bind_param($types, ...$values);

                if ($updateStmt->execute()) {
                    if ($nameColumn) $_SESSION['username'] = $newName;
                    if ($emailColumn) $_SESSION['email'] = $newEmail;

                    $profileMessage = "Profile updated successfully.";
                    $profileMessageType = "success";

                    $fullName = $newName;
                    $email = $newEmail;
                    $phone = $newPhone;
                    $drivingLicense = $newLicense;
                    $address = $newAddress;
                } else {
                    $profileMessage = "Failed to update profile.";
                    $profileMessageType = "error";
                }
            }
        }
    }

    if (isset($_POST['action']) && $_POST['action'] === "change_password") {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (!$passwordColumn) {
            $passwordMessage = "Password column not found.";
            $passwordMessageType = "error";
        } elseif ($currentPassword === "" || $newPassword === "" || $confirmPassword === "") {
            $passwordMessage = "Please fill in all password fields.";
            $passwordMessageType = "error";
        } elseif (strlen($newPassword) < 8) {
            $passwordMessage = "New password must be at least 8 characters.";
            $passwordMessageType = "error";
        } elseif ($newPassword !== $confirmPassword) {
            $passwordMessage = "New password and confirm password do not match.";
            $passwordMessageType = "error";
        } else {
            $storedPassword = $userData[$passwordColumn] ?? "";
            $passwordOk = false;

            if (password_verify($currentPassword, $storedPassword)) {
                $passwordOk = true;
            } elseif ($currentPassword === $storedPassword) {
                $passwordOk = true;
            }

            if (!$passwordOk) {
                $passwordMessage = "Current password is incorrect.";
                $passwordMessageType = "error";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                $passStmt = $conn->prepare("UPDATE `$userTable` SET `$passwordColumn` = ? WHERE `$userIdColumn` = ?");
                $passStmt->bind_param("si", $hashedPassword, $user_id);

                if ($passStmt->execute()) {
                    $passwordMessage = "Password updated successfully.";
                    $passwordMessageType = "success";
                } else {
                    $passwordMessage = "Failed to update password.";
                    $passwordMessageType = "error";
                }
            }
        }
    }
}

$bookingTable = getFirstExistingTable($conn, ['bookings', 'booking', 'test_drive_bookings', 'car_bookings']);
$bookingUserColumn = $bookingTable ? getFirstExistingColumn($conn, $bookingTable, ['user_id', 'customer_id']) : null;
$bookingModelColumn = $bookingTable ? getFirstExistingColumn($conn, $bookingTable, ['car_model', 'model', 'vehicle_model', 'car_name']) : null;
$bookingVariantColumn = $bookingTable ? getFirstExistingColumn($conn, $bookingTable, ['car_variant', 'variant']) : null;
$bookingDateColumn = $bookingTable ? getFirstExistingColumn($conn, $bookingTable, ['booking_date', 'test_drive_date', 'created_at', 'date_created']) : null;
$bookingStatusColumn = $bookingTable ? getFirstExistingColumn($conn, $bookingTable, ['status', 'booking_status']) : null;
$bookingAmountColumn = $bookingTable ? getFirstExistingColumn($conn, $bookingTable, ['total_amount', 'amount', 'price']) : null;

$totalBookings = 0;
$totalSpent = 0;
$recentBookings = [];

if ($bookingTable && $bookingUserColumn) {
    $bookingCountStmt = $conn->prepare("SELECT COUNT(*) AS total FROM `$bookingTable` WHERE `$bookingUserColumn` = ?");
    $bookingCountStmt->bind_param("i", $user_id);
    $bookingCountStmt->execute();
    $bookingCountResult = $bookingCountStmt->get_result();
    $totalBookings = (int)($bookingCountResult->fetch_assoc()['total'] ?? 0);

    if ($bookingAmountColumn) {
        $spentStmt = $conn->prepare("SELECT COALESCE(SUM(`$bookingAmountColumn`), 0) AS total_spent FROM `$bookingTable` WHERE `$bookingUserColumn` = ?");
        $spentStmt->bind_param("i", $user_id);
        $spentStmt->execute();
        $spentResult = $spentStmt->get_result();
        $totalSpent = (float)($spentResult->fetch_assoc()['total_spent'] ?? 0);
    }

    $orderColumn = $bookingDateColumn ?: $bookingUserColumn;
    $recentStmt = $conn->prepare("SELECT * FROM `$bookingTable` WHERE `$bookingUserColumn` = ? ORDER BY `$orderColumn` DESC LIMIT 5");
    $recentStmt->bind_param("i", $user_id);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();

    while ($row = $recentResult->fetch_assoc()) {
        $recentBookings[] = $row;
    }
}

$jpjTable = getFirstExistingTable($conn, ['jpj_applications']);
$jpjUserColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['user_id', 'customer_id']) : null;
$jpjStatusColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['payment_status', 'status']) : null;
$jpjDateColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['created_at', 'date_created']) : null;
$jpjPlateColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['plate_number', 'no_plate']) : null;
$jpjModelColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['car_model', 'model']) : null;
$jpjVariantColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['car_variant', 'variant']) : null;
$jpjInsuranceColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['insurance_plan', 'insurance']) : null;
$jpjInsurancePriceColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['insurance_price']) : null;
$jpjRoadTaxColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['road_tax']) : null;
$jpjRegistrationFeeColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['registration_fee']) : null;
$jpjServiceFeeColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['service_fee']) : null;
$jpjTotalColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['total_amount', 'amount']) : null;
$jpjPaymentMethodColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['payment_method']) : null;
$jpjRemarkColumn = $jpjTable ? getFirstExistingColumn($conn, $jpjTable, ['admin_remark', 'remark']) : null;

$latestJPJ = null;
$jpjStatus = "Not Started";

if ($jpjTable && $jpjUserColumn) {
    $orderColumn = $jpjDateColumn ?: $jpjUserColumn;
    $jpjStmt = $conn->prepare("SELECT * FROM `$jpjTable` WHERE `$jpjUserColumn` = ? ORDER BY `$orderColumn` DESC LIMIT 1");
    $jpjStmt->bind_param("i", $user_id);
    $jpjStmt->execute();
    $jpjResult = $jpjStmt->get_result();
    $latestJPJ = $jpjResult->fetch_assoc();

    if ($latestJPJ && $jpjStatusColumn && !empty($latestJPJ[$jpjStatusColumn])) {
        $jpjStatus = $latestJPJ[$jpjStatusColumn];
    }
}

$avatarLetter = strtoupper(substr(trim($fullName ?: "U"), 0, 1));

$statusBadgeClass = "badge-gray";
if ($jpjStatus === "Pending Approval") $statusBadgeClass = "badge-orange";
if ($jpjStatus === "Approved") $statusBadgeClass = "badge-green";
if ($jpjStatus === "Rejected") $statusBadgeClass = "badge-red";

$step1Class = "done";
$step2Class = "active";
$step3Class = "wait";
$step4Class = "wait";
$step4Label = "Successfully Completed";

if ($latestJPJ) {
    $step2Class = "done";

    if ($jpjStatus === "Pending Approval") {
        $step3Class = "active";
        $step4Class = "wait";
    } elseif ($jpjStatus === "Approved") {
        $step3Class = "done";
        $step4Class = "done";
    } elseif ($jpjStatus === "Rejected") {
        $step3Class = "rejected";
        $step4Class = "wait";
        $step4Label = "Action Required";
    } else {
        $step3Class = "active";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Toyota Car Selling</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        :root {
            --red: #ed1c24;
            --dark-red: #b80f18;
            --black: #111111;
            --dark: #181818;
            --white: #ffffff;
            --light: #f7f7f7;
            --soft-red: #fff1f2;
            --text: #121212;
            --muted: #777777;
            --border: #e7e7e7;
            --green: #16a34a;
            --orange: #f59e0b;
            --danger: #dc2626;
            --shadow: 0 18px 45px rgba(0, 0, 0, 0.08);
            --radius: 28px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Segoe UI", Arial, sans-serif;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            min-height: 100vh;
            background:
                radial-gradient(circle at top left, rgba(237, 28, 36, 0.10), transparent 28%),
                linear-gradient(180deg, #ffffff 0%, #f5f5f5 100%);
            color: var(--text);
        }

        a {
            text-decoration: none;
            color: inherit;
        }

        .dashboard-wrapper {
            width: 100%;
            max-width: 1500px;
            margin: 0 auto;
            padding: 35px;
            display: grid;
            grid-template-columns: 350px 1fr;
            gap: 35px;
            align-items: start;
        }

        .sidebar {
            background: var(--white);
            border-radius: 34px;
            box-shadow: var(--shadow);
            padding: 36px 28px 28px;
            position: sticky;
            top: 35px;
            border: 1px solid var(--border);
        }

        .profile-box {
            text-align: center;
            padding-bottom: 28px;
            border-bottom: 1px solid var(--border);
        }

        .avatar {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--red), var(--black));
            color: var(--white);
            font-size: 72px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            box-shadow: 0 20px 45px rgba(237, 28, 36, 0.22);
        }

        .profile-box h2 {
            font-size: 30px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .profile-box p {
            color: var(--muted);
            font-size: 16px;
            margin-bottom: 22px;
            word-break: break-word;
        }

        .member-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 13px 26px;
            border-radius: 999px;
            color: var(--white);
            font-weight: 900;
            background: linear-gradient(135deg, var(--red), var(--black));
        }

        .sidebar-menu {
            padding-top: 28px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .menu-link {
            display: flex;
            align-items: center;
            gap: 14px;
            min-height: 58px;
            padding: 17px 20px;
            border-radius: 18px;
            background: #f7f7f7;
            color: var(--text);
            font-size: 16px;
            font-weight: 900;
            transition: 0.25s;
        }

        .menu-link:hover,
        .menu-link.active {
            background: linear-gradient(135deg, var(--red), var(--black));
            color: var(--white);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(237, 28, 36, 0.16);
        }

        .menu-link.logout {
            margin-top: 14px;
            background: var(--soft-red);
            color: var(--red);
            border: 1px solid #ffd6d6;
        }

        .menu-link.logout:hover {
            color: var(--white);
            background: var(--red);
        }

        .main-content {
            display: flex;
            flex-direction: column;
            gap: 28px;
        }

        .top-header {
            background: linear-gradient(135deg, var(--black), #2a2a2a);
            color: var(--white);
            border-radius: 34px;
            padding: 34px;
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .top-header::after {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            background: rgba(237, 28, 36, 0.18);
            right: -80px;
            top: -100px;
        }

        .top-header-content {
            position: relative;
            z-index: 2;
        }

        .top-header h1 {
            font-size: 36px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .top-header p {
            color: #dddddd;
            line-height: 1.7;
            font-size: 15px;
            max-width: 850px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 22px;
        }

        .stat-card {
            background: var(--white);
            border-radius: 30px;
            box-shadow: var(--shadow);
            padding: 28px;
            border: 1px solid var(--border);
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--red), var(--black));
            color: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            margin-bottom: 18px;
        }

        .stat-card h3 {
            font-size: 17px;
            color: var(--muted);
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 34px;
            font-weight: 900;
            color: var(--text);
            margin-bottom: 6px;
        }

        .stat-card .sub {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.6;
        }

        .section {
            background: var(--white);
            border-radius: 34px;
            box-shadow: var(--shadow);
            padding: 34px;
            scroll-margin-top: 35px;
            border: 1px solid var(--border);
        }

        .section-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 18px;
            margin-bottom: 26px;
        }

        .section-title {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .section-title-icon {
            font-size: 36px;
        }

        .section-title h2 {
            font-size: 30px;
            font-weight: 900;
            color: var(--text);
        }

        .section-title p {
            color: var(--muted);
            margin-top: 5px;
            line-height: 1.6;
            font-size: 14px;
        }

        .badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 9px 15px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 900;
            white-space: nowrap;
        }

        .badge-gray {
            background: #eeeeee;
            color: #555555;
        }

        .badge-orange {
            background: #fff4df;
            color: #9a5b00;
        }

        .badge-green {
            background: #e8f8ee;
            color: #166534;
        }

        .badge-red {
            background: #ffe8e8;
            color: #b91c1c;
        }

        .jpj-banner {
            background: linear-gradient(135deg, var(--black), #262626);
            color: var(--white);
            border-radius: 28px;
            padding: 30px;
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .jpj-banner::after {
            content: "";
            position: absolute;
            width: 230px;
            height: 230px;
            border-radius: 50%;
            background: rgba(237, 28, 36, 0.18);
            right: -70px;
            top: -90px;
        }

        .jpj-banner-content {
            position: relative;
            z-index: 2;
        }

        .jpj-banner h3 {
            font-size: 28px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .jpj-banner p {
            color: #dddddd;
            line-height: 1.8;
            max-width: 900px;
            font-size: 15px;
        }

        .jpj-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 14px;
            margin-top: 22px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            border: none;
            outline: none;
            cursor: pointer;
            padding: 14px 22px;
            border-radius: 999px;
            font-size: 15px;
            font-weight: 900;
            transition: 0.25s;
            white-space: nowrap;
        }

        .btn-primary {
            color: var(--white);
            background: var(--red);
        }

        .btn-dark {
            color: var(--white);
            background: var(--black);
        }

        .btn-light {
            color: var(--text);
            background: var(--white);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 26px rgba(0, 0, 0, 0.12);
        }

        .steps {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            margin: 12px 0 0;
        }

        .step {
            position: relative;
            text-align: center;
            padding: 0 12px;
        }

        .step:not(:last-child)::after {
            content: "";
            position: absolute;
            width: 100%;
            height: 5px;
            background: #e7e7e7;
            top: 24px;
            right: -50%;
            z-index: 1;
        }

        .step.done:not(:last-child)::after {
            background: linear-gradient(90deg, var(--green), #88d69f);
        }

        .step-circle {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: var(--white);
            border: 4px solid #d7d7d7;
            color: #6b7280;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 14px;
            font-size: 20px;
            font-weight: 900;
            position: relative;
            z-index: 3;
        }

        .step.done .step-circle {
            background: var(--green);
            border-color: var(--green);
            color: var(--white);
        }

        .step.active .step-circle {
            background: var(--red);
            border-color: var(--red);
            color: var(--white);
        }

        .step.rejected .step-circle {
            background: var(--danger);
            border-color: var(--danger);
            color: var(--white);
        }

        .step h4 {
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .step p {
            color: var(--muted);
            font-size: 13px;
            line-height: 1.6;
        }

        .summary-card {
            border: 1px solid var(--border);
            background: #fbfbfb;
            border-radius: 26px;
            padding: 26px;
            margin-top: 30px;
        }

        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        .summary-item {
            background: var(--white);
            border: 1px solid var(--border);
            border-radius: 18px;
            padding: 17px 18px;
        }

        .summary-item span {
            display: block;
            color: var(--muted);
            font-size: 13px;
            margin-bottom: 7px;
        }

        .summary-item strong {
            font-size: 16px;
            font-weight: 900;
            color: var(--text);
            word-break: break-word;
        }

        .empty-box {
            border: 2px dashed #d9d9d9;
            border-radius: 26px;
            background: #fbfbfb;
            text-align: center;
            padding: 42px 24px;
        }

        .empty-box .emoji {
            font-size: 54px;
            margin-bottom: 14px;
        }

        .empty-box h3 {
            font-size: 25px;
            font-weight: 900;
            margin-bottom: 8px;
        }

        .empty-box p {
            color: var(--muted);
            line-height: 1.8;
            max-width: 750px;
            margin: 0 auto;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 22px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        .form-group label {
            display: block;
            font-size: 15px;
            font-weight: 900;
            margin-bottom: 10px;
        }

        .form-control {
            width: 100%;
            border: 2px solid #eeeeee;
            border-radius: 18px;
            background: #fbfbfb;
            padding: 16px 18px;
            outline: none;
            font-size: 16px;
            transition: 0.25s;
        }

        .form-control:focus {
            border-color: rgba(237, 28, 36, 0.45);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(237, 28, 36, 0.08);
        }

        textarea.form-control {
            min-height: 135px;
            resize: vertical;
        }

        .form-help {
            margin-top: 8px;
            color: var(--muted);
            font-size: 13px;
        }

        .alert {
            padding: 15px 18px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-weight: 800;
            font-size: 14px;
        }

        .alert.success {
            background: #e8f8ee;
            color: #166534;
            border: 1px solid #c6ead2;
        }

        .alert.error {
            background: #fff0f0;
            color: #b91c1c;
            border: 1px solid #ffd2d2;
        }

        .booking-list {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .booking-card {
            border: 1px solid var(--border);
            background: #fbfbfb;
            border-radius: 24px;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            gap: 18px;
            align-items: center;
        }

        .booking-card h4 {
            font-size: 18px;
            font-weight: 900;
            margin-bottom: 6px;
        }

        .booking-card p {
            color: var(--muted);
            font-size: 14px;
            line-height: 1.7;
        }

        .booking-side {
            text-align: right;
            min-width: 160px;
        }

        .booking-side .amount {
            display: block;
            font-size: 18px;
            font-weight: 900;
            margin-top: 8px;
        }

        @media (max-width: 1150px) {
            .dashboard-wrapper {
                grid-template-columns: 1fr;
            }

            .sidebar {
                position: static;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .steps {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .step {
                text-align: left;
                min-height: 60px;
                padding-left: 72px;
            }

            .step-circle {
                position: absolute;
                left: 0;
                top: 0;
                margin: 0;
            }

            .step:not(:last-child)::after {
                left: 24px;
                top: 52px;
                width: 5px;
                height: calc(100% + 18px);
            }
        }

        @media (max-width: 700px) {
            .dashboard-wrapper {
                padding: 18px;
            }

            .section,
            .sidebar,
            .top-header,
            .stat-card {
                border-radius: 24px;
            }

            .section {
                padding: 24px;
            }

            .form-grid,
            .summary-grid {
                grid-template-columns: 1fr;
            }

            .section-header {
                flex-direction: column;
                align-items: flex-start;
            }

            .booking-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .booking-side {
                text-align: left;
            }
        }
    </style>
</head>

<body>

<div class="dashboard-wrapper">

    <aside class="sidebar">
        <div class="profile-box">
            <div class="avatar"><?php echo htmlspecialchars($avatarLetter); ?></div>
            <h2><?php echo htmlspecialchars($fullName); ?></h2>
            <p><?php echo htmlspecialchars($email); ?></p>
            <div class="member-badge">👑 Member Since <?php echo htmlspecialchars($memberSince); ?></div>
        </div>

        <div class="sidebar-menu">
            <a href="#overview" class="menu-link active">🏠 Dashboard</a>
            <a href="#check-status" class="menu-link">✅ Check Status</a>
            <a href="#profile" class="menu-link">👤 Edit Profile</a>
            <a href="#bookings" class="menu-link">📋 My Bookings</a>
            <a href="#password" class="menu-link">🔐 Change Password</a>
            <a href="homepage.php" class="menu-link">🚗 Back to Home</a>
            <a href="logout.php" class="menu-link logout">🚪 Logout</a>
        </div>
    </aside>

    <main class="main-content">

        <section class="top-header" id="overview">
            <div class="top-header-content">
                <h1>Welcome back, <?php echo htmlspecialchars($fullName); ?></h1>
                <p>
                    Manage your profile, check your booking information, and track your JPJ number plate
                    and payment simulation status from one place.
                </p>
            </div>
        </section>

        <section class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <h3>Total Bookings</h3>
                <div class="value"><?php echo $totalBookings; ?></div>
                <div class="sub">Total booking records found under your account.</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <h3>JPJ Status</h3>
                <div class="value" style="font-size:28px;"><?php echo htmlspecialchars($jpjStatus); ?></div>
                <div class="sub">Latest number plate and payment simulation status.</div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <h3>Total Spent</h3>
                <div class="value"><?php echo formatMoney($totalSpent); ?></div>
                <div class="sub">Based on available booking amount records.</div>
            </div>
        </section>

        <section class="section" id="check-status">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">✅</div>
                    <div>
                        <h2>Check Status</h2>
                        <p>Track your number plate selection and payment simulation status.</p>
                    </div>
                </div>

                <span class="badge <?php echo $statusBadgeClass; ?>">
                    <?php echo htmlspecialchars($jpjStatus); ?>
                </span>
            </div>

            <div class="jpj-banner">
                <div class="jpj-banner-content">
                    <h3>JPJ Number Plate & Payment Simulation</h3>
                    <p>
                        This module is a simulated JPJ registration flow. User can choose a preferred number plate,
                        select insurance based on engine CC, and submit the request. After that, admin can approve it.
                        Once approved, the final JPJ summary will be shown here for the user.
                    </p>

                    <div class="jpj-actions">
                        <a href="jpj.php" class="btn btn-primary">🔢 Select No. Plate</a>
                    </div>
                </div>
            </div>

            <div class="steps">
                <div class="step <?php echo $step1Class; ?>">
                    <div class="step-circle"><?php echo $step1Class === "done" ? "✓" : "1"; ?></div>
                    <h4>Order Confirmed</h4>
                    <p>Your car order has been successfully placed.</p>
                </div>

                <div class="step <?php echo $step2Class; ?>">
                    <div class="step-circle"><?php echo $step2Class === "done" ? "✓" : "2"; ?></div>
                    <h4>Select No. Plate</h4>
                    <p>Choose your preferred vehicle number plate.</p>
                </div>

                <div class="step <?php echo $step3Class; ?>">
                    <div class="step-circle">
                        <?php
                        if ($step3Class === "done") {
                            echo "✓";
                        } elseif ($step3Class === "rejected") {
                            echo "!";
                        } else {
                            echo "3";
                        }
                        ?>
                    </div>
                    <h4>Processing</h4>
                    <p>
                        <?php
                        if ($jpjStatus === "Pending Approval") {
                            echo "Your request is being processed.";
                        } elseif ($jpjStatus === "Approved") {
                            echo "Your request has been processed successfully.";
                        } elseif ($jpjStatus === "Rejected") {
                            echo "Your request needs to be updated.";
                        } else {
                            echo "Waiting for your number plate request.";
                        }
                        ?>
                    </p>
                </div>

                <div class="step <?php echo $step4Class; ?>">
                    <div class="step-circle"><?php echo $step4Class === "done" ? "✓" : "4"; ?></div>
                    <h4><?php echo htmlspecialchars($step4Label); ?></h4>
                    <p>
                        <?php
                        if ($jpjStatus === "Approved") {
                            echo "Your JPJ process has been completed.";
                        } elseif ($jpjStatus === "Rejected") {
                            echo "Please resubmit your JPJ request.";
                        } else {
                            echo "Completion status will be shown here.";
                        }
                        ?>
                    </p>
                </div>
            </div>

            <?php if ($latestJPJ): ?>
                <div class="summary-card">
                    <div class="section-header" style="margin-bottom:20px;">
                        <div class="section-title">
                            <div class="section-title-icon">📑</div>
                            <div>
                                <h2 style="font-size:25px;">Latest JPJ Summary</h2>
                                <p>Your latest JPJ simulation record.</p>
                            </div>
                        </div>

                        <span class="badge <?php echo $statusBadgeClass; ?>">
                            <?php echo htmlspecialchars($jpjStatus); ?>
                        </span>
                    </div>

                    <div class="summary-grid">
                        <div class="summary-item">
                            <span>Plate Number</span>
                            <strong><?php echo htmlspecialchars($jpjPlateColumn ? ($latestJPJ[$jpjPlateColumn] ?? "-") : "-"); ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Vehicle</span>
                            <strong>
                                <?php
                                $vehicle = trim(
                                    ($jpjModelColumn ? ($latestJPJ[$jpjModelColumn] ?? "") : "") . " " .
                                    ($jpjVariantColumn ? ($latestJPJ[$jpjVariantColumn] ?? "") : "")
                                );
                                echo htmlspecialchars($vehicle !== "" ? $vehicle : "-");
                                ?>
                            </strong>
                        </div>

                        <div class="summary-item">
                            <span>Insurance Plan</span>
                            <strong><?php echo htmlspecialchars($jpjInsuranceColumn ? ($latestJPJ[$jpjInsuranceColumn] ?? "-") : "-"); ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Total Amount</span>
                            <strong><?php echo $jpjTotalColumn ? formatMoney($latestJPJ[$jpjTotalColumn] ?? 0) : formatMoney(0); ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Road Tax</span>
                            <strong><?php echo $jpjRoadTaxColumn ? formatMoney($latestJPJ[$jpjRoadTaxColumn] ?? 0) : "-"; ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Insurance Price</span>
                            <strong><?php echo $jpjInsurancePriceColumn ? formatMoney($latestJPJ[$jpjInsurancePriceColumn] ?? 0) : "-"; ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Registration Fee</span>
                            <strong><?php echo $jpjRegistrationFeeColumn ? formatMoney($latestJPJ[$jpjRegistrationFeeColumn] ?? 0) : "-"; ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Service Fee</span>
                            <strong><?php echo $jpjServiceFeeColumn ? formatMoney($latestJPJ[$jpjServiceFeeColumn] ?? 0) : "-"; ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Payment Method</span>
                            <strong><?php echo htmlspecialchars($jpjPaymentMethodColumn ? ($latestJPJ[$jpjPaymentMethodColumn] ?? "-") : "-"); ?></strong>
                        </div>

                        <div class="summary-item">
                            <span>Submitted Date</span>
                            <strong>
                                <?php
                                echo ($jpjDateColumn && !empty($latestJPJ[$jpjDateColumn]))
                                    ? htmlspecialchars(date("d M Y, h:i A", strtotime($latestJPJ[$jpjDateColumn])))
                                    : "-";
                                ?>
                            </strong>
                        </div>

                        <div class="summary-item" style="grid-column:1 / -1;">
                            <span>Admin Remark</span>
                            <strong>
                                <?php
                                $remark = $jpjRemarkColumn ? trim($latestJPJ[$jpjRemarkColumn] ?? "") : "";
                                echo htmlspecialchars($remark !== "" ? $remark : "No remark yet.");
                                ?>
                            </strong>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <section class="section" id="profile">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">👤</div>
                    <div>
                        <h2>Edit Profile</h2>
                        <p>Update your personal information.</p>
                    </div>
                </div>
            </div>

            <?php if ($profileMessage !== ""): ?>
                <div class="alert <?php echo $profileMessageType; ?>">
                    <?php echo htmlspecialchars($profileMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="dashboard.php#profile">
                <input type="hidden" name="action" value="update_profile">

                <div class="form-grid">
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($fullName); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="phone">Phone Number</label>
                        <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                    </div>

                    <div class="form-group">
                        <label for="driving_license">Driving License</label>
                        <input type="text" class="form-control" id="driving_license" name="driving_license" value="<?php echo htmlspecialchars($drivingLicense); ?>">
                    </div>

                    <div class="form-group full">
                        <label for="address">Address</label>
                        <textarea class="form-control" id="address" name="address"><?php echo htmlspecialchars($address); ?></textarea>
                    </div>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                </div>
            </form>
        </section>

        <section class="section" id="bookings">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">📋</div>
                    <div>
                        <h2>Recent Bookings</h2>
                        <p>Your latest booking records.</p>
                    </div>
                </div>
            </div>

            <?php if (!empty($recentBookings)): ?>
                <div class="booking-list">
                    <?php foreach ($recentBookings as $booking): ?>
                        <div class="booking-card">
                            <div>
                                <h4>
                                    <?php
                                    $bookingVehicle = trim(
                                        ($bookingModelColumn ? ($booking[$bookingModelColumn] ?? "") : "") . " " .
                                        ($bookingVariantColumn ? ($booking[$bookingVariantColumn] ?? "") : "")
                                    );
                                    echo htmlspecialchars($bookingVehicle !== "" ? $bookingVehicle : "Booking Record");
                                    ?>
                                </h4>

                                <p>
                                    Date:
                                    <?php
                                    echo ($bookingDateColumn && !empty($booking[$bookingDateColumn]))
                                        ? htmlspecialchars(date("d M Y, h:i A", strtotime($booking[$bookingDateColumn])))
                                        : "-";
                                    ?>
                                </p>

                                <p>
                                    Status:
                                    <strong><?php echo htmlspecialchars($bookingStatusColumn ? ($booking[$bookingStatusColumn] ?? "N/A") : "N/A"); ?></strong>
                                </p>
                            </div>

                            <div class="booking-side">
                                <span class="badge badge-gray">
                                    <?php echo htmlspecialchars($bookingStatusColumn ? ($booking[$bookingStatusColumn] ?? "Booking") : "Booking"); ?>
                                </span>

                                <?php if ($bookingAmountColumn): ?>
                                    <span class="amount"><?php echo formatMoney($booking[$bookingAmountColumn] ?? 0); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-box">
                    <div class="emoji">🗓️</div>
                    <h3>No bookings yet</h3>
                    <p>You have not made any booking yet. Browse our Toyota catalogue to get started.</p>
                    <a href="catalogue.php" class="btn btn-primary" style="margin-top:22px;">🚗 Start Browsing Cars</a>
                </div>
            <?php endif; ?>
        </section>

        <section class="section" id="password">
            <div class="section-header">
                <div class="section-title">
                    <div class="section-title-icon">🔐</div>
                    <div>
                        <h2>Change Password</h2>
                        <p>Password must be at least 8 characters.</p>
                    </div>
                </div>
            </div>

            <?php if ($passwordMessage !== ""): ?>
                <div class="alert <?php echo $passwordMessageType; ?>">
                    <?php echo htmlspecialchars($passwordMessage); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="dashboard.php#password">
                <input type="hidden" name="action" value="change_password">

                <div class="form-grid">
                    <div class="form-group full">
                        <label for="current_password">Current Password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" minlength="8" required>
                        <div class="form-help">Password must be at least 8 characters.</div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" minlength="8" required>
                        <div class="form-help">Re-enter the same password for confirmation.</div>
                    </div>
                </div>

                <div style="margin-top:24px;">
                    <button type="submit" class="btn btn-primary">🔒 Update Password</button>
                </div>
            </form>
        </section>

    </main>
</div>

<script>
    const menuLinks = document.querySelectorAll('.menu-link[href^="#"]');
    const sections = document.querySelectorAll('.main-content > section');

    function setActiveMenu() {
        let current = 'overview';

        sections.forEach(section => {
            if (window.scrollY >= section.offsetTop - 170) {
                current = section.getAttribute('id');
            }
        });

        menuLinks.forEach(link => {
            link.classList.remove('active');

            if (link.getAttribute('href') === '#' + current) {
                link.classList.add('active');
            }
        });
    }

    menuLinks.forEach(link => {
        link.addEventListener('click', function () {
            menuLinks.forEach(item => item.classList.remove('active'));
            this.classList.add('active');
        });
    });

    window.addEventListener('scroll', setActiveMenu);
    window.addEventListener('load', setActiveMenu);
</script>

</body>
</html>