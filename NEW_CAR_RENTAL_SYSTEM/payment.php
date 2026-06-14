<?php
require_once "config.php";
require_once "terms_helpers.php";

function e($value){ return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8"); }
function money($value){ return "RM " . number_format((float)$value, 2); }
function dtLabel($value){ $t=strtotime((string)$value); return $t ? date("d M Y, h:i A", $t) : "-"; }
function tableExists($conn,$table){ $stmt=$conn->prepare("SELECT COUNT(*) AS total FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?"); $stmt->bind_param("s",$table); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close(); return (int)($row["total"]??0)>0; }
function columnExists($conn,$table,$column){ $stmt=$conn->prepare("SELECT COUNT(*) AS total FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?"); $stmt->bind_param("ss",$table,$column); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close(); return (int)($row["total"]??0)>0; }
function firstColumn($conn,$table,$columns,$fallback=null){ foreach($columns as $c){ if(columnExists($conn,$table,$c)) return $c; } return $fallback; }
function fetchRows($conn,$sql,$types="",$params=[]){ if($types!=="" && $params){ $stmt=$conn->prepare($sql); if(!$stmt) return []; $stmt->bind_param($types,...$params); $stmt->execute(); $res=$stmt->get_result(); $rows=$res?$res->fetch_all(MYSQLI_ASSOC):[]; $stmt->close(); return $rows; } $res=$conn->query($sql); return $res?$res->fetch_all(MYSQLI_ASSOC):[]; }
function fetchOne($conn,$sql,$types="",$params=[]){ $rows=fetchRows($conn,$sql,$types,$params); return $rows[0]??null; }
function getNavCartCount($conn){ $sessionCount=(!empty($_SESSION["cart"]) && is_array($_SESSION["cart"]))?count($_SESSION["cart"]):0; if(empty($_SESSION["user_id"]) || !tableExists($conn,"cart_items")) return $sessionCount; $stmt=$conn->prepare("SELECT COUNT(*) AS total FROM cart_items WHERE user_id=? AND LOWER(COALESCE(status,'active')) NOT IN ('removed','checked_out')"); if(!$stmt) return $sessionCount; $uid=(int)$_SESSION["user_id"]; $stmt->bind_param("i",$uid); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close(); return (int)($row["total"]??0); }
function resolveCarImageSrc($imagePath,$carName="Car Image"){
    $imagePath=trim((string)$imagePath);
    if($imagePath!=="" && preg_match('/^https?:\/\//i',$imagePath)) return $imagePath;
    if($imagePath!=="" && is_file(__DIR__."/".ltrim($imagePath,"/"))) return $imagePath;
    $safe=htmlspecialchars((string)$carName,ENT_QUOTES,"UTF-8");
    $svg='<svg xmlns="http://www.w3.org/2000/svg" width="900" height="520"><defs><linearGradient id="g" x1="0" x2="1" y1="0" y2="1"><stop offset="0" stop-color="#eaf7ff"/><stop offset="1" stop-color="#f8fcff"/></linearGradient></defs><rect width="900" height="520" fill="url(#g)"/><rect x="58" y="64" width="784" height="392" rx="34" fill="#fff" opacity=".72" stroke="#b8e4ff"/><path d="M198 318h475c32 0 58-24 58-54v-20c0-14-10-27-24-31l-96-28c-31-45-68-68-112-68H356c-54 0-94 24-128 70l-70 24c-17 6-29 22-29 40v13c0 30 26 54 69 54Z" fill="#1fa2df" opacity=".92"/><circle cx="271" cy="323" r="45" fill="#10233d"/><circle cx="624" cy="323" r="45" fill="#10233d"/><text x="450" y="92" text-anchor="middle" font-family="Segoe UI,Arial" font-size="28" font-weight="800" fill="#10233d">'.$safe.'</text></svg>';
    return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
}
function getCarImage($conn,$carId,$fallback="",$carName="Car Image"){
    if(tableExists($conn,"car_images")){
        $carCol=firstColumn($conn,"car_images",["car_id"],"car_id");
        $urlCol=firstColumn($conn,"car_images",["image_url","image","image_path"],"image_url");
        $sortCol=firstColumn($conn,"car_images",["sort_order"],null);
        $pk=firstColumn($conn,"car_images",["image_id","id"],null);
        $order=$sortCol?"ORDER BY $sortCol ASC":($pk?"ORDER BY $pk ASC":"");
        $row=fetchOne($conn,"SELECT $urlCol AS image_url FROM car_images WHERE $carCol=? $order LIMIT 1","i",[$carId]);
        if(!empty($row["image_url"])) return resolveCarImageSrc($row["image_url"],$carName);
    }
    return resolveCarImageSrc($fallback,$carName);
}
function hasBookingOverlap($conn,$carId,$unitId,$stateId,$start,$end){
    if(!tableExists($conn,"booking_items") || !tableExists($conn,"bookings")) return false;
    $where=["? < bi.end_datetime","? > bi.start_datetime"];
    $types="ss"; $params=[$start,$end];
    if($unitId>0){ $where[]="bi.unit_id=?"; $types.="i"; $params[]=$unitId; }
    else { $where[]="bi.car_id=?"; $types.="i"; $params[]=$carId; if($stateId>0){ $where[]="(bi.pickup_state_id=? OR bi.pickup_state_id IS NULL)"; $types.="i"; $params[]=$stateId; } }
    $sql="SELECT COUNT(*) AS total FROM booking_items bi INNER JOIN bookings b ON b.booking_id=bi.booking_id WHERE ".implode(" AND ",$where)." AND LOWER(COALESCE(b.booking_status,'pending')) NOT IN ('rejected','cancelled','cancellation requested') AND LOWER(COALESCE(b.payment_status,'pending')) NOT IN ('failed','refunded')";
    $row=fetchOne($conn,$sql,$types,$params); return (int)($row["total"]??0)>0;
}
function findAvailableUnit($conn,$carId,$stateId,$start,$end){
    if(!tableExists($conn,"car_units")) return 0;
    $unitIdCol=firstColumn($conn,"car_units",["unit_id","id"],"unit_id"); $unitCarCol=firstColumn($conn,"car_units",["car_id"],"car_id"); $unitStateCol=firstColumn($conn,"car_units",["state_id"],"state_id"); $unitStatusCol=firstColumn($conn,"car_units",["current_status","status"],null);
    $where=["$unitCarCol=?","$unitStateCol=?"]; $types="ii"; $params=[$carId,$stateId]; if($unitStatusCol) $where[]="LOWER(COALESCE($unitStatusCol,'available')) NOT IN ('maintenance','inactive')";
    $units=fetchRows($conn,"SELECT $unitIdCol AS unit_id FROM car_units WHERE ".implode(" AND ",$where),$types,$params);
    foreach($units as $u){ $uid=(int)$u["unit_id"]; if(!hasBookingOverlap($conn,$carId,$uid,$stateId,$start,$end)) return $uid; }
    return 0;
}
function fallbackFuelChargeByCategory($cat){ $c=strtolower(trim((string)$cat)); if(in_array($c,["sedan","hatchback"],true)) return 60.00; if(in_array($c,["suv","mpv"],true)) return 80.00; if(in_array($c,["pickup","luxury"],true)) return 100.00; if(in_array($c,["sport","coupe"],true)) return 120.00; if($c==="ev") return 0.00; return 80.00; }
function fuelChargeByCategory($conn,$cat){ if(trim((string)$cat)!=="" && tableExists($conn,"fuel_packages")){ $stmt=$conn->prepare("SELECT fuel_charge,status FROM fuel_packages WHERE LOWER(category_name)=LOWER(?) LIMIT 1"); if($stmt){ $stmt->bind_param("s",$cat); $stmt->execute(); $row=$stmt->get_result()->fetch_assoc(); $stmt->close(); if($row){ if(strtolower((string)($row["status"]??"active"))!=="active") return 0.00; return (float)($row["fuel_charge"]??0); } } } return fallbackFuelChargeByCategory($cat); }
function insertDynamic($conn,$table,$data){ $cols=[];$vals=[];$types=""; foreach($data as $col=>$val){ if(columnExists($conn,$table,$col)){ $cols[]=$col; $vals[]=$val; $types.=is_int($val)?"i":(is_float($val)?"d":"s"); } } if(!$cols) return 0; $ph=implode(",",array_fill(0,count($cols),"?")); $sql="INSERT INTO $table (".implode(",",$cols).") VALUES ($ph)"; $stmt=$conn->prepare($sql); if(!$stmt) return 0; $stmt->bind_param($types,...$vals); $ok=$stmt->execute(); $id=$ok?$stmt->insert_id:0; $stmt->close(); return $id; }
function updateCartStatus($conn,$cartIds,$status){ if(!$cartIds) return; $ids=implode(",",array_map("intval",$cartIds)); $conn->query("UPDATE cart_items SET status='".$conn->real_escape_string($status)."'".(columnExists($conn,"cart_items","updated_at")?", updated_at=NOW()":"")." WHERE cart_item_id IN ($ids)"); }

if(empty($_SESSION["user_id"])){ header("Location: login.php?redirect=payment.php"); exit; }
$userId=(int)$_SESSION["user_id"];
if(!tableExists($conn,"cart_items")){ die("Cart table not found. Please import cart SQL first."); }

$selectedCheckoutIds = $_SESSION["checkout_cart_item_ids"] ?? [];
if (!is_array($selectedCheckoutIds)) {
    $selectedCheckoutIds = [];
}
$selectedCheckoutIds = array_values(array_unique(array_filter(array_map("intval", $selectedCheckoutIds), fn($id) => $id > 0)));

if (empty($selectedCheckoutIds)) {
    header("Location: cart.php?checkout_required=1");
    exit;
}

$user=null; if(tableExists($conn,"users")){ $userIdCol=firstColumn($conn,"users",["user_id","id"],"user_id"); $user=fetchOne($conn,"SELECT * FROM users WHERE $userIdCol=? LIMIT 1","i",[$userId]); }
$navCartCount=getNavCartCount($conn);

$insurancePackages=["basic"=>["name"=>"Basic Coverage","price"=>0],"standard"=>["name"=>"Standard Coverage","price"=>20],"premium"=>["name"=>"Premium Coverage","price"=>45]];
$termsVersion="KHCR-2026-01";
ensureTermsAcceptanceTable($conn);
$driverAgeGroups=["normal"=>["name"=>"25–69 years","price"=>0],"under25"=>["name"=>"Under 25 years","price"=>10],"over69"=>["name"=>"Over 69 years","price"=>15]];
$addonServices=["gps"=>["name"=>"GPS Navigation","price"=>15,"unit"=>"day"],"child_seat"=>["name"=>"Child Seat","price"=>10,"unit"=>"day"],"dashcam"=>["name"=>"Dashcam","price"=>12,"unit"=>"day"],"touchngo"=>["name"=>"Touch n Go Card","price"=>5,"unit"=>"booking"],"phone_holder"=>["name"=>"Car Phone Holder","price"=>6,"unit"=>"day"],"umbrella"=>["name"=>"Rain Umbrella","price"=>5,"unit"=>"booking"],"cooler_box"=>["name"=>"Portable Cooler Box","price"=>15,"unit"=>"day"],"roof_rack"=>["name"=>"Roof Rack","price"=>25,"unit"=>"day"],"first_aid"=>["name"=>"First Aid Kit","price"=>8,"unit"=>"booking"],"luggage_strap"=>["name"=>"Luggage Strap Set","price"=>6,"unit"=>"booking"]];
function cleanAddons($raw,$insurancePackages,$addonServices,$driverAgeGroups){ $raw=is_array($raw)?$raw:[]; $ins=(string)($raw["insurance"]??"basic"); if(!isset($insurancePackages[$ins])) $ins="basic"; $driver=(string)($raw["driver_age"]??"normal"); if(!isset($driverAgeGroups[$driver])) $driver="normal"; $fuel=(string)($raw["fuel"]??"none"); if(!in_array($fuel,["none","prepaid_fuel"],true)) $fuel="none"; $services=$raw["services"]??[]; if(!is_array($services)) $services=[]; $services=array_values(array_unique(array_filter(array_map("strval",$services),fn($s)=>isset($addonServices[$s])))); return ["insurance"=>$ins,"driver_age"=>$driver,"fuel"=>$fuel,"services"=>$services]; }

function getRequiredDocumentStatus($conn, $userId){
    $required = [
        "IC Photo" => "IC document",
        "Driving License Photo" => "Driving license document"
    ];
    $status = ["verified" => [], "missing" => [], "pending" => [], "rejected" => [], "state" => "unverified"];

    if (!tableExists($conn, "user_documents")) {
        $status["missing"] = array_values($required);
        return $status;
    }

    foreach ($required as $type => $label) {
        $row = fetchOne(
            $conn,
            "SELECT verification_status FROM user_documents WHERE user_id=? AND document_type=? AND TRIM(COALESCE(file_path,''))<>'' ORDER BY uploaded_at DESC, document_id DESC LIMIT 1",
            "is",
            [$userId, $type]
        );

        if (!$row) {
            $status["missing"][] = $label;
            continue;
        }

        $docStatus = (string)($row["verification_status"] ?? "Pending Verification");
        if (strcasecmp($docStatus, "Verified") === 0) {
            $status["verified"][] = $label;
        } elseif (strcasecmp($docStatus, "Rejected") === 0) {
            $status["rejected"][] = $label;
        } else {
            $status["pending"][] = $label;
        }
    }

    if (!empty($status["rejected"])) $status["state"] = "rejected";
    elseif (!empty($status["missing"])) $status["state"] = "missing";
    elseif (!empty($status["pending"])) $status["state"] = "pending";
    else $status["state"] = "verified";

    return $status;
}

function documentRequirementMessage($requiredDocuments){
    if(!empty($requiredDocuments["rejected"])){
        return "Your " . implode(" and ", $requiredDocuments["rejected"]) . " was rejected. Please upload a clearer replacement document in My Profile before payment.";
    }
    if(!empty($requiredDocuments["pending"])){
        return "Your " . implode(" and ", $requiredDocuments["pending"]) . " is waiting for admin verification. Payment will unlock after both documents are verified.";
    }
    if(!empty($requiredDocuments["missing"])){
        return "Please upload your " . implode(" and ", $requiredDocuments["missing"]) . " in My Profile before payment.";
    }
    return "Please complete IC and driving license verification before payment.";
}

function paymentDigitsOnly($value){
    return preg_replace("/\D/", "", (string)$value);
}

function validatePaymentDetails($method, $data){
    if(!is_array($data)) return "Please fill payment information before payment.";

    $payerName = trim((string)($data["Payer Name"] ?? $data["Cardholder Name"] ?? ""));
    if($payerName === "" || strlen($payerName) < 2) return "Please enter a valid payer name.";

    if($method === "Online Banking"){
        $username = trim((string)($data["Username / User ID"] ?? ""));
        $password = (string)($data["Password"] ?? "");
        $mobile = paymentDigitsOnly($data["Mobile Number"] ?? "");

        if(!preg_match("/^[A-Za-z0-9._-]{5,30}$/", $username)) return "Online banking username must be 5 to 30 characters.";
        if(strlen($password) < 8) return "Online banking password must be at least 8 characters.";
        if(strlen($mobile) < 10 || strlen($mobile) > 12) return "Online banking mobile number must contain 10 to 12 digits.";
        return "";
    }

    if($method === "Credit / Debit Card"){
        $cardNumber = paymentDigitsOnly($data["Card Number"] ?? "");
        $expiry = trim((string)($data["Expiry Date"] ?? ""));
        $email = trim((string)($data["Billing Email"] ?? ""));
        $cvv = paymentDigitsOnly($data["CVV"] ?? "");

        if(strlen($cardNumber) !== 16) return "Card number must contain exactly 16 digits.";
        if(!preg_match("/^(0[1-9]|1[0-2])\s*\/?\s*(\d{2})$/", $expiry, $match)) return "Card expiry date must use MM/YY format.";

        $expiryYear = 2000 + (int)$match[2];
        $expiryMonth = (int)$match[1];
        $lastDay = strtotime(sprintf("%04d-%02d-01 +1 month -1 day 23:59:59", $expiryYear, $expiryMonth));
        if(!$lastDay || $lastDay < time()) return "Card expiry date cannot be expired.";
        if(!filter_var($email, FILTER_VALIDATE_EMAIL)) return "Billing email format is invalid.";
        if(strlen($cvv) !== 3) return "CVV must contain exactly 3 digits.";
        return "";
    }

    if($method === "E-Wallet"){
        $walletPhone = paymentDigitsOnly($data["Wallet Phone Number"] ?? "");
        $pin = paymentDigitsOnly($data["Wallet PIN"] ?? "");

        if(strlen($walletPhone) < 10 || strlen($walletPhone) > 12) return "Wallet phone number must contain 10 to 12 digits.";
        if(strlen($pin) !== 6) return "Wallet PIN must contain exactly 6 digits.";
        return "";
    }

    return "Please select a valid payment method.";
}

$brandSelect="'-' AS brand"; $brandJoin=""; if(tableExists($conn,"brands") && columnExists($conn,"cars","brand_id")){ $brandSelect="COALESCE(b.brand_name,'-') AS brand"; $brandJoin=" LEFT JOIN brands b ON b.brand_id=c.brand_id "; } elseif(columnExists($conn,"cars","brand")){ $brandSelect="COALESCE(c.brand,'-') AS brand"; }
$categorySelect="'Others' AS category_name"; $categoryJoin=""; if(tableExists($conn,"categories") && columnExists($conn,"cars","category_id")){ $categorySelect="COALESCE(cat.category_name,'Others') AS category_name"; $categoryJoin=" LEFT JOIN categories cat ON cat.category_id=c.category_id "; } elseif(tableExists($conn,"vehicle_categories") && columnExists($conn,"cars","category_id")){ $categorySelect="COALESCE(cat.category_name,'Others') AS category_name"; $categoryJoin=" LEFT JOIN vehicle_categories cat ON cat.category_id=c.category_id "; } elseif(columnExists($conn,"cars","type")){ $categorySelect="COALESCE(c.type,'Others') AS category_name"; }
$imageCol=firstColumn($conn,"cars",["image","main_image","car_image"],null); $imageSelect=$imageCol?"c.$imageCol AS image":"'' AS image"; $carNameCol=firstColumn($conn,"cars",["car_name","name"],"car_name");
$cartItems=fetchRows($conn,"SELECT ci.*, c.$carNameCol AS car_name, $imageSelect, $brandSelect, $categorySelect, COALESCE(rs.state_name,'-') AS pickup_state_name, COALESCE(pl.location_name,'-') AS pickup_location_name, COALESCE(dl.location_name,'-') AS dropoff_location_name FROM cart_items ci INNER JOIN cars c ON c.car_id=ci.car_id $brandJoin $categoryJoin LEFT JOIN rental_states rs ON rs.state_id=ci.pickup_state_id LEFT JOIN rental_locations pl ON pl.location_id=ci.pickup_location LEFT JOIN rental_locations dl ON dl.location_id=ci.dropoff_location WHERE ci.user_id=? AND LOWER(COALESCE(ci.status,'active')) NOT IN ('removed','checked_out') ORDER BY ci.created_at DESC, ci.cart_item_id DESC","i",[$userId]);
$selectedCheckoutSet = array_flip($selectedCheckoutIds);
$cartItems = array_values(array_filter($cartItems, fn($item) => isset($selectedCheckoutSet[(int)$item["cart_item_id"]])));

if (empty($cartItems)) {
    unset($_SESSION["checkout_cart_item_ids"]);
    header("Location: cart.php?checkout_required=1");
    exit;
}

$summary=["cars"=>0,"available"=>0,"unavailable"=>0,"car_total"=>0.0,"insurance"=>0.0,"driver"=>0.0,"addons"=>0.0,"fuel"=>0.0,"services_total"=>0.0,"subtotal"=>0.0,"discount"=>0.0,"grand"=>0.0];
$unavailableItems=[]; $checkoutIds=[];
foreach($cartItems as $i=>$item){
    $carId=(int)$item["car_id"]; $unitId=(int)($item["unit_id"]??0); $stateId=(int)$item["pickup_state_id"]; $start=(string)$item["start_datetime"]; $end=(string)$item["end_datetime"];
    $available=false; $availableUnit=0;
    if($unitId>0 && !hasBookingOverlap($conn,$carId,$unitId,$stateId,$start,$end)){ $available=true; $availableUnit=$unitId; }
    if(!$available){ $availableUnit=findAvailableUnit($conn,$carId,$stateId,$start,$end); $available=$availableUnit>0; }
    $days=max(1,(int)$item["rental_days"]); $price=(float)$item["price_per_day"]; $carSubtotal=$price*$days;
    $addons=cleanAddons($_SESSION["cart_item_addons"][(string)(int)$item["cart_item_id"]]??[],$insurancePackages,$addonServices,$driverAgeGroups);
    $insPkg=$insurancePackages[$addons["insurance"]]; $driverGrp=$driverAgeGroups[$addons["driver_age"]];
    $insuranceTotal=$available?(float)$insPkg["price"]*$days:0; $driverTotal=$available?(float)$driverGrp["price"]*$days:0; $addonTotal=0; $serviceLabels=[];
    foreach($addons["services"] as $key){ $s=$addonServices[$key]; $line=((string)$s["unit"]==="day")?(float)$s["price"]*$days:(float)$s["price"]; if($available) $addonTotal+=$line; $serviceLabels[]=$s["name"]." (".money($s["price"])." / ".$s["unit"].")"; }
    $fuelPreview=fuelChargeByCategory($conn,$item["category_name"]??"Others"); $fuelTotal=($available && $addons["fuel"]==="prepaid_fuel")?$fuelPreview:0;
    $serviceTotal=$insuranceTotal+$driverTotal+$addonTotal+$fuelTotal;
    $cartItems[$i]=array_merge($item,["current_available"=>$available,"available_unit_id"=>$availableUnit,"computed_subtotal"=>$carSubtotal,"active_addons"=>$addons,"insurance_name"=>$insPkg["name"],"driver_age_name"=>$driverGrp["name"],"insurance_total"=>$insuranceTotal,"driver_age_total"=>$driverTotal,"addons_total"=>$addonTotal,"fuel_total"=>$fuelTotal,"fuel_label"=>$addons["fuel"]==="prepaid_fuel"?"Prepaid Fuel Package":"No Fuel Package","services_total"=>$serviceTotal,"service_labels"=>$serviceLabels,"image_src"=>getCarImage($conn,$carId,$item["image"]??"",$item["car_name"]??"Car")]);
    $summary["cars"]++;
    if($available){ $summary["available"]++; $summary["car_total"]+=$carSubtotal; $summary["insurance"]+=$insuranceTotal; $summary["driver"]+=$driverTotal; $summary["addons"]+=$addonTotal; $summary["fuel"]+=$fuelTotal; $summary["services_total"]+=$serviceTotal; $checkoutIds[]=(int)$item["cart_item_id"]; }
    else { $summary["unavailable"]++; $unavailableItems[]=$item["car_name"]??"Selected car"; }
}
$summary["subtotal"]=$summary["car_total"]+$summary["services_total"];
$activeVoucher=$_SESSION["cart_voucher"]??null; if(is_array($activeVoucher) && !empty($activeVoucher["discount_percent"])) $summary["discount"]=round($summary["subtotal"]*((float)$activeVoucher["discount_percent"]/100),2);
$summary["grand"]=max(0,$summary["subtotal"]-$summary["discount"]);
$availabilityReady=$summary["cars"]>0 && $summary["unavailable"]===0 && $summary["available"]>0;
$requiredDocuments=getRequiredDocumentStatus($conn, $userId);
$hasRequiredDocuments=$requiredDocuments["state"]==="verified";
$requiredDocumentMessage=documentRequirementMessage($requiredDocuments);
$canPay=$availabilityReady && $hasRequiredDocuments;

$error="";
if(($_SERVER["REQUEST_METHOD"] ?? "")==="POST" && ($_POST["action"]??"")==="pay_now"){
    $method=(string)($_POST["payment_method"]??"");
    $paymentConfirmed=(string)($_POST["payment_confirmed"]??"")==="1";
    $paymentDetailJson=trim((string)($_POST["payment_detail_json"]??""));
    $paymentDetailData=json_decode($paymentDetailJson,true);
    $paymentValidationError=validatePaymentDetails($method,$paymentDetailData);
    $acceptedRentalTerms=!empty($_POST["accept_rental_terms"]);
    if(!in_array($method,["Online Banking","Credit / Debit Card","E-Wallet"],true)) $error="Please select a valid payment method.";
    elseif(!$paymentConfirmed || $paymentDetailJson==="" || !is_array($paymentDetailData)) $error="Please fill payment information before payment.";
    elseif($paymentValidationError !== "") $error=$paymentValidationError;
    elseif(!$availabilityReady) $error="Some cars are no longer available. Please return to cart and update your rental items.";
    elseif(!$hasRequiredDocuments) $error=$requiredDocumentMessage;
    elseif(!$acceptedRentalTerms) $error="Please agree to the rental Terms & Conditions before payment.";
    else{
        $conn->begin_transaction();
        try{
            $reference="KH".date("YmdHis").rand(100,999);
            $bookingId=insertDynamic($conn,"bookings",["user_id"=>$userId,"booking_reference"=>$reference,"booking_date"=>date("Y-m-d H:i:s"),"total_amount"=>(float)$summary["subtotal"],"grand_total"=>(float)$summary["grand"],"booking_status"=>"pending_admin_approval","payment_status"=>"paid","payment_method"=>$method,"promo_id"=>is_array($activeVoucher)?(int)($activeVoucher["promo_id"]??0):0,"promo_code"=>is_array($activeVoucher)?(string)($activeVoucher["promo_code"]??""):"","voucher_discount"=>(float)$summary["discount"],"extra_services_total"=>(float)$summary["services_total"],"created_at"=>date("Y-m-d H:i:s")]);
            if($bookingId<=0) throw new Exception("Booking creation failed.");
            foreach($cartItems as $item){
                if(empty($item["current_available"])) throw new Exception("A selected car is unavailable.");
                $extraJson=json_encode($item["service_labels"],JSON_UNESCAPED_UNICODE);
                $bookingItemId=insertDynamic($conn,"booking_items",["booking_id"=>$bookingId,"car_id"=>(int)$item["car_id"],"unit_id"=>(int)$item["available_unit_id"],"pickup_state_id"=>(int)$item["pickup_state_id"],"pickup_location"=>(int)$item["pickup_location"],"dropoff_location"=>(int)$item["dropoff_location"],"start_datetime"=>(string)$item["start_datetime"],"end_datetime"=>(string)$item["end_datetime"],"rental_days"=>(int)$item["rental_days"],"price_per_day"=>(float)$item["price_per_day"],"subtotal"=>(float)$item["computed_subtotal"],"insurance_package"=>(string)$item["insurance_name"],"insurance_charge"=>(float)$item["insurance_total"],"driver_age_group"=>(string)$item["driver_age_name"],"driver_age_charge"=>(float)$item["driver_age_total"],"addon_services"=>$extraJson,"addon_services_charge"=>(float)$item["addons_total"],"fuel_option"=>(string)$item["fuel_label"],"fuel_charge"=>(float)$item["fuel_total"],"extra_services_total"=>(float)$item["services_total"],"created_at"=>date("Y-m-d H:i:s")]);
                if($bookingItemId<=0) throw new Exception("Booking item creation failed.");
            }
            $txn="PAY".date("YmdHis").rand(1000,9999);
            insertDynamic($conn,"payments",["user_id"=>$userId,"booking_id"=>$bookingId,"amount"=>(float)$summary["grand"],"payment_method"=>$method,"payment_status"=>"paid","transaction_reference"=>$txn,"payment_date"=>date("Y-m-d H:i:s"),"created_at"=>date("Y-m-d H:i:s")]);
            recordTermsAcceptance($conn,$userId,"payment_rental",$termsVersion,$bookingId);
            if(is_array($activeVoucher) && !empty($activeVoucher["promo_id"]) && tableExists($conn,"promo_code_usage")){
                insertDynamic($conn,"promo_code_usage",["promo_id"=>(int)$activeVoucher["promo_id"],"user_id"=>$userId,"booking_id"=>$bookingId,"used_at"=>date("Y-m-d H:i:s"),"created_at"=>date("Y-m-d H:i:s")]);
                unset($_SESSION["cart_voucher"]);
            }
            updateCartStatus($conn,$checkoutIds,"checked_out");
            foreach($checkoutIds as $id){ unset($_SESSION["cart_item_addons"][(string)$id]); }
            unset($_SESSION["checkout_cart_item_ids"]);
            $conn->commit();
            header("Location: booking_details.php?booking_id=".$bookingId);
            exit;
        }catch(Throwable $ex){ $conn->rollback(); $error=$ex->getMessage(); }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Payment | KH Car Rental</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--sky50:#f5fbff;--sky100:#eaf7ff;--sky200:#d8f2ff;--sky500:#28a8ea;--sky600:#1284c6;--dark:#10233d;--muted:#6e8297;--orange:#ff7a1a;--orange2:#f15f12;--border:#d8ecfb;--green:#16a765;--red:#e2453b;--shadow:0 24px 70px rgba(39,137,199,.13);--soft:0 12px 35px rgba(39,137,199,.10)}*{box-sizing:border-box;margin:0;padding:0}body{font-family:"Segoe UI",Tahoma,sans-serif;color:var(--dark);background:radial-gradient(circle at 8% 0%,rgba(210,239,255,.42),transparent 30%),radial-gradient(circle at 95% 8%,rgba(234,247,255,.45),transparent 34%),linear-gradient(180deg,#fff 0%,#f8fcff 48%,#fff 100%)}a{text-decoration:none;color:inherit}button,input{font-family:inherit}.navbar{position:sticky;top:0;z-index:100;height:64px;background:linear-gradient(135deg,rgba(224,247,255,.94),rgba(255,255,255,.96),rgba(240,250,255,.94));border-bottom:1px solid rgba(142,207,244,.42);backdrop-filter:blur(18px)}.nav-inner{width:min(1200px,calc(100% - 40px));height:64px;margin:auto;display:flex;align-items:center;justify-content:space-between;gap:18px}.brand{display:flex;align-items:center;gap:13px;font-size:15px;font-weight:950;white-space:nowrap;margin-right:28px}.brand-icon{width:42px;height:42px;display:grid;place-items:center;border-radius:15px;color:var(--sky600);background:linear-gradient(135deg,#d8f2ff,#fff);border:1px solid rgba(142,207,244,.46);box-shadow:0 14px 28px rgba(40,168,234,.13)}.nav-links{flex:1;display:flex;align-items:center;justify-content:center;gap:18px;list-style:none}.nav-links a{display:inline-flex;align-items:center;gap:6px;padding:8px 7px;border-radius:999px;font-size:12px;font-weight:950;color:#2b4969;letter-spacing:.2px;position:relative}.nav-links a i{color:#2b4969;font-size:13px}.nav-links a.active,.nav-links a.active i,.nav-links a:hover,.nav-links a:hover i{color:var(--sky600)}.cart-badge{position:absolute;top:0;right:-8px;min-width:17px;height:17px;padding:0 5px;border-radius:999px;background:#ef4444;color:#fff;font-size:10px;font-weight:950;display:grid;place-items:center;box-shadow:0 7px 16px rgba(239,68,68,.35);border:2px solid #fff}.avatar-wrap{position:relative;margin-left:0}.avatar-btn{border:0;background:transparent;display:flex;align-items:center;gap:10px;cursor:pointer;font-weight:950;color:var(--dark)}.avatar-circle{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;overflow:hidden;color:#fff;background:linear-gradient(135deg,var(--sky500),#0d3f82);border:3px solid #fff;box-shadow:0 14px 28px rgba(40,168,234,.18)}.avatar-circle img{width:100%;height:100%;object-fit:cover}.dropdown{position:absolute;right:0;top:62px;width:260px;display:none;padding:12px;border-radius:24px;background:rgba(255,255,255,.96);border:1px solid var(--border);box-shadow:0 24px 70px rgba(39,137,199,.18)}.dropdown.show{display:block}.dropdown a{min-height:54px;display:flex;align-items:center;gap:12px;padding:14px 16px;border-radius:17px;font-weight:900;color:#24415f}.dropdown a:hover{background:var(--sky100);color:var(--sky600)}.login-btn{display:inline-flex;align-items:center;gap:8px;padding:14px 20px;border-radius:999px;color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600));font-weight:950}.payment-page{width:min(1280px,100%);margin:18px auto 58px;padding:0 22px}.breadcrumb{display:flex;gap:9px;margin:14px 0;color:var(--muted);font-size:12px;font-weight:850}.breadcrumb a{color:var(--sky600)}.hero{padding:28px;border-radius:30px;background:radial-gradient(circle at 100% 0%,rgba(184,228,255,.25),transparent 32%),linear-gradient(135deg,rgba(255,255,255,.98),rgba(247,253,255,.94));border:1px solid rgba(184,228,255,.92);box-shadow:var(--shadow);margin-bottom:18px}.pill{display:inline-flex;align-items:center;gap:8px;width:fit-content;padding:6px 11px;border-radius:999px;background:rgba(40,168,234,.12);color:var(--sky600);border:1px solid rgba(40,168,234,.22);font-size:10.5px;font-weight:950;letter-spacing:.8px;text-transform:uppercase;margin-bottom:10px}.hero h1{font-size:clamp(38px,4vw,56px);line-height:1;font-weight:950;letter-spacing:-1.8px;margin-bottom:10px}.hero p{color:var(--muted);font-size:14px;line-height:1.55;font-weight:700;max-width:760px}.layout{display:grid;grid-template-columns:1fr 390px;gap:18px;align-items:start}.stack{display:grid;gap:16px}.card,.summary-card{border-radius:28px;background:radial-gradient(circle at 100% 0%,rgba(40,168,234,.08),transparent 28%),linear-gradient(145deg,rgba(255,255,255,.98),rgba(246,252,255,.92));border:1px solid rgba(184,228,255,.98);box-shadow:0 18px 46px rgba(29,109,164,.12);padding:20px}.card h2,.summary-card h2{font-size:24px;font-weight:950;letter-spacing:-.5px;margin-bottom:14px}.car-card{display:grid;grid-template-columns:190px 1fr;gap:16px}.car-img{height:145px;border-radius:20px;overflow:hidden;border:1px solid var(--border);background:#fff}.car-img img{width:100%;height:100%;object-fit:cover}.detail-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.detail{border-radius:16px;background:rgba(255,255,255,.86);border:1px solid var(--border);padding:12px}.detail span{display:block;color:var(--muted);font-size:10px;font-weight:950;letter-spacing:.55px;text-transform:uppercase;margin-bottom:4px}.detail strong{font-size:14px;font-weight:950}.calculation{margin-top:12px;border-radius:18px;background:var(--sky100);border:1px solid var(--border);padding:14px;display:grid;gap:8px}.row{display:flex;justify-content:space-between;gap:16px;align-items:center;padding:10px 0;border-bottom:1px solid rgba(216,236,251,.92);font-size:13.5px;font-weight:850;color:#2b4969}.row:last-child{border-bottom:0}.row strong{color:var(--dark);font-weight:950}.row.big strong{font-size:22px;color:var(--sky600)}.method-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}.method{min-height:105px;border-radius:20px;background:#fff;border:1px solid var(--border);padding:14px;display:grid;place-items:center;text-align:center;gap:7px;cursor:pointer;transition:.24s;font-weight:950}.method i{font-size:24px;color:var(--sky600)}.method input{accent-color:var(--sky600)}.method:has(input:checked){border-color:var(--green);background:#f0fff8;box-shadow:0 14px 32px rgba(22,167,101,.12)}.summary-card{position:sticky;top:84px}.summary-card .btn{width:100%;min-height:48px;border:0;border-radius:16px;margin-top:12px;color:#fff;background:linear-gradient(135deg,#22c77a,#16a765 48%,#079455);font-size:14px;font-weight:950;cursor:pointer;box-shadow:0 18px 36px rgba(22,167,101,.26)}.btn-white{display:inline-flex;align-items:center;justify-content:center;gap:8px;min-height:44px;padding:0 16px;border-radius:15px;background:#fff;color:var(--sky600);border:1px solid var(--border);font-weight:950}.alert{border-radius:20px;padding:14px 16px;font-size:13.5px;font-weight:850;line-height:1.45;margin-bottom:14px;display:flex;gap:12px}.alert i{width:32px;height:32px;border-radius:50%;display:grid;place-items:center;flex:0 0 auto}.alert.ok{background:#f0fff8;border:1px solid rgba(20,184,116,.22);color:#087747}.alert.ok i{background:var(--green);color:#fff}.alert.bad{background:#fff4f2;border:1px solid rgba(244,67,54,.22);color:#b42318}.alert.bad i{background:var(--red);color:#fff}.footer{background:#12304f;color:#fff;padding:82px 34px 28px}.footer-inner{width:min(1200px,calc(100% - 40px));margin:0 auto 42px;display:grid;grid-template-columns:1.35fr 1fr 1.2fr;gap:62px}.footer h3{font-size:22px;font-weight:950;color:#fff;margin-bottom:18px}.footer p,.footer a{color:rgba(218,235,248,.76);font-size:15.5px;line-height:1.85;font-weight:650}.footer-hover-link,.footer .contact-link{width:fit-content;display:inline-flex;align-items:center;gap:10px;padding:3px 0;border-radius:12px;transition:.22s}.footer .contact-link i{width:18px;color:var(--sky500)}.footer-hover-link:hover,.footer .contact-link:hover{color:#fff;transform:translateX(6px);background:rgba(255,255,255,.055);padding-left:8px;padding-right:10px}.footer .start-btn{display:inline-flex!important;align-items:center!important;justify-content:center!important;gap:10px!important;width:auto!important;min-width:210px!important;height:44px!important;padding:0 28px!important;border-radius:16px!important;background:linear-gradient(135deg,#28a8ea,#1284c6)!important;color:#fff!important;margin-top:22px;font-size:15.5px;font-weight:950}.footer-bottom{width:min(1200px,calc(100% - 40px));margin:0 auto;padding-top:22px;border-top:1px solid rgba(255,255,255,.14);text-align:center;color:rgba(218,235,248,.86);font-size:14px}.back-top{position:fixed;right:28px;bottom:28px;width:54px;height:54px;border-radius:50%;border:0;color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600));box-shadow:0 20px 40px rgba(40,168,234,.3);cursor:pointer}@media(max-width:1080px){.layout{grid-template-columns:1fr}.summary-card{position:static}.nav-links{display:none}.car-card{grid-template-columns:1fr}.method-grid{grid-template-columns:1fr}.footer-inner{grid-template-columns:1fr;gap:34px}}

.doc-warning{align-items:flex-start;background:#fff8e6!important;border-color:rgba(255,176,32,.38)!important;color:#9a5b00!important}.doc-warning i{background:linear-gradient(135deg,#ffbf47,#f59f00)!important}.doc-warning a{color:#0b7fc6;font-weight:950;text-decoration:underline}.terms-confirm{display:flex;gap:11px;align-items:flex-start;padding:13px 14px;margin:12px 0;border-radius:18px;background:rgba(255,255,255,.82);border:1px solid var(--border);color:#24415f;font-size:12px;font-weight:850;line-height:1.45}.terms-confirm input{width:18px;height:18px;margin-top:1px;accent-color:var(--green);flex:0 0 auto}.terms-confirm a{color:var(--sky600);font-weight:950;text-decoration:underline}.terms-confirm.invalid-field{border-color:#e2453b;background:#fff5f5}.btn-pay-disabled{width:100%;margin-top:8px;min-height:54px;border:0;border-radius:18px;background:linear-gradient(135deg,#d9e4ee,#aebdca)!important;color:#ffffff!important;font-weight:950;font-size:16px;cursor:not-allowed;opacity:.95;box-shadow:none!important}.upload-doc-btn{width:100%;margin-top:8px;min-height:48px;border-radius:16px}
.payment-page-compact{margin-top:10px}.payment-page-compact .breadcrumb{margin-bottom:18px}.trip-service-card{padding:22px}.section-title-row{display:flex;align-items:center;justify-content:space-between;gap:14px;margin-bottom:14px}.section-title-row h2{margin-bottom:0}.extra-title-row{margin-top:0}.section-divider{height:1px;background:linear-gradient(90deg,transparent,rgba(40,168,234,.28),transparent);margin:22px 0}.trip-detail-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.service-sub-row{padding-left:20px!important;font-size:12.5px}.edit-cart-top{min-height:40px;white-space:nowrap}.method-note{color:var(--muted);font-size:13px;font-weight:750;line-height:1.45;margin:-4px 0 14px}.selected-payment-details{margin-top:14px;padding:14px;border-radius:20px;background:linear-gradient(135deg,#f0fff8,#ffffff);border:1px solid rgba(20,184,116,.24);box-shadow:0 12px 28px rgba(20,184,116,.07)}.selected-payment-head{display:flex;align-items:center;gap:9px;color:#087747;font-size:12px;font-weight:950;text-transform:uppercase;letter-spacing:.65px;margin-bottom:10px}.selected-payment-head i{width:28px;height:28px;border-radius:11px;display:grid;place-items:center;color:#fff;background:linear-gradient(135deg,#22c77a,#079455)}.selected-payment-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}.selected-payment-item{padding:10px 12px;border-radius:15px;background:#fff;border:1px solid rgba(216,236,251,.92)}.selected-payment-item span{display:block;color:var(--muted);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.55px;margin-bottom:3px}.selected-payment-item strong{display:block;color:var(--dark);font-size:13px;font-weight:950;word-break:break-word}.addon-summary-sub{gap:5px}.addon-car-line{margin-top:4px;font-weight:950}.addon-bullet{padding-left:12px}.addon-bullet span{max-width:260px!important}.addon-bullet em:empty{display:none}.summary-group{border-bottom:1px solid rgba(216,236,251,.92);padding-bottom:9px;margin-bottom:2px}.summary-group .row{border-bottom:0;padding-bottom:6px}.summary-sub{display:grid;gap:6px;padding:0 0 4px 12px}.summary-sub div{display:flex;justify-content:space-between;gap:12px;color:var(--muted);font-size:11.5px;font-weight:800;line-height:1.35}.summary-sub span{max-width:230px}.summary-sub em{font-style:normal;color:#24415f;font-weight:950;white-space:nowrap}.payment-ok{align-items:center}.payment-method-modal{position:fixed;inset:0;z-index:9999;display:none;place-items:center;background:rgba(13,31,55,.48);backdrop-filter:blur(12px);padding:20px}.payment-method-modal.show{display:grid}.payment-method-box{width:min(760px,100%);max-height:min(88vh,820px);display:flex;flex-direction:column;border-radius:30px;background:linear-gradient(135deg,rgba(255,255,255,.98),rgba(244,251,255,.96));border:1px solid rgba(184,228,255,.95);box-shadow:0 34px 95px rgba(23,48,79,.28);overflow:hidden}.payment-method-head{padding:22px 24px 16px;display:flex;justify-content:space-between;gap:18px;align-items:flex-start;border-bottom:1px solid rgba(216,236,251,.92)}.payment-method-head h2{font-size:30px;line-height:1;font-weight:950;letter-spacing:-.8px;margin-bottom:6px}.payment-method-head p{color:var(--muted);font-size:13px;font-weight:750}.payment-modal-close{width:46px;height:46px;border:0;border-radius:17px;background:var(--sky100);color:var(--sky600);font-size:18px;cursor:pointer;flex:0 0 auto}.payment-method-body{padding:18px 24px;overflow-y:auto}.fixed-amount-card{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:15px 16px;border-radius:20px;background:linear-gradient(135deg,#eaf7ff,#fff);border:1px solid var(--border);margin-bottom:16px}.fixed-amount-card span{color:var(--muted);font-size:11px;font-weight:950;letter-spacing:.65px;text-transform:uppercase}.fixed-amount-card strong{font-size:28px;color:var(--sky600);font-weight:950}.payment-panel{display:none}.payment-panel.active{display:block}.payment-panel h3{font-size:18px;font-weight:950;margin:0 0 12px;display:flex;align-items:center;gap:10px}.payment-panel h3 i{color:var(--sky600)}.option-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:9px;margin-bottom:14px}.pay-option{min-height:44px;border-radius:15px;border:1px solid var(--border);background:#fff;color:#24415f;font-size:12px;font-weight:950;cursor:pointer;transition:.22s;padding:8px}.pay-option:hover,.pay-option.active{color:#fff;background:linear-gradient(135deg,var(--sky500),var(--sky600));border-color:rgba(40,168,234,.45);box-shadow:0 14px 28px rgba(40,168,234,.18);transform:translateY(-2px)}.pay-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.pay-form-grid label{display:grid;gap:7px;color:#24415f;font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.5px}.pay-form-grid input{height:46px;border-radius:15px;border:2px solid #e2f2ff;background:#fff;padding:0 13px;outline:none;font-size:13px;font-weight:800;color:var(--dark)}.pay-form-grid input:focus{border-color:var(--sky500);box-shadow:0 0 0 .22rem rgba(40,168,234,.12)}.pay-form-grid input.error{border-color:#ff4d4f!important;background:#fff5f5!important}.full-pay-field{grid-column:1/-1}.payment-method-footer{display:grid;grid-template-columns:180px 1fr;gap:12px;padding:16px 24px 22px;border-top:1px solid rgba(216,236,251,.92);background:rgba(255,255,255,.78)}.modal-confirm{color:#fff;background:linear-gradient(135deg,#22c77a,#16a765 48%,#079455);min-height:46px}.method:hover{border-color:rgba(40,168,234,.45);box-shadow:0 14px 28px rgba(40,168,234,.14);transform:translateY(-2px)}
@media(max-width:760px){.trip-detail-grid,.pay-form-grid,.option-grid,.selected-payment-grid{grid-template-columns:1fr}.payment-method-footer{grid-template-columns:1fr}.edit-cart-top{width:100%}.section-title-row{align-items:flex-start;flex-direction:column}.summary-sub span{max-width:none}}

.payment-item-card{padding:24px}
.payment-car-summary{display:grid;grid-template-columns:190px 1fr;gap:16px;align-items:start}
.payment-item-card .section-divider{margin:24px 0}
@media(max-width:760px){.payment-car-summary{grid-template-columns:1fr}}
</style>
</head>
<body>
<header class="navbar"><div class="nav-inner"><a href="homepage.php" class="brand"><span class="brand-icon"><i class="fa-solid fa-car-side"></i></span><span>KH Car Rental</span></a><ul class="nav-links"><li><a href="homepage.php"><i class="fa-solid fa-house"></i> HOME</a></li><li><a href="catalogue.php"><i class="fa-solid fa-car"></i> CATALOGUE</a></li><li><a href="find_car_smart.php"><i class="fa-solid fa-wand-magic-sparkles"></i> FIND CAR SMART</a></li><li><a href="compare_car.php"><i class="fa-solid fa-code-compare"></i> COMPARE CAR</a></li><li><a href="aboutus.php"><i class="fa-solid fa-circle-info"></i> ABOUT US</a></li><li><a href="contactus.php"><i class="fa-solid fa-envelope"></i> CONTACT US</a></li><li><a href="cart.php" class="active"><i class="fa-solid fa-cart-shopping"></i> CART<?php if($navCartCount>0): ?><span class="cart-badge"><?= e($navCartCount>99?'99+':$navCartCount) ?></span><?php endif; ?></a></li></ul><div class="avatar-wrap"><?php if($user): ?><button class="avatar-btn" type="button" id="avatarBtn"><span class="avatar-circle"><?php if(!empty($user["profile_picture"])): ?><img src="<?= e($user["profile_picture"]) ?>" alt="Profile"><?php else: ?><?= e(strtoupper(substr($user["name"]??"U",0,1))) ?><?php endif; ?></span><i class="fa-solid fa-chevron-down"></i></button><div class="dropdown" id="profileDropdown"><a href="my_profile.php"><i class="fa-solid fa-user"></i> Manage My Profile</a><a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div><?php else: ?><a href="login.php" class="login-btn"><i class="fa-solid fa-user"></i> Login / Register</a><?php endif; ?></div></div></header>
<main class="payment-page payment-page-compact">
    <div class="breadcrumb">
        <a href="homepage.php">Home</a>
        <i class="fa-solid fa-chevron-right"></i>
        <a href="cart.php">Cart</a>
        <i class="fa-solid fa-chevron-right"></i>
        <span>Payment</span>
    </div>

    <?php if($error): ?>
        <div class="alert bad"><i class="fa-solid fa-triangle-exclamation"></i><div><?= e($error) ?></div></div>
    <?php endif; ?>

    <?php if(empty($cartItems)): ?>
        <section class="card">
            <h2>Your cart is empty</h2>
            <p>Please add an available car before payment.</p>
            <br>
            <a class="btn-white" href="catalogue.php"><i class="fa-solid fa-car"></i> Browse Catalogue</a>
        </section>
    <?php else: ?>
        <?php if(!$availabilityReady): ?>
            <div class="alert bad"><i class="fa-solid fa-triangle-exclamation"></i><div><strong>Some cars are no longer available.</strong><br>Please return to cart and choose another vehicle or date.</div></div>
        <?php elseif(!$hasRequiredDocuments): ?>
            <div class="alert doc-warning"><i class="fa-solid fa-id-card"></i><div><strong>Identity verification required before payment.</strong><br><?= e($requiredDocumentMessage) ?> <a href="my_profile.php?tab=documents">My Profile &gt; Documents</a></div></div>
        <?php endif; ?>

        <form method="POST" id="paymentForm">
            <input type="hidden" name="action" value="pay_now">
            <input type="hidden" name="payment_confirmed" id="paymentConfirmed" value="0">
            <input type="hidden" name="payment_detail_json" id="paymentDetailJson" value="">
            <div class="layout">
                <section class="stack">
                    <?php foreach($cartItems as $item): ?>
                        <article class="card payment-item-card">
                            <div class="payment-car-summary car-card">
                                <div class="car-img"><img src="<?= e($item["image_src"]) ?>" alt="<?= e($item["car_name"]) ?>"></div>
                                <div>
                                    <h2><?= e($item["car_name"]) ?></h2>
                                    <p style="color:var(--muted);font-weight:850;margin-bottom:12px;"><?= e($item["brand"]) ?> • <?= e($item["category_name"]) ?> • <?= e($item["pickup_state_name"]) ?></p>
                                    <div class="calculation">
                                        <div class="row"><span>Car Price Per Day</span><strong><?= e(money($item["price_per_day"])) ?></strong></div>
                                        <div class="row"><span>Rental Duration</span><strong><?= e((int)$item["rental_days"]) ?> day(s)</strong></div>
                                        <div class="row"><span>Total Calculation</span><strong><?= e(money($item["price_per_day"])) ?> × <?= e((int)$item["rental_days"]) ?> day(s)</strong></div>
                                        <div class="row big"><span>Rental Price</span><strong><?= e(money($item["computed_subtotal"])) ?></strong></div>
                                    </div>
                                </div>
                            </div>

                            <div class="section-divider"></div>

                            <div class="section-title-row">
                                <h2><i class="fa-solid fa-route"></i> Trip Summary</h2>
                            </div>
                            <div class="detail-grid trip-detail-grid">
                                <div class="detail"><span>Pickup State</span><strong><?= e($item["pickup_state_name"]) ?></strong></div>
                                <div class="detail"><span>Rental Days</span><strong><?= e((int)$item["rental_days"]) ?> day(s)</strong></div>
                                <div class="detail"><span>Pickup Date & Time</span><strong><?= e(dtLabel($item["start_datetime"])) ?></strong></div>
                                <div class="detail"><span>Return Date & Time</span><strong><?= e(dtLabel($item["end_datetime"])) ?></strong></div>
                                <div class="detail"><span>Pickup Location</span><strong><?= e($item["pickup_location_name"]) ?></strong></div>
                                <div class="detail"><span>Drop-off Location</span><strong><?= e($item["dropoff_location_name"]) ?></strong></div>
                            </div>

                            <div class="section-divider"></div>

                            <div class="section-title-row extra-title-row">
                                <h2><i class="fa-solid fa-shield-heart"></i> Extra Protection & Services</h2>
                                <a class="btn-white edit-cart-top" href="cart.php"><i class="fa-solid fa-pen-to-square"></i> Edit in Cart</a>
                            </div>
                            <div class="row"><span>Insurance Package: <?= e($item["insurance_name"]) ?></span><strong><?= e(money($item["insurance_total"])) ?></strong></div>
                            <div class="row"><span>Driver Age Surcharge: <?= e($item["driver_age_name"]) ?></span><strong><?= e(money($item["driver_age_total"])) ?></strong></div>
                            <div class="row"><span>Add-on Services<?= empty($item["service_labels"])?": No optional add-on selected":"" ?></span><strong><?= e(money($item["addons_total"])) ?></strong></div>
                            <?php foreach($item["service_labels"] as $label): ?>
                                <div class="row service-sub-row"><span><i class="fa-solid fa-check"></i> <?= e($label) ?></span><strong></strong></div>
                            <?php endforeach; ?>
                            <div class="row"><span>Fuel Option: <?= e($item["fuel_label"]) ?></span><strong><?= e(money($item["fuel_total"])) ?></strong></div>
                            <div class="row big"><span>Total Extra Protection & Services</span><strong><?= e(money($item["services_total"])) ?></strong></div>
                        </article>
                    <?php endforeach; ?>

                    <section class="card">
                        <h2><i class="fa-solid fa-wallet"></i> Payment Method</h2>
                        <p class="method-note">Choose a payment method. A secure simulated payment window will open for the selected method.</p>
                        <div class="method-grid">
                            <label class="method payment-method-option" data-method="banking"><input type="radio" name="payment_method" value="Online Banking" checked><i class="fa-solid fa-building-columns"></i><span>Online Banking</span></label>
                            <label class="method payment-method-option" data-method="card"><input type="radio" name="payment_method" value="Credit / Debit Card"><i class="fa-solid fa-credit-card"></i><span>Credit / Debit Card</span></label>
                            <label class="method payment-method-option" data-method="ewallet"><input type="radio" name="payment_method" value="E-Wallet"><i class="fa-solid fa-mobile-screen-button"></i><span>E-Wallet</span></label>
                        </div>
                        <div class="selected-payment-details" id="selectedPaymentDetails" style="display:none;">
                            <div class="selected-payment-head"><i class="fa-solid fa-circle-check"></i><strong>Selected Payment Details</strong></div>
                            <div class="selected-payment-grid" id="selectedPaymentGrid"></div>
                        </div>
                    </section>
                </section>

                <aside class="summary-card">
                    <h2><i class="fa-solid fa-receipt"></i> Payment Summary</h2>
                    <div class="row"><span>Total Cars</span><strong><?= e($summary["cars"]) ?></strong></div>
                    <div class="row"><span>Available Items</span><strong><?= e($summary["available"]) ?></strong></div>

                    <div class="summary-group">
                        <div class="row summary-main"><span>Car Rental Total</span><strong><?= e(money($summary["car_total"])) ?></strong></div>
                        <div class="summary-sub">
                            <?php foreach($cartItems as $item): ?>
                                <div><span><?= e($item["car_name"]) ?></span><em><?= e(money($item["computed_subtotal"])) ?></em></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="summary-group">
                        <div class="row summary-main"><span>Insurance</span><strong><?= e(money($summary["insurance"])) ?></strong></div>
                        <div class="summary-sub">
                            <?php foreach($cartItems as $item): ?>
                                <div><span><?= e($item["car_name"]) ?> • <?= e($item["insurance_name"]) ?></span><em><?= e(money($item["insurance_total"])) ?></em></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="summary-group">
                        <div class="row summary-main"><span>Driver Age</span><strong><?= e(money($summary["driver"])) ?></strong></div>
                        <div class="summary-sub">
                            <?php foreach($cartItems as $item): ?>
                                <div><span><?= e($item["car_name"]) ?> • <?= e($item["driver_age_name"]) ?></span><em><?= e(money($item["driver_age_total"])) ?></em></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="summary-group">
                        <div class="row summary-main"><span>Add-ons</span><strong><?= e(money($summary["addons"])) ?></strong></div>
                        <div class="summary-sub addon-summary-sub">
                            <?php foreach($cartItems as $item): ?>
                                <div class="addon-car-line"><span><?= e($item["car_name"]) ?></span><em><?= e(money($item["addons_total"])) ?></em></div>
                                <?php if(empty($item["service_labels"])): ?>
                                    <div class="addon-bullet"><span>• No optional add-on</span><em></em></div>
                                <?php else: ?>
                                    <?php foreach($item["service_labels"] as $serviceLabel): ?>
                                        <div class="addon-bullet"><span>• <?= e($serviceLabel) ?></span><em></em></div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="summary-group">
                        <div class="row summary-main"><span>Fuel</span><strong><?= e(money($summary["fuel"])) ?></strong></div>
                        <div class="summary-sub">
                            <?php foreach($cartItems as $item): ?>
                                <div><span><?= e($item["car_name"]) ?></span><em><?= e(money($item["fuel_total"])) ?></em></div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="row"><span>Extra Protection & Services</span><strong><?= e(money($summary["services_total"])) ?></strong></div>
                    <div class="row"><span>Subtotal</span><strong><?= e(money($summary["subtotal"])) ?></strong></div>
                    <div class="row"><span>Voucher Discount<?= is_array($activeVoucher)?" (".e($activeVoucher["promo_code"]??"").")":"" ?></span><strong>-<?= e(money($summary["discount"])) ?></strong></div>
                    <div class="row big"><span>Grand Total</span><strong><?= e(money($summary["grand"])) ?></strong></div>
                    <?php if($canPay): ?>
                        <div class="alert ok payment-ok"><i class="fa-solid fa-check"></i><div>All items are available and your IC and driving license documents are verified. You can proceed with payment.</div></div>
                        <label class="terms-confirm" id="rentalTermsBox">
                            <input type="checkbox" name="accept_rental_terms" id="acceptRentalTerms" value="1" required>
                            <span>I agree to the <a href="terms_conditions.php" target="_blank">Rental Terms & Conditions</a>, including accident responsibility, traffic summons, vehicle damage, late return, payment and insurance coverage rules.</span>
                        </label>
                        <button class="btn" type="submit"><i class="fa-solid fa-lock"></i> Pay Now</button>
                    <?php elseif(!$availabilityReady): ?>
                        <div class="alert bad"><i class="fa-solid fa-lock"></i><div>Payment is disabled because at least one item is unavailable.</div></div>
                        <a class="btn-white" href="cart.php"><i class="fa-solid fa-arrow-left"></i> Back to Cart</a>
                    <?php else: ?>
                        <div class="alert doc-warning payment-ok"><i class="fa-solid fa-id-card"></i><div><?= e($requiredDocumentMessage) ?></div></div>
                        <a class="btn-white upload-doc-btn" href="my_profile.php?tab=documents"><i class="fa-solid fa-upload"></i> Upload Documents in My Profile</a>
                        <button class="btn-pay-disabled" type="button" disabled><i class="fa-solid fa-lock"></i> Pay Now</button>
                    <?php endif; ?>
                </aside>
            </div>
        </form>

        <div class="payment-method-modal" id="paymentMethodModal" aria-hidden="true">
            <div class="payment-method-box">
                <div class="payment-method-head">
                    <div>
                        <span class="pill"><i class="fa-solid fa-lock"></i> Secure Payment</span>
                        <h2 id="paymentModalTitle">Online Banking</h2>
                        <p>The payable amount is fixed from your cart summary.</p>
                    </div>
                    <button type="button" class="payment-modal-close" id="paymentModalClose"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="payment-method-body">
                    <div class="fixed-amount-card">
                        <span>Amount to Pay</span>
                        <strong><?= e(money($summary["grand"])) ?></strong>
                    </div>

                    <div class="payment-panel active" data-panel="banking">
                        <h3><i class="fa-solid fa-building-columns"></i> Choose Your Bank</h3>
                        <div class="option-grid bank-grid">
                            <button type="button" class="pay-option active">Maybank</button>
                            <button type="button" class="pay-option">CIMB Bank</button>
                            <button type="button" class="pay-option">Public Bank</button>
                            <button type="button" class="pay-option">RHB Bank</button>
                            <button type="button" class="pay-option">Hong Leong Bank</button>
                            <button type="button" class="pay-option">Bank Islam</button>
                            <button type="button" class="pay-option">AmBank</button>
                            <button type="button" class="pay-option">UOB Bank</button>
                        </div>
                        <div class="pay-form-grid">
                            <label>Payer Name<input type="text" data-pay-field="Payer Name" placeholder="Enter payer full name" maxlength="80"></label>
                            <label>Username / User ID<input type="text" data-pay-field="Username / User ID" placeholder="5-30 letters, numbers, . _ -" maxlength="30"></label>
                            <label>Password<input type="password" data-pay-field="Password" placeholder="At least 8 characters" minlength="8" maxlength="64"></label>
                            <label>Mobile Number<input type="text" data-pay-field="Mobile Number" placeholder="Example: 0123456789" inputmode="numeric" maxlength="12"></label>
                        </div>
                    </div>

                    <div class="payment-panel" data-panel="card">
                        <h3><i class="fa-solid fa-credit-card"></i> Card Payment</h3>
                        <div class="pay-form-grid">
                            <label>Cardholder Name<input type="text" data-pay-field="Cardholder Name" placeholder="Name on card" maxlength="80"></label>
                            <label>Card Number<input type="text" data-pay-field="Card Number" placeholder="0000 0000 0000 0000" inputmode="numeric" maxlength="19"></label>
                            <label>Expiry Date<input type="text" data-pay-field="Expiry Date" placeholder="MM/YY" inputmode="numeric" maxlength="5"></label>
                            <label>CVV<input type="password" data-pay-field="CVV" placeholder="3 digits" inputmode="numeric" maxlength="3"></label>
                            <label class="full-pay-field">Billing Email<input type="email" data-pay-field="Billing Email" placeholder="customer@email.com" maxlength="120"></label>
                        </div>
                    </div>

                    <div class="payment-panel" data-panel="ewallet">
                        <h3><i class="fa-solid fa-mobile-screen-button"></i> Choose Your E-Wallet</h3>
                        <div class="option-grid wallet-grid">
                            <button type="button" class="pay-option active">Touch 'n Go eWallet</button>
                            <button type="button" class="pay-option">GrabPay</button>
                            <button type="button" class="pay-option">Boost</button>
                            <button type="button" class="pay-option">ShopeePay</button>
                            <button type="button" class="pay-option">MAE</button>
                            <button type="button" class="pay-option">BigPay</button>
                            <button type="button" class="pay-option">GXBank</button>
                            <button type="button" class="pay-option">Setel</button>
                        </div>
                        <div class="pay-form-grid">
                            <label>Payer Name<input type="text" data-pay-field="Payer Name" placeholder="Enter payer full name" maxlength="80"></label>
                            <label>Wallet Phone Number<input type="text" data-pay-field="Wallet Phone Number" placeholder="Example: 0123456789" inputmode="numeric" maxlength="12"></label>
                            <label class="full-pay-field">Wallet PIN<input type="password" data-pay-field="Wallet PIN" placeholder="6-digit wallet PIN" inputmode="numeric" maxlength="6"></label>
                        </div>
                    </div>
                </div>
                <div class="payment-method-footer">
                    <button type="button" class="btn-white" id="paymentModalCancel"><i class="fa-solid fa-arrow-left"></i> Back</button>
                    <button type="button" class="btn modal-confirm" id="paymentModalConfirm"><i class="fa-solid fa-check"></i> Confirm Method</button>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>
<footer class="footer"><div class="footer-inner"><div><h3>KH Car Rental</h3><p>KH Car Rental provides reliable, affordable and convenient car rental services across Johor, Melaka and Kuala Lumpur. Customers can search available cars, compare vehicles and manage bookings easily through our online system.</p><a href="catalogue.php" class="start-btn"><i class="fa-solid fa-car-side"></i> START Browse</a></div><div><h3>Quick Links</h3><p><a href="homepage.php" class="footer-hover-link">HOME</a></p><p><a href="catalogue.php" class="footer-hover-link">CATALOGUE</a></p><p><a href="find_car_smart.php" class="footer-hover-link">FIND CAR SMART</a></p><p><a href="compare_car.php" class="footer-hover-link">COMPARE CAR</a></p><p><a href="aboutus.php" class="footer-hover-link">ABOUT US</a></p><p><a href="contactus.php" class="footer-hover-link">CONTACT US</a></p><p><a href="cart.php" class="footer-hover-link">CART</a></p></div><div><h3>Contact</h3><p><a href="tel:+60123456789" class="contact-link"><i class="fa-solid fa-phone"></i> +60 12-345 6789</a></p><p><a href="mailto:hoomenghui@student.mmu.edu.my" class="contact-link"><i class="fa-solid fa-envelope"></i> hoomenghui@student.mmu.edu.my</a></p><p><a href="mailto:pangkanghorng@student.mmu.edu.my" class="contact-link"><i class="fa-solid fa-envelope"></i> pangkanghorng@student.mmu.edu.my</a></p><p><a href="mailto:ngmengxin@student.mmu.edu.my" class="contact-link"><i class="fa-solid fa-envelope"></i> ngmengxin@student.mmu.edu.my</a></p><p><a href="https://maps.google.com/?q=Multimedia+University+Melaka" target="_blank" class="contact-link"><i class="fa-solid fa-location-dot"></i> Multimedia University, Melaka</a></p></div></div><div class="footer-bottom">© 2026 KH Car Rental. All rights reserved.</div></footer><button class="back-top" type="button" id="backTop"><i class="fa-solid fa-arrow-up"></i></button><script>const avatarBtn=document.getElementById('avatarBtn'),profileDropdown=document.getElementById('profileDropdown');if(avatarBtn&&profileDropdown){avatarBtn.addEventListener('click',e=>{e.stopPropagation();profileDropdown.classList.toggle('show')});document.addEventListener('click',()=>profileDropdown.classList.remove('show'))}const backTop=document.getElementById('backTop');if(backTop){backTop.addEventListener('click',()=>window.scrollTo({top:0,behavior:'smooth'}))}</script><script>
(function(){
    const modal=document.getElementById('paymentMethodModal');
    if(!modal) return;
    const title=document.getElementById('paymentModalTitle');
    const closeBtn=document.getElementById('paymentModalClose');
    const cancelBtn=document.getElementById('paymentModalCancel');
    const confirmBtn=document.getElementById('paymentModalConfirm');
    const methodLabels=document.querySelectorAll('.payment-method-option');
    const panels=document.querySelectorAll('.payment-panel');
    const titleMap={banking:'Online Banking',card:'Credit / Debit Card',ewallet:'E-Wallet'};
    function openModal(method){
        panels.forEach(p=>p.classList.toggle('active',p.dataset.panel===method));
        if(title) title.textContent=titleMap[method]||'Payment Method';
        modal.classList.add('show');
        modal.setAttribute('aria-hidden','false');
    }
    function closeModal(){
        modal.classList.remove('show');
        modal.setAttribute('aria-hidden','true');
    }
    methodLabels.forEach(label=>{
        const input=label.querySelector('input[type="radio"]');
        label.addEventListener('click',()=>{
            if(input) input.checked=true;
            if(paymentConfirmed) paymentConfirmed.value='0';
            if(paymentDetailJson) paymentDetailJson.value='';
            if(selectedPaymentDetails) selectedPaymentDetails.style.display='none';
            openModal(label.dataset.method||'banking');
        });
    });
    document.querySelectorAll('.pay-option').forEach(btn=>{
        btn.addEventListener('click',()=>{
            const group=btn.closest('.option-grid');
            if(group) group.querySelectorAll('.pay-option').forEach(b=>b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
    const selectedPaymentDetails=document.getElementById('selectedPaymentDetails');
    const selectedPaymentGrid=document.getElementById('selectedPaymentGrid');
    const paymentConfirmed=document.getElementById('paymentConfirmed');
    const paymentDetailJson=document.getElementById('paymentDetailJson');
    const paymentForm=document.getElementById('paymentForm');
    const digitsOnly=value=>String(value||'').replace(/\D/g,'');
    function paymentFieldError(panel,input,value){
        const fieldName=input.dataset.payField || '';
        const panelName=panel.dataset.panel || '';
        const digits=digitsOnly(value);
        if(value.trim()==='') return 'Please fill payment information before payment.';
        if((fieldName==='Payer Name' || fieldName==='Cardholder Name') && value.trim().length<2) return 'Please enter a valid payer name.';
        if(panelName==='banking'){
            if(fieldName==='Username / User ID' && !/^[A-Za-z0-9._-]{5,30}$/.test(value.trim())) return 'Online banking username must be 5 to 30 characters.';
            if(fieldName==='Password' && value.length<8) return 'Online banking password must be at least 8 characters.';
            if(fieldName==='Mobile Number' && (digits.length<10 || digits.length>12)) return 'Online banking mobile number must contain 10 to 12 digits.';
        }
        if(panelName==='card'){
            if(fieldName==='Card Number' && digits.length!==16) return 'Card number must contain exactly 16 digits.';
            if(fieldName==='Expiry Date'){
                const match=value.trim().match(/^(0[1-9]|1[0-2])\s*\/?\s*(\d{2})$/);
                if(!match) return 'Card expiry date must use MM/YY format.';
                const expiryDate=new Date(2000+Number(match[2]), Number(match[1]), 0, 23, 59, 59);
                if(expiryDate < new Date()) return 'Card expiry date cannot be expired.';
            }
            if(fieldName==='CVV' && digits.length!==3) return 'CVV must contain exactly 3 digits.';
            if(fieldName==='Billing Email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value.trim())) return 'Billing email format is invalid.';
        }
        if(panelName==='ewallet'){
            if(fieldName==='Wallet Phone Number' && (digits.length<10 || digits.length>12)) return 'Wallet phone number must contain 10 to 12 digits.';
            if(fieldName==='Wallet PIN' && digits.length!==6) return 'Wallet PIN must contain exactly 6 digits.';
        }
        return '';
    }
    function maskPaymentValue(label,value){
        if(!value) return '-';
        const lower=label.toLowerCase();
        if(lower.includes('password') || lower.includes('pin') || lower.includes('cvv')) return '******';
        if(lower.includes('card number')) return value.replace(/\d(?=\d{4})/g,'*');
        return value;
    }
    document.querySelectorAll('input[data-pay-field]').forEach(input=>{
        input.addEventListener('input',()=>{
            const fieldName=input.dataset.payField || '';
            if(['Mobile Number','Wallet Phone Number','Wallet PIN','CVV'].includes(fieldName)){
                input.value=digitsOnly(input.value).slice(0, Number(input.maxLength || 99));
            }
            if(fieldName==='Card Number'){
                input.value=digitsOnly(input.value).slice(0,16).replace(/(.{4})/g,'$1 ').trim();
            }
            if(fieldName==='Expiry Date'){
                const digits=digitsOnly(input.value).slice(0,4);
                input.value=digits.length>2 ? digits.slice(0,2)+'/'+digits.slice(2) : digits;
            }
        });
    });
    function maskValue(label,value){
        if(!value) return '-';
        if(label.toLowerCase().includes('password')||label.toLowerCase().includes('pin')) return '••••••';
        if(label.toLowerCase().includes('card number')) return value.replace(/\d(?=\d{4})/g,'•');
        return value;
    }
    function confirmPaymentDetails(){
        const activePanel=document.querySelector('.payment-panel.active');
        if(!activePanel) { closeModal(); return; }
        const selectedMethod=document.querySelector('.payment-method-option input[type="radio"]:checked');
        const methodName=selectedMethod ? selectedMethod.value : (title ? title.textContent : 'Payment Method');
        const selectedOption=activePanel.querySelector('.pay-option.active');
        const optionLabel=selectedOption ? selectedOption.textContent.trim() : '';
        const fields=[];
        const rawDetails={method:methodName};
        fields.push(['Payment Method',methodName]);
        if(optionLabel){
            const optionKey=activePanel.dataset.panel==='banking'?'Bank':activePanel.dataset.panel==='ewallet'?'E-Wallet':'Provider';
            fields.push([optionKey,optionLabel]);
            rawDetails[optionKey]=optionLabel;
        }
        let firstInvalid=null;
        let firstInvalidMessage='';
        activePanel.querySelectorAll('input[data-pay-field]').forEach(input=>{
            const fieldName=input.dataset.payField || 'Detail';
            const value=input.value.trim();
            const validationError=paymentFieldError(activePanel,input,value);
            if(validationError){
                input.classList.add('error');
                if(!firstInvalid){
                    firstInvalid=input;
                    firstInvalidMessage=validationError;
                }
            }else{
                input.classList.remove('error');
            }
            fields.push([fieldName,maskPaymentValue(fieldName,value)]);
            rawDetails[fieldName]=value;
        });
        if(firstInvalid){
            alert(firstInvalidMessage || 'Please fill payment information before payment.');
            firstInvalid.focus();
            return;
        }
        if(selectedPaymentGrid){
            selectedPaymentGrid.innerHTML=fields.map(([k,v])=>`<div class="selected-payment-item"><span>${k}</span><strong>${v}</strong></div>`).join('');
        }
        if(selectedPaymentDetails) selectedPaymentDetails.style.display='block';
        if(paymentConfirmed) paymentConfirmed.value='1';
        if(paymentDetailJson) paymentDetailJson.value=JSON.stringify(rawDetails);
        closeModal();
    }
    if(closeBtn) closeBtn.addEventListener('click',closeModal);
    if(cancelBtn) cancelBtn.addEventListener('click',closeModal);

    if(paymentForm){
        paymentForm.addEventListener('submit',e=>{
            const rentalTerms=document.getElementById('acceptRentalTerms');
            const rentalTermsBox=document.getElementById('rentalTermsBox');
            if(rentalTerms && !rentalTerms.checked){
                e.preventDefault();
                rentalTermsBox?.classList.add('invalid-field');
                alert('Please agree to the rental Terms & Conditions before payment.');
                rentalTermsBox?.scrollIntoView({behavior:'smooth',block:'center'});
                return;
            }
            rentalTermsBox?.classList.remove('invalid-field');
            if(paymentConfirmed && paymentConfirmed.value!=='1'){
                e.preventDefault();
                alert('Please fill payment information before payment.');
                const checked=document.querySelector('.payment-method-option input[type="radio"]:checked');
                const label=checked ? checked.closest('.payment-method-option') : document.querySelector('.payment-method-option');
                if(label) openModal(label.dataset.method||'banking');
            }
        });
    }
    if(confirmBtn) confirmBtn.addEventListener('click',confirmPaymentDetails);
    modal.addEventListener('click',e=>{if(e.target===modal) closeModal();});
    document.addEventListener('keydown',e=>{if(e.key==='Escape') closeModal();});
})();
</script></body></html>
