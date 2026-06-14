<?php
require_once "config.php";

function e($v){ return htmlspecialchars((string)$v, ENT_QUOTES, "UTF-8"); }
function money($v){ return "RM " . number_format((float)$v, 2); }
function dtLabel($v){ $t=strtotime((string)$v); return $t?date("d M Y, h:i A",$t):"-"; }

function tableExists($conn,$table){
    $stmt=$conn->prepare("SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $stmt->bind_param("s",$table);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["total"]??0)>0;
}

function columnExists($conn,$table,$column){
    $stmt=$conn->prepare("SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $stmt->bind_param("ss",$table,$column);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)($row["total"]??0)>0;
}

function firstColumn($conn,$table,$columns,$fallback=null){
    foreach($columns as $c){
        if(columnExists($conn,$table,$c)) return $c;
    }
    return $fallback;
}

function fetchRows($conn,$sql,$types="",$params=[]){
    if($types!=="" && $params){
        $stmt=$conn->prepare($sql);
        if(!$stmt) return [];
        $stmt->bind_param($types,...$params);
        $stmt->execute();
        $res=$stmt->get_result();
        $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[];
        $stmt->close();
        return $rows;
    }
    $res=$conn->query($sql);
    return $res?$res->fetch_all(MYSQLI_ASSOC):[];
}

function fetchOne($conn,$sql,$types="",$params=[]){
    $rows=fetchRows($conn,$sql,$types,$params);
    return $rows[0]??null;
}

function getNavCartCount($conn){
    if(empty($_SESSION["user_id"]) || !tableExists($conn,"cart_items")) return 0;

    $stmt=$conn->prepare("
        SELECT COUNT(*) AS total
        FROM cart_items
        WHERE user_id=?
        AND LOWER(COALESCE(status,'active')) NOT IN ('removed','checked_out')
    ");

    if(!$stmt) return 0;

    $uid=(int)$_SESSION["user_id"];
    $stmt->bind_param("i",$uid);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    $stmt->close();

    return (int)($row["total"]??0);
}

function maskIc($ic){
    $ic=trim((string)$ic);
    if($ic==="") return "-";
    if(strlen($ic)<=6) return str_repeat("*", strlen($ic));
    return substr($ic,0,6) . "-**-" . substr($ic,-4);
}

function maskLicense($license){
    $license=trim((string)$license);
    if($license==="") return "-";
    if(strlen($license)<=4) return str_repeat("*", strlen($license));
    return substr($license,0,1) . str_repeat("*", max(3, strlen($license)-4)) . substr($license,-3);
}

function statusKey($status){
    $s=strtolower(trim((string)$status));
    $s=str_replace([" ","-"],"_",$s);
    if($s==="pending" || $s==="pending_approval" || $s==="waiting_admin_approval") return "pending_admin_approval";
    return $s;
}

// ========== 修复点 1：让 Customer 页面认识 active (Handover) 状态 ==========
function statusLabel($status){
    $key=statusKey($status);
    $map=[
        "pending_admin_approval"=>"Waiting Approval",
        "approved"=>"Approved",
        "active"=>"On The Road", // 拿到车后显示这个
        "rejected"=>"Rejected",
        "completed"=>"Completed",
        "cancelled"=>"Cancelled",
        "paid"=>"Paid",
        "success"=>"Success",
        "pending"=>"Pending",
        "failed"=>"Failed",
        "refunded"=>"Refunded"
    ];
    return $map[$key] ?? ucwords(str_replace("_"," ",$key));
}

// ========== 修复点 2：给 active 状态分配绿色的 UI 样式 ==========
function statusClass($status){
    $key=statusKey($status);
    if(in_array($key,["approved","active","paid","completed","success"],true)) return "status-green";
    if(in_array($key,["rejected","failed","cancelled"],true)) return "status-red";
    if(in_array($key,["refunded"],true)) return "status-grey";
    if(in_array($key,["pending","pending_admin_approval"],true)) return "status-orange";
    return "status-blue";
}

function addonList($raw){
    $raw=(string)$raw;
    if(trim($raw)==="") return [];
    $decoded=json_decode($raw,true);
    if(is_array($decoded)){
        return array_values(array_filter(array_map("strval",$decoded)));
    }
    $parts=preg_split("/[,|]+/",$raw);
    return array_values(array_filter(array_map("trim",$parts)));
}

if(empty($_SESSION["user_id"])){
    header("Location: login.php");
    exit;
}

$userId=(int)$_SESSION["user_id"];
$bookingId=(int)($_GET["booking_id"]??0);
$user=null;

if(tableExists($conn,"users")){
    $userIdCol=firstColumn($conn,"users",["user_id","id"],"user_id");
    $user=fetchOne($conn,"SELECT * FROM users WHERE $userIdCol=? LIMIT 1","i",[$userId]);
}

$navCartCount=getNavCartCount($conn);

if($bookingId<=0 || !tableExists($conn,"bookings")){
    die("Booking not found.");
}

$bookingIdCol=firstColumn($conn,"bookings",["booking_id","id"],"booking_id");
$booking=fetchOne($conn,"SELECT * FROM bookings WHERE $bookingIdCol=? AND user_id=? LIMIT 1","ii",[$bookingId,$userId]);

if(!$booking){
    die("Booking not found.");
}

$reference=$booking["booking_reference"]??("KH".str_pad((string)$bookingId,6,"0",STR_PAD_LEFT));
$bookingStatus=$booking["booking_status"]??($booking["status"]??"pending_admin_approval");
$paymentStatus="success";
$adminNote=$booking["admin_note"]??($booking["rejection_reason"]??($booking["note"]??""));
$bookingDate=$booking["created_at"]??($booking["booking_date"]??"");

$items=[];
if(tableExists($conn,"booking_items")){
    $brandSelect="'-' AS brand";
    $brandJoin="";
    if(tableExists($conn,"brands") && columnExists($conn,"cars","brand_id")){
        $brandSelect="COALESCE(b.brand_name,'-') AS brand";
        $brandJoin=" LEFT JOIN brands b ON b.brand_id=c.brand_id ";
    }elseif(columnExists($conn,"cars","brand")){
        $brandSelect="COALESCE(c.brand,'-') AS brand";
    }

    $categorySelect="'Others' AS category_name";
    $categoryJoin="";
    if(tableExists($conn,"categories") && columnExists($conn,"cars","category_id")){
        $categorySelect="COALESCE(cat.category_name,'Others') AS category_name";
        $categoryJoin=" LEFT JOIN categories cat ON cat.category_id=c.category_id ";
    }elseif(tableExists($conn,"vehicle_categories") && columnExists($conn,"cars","category_id")){
        $categorySelect="COALESCE(cat.category_name,'Others') AS category_name";
        $categoryJoin=" LEFT JOIN vehicle_categories cat ON cat.category_id=c.category_id ";
    }elseif(columnExists($conn,"cars","type")){
        $categorySelect="COALESCE(c.type,'Others') AS category_name";
    }

    $carNameCol=firstColumn($conn,"cars",["car_name","name"],"car_name");

    $plateSelect="'Assigned after approval' AS plate_number";
    $unitJoin="";
    if(tableExists($conn,"car_units") && columnExists($conn,"booking_items","unit_id")){
        $unitIdCol=firstColumn($conn,"car_units",["unit_id","id"],"unit_id");
        $plateCol=firstColumn($conn,"car_units",["plate_number","plate_no","car_plate"],null);

        if($plateCol){
            $plateSelect="COALESCE(cu.$plateCol,'Assigned after approval') AS plate_number";
            $unitJoin=" LEFT JOIN car_units cu ON cu.$unitIdCol=bi.unit_id ";
        }
    }

    $items=fetchRows($conn,"
        SELECT 
            bi.*,
            c.$carNameCol AS car_name,
            $brandSelect,
            $categorySelect,
            $plateSelect,
            COALESCE(rs.state_name,'-') AS pickup_state_name,
            COALESCE(pl.location_name,'-') AS pickup_location_name,
            COALESCE(dl.location_name,'-') AS dropoff_location_name
        FROM booking_items bi
        LEFT JOIN cars c ON c.car_id=bi.car_id
        $brandJoin
        $categoryJoin
        $unitJoin
        LEFT JOIN rental_states rs ON rs.state_id=bi.pickup_state_id
        LEFT JOIN rental_locations pl ON pl.location_id=bi.pickup_location
        LEFT JOIN rental_locations dl ON dl.location_id=bi.dropoff_location
        WHERE bi.booking_id=?
        ORDER BY bi.booking_item_id ASC
    ","i",[$bookingId]);
}

$payment=tableExists($conn,"payments")?fetchOne($conn,"SELECT * FROM payments WHERE booking_id=? ORDER BY payment_id DESC LIMIT 1","i",[$bookingId]):null;

$carTotal=0;
$servicesTotal=0;
$insuranceTotal=0;
$driverAgeTotal=0;
$addonsTotal=0;
$fuelTotal=0;

foreach($items as $it){
    $carTotal+=(float)($it["subtotal"]??0);
    $servicesTotal+=(float)($it["extra_services_total"]??0);
    $insuranceTotal+=(float)($it["insurance_charge"]??0);
    $driverAgeTotal+=(float)($it["driver_age_charge"]??0);
    $addonsTotal+=(float)($it["addon_services_charge"]??0);
    $fuelTotal+=(float)($it["fuel_charge"]??0);
}

$discount=(float)($booking["voucher_discount"]??0);
$grand=(float)($booking["grand_total"]??($booking["total_amount"]??($carTotal+$servicesTotal-$discount)));
$paymentMethod=$payment["payment_method"]??($booking["payment_method"]??"-");
$paymentDate=$payment["payment_date"]??($booking["created_at"]??"");
$transactionRef=$payment["transaction_reference"]??($payment["transaction_id"]??($payment["payment_reference"]??("-")));
$bookingStatusKey=statusKey($bookingStatus);

// ========== 修复点 3：确保 Timeline 在 active 状态时正确点亮 ==========
$timeline=[
    ["title"=>"Payment Success","desc"=>"Receipt has been generated.","icon"=>"fa-check","state"=>"done"],
    ["title"=>"Booking Submitted","desc"=>"Booking request has been created.","icon"=>"fa-paper-plane","state"=>"done"],
    ["title"=>"Waiting Approval","desc"=>"Admin will review this booking.","icon"=>"fa-user-shield","state"=>$bookingStatusKey==="pending_admin_approval"?"active":(in_array($bookingStatusKey,["approved","active","completed"],true)?"done":($bookingStatusKey==="rejected"?"rejected":"waiting"))],
    ["title"=>statusLabel($bookingStatus),"desc"=>$bookingStatusKey==="rejected"?"Booking rejected by admin.":($bookingStatusKey==="approved"?"Booking approved and ready for pickup.":($bookingStatusKey==="active"?"Vehicle handed over. Have a safe journey!":($bookingStatusKey==="completed"?"Rental completed.":"Latest booking status."))),"icon"=>$bookingStatusKey==="rejected"?"fa-xmark":($bookingStatusKey==="active"?"fa-car-side":"fa-clipboard-check"),"state"=>in_array($bookingStatusKey,["approved","active","completed"],true)?"done":($bookingStatusKey==="rejected"?"rejected":"waiting")]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Booking Details | KH Car Rental</title>
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
    --border:#d8ecfb;
    --green:#16a765;
    --orange:#ff7a1a;
    --orange2:#f15f12;
    --red:#e2453b;
    --grey:#7b8794;
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
        radial-gradient(circle at 95% 8%,rgba(234,247,255,.48),transparent 34%),
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
    position:relative;
}
.nav-links a i{color:#2b4969;font-size:13px}
.nav-links a.active,
.nav-links a.active i,
.nav-links a:hover,
.nav-links a:hover i{color:var(--sky600)}
.cart-badge{
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

.page{
    width:min(1120px,100%);
    margin:18px auto 58px;
    padding:0 22px;
}
.simple-header,
.invoice,
.status-card,
.info-card,
.trip-card{
    border-radius:30px;
    background:
        radial-gradient(circle at 100% 0%,rgba(184,228,255,.16),transparent 30%),
        linear-gradient(135deg,rgba(255,255,255,.98),rgba(247,253,255,.94));
    border:1px solid rgba(184,228,255,.92);
    box-shadow:var(--shadow);
}
.simple-header{
    padding:24px 28px;
    margin-bottom:18px;
    display:grid;
    grid-template-columns:1fr auto;
    gap:18px;
    align-items:center;
}
.simple-header h1{
    font-size:clamp(34px,4vw,50px);
    line-height:1;
    letter-spacing:-1.5px;
    font-weight:950;
    margin-bottom:8px;
}
.simple-header p{
    color:var(--muted);
    font-weight:750;
}
.status-pair{
    display:flex;
    gap:10px;
    flex-wrap:wrap;
    justify-content:flex-end;
}
.status-badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:10px 14px;
    border-radius:999px;
    font-weight:950;
    font-size:12px;
    white-space:nowrap;
}
.status-green{background:#f0fff8;color:#087747;border:1px solid rgba(20,184,116,.22)}
.status-orange{background:#fff8ed;color:#c25100;border:1px solid rgba(255,122,26,.25)}
.status-red{background:#fff4f2;color:#b42318;border:1px solid rgba(244,67,54,.22)}
.status-blue{background:var(--sky100);color:var(--sky600);border:1px solid rgba(40,168,234,.22)}
.status-grey{background:#f3f5f7;color:#667085;border:1px solid rgba(102,112,133,.18)}

.invoice{
    padding:34px;
    margin-bottom:18px;
}
.invoice-top{
    display:grid;
    grid-template-columns:1fr auto;
    gap:20px;
    align-items:start;
    padding-bottom:22px;
    border-bottom:2px solid var(--border);
}
.invoice-brand{
    display:flex;
    gap:14px;
    align-items:center;
}
.invoice-logo{
    width:58px;
    height:58px;
    border-radius:20px;
    display:grid;
    place-items:center;
    color:var(--sky600);
    background:linear-gradient(135deg,#d8f2ff,#fff);
    border:1px solid rgba(142,207,244,.46);
    font-size:24px;
}
.invoice-brand h2{
    font-size:27px;
    font-weight:950;
    letter-spacing:-.6px;
}
.invoice-brand p{
    color:var(--muted);
    font-weight:750;
    margin-top:3px;
}
.invoice-meta{
    min-width:260px;
    text-align:right;
}
.invoice-meta h1{
    font-size:42px;
    line-height:1;
    letter-spacing:-1.2px;
    font-weight:950;
    color:var(--sky600);
    margin-bottom:10px;
}
.meta-line{
    display:flex;
    justify-content:space-between;
    gap:14px;
    font-size:13px;
    padding:5px 0;
    color:#2b4969;
    font-weight:850;
}
.meta-line strong{
    color:var(--dark);
    font-weight:950;
}
.bill-row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:16px;
    padding:22px 0;
    border-bottom:1px solid var(--border);
}
.bill-box{
    padding:16px;
    border-radius:20px;
    background:rgba(255,255,255,.76);
    border:1px solid var(--border);
}
.bill-box span{
    display:block;
    color:var(--muted);
    font-size:10.5px;
    font-weight:950;
    letter-spacing:.7px;
    text-transform:uppercase;
    margin-bottom:8px;
}
.bill-box h3{
    font-size:18px;
    font-weight:950;
    margin-bottom:4px;
}
.bill-box p{
    color:#2b4969;
    font-size:13px;
    line-height:1.6;
    font-weight:750;
}
.invoice-table{
    width:100%;
    border-collapse:collapse;
    margin-top:22px;
}
.invoice-table th{
    text-align:left;
    padding:13px 10px;
    color:var(--muted);
    font-size:10.5px;
    letter-spacing:.7px;
    text-transform:uppercase;
    border-bottom:2px solid var(--border);
}
.invoice-table td{
    padding:15px 10px;
    vertical-align:top;
    border-bottom:1px solid var(--border);
    font-size:13.5px;
    font-weight:800;
}
.invoice-table .right{
    text-align:right;
    white-space:nowrap;
}
.item-title{
    font-size:15px;
    font-weight:950;
    color:var(--dark);
    margin-bottom:4px;
}
.item-desc{
    color:var(--muted);
    font-size:12.5px;
    line-height:1.5;
    font-weight:750;
}
.addon-list{
    margin:6px 0 0 17px;
    color:var(--muted);
    font-size:12.5px;
    line-height:1.55;
    font-weight:750;
}
.invoice-total{
    display:grid;
    grid-template-columns:1fr 360px;
    gap:18px;
    margin-top:20px;
    align-items:start;
}
.payment-note{
    padding:16px;
    border-radius:20px;
    background:rgba(234,247,255,.72);
    border:1px solid var(--border);
}
.payment-note span{
    display:block;
    color:var(--muted);
    font-size:10.5px;
    font-weight:950;
    letter-spacing:.7px;
    text-transform:uppercase;
    margin-bottom:8px;
}
.payment-note p{
    color:#2b4969;
    font-size:13px;
    font-weight:800;
    line-height:1.6;
}
.total-box{
    padding:16px;
    border-radius:20px;
    background:rgba(255,255,255,.84);
    border:1px solid var(--border);
}
.total-line{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:12px;
    padding:9px 0;
    border-bottom:1px solid var(--border);
    color:#2b4969;
    font-weight:850;
}
.total-line span{
    min-width:0;
}
.total-line strong{
    color:var(--dark);
    font-weight:950;
    white-space:nowrap;
    text-align:right;
}
.total-line.voucher-line{
    align-items:flex-start;
}
.total-line.voucher-line span{
    display:flex;
    flex-direction:column;
    gap:3px;
    line-height:1.2;
}
.total-line.voucher-line .voucher-code-label{
    width:max-content;
    max-width:100%;
    padding:4px 9px;
    border-radius:999px;
    background:rgba(40,168,234,.10);
    color:var(--sky600);
    font-size:11px;
    font-weight:950;
    letter-spacing:.4px;
}
.total-line.grand{
    border-bottom:0;
    padding-top:14px;
    align-items:flex-end;
}
.total-line.grand span{
    font-size:17px;
    font-weight:950;
    color:var(--dark);
}
.total-line.grand strong{
    font-size:28px;
    color:var(--sky600);
}
.print-row{
    display:flex;
    gap:10px;
    justify-content:flex-end;
    margin-top:18px;
    flex-wrap:wrap;
}
.btn{
    min-height:42px;
    border-radius:15px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:9px;
    padding:0 16px;
    font-size:13px;
    font-weight:950;
    border:0;
    cursor:pointer;
    transition:.24s;
    position:relative;
    overflow:hidden;
}
.btn::before{
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
.btn:hover::before{left:130%}
.btn:hover{
    transform:translateY(-2px);
    box-shadow:var(--soft);
}
.btn-blue{background:linear-gradient(135deg,var(--sky500),var(--sky600));color:#fff}
.btn-white{background:#fff;color:var(--sky600);border:1px solid var(--border)}

.status-card,
.info-card,
.trip-card{
    padding:24px;
    margin-bottom:18px;
}
.section-title{
    display:flex;
    align-items:center;
    gap:12px;
    margin-bottom:16px;
}
.section-title i{
    width:42px;
    height:42px;
    border-radius:16px;
    display:grid;
    place-items:center;
    background:var(--sky100);
    color:var(--sky600);
}
.section-title h2{
    font-size:24px;
    font-weight:950;
    letter-spacing:-.5px;
}
.timeline{
    display:grid;
    grid-template-columns:repeat(4,1fr);
    gap:12px;
}
.timeline-step{
    min-height:118px;
    padding:15px;
    border-radius:22px;
    background:rgba(255,255,255,.82);
    border:1px solid var(--border);
    box-shadow:var(--soft);
}
.timeline-icon{
    width:40px;
    height:40px;
    border-radius:15px;
    display:grid;
    place-items:center;
    margin-bottom:10px;
    color:var(--sky600);
    background:var(--sky100);
}
.timeline-step.done .timeline-icon{color:#fff;background:linear-gradient(135deg,#19bc78,#079455)}
.timeline .timeline-step:nth-child(3) .timeline-icon{color:#fff!important;background:linear-gradient(135deg,#ffc107,#ff9800)!important;}
.timeline-step.active .timeline-icon{color:#fff;background:linear-gradient(135deg,#ffc107,#ff9800)}
.timeline-step.rejected .timeline-icon{color:#fff;background:linear-gradient(135deg,#ff6b5f,#d92d20)}
.timeline-step h3{
    font-size:14px;
    font-weight:950;
    margin-bottom:5px;
}
.timeline-step p{
    color:var(--muted);
    font-size:12px;
    line-height:1.4;
    font-weight:700;
}
.info-grid{
    display:grid;
    grid-template-columns:repeat(3,1fr);
    gap:10px;
}
.info-box{
    min-height:70px;
    border-radius:17px;
    background:rgba(255,255,255,.88);
    border:1px solid var(--border);
    padding:13px;
}
.info-box span{
    display:block;
    color:var(--muted);
    font-size:10px;
    font-weight:950;
    letter-spacing:.55px;
    text-transform:uppercase;
    margin-bottom:5px;
}
.info-box strong{
    display:block;
    font-size:14px;
    font-weight:950;
    line-height:1.25;
}
.trip-car{
    border-radius:22px;
    background:rgba(255,255,255,.78);
    border:1px solid var(--border);
    padding:16px;
    margin-top:12px;
}
.trip-car h3{
    font-size:20px;
    font-weight:950;
    margin-bottom:5px;
}
.trip-car p{
    color:var(--muted);
    font-weight:850;
    margin-bottom:12px;
}
.admin-note{
    margin-top:12px;
    padding:14px;
    border-radius:18px;
    font-size:13px;
    line-height:1.5;
    font-weight:800;
}
.note-orange{color:#c25100;background:#fff8ed;border:1px solid rgba(255,122,26,.25)}
.note-red{color:#b42318;background:#fff4f2;border:1px solid rgba(244,67,54,.22)}

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
.footer .start-btn{
    margin-top:22px;
    width:fit-content;
    min-width:210px;
    height:44px;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:10px;
    padding:0 28px;
    border-radius:16px;
    background:linear-gradient(135deg,var(--sky500),var(--sky600));
    color:#ffffff;
    font-size:15.5px;
    font-weight:950;
    line-height:1;
    letter-spacing:.15px;
    box-shadow:0 18px 36px rgba(40,168,234,.25);
    border:1px solid rgba(113,210,255,.18);
}
.footer-bottom{
    width:min(1200px,calc(100% - 40px));
    margin:0 auto;
    padding-top:22px;
    border-top:1px solid rgba(255,255,255,.14);
    text-align:center;
    color:rgba(218,235,248,.86);
    font-size:14px;
}

@media print{
    @page{
        size:A4 landscape;
        margin:5mm;
    }

    .navbar,
    .footer,
    .simple-header,
    .status-card,
    .info-card,
    .trip-card,
    .print-row{
        display:none!important;
    }

    html,
    body{
        width:100%!important;
        height:auto!important;
        margin:0!important;
        padding:0!important;
        background:#fff!important;
        overflow:hidden!important;
    }

    .page{
        width:100%!important;
        max-width:none!important;
        margin:0!important;
        padding:0!important;
    }

    .invoice{
        display:block!important;
        width:100%!important;
        max-width:none!important;
        height:auto!important;
        min-height:0!important;
        margin:0!important;
        padding:14px 18px!important;
        border:1px solid rgba(184,228,255,.92)!important;
        border-radius:18px!important;
        box-shadow:none!important;
        background:
            radial-gradient(circle at 100% 0%,rgba(184,228,255,.16),transparent 30%),
            linear-gradient(135deg,rgba(255,255,255,.98),rgba(247,253,255,.94))!important;
        page-break-after:avoid!important;
        page-break-before:avoid!important;
        page-break-inside:avoid!important;
        break-inside:avoid!important;
        transform:scale(.86);
        transform-origin:top left;
        width:116.2%!important;
    }

    .invoice-top{
        display:grid!important;
        grid-template-columns:1fr auto!important;
        gap:12px!important;
        align-items:start!important;
        padding-bottom:12px!important;
        border-bottom:1px solid var(--border)!important;
    }

    .invoice-logo{
        width:44px!important;
        height:44px!important;
        border-radius:15px!important;
        font-size:18px!important;
    }

    .invoice-brand h2{
        font-size:20px!important;
    }

    .invoice-brand p{
        font-size:11px!important;
    }

    .invoice-meta{
        min-width:230px!important;
        text-align:right!important;
    }

    .invoice-meta h1{
        font-size:34px!important;
        margin-bottom:6px!important;
    }

    .meta-line{
        font-size:10.5px!important;
        padding:3px 0!important;
    }

    .bill-row{
        display:grid!important;
        grid-template-columns:1fr 1fr!important;
        gap:10px!important;
        padding:12px 0!important;
        border-bottom:1px solid var(--border)!important;
    }

    .bill-box{
        padding:10px 12px!important;
        border-radius:14px!important;
    }

    .bill-box span,
    .payment-note span{
        font-size:9px!important;
        margin-bottom:5px!important;
    }

    .bill-box h3{
        font-size:15px!important;
        margin-bottom:2px!important;
    }

    .bill-box p{
        font-size:10.5px!important;
        line-height:1.35!important;
    }

    .invoice-table{
        width:100%!important;
        border-collapse:collapse!important;
        margin-top:12px!important;
        page-break-inside:avoid!important;
        break-inside:avoid!important;
    }

    .invoice-table th{
        padding:8px 8px!important;
        font-size:9px!important;
        border-bottom:1px solid var(--border)!important;
    }

    .invoice-table td{
        padding:9px 8px!important;
        font-size:11px!important;
        border-bottom:1px solid var(--border)!important;
        page-break-inside:avoid!important;
        break-inside:avoid!important;
    }

    .item-title{
        font-size:12.5px!important;
        margin-bottom:2px!important;
    }

    .item-desc,
    .addon-list{
        font-size:10px!important;
        line-height:1.3!important;
    }

    .invoice-total{
        display:grid!important;
        grid-template-columns:1fr 320px!important;
        gap:10px!important;
        margin-top:12px!important;
        align-items:start!important;
        page-break-inside:avoid!important;
        break-inside:avoid!important;
    }

    .payment-note,
    .total-box{
        padding:10px 12px!important;
        border-radius:14px!important;
        page-break-inside:avoid!important;
        break-inside:avoid!important;
    }

    .payment-note p{
        font-size:10.5px!important;
        line-height:1.35!important;
    }

    .total-line{
        padding:6px 0!important;
        font-size:11.5px!important;
    }

    .total-line.grand{
        padding-top:8px!important;
    }

    .total-line.grand span{
        font-size:13px!important;
    }

    .total-line.grand strong{
        font-size:22px!important;
    }

    .invoice *{
        -webkit-print-color-adjust:exact!important;
        print-color-adjust:exact!important;
    }
}
@media(max-width:900px){
    .simple-header,
    .invoice-top,
    .bill-row,
    .invoice-total{grid-template-columns:1fr}
    .invoice-meta{text-align:left}
    .timeline,
    .info-grid{grid-template-columns:1fr}
    .nav-links{display:none}
    .footer-inner{grid-template-columns:1fr;gap:34px}
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
            <li>
                <a href="cart.php">
                    <i class="fa-solid fa-cart-shopping"></i> CART
                    <?php if($navCartCount>0): ?>
                        <span class="cart-badge"><?= e($navCartCount>99?'99+':$navCartCount) ?></span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>

        <div class="avatar-wrap">
            <?php if($user): ?>
                <button class="avatar-btn" type="button" id="avatarBtn">
                    <span class="avatar-circle">
                        <?php if(!empty($user["profile_picture"])): ?>
                            <img src="<?= e($user["profile_picture"]) ?>" alt="Profile">
                        <?php else: ?>
                            <?= e(strtoupper(substr($user["name"]??"U",0,1))) ?>
                        <?php endif; ?>
                    </span>
                    <i class="fa-solid fa-chevron-down"></i>
                </button>
                <div class="dropdown" id="profileDropdown">
                    <a href="my_profile.php"><i class="fa-solid fa-user"></i> Manage My Profile</a>
                    <a href="my_profile.php?tab=bookings"><i class="fa-solid fa-calendar-check"></i> My Bookings</a>
                    <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="page">
    <section class="simple-header">
        <div>
            <h1>Booking Details</h1>
            <p>Receipt and booking approval status for your KH Car Rental booking.</p>
        </div>

        <div class="status-pair">
            <span class="status-badge status-green"><i class="fa-solid fa-circle-check"></i> Payment Status: Success</span>
            <span class="status-badge <?= e(statusClass($bookingStatus)) ?>"><i class="fa-solid fa-clock"></i> Booking Status: <?= e(statusLabel($bookingStatus)) ?></span>
        </div>
    </section>

    <section class="invoice">
        <div class="invoice-top">
            <div class="invoice-brand">
                <div class="invoice-logo"><i class="fa-solid fa-car-side"></i></div>
                <div>
                    <h2>KH Car Rental</h2>
                    <p>Official Rental Invoice</p>
                </div>
            </div>

            <div class="invoice-meta">
                <h1>INVOICE</h1>
                <div class="meta-line"><span>Invoice No.</span><strong><?= e($reference) ?></strong></div>
                <div class="meta-line"><span>Booking Date</span><strong><?= e(dtLabel($bookingDate)) ?></strong></div>
                <div class="meta-line"><span>Payment Date</span><strong><?= e(dtLabel($paymentDate)) ?></strong></div>
            </div>
        </div>

        <div class="bill-row">
            <div class="bill-box">
                <span>Bill To</span>
                <h3><?= e($user["name"] ?? "-") ?></h3>
                <p><?= e($user["email"] ?? "-") ?><br><?= e($user["phone"] ?? "-") ?></p>
            </div>

            <div class="bill-box">
                <span>Payment Information</span>
                <h3><?= e($paymentMethod) ?></h3>
                <p>Transaction Reference: <?= e($transactionRef) ?><br>Payment Status: Success</p>
            </div>
        </div>

        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="right">Qty / Days</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($items as $item): ?>
                    <?php
                        $addons=addonList($item["addon_services"]??"");
                        $days=(int)($item["rental_days"]??1);
                    ?>
                    <tr>
                        <td>
                            <div class="item-title"><?= e($item["car_name"] ?? "Rental Car") ?></div>
                            <div class="item-desc">
                                <?= e($item["brand"] ?? "-") ?> • <?= e($item["category_name"] ?? "-") ?><br>
                                <?= e($item["pickup_location_name"] ?? "-") ?> → <?= e($item["dropoff_location_name"] ?? "-") ?><br>
                                <?= e(dtLabel($item["start_datetime"] ?? "")) ?> - <?= e(dtLabel($item["end_datetime"] ?? "")) ?>
                            </div>
                        </td>
                        <td class="right"><?= e($days) ?> day(s)</td>
                        <td class="right"><?= e(money($item["price_per_day"] ?? 0)) ?></td>
                        <td class="right"><strong><?= e(money($item["subtotal"] ?? 0)) ?></strong></td>
                    </tr>

                    <?php if((float)($item["insurance_charge"]??0)>0): ?>
                        <tr>
                            <td>
                                <div class="item-title">Insurance Package</div>
                                <div class="item-desc"><?= e($item["insurance_package"] ?? "Basic Coverage") ?> • <?= e($item["car_name"] ?? "Rental Car") ?></div>
                            </td>
                            <td class="right">-</td>
                            <td class="right">-</td>
                            <td class="right"><?= e(money($item["insurance_charge"] ?? 0)) ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if((float)($item["driver_age_charge"]??0)>0): ?>
                        <tr>
                            <td>
                                <div class="item-title">Driver Age Surcharge</div>
                                <div class="item-desc"><?= e($item["driver_age_group"] ?? "25–69 years") ?> • <?= e($item["car_name"] ?? "Rental Car") ?></div>
                            </td>
                            <td class="right">-</td>
                            <td class="right">-</td>
                            <td class="right"><?= e(money($item["driver_age_charge"] ?? 0)) ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if((float)($item["addon_services_charge"]??0)>0 || $addons): ?>
                        <tr>
                            <td>
                                <div class="item-title">Add-on Services</div>
                                <div class="item-desc"><?= e($item["car_name"] ?? "Rental Car") ?></div>
                                <?php if($addons): ?>
                                    <ul class="addon-list">
                                        <?php foreach($addons as $add): ?>
                                            <li><?= e($add) ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="item-desc">No optional add-on selected.</div>
                                <?php endif; ?>
                            </td>
                            <td class="right">-</td>
                            <td class="right">-</td>
                            <td class="right"><?= e(money($item["addon_services_charge"] ?? 0)) ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if((float)($item["fuel_charge"]??0)>0): ?>
                        <tr>
                            <td>
                                <div class="item-title">Fuel Option</div>
                                <div class="item-desc"><?= e($item["car_name"] ?? "Rental Car") ?></div>
                            </td>
                            <td class="right">-</td>
                            <td class="right">-</td>
                            <td class="right"><?= e(money($item["fuel_charge"] ?? 0)) ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="invoice-total">
            <div class="payment-note">
                <span>Booking Note</span>
                <p>Your receipt is successfully generated. Your booking is currently <strong><?= e(statusLabel($bookingStatus)) ?></strong> and will be updated after admin review.</p>
            </div>

            <div class="total-box">
                <div class="total-line"><span>Car Rental Total</span><strong><?= e(money($carTotal)) ?></strong></div>
                <div class="total-line"><span>Extra Protection & Services</span><strong><?= e(money($servicesTotal)) ?></strong></div>
                <div class="total-line voucher-line">
                    <span>
                        Voucher Discount
                        <?php if(!empty($booking["promo_code"])): ?>
                            <em class="voucher-code-label"><?= e($booking["promo_code"]) ?></em>
                        <?php endif; ?>
                    </span>
                    <strong>-<?= e(money($discount)) ?></strong>
                </div>
                <div class="total-line grand"><span>Grand Total</span><strong><?= e(money($grand)) ?></strong></div>
            </div>
        </div>

        <div class="print-row">
            <button onclick="window.print()" class="btn btn-blue"><i class="fa-solid fa-print"></i> Print Invoice</button>
            <a href="my_profile.php?tab=bookings" class="btn btn-white"><i class="fa-solid fa-calendar-check"></i> My Bookings</a>
            <a href="catalogue.php" class="btn btn-white"><i class="fa-solid fa-car"></i> Browse More Cars</a>
        </div>
    </section>

    <section class="status-card">
        <div class="section-title">
            <i class="fa-solid fa-route"></i>
            <h2>Status Timeline</h2>
        </div>

        <div class="timeline">
            <?php foreach($timeline as $step): ?>
                <div class="timeline-step <?= e($step["state"]) ?>">
                    <div class="timeline-icon"><i class="fa-solid <?= e($step["icon"]) ?>"></i></div>
                    <h3><?= e($step["title"]) ?></h3>
                    <p><?= e($step["desc"]) ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($bookingStatusKey==="pending_admin_approval"): ?>
            <div class="admin-note note-orange">
                Your payment is successful. Please wait for admin approval before collecting the car.
            </div>
        <?php elseif($bookingStatusKey==="approved"): ?>
            <div class="admin-note note-orange">
                Your booking has been approved. Please bring your documents and booking reference during pickup.
            </div>
        <?php elseif($bookingStatusKey==="active"): ?>
            <div class="admin-note" style="color:#087747; background:#f0fff8; border:1px solid rgba(20,184,116,.22)">
                <i class="fas fa-car-side mr-2"></i> Vehicle successfully handed over. Have a safe and pleasant journey!
            </div>
        <?php elseif($bookingStatusKey==="rejected"): ?>
            <div class="admin-note note-red">
                This booking has been rejected. Please check the admin note or contact support.
            </div>
        <?php endif; ?>
        </section>

    <section class="info-card">
        <div class="section-title">
            <i class="fa-solid fa-user"></i>
            <h2>Customer Information</h2>
        </div>

        <div class="info-grid">
            <div class="info-box"><span>Customer Name</span><strong><?= e($user["name"] ?? "-") ?></strong></div>
            <div class="info-box"><span>Email</span><strong><?= e($user["email"] ?? "-") ?></strong></div>
            <div class="info-box"><span>Phone Number</span><strong><?= e($user["phone"] ?? "-") ?></strong></div>
            <div class="info-box"><span>IC Number</span><strong><?= e(maskIc($user["ic_number"] ?? "")) ?></strong></div>
            <div class="info-box"><span>Driving License Number</span><strong><?= e(maskLicense($user["license_number"] ?? "")) ?></strong></div>
            <div class="info-box"><span>Booking Reference</span><strong><?= e($reference) ?></strong></div>
        </div>
    </section>

    <section class="trip-card">
        <div class="section-title">
            <i class="fa-solid fa-bell"></i>
            <h2>Pickup Reminder</h2>
        </div>

        <div class="info-grid">
            <div class="info-box">
                <span>Required Document</span>
                <strong>Bring your IC / Passport</strong>
            </div>
            <div class="info-box">
                <span>Required License</span>
                <strong>Bring your Driving License</strong>
            </div>
            <div class="info-box">
                <span>Payment Proof</span>
                <strong>Show payment confirmation</strong>
            </div>
            <div class="info-box">
                <span>Booking Reference</span>
                <strong><?= e($reference) ?></strong>
            </div>
            <div class="info-box">
                <span>Before Pickup</span>
                <strong>Please wait for admin approval</strong>
            </div>
            <div class="info-box">
                <span>Important Note</span>
                <strong>Arrive on time at the selected pickup location</strong>
            </div>
        </div>
    </section>
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
            <p><a href="homepage.php">HOME</a></p>
            <p><a href="catalogue.php">CATALOGUE</a></p>
            <p><a href="find_car_smart.php">FIND CAR SMART</a></p>
            <p><a href="compare_car.php">COMPARE CAR</a></p>
            <p><a href="aboutus.php">ABOUT US</a></p>
            <p><a href="contactus.php">CONTACT US</a></p>
            <p><a href="cart.php">CART</a></p>
        </div>
        <div>
            <h3>Contact</h3>
            <p><i class="fa-solid fa-phone"></i> +60 12-345 6789</p>
            <p><i class="fa-solid fa-envelope"></i> hoomenghui@student.mmu.edu.my</p>
            <p><i class="fa-solid fa-envelope"></i> pangkanghorng@student.mmu.edu.my</p>
            <p><i class="fa-solid fa-envelope"></i> ngmengxin@student.mmu.edu.my</p>
            <p><i class="fa-solid fa-location-dot"></i> Multimedia University, Melaka</p>
        </div>
    </div>
    <div class="footer-bottom">© 2026 KH Car Rental. All rights reserved.</div>
</footer>

<script>
const avatarBtn=document.getElementById("avatarBtn");
const profileDropdown=document.getElementById("profileDropdown");

if(avatarBtn&&profileDropdown){
    avatarBtn.addEventListener("click",e=>{
        e.stopPropagation();
        profileDropdown.classList.toggle("show");
    });

    document.addEventListener("click",()=>{
        profileDropdown.classList.remove("show");
    });
}
</script>
</body>
</html>