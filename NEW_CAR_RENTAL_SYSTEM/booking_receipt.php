<?php
require_once "config.php";

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }

function tableExists($conn,$table){
    $stmt=$conn->prepare("SELECT COUNT(*) total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $stmt->bind_param("s",$table);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row["total"] > 0;
}

function columnExists($conn,$table,$column){
    $stmt=$conn->prepare("SELECT COUNT(*) total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $stmt->bind_param("ss",$table,$column);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row["total"] > 0;
}

function firstColumn($conn,$table,$columns,$fallback=null){
    foreach($columns as $column){
        if(columnExists($conn,$table,$column)) return $column;
    }
    return $fallback;
}

if(!isset($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

$user_id=(int)$_SESSION["user_id"];
$booking_id=(int)($_GET["booking_id"] ?? 0);

if($booking_id <= 0 || !tableExists($conn,"bookings")){
    die("Invalid booking receipt.");
}

$bookingIdCol=firstColumn($conn,"bookings",["booking_id","id"],"booking_id");

$stmt=$conn->prepare("SELECT * FROM bookings WHERE $bookingIdCol=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii",$booking_id,$user_id);
$stmt->execute();
$booking=$stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$booking){
    die("Booking receipt not found.");
}

$stmt=$conn->prepare("SELECT * FROM users WHERE user_id=? LIMIT 1");
$stmt->bind_param("i",$user_id);
$stmt->execute();
$user=$stmt->get_result()->fetch_assoc();
$stmt->close();

$items=[];
if(tableExists($conn,"booking_items")){
    if(tableExists($conn,"cars") && columnExists($conn,"booking_items","car_id")){
        $carIdCol=firstColumn($conn,"cars",["car_id","id"],"car_id");
        $carNameCol=firstColumn($conn,"cars",["car_name","name"],"car_name");
        $sql="SELECT bi.*, COALESCE(c.$carNameCol,'Rental Car') AS car_name FROM booking_items bi LEFT JOIN cars c ON c.$carIdCol=bi.car_id WHERE bi.booking_id=?";
    }else{
        $sql="SELECT bi.*, 'Rental Car' AS car_name FROM booking_items bi WHERE bi.booking_id=?";
    }

    $stmt=$conn->prepare($sql);
    $stmt->bind_param("i",$booking_id);
    $stmt->execute();
    $items=$stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

$ref=$booking["booking_reference"] ?? ("BOOKING-".str_pad((string)$booking_id,5,"0",STR_PAD_LEFT));
$total=$booking["grand_total"] ?? $booking["total_amount"] ?? 0;

if(isset($_GET["download"])){
    header("Content-Type: text/html");
    header("Content-Disposition: attachment; filename=KH_Car_Rental_Invoice_".$ref.".html");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Receipt | <?= e($ref) ?></title>
<style>
body{font-family:Arial,sans-serif;background:#f4fbff;color:#17304f;margin:0;padding:30px}
.invoice{max-width:900px;margin:auto;background:#fff;border:1px solid #d8ecfb;border-radius:24px;padding:34px;box-shadow:0 24px 70px rgba(39,137,199,.16)}
.header{display:flex;justify-content:space-between;gap:20px;border-bottom:2px solid #eaf7ff;padding-bottom:20px;margin-bottom:24px}
.logo{font-size:28px;font-weight:900}
.badge{display:inline-block;padding:8px 12px;border-radius:999px;background:#eaf7ff;color:#1284c6;font-weight:800;font-size:12px}
h1{font-size:42px;margin:16px 0 6px}
.grid{display:grid;grid-template-columns:repeat(2,1fr);gap:14px;margin:22px 0}
.box{border:1px solid #d8ecfb;border-radius:16px;padding:15px;background:#fbfdff}
small{display:block;color:#6e8297;font-weight:800;text-transform:uppercase;font-size:11px;margin-bottom:6px}
strong{font-size:15px}
table{width:100%;border-collapse:collapse;margin-top:20px}
th,td{text-align:left;border-bottom:1px solid #eaf7ff;padding:13px}
th{color:#6e8297;font-size:12px;text-transform:uppercase}
.total{text-align:right;font-size:24px;font-weight:900;margin-top:24px}
.actions{margin-top:24px}
.btn{display:inline-block;padding:13px 18px;border-radius:14px;background:#1284c6;color:#fff;text-decoration:none;font-weight:800;margin-right:8px}
@media print{.actions{display:none}body{background:#fff}.invoice{box-shadow:none}}
</style>
</head>
<body>
<div class="invoice">
    <div class="header">
        <div>
            <div class="logo">KH Car Rental</div>
            <span class="badge">Booking Receipt / Invoice</span>
        </div>
        <div style="text-align:right">
            <h1>Invoice</h1>
            <strong><?= e($ref) ?></strong>
        </div>
    </div>

    <div class="grid">
        <div class="box"><small>Customer Name</small><strong><?= e($user["name"] ?? "-") ?></strong></div>
        <div class="box"><small>Customer Email</small><strong><?= e($user["email"] ?? "-") ?></strong></div>
        <div class="box"><small>Pickup Location</small><strong><?= e($booking["pickup_location"] ?? "-") ?></strong></div>
        <div class="box"><small>Drop-off Location</small><strong><?= e($booking["return_location"] ?? "-") ?></strong></div>
        <div class="box"><small>Booking Status</small><strong><?= e($booking["booking_status"] ?? $booking["status"] ?? "-") ?></strong></div>
        <div class="box"><small>Payment Status</small><strong><?= e($booking["payment_status"] ?? "-") ?></strong></div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Car</th>
                <th>Pickup Date / Time</th>
                <th>Return Date / Time</th>
                <th>Days</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php if(!empty($items)): ?>
                <?php foreach($items as $item): ?>
                    <tr>
                        <td><?= e($item["car_name"] ?? "Rental Car") ?></td>
                        <td><?= e($item["start_datetime"] ?? "-") ?></td>
                        <td><?= e($item["end_datetime"] ?? "-") ?></td>
                        <td><?= e($item["rental_days"] ?? $item["days"] ?? "-") ?></td>
                        <td>RM <?= e(number_format((float)($item["subtotal"] ?? 0),2)) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td>Rental Booking</td>
                    <td>-</td>
                    <td>-</td>
                    <td>-</td>
                    <td>RM <?= e(number_format((float)$total,2)) ?></td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="total">Total Amount: RM <?= e(number_format((float)$total,2)) ?></div>

    <div class="actions">
        <a class="btn" href="javascript:window.print()">Print Receipt</a>
        <a class="btn" href="booking_receipt.php?booking_id=<?= e($booking_id) ?>&download=1">Download HTML Invoice</a>
        <a class="btn" href="my_profile.php?tab=bookings">Back to My Booking</a>
    </div>
</div>
</body>
</html>
